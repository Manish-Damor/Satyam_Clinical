<?php
/**
 * Convert Purchase Order to Purchase Invoice
 * This action takes an approved PO and creates a corresponding invoice with the same items and prices.
 * The invoice can then be edited (to add batch numbers on receipt) and approved to create stock batches.
 */
// convert functionality disabled as per latest requirements
// require_once 'core.php';

echo json_encode(['success'=>false,'error'=>'Convert functionality disabled']);
exit;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
    exit;
}

if (!isset($_POST['po_id'])) {
    echo json_encode(['success' => false, 'error' => 'PO ID required']);
    exit;
}

$poId = intval($_POST['po_id']);
$userId = $_SESSION['userId'] ?? 0;

global $connect;

try {
    // Start transaction
    $connect->begin_transaction();

    // Fetch PO details
    $poStmt = $connect->prepare("SELECT * FROM purchase_orders WHERE po_id = ? AND po_status = 'Approved'");
    if (!$poStmt) throw new Exception("Database error: " . $connect->error);
    
    $poStmt->bind_param('i', $poId);
    $poStmt->execute();
    $poResult = $poStmt->get_result();
    $poStmt->close();

    if ($poResult->num_rows === 0) {
        throw new Exception('PO not found or not approved');
    }

    $po = $poResult->fetch_assoc();

    // Fetch PO items
    $itemsStmt = $connect->prepare("
        SELECT poi.*, p.hsn_code, p.gst_rate 
        FROM po_items poi
        LEFT JOIN product p ON poi.product_id = p.product_id
        WHERE poi.po_id = ?
    ");
    if (!$itemsStmt) throw new Exception("Database error: " . $connect->error);
    
    $itemsStmt->bind_param('i', $poId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $itemsStmt->close();

    $poItems = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $poItems[] = $row;
    }

    if (count($poItems) === 0) {
        throw new Exception('PO has no items');
    }

    // Generate unique invoice number
    $year = date('y');
    $invSql = "SELECT MAX(CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED)) as maxInv FROM purchase_invoices WHERE YEAR(invoice_date) = YEAR(NOW())";
    $invResult = $connect->query($invSql);
    $invRow = $invResult->fetch_assoc();
    $nextInvNum = (isset($invRow['maxInv']) && $invRow['maxInv']) ? $invRow['maxInv'] + 1 : 1;
    $invoiceNo = 'INV-' . $year . '-' . str_pad($nextInvNum, 5, '0', STR_PAD_LEFT);

    // Get supplier state if needed for GST determination
    $supStmt = $connect->prepare("SELECT state FROM suppliers WHERE supplier_id = ?");
    if (!$supStmt) throw new Exception("Database error: " . $connect->error);
    
    $supStmt->bind_param('i', $po['supplier_id']);
    $supStmt->execute();
    $supResult = $supStmt->get_result();
    $supStmt->close();
    
    $supplier = $supResult->fetch_assoc();
    $supplierState = $supplier['state'] ?? 'Gujarat';
    $companyState = 'Gujarat'; // Hardcoded for now; can be from config later
    
    // Determine GST type
    $gstType = (strtolower($supplierState) === strtolower($companyState)) ? 'intrastate' : 'interstate';

    // Create invoice header
    $invoiceStmt = $connect->prepare("
        INSERT INTO purchase_invoices (
            supplier_id, invoice_no, supplier_invoice_no, supplier_invoice_date,
            invoice_date, po_reference, place_of_supply, gst_determination_type,
            is_gst_registered, subtotal, total_discount, total_tax, freight,
            round_off, grand_total, payment_mode, status, notes, created_by, 
            created_at, supplier_location_state, company_location_state,
            total_cgst, total_sgst, total_igst
        ) VALUES (?, ?, '', ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 0, ?, 'Credit', ?, ?, 1, NOW(), ?, ?, 0, 0, ?)
    ");
    
    if (!$invoiceStmt) throw new Exception("Database error: prepare invoice: " . $connect->error);

    $invoiceDate = date('Y-m-d');
    $draftStatus = 'Draft';
    $subtotal = floatval($po['subtotal'] ?? 0);
    $totalDisc = floatval($po['discount_amount'] ?? 0);
    $totalTax = floatval($po['gst_amount'] ?? 0);
    $grandTotal = $subtotal - $totalDisc + $totalTax;
    $freight = floatval($po['other_charges'] ?? 0);
    $poReference = $po['po_number'];
    $notes = "Converted from PO {$po['po_number']}";

    $invoiceStmt->bind_param(
        'isdddsddsdssssssd',
        $po['supplier_id'], $invoiceNo, $invoiceDate,
        $invoiceDate, $poReference, $supplierState, $gstType,
        $subtotal, $totalDisc, $totalTax, $freight, $grandTotal,
        $draftStatus, $notes, $supplierState, $companyState, $totalTax
    );

    if (!$invoiceStmt->execute()) {
        throw new Exception("Failed to create invoice: " . $invoiceStmt->error);
    }

    $invoiceId = $connect->insert_id;
    $invoiceStmt->close();

    // Copy PO items to invoice items
    $itemsInsertedCount = 0;
    foreach ($poItems as $poItem) {
        $productId = $poItem['product_id'];
        $qty = floatval($poItem['quantity_ordered']);
        $unitCost = floatval($poItem['unit_price']);
        $lineAmount = $qty * $unitCost;
        $discountPct = floatval($poItem['discount_percent'] ?? 0);
        $discountAmt = ($lineAmount * $discountPct) / 100;
        $taxableAmt = $lineAmount - $discountAmt;
        $taxRate = floatval($poItem['gst_rate'] ?? 0);
        
        // Determine CGST/SGST/IGST split
        $cgstAmt = 0;
        $sgstAmt = 0;
        $igstAmt = 0;
        $cgstPct = 0;
        $sgstPct = 0;
        $igstPct = 0;
        
        if ($gstType === 'intrastate') {
            $cgstPct = $taxRate / 2;
            $sgstPct = $taxRate / 2;
            $cgstAmt = ($taxableAmt * $cgstPct) / 100;
            $sgstAmt = ($taxableAmt * $sgstPct) / 100;
        } else {
            $igstPct = $taxRate;
            $igstAmt = ($taxableAmt * $taxRate) / 100;
        }

        $taxAmt = $cgstAmt + $sgstAmt + $igstAmt;
        $lineTotal = $taxableAmt + $taxAmt;

        $itemSql = "
            INSERT INTO purchase_invoice_items (
                invoice_id, product_id, product_name, hsn_code,
                qty, unit_cost, effective_rate, mrp,
                discount_percent, discount_amount, taxable_value,
                tax_rate, cgst_percent, sgst_percent, igst_percent,
                cgst_amount, sgst_amount, igst_amount, tax_amount,
                line_total, product_gst_rate, margin_percent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $itemStmt = $connect->prepare($itemSql);
        if (!$itemStmt) throw new Exception("Failed to prepare item statement");

        $productName = $poItem['product_name'] ?? '';
        $hsnCode = $poItem['hsn_code'] ?? '';
        $marginPct = 0; // Will be calculated on invoice approval

        $itemStmt->bind_param(
            'iissdddddddddddddddddd',
            $invoiceId, $productId, $productName, $hsnCode,
            $qty, $unitCost, $unitCost, 0,
            $discountPct, $discountAmt, $taxableAmt,
            $taxRate, $cgstPct, $sgstPct, $igstPct,
            $cgstAmt, $sgstAmt, $igstAmt, $taxAmt,
            $lineTotal, $taxRate, $marginPct
        );

        if ($itemStmt->execute()) {
            $itemsInsertedCount++;
        } else {
            throw new Exception("Failed to insert invoice item");
        }
        $itemStmt->close();
    }

    // Update PO status to indicate conversion
    $poUpdateStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Converted' WHERE po_id = ?");
    if (!$poUpdateStmt) throw new Exception("Database error");
    $poUpdateStmt->bind_param('i', $poId);
    $poUpdateStmt->execute();
    $poUpdateStmt->close();

    $connect->commit();

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoiceId,
        'invoice_no' => $invoiceNo,
        'message' => "PO converted to Invoice {$invoiceNo} with {$itemsInsertedCount} items. Go to invoice to add batch details."
    ]);

} catch (Exception $e) {
    $connect->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
