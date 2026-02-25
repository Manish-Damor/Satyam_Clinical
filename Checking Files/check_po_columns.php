<?php
require 'constant/connect.php';

$res = $connect->query('DESCRIBE purchase_orders');
echo "Column names in purchase_orders table:\n";
echo "======================================\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
