<?php
$connect = new mysqli('localhost','root','', 'satyam_clinical');
if ($connect->connect_error) {
    die('connect error');
}
$res = $connect->query('SHOW COLUMNS FROM purchase_invoices');
if (!$res) {
    echo 'err: '.$connect->error;
    exit;
}
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . '\n';
}
?>