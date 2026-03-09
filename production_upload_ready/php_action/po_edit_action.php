<?php
// Edit purchase invoice action handler (Draft-only).
require_once __DIR__ . '/json_core.php';

header('Content-Type: application/json');

function respond_json($payload, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respond_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$invoiceId = intval($_POST['invoice_id'] ?? 0);
$items = $_POST['items'] ?? null;

if ($invoiceId <= 0 || !is_array($items)) {
    respond_json(['success' => false, 'error' => 'Missing required fields']);
}

if (count($items) === 0) {
    respond_json(['success' => false, 'error' => 'At least one item is required']);
}

$invStmt = $connect->prepare("SELECT id, status FROM purchase_invoices WHERE id = ? LIMIT 1");
if (!$invStmt) {
    respond_json(['success' => false, 'error' => 'Database error: ' . $connect->error]);
}
$invStmt->bind_param('i', $invoiceId);
$invStmt->execute();
$invRes = $invStmt->get_result();
$invoiceRow = ($invRes && $invRes->num_rows > 0) ? $invRes->fetch_assoc() : null;
$invStmt->close();

if (!$invoiceRow) {
    respond_json(['success' => false, 'error' => 'Invoice not found']);
}

if (($invoiceRow['status'] ?? '') !== 'Draft') {
    respond_json(['success' => false, 'error' => 'Only Draft invoices can be edited']);
}

$invoiceNo = trim((string)($_POST['invoice_no'] ?? ''));
$supplierId = intval($_POST['supplier_id'] ?? 0);
$invoiceDate = trim((string)($_POST['invoice_date'] ?? ''));
$dueDate = trim((string)($_POST['due_date'] ?? ''));
$poReference = trim((string)($_POST['po_reference'] ?? ''));
$grnReference = trim((string)($_POST['grn_reference'] ?? ''));
$gstType = trim((string)($_POST['gst_determination_type'] ?? ''));
$paymentMode = trim((string)($_POST['payment_mode'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));
$freight = floatval($_POST['freight'] ?? 0);
$roundOff = floatval($_POST['round_off'] ?? 0);
$manualDiscount = max(0, floatval($_POST['total_discount'] ?? 0));
$paidAmountInput = max(0, floatval($_POST['paid_amount'] ?? 0));

if ($invoiceNo === '') {
    respond_json(['success' => false, 'error' => 'Invoice number is required']);
}
if ($supplierId <= 0) {
    respond_json(['success' => false, 'error' => 'Supplier is required']);
}

$invoiceDateObj = DateTime::createFromFormat('Y-m-d', $invoiceDate);
if (!$invoiceDateObj || $invoiceDateObj->format('Y-m-d') !== $invoiceDate) {
    respond_json(['success' => false, 'error' => 'Invalid invoice date']);
}

if ($dueDate !== '') {
    $dueDateObj = DateTime::createFromFormat('Y-m-d', $dueDate);
    if (!$dueDateObj || $dueDateObj->format('Y-m-d') !== $dueDate) {
        respond_json(['success' => false, 'error' => 'Invalid due date']);
    }
    if ($dueDateObj < $invoiceDateObj) {
        respond_json(['success' => false, 'error' => 'Due date cannot be before invoice date']);
    }
} else {
    $dueDate = null;
}

$validGstTypes = ['intrastate', 'interstate'];
if (!in_array($gstType, $validGstTypes, true)) {
    respond_json(['success' => false, 'error' => 'Invalid GST type']);
}

$validPaymentModes = ['Cash', 'Credit', 'Bank', 'Cheque', 'UPI'];
if ($paymentMode === '') {
    $paymentMode = 'Credit';
}
if (!in_array($paymentMode, $validPaymentModes, true)) {
    respond_json(['success' => false, 'error' => 'Invalid payment mode']);
}

if ($poReference === '') {
    $poReference = null;
}
if ($grnReference === '') {
    $grnReference = null;
}
if ($notes === '') {
    $notes = null;
}

$subtotal = 0.0;
$totalCgst = 0.0;
$totalSgst = 0.0;
$totalIgst = 0.0;
$itemsInserted = 0;

$connect->begin_transaction();

try {
    // Draft edits should not touch stock tables. Replace invoice item rows only.
    $deleteItemsStmt = $connect->prepare("DELETE FROM purchase_invoice_items WHERE invoice_id = ?");
    if (!$deleteItemsStmt) {
        throw new Exception('Unable to delete existing items: ' . $connect->error);
    }
    $deleteItemsStmt->bind_param('i', $invoiceId);
    if (!$deleteItemsStmt->execute()) {
        throw new Exception('Failed deleting existing items: ' . $deleteItemsStmt->error);
    }
    $deleteItemsStmt->close();

    $productStmt = $connect->prepare(
        "SELECT p.product_id, p.product_name, p.hsn_code, p.gst_rate, COALESCE(p.pack_size, '') AS pack_size,
                COALESCE(b.brand_name, '') AS brand_name
         FROM product p
         LEFT JOIN brands b ON b.brand_id = p.brand_id
         WHERE p.product_id = ? AND p.status = 1
         LIMIT 1"
    );
    if (!$productStmt) {
        throw new Exception('Unable to prepare product lookup: ' . $connect->error);
    }

    $itemInsertSql = "INSERT INTO purchase_invoice_items (
            invoice_id, product_id, product_name, pack_size_snapshot, manufacturer_snapshot,
            hsn_code, batch_no, manufacture_date, expiry_date,
            qty, free_qty, unit_cost, effective_rate, mrp,
            discount_percent, discount_amount, taxable_value,
            cgst_percent, sgst_percent, igst_percent,
            cgst_amount, sgst_amount, igst_amount,
            tax_rate, tax_amount, line_total,
            product_gst_rate, supplier_quoted_mrp, margin_amount, margin_percent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $itemStmt = $connect->prepare($itemInsertSql);
    if (!$itemStmt) {
        throw new Exception('Unable to prepare item insert: ' . $connect->error);
    }

    $productCache = [];
    $seenItems = [];

    foreach ($items as $idx => $itemRaw) {
        $rowNo = $idx + 1;

        $productId = intval($itemRaw['product_id'] ?? 0);
        $batchNo = trim((string)($itemRaw['batch_no'] ?? ''));
        $expiryDate = trim((string)($itemRaw['expiry_date'] ?? ''));
        $manufactureDate = trim((string)($itemRaw['manufacture_date'] ?? ''));
        $qty = floatval($itemRaw['qty'] ?? 0);
        $freeQty = max(0, floatval($itemRaw['free_qty'] ?? 0));
        $unitCost = floatval($itemRaw['unit_cost'] ?? 0);
        $mrp = floatval($itemRaw['mrp'] ?? 0);

        if ($productId <= 0) {
            throw new Exception("Item {$rowNo}: Invalid product");
        }
        if ($batchNo === '') {
            throw new Exception("Item {$rowNo}: Batch number is required");
        }
        if ($expiryDate === '') {
            throw new Exception("Item {$rowNo}: Expiry date is required");
        }
        if ($qty <= 0) {
            throw new Exception("Item {$rowNo}: Quantity must be greater than 0");
        }
        if ($unitCost <= 0) {
            throw new Exception("Item {$rowNo}: Unit cost must be greater than 0");
        }
        if ($mrp <= 0) {
            throw new Exception("Item {$rowNo}: MRP must be greater than 0");
        }
        if ($unitCost > $mrp) {
            throw new Exception("Item {$rowNo}: Unit cost cannot be greater than MRP");
        }

        $expiryDateObj = DateTime::createFromFormat('Y-m-d', $expiryDate);
        if (!$expiryDateObj || $expiryDateObj->format('Y-m-d') !== $expiryDate) {
            throw new Exception("Item {$rowNo}: Invalid expiry date");
        }
        if ($expiryDateObj <= $invoiceDateObj) {
            throw new Exception("Item {$rowNo}: Expiry date must be after invoice date");
        }

        if ($manufactureDate !== '') {
            $mfgObj = DateTime::createFromFormat('Y-m-d', $manufactureDate);
            if (!$mfgObj || $mfgObj->format('Y-m-d') !== $manufactureDate) {
                throw new Exception("Item {$rowNo}: Invalid manufacture date");
            }
        } else {
            $manufactureDate = null;
        }

        $duplicateKey = $productId . '|' . strtoupper($batchNo);
        if (isset($seenItems[$duplicateKey])) {
            throw new Exception("Item {$rowNo}: Duplicate medicine and batch combination");
        }
        $seenItems[$duplicateKey] = true;

        if (!isset($productCache[$productId])) {
            $productStmt->bind_param('i', $productId);
            $productStmt->execute();
            $prodRes = $productStmt->get_result();
            $product = ($prodRes && $prodRes->num_rows > 0) ? $prodRes->fetch_assoc() : null;
            if (!$product) {
                throw new Exception("Item {$rowNo}: Product not found or inactive");
            }
            $productCache[$productId] = $product;
        }

        $product = $productCache[$productId];

        $postedTaxRate = isset($itemRaw['tax_rate']) ? floatval($itemRaw['tax_rate']) : null;
        $taxRate = ($postedTaxRate !== null) ? $postedTaxRate : floatval($product['gst_rate'] ?? 0);
        if ($taxRate < 0 || $taxRate > 100) {
            throw new Exception("Item {$rowNo}: Invalid tax rate");
        }

        $discountPercent = max(0, floatval($itemRaw['discount_percent'] ?? 0));
        if ($discountPercent > 100) {
            throw new Exception("Item {$rowNo}: Discount percent cannot exceed 100");
        }

        $lineAmount = $qty * $unitCost;
        $discountAmount = ($lineAmount * $discountPercent) / 100;
        $taxableValue = $lineAmount - $discountAmount;

        $cgstPercent = 0.0;
        $sgstPercent = 0.0;
        $igstPercent = 0.0;
        $cgstAmount = 0.0;
        $sgstAmount = 0.0;
        $igstAmount = 0.0;

        if ($gstType === 'intrastate') {
            $cgstPercent = $taxRate / 2;
            $sgstPercent = $taxRate / 2;
            $cgstAmount = ($taxableValue * $cgstPercent) / 100;
            $sgstAmount = ($taxableValue * $sgstPercent) / 100;
        } else {
            $igstPercent = $taxRate;
            $igstAmount = ($taxableValue * $igstPercent) / 100;
        }

        $taxAmount = $cgstAmount + $sgstAmount + $igstAmount;
        $lineTotal = $taxableValue + $taxAmount;
        $effectiveRate = (($qty + $freeQty) > 0) ? (($qty * $unitCost) / ($qty + $freeQty)) : $unitCost;

        $subtotal += $lineAmount;
        $totalCgst += $cgstAmount;
        $totalSgst += $sgstAmount;
        $totalIgst += $igstAmount;

        $productName = trim((string)($itemRaw['product_name'] ?? ''));
        if ($productName === '') {
            $productName = (string)($product['product_name'] ?? '');
        }

        $packSnapshot = trim((string)($itemRaw['pack_size_snapshot'] ?? ($product['pack_size'] ?? '')));
        if ($packSnapshot === '') {
            $packSnapshot = null;
        }

        $manufacturerSnapshot = trim((string)($itemRaw['manufacturer_snapshot'] ?? ($product['brand_name'] ?? '')));
        if ($manufacturerSnapshot === '') {
            $manufacturerSnapshot = null;
        }

        $hsnCode = trim((string)($itemRaw['hsn_code'] ?? ($product['hsn_code'] ?? '')));
        if ($hsnCode === '') {
            $hsnCode = null;
        }

        $productGstRate = floatval($product['gst_rate'] ?? $taxRate);
        $supplierQuotedMrp = $mrp;
        $marginAmount = $mrp - $unitCost;
        $marginPercent = ($unitCost > 0) ? (($marginAmount / $unitCost) * 100) : 0;

        $itemTypes = 'iisssssss' . str_repeat('d', 21);
        $itemStmt->bind_param(
            $itemTypes,
            $invoiceId,
            $productId,
            $productName,
            $packSnapshot,
            $manufacturerSnapshot,
            $hsnCode,
            $batchNo,
            $manufactureDate,
            $expiryDate,
            $qty,
            $freeQty,
            $unitCost,
            $effectiveRate,
            $mrp,
            $discountPercent,
            $discountAmount,
            $taxableValue,
            $cgstPercent,
            $sgstPercent,
            $igstPercent,
            $cgstAmount,
            $sgstAmount,
            $igstAmount,
            $taxRate,
            $taxAmount,
            $lineTotal,
            $productGstRate,
            $supplierQuotedMrp,
            $marginAmount,
            $marginPercent
        );

        if (!$itemStmt->execute()) {
            throw new Exception("Item {$rowNo}: Failed to save item - " . $itemStmt->error);
        }

        $itemsInserted++;
    }

    $productStmt->close();
    $itemStmt->close();

    if ($itemsInserted <= 0) {
        throw new Exception('No valid items were saved');
    }

    $totalDiscount = min($manualDiscount, $subtotal);
    $totalTax = $totalCgst + $totalSgst + $totalIgst;
    $grandTotal = $subtotal - $totalDiscount + $totalTax + $freight + $roundOff;

    if ($grandTotal < 0) {
        $grandTotal = 0;
    }

    $paidAmount = min($paidAmountInput, $grandTotal);
    $outstandingAmount = $grandTotal - $paidAmount;

    $paymentStatus = 'UNPAID';
    if (abs($paidAmount) <= 0.00001) {
        $paymentStatus = 'UNPAID';
    } elseif (abs($paidAmount - $grandTotal) <= 0.01) {
        $paymentStatus = 'PAID';
    } else {
        $paymentStatus = 'PARTIAL';
    }

    if ($paymentStatus !== 'UNPAID' && $paymentMode === 'Credit') {
        throw new Exception('Payment mode must be non-credit when paid amount is greater than 0');
    }
    if ($paymentStatus === 'UNPAID') {
        $paymentMode = 'Credit';
    }

    $updateSql = "UPDATE purchase_invoices SET
            invoice_no = ?,
            supplier_id = ?,
            invoice_date = ?,
            due_date = ?,
            po_reference = ?,
            grn_reference = ?,
            gst_determination_type = ?,
            payment_mode = ?,
            payment_status = ?,
            notes = ?,
            subtotal = ?,
            total_discount = ?,
            total_tax = ?,
            total_cgst = ?,
            total_sgst = ?,
            total_igst = ?,
            freight = ?,
            round_off = ?,
            grand_total = ?,
            paid_amount = ?,
            outstanding_amount = ?,
            updated_at = NOW()
        WHERE id = ?";

    $updateStmt = $connect->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception('Unable to update invoice header: ' . $connect->error);
    }

    $updateTypes = 'sissssssss' . str_repeat('d', 11) . 'i';
    $updateStmt->bind_param(
        $updateTypes,
        $invoiceNo,
        $supplierId,
        $invoiceDate,
        $dueDate,
        $poReference,
        $grnReference,
        $gstType,
        $paymentMode,
        $paymentStatus,
        $notes,
        $subtotal,
        $totalDiscount,
        $totalTax,
        $totalCgst,
        $totalSgst,
        $totalIgst,
        $freight,
        $roundOff,
        $grandTotal,
        $paidAmount,
        $outstandingAmount,
        $invoiceId
    );

    if (!$updateStmt->execute()) {
        throw new Exception('Failed updating invoice header: ' . $updateStmt->error);
    }
    $updateStmt->close();

    $connect->commit();

    respond_json([
        'success' => true,
        'invoice_id' => $invoiceId,
        'message' => 'Invoice updated successfully'
    ]);
} catch (Throwable $e) {
    $connect->rollback();
    respond_json(['success' => false, 'error' => $e->getMessage()]);
}
