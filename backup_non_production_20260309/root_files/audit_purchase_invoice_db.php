<?php
// Use the project's existing database connection
require_once 'constant/connect.php';

echo "\n================== PURCHASE INVOICE MODULE - DATABASE AUDIT ==================\n\n";

// Function to show table structure
function showTableStructure($tableName) {
    global $connect;
    echo "\n▶ TABLE: $tableName\n";
    echo str_repeat("─", 100) . "\n";
    
    $result = $connect->query("DESC $tableName");
    if (!$result) {
        echo "  ✗ Table does not exist\n";
        return false;
    }
    
    printf("%-30s %-25s %-12s %-12s %-30s\n", "Field", "Type", "Null", "Key", "Extra");
    echo str_repeat("─", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-30s %-25s %-12s %-12s %-30s\n", 
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Key'] ?? '',
            $row['Extra'] ?? ''
        );
    }
    return true;
}

// Check if tables exist
$tables = ['purchase_invoices', 'purchase_invoice', 'purchase_invoice_items', 'product', 'product_batches', 'suppliers'];

echo "1) CHECKING WHICH TABLES EXIST:\n";
echo str_repeat("─", 100) . "\n";
foreach ($tables as $tbl) {
    $check = $connect->query("SHOW TABLES LIKE '$tbl'");
    $exists = $check && $check->num_rows > 0 ? "✓ EXISTS" : "✗ MISSING";
    echo "  {$tbl}: {$exists}\n";
}

// Get actual tables from database
echo "\n2) ALL TABLES IN DATABASE:\n";
echo str_repeat("─", 100) . "\n";
$result = $connect->query("SHOW TABLES");
$allTables = [];
while ($row = $result->fetch_row()) {
    $allTables[] = $row[0];
    echo "  - {$row[0]}\n";
}

// Show structure of relevant tables
echo "\n3) RELEVANT TABLE STRUCTURES:\n";

// Try both table name variations
if (in_array('purchase_invoices', $allTables)) {
    showTableStructure('purchase_invoices');
} elseif (in_array('purchase_invoice', $allTables)) {
    showTableStructure('purchase_invoice');
}

if (in_array('purchase_invoice_items', $allTables)) {
    showTableStructure('purchase_invoice_items');
}

showTableStructure('product');
showTableStructure('product_batches');
showTableStructure('suppliers');

// 4. Check for specific columns
echo "\n4) KEY COLUMN VERIFICATION:\n";
echo str_repeat("─", 100) . "\n";

$checks = [
    ['table' => 'product', 'column' => 'gst_rate'],
    ['table' => 'suppliers', 'column' => 'state'],
    ['table' => 'suppliers', 'column' => 'gst_number'],
    ['table' => 'purchase_invoice_items', 'column' => 'free_qty'],
    ['table' => 'purchase_invoice_items', 'column' => 'effective_rate'],
    ['table' => in_array('purchase_invoices', $allTables) ? 'purchase_invoices' : 'purchase_invoice', 'column' => 'supplier_invoice_no'],
    ['table' => in_array('purchase_invoices', $allTables) ? 'purchase_invoices' : 'purchase_invoice', 'column' => 'place_of_supply'],
    ['table' => in_array('purchase_invoices', $allTables) ? 'purchase_invoices' : 'purchase_invoice', 'column' => 'grn_reference'],
];

foreach ($checks as $check) {
    $table = $check['table'];
    $column = $check['column'];
    
    // Skip if table doesn't exist
    if (!in_array($table, $allTables)) {
        echo "  ✗ {$table}.{$column}: TABLE DOESN'T EXIST\n";
        continue;
    }
    
    $result = $connect->query("DESC $table");
    $found = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === $column) {
            echo "  ✓ {$table}.{$column}: EXISTS ({$row['Type']})\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "  ✗ {$table}.{$column}: MISSING\n";
    }
}

// 5. Check unique constraints
echo "\n5) UNIQUE CONSTRAINTS:\n";
echo str_repeat("─", 100) . "\n";

$invoiceTable = in_array('purchase_invoices', $allTables) ? 'purchase_invoices' : 'purchase_invoice';
$result = $connect->query("SHOW INDEX FROM $invoiceTable");
echo "Indexes on {$invoiceTable}:\n";
while ($row = $result->fetch_assoc()) {
    if ($row['Key_name'] !== 'PRIMARY') {
        echo "  - {$row['Key_name']}: " . ($row['Non_unique'] ? '(not unique)' : '(UNIQUE)') . " on {$row['Column_name']}\n";
    }
}

// 6. Check for audit fields
echo "\n6) AUDIT FIELDS CHECK:\n";
echo str_repeat("─", 100) . "\n";

$result = $connect->query("DESC $invoiceTable");
$auditFields = ['created_by', 'created_at', 'approved_by', 'approved_at', 'updated_at'];
while ($row = $result->fetch_assoc()) {
    if (in_array($row['Field'], $auditFields)) {
        echo "  ✓ {$row['Field']}: EXISTS\n";
        unset($auditFields[array_search($row['Field'], $auditFields)]);
    }
}
foreach ($auditFields as $field) {
    echo "  ✗ {$field}: MISSING\n";
}

// 7. Sample data
echo "\n7) SAMPLE DATA CHECK:\n";
echo str_repeat("─", 100) . "\n";

$result = $connect->query("SELECT COUNT(*) as cnt FROM $invoiceTable");
$row = $result->fetch_assoc();
echo "  Total invoices: {$row['cnt']}\n";

if ($row['cnt'] > 0) {
    $result = $connect->query("SELECT id, supplier_id, invoice_no, status, created_at FROM $invoiceTable LIMIT 3");
    echo "  Sample invoices:\n";
    while ($data = $result->fetch_assoc()) {
        echo "    - ID: {$data['id']}, Supplier: {$data['supplier_id']}, Invoice: {$data['invoice_no']}, Status: {$data['status']}\n";
    }
}

// 8. Product GST rates available
echo "\n8) PRODUCT GST RATES:\n";
echo str_repeat("─", 100) . "\n";

$result = $connect->query("SELECT COUNT(*) as cnt, COUNT(DISTINCT gst_rate) as unique_rates FROM product WHERE status=1");
$row = $result->fetch_assoc();
echo "  Active products: {$row['cnt']}\n";
echo "  Unique GST rates: {$row['unique_rates']}\n";

$result = $connect->query("SELECT DISTINCT gst_rate FROM product ORDER BY gst_rate");
echo "  Available GST rates: ";
$rates = [];
while ($row = $result->fetch_assoc()) {
    $rates[] = $row['gst_rate'] . '%';
}
echo implode(', ', $rates) . "\n";

echo "\n" . str_repeat("═", 100) . "\n";
echo "AUDIT COMPLETE\n";
echo str_repeat("═", 100) . "\n";

$connect->close();
?>
