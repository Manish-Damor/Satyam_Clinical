<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate session
session_start();
$_SESSION['userId'] = 1;

require_once 'constant/connect.php';
require_once 'php_action/purchase_invoice_action.php';

echo "=== Testing Invoice Creation ===\n\n";

// Test data
$testData = [
    'supplier_id' => '1',
    'invoice_no' => 'TEST-INV-' . date('YmdHis'),
    'supplier_invoice_no' => 'SUP-INV-' . date('YmdHis'),
    'supplier_invoice_date' => date('Y-m-d'),
    'invoice_date' => date('Y-m-d'),
    'po_reference' => 'PO-001',
    'place_of_supply' => 'Gujarat',
    'gst_type' => 'intrastate',
    'payment_terms' => 'Net 30',
    'status' => 'Draft',
    'notes' => 'Test invoice',
    'freight' => 0,
    'round_off' => 0,
    'paid_amount' => 0,
    'payment_mode' => 'Credit'
];

$testItems = [
    [
        'product_id' => 1,
        'product_name' => 'Paracetamol 650mg',
        'hsn_code' => '30041090',
        'batch_no' => 'BATCH-001',
        'manufacture_date' => date('Y-m-d'),
        'expiry_date' => date('Y-m-d', strtotime('+12 months')),
        'qty' => 100,
        'free_qty' => 10,
        'unit_cost' => 50.00,
        'mrp' => 60.00,
        'discount_percent' => 5,
        'tax_rate' => 5
    ]
];

try {
    echo "Calling PurchaseInvoiceAction::createInvoice()...\n";
    $result = PurchaseInvoiceAction::createInvoice($testData, $testItems);
    echo "\nResult:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if ($result['success']) {
        echo "\n✅ Invoice created successfully!\n";
        echo "Invoice ID: " . $result['invoice_id'] . "\n";
    } else {
        echo "\n❌ Failed: " . $result['error'] . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
