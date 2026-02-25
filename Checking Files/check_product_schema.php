<?php
require 'constant/connect.php';
$result = $connect->query('DESCRIBE product');
echo "Product Table Fields:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

$result = $connect->query('DESCRIBE product_batches');
echo "\n\nProduct Batches Table Fields:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
