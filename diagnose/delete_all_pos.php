<?php
require_once 'php_action/core.php';

$connect->query('DELETE FROM po_items');
$connect->query('DELETE FROM purchase_orders');

echo "Database cleaned successfully.\n";
$result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_orders");
$row = $result->fetch_assoc();
echo "Current POs: " . $row['cnt'] . "\n";
