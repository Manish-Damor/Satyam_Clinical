<?php
/**
 * Phase 4 - Workflow Scenario Tests
 * End-to-end testing of core purchase order, sales order, and GRN workflows
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/constant/connect.php';
require_once $rootDir . '/config/bootstrap.php';
require_once __DIR__ . '/TestFramework.php';

use Controllers\PurchaseOrderController;
use Controllers\SalesOrderController;
use Controllers\GRNController;

$test = new TestFramework($connect);

echo "PHASE 4 WORKFLOW SCENARIO TESTS\n";
echo "================================================================================\n\n";

$test->cleanupTestData();

// ========================================
// TEST SUITE 1: PO CREATION WORKFLOW
// ========================================

echo "SUITE 1: Purchase Order Creation Workflow\n";
echo "----------------------------------------\n";

$supplierId = $test->createTestSupplier(999);

$poData = [
    'po_number' => 'TEST-PO-' . date('YmdHis'),
    'po_date' => date('Y-m-d'),
    'po_type' => 'Regular',
    'supplier_id' => $supplierId,
    'supplier_name' => 'Test Supplier',
    'supplier_contact' => '9876543210',
    'supplier_email' => 'test@supplier.com',
    'supplier_gst' => '27AAFCU5055K1Z0',
    'supplier_address' => 'Test Address',
    'supplier_city' => 'Mumbai',
    'supplier_state' => 'MH',
    'supplier_pincode' => '400001',
    'expected_delivery_date' => date('Y-m-d', strtotime('+30 days')),
    'sub_total' => 10000,
    'total_discount' => 0,
    'discount_percent' => 0,
    'taxable_amount' => 10000,
    'cgst_amount' => 900,
    'sgst_amount' => 900,
    'igst_amount' => 0,
    'round_off' => 0,
    'grand_total' => 11800,
    'payment_terms' => '30 days',
    'payment_method' => 'Wire Transfer',
    'po_status' => 'draft'
];

$items = [
    [
        'medicine_id' => 1,
        'medicine_name' => 'Test Medicine 1',
        'pack_size' => '10 x 10',
        'hsn_code' => '3004',
        'batch_number' => 'B12345',
        'expiry_date' => date('Y-m-d', strtotime('+2 years')),
        'quantity' => 100,
        'mrp' => 50,
        'ptr' => 40,
        'unit_price' => 35,
        'discount_percent' => 0,
        'tax_percent' => 18
    ]
];

try {
    $controller = new PurchaseOrderController($connect, 1, 'admin');
    $result = $controller->createPurchaseOrder($poData, $items);
    
    $test->assertTrue($result['success'], "PO01: Create PO with valid data", $result['message']);
    $test->assertNotNull($result['po_id'], "PO02: PO ID generated", "PO ID: " . $result['po_id']);
    
    $poId = $result['po_id'];
    
    // Verify PO in database
    $test->assertDatabaseHasRecord('purchase_orders', [
        'po_id' => $poId
    ], "PO03: PO recorded in database");
    
    // Verify approval log created
    $test->assertDatabaseHasRecord('approval_logs', [
        'entity_type' => 'purchase_order',
        'entity_id' => $poId
    ], "PO04: Approval workflow initialized");
    
    // Verify audit log created
    $test->assertDatabaseHasRecord('audit_logs', [
        'table_name' => 'purchase_order',
        'record_id' => $poId
    ], "PO05: Audit trail created");
    
} catch (Exception $e) {
    $test->assertTrue(false, "PO01: Create PO - Exception", $e->getMessage());
}

$test->printResults("PO Creation Workflow");

// ========================================
// TEST SUITE 2: SALES ORDER WITH CREDIT CHECK
// ========================================

echo "\nSUITE 2: Sales Order Creation with Credit Control\n";
echo "----------------------------------------\n";

$customerId = $test->createTestCustomer(999);

$orderData = [
    'uno' => 'TEST-ORD-' . date('YmdHis'),
    'orderDate' => date('Y-m-d'),
    'clientName' => 'Test Customer',
    'clientContact' => '9999999999',
    'subTotalValue' => 5000,
    'totalAmountValue' => 5000,
    'discount' => 0,
    'grandTotalValue' => 5900,
    'gstn' => '27AAFCU5055K1Z0',
    'paid' => 0,
    'dueValue' => 5900,
    'paymentType' => 'credit',
    'paymentStatus' => 'pending',
    'paymentPlace' => 'counter',
    'gstPercentage' => 18
];

$orderItems = [
    [
        'product_id' => 1,
        'productName' => 'Test Product',
        'quantity' => 10,
        'rate' => 500
    ]
];

try {
    $ctrl = new SalesOrderController($connect, 1, 'user');
    $orderResult = $ctrl->createSalesOrder($orderData, $orderItems);
    
    $test->assertTrue($orderResult['success'], "SO01: Create sales order", $orderResult['message']);
    $test->assertNotNull($orderResult['order_id'], "SO02: Order ID generated");
    
    // Check credit analysis
    $test->assertTrue(isset($orderResult['credit_analysis']), "SO03: Credit analysis provided");
    
    $orderId = $orderResult['order_id'];
    $test->assertDatabaseHasRecord('orders', [
        'order_id' => $orderId
    ], "SO04: Order recorded in database");
    
} catch (Exception $e) {
    $test->assertTrue(false, "SO01: Create order - Exception", $e->getMessage());
}

$test->printResults("Sales Order with Credit Control");

// ========================================
// TEST SUITE 3: GRN QUALITY CHECK WORKFLOW
// ========================================

echo "\nSUITE 3: GRN Creation with Quality Checks\n";
echo "----------------------------------------\n";

// Get the PO we created earlier
if (isset($poId)) {
    
    // Get PO items
    $poItemsResult = $connect->query("SELECT po_item_id FROM po_items WHERE po_id = $poId LIMIT 1");
    $poItemRow = $poItemsResult->fetch_assoc();
    $poItemId = $poItemRow['po_item_id'] ?? 0;
    
    if ($poItemId) {
        $grnData = [
            'po_id' => $poId,
            'grn_date' => date('Y-m-d'),
            'warehouse_id' => 1,
            'received_by' => 1,
            'notes' => 'Test GRN'
        ];
        
        $grnItems = [
            [
                'po_item_id' => $poItemId,
                'quantity_received' => 100,
                'batch_number' => 'B12345',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'product_id' => 1
            ]
        ];
        
        $qualityChecks = [
            $poItemId => [
                'check_result' => 'passed',
                'notes' => 'All items in good condition'
            ]
        ];
        
        try {
            $grnCtrl = new GRNController($connect, 1, 'user');
            $grnResult = $grnCtrl->createGRN($grnData, $grnItems, $qualityChecks);
            
            $test->assertTrue($grnResult['success'], "GRN01: Create GRN with quality check", $grnResult['message']);
            $test->assertNotNull($grnResult['grn_id'], "GRN02: GRN ID generated");
            
            // Verify quality summary
            $test->assertEqual(
                $grnResult['quality_summary']['passed_items'],
                1,
                "GRN03: Quality check passed count matches"
            );
            
            $grnId = $grnResult['grn_id'];
            $test->assertDatabaseHasRecord('goods_received', [
                'grn_id' => $grnId
            ], "GRN04: GRN recorded in database");
            
        } catch (Exception $e) {
            $test->assertTrue(false, "GRN01: Create GRN - Exception", $e->getMessage());
        }
    } else {
        echo "⚠ Warning: No PO items found, skipping GRN tests\n";
    }
}

$test->printResults("GRN Quality Check Workflow");

// ========================================
// SUMMARY
// ========================================

$totalTime = $test->getExecutionTime();

echo "\n================================================================================\n";
echo "WORKFLOW TESTS SUMMARY\n";
echo "================================================================================\n";
echo "Total Test Cases: " . ($test->testsPassed + $test->testsFailed) . "\n";
echo "Passed: {$test->testsPassed}\n";
echo "Failed: {$test->testsFailed}\n";
echo "Execution Time: {$totalTime}s\n";
echo "================================================================================\n";

$test->cleanupTestData();

?>
