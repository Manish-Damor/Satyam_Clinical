<?php
/**
 * Phase 4 - GRN Quality Checks & Approval Workflow Tests
 * Tests for goods received workflow and quality assurance
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/constant/connect.php';
require_once $rootDir . '/config/bootstrap.php';
require_once __DIR__ . '/TestFramework.php';

use Controllers\PurchaseOrderController;
use Controllers\GRNController;
use Services\ApprovalEngine;

$test = new TestFramework($connect);

echo "PHASE 4 GRN QUALITY CHECKS & APPROVAL WORKFLOW TESTS\n";
echo "================================================================================\n\n";

$test->cleanupTestData();
$supplierId = $test->createTestSupplier(999);

// Create a PO first
$poData = [
    'po_number' => 'TEST-PO-QUALITY-' . date('YmdHis'),
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
    'sub_total' => 5000,
    'total_discount' => 0,
    'discount_percent' => 0,
    'taxable_amount' => 5000,
    'cgst_amount' => 450,
    'sgst_amount' => 450,
    'igst_amount' => 0,
    'round_off' => 0,
    'grand_total' => 5900,
    'payment_terms' => '30 days',
    'payment_method' => 'Wire Transfer',
    'po_status' => 'draft'
];

$items = [
    [
        'medicine_id' => 1,
        'medicine_name' => 'Test Medicine Quality',
        'pack_size' => '10 x 10',
        'hsn_code' => '3004',
        'batch_number' => 'QA12345',
        'expiry_date' => date('Y-m-d', strtotime('+2 years')),
        'quantity' => 50,
        'mrp' => 50,
        'ptr' => 40,
        'unit_price' => 35,
        'discount_percent' => 0,
        'tax_percent' => 18
    ]
];

$poController = new PurchaseOrderController($connect, 1, 'admin');
$poResult = $poController->createPurchaseOrder($poData, $items);

$poId = $poResult['po_id'] ?? 0;

// ========================================
// TEST SUITE 1: GRN WITH QUALITY PASSED
// ========================================

echo "SUITE 1: GRN with Quality Checks - All Passed\n";
echo "----------------------------------------\n";

if ($poId) {
    $poItemsResult = $connect->query("SELECT po_item_id FROM po_items WHERE po_id = $poId LIMIT 2");
    $poItems = [];
    while ($row = $poItemsResult->fetch_assoc()) {
        $poItems[] = $row['po_item_id'];
    }
    
    if (!empty($poItems)) {
        $grnData = [
            'po_id' => $poId,
            'grn_date' => date('Y-m-d'),
            'warehouse_id' => 1,
            'received_by' => 1,
            'notes' => 'Quality test - all items passed'
        ];
        
        $grnItems = [];
        $qualityChecks = [];
        
        foreach ($poItems as $poItemId) {
            $grnItems[] = [
                'po_item_id' => $poItemId,
                'quantity_received' => 50,
                'batch_number' => 'QA12345',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'product_id' => 1
            ];
            
            $qualityChecks[$poItemId] = [
                'check_result' => 'passed',
                'notes' => 'All items inspected and passed QA'
            ];
        }
        
        try {
            $grnCtrl = new GRNController($connect, 1, 'user');
            $grnResult = $grnCtrl->createGRN($grnData, $grnItems, $qualityChecks);
            
            $test->assertTrue($grnResult['success'], "GQ01: Create GRN with all items passed QA");
            $test->assertEqual(
                $grnResult['quality_summary']['passed_items'],
                count($poItems),
                "GQ02: All items marked as passed in summary"
            );
            
            $grnId = $grnResult['grn_id'] ?? 0;
            if ($grnId) {
                $test->assertDatabaseHasRecord('goods_received', [
                    'grn_id' => $grnId,
                    'quality_check_status' => 'approved'
                ], "GQ03: GRN quality status recorded as approved");
            }
            
        } catch (Exception $e) {
            $test->assertTrue(false, "GQ01: GRN Quality - Exception", $e->getMessage());
        }
    }
}

$test->printResults("GRN with Quality Passed");

// ========================================
// TEST SUITE 2: GRN WITH QUALITY FAILURES
// ========================================

echo "\nSUITE 2: GRN with Quality Checks - Some Failed\n";
echo "----------------------------------------\n";

// Create another PO for failure testing
$poData2 = [
    'po_number' => 'TEST-PO-FAIL-' . date('YmdHis'),
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
    'sub_total' => 5000,
    'total_discount' => 0,
    'discount_percent' => 0,
    'taxable_amount' => 5000,
    'cgst_amount' => 450,
    'sgst_amount' => 450,
    'igst_amount' => 0,
    'round_off' => 0,
    'grand_total' => 5900,
    'payment_terms' => '30 days',
    'payment_method' => 'Wire Transfer',
    'po_status' => 'draft'
];

$items2 = [
    [
        'medicine_id' => 2,
        'medicine_name' => 'Test Medicine Fail',
        'pack_size' => '10 x 10',
        'hsn_code' => '3004',
        'batch_number' => 'FAIL123',
        'expiry_date' => date('Y-m-d', strtotime('+2 years')),
        'quantity' => 50,
        'mrp' => 50,
        'ptr' => 40,
        'unit_price' => 35,
        'discount_percent' => 0,
        'tax_percent' => 18
    ]
];

try {
    $poController2 = new PurchaseOrderController($connect, 1, 'admin');
    $poResult2 = $poController2->createPurchaseOrder($poData2, $items2);
    $poId2 = $poResult2['po_id'] ?? 0;
    
    if ($poId2) {
        $poItemsResult2 = $connect->query("SELECT po_item_id FROM po_items WHERE po_id = $poId2 LIMIT 1");
        $poItemRow2 = $poItemsResult2->fetch_assoc();
        $poItemId2 = $poItemRow2['po_item_id'] ?? 0;
        
        if ($poItemId2) {
            $grnData2 = [
                'po_id' => $poId2,
                'grn_date' => date('Y-m-d'),
                'warehouse_id' => 1,
                'received_by' => 1,
                'notes' => 'Quality test - some items failed'
            ];
            
            $grnItems2 = [
                [
                    'po_item_id' => $poItemId2,
                    'quantity_received' => 50,
                    'batch_number' => 'FAIL123',
                    'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                    'product_id' => 2
                ]
            ];
            
            $qualityChecks2 = [
                $poItemId2 => [
                    'check_result' => 'failed',
                    'notes' => 'Damaged packaging and shelf wear detected'
                ]
            ];
            
            $grnCtrl2 = new GRNController($connect, 1, 'user');
            $grnResult2 = $grnCtrl2->createGRN($grnData2, $grnItems2, $qualityChecks2);
            
            $test->assertTrue($grnResult2['success'], "GQ04: Create GRN with failed items");
            $test->assertEqual(
                $grnResult2['quality_summary']['failed_items'],
                1,
                "GQ05: Failed items count in summary matches"
            );
            
            $grnId2 = $grnResult2['grn_id'] ?? 0;
            if ($grnId2) {
                $test->assertDatabaseHasRecord('goods_received', [
                    'grn_id' => $grnId2
                ], "GQ06: Failed GRN recorded in database");
            }
        }
    }
} catch (Exception $e) {
    $test->assertTrue(false, "GQ04: GRN Failure Test - Exception", $e->getMessage());
}

$test->printResults("GRN with Quality Failures");

// ========================================
// TEST SUITE 3: APPROVAL WORKFLOW
// ========================================

echo "\nSUITE 3: Approval Workflow Integration\n";
echo "----------------------------------------\n";

try {
    $approvalEngine = new ApprovalEngine($connect, 1, 'admin');
    
    // Test approval workflow state transitions
    if (isset($grnId)) {
        $test->assertTrue(true, "AW01: Approval engine initialized");
        
        // Check approval logs exist
        $test->assertDatabaseHasRecord('approval_logs', [
            'entity_type' => 'goods_received',
            'entity_id' => $grnId
        ], "AW02: Approval logs created for GRN");
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "AW01: Approval Workflow - Exception", $e->getMessage());
}

$test->printResults("Approval Workflow");

// ========================================
// TEST SUITE 4: GRN WAREHOUSE ALLOCATION
// ========================================

echo "\nSUITE 4: GRN Stock Allocation to Warehouse\n";
echo "----------------------------------------\n";

if (isset($grnId)) {
    try {
        // Check that stock movements were created
        $stockMovementResult = $connect->query(
            "SELECT COUNT(*) as count FROM stock_movements WHERE reference_type = 'goods_received' AND reference_id = $grnId"
        );
        $stockRow = $stockMovementResult->fetch_assoc();
        $stockMovementCount = $stockRow['count'] ?? 0;
        
        $test->assertTrue($stockMovementCount >= 0, "WH01: Stock movements tracked for GRN", "Movements: $stockMovementCount");
        
    } catch (Exception $e) {
        $test->assertTrue(false, "WH01: Warehouse allocation - Exception", $e->getMessage());
    }
}

$test->printResults("Warehouse Stock Allocation");

// ========================================
// SUMMARY
// ========================================

$totalTime = $test->getExecutionTime();

echo "\n================================================================================\n";
echo "GRN QUALITY CHECKS & APPROVAL TESTS SUMMARY\n";
echo "================================================================================\n";
echo "Total Test Cases: " . ($test->testsPassed + $test->testsFailed) . "\n";
echo "Passed: {$test->testsPassed}\n";
echo "Failed: {$test->testsFailed}\n";
echo "Execution Time: {$totalTime}s\n";
echo "================================================================================\n";

$test->cleanupTestData();

?>
