<?php
require_once 'php_action/core.php';

echo "=== Checking Products ===\n\n";

$result = $connect->query("SELECT product_id, product_name FROM products LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " sample products:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: " . $row['product_id'] . ", Name: " . $row['product_name'] . "\n";
    }
} else {
    echo "Error: No products found or error in query\n";
}
