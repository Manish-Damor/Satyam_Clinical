<?php
require_once __DIR__ . '/../php_action/core.php';
$res = $connect->query('SELECT supplier_id, supplier_name FROM suppliers LIMIT 5');
while ($r = $res->fetch_assoc()) {
    echo $r['supplier_id'] . ' - ' . $r['supplier_name'] . "\n";
}
