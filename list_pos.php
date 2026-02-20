<?php
require_once __DIR__ . '/php_action/core.php';
$res = $connect->query('SELECT po_id, po_number FROM purchase_orders');
if(!$res) {
    echo "Query failed: " . $connect->error . "\n";
} else {
    while($r = $res->fetch_assoc()) {
        echo $r['po_id'].' - '.$r['po_number'].'\n';
    }
}
?>