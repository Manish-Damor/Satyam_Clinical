<?php
require_once __DIR__ . '/../php_action/db_connect.php';
$poId = intval($argv[1] ?? 0);
$res = $connect->query("SELECT po_item_id, product_id, quantity_ordered, quantity_received FROM po_items WHERE po_id = $poId");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo json_encode($r) . "\n";
    }
} else echo "No items or query failed\n";
