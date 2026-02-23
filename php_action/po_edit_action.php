<?php
// Edit purchase invoice action handler
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/purchase_invoice_action.php';

header('Content-Type: application/json');

if (!isset($_POST['invoice_id']) || !isset($_POST['items'])) {
    die(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

$invoiceId = intval($_POST['invoice_id']);
$items = $_POST['items'];

// Verify invoice exists and is in Draft status
$invRes = $connect->query("SELECT status FROM purchase_invoices WHERE id = $invoiceId");
if (!$invRes || $invRes->num_rows === 0) {
    die(json_encode(['success' => false, 'error' => 'Invoice not found']));
}

$inv = $invRes->fetch_assoc();
if ($inv['status'] !== 'Draft') {
    die(json_encode(['success' => false, 'error' => 'Only Draft invoices can be edited']));
}

// Prepare data array for validation
$data = [
    'supplier_id' => intval($_POST['supplier_id']),
    'invoice_no' => $_POST['invoice_no'],
    'invoice_date' => $_POST['invoice_date'],
    'gst_determination_type' => $_POST['gst_determination_type']
];

// Items validation
$validatedItems = [];
foreach ($items as $item) {
    if (empty($item['product_id']) || empty($item['batch_no']) || empty($item['qty'])) {
        die(json_encode(['success' => false, 'error' => 'All item fields are required']));
    }
    
    $validatedItems[] = [
        'product_id' => intval($item['product_id']),
        'batch_no' => $item['batch_no'],
        'expiry_date' => $item['expiry_date'],
        'qty' => floatval($item['qty']),
        'free_qty' => floatval($item['free_qty'] ?? 0),
        'unit_cost' => floatval($item['unit_cost']),
        'mrp' => floatval($item['mrp']),
        'tax_rate' => floatval($item['tax_rate'])
    ];
}

try {
    // Begin transaction
    $connect->begin_transaction();

    // ===== UPDATE INVOICE HEADER =====
    $updateSql = "UPDATE purchase_invoices SET 
                    invoice_no = ?,
                    supplier_id = ?,
                    invoice_date = ?,
                    due_date = ?,
                    po_reference = ?,
                    grn_reference = ?,
                    gst_determination_type = ?,
                    payment_mode = ?,
                    notes = ?,
                    updated_at = NOW()
                  WHERE id = ?";

    $stmt = $connect->prepare($updateSql);
    if (!$stmt) throw new Exception("Prepare failed: " . $connect->error);

    // Bind parameters
    $invoice_no = $_POST['invoice_no'];
    $supplier_id = intval($_POST['supplier_id']);
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'] ?? null;
    $po_ref = $_POST['po_reference'] ?? null;
    $grn_ref = $_POST['grn_reference'] ?? null;
    $gst_type = $_POST['gst_determination_type'];
    $payment_mode = $_POST['payment_mode'] ?? null;
    $notes = $_POST['notes'] ?? null;

    $stmt->bind_param('ssissssssi', $invoice_no, $supplier_id, $invoice_date, $due_date, $po_ref, $grn_ref, $gst_type, $payment_mode, $notes, $invoiceId);
    $stmt->execute();
    $stmt->close();

    // ===== DELETE OLD ITEMS AND STOCK BATCHES =====
    // Get old items to find stock batches
    $oldItems = $connect->query("SELECT invoice_item_id, product_id, batch_no FROM purchase_invoice_items WHERE invoice_id = $invoiceId");
    while ($oldItem = $oldItems->fetch_assoc()) {
        // Delete from stock_batches (soft delete by setting a flag or hard delete)
        $sbRes = $connect->query("SELECT id FROM stock_batches WHERE invoice_id = $invoiceId AND product_id = " . intval($oldItem['product_id']) . " AND batch_no = '" . $connect->real_escape_string($oldItem['batch_no']) . "'");
        while ($sb = $sbRes->fetch_assoc()) {
            $connect->query("DELETE FROM stock_batches WHERE id = " . intval($sb['id']));
        }
    }

    // Delete old items
    $connect->query("DELETE FROM purchase_invoice_items WHERE invoice_id = $invoiceId");

    // ===== INSERT NEW ITEMS AND CREATE/MERGE STOCK BATCHES =====
    $subtotal = 0;
    $totalCgst = 0;
    $totalSgst = 0;
    $totalIgst = 0;

    $itemInsertSql = "INSERT INTO purchase_invoice_items 
                    (invoice_id, product_id, hsn_code, batch_no, manufacture_date, expiry_date, qty, free_qty, unit_cost, effective_rate, mrp, tax_rate, tax_amount, margin_percent, line_total, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $itemStmt = $connect->prepare($itemInsertSql);

    foreach ($validatedItems as $item) {
        // Fetch product details
        $prodRes = $connect->query("SELECT product_id, product_name, hsn_code, gst_rate FROM product WHERE product_id = " . $item['product_id']);
        if (!$prodRes || $prodRes->num_rows === 0) {
            throw new Exception("Product not found: " . $item['product_id']);
        }
        $product = $prodRes->fetch_assoc();

        // Validate expiry > invoice_date
        if (strtotime($item['expiry_date']) <= strtotime($invoice_date)) {
            throw new Exception("Batch expiry date must be after invoice date");
        }

        // Calculate tax amount
        $lineAmount = $item['qty'] * $item['unit_cost'];
        $taxAmount = $lineAmount * ($item['tax_rate'] / 100);
        $lineTotal = $lineAmount + $taxAmount;

        // Calculate margin
        $margin = $item['unit_cost'] > 0 ? (($item['mrp'] - $item['unit_cost']) / $item['unit_cost']) * 100 : 0;

        // Add to totals
        $subtotal += $lineAmount;
        if ($gst_type === 'intrastate') {
            $totalCgst += $taxAmount / 2;
            $totalSgst += $taxAmount / 2;
        } else {
            $totalIgst += $taxAmount;
        }

        // Insert item
        $product_id = $item['product_id'];
        $hsn = $product['hsn_code'];
        $batch_no = $item['batch_no'];
        $mfg_date = null; // Manufacture date not provided in edit form
        $exp_date = $item['expiry_date'];
        $qty = $item['qty'];
        $unit_cost = $item['unit_cost'];
        $mrp = $item['mrp'];
        $tax_rate = $item['tax_rate'];
        $userId = $_SESSION['userId'] ?? null;

        // calculate effective_rate (free_qty assumed to be 0 if not provided)
        $freeQty = $item['free_qty'] ?? 0;
        $effective = ($qty + $freeQty) > 0 ? ($qty * $unit_cost) / ($qty + $freeQty) : $unit_cost;
        $itemStmt->bind_param('iissss' . 'ddddddddd' . 'i', $invoiceId, $product_id, $hsn, $batch_no, $mfg_date, $exp_date, $qty, $freeQty, $unit_cost, $effective, $mrp, $tax_rate, $taxAmount, $margin, $lineTotal, $userId);
        $itemStmt->execute();

        // Create or merge stock batch
        $checkSql = "SELECT id, qty FROM stock_batches WHERE product_id = ? AND batch_no = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param('is', $product_id, $batch_no);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $checkStmt->close();

        if ($result->num_rows > 0) {
            // Merge: add to existing batch
            $row = $result->fetch_assoc();
            $batch_id = $row['id'];
            $updateSql = "UPDATE stock_batches SET qty = qty + ? WHERE id = ?";
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param('di', $qty, $batch_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Create new batch
            $batchSql = "INSERT INTO stock_batches 
                         (product_id, batch_no, manufacture_date, expiry_date, qty, mrp, cost_price, supplier_id, invoice_id, gst_rate_applied, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $batchStmt = $connect->prepare($batchSql);
            $cost_price = $item['unit_cost'];
            $tax_rate_applied = $item['tax_rate'];
            $batchStmt->bind_param('isssddiiidi', $product_id, $batch_no, $mfg_date, $exp_date, $qty, $mrp, $cost_price, $supplier_id, $invoiceId, $tax_rate_applied, $userId);
            $batchStmt->execute();
            $batchStmt->close();
        }
    }
    $itemStmt->close();

    // ===== UPDATE TOTALS =====
    $discount = floatval($_POST['total_discount'] ?? 0);
    $freight = floatval($_POST['freight'] ?? 0);
    $roundOff = floatval($_POST['round_off'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);

    $grandTotal = $subtotal + $totalCgst + $totalSgst + $totalIgst + $freight + $roundOff - $discount;
    $outstanding = $grandTotal - $paidAmount;

    $totalsUpdateSql = "UPDATE purchase_invoices SET 
                         subtotal = ?,
                         total_cgst = ?,
                         total_sgst = ?,
                         total_igst = ?,
                         freight = ?,
                         round_off = ?,
                         total_discount = ?,
                         grand_total = ?,
                         paid_amount = ?,
                         outstanding_amount = ?,
                         updated_at = NOW()
                       WHERE id = ?";

    $totalsStmt = $connect->prepare($totalsUpdateSql);
    if (!$totalsStmt) throw new Exception("Prepare failed: " . $connect->error);

    $totalsStmt->bind_param('ddddddddddi', $subtotal, $totalCgst, $totalSgst, $totalIgst, $freight, $roundOff, $discount, $grandTotal, $paidAmount, $outstanding, $invoiceId);
    $totalsStmt->execute();
    $totalsStmt->close();

    // Commit transaction
    $connect->commit();

    die(json_encode(['success' => true, 'invoice_id' => $invoiceId, 'message' => 'Invoice updated successfully']));

} catch (Exception $e) {
    $connect->rollback();
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}

?>
