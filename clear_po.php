<?php
require_once __DIR__ . '/php_action/core.php';

$connect->query('DELETE FROM po_items');
$connect->query('DELETE FROM purchase_orders');
echo "PO tables cleared\n";
?>