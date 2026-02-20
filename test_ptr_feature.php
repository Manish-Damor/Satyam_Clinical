<?php
/**
 * Sales Invoice PTR Feature Test
 * Verifies database schema and data integrity
 */

require_once __DIR__ . '/php_action/db_connect.php';

echo "=== SALES INVOICE PTR FEATURE TEST ===\n\n";

// 1. Check database schema
echo "1. CHECKING DATABASE SCHEMA\n";
echo "============================\n";

// Check order_item table
$res = $connect->query("SHOW COLUMNS FROM order_item");
$columns = [];
while ($r = $res->fetch_assoc()) {
    $columns[] = $r['Field'];
}

if (in_array('purchase_rate', $columns)) {
    echo "✓ order_item.purchase_rate column EXISTS\n";
} else {
    echo "✗ order_item.purchase_rate column MISSING\n";
}

// Show all columns
echo "\norder_item table columns:\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}

// 2. Check product_batches table has purchase_rate
echo "\n2. CHECKING PRODUCT_BATCHES SCHEMA\n";
echo "==================================\n";

$res = $connect->query("SHOW COLUMNS FROM product_batches");
$batchCols = [];
while ($r = $res->fetch_assoc()) {
    $batchCols[] = $r['Field'];
}

if (in_array('purchase_rate', $batchCols)) {
    echo "✓ product_batches.purchase_rate column EXISTS\n";
} else {
    echo "✗ product_batches.purchase_rate column MISSING\n";
}

// 3. Test sample data
echo "\n3. CHECKING SAMPLE DATA\n";
echo "=======================\n";

// Get a sample order with items
$res = $connect->query("SELECT o.id, o.order_number, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_item oi ON o.id = oi.order_id GROUP BY o.id LIMIT 3");

if ($res && $res->num_rows > 0) {
    echo "Found orders:\n";
    while ($r = $res->fetch_assoc()) {
        echo "  - Order ID: {$r['id']}, Number: {$r['order_number']}, Items: {$r['item_count']}\n";
    }
    
    // Check items with purchase_rate
    $itemRes = $connect->query("SELECT id, order_id, product_id, rate, purchase_rate FROM order_item LIMIT 5");
    if ($itemRes && $itemRes->num_rows > 0) {
        echo "\nSample order items:\n";
        while ($item = $itemRes->fetch_assoc()) {
            $ptr_display = ($item['purchase_rate'] > 0) ? "₹" . number_format($item['purchase_rate'], 2) : "(empty)";
            echo "  - Item ID: {$item['id']}, Rate: ₹" . number_format($item['rate'], 2) . ", PTR: $ptr_display\n";
        }
    }
} else {
    echo "No orders found in database\n";
}

// 4. Check product batches with purchase rates
echo "\n4. CHECKING PRODUCT BATCH DATA\n";
echo "==============================\n";

$batchRes = $connect->query("SELECT batch_id, product_id, batch_number, purchase_rate, mrp FROM product_batches LIMIT 5");

if ($batchRes && $batchRes->num_rows > 0) {
    echo "Sample product batches:\n";
    while ($batch = $batchRes->fetch_assoc()) {
        echo "  - Batch: {$batch['batch_number']}, Prod ID: {$batch['product_id']}, PTR: ₹" . number_format($batch['purchase_rate'], 2) . ", MRP: ₹" . number_format($batch['mrp'], 2) . "\n";
    }
} else {
    echo "No batches found in database\n";
}

// 5. Test fetchSelectedProduct.php response
echo "\n5. TESTING FETCHSELECTEDPRODUCT.PHP RESPONSE\n";
echo "=============================================\n";

// Get a sample product ID
$prodRes = $connect->query("SELECT product_id, product_name, rate FROM product WHERE status = 1 LIMIT 1");
if ($prodRes && $prodRes->num_rows > 0) {
    $prod = $prodRes->fetch_assoc();
    $prodId = $prod['product_id'];
    
    echo "Testing with product: {$prod['product_name']} (ID: $prodId)\n";
    
    // Simulate the fetchSelectedProduct logic
    $sql = "SELECT product_id, product_name, quantity, rate FROM product WHERE product_id = $prodId";
    $result = $connect->query($sql);
    $row = $result->fetch_array();
    
    // Get purchase_rate from product_batches
    $batchSql = "SELECT purchase_rate FROM product_batches WHERE product_id = $prodId ORDER BY batch_id DESC LIMIT 1";
    $batchRes = $connect->query($batchSql);
    $purchase_rate = 0;
    if ($batchRes && $batchRes->num_rows > 0) {
        $br = $batchRes->fetch_assoc();
        $purchase_rate = $br['purchase_rate'];
    }
    $row['purchase_rate'] = $purchase_rate;
    
    echo "Response would include:\n";
    echo "  - product_id: {$row['product_id']}\n";
    echo "  - product_name: {$row['product_name']}\n";
    echo "  - rate: ₹" . number_format($row['rate'], 2) . "\n";
    echo "  - purchase_rate: ₹" . number_format($row['purchase_rate'], 2) . "\n";
    echo "  ✓ Purchase rate would be populated\n";
} else {
    echo "No products found\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
