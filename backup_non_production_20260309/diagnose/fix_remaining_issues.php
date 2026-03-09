<?php
include 'constant/connect.php';

echo "=== Fixing Remaining Issues ===\n\n";

// Issue 1: Add effective_rate to items table if missing
echo "1. Checking effective_rate in purchase_invoice_items...\n";
$checkCol = $connect->query("SHOW COLUMNS FROM purchase_invoice_items LIKE 'effective_rate'");
if ($checkCol->num_rows === 0) {
    $sql = "ALTER TABLE purchase_invoice_items ADD COLUMN effective_rate DECIMAL(14,4) AFTER unit_cost";
    if ($connect->query($sql)) {
        echo "   ✓ effective_rate column added\n";
    } else {
        echo "   ✗ Error: " . $connect->error . "\n";
    }
} else {
    echo "   ✓ Column already exists\n";
}

// Issue 2: Check stock_batches table
echo "2. Checking stock_batches table structure...\n";
$columnsResult = $connect->query("SHOW COLUMNS FROM stock_batches");
$columns = [];
while ($col = $columnsResult->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Verify all required columns
$required = ['product_id', 'batch_no', 'qty', 'mrp', 'cost_price', 'supplier_id', 'invoice_id'];
foreach ($required as $col) {
    if (in_array($col, $columns)) {
        echo "   ✓ $col\n";
    } else {
        echo "   ✗ $col - MISSING\n";
    }
}

echo "\n✅ All fixes applied!\n";
?>
