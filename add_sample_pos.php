<?php
// Add sample data to purchase_orders table
require_once __DIR__ . '/php_action/core.php';

echo "=== Adding Sample Purchase Orders ===\n\n";

// Get a supplier ID for testing
$suppRes = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' LIMIT 5");
$suppliers = [];
while ($row = $suppRes->fetch_assoc()) {
    $suppliers[] = $row;
}

if (empty($suppliers)) {
    die("ERROR: No active suppliers found. Please add suppliers first.\n");
}

echo "Found " . count($suppliers) . " active suppliers\n\n";

// Sample POs data
$samplePOs = [
    [
        'po_number' => 'PO-26-0001',
        'po_date' => date('Y-m-d', strtotime('-10 days')),
        'expected_delivery_date' => date('Y-m-d', strtotime('+10 days')),
        'delivery_location' => 'Main Warehouse, Ahmedabad',
        'subtotal' => 50000,
        'discount_percentage' => 5,
        'discount_amount' => 2500,
        'gst_percentage' => 5,
        'gst_amount' => 2375,
        'other_charges' => 500,
        'po_status' => 'Draft',
        'payment_status' => 'NotDue',
        'notes' => 'Important medicines for inventory replenishment'
    ],
    [
        'po_number' => 'PO-26-0002',
        'po_date' => date('Y-m-d', strtotime('-5 days')),
        'expected_delivery_date' => date('Y-m-d', strtotime('+5 days')),
        'delivery_location' => 'Branch Office, Surat',
        'subtotal' => 75000,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'gst_percentage' => 12,
        'gst_amount' => 9000,
        'other_charges' => 1000,
        'po_status' => 'Approved',
        'payment_status' => 'Due',
        'notes' => 'Pharma supplies for branch'
    ],
    [
        'po_number' => 'PO-26-0003',
        'po_date' => date('Y-m-d', strtotime('-2 days')),
        'expected_delivery_date' => date('Y-m-d', strtotime('+8 days')),
        'delivery_location' => 'Main Warehouse, Ahmedabad',
        'subtotal' => 100000,
        'discount_percentage' => 10,
        'discount_amount' => 10000,
        'gst_percentage' => 18,
        'gst_amount' => 16200,
        'other_charges' => 2000,
        'po_status' => 'Submitted',
        'payment_status' => 'NotDue',
        'notes' => 'Bulk order for monthly stock'
    ],
    [
        'po_number' => 'PO-26-0004',
        'po_date' => date('Y-m-d', strtotime('-15 days')),
        'expected_delivery_date' => date('Y-m-d', strtotime('-2 days')),
        'delivery_location' => 'Main Warehouse, Ahmedabad',
        'subtotal' => 30000,
        'discount_percentage' => 2,
        'discount_amount' => 600,
        'gst_percentage' => 5,
        'gst_amount' => 1470,
        'other_charges' => 200,
        'po_status' => 'Received',
        'payment_status' => 'PartialPaid',
        'notes' => 'First consignment received'
    ],
    [
        'po_number' => 'PO-26-0005',
        'po_date' => date('Y-m-d', strtotime('-20 days')),
        'expected_delivery_date' => date('Y-m-d', strtotime('-5 days')),
        'delivery_location' => 'Main Warehouse, Ahmedabad',
        'subtotal' => 45000,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'gst_percentage' => 12,
        'gst_amount' => 5400,
        'other_charges' => 800,
        'po_status' => 'Received',
        'payment_status' => 'Paid',
        'notes' => 'Completed and fully paid'
    ]
];

// Calculate grand totals
foreach ($samplePOs as &$po) {
    $po['grand_total'] = $po['subtotal'] - $po['discount_amount'] + $po['gst_amount'] + $po['other_charges'];
}

// Insert POs
$insertCount = 0;
foreach ($samplePOs as $idx => $po) {
    // Use rotating suppliers
    $supplier = $suppliers[$idx % count($suppliers)];
    $supplierId = $supplier['supplier_id'];
    
    $sql = "INSERT INTO purchase_orders (
                po_number, po_date, supplier_id, expected_delivery_date, delivery_location,
                subtotal, discount_percentage, discount_amount, gst_percentage, gst_amount,
                other_charges, grand_total, po_status, payment_status, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $userId = 1;
    $subtotal = (double)$po['subtotal'];
    $discount_pct = (double)$po['discount_percentage'];
    $discount_amt = (double)$po['discount_amount'];
    $gst_pct = (double)$po['gst_percentage'];
    $gst_amt = (double)$po['gst_amount'];
    $other_chrg = (double)$po['other_charges'];
    $grand_tot = (double)$po['grand_total'];
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'ssisdddddddssssi',
        $po['po_number'], $po['po_date'], $supplierId, $po['expected_delivery_date'],
        $po['delivery_location'], $subtotal, $discount_pct,
        $discount_amt, $gst_pct, $gst_amt,
        $other_chrg, $grand_tot, $po['po_status'], 
        $po['payment_status'], $po['notes'], $userId
    );
    
    if ($stmt->execute()) {
        $poId = $connect->insert_id;
        $insertCount++;
        echo "✓ Inserted PO: {$po['po_number']} (ID: $poId) for {$supplier['supplier_name']}\n";
        
        // Add sample items to PO
        $numItems = rand(2, 4);
        $itemSubtotal = $po['subtotal'] / $numItems;
        
        for ($i = 0; $i < $numItems; $i++) {
            $itemSql = "INSERT INTO po_items (
                            po_id, product_id, quantity_ordered, quantity_received,
                            unit_price, total_price, item_status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $itemStmt = $connect->prepare($itemSql);
            
            $productIds = [1, 2, 3, 4, 5, 7, 8];  // Valid product IDs from database
            $productId = $productIds[$i % count($productIds)];
            $qty_ordered = rand(50, 200);
            $qty_received = 0;  // Nothing received yet
            $unit_price = $itemSubtotal / $qty_ordered;
            $total_price = $qty_ordered * $unit_price;
            $item_status = 'Pending';  // Items are pending delivery
            
            $itemStmt->bind_param(
                'iiiddds',
                $poId, $productId, $qty_ordered, $qty_received,
                $unit_price, $total_price, $item_status
            );
            
            if (!$itemStmt->execute()) {
                echo "  ├─ Error adding item: " . $itemStmt->error . "\n";
            }
            $itemStmt->close();
        }
        
        echo "  ├─ Added $numItems items\n\n";
    } else {
        echo "✗ Error inserting PO {$po['po_number']}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "\n=== Summary ===\n";
echo "Total POs Inserted: $insertCount\n";
echo "\nPurchase Orders are now ready for testing!\n";
echo "Navigate to: po_list.php to view all purchase orders\n";

?>
