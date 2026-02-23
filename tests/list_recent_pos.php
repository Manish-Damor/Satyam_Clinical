<?php
chdir(__DIR__ . '/..');
require_once 'php_action/core.php';
$res = $connect->query("SELECT po_id, po_number, created_at, supplier_id FROM purchase_orders ORDER BY created_at DESC LIMIT 10");
while($r = $res->fetch_assoc()){
    echo $r['po_id'] . ' | ' . $r['po_number'] . ' | ' . $r['supplier_id'] . ' | ' . $r['created_at'] . "\n";
}
$connect->close();
