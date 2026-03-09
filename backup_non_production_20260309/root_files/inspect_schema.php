<?php
// CLI script: describe only tables related to products/medicines/batches.
$mysqli = new mysqli('localhost','root','','satyam_clinical_new');
if ($mysqli->connect_error) die("conn error: {$mysqli->connect_error}\n");

// gather matching tables, avoid names with hyphens/backups
$tables = [];
foreach (['%product%','%batch%'] as $pattern) {
    $res = $mysqli->query("SHOW TABLES LIKE '{$pattern}'");
    if ($res) {
        while ($row = $res->fetch_row()) {
            $tables[] = $row[0];
        }
    }
}

$tables = array_unique($tables);
foreach ($tables as $name) {
    echo "Table: {$name}\n";
    $cols = $mysqli->query("DESCRIBE `{$name}`");
    if ($cols) {
        while ($col = $cols->fetch_assoc()) {
            echo "  {$col['Field']} {$col['Type']} {$col['Null']} {$col['Key']} {$col['Extra']}\n";
        }
    }
    echo "\n";
}

