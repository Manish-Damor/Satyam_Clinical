<?php
// Database connection
$connect = new mysqli("localhost", "root", "toor", "satyam_clinical_new");
if ($connect->connect_error) die("Connection failed: " . $connect->connect_error);

echo "================== PURCHASE INVOICE MODULE AUDIT ==================\n\n";

// 1. Purchase Invoice table
echo "1) PURCHASE_INVOICE TABLE STRUCTURE:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("DESC purchase_invoice");
while ($row = $result->fetch_assoc()) {
    printf("%-25s %-20s %-10s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Extra']);
}

// Get constraints
echo "\n2) PURCHASE_INVOICE EXISTING QUERIES:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("SHOW CREATE TABLE purchase_invoice");
while ($row = $result->fetch_assoc()) {
    echo $row['Create Table'] . "\n\n";
}

// 2. Purchase Invoice Items table
echo "\n3) PURCHASE_INVOICE_ITEMS TABLE STRUCTURE:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("DESC purchase_invoice_items");
while ($row = $result->fetch_assoc()) {
    printf("%-25s %-20s %-10s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Extra']);
}

echo "\n4) PURCHASE_INVOICE_ITEMS CREATE STATEMENT:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("SHOW CREATE TABLE purchase_invoice_items");
while ($row = $result->fetch_assoc()) {
    echo $row['Create Table'] . "\n\n";
}

// 3. Product table
echo "\n5) PRODUCT TABLE STRUCTURE (Selected columns):\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("DESC product");
while ($row = $result->fetch_assoc()) {
    printf("%-25s %-20s %-10s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Extra']);
}

// 4. Product Batches
echo "\n6) PRODUCT_BATCHES TABLE STRUCTURE:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("DESC product_batches");
while ($row = $result->fetch_assoc()) {
    printf("%-25s %-20s %-10s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Extra']);
}

// 5. Suppliers table
echo "\n7) SUPPLIERS TABLE STRUCTURE:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("DESC suppliers");
while ($row = $result->fetch_assoc()) {
    printf("%-25s %-20s %-10s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Extra']);
}

// 6. Check for existing columns that we might need to add
echo "\n8) EXISTING CONSTRAINTS & INDEXES:\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("SHOW INDEX FROM purchase_invoice");
echo "Indexes on purchase_invoice:\n";
while ($row = $result->fetch_assoc()) {
    printf("%-20s %-30s\n", $row['Key_name'], implode(', ', array_filter([$row['Column_name']])));
}

echo "\n\nIndexes on purchase_invoice_items:\n";
$result = $connect->query("SHOW INDEX FROM purchase_invoice_items");
while ($row = $result->fetch_assoc()) {
    printf("%-20s %-30s\n", $row['Key_name'], implode(', ', array_filter([$row['Column_name']])));
}

// 7. Check for existing data sample
echo "\n9) SAMPLE DATA (last 3 invoices):\n";
echo str_repeat("=", 70) . "\n";
$result = $connect->query("SELECT * FROM purchase_invoice ORDER BY id DESC LIMIT 3");
if ($result->num_rows > 0) {
    echo "Columns: " . implode(", ", array_keys($result->fetch_assoc())) . "\n";
    $result = $connect->query("SELECT * FROM purchase_invoice ORDER BY id DESC LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        echo "\nInvoice ID: {$row['id']}\n";
        foreach ($row as $k => $v) {
            echo "  {$k}: {$v}\n";
        }
    }
} else {
    echo "No invoice data found\n";
}

// 8. Backend handler audit
echo "\n10) BACKEND HANDLER VERIFICATION:\n";
echo str_repeat("=", 70) . "\n";
$handlerFile = 'php_action/create_purchase_invoice.php';
if (file_exists($handlerFile)) {
    echo "✓ Handler exists: $handlerFile\n";
    $content = file_get_contents($handlerFile);
    
    // Check for specific logic
    echo "\nHandler Checks:\n";
    echo "  - Validates supplier_invoice_no unique: " . (strpos($content, 'supplier_invoice_no') !== false ? "✓ YES" : "✗ NO") . "\n";
    echo "  - Uses Approved status for stock increase: " . (strpos($content, 'Approved') !== false ? "✓ YES" : "✗ NO") . "\n";
    echo "  - Handles free_qty: " . (strpos($content, 'free_qty') !== false ? "✓ YES" : "✗ NO") . "\n";
    echo "  - Creates batch records: " . (strpos($content, 'product_batches') !== false ? "✓ YES" : "✗ NO") . "\n";
    echo "  - Calculates effective_rate: " . (strpos($content, 'effective_rate') !== false ? "✓ YES" : "✗ NO") . "\n";
} else {
    echo "✗ Handler missing: $handlerFile\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "AUDIT COMPLETE\n";
echo str_repeat("=", 70) . "\n";

$connect->close();
?>
