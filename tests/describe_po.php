<?php
require_once __DIR__ . '/../php_action/core.php';
$res = $connect->query('DESCRIBE purchase_orders');
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' ' . $r['Type'] . "\n";
}
