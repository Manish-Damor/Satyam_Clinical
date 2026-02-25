<?php
require 'constant/connect.php';

$res = $connect->query('DESCRIBE purchase_invoices');
echo "Column names in purchase_invoices table:\n";
echo "=======================================\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
