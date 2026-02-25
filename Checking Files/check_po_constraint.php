<?php
require './constant/connect.php';

// Check table structure
$res = $connect->query("SHOW CREATE TABLE purchase_orders");
$row = $res->fetch_assoc();
echo $row['Create Table'];
echo "\n\n=== CONSTRAINT CHECK ===\n";

// Check for unique constraint
$indexRes = $connect->query("SHOW INDEX FROM purchase_orders WHERE Column_name = 'po_number'");
if ($indexRes->num_rows > 0) {
    while ($idx = $indexRes->fetch_assoc()) {
        echo "Index on po_number: " . $idx['Key_name'] . " (Unique: " . ($idx['Non_unique'] ? 'NO' : 'YES') . ")\n";
    }
} else {
    echo "NO UNIQUE CONSTRAINT on po_number\n";
}
?>
