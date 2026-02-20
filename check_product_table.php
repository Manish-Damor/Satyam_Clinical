<?php
require 'constant/connect.php';

echo "=== CHECKING PRODUCT TABLE ===\n\n";

// Check if product table exists
$result = $connect->query("SHOW TABLES LIKE 'product%'");
echo "Tables matching 'product%':\n";
while ($row = $result->fetch_row()) {
    echo "  - " . $row[0] . "\n";
}



// Check if product table exists
echo "\n\nChecking if product table exists and its columns:\n";
$result = $connect->query("SHOW COLUMNS FROM product");
if ($result) {
    echo "Columns in product:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Table product not found\n";
}

// Check product_batches
echo "\n\nChecking if product_batches table exists and its columns:\n";
$result = $connect->query("SHOW COLUMNS FROM product_batches");
if ($result) {
    echo "Columns in product_batches:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Table product_batches not found\n";
}

?>
