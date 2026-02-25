<?php
require_once 'php_action/core.php';

echo "=== PO Items Table Structure ===\n\n";

$result = $connect->query("DESCRIBE po_items");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")" . "\n";
    }
} else {
    echo "Error: " . $connect->error . "\n";
}
