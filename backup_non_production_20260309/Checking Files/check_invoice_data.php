<?php
require './constant/connect.php';

echo "=== CHECKING ACTUAL INVOICE DATA ===\n\n";

// Check if there are any invoices
$result = $connect->query("SELECT COUNT(*) as cnt FROM sales_invoices");
$row = $result->fetch_assoc();
echo "Total invoices in database: " . $row['cnt'] . "\n\n";

// Check if there are any invoice items
$result = $connect->query("SELECT COUNT(*) as cnt FROM sales_invoice_items");
$row = $result->fetch_assoc();
echo "Total invoice items in database: " . $row['cnt'] . "\n\n";

// Check stock_movements table
$result = $connect->query("DESCRIBE stock_movements");
if ($result) {
    echo "STOCK_MOVEMENTS TABLE EXISTS\n";
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "stock_movements table does NOT exist\n";
}

echo "\n";

// Check if there are any allocations or special data structures
$result = $connect->query("SHOW TABLES LIKE '%allocation%'");
if ($result->num_rows > 0) {
    echo "Found allocation-related tables\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No allocation-specific tables found\n";
}

?>
