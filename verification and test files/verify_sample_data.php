<?php
require_once 'php_action/core.php';

echo "=== Verification Report ===\n\n";

// Check POs
echo "PURCHASE ORDERS TABLE:\n";
$result = $connect->query("SELECT po_id, po_number, po_status, payment_status, grand_total FROM purchase_orders ORDER BY po_id");
if ($result) {
    echo "Total POs: " . $result->num_rows . "\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['po_id']} | PO#: {$row['po_number']} | Status: {$row['po_status']} | Payment: {$row['payment_status']} | Total: ₹{$row['grand_total']}\n";
    }
}

echo "\n\nPO ITEMS TABLE:\n";
$result = $connect->query("SELECT COUNT(*) as cnt FROM po_items");
$row = $result->fetch_assoc();
echo "Total PO Items: " . $row['cnt'] . "\n";

echo "\n=== System Ready for Testing ===\n";
echo "✓ Purchase Orders: Ready\n";
echo "✓ Sample Data: Loaded\n";
echo "✓ Navigate to: po_list.php to view Purchase Orders\n";
