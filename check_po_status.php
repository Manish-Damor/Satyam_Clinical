<?php
require_once 'php_action/core.php';
$res = $connect->query('SELECT po_id, po_number, po_status FROM purchase_orders');
while($r = $res->fetch_assoc()){
    echo $r['po_id'] . ' ' . $r['po_number'] . ' ' . $r['po_status'] . "\n";
}
