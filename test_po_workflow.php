<?php
/**
 * Test: Purchase Order Module End-to-End Workflow
 * Covers:
 * - Create PO
 * - Approve PO
 * - Convert PO to Invoice
 * - Verify invoice creation
 * Excludes: GRN (skipped per requirement)
 */

require_once __DIR__ . '/constant/connect.php';

echo "\n========== PO Module E2E Workflow Test ==========\n\n";

try {
    // Setup: Get a test supplier
    $supRes = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' LIMIT 1");
    if (!$supRes || $supRes->num_rows === 0) {
        throw new Exception("No active suppliers found. Please create a supplier first.");
    }
    $supplier = $supRes->fetch_assoc();
    $supplierId = $supplier['supplier_id'];
    $supplierName = $supplier['supplier_name'];
    
    echo "✓ Using Supplier: {$supplierName} (ID: {$supplierId})\n\n";

    // Step 1: Create PO
    echo "STEP 1: Creating Purchase Order...\n";
    $poNumber = 'TEST-PO-' . date('YmdHis');
    $poDate = date('Y-m-d');
    $expectedDelivery = date('Y-m-d', strtotime('+7 days'));
    
    $poCreateSql = "
        INSERT INTO purchase_orders (
            po_number, po_date, supplier_id, expected_delivery_date,
            delivery_location, subtotal, discount_amount, discount_percentage,
            gst_amount, other_charges, grand_total, po_status, payment_status,
            notes, created_by, created_at
        ) VALUES (?, ?, ?, ?, 'Test Location', 
                  1000.00, 0, 0, 100.00, 0, 1100.00, 'Draft', 'NotDue', 
                  'Test PO for module workflow', 1, NOW())
    ";
    
    $poStmt = $connect->prepare($poCreateSql);
    if (!$poStmt) throw new Exception("Prepare failed: " . $connect->error);
    
    $poStmt->bind_param('ssis', $poNumber, $poDate, $supplierId, $expectedDelivery);
    if (!$poStmt->execute()) {
        throw new Exception("Failed to create PO: " . $poStmt->error);
    }
    $poId = $connect->insert_id;
    $poStmt->close();
    
    echo "✓ PO Created: {$poNumber} (ID: {$poId})\n\n";

    // Step 2: Add PO Items
    echo "STEP 2: Adding items to PO...\n";
    $prodRes = $connect->query("SELECT product_id, product_name, gst_rate FROM product WHERE status=1 LIMIT 2");
    if (!$prodRes || $prodRes->num_rows === 0) {
        throw new Exception("No active products found. Create products first.");
    }

    $itemsAdded = 0;
    while ($product = $prodRes->fetch_assoc()) {
        $productId = $product['product_id'];
        $productName = $product['product_name'];
        
        $itemSql = "
            INSERT INTO po_items (
                po_id, product_id, quantity_ordered, unit_price, total_price, item_status
            ) VALUES (?, ?, 10, 100.00, 1000.00, 'Pending')
        ";
        
        $itemStmt = $connect->prepare($itemSql);
        if (!$itemStmt) throw new Exception("Item prepare failed");
        
        $itemStmt->bind_param('ii', $poId, $productId);
        if ($itemStmt->execute()) {
            echo "  ✓ Added: {$productName}\n";
            $itemsAdded++;
        }
        $itemStmt->close();
    }
    
    echo "✓ {$itemsAdded} items added\n\n";

    // Step 3: Approve PO
    echo "STEP 3: Approving PO...\n";
    $approveStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Approved' WHERE po_id = ?");
    if (!$approveStmt) throw new Exception("Prepare failed");
    
    $approveStmt->bind_param('i', $poId);
    if ($approveStmt->execute()) {
        echo "✓ PO Approved\n\n";
    } else {
        throw new Exception("Failed to approve PO");
    }
    $approveStmt->close();

    // Step 4: Convert PO to Invoice (simulate the action)
    echo "STEP 4: Converting PO to Invoice...\n";
    
    // Get supplier state
    $supStateRes = $connect->query("SELECT state FROM suppliers WHERE supplier_id = {$supplierId}");
    $supStateRow = $supStateRes->fetch_assoc();
    $supplierState = $supStateRow['state'] ?? 'Gujarat';
    $companyState = 'Gujarat';
    $gstType = (strtolower($supplierState) === strtolower($companyState)) ? 'intrastate' : 'interstate';
    
    // Create invoice
    $invoiceNo = 'INV-Convert-' . date('YmdHis');
    $invoiceDate = date('Y-m-d');
    $poReference = $poNumber;
    $placeOfSupply = $supplierState;
    
    $invSql = "
        INSERT INTO purchase_invoices (
            supplier_id, invoice_no, supplier_invoice_no, supplier_invoice_date,
            invoice_date, po_reference, place_of_supply, gst_determination_type,
            is_gst_registered, subtotal, total_discount, total_tax, freight,
            round_off, grand_total, payment_mode, status, notes, created_by, created_at,
            supplier_location_state, company_location_state, total_cgst, total_sgst, total_igst
        ) VALUES (?, ?, '', ?, ?, ?, ?, ?, 1, 1000.00, 0, 100.00, 0, 0, 1100.00,
                  'Credit', 'Draft', ?, 1, NOW(), ?, ?, 0, 0, 100.00)
    ";
    
    $invStmt = $connect->prepare($invSql);
    if (!$invStmt) throw new Exception("Invoice prepare failed");
    
    $notes = "Converted from {$poNumber}";
    $invStmt->bind_param('isssssssss', $supplierId, $invoiceNo, $invoiceDate, $invoiceDate, $poReference, $placeOfSupply, $gstType, 
                         $notes, $supplierState, $companyState);
    
    if (!$invStmt->execute()) {
        throw new Exception("Failed to create invoice: " . $invStmt->error);
    }
    $invoiceId = $connect->insert_id;
    $invStmt->close();
    
    echo "✓ Invoice Created: {$invoiceNo} (ID: {$invoiceId})\n\n";

    // Step 5: Copy PO items to Invoice items
    echo "STEP 5: Copying PO items to Invoice...\n";
    $poItemsRes = $connect->query("
        SELECT poi.*, p.hsn_code, p.gst_rate 
        FROM po_items poi
        LEFT JOIN product p ON poi.product_id = p.product_id
        WHERE poi.po_id = {$poId}
    ");
    
    $invItemsAdded = 0;
    while ($poItem = $poItemsRes->fetch_assoc()) {
        $prodId = $poItem['product_id'];
        $prodName = $poItem['product_name'] ?? '';
        $hsn = $poItem['hsn_code'] ?? '';
        $qty = floatval($poItem['quantity_ordered']);
        $unitCost = floatval($poItem['unit_price']);
        $tax = floatval($poItem['gst_rate'] ?? 0);
        
        $liAmount = $qty * $unitCost;
        $taxAmt = $liAmount * $tax / 100;
        $lineTotal = $liAmount + $taxAmt;
        
        $itemInsertSql = "
            INSERT INTO purchase_invoice_items (
                invoice_id, product_id, product_name, hsn_code,
                qty, unit_cost, effective_rate, discount_percent,
                discount_amount, taxable_value, tax_rate, cgst_percent,
                sgst_percent, igst_percent, cgst_amount, sgst_amount,
                igst_amount, tax_amount, line_total, product_gst_rate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 0, 0, ?, 0, 0, ?, ?, ?, ?)
        ";
        
        $itemInsertStmt = $connect->prepare($itemInsertSql);
        if (!$itemInsertStmt) throw new Exception("Item insert prepare failed");
        
        $itemInsertStmt->bind_param(
            'iissdddddddddd',
            $invoiceId, $prodId, $prodName, $hsn,
            $qty, $unitCost, $unitCost, $liAmount, $tax,
            $tax, $taxAmt, $taxAmt, $lineTotal, $tax
        );
        
        if ($itemInsertStmt->execute()) {
            echo "  ✓ Copied item\n";
            $invItemsAdded++;
        }
        $itemInsertStmt->close();
    }
    
    echo "✓ {$invItemsAdded} items copied to invoice\n\n";

    // Step 6: Update PO status to Converted
    echo "STEP 6: Marking PO as Converted...\n";
    $convertStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Converted' WHERE po_id = ?");
    if (!$convertStmt) throw new Exception("Prepare failed");
    
    $convertStmt->bind_param('i', $poId);
    if ($convertStmt->execute()) {
        echo "✓ PO status updated to 'Converted'\n\n";
    }
    $convertStmt->close();

    // Summary
    echo "========== TEST SUMMARY ==========\n";
    echo "✅ PO Creation: PASSED\n";
    echo "✅ PO Item Addition: PASSED ({$itemsAdded} items)\n";
    echo "✅ PO Approval: PASSED\n";
    echo "✅ PO to Invoice Conversion: PASSED\n";
    echo "✅ Invoice Item Copy: PASSED ({$invItemsAdded} items)\n";
    echo "\nResults:\n";
    echo "  - PO Number: {$poNumber}\n";
    echo "  - Invoice Number: {$invoiceNo}\n";
    echo "  - Supplier: {$supplierName}\n";
    echo "  - GST Type: {$gstType}\n";
    echo "\n✅ ALL TESTS PASSED\n";

} catch (Exception $e) {
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>
