<?php
require 'constant/connect.php';

echo "===== TABLES IN DATABASE =====\n\n";
$result = $connect->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}

echo "\n===== SUPPLIERS TABLE STRUCTURE =====\n";
$result = $connect->query('DESCRIBE suppliers');
while($row = $result->fetch_array()) {
    echo $row[0] . " (" . $row[1] . ")\n";
}

echo "\n===== ORDERS TABLE STRUCTURE =====\n";
$result = $connect->query('DESCRIBE orders');
while($row = $result->fetch_array()) {
    echo $row[0] . " (" . $row[1] . ")\n";
}

?>
