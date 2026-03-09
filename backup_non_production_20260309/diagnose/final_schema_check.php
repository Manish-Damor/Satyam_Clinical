<?php
require 'constant/connect.php';

// Just add is_cancelled if it doesn't exist
$sql = "ALTER TABLE sales_invoices ADD COLUMN is_cancelled TINYINT DEFAULT 0 AFTER updated_at";

echo "Attempting to add is_cancelled column...\n";
if ($connect->query($sql)) {
    echo "✓ Column added successfully\n";
} else {
    echo "Info: " . $connect->error . "\n";
}

echo "\n\nCurrent schema:\n";
$result = $connect->query("DESCRIBE sales_invoices");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    printf("%2d. %-30s %-40s\n", $count, $row['Field'], $row['Type']);
}

echo "\nTotal columns: " . $count . "\n";
$connect->close();
?>
