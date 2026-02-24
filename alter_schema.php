<?php
require 'constant/connect.php';

// Array of SQL statements - adjusted after payment_notes already exists
$statements = [
    "ALTER TABLE sales_invoices ADD COLUMN payment_method VARCHAR(50) AFTER payment_type",
    "ALTER TABLE sales_invoices ADD COLUMN payment_received_date DATETIME AFTER paid_amount",
    "ALTER TABLE sales_invoices ADD COLUMN is_cancelled TINYINT DEFAULT 0 AFTER updated_at"
];

echo "Starting database schema updates...\n\n";

foreach ($statements as $i => $sql) {
    echo "Executing statement " . ($i + 1) . "...\n";
    echo "SQL: " . substr($sql, 0, 60) . "...\n";
    
    if ($connect->query($sql)) {
        echo "✓ Success\n\n";
    } else {
        echo "✗ Error: " . $connect->error . "\n\n";
    }
}

echo "\n✓ All done! Current table structure:\n\n";

// Show table structure
$result = $connect->query("DESCRIBE sales_invoices");
if ($result) {
    echo "sales_invoices columns:\n";
    while ($row = $result->fetch_assoc()) {
        printf("  %-25s %s\n", $row['Field'], $row['Type']);
    }
}

$connect->close();
?>
