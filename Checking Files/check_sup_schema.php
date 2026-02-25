<?php
require 'constant/connect.php';
$res = $connect->query('DESCRIBE suppliers');
echo "Suppliers table columns:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>