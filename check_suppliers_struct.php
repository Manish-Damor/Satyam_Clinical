<?php
require_once 'php_action/core.php';

echo "=== Checking Suppliers Table Structure ===\n\n";

$res = $connect->query("DESCRIBE suppliers");
echo "Suppliers Table Columns:\n";
while ($col = $res->fetch_assoc()) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ") " . ($col['Null'] === 'NO' ? 'NOT NULL' : '') . "\n";
}

echo "\n=== Checking Status Field ===\n\n";
$res = $connect->query("SELECT DISTINCT supplier_status FROM suppliers LIMIT 5");
echo "Status values: ";
while ($row = $res->fetch_assoc()) {
    echo $row['supplier_status'] . ", ";
}
echo "\n";
