<?php
require './constant/connect.php';

echo "=== DATABASE STRUCTURE ANALYSIS ===\n\n";

echo "1. ALL TABLES IN DATABASE:\n";
echo "-----------------------------------------\n";
$query = "SHOW TABLES";
$result = $connect->query($query);
$tables = [];
while($row = $result->fetch_row()) {
    $tables[] = $row[0];
    echo "  - " . $row[0] . "\n";
}

echo "\n2. PRODUCT-RELATED TABLES:\n";
echo "-----------------------------------------\n";

// Check product table structure
echo "\n2a. PRODUCT TABLE (Product Master):\n";
$productCheck = $connect->query("DESCRIBE product");
echo "Columns:\n";
$productCols = [];
while($col = $productCheck->fetch_assoc()) {
    $productCols[] = $col['Field'];
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ", " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . ")\n";
}

// Check for stock-related tables
echo "\n2b. STOCK/BATCH RELATED TABLES:\n";
foreach(['medicine_batch', 'product_batches', 'stock', 'inventory', 'medicine_stock'] as $tbl) {
    if(in_array($tbl, $tables)) {
        echo "\nTable: $tbl\n";
        $result = $connect->query("DESCRIBE $tbl");
        while($col = $result->fetch_assoc()) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
}

// Check for any other medicine/product related tables
echo "\n2c. OTHER RELATED TABLES:\n";
$medicineRelated = ['medicine', 'batch', 'inventory_stock', 'stock_movement', 'stock_in', 'stock_out', 'purchase_rate', 'product_stock'];
foreach($tables as $tbl) {
    if(preg_match('/(medicine|product|stock|batch|inventory|medicine_batch)/i', $tbl)) {
        if(!in_array($tbl, ['product', 'medicine_batch', 'product_batches'])) {
            echo "\nFound: $tbl\n";
            $result = $connect->query("DESCRIBE $tbl");
            while($col = $result->fetch_assoc()) {
                echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        }
    }
}

echo "\n\n3. RELATIONSHIPS & FOREIGN KEYS:\n";
echo "-----------------------------------------\n";
$allTables = $tables;
foreach($allTables as $tbl) {
    $result = $connect->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = '$tbl' AND REFERENCED_TABLE_NAME IS NOT NULL");
    
    if($result && $result->num_rows > 0) {
        echo "\nTable: $tbl\n";
        while($row = $result->fetch_assoc()) {
            echo "  FK: " . $row['COLUMN_NAME'] . " → " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
        }
    }
}

echo "\n\n4. CURRENT PRODUCT/STOCK SUMMARY:\n";
echo "-----------------------------------------\n";

// Count records in key tables
$checks = [
    'product' => "SELECT COUNT(*) as cnt FROM product",
    'medicine_batch' => "SELECT COUNT(*) as cnt FROM medicine_batch",
    'product_batches' => "SELECT COUNT(*) as cnt FROM product_batches"
];

foreach($checks as $name => $sql) {
    $result = $connect->query($sql);
    if($result) {
        $row = $result->fetch_assoc();
        echo "\n$name: " . $row['cnt'] . " records\n";
    }
}

echo "\n\n5. SAMPLE DATA FROM PRODUCT TABLE:\n";
echo "-----------------------------------------\n";
$result = $connect->query("SELECT product_id, product_name, content, gst_rate, selling_rate, purchase_rate, reorder_level, status FROM product LIMIT 5");
if($result && $result->num_rows > 0) {
    echo "ID | Name | Content | GST% | Sell Rate | Purchase Rate | Reorder | Status\n";
    while($row = $result->fetch_assoc()) {
        echo $row['product_id'] . " | " . $row['product_name'] . " | " . $row['content'] . " | " . $row['gst_rate'] . "% | " . $row['selling_rate'] . " | " . $row['purchase_rate'] . " | " . $row['reorder_level'] . " | " . $row['status'] . "\n";
    }
}

echo "\n\n6. SAMPLE DATA FROM MEDICINE_BATCH:\n";
echo "-----------------------------------------\n";
$result = $connect->query("SELECT batch_id, product_id, batch_number, available_quantity, expiry_date, status FROM medicine_batch LIMIT 3");
if($result && $result->num_rows > 0) {
    echo "Batch ID | Product ID | Batch# | Qty | Expiry | Status\n";
    while($row = $result->fetch_assoc()) {
        echo $row['batch_id'] . " | " . $row['product_id'] . " | " . $row['batch_number'] . " | " . $row['available_quantity'] . " | " . $row['expiry_date'] . " | " . $row['status'] . "\n";
    }
} else {
    echo "No records\n";
}

echo "\n\n7. SAMPLE DATA FROM PRODUCT_BATCHES:\n";
echo "-----------------------------------------\n";
$result = $connect->query("SELECT * FROM product_batches LIMIT 3");
if($result && $result->num_rows > 0) {
    $cols = $result->fetch_field_direct(0);
    $num_fields = $result->field_count;
    echo "Fields: ";
    for($i = 0; $i < $num_fields; $i++) {
        $field = $result->fetch_field_direct($i);
        echo $field->name . " | ";
    }
    echo "\n";
    while($row = $result->fetch_assoc()) {
        foreach($row as $val) {
            echo $val . " | ";
        }
        echo "\n";
    }
} else {
    echo "No records\n";
}

echo "\n\n8. MEDICINE MODULE FILES:\n";
echo "-----------------------------------------\n";
$files = [
    'add_medicine.php' => 'Add new medicine',
    'manage_medicine.php' => 'Manage medicines',
    'manage_batches.php' => 'Manage batches',
    'addProductStock.php' => 'Add product stock',
    'check_product_batches.php' => 'Check batches',
    'viewStock.php' => 'View stock'
];

foreach($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    if(file_exists($path)) {
        echo "  ✓ " . $file . " (" . $desc . ") - " . filesize($path) . " bytes\n";
    } else {
        echo "  ✗ " . $file . " (MISSING)\n";
    }
}

echo "\n\n=== END ANALYSIS ===\n";
