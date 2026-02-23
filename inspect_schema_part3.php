<?php
require './constant/connect.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       CUSTOMERS TABLE & FINAL SCHEMA CHECK                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Check customers table
echo "[1] CUSTOMERS TABLE STRUCTURE:\n";
$res = $connect->query("SHOW COLUMNS FROM customers");
if ($res) {
    while($col = $res->fetch_assoc()) {
        echo "  • {$col['Field']}: {$col['Type']} ({$col['Null']}) {$col['Extra']}\n";
    }
}

// Sample customer
echo "\n[2] SAMPLE CUSTOMER RECORD:\n";
$res = $connect->query("SELECT * FROM customers LIMIT 1");
if ($res && $res->num_rows > 0) {
    $cust = $res->fetch_assoc();
    foreach($cust as $k => $v) {
        echo "  • $k: $v\n";
    }
} else {
    echo "  (No customers yet)\n";
}

// Check customer count
echo "\n[3] CUSTOMER DATA COUNT:\n";
$res = $connect->query("SELECT COUNT(*) as cnt FROM customers");
$row = $res->fetch_assoc();
echo "  Total customers: {$row['cnt']}\n";

// Check order_item count
echo "\n[4] ORDER_ITEM DATA COUNT:\n";
$res = $connect->query("SELECT COUNT(*) as cnt FROM order_item");
$row = $res->fetch_assoc();
echo "  Total order items: {$row['cnt']}\n";

echo "\n════════════════════════════════════════════════════════════════\n";
?>
