<?php
require_once 'php_action/core.php';

echo "Checking current state...\n";
$result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_orders");
$row = $result->fetch_assoc();
$poCount = $row['cnt'];

$result2 = $connect->query("SELECT COUNT(*) as cnt FROM po_items");
$row2 = $result2->fetch_assoc();
$itemCount = $row2['cnt'];

echo "Current POs: $poCount\n";
echo "Current PO Items: $itemCount\n";

if ($poCount > 0 || $itemCount > 0) {
    echo "\nCleaning database...\n";
    $connect->query('DELETE FROM po_items WHERE 1=1');
    $connect->query('DELETE FROM purchase_orders WHERE 1=1');
    
    // Verify
    $result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_orders");
    $row = $result->fetch_assoc();
    echo "After cleanup - POs: " . $row['cnt'] . "\n";
}
