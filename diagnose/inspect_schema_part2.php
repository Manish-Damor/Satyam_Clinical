<?php
require './constant/connect.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         DETAILED SCHEMA INVESTIGATION - PART 2                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Check order_item table
echo "[1] ORDER_ITEM TABLE STRUCTURE:\n";
$res = $connect->query("SHOW COLUMNS FROM order_item");
if ($res) {
    while($col = $res->fetch_assoc()) {
        echo "  • {$col['Field']}: {$col['Type']} ({$col['Null']}) {$col['Extra']}\n";
    }
} else {
    echo "  ERROR: Table not found\n";
}

// Sample order item
echo "\n[2] SAMPLE ORDER_ITEM RECORD:\n";
$res = $connect->query("SELECT * FROM order_item LIMIT 1");
if ($res && $res->num_rows > 0) {
    $item = $res->fetch_assoc();
    foreach($item as $k => $v) {
        echo "  • $k: $v\n";
    }
} else {
    echo "  (No order items yet)\n";
}

// Search for PTR in product table or elsewhere
echo "\n[3] SEARCHING FOR PTR FIELD:\n";
$res = $connect->query("SHOW COLUMNS FROM product LIKE '%ptr%'");
if ($res && $res->num_rows > 0) {
    echo "  ✓ Found PTR-related field in product table\n";
    while($col = $res->fetch_assoc()) {
        echo "    • {$col['Field']}\n";
    }
} else {
    echo "  ✗ No PTR field found in product table\n";
}

// Check all columns in product that might be price-related
echo "\n[4] PRICE-RELATED COLUMNS IN PRODUCT:\n";
$res = $connect->query("SHOW COLUMNS FROM product");
while($col = $res->fetch_assoc()) {
    if (stripos($col['Field'], 'price') !== false || stripos($col['Field'], 'mrp') !== false || stripos($col['Field'], 'cost') !== false || stripos($col['Field'], 'rate') !== false) {
        echo "  • {$col['Field']}: {$col['Type']}\n";
    }
}

// Check invoice_payments structure
echo "\n[5] INVOICE_PAYMENTS TABLE:\n";
$res = $connect->query("SHOW COLUMNS FROM invoice_payments");
while($col = $res->fetch_assoc()) {
    echo "  • {$col['Field']}: {$col['Type']}\n";
}

// Check if there's a customer/client table
echo "\n[6] SEARCHING FOR CUSTOMER/CLIENT TABLES:\n";
$res = $connect->query("SHOW TABLES LIKE '%customer%'");
$found = false;
while($t = $res->fetch_row()) {
    echo "  ✓ Found: {$t[0]}\n";
    $found = true;
}
if (!$found) {
    echo "  ✗ No customer/client tables found\n";
}

// Check purchase_invoices for comparison
echo "\n[7] PURCHASE_INVOICES TABLE STRUCTURE:\n";
$res = $connect->query("SHOW COLUMNS FROM purchase_invoices");
while($col = $res->fetch_assoc()) {
    echo "  • {$col['Field']}: {$col['Type']}\n";
}

// Check purchase_invoice_items
echo "\n[8] PURCHASE_INVOICE_ITEMS TABLE STRUCTURE:\n";
$res = $connect->query("SHOW COLUMNS FROM purchase_invoice_items");
while($col = $res->fetch_assoc()) {
    echo "  • {$col['Field']}: {$col['Type']}\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
?>
