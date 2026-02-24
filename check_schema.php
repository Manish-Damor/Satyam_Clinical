<?php
require 'constant/connect.php';

echo "Current sales_invoices table structure:\n\n";

$result = $connect->query("DESCRIBE sales_invoices");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        printf("%-30s %-40s %s\n", $row['Field'], $row['Type'], $row['Null']);
    }
}

echo "\n\n---\n";
echo "Attempting to add only missing columns...\n\n";

// Check and add only missing columns
$columnsToAdd = [
    "payment_method" => "VARCHAR(50)",
    "payment_received_date" => "DATETIME",
    "is_cancelled" => "TINYINT DEFAULT 0"
];

$result = $connect->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='sales_invoices' AND TABLE_SCHEMA='satyam_clinical'");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[$row['COLUMN_NAME']] = true;
}

foreach ($columnsToAdd as $colName => $colDef) {
    if (isset($existingColumns[$colName])) {
        echo "✓ Column '$colName' already exists - skipping\n";
    } else {
        $sql = "ALTER TABLE sales_invoices ADD COLUMN $colName $colDef";
        if ($connect->query($sql)) {
            echo "✓ Added column '$colName'\n";
        } else {
            echo "✗ Error adding '$colName': " . $connect->error . "\n";
        }
    }
}

echo "\n\n✓ Done!\n";
$connect->close();
?>
