<?php
/**
 * Database Structure Diagnostic
 */

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection Failed: " . $conn->connect_error);
}

echo "=" . str_repeat("=", 70) . "\n";
echo "DATABASE STRUCTURE DIAGNOSTIC\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Get all tables
$result = $conn->query("SHOW TABLES");
echo "Existing tables in database:\n";
$tables = [];
while ($row = $result->fetch_row()) {
    echo "  - " . $row[0] . "\n";
    $tables[] = $row[0];
}

echo "\n";

// Check users table structure
if (in_array('users', $tables)) {
    echo "Users table structure:\n";
    echo str_repeat("-", 70) . "\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "  Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']}\n";
    }
    echo "\n";
}

// Check other critical tables
foreach (['customers', 'products', 'purchase_orders', 'orders'] as $table) {
    if (in_array($table, $tables)) {
        echo "$table table structure (first 5 columns):\n";
        echo str_repeat("-", 70) . "\n";
        $result = $conn->query("DESCRIBE $table LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            echo "  {$row['Field']} ({$row['Type']})\n";
        }
        echo "\n";
    }
}

$conn->close();
?>
