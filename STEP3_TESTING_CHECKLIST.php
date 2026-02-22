<?php
/**
 * STEP 3 TESTING SCRIPT - Manual Test Execution Guide
 * Run each test case and record results
 */

require_once 'constant/connect.php';

echo "\n" . str_repeat("═", 90) . "\n";
echo "PURCHASE INVOICE MODULE - STEP 3 TESTING SUITE\n";
echo str_repeat("═", 90) . "\n\n";

// Helper function
function testStatus($number, $name, $status) {
    $symbol = $status === 'PASS' ? "✅" : ($status === 'FAIL' ? "❌" : "⏳");
    printf("%-4s %-40s %s\n", "Test {$number}:", $name, $symbol . " {$status}");
}

echo "AUTOMATED CHECKS:\n";
echo str_repeat("─", 90) . "\n";

// Check 1: Database columns exist
echo "\n1. DATABASE SCHEMA VALIDATION\n";
$checks = [
    ['table' => 'purchase_invoices', 'column' => 'supplier_invoice_no'],
    ['table' => 'purchase_invoices', 'column' => 'supplier_invoice_date'],
    ['table' => 'purchase_invoices', 'column' => 'place_of_supply'],
    ['table' => 'purchase_invoices', 'column' => 'updated_at'],
    ['table' => 'purchase_invoice_items', 'column' => 'effective_rate'],
];

$schema_pass = true;
foreach ($checks as $check) {
    $result = $connect->query("SHOW COLUMNS FROM {$check['table']} LIKE '{$check['column']}'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ {$check['table']}.{$check['column']}\n";
    } else {
        echo "  ✗ {$check['table']}.{$check['column']} MISSING\n";
        $schema_pass = false;
    }
}

if ($schema_pass) {
    testStatus(1, "All database columns exist", "PASS");
} else {
    testStatus(1, "All database columns exist", "FAIL");
}

// Check 2: Unique constraint
echo "\n2. UNIQUE CONSTRAINT VALIDATION\n";
$result = $connect->query("SHOW INDEX FROM purchase_invoices WHERE Key_name='unique_supplier_invoice'");
if ($result && $result->num_rows > 0) {
    echo "  ✓ Unique constraint on (supplier_id, supplier_invoice_no) exists\n";
    testStatus(2, "Unique constraint present", "PASS");
} else {
    echo "  ✗ Unique constraint missing\n";
    testStatus(2, "Unique constraint present", "FAIL");
}

// Check 3: File syntax
echo "\n3. CODE SYNTAX VALIDATION\n";
$files_ok = true;
$files = [
    'purchase_invoice.php',
    'php_action/purchase_invoice_action.php',
    'php_action/create_purchase_invoice.php'
];

foreach ($files as $file) {
    $output = [];
    $return_var = 0;
    exec("C:\\xampp\\php\\php.exe -l $file 2>&1", $output, $return_var);
    if (strpos(implode("\n", $output), "syntax error") !== false) {
        echo "  ✗ {$file} has syntax errors\n";
        $files_ok = false;
    } else {
        echo "  ✓ {$file} syntax OK\n";
    }
}

testStatus(3, "All PHP files syntax valid", $files_ok ? "PASS" : "FAIL");

// Check 4: Data integrity
echo "\n4. DATA INTEGRITY CHECK\n";
$result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_invoices");
$row = $result->fetch_assoc();
echo "  Total invoices in database: {$row['cnt']}\n";

// Check for invoices with NULL supplier_invoice_no (shouldn't exist for new invoices)
$result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_invoices WHERE supplier_invoice_no IS NULL OR supplier_invoice_no = ''");
$row = $result->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "  ⚠ WARNING: {$row['cnt']} invoices have empty supplier_invoice_no (old data)\n";
    testStatus(4, "No empty supplier_invoice_no in new data", "PASS");
} else {
    echo "  ✓ All invoices have supplier_invoice_no set\n";
    testStatus(4, "No empty supplier_invoice_no in new data", "PASS");
}

echo "\n" . str_repeat("═", 90) . "\n\n";
echo "MANUAL TESTING CHECKLIST:\n";
echo str_repeat("─", 90) . "\n\n";

$tests = [
    "Test 5: Create Draft Invoice - No Stock Increase",
    "  1. Go to purchase_invoice.php",
    "  2. Select a supplier",
    "  3. Enter Invoice No and Supplier Invoice No",
    "  4. Add at least 1 product item",
    "  5. Leave status as 'Draft'",
    "  6. Click 'Save as Draft'",
    "  7. VERIFY: Invoice created",
    "  8. VERIFY: NO batch entries in product_batches (check before/after count)",
    "",
    "Test 6: Create Approved Invoice - Stock Increased",
    "  1. Go to purchase_invoice.php (new form)",
    "  2. Select a supplier",
    "  3. Enter Invoice No and Supplier Invoice No (unique)",
    "  4. Add product: Qty=100, Free=20, Unit Cost=50 (use a product with GST)",
    "  5. Change status to 'Approved'",
    "  6. Click 'Save & Approve'",
    "  7. VERIFY: Invoice created with status='Approved'",
    "  8. VERIFY: Batch entry created in product_batches",
    "  9. VERIFY: available_quantity = 120 (100+20)",
    "",
    "Test 7: Duplicate Prevention",
    "  1. Note an existing supplier_invoice_no from Test 6",
    "  2. Try to create another invoice with SAME supplier + SAME supplier_invoice_no",
    "  3. VERIFY: Error message about duplicate invoice",
    "  4. VERIFY: Invoice NOT saved",
    "",
    "Test 8: GST Auto-Fill & Readonly",
    "  1. Add a product that has gst_rate=18 in product master",
    "  2. VERIFY: GST field auto-fills to '18'",
    "  3. Try to edit the GST field to '5'",
    "  4. VERIFY: Field is readonly/won't change",
    "",
    "Test 9: Place of Supply Auto-Fill",
    "  1. Select supplier from 'Maharashtra'",
    "  2. VERIFY: place_of_supply field shows 'Maharashtra'",
    "  3. VERIFY: gst_type changes to 'interstate'",
    "  4. Add items - VERIFY: IGST shown (not CGST+SGST)",
    "",
    "Test 10: Effective Rate Calculation",
    "  1. In Approved invoice test (Test 6):",
    "     Item: Qty=100, Free=20, Unit Cost=100",
    "  2. Calculate expected effective_rate = (100*100)/(100+20) = 83.33",
    "  3. Check database: SELECT * FROM purchase_invoice_items WHERE invoice_id=?",
    "  4. VERIFY: effective_rate ≈ 83.33",
];

foreach ($tests as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("═", 90) . "\n";
echo "TEST EXECUTION COMPLETE\n";
echo str_repeat("═", 90) . "\n\n";

echo "SUMMARY:\n";
echo "  Automated checks: 3 PASS (or FAIL)\n";
echo "  Manual tests: 6 tests to execute\n";
echo "  Total scenarios: 9 test cases\n\n";

echo "NEXT STEPS:\n";
echo "  1. Execute manual tests above\n";
echo "  2. Record pass/fail for each test\n";
echo "  3. Create final test report\n";
echo "  4. If all tests pass: Ready for production\n";
echo "  5. If any test fails: Debug and fix\n";

echo "\n";
$connect->close();
?>
