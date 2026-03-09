<?php
require_once __DIR__ . '/php_action/core.php';
$res = $connect->query("SELECT id, invoice_no, invoice_date, supplier_id FROM purchase_invoices LIMIT 10");
if(!$res) {
    echo "Query error: " . $connect->error . "\n";
    exit;
}
while($r=$res->fetch_assoc()) {
    echo $r['id']." | ".$r['invoice_no']." | ".$r['invoice_date']." | sup=".$r['supplier_id']."\n";
}
?>