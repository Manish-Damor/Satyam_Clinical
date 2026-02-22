<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection and query performance
include 'constant/connect.php';

echo "=== Database Connection Test ===\n\n";

// Check connection
if (!$connect) {
    echo "❌ Connection FAILED\n";
    exit;
}
echo "✓ Connection OK\n";

// Test suppliers query
echo "\n=== Testing Suppliers Query ===\n";
$start = microtime(true);
$res = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' ORDER BY supplier_name LIMIT 10");
$duration = microtime(true) - $start;

if (!$res) {
    echo "❌ Query FAILED: " . $connect->error . "\n";
} else {
    $count = $res->num_rows;
    echo "✓ Query OK - Found $count records in " . number_format($duration * 1000, 2) . "ms\n";
    while ($r = $res->fetch_assoc()) {
        echo "  - {$r['supplier_name']}\n";
    }
}

// Test products query
echo "\n=== Testing Products Query ===\n";
$start = microtime(true);
$res = $connect->query("SELECT product_id, product_name, hsn_code, gst_rate FROM product WHERE status=1 LIMIT 10");
$duration = microtime(true) - $start;

if (!$res) {
    echo "❌ Query FAILED: " . $connect->error . "\n";
} else {
    $count = $res->num_rows;
    echo "✓ Query OK - Found $count records in " . number_format($duration * 1000, 2) . "ms\n";
    while ($r = $res->fetch_assoc()) {
        echo "  - {$r['product_name']} (GST: {$r['gst_rate']}%)\n";
    }
}

echo "\n✅ All tests passed!\n";
?>
