<?php
// Automated Phase 2 test runner for purchase invoice scenarios
// Run: C:\xampp\php\php.exe test_phase2_scenarios.php

session_start();
// Ensure a test user id exists for created_by
$_SESSION['userId'] = 1;

require_once __DIR__ . '/php_action/purchase_invoice_action.php';
require_once __DIR__ . '/php_action/db_connect.php';

function ensureSupplier($name, $state, $gst) {
    global $connect;
    $nameEsc = $connect->real_escape_string($name);
    $res = $connect->query("SELECT supplier_id FROM suppliers WHERE supplier_name = '$nameEsc' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        return intval($r['supplier_id']);
    }
    $stmt = $connect->prepare("INSERT INTO suppliers (supplier_name, state, gst_number, supplier_status) VALUES (?, ?, ?, 'Active')");
    $stmt->bind_param('sss', $name, $state, $gst);
    $stmt->execute();
    $id = $connect->insert_id;
    $stmt->close();
    return $id;
}

function ensureProduct($name, $gst, $hsn) {
    global $connect;
    $nameEsc = $connect->real_escape_string($name);
    $res = $connect->query("SELECT product_id FROM product WHERE product_name = '$nameEsc' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        return intval($r['product_id']);
    }
    // Provide required fields: content, brand_id, categories_id, product_type, unit_type, pack_size, reorder_level, status
    $brandId = 1;
    $categoryId = 1;
    $content = 'TEST PRODUCT';
    $productType = 'Tablet';
    $unitType = 'Strip';
    $packSize = '10x10';
    $reorderLevel = 0;
    $expectedMrp = 0.00;
    $stmt = $connect->prepare("INSERT INTO product (product_name, content, brand_id, categories_id, product_type, unit_type, pack_size, hsn_code, gst_rate, reorder_level, status, expected_mrp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt->bind_param('ssiiisssdid', $name, $content, $brandId, $categoryId, $productType, $unitType, $packSize, $hsn, $gst, $reorderLevel, $expectedMrp);
    $stmt->execute();
    $id = $connect->insert_id;
    $stmt->close();
    return $id;
}

function runScenario($label, $data, $items) {
    echo "\n--- $label ---\n";
    $result = PurchaseInvoiceAction::createInvoice($data, $items);
    echo "Result: ";
    print_r($result);
    if (!empty($result['invoice_id'])) {
        $inv = PurchaseInvoiceAction::getInvoice($result['invoice_id']);
        echo "Stored invoice summary:\n";
        print_r($inv);
    }
}

// Prepare test suppliers & products
$supGuj = ensureSupplier('TEST_SUPPLIER_GUJARAT', 'Gujarat', '24TESTGSTIN');
$supDelhi = ensureSupplier('TEST_SUPPLIER_DELHI', 'Delhi', '07TESTGSTIN');
$p5 = ensureProduct('TEST_PRODUCT_5pct', 5.00, '30045010');
$p12 = ensureProduct('TEST_PRODUCT_12pct', 12.00, '30049099');
$p18 = ensureProduct('TEST_PRODUCT_18pct', 18.00, '30051010');

$today = date('Y-m-d');
$nextYear = date('Y-m-d', strtotime('+400 days'));

// Scenario 1: Intra-state (Gujarat -> Gujarat)
$data1 = [
    'supplier_id' => $supGuj,
    'invoice_no' => 'TEST-IS-001',
    'invoice_date' => $today,
    'po_reference' => 'PO-TEST-1',
    'grn_reference' => '',
    'payment_terms' => '30',
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'currency' => 'INR',
    'freight' => 0,
    'round_off' => 0,
    'paid_amount' => 0,
    'payment_mode' => 'credit',
    'gst_type' => 'intrastate',
    'status' => 'Draft',
    'attachment_path' => '',
    'notes' => 'Automated test'
];

$items1 = [[
    'product_id' => $p5,
    'product_name' => 'TEST_PRODUCT_5pct',
    'hsn_code' => '30045010',
    'batch_no' => 'TSG-B001',
    'manufacture_date' => $today,
    'expiry_date' => $nextYear,
    'qty' => 10,
    'free_qty' => 0,
    'unit_cost' => 50,
    'mrp' => 75,
    'discount_percent' => 0,
    'tax_rate' => 5.00
]];

runScenario('Scenario 1 - Intra-state (Gujarat->Gujarat)', $data1, $items1);

// Scenario 2: Inter-state (Gujarat -> Delhi)
$data2 = $data1;
$data2['supplier_id'] = $supDelhi;
$data2['invoice_no'] = 'TEST-IT-001';
$data2['gst_type'] = 'interstate';

$items2 = $items1;
$items2[0]['batch_no'] = 'TDEL-B001';

runScenario('Scenario 2 - Inter-state (Gujarat->Delhi)', $data2, $items2);

// Scenario 3: Multi-rate invoice (5%,12%,18%)
$data3 = $data1;
$data3['invoice_no'] = 'TEST-MR-001';

$items3 = [
    [
        'product_id' => $p5,
        'product_name' => 'TEST_PRODUCT_5pct',
        'hsn_code' => '30045010',
        'batch_no' => 'MR-B001',
        'manufacture_date' => $today,
        'expiry_date' => $nextYear,
        'qty' => 5,
        'free_qty' => 0,
        'unit_cost' => 40,
        'mrp' => 60,
        'discount_percent' => 0,
        'tax_rate' => 5.00
    ],
    [
        'product_id' => $p12,
        'product_name' => 'TEST_PRODUCT_12pct',
        'hsn_code' => '30049099',
        'batch_no' => 'MR-B002',
        'manufacture_date' => $today,
        'expiry_date' => $nextYear,
        'qty' => 3,
        'free_qty' => 0,
        'unit_cost' => 100,
        'mrp' => 150,
        'discount_percent' => 0,
        'tax_rate' => 12.00
    ],
    [
        'product_id' => $p18,
        'product_name' => 'TEST_PRODUCT_18pct',
        'hsn_code' => '30051010',
        'batch_no' => 'MR-B003',
        'manufacture_date' => $today,
        'expiry_date' => $nextYear,
        'qty' => 2,
        'free_qty' => 0,
        'unit_cost' => 200,
        'mrp' => 260,
        'discount_percent' => 0,
        'tax_rate' => 18.00
    ]
];

runScenario('Scenario 3 - Multi-rate invoice', $data3, $items3);

// Scenario 4: Partial payment
$data4 = $data1;
$data4['invoice_no'] = 'TEST-PAY-001';
$data4['paid_amount'] = 100;

runScenario('Scenario 4 - Partial payment', $data4, $items1);

// Scenario 5: Margin calculation verification (computed client-side but derive here)
// We'll create an invoice and compute margin = (MRP - Cost) / Cost * 100
$data5 = $data1;
$data5['invoice_no'] = 'TEST-MARGIN-001';
$items5 = [[
    'product_id' => $p5,
    'product_name' => 'TEST_PRODUCT_5pct',
    'hsn_code' => '30045010',
    'batch_no' => 'MG-B001',
    'manufacture_date' => $today,
    'expiry_date' => $nextYear,
    'qty' => 10,
    'free_qty' => 0,
    'unit_cost' => 80,
    'mrp' => 120,
    'discount_percent' => 0,
    'tax_rate' => 5.00
]];

runScenario('Scenario 5 - Margin calculation', $data5, $items5);

// Scenario 6: Auto-tax rate (we send tax_rate but ensure product has gst_rate)
$data6 = $data1;
$data6['invoice_no'] = 'TEST-AUTO-001';

runScenario('Scenario 6 - Auto-tax rate', $data6, $items1);

// Scenario 7: Batch duplicate merge (two items same product+batch in same invoice)
$data7 = $data1;
$data7['invoice_no'] = 'TEST-BATCHMERGE-001';
$items7 = [
    [
        'product_id' => $p5,
        'product_name' => 'TEST_PRODUCT_5pct',
        'hsn_code' => '30045010',
        'batch_no' => 'DUP-B001',
        'manufacture_date' => $today,
        'expiry_date' => $nextYear,
        'qty' => 5,
        'free_qty' => 0,
        'unit_cost' => 50,
        'mrp' => 75,
        'discount_percent' => 0,
        'tax_rate' => 5.00
    ],
    [
        'product_id' => $p5,
        'product_name' => 'TEST_PRODUCT_5pct',
        'hsn_code' => '30045010',
        'batch_no' => 'DUP-B001',
        'manufacture_date' => $today,
        'expiry_date' => $nextYear,
        'qty' => 7,
        'free_qty' => 0,
        'unit_cost' => 50,
        'mrp' => 75,
        'discount_percent' => 0,
        'tax_rate' => 5.00
    ]
];

runScenario('Scenario 7 - Batch duplicate merge', $data7, $items7);

// Scenario 8: Invoice uniqueness (attempt to re-create invoice_no for same supplier)
$data8 = $data1;
$data8['invoice_no'] = 'TEST-IS-001'; // same as scenario 1

runScenario('Scenario 8 - Invoice uniqueness', $data8, $items1);

echo "\nAll scenarios executed.\n";

?>
