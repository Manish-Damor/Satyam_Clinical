<?php
require_once __DIR__ . '/php_action/core.php';
$res = $connect->query('SELECT COUNT(*) as c FROM purchase_invoices');
if($res) {
    $row=$res->fetch_assoc();
    echo "purchase_invoices count=" . $row['c'] . "\n";
} else {
    echo "query error: " . $connect->error . "\n";
}
?>