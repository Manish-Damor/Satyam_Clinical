<?php
// Test database import
$conn = new mysqli("localhost", "root", "", "satyam_clinical");

if ($conn->connect_error) {
    echo "Database Connection Failed: " . $conn->connect_error . "\n";
    exit(1);
}

// Check all 14 tables exist
$tables = [
    'brands',
    'categories', 
    'users',
    'suppliers',
    'product',
    'product_batches',
    'stock_movements',
    'purchase_orders',
    'po_items',
    'inventory_adjustments',
    'reorder_management',
    'expiry_tracking',
    'orders',
    'order_item'
];

echo "Checking database tables...\n";
echo "==============================\n\n";

$all_ok = true;
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' MISSING\n";
        $all_ok = false;
    }
}

echo "\n==============================\n";

// Check for PRIMARY KEYS
echo "\nChecking PRIMARY KEYS...\n";
echo "==============================\n\n";

$pk_check_tables = ['brands', 'categories', 'users', 'suppliers', 'product', 'product_batches', 'stock_movements', 'purchase_orders', 'po_items', 'inventory_adjustments', 'reorder_management', 'expiry_tracking', 'orders', 'order_item'];

foreach ($pk_check_tables as $table) {
    $result = $conn->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✓ Table '$table' has PRIMARY KEY on column '{$row['Column_name']}'\n";
    } else {
        echo "✗ Table '$table' MISSING PRIMARY KEY\n";
        $all_ok = false;
    }
}

echo "\n==============================\n";
if ($all_ok) {
    echo "\n✓ ALL CHECKS PASSED - Database is ready!\n";
} else {
    echo "\n✗ SOME CHECKS FAILED - Please review errors above\n";
}

$conn->close();
?>
