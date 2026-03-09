<?php
require_once 'php_action/core.php';

$result = $connect->query("SELECT product_id, product_name FROM product LIMIT 10");
echo "Found " . $result->num_rows . " products:\n";
while($row = $result->fetch_assoc()) {
    echo $row['product_id'] . " - " . $row['product_name'] . "\n";
}
