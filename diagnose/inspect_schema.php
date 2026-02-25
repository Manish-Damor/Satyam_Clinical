<?php
require './constant/connect.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║            LIVE DATABASE SCHEMA INSPECTION                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Check orders table
echo "[1] ORDERS TABLE STRUCTURE:\n";
$res = $connect->query("SHOW COLUMNS FROM orders");
if ($res) {
    while($col = $res->fetch_assoc()) {
        echo "  • {$col['Field']}: {$col['Type']} ({$col['Null']}) {$col['Extra']}\n";
    }
}

// Check all tables like order
echo "\n[2] RELATED TABLES:\n";
$res = $connect->query("SHOW TABLES");
while($t = $res->fetch_row()) {
    if (strpos(strtolower($t[0]), 'order') !== false || strpos(strtolower($t[0]), 'client') !== false || strpos(strtolower($t[0]), 'invoice') !== false) {
        echo "  • {$t[0]}\n";
    }
}

// Check clients table
echo "\n[3] CHECKING FOR CLIENTS TABLE:\n";
$res = $connect->query("SHOW TABLES LIKE 'clients'");
if ($res->num_rows > 0) {
    echo "  ✓ CLIENTS TABLE EXISTS\n";
    $res2 = $connect->query("SHOW COLUMNS FROM clients");
    while($col = $res2->fetch_assoc()) {
        echo "    • {$col['Field']}: {$col['Type']}\n";
    }
} else {
    echo "  ✗ CLIENTS TABLE MISSING - NEEDS TO BE CREATED\n";
}

// Check product table for PTR
echo "\n[4] PRODUCT TABLE COLUMNS (checking for PTR):\n";
$res = $connect->query("SHOW COLUMNS FROM product");
while($col = $res->fetch_assoc()) {
    echo "  • {$col['Field']}: {$col['Type']}\n";
}

// Check current orders
echo "\n[5] CURRENT ORDERS DATA:\n";
$res = $connect->query("SELECT COUNT(*) as cnt, MAX(id) as max_id FROM orders");
$row = $res->fetch_assoc();
echo "  Total orders: {$row['cnt']}\n";
echo "  Max ID: {$row['max_id']}\n";

// Sample order
echo "\n[6] SAMPLE ORDER RECORD:\n";
$res = $connect->query("SELECT * FROM orders LIMIT 1");
if ($res->num_rows > 0) {
    $order = $res->fetch_assoc();
    foreach($order as $k => $v) {
        echo "  • $k: $v\n";
    }
} else {
    echo "  (No orders yet)\n";
}

// Check order items storage
echo "\n[7] CHECKING ORDER ITEMS STORAGE:\n";
$tables = ['order_items', 'orders_items', 'orderitems', 'order_details'];
$found = false;
foreach ($tables as $t) {
    $res = $connect->query("SHOW TABLES LIKE '$t'");
    if ($res && $res->num_rows > 0) {
        echo "  ✓ Found: $t\n";
        $res2 = $connect->query("SHOW COLUMNS FROM $t");
        while($col = $res2->fetch_assoc()) {
            echo "    • {$col['Field']}: {$col['Type']}\n";
        }
        $found = true;
    }
}
if (!$found) {
    echo "  ✗ NO SEPARATE ORDER_ITEMS TABLE - Items likely in orders table or JSON format\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
?>
