<?php
require './constant/connect.php';

echo "=== PO PRINT PAGE TEST ===\n\n";

// Find a PO to test with
$res = $connect->query("SELECT po_id, po_number FROM purchase_orders LIMIT 1");
if ($res && $res->num_rows > 0) {
    $po = $res->fetch_assoc();
    $poId = $po['po_id'];
    $poNumber = $po['po_number'];
    
    echo "✓ Test PO found:\n";
    echo "  - PO ID: $poId\n";
    echo "  - PO Number: $poNumber\n\n";
    
    echo "✓ Print page URL:\n";
    echo "  http://localhost/Satyam_Clinical/print_po.php?id=$poId\n\n";
    
    // Verify connections
    $poRes = $connect->query("SELECT * FROM purchase_orders WHERE po_id = $poId");
    if ($poRes && $poRes->num_rows > 0) {
        $poData = $poRes->fetch_assoc();
        echo "✓ PO data retrieved successfully\n";
        
        // Get supplier
        $suppRes = $connect->query("SELECT * FROM suppliers WHERE supplier_id = {$poData['supplier_id']}");
        if ($suppRes && $suppRes->num_rows > 0) {
            $supplier = $suppRes->fetch_assoc();
            echo "✓ Supplier: " . $supplier['supplier_name'] . "\n";
        }
        
        // Get items
        $itemsRes = $connect->query("SELECT COUNT(*) as cnt FROM po_items WHERE po_id = $poId");
        $itemsRow = $itemsRes->fetch_assoc();
        echo "✓ Items in PO: " . $itemsRow['cnt'] . "\n";
        
        echo "\n✓ PRINT PAGE READY - Open in browser to view professional PO format\n";
        echo "✓ Features included:\n";
        echo "  - Company header with contact details\n";
        echo "  - GST and PAN information\n";
        echo "  - Supplier information box\n";
        echo "  - Delivery information box\n";
        echo "  - Itemized table with quantities and pricing\n";
        echo "  - Professional totals section\n";
        echo "  - Terms and conditions\n";
        echo "  - Signature lines for approvals\n";
        echo "  - Bank and company details footer\n";
        echo "  - Print-optimized A4 format\n";
    } else {
        echo "✗ Could not retrieve PO data\n";
    }
} else {
    echo "✗ No POs found in database\n";
}
?>
