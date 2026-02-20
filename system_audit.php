<?php
require_once 'php_action/core.php';

echo "=== SYSTEM AUDIT REPORT ===\n\n";

// 1. Check Database Tables
echo "1. DATABASE TABLES\n";
echo "=================\n";
$tables = ['purchase_orders', 'po_items', 'purchase_invoices', 'purchase_invoice_items', 'suppliers'];
foreach ($tables as $table) {
    $res = $connect->query("SELECT COUNT(*) as cnt FROM $table");
    $row = $res->fetch_assoc();
    echo "✓ $table: " . $row['cnt'] . " records\n";
}

// 2. Check PO Status and Payment Status Enums
echo "\n2. PURCHASE ORDER ENUM VALUES\n";
echo "=============================\n";
$res = $connect->query("SHOW COLUMNS FROM purchase_orders WHERE Field='po_status'");
$col = $res->fetch_assoc();
echo "po_status: " . $col['Type'] . "\n";

$res = $connect->query("SHOW COLUMNS FROM purchase_orders WHERE Field='payment_status'");
$col = $res->fetch_assoc();
echo "payment_status: " . $col['Type'] . "\n";

// 3. Check if po_items has proper product links
echo "\n3. PO ITEMS STRUCTURE\n";
echo "====================\n";
$res = $connect->query("SELECT po_item_id, po_id, product_id, quantity_ordered, item_status FROM po_items LIMIT 3");
if ($res) {
    echo "Found " . $res->num_rows . " sample po_items\n";
    while ($row = $res->fetch_assoc()) {
        echo "  PO Item #" . $row['po_item_id'] . " | PO: " . $row['po_id'] . " | Product: " . $row['product_id'] . " | Qty: " . $row['quantity_ordered'] . " | Status: " . $row['item_status'] . "\n";
    }
}

// 4. Check Invoice Status
echo "\n4. PURCHASE INVOICE STATUS\n";
echo "==========================\n";
$res = $connect->query("SHOW COLUMNS FROM purchase_invoices WHERE Field='status'");
$col = $res->fetch_assoc();
echo "Status column: " . $col['Type'] . "\n";

// 5. Check Create PO page
echo "\n5. CREATE PO PAGE\n";
echo "=================\n";
if (file_exists('create_po.php')) {
    echo "✓ create_po.php exists\n";
    $content = file_get_contents('create_po.php');
    if (strpos($content, 'purchase_orders') !== false) {
        echo "✓ Uses purchase_orders table\n";
    } else {
        echo "✗ May not use purchase_orders table correctly\n";
    }
} else {
    echo "✗ create_po.php NOT FOUND\n";
}

// 6. Check Edit PO page
echo "\n6. EDIT PO PAGE\n";
echo "===============\n";
if (file_exists('editorder.php')) {
    echo "✓ editorder.php exists\n";
} else {
    echo "✗ editorder.php NOT FOUND\n";
}

// 7. Check Action Handlers
echo "\n7. ACTION HANDLERS\n";
echo "==================\n";
if (file_exists('php_action/po_actions.php')) {
    $content = file_get_contents('php_action/po_actions.php');
    if (strpos($content, 'purchase_orders') !== false) {
        echo "✓ po_actions.php handles purchase_orders\n";
    } else {
        echo "✗ po_actions.php does NOT handle purchase_orders (likely invoices)\n";
    }
    
    if (strpos($content, 'approve_po') !== false) {
        echo "✓ Has approve_po action\n";
    } else {
        echo "✗ Missing approve_po action\n";
    }
    
    if (strpos($content, 'cancel_po') !== false) {
        echo "✓ Has cancel_po action\n";
    } else {
        echo "✗ Missing cancel_po action\n";
    }
} else {
    echo "✗ po_actions.php NOT FOUND\n";
}

// 8. Check Invoice Pages
echo "\n8. INVOICE PAGES\n";
echo "================\n";
$invoiceFiles = ['purchase_invoice.php', 'invoice_list.php', 'invoice_view.php', 'invoice_edit.php'];
foreach ($invoiceFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}

echo "\n=== END OF AUDIT ===\n";
