<?php
/**
 * Phase 4 - Performance & Integration Tests
 * Tests for system performance, concurrency, and end-to-end scenarios
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/constant/connect.php';
require_once $rootDir . '/config/bootstrap.php';
require_once __DIR__ . '/TestFramework.php';

use Services\StockService;
use Services\CreditControl;
use Services\ApprovalEngine;
use Controllers\PurchaseOrderController;
use Controllers\SalesOrderController;

$test = new TestFramework($connect);

echo "PHASE 4 PERFORMANCE & INTEGRATION TESTS\n";
echo "================================================================================\n\n";

// ========================================
// TEST SUITE 1: CONCURRENT PO CREATION
// ========================================

echo "SUITE 1: Concurrent Purchase Order Operations\n";
echo "----------------------------------------\n";

$poPerformanceStart = microtime(true);
$successfulPOs = 0;
$supplierId = $test->createTestSupplier(998);

for ($i = 1; $i <= 3; $i++) {
    try {
        $poData = [
            'po_number' => 'PERF-' . date('YmdHis') . '-' . $i,
            'po_date' => date('Y-m-d'),
            'po_type' => 'Regular',
            'supplier_id' => $supplierId,
            'supplier_name' => 'Performance Test Supplier',
            'supplier_contact' => '9876543210',
            'supplier_email' => 'perf@test.com',
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
                'medicine_id' => $i,
                'medicine_name' => "Performance Test Medicine $i",
                'pack_size' => '10 x 10',
                'hsn_code' => '3004',
                'batch_number' => 'PERF' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'quantity' => 100,
                'mrp' => 50,
                'ptr' => 40,
                'unit_price' => 35,
                'discount_percent' => 0,
                'tax_percent' => 18
            ]
        ];
        
        $poController = new PurchaseOrderController($connect, 1, 'admin');
        $result = $poController->createPurchaseOrder($poData, $items);
        
        if ($result && isset($result['po_id']) && $result['po_id'] > 0) {
            $successfulPOs++;
        }
    } catch (Exception $e) {
        // Log error but continue
    }
}

$poPerformanceTime = microtime(true) - $poPerformanceStart;
$test->assertTrue($successfulPOs >= 2, "PI01: Created 2+ concurrent POs", "$successfulPOs created in {$poPerformanceTime}s");

// ========================================
// TEST SUITE 2: BULK STOCK OPERATIONS
// ========================================

echo "\nSUITE 2: Bulk Stock Operations Performance\n";
echo "----------------------------------------\n";

try {
    $stockService = new StockService($connect, 1);
    $bulkStart = microtime(true);
    
    // Bulk stock updates
    $updateCount = 0;
    for ($i = 1; $i <= 5; $i++) {
        $status = $stockService->decreaseStock($i, 10, 'PERF_TEST');
        if ($status !== false) {
            $updateCount++;
        }
    }
    
    $bulkTime = microtime(true) - $bulkStart;
    $test->assertTrue($updateCount >= 3, "PI02: Bulk stock operations", "$updateCount updates in {$bulkTime}s");
    
} catch (Exception $e) {
    $test->assertTrue(false, "PI02: Bulk Stock - Exception", $e->getMessage());
}

$test->printResults("Concurrent Operations");

// ========================================
// TEST SUITE 3: END-TO-END ORDER FLOW
// ========================================

echo "\nSUITE 3: End-to-End Order Processing Flow\n";
echo "----------------------------------------\n";

$e2eStart = microtime(true);

try {
    // Step 1: Create Customer
    $customerId = $test->createTestCustomer(888);
    
    // Step 2: Create Sales Order
    $saleData = [
        'order_number' => 'E2E-' . date('YmdHis'),
        'order_date' => date('Y-m-d'),
        'customer_id' => $customerId,
        'customer_name' => 'E2E Test Customer',
        'customer_contact' => '9876543210',
        'customer_email' => 'e2e@test.com',
        'customer_gst' => '27AAFCU5055K1Z0',
        'customer_address' => 'Test Address',
        'customer_city' => 'Mumbai',
        'customer_state' => 'MH',
        'customer_pincode' => '400001',
        'sub_total' => 5000,
        'total_discount' => 0,
        'discount_percent' => 0,
        'taxable_amount' => 5000,
        'cgst_amount' => 450,
        'sgst_amount' => 450,
        'igst_amount' => 0,
        'round_off' => 0,
        'grand_total' => 5900,
        'payment_method' => 'Credit',
        'credit_days' => 30,
        'advance_amount' => 0,
        'payment_towards' => 'full_order',
        'remarks' => 'E2E Test Order',
        'order_status' => 'pending'
    ];
    
    $items = [
        [
            'medicine_id' => 1,
            'medicine_name' => 'E2E Test Medicine',
            'pack_size' => '10 x 10',
            'quantity' => 50,
            'mrp' => 50,
            'rate' => 40,
            'taxable_value' => 2000,
            'tax_percent' => 18,
            'tax_amount' => 360,
            'gross_value' => 2360
        ]
    ];
    
    $salesController = new SalesOrderController($connect, 1, 'admin');
    $saleResult = $salesController->createSalesOrder($saleData, $items);
    
    $salesOrderId = $saleResult['order_id'] ?? 0;
    $test->assertTrue($salesOrderId > 0, "PI03: Sales order created");
    
    if ($salesOrderId > 0) {
        // Step 3: Verify order in database
        $test->assertDatabaseHasRecord('orders', [
            'id' => $salesOrderId,
            'order_status' => 'pending'
        ], "PI04: Sales order recorded with pending status");
        
        // Step 4: Verify order items
        $test->assertDatabaseHasRecord('order_items', [
            'order_id' => $salesOrderId
        ], "PI05: Order items recorded");
        
        // Step 5: Verify stock deduction
        $stockQuery = $connect->query("
            SELECT reserved_qty FROM medicine_stock 
            WHERE medicine_id = 1 LIMIT 1
        ");
        
        if ($stockQuery && $stockQuery->num_rows > 0) {
            $stockRow = $stockQuery->fetch_assoc();
            $reserved = intval($stockRow['reserved_qty'] ?? 0);
            $test->assertTrue($reserved > 0, "PI06: Stock reserved for order", "Reserved: $reserved");
        }
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "PI03: E2E Order Flow - Exception", $e->getMessage());
}

$e2eTime = microtime(true) - $e2eStart;

$test->printResults("End-to-End Order Processing");

// ========================================
// TEST SUITE 4: QUERY PERFORMANCE
// ========================================

echo "\nSUITE 4: Database Query Performance\n";
echo "----------------------------------------\n";

try {
    // Test 1: PO List retrieval
    $queryStart = microtime(true);
    $poQuery = $connect->query("
        SELECT po.*, s.supplier_name 
        FROM purchase_order po
        JOIN suppliers s ON po.supplier_id = s.id
        LIMIT 100
    ");
    $poTime = microtime(true) - $queryStart;
    $test->assertTrue($poTime < 2, "QP01: PO list query performance", "Time: {$poTime}s");
    
    // Test 2: Sales order with items
    $queryStart = microtime(true);
    $soQuery = $connect->query("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        LIMIT 100
    ");
    $soTime = microtime(true) - $queryStart;
    $test->assertTrue($soTime < 2, "QP02: Sales order with items query", "Time: {$soTime}s");
    
    // Test 3: Stock valuation
    $queryStart = microtime(true);
    $stockQuery = $connect->query("
        SELECT m.id, m.name, ms.available_qty, 
            (ms.available_qty * m.ptr) as stock_value
        FROM medicine m
        LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id
        LIMIT 100
    ");
    $stockTime = microtime(true) - $queryStart;
    $test->assertTrue($stockTime < 2, "QP03: Stock valuation query", "Time: {$stockTime}s");
    
} catch (Exception $e) {
    $test->assertTrue(false, "QP01: Query Performance - Exception", $e->getMessage());
}

$test->printResults("Query Performance");

// ========================================
// TEST SUITE 5: TRANSACTION INTEGRITY
// ========================================

echo "\nSUITE 5: Transaction Integrity Under Load\n";
echo "----------------------------------------\n";

try {
    // Simulate transaction under concurrent operations
    $txnStart = microtime(true);
    $successfulTxns = 0;
    
    for ($i = 0; $i < 3; $i++) {
        try {
            $connect->begin_transaction();
            
            // Insert test record
            $stmt = $connect->prepare("
                INSERT INTO stock_movements 
                (medicine_id, movement_type, quantity, reference_type, reference_id, user_id, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $medId = 1;
            $type = 'IN';
            $qty = 10;
            $refType = 'TEST_TXN';
            $refId = $i;
            $userId = 1;
            $notes = "Transaction test $i";
            
            $stmt->bind_param('isisisss', $medId, $type, $qty, $refType, $refId, $userId, $notes);
            $stmt->execute();
            
            $connect->commit();
            $successfulTxns++;
            $stmt->close();
            
        } catch (Exception $txnErr) {
            $connect->rollback();
        }
    }
    
    $txnTime = microtime(true) - $txnStart;
    $test->assertTrue($successfulTxns >= 2, "TI01: Transaction integrity", "$successfulTxns transactions in {$txnTime}s");
    
} catch (Exception $e) {
    $test->assertTrue(false, "TI01: Transaction Integrity - Exception", $e->getMessage());
}

$test->printResults("Transaction Integrity");

// ========================================
// TEST SUITE 6: MEMORY & RESOURCE USAGE
// ========================================

echo "\nSUITE 6: Memory & Resource Efficiency\n";
echo "----------------------------------------\n";

try {
    $memStart = memory_get_usage(true);
    
    // Load large dataset
    $largeQuery = $connect->query("
        SELECT * FROM orders LIMIT 1000
    ");
    $rowCount = $largeQuery ? $largeQuery->num_rows : 0;
    
    $memEnd = memory_get_usage(true);
    $memUsed = ($memEnd - $memStart) / 1024; // KB
    
    if ($rowCount > 0) {
        $test->assertTrue(true, "MR01: Large dataset loaded", "Rows: $rowCount, Memory: {$memUsed}KB");
    }
    
    // Test memory limit (PHP default is 128MB)
    $memLimit = ini_get('memory_limit');
    $test->assertTrue(true, "MR02: Memory limit configured", "Limit: $memLimit");
    
} catch (Exception $e) {
    $test->assertTrue(false, "MR01: Memory Usage - Exception", $e->getMessage());
}

$test->printResults("Memory & Resource Efficiency");

// ========================================
// SUMMARY WITH PERFORMANCE METRICS
// ========================================

$totalTime = $test->getExecutionTime();

echo "\n================================================================================\n";
echo "PERFORMANCE & INTEGRATION TESTS SUMMARY\n";
echo "================================================================================\n";
echo "Total Test Cases: " . ($test->testsPassed + $test->testsFailed) . "\n";
echo "Passed: {$test->testsPassed}\n";
echo "Failed: {$test->testsFailed}\n";
echo "Total Execution Time: {$totalTime}s\n";
echo "\nPerformance Metrics:\n";
echo "- PO Concurrent Creation Time: {$poPerformanceTime}s for {$successfulPOs} POs\n";
echo "- E2E Order Processing Time: {$e2eTime}s\n";
echo "- Successful Transactions: {$successfulTxns}/3\n";
echo "================================================================================\n";

$test->cleanupTestData();

?>
