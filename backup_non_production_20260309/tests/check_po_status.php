<?php
require_once __DIR__ . '/../php_action/db_connect.php';
$poId = $argv[1] ?? 0;
$res = $connect->query("SELECT po_id, po_number, po_status FROM purchase_orders WHERE po_id = " . intval($poId));
if ($res && $res->num_rows) {
    $row = $res->fetch_assoc();
    echo json_encode($row) . "\n";
} else {
    echo "PO not found\n";
}
