<?php
require '../constant/connect.php';
$res = $connect->query("SELECT supplier_id,supplier_name FROM suppliers LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['supplier_id'] . ' ' . $row['supplier_name'] . "\n";
    }
} else {
    echo "Query failed: " . $connect->error;
}
