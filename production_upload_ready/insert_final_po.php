<?php
require_once 'php_action/core.php';

echo "=== Inserting Remaining PO ===\n\n";

// Get the 5th supplier
$suppRes = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' LIMIT 5");
$suppliers = [];
while ($row = $suppRes->fetch_assoc()) {
    $suppliers[] = $row;
}

if (count($suppliers) >= 5) {
    $supplier = $suppliers[4];
    $supplierId = $supplier['supplier_id'];
    
    // PO-26-0005 data
    $po_number = 'PO-26-0005';
    $po_date = date('Y-m-d', strtotime('-20 days'));
    $expected_delivery = date('Y-m-d', strtotime('-5 days'));
    $delivery_location = 'Main Warehouse, Ahmedabad';
    $subtotal = 45000.00;
    $discount_pct = 0.00;
    $discount_amt = 0.00;
    $gst_pct = 12.00;
    $gst_amt = 5400.00;
    $other_charges = 800.00;
    $grand_total = 51200.00;
    $po_status = 'Received';
    $payment_status = 'Paid';
    $notes = 'Completed and fully paid';
    $userId = 1;
    
    $sql = "INSERT INTO purchase_orders (
                po_number, po_date, supplier_id, expected_delivery_date, delivery_location,
                subtotal, discount_percentage, discount_amount, gst_percentage, gst_amount,
                other_charges, grand_total, po_status, payment_status, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'ssisdddddddssssi',
        $po_number, $po_date, $supplierId, $expected_delivery,
        $delivery_location, $subtotal, $discount_pct,
        $discount_amt, $gst_pct, $gst_amt,
        $other_charges, $grand_total, $po_status,
        $payment_status, $notes, $userId
    );
    
    if ($stmt->execute()) {
        $poId = $connect->insert_id;
        echo "✓ Inserted PO: $po_number (ID: $poId) for {$supplier['supplier_name']}\n";
        
        // Add items
        $productIds = [1, 2, 3, 4, 5, 7, 8];
        $itemSubtotal = $subtotal / 3;
        
        for ($i = 0; $i < 3; $i++) {
            $productId = $productIds[$i % count($productIds)];
            $qty_ordered = rand(50, 200);
            $qty_received = $qty_ordered;  // All received for this PO
            $unit_price = $itemSubtotal / $qty_ordered;
            $total_price = $qty_ordered * $unit_price;
            $item_status = 'Received';
            
            $itemSql = "INSERT INTO po_items (
                            po_id, product_id, quantity_ordered, quantity_received,
                            unit_price, total_price, item_status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $itemStmt = $connect->prepare($itemSql);
            $itemStmt->bind_param(
                'iiiddds',
                $poId, $productId, $qty_ordered, $qty_received,
                $unit_price, $total_price, $item_status
            );
            $itemStmt->execute();
            $itemStmt->close();
        }
        
        echo "✓ Added 3 items\n";
        echo "\n=== All 5 Sample POs Successfully Inserted ===\n";
    } else {
        echo "✗ Error: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} else {
    echo "Error: Not enough suppliers\n";
}
