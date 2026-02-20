<?php
require_once 'php_action/core.php';

echo "=== All Tables in Database ===\n\n";

$result = $connect->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        echo "  - " . $row[0] . "\n";
    }
} else {
    echo "Error: " . $connect->error . "\n";
}
