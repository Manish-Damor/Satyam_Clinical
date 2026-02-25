<?php
// Test the view and edit pages
require_once __DIR__ . '/php_action/core.php';
require_once __DIR__ . '/php_action/purchase_invoice_action.php';

echo "=== Testing Invoice View & Edit Pages ===\n\n";

// 1. Get one existing invoice (from our test data)
$result = $connect->query("SELECT id, invoice_no, supplier_id, status FROM purchase_invoices LIMIT 1");
if (!$result || $result->num_rows === 0) {
    die("No invoices found. Please run test_phase2_scenarios.php first.\n");
}

$invoice = $result->fetch_assoc();
$invoiceId = $invoice['id'];

echo "Invoice Found: ID={$invoiceId}, Number={$invoice['invoice_no']}, Status={$invoice['status']}\n";

// 2. Test getInvoice() method
echo "\n--- Testing getInvoice() ---\n";
$fullInvoice = PurchaseInvoiceAction::getInvoice($invoiceId);
if ($fullInvoice) {
    echo "✓ getInvoice() returned invoice with " . count($fullInvoice['items']) . " items\n";
    echo "  - Supplier ID: {$fullInvoice['supplier_id']}\n";
    echo "  - Invoice Date: {$fullInvoice['invoice_date']}\n";
    echo "  - Grand Total: ₹{$fullInvoice['grand_total']}\n";
    echo "  - Items:\n";
    foreach ($fullInvoice['items'] as $idx => $item) {
        echo "    {$idx}. Product ID: {$item['product_id']}, Batch: {$item['batch_no']}, Qty: {$item['qty']}, Total: {$item['line_total']}\n";
    }
} else {
    die("ERROR: getInvoice() returned null\n");
}

// 3. Check if supplier details are available
echo "\n--- Checking Supplier Details ---\n";
$suppRes = $connect->query("SELECT supplier_id, supplier_name, state, gst_number FROM suppliers WHERE supplier_id = {$fullInvoice['supplier_id']} LIMIT 1");
if ($suppRes && $suppRes->num_rows > 0) {
    $supp = $suppRes->fetch_assoc();
    echo "✓ Supplier found:\n";
    echo "  - Name: {$supp['supplier_name']}\n";
    echo "  - State: {$supp['state']}\n";
    echo "  - GSTIN: {$supp['gst_number']}\n";
} else {
    echo "✗ Supplier not found!\n";
}

// 4. Check if all product details are available
echo "\n--- Checking Product Details ---\n";
$productsOk = true;
foreach ($fullInvoice['items'] as $item) {
    $pRes = $connect->query("SELECT product_id, product_name, gst_rate FROM product WHERE product_id = {$item['product_id']} LIMIT 1");
    if ($pRes && $pRes->num_rows > 0) {
        $p = $pRes->fetch_assoc();
        echo "✓ Product {$item['product_id']}: {$p['product_name']} (GST: {$p['gst_rate']}%)\n";
    } else {
        echo "✗ Product {$item['product_id']} not found!\n";
        $productsOk = false;
    }
}

// 5. Test edit functionality if invoice is Draft
echo "\n--- Testing Edit Functionality ---\n";
if ($invoice['status'] === 'Draft') {
    echo "Invoice status is Draft - editable\n";
    
    // Simulate an edit
    echo "\nTest: Would update invoice {$invoiceId} with new freight amount\n";
    
    // Get first item to test updating
    if (count($fullInvoice['items']) > 0) {
        $firstItem = $fullInvoice['items'][0];
        echo "First item details:\n";
        echo "  Product ID: {$firstItem['product_id']}\n";
        echo "  Batch: {$firstItem['batch_no']}\n";
        echo "  Qty: {$firstItem['qty']}\n";
        echo "  Unit Cost: {$firstItem['unit_cost']}\n";
        echo "  MRP: {$firstItem['mrp']}\n";
        echo "  Tax Rate: {$firstItem['tax_rate']}%\n";
        
        // Verify calculations
        $expectedLineAmount = $firstItem['qty'] * $firstItem['unit_cost'];
        $expectedTax = $expectedLineAmount * ($firstItem['tax_rate'] / 100);
        $expectedTotal = $expectedLineAmount + $expectedTax;
        
        echo "\nCalculation verification:\n";
        echo "  Expected line amount: ₹{$expectedLineAmount}\n";
        echo "  Expected tax: ₹{$expectedTax}\n";
        echo "  Expected total: ₹{$expectedTotal}\n";
        echo "  Actual line total: ₹{$firstItem['line_total']}\n";
        
        if (abs($expectedTotal - $firstItem['line_total']) < 0.01) {
            echo "  ✓ Calculations are correct\n";
        } else {
            echo "  ✗ Calculation mismatch!\n";
        }
    }
} else {
    echo "Invoice status is '{$invoice['status']}' - not editable (view-only)\n";
}

// 6. Check database structure
echo "\n--- Verifying Database Structure ---\n";
$columns = [
    'purchase_invoices' => ['invoice_no', 'supplier_id', 'invoice_date', 'grand_total', 'outstanding_amount'],
    'purchase_invoice_items' => ['invoice_id', 'product_id', 'batch_no', 'qty', 'line_total'],
    'stock_batches' => ['product_id', 'batch_no', 'qty', 'invoice_id']
];

foreach ($columns as $table => $cols) {
    $res = $connect->query("DESCRIBE $table");
    $tableColumns = [];
    while ($row = $res->fetch_assoc()) {
        $tableColumns[] = $row['Field'];
    }
    
    $missingCols = array_diff($cols, $tableColumns);
    if (empty($missingCols)) {
        echo "✓ $table has all required columns\n";
    } else {
        echo "✗ $table missing: " . implode(', ', $missingCols) . "\n";
    }
}

echo "\n=== All Tests Complete ===\n";

?>
