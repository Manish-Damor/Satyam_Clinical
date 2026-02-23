<?php
require 'constant/connect.php';

$res = $connect->query('DESCRIBE purchase_invoice_items');
echo "Column names in purchase_invoice_items table:\n";
echo "==============================================\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
