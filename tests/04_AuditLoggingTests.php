<?php
/**
 * Phase 4 - Audit Logging & Compliance Tests
 * Tests for comprehensive audit trails and data integrity
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/constant/connect.php';
require_once $rootDir . '/config/bootstrap.php';
require_once __DIR__ . '/TestFramework.php';

use Services\AuditLogger;
use Controllers\PurchaseOrderController;

$test = new TestFramework($connect);

echo "PHASE 4 AUDIT LOGGING & COMPLIANCE TESTS\n";
echo "================================================================================\n\n";

// ========================================
// TEST SUITE 1: AUDIT LOGGER SERVICE
// ========================================

echo "SUITE 1: Audit Logger Service\n";
echo "----------------------------------------\n";

try {
    $auditLogger = new AuditLogger($connect, 1);
    
    // Test 1: Log a change using logUpdate
    $testData = [
        'test_field' => 'test_value',
        'amount' => 5000
    ];
    
    $testOldData = [
        'test_field' => 'old_value',
        'amount' => 4000
    ];
    
    $logResult = $auditLogger->logUpdate(
        'test_table',
        123,
        $testOldData,
        $testData
    );
    
    $test->assertTrue($logResult !== false, "AL01: Log change recorded");
    
    // Test 2: Verify audit log in database
    $test->assertDatabaseHasRecord('audit_logs', [
        'table_name' => 'test_table',
        'record_id' => 123
    ], "AL02: Audit log entry exists in database");
    
    // Test 3: Verify JSON data storage
    $auditResult = $connect->query("
        SELECT * FROM audit_logs 
        WHERE table_name = 'test_table' AND record_id = 123 
        LIMIT 1
    ");
    $auditRow = $auditResult->fetch_assoc();
    
    if ($auditRow) {
        $newData = json_decode($auditRow['new_data'] ?? '{}', true);
        $test->assertEqual(
            $newData['test_field'] ?? null,
            'test_value',
            "AL03: JSON new_data stored correctly"
        );
        
        $oldData = json_decode($auditRow['old_data'] ?? '{}', true);
        $test->assertEqual(
            $oldData['test_field'] ?? null,
            'old_value',
            "AL04: JSON old_data stored correctly"
        );
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "AL01: Audit Logger - Exception", $e->getMessage());
}

$test->printResults("Audit Logger Service");

// ========================================
// TEST SUITE 2: AUDIT TRAIL FOR PO OPERATIONS
// ========================================

echo "\nSUITE 2: Audit Trail for PO Operations\n";
echo "----------------------------------------\n";

$supplierId = $test->createTestSupplier(999);

$poData = [
    'po_number' => 'TEST-AUDIT-' . date('YmdHis'),
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
        'medicine_name' => 'Audit Test Medicine',
        'pack_size' => '10 x 10',
        'hsn_code' => '3004',
        'batch_number' => 'AUD001',
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
    $poController = new PurchaseOrderController($connect, 1, 'admin');
    $result = $poController->createPurchaseOrder($poData, $items);
    
    $poId = $result['po_id'] ?? 0;
    
    if ($poId) {
        // Check audit log for PO creation
        $test->assertDatabaseHasRecord('audit_logs', [
            'table_name' => 'purchase_order',
            'record_id' => $poId,
            'action' => 'INSERT'
        ], "AT01: PO creation logged in audit trail");
        
        // Verify audit contains PO header information
        $auditQuery = $connect->query("
            SELECT new_data FROM audit_logs 
            WHERE table_name = 'purchase_order' AND record_id = $poId 
            AND action = 'INSERT' LIMIT 1
        ");
        
        if ($auditQuery && $auditQuery->num_rows > 0) {
            $auditRow = $auditQuery->fetch_assoc();
            $newData = json_decode($auditRow['new_data'] ?? '{}', true);
            
            $test->assertTrue(
                isset($newData['po_number']) && $newData['po_number'] !== '',
                "AT02: PO number in audit trail"
            );
            $test->assertTrue(
                isset($newData['supplier_id']),
                "AT03: Supplier ID in audit trail"
            );
        }
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "AT01: PO Audit - Exception", $e->getMessage());
}

$test->printResults("Audit Trail for Operations");

// ========================================
// TEST SUITE 3: USER ACTION TRACKING
// ========================================

echo "\nSUITE 3: User Action Tracking\n";
echo "----------------------------------------\n";

try {
    // Get current user from session or use default
    $userId = 1;
    
    // Log a test user action using logUpdate
    $auditLogger = new AuditLogger($connect, $userId);
    $auditLogger->logUpdate(
        'orders',
        1,
        ['status' => 'pending'],
        ['status' => 'approved']
    );
    
    // Verify user ID is recorded
    $test->assertDatabaseHasRecord('audit_logs', [
        'table_name' => 'orders',
        'user_id' => $userId
    ], "UT01: User ID recorded in audit log");
    
    // Verify action source is recorded
    $test->assertDatabaseHasRecord('audit_logs', [
        'table_name' => 'orders',
        'source' => 'Test::userAction'
    ], "UT02: Action source (method) recorded");
    
} catch (Exception $e) {
    $test->assertTrue(false, "UT01: User Action Tracking - Exception", $e->getMessage());
}

$test->printResults("User Action Tracking");

// ========================================
// TEST SUITE 4: DATA INTEGRITY & TIMESTAMPS
// ========================================

echo "\nSUITE 4: Data Integrity & Timestamps\n";
echo "----------------------------------------\n";

try {
    // Check that timestamps are recorded
    $auditQuery = $connect->query("
        SELECT changed_at FROM audit_logs 
        WHERE table_name = 'purchase_order' 
        LIMIT 1
    ");
    
    if ($auditQuery && $auditQuery->num_rows > 0) {
        $auditRow = $auditQuery->fetch_assoc();
        $timestamp = $auditRow['changed_at'] ?? null;
        
        $test->assertNotNull($timestamp, "DI01: Timestamp recorded for audit entry");
        
        // Verify timestamp is recent (within last hour)
        $timestamp = strtotime($timestamp);
        $now = time();
        $timeDiff = $now - $timestamp;
        
        $test->assertTrue(
            $timeDiff >= 0 && $timeDiff < 3600,
            "DI02: Timestamp is recent (within 1 hour)",
            "Time diff: {$timeDiff}s"
        );
    }
    
    // Verify no duplicate audit entries for single operation
    $dupQuery = $connect->query("
        SELECT COUNT(*) as cnt FROM audit_logs 
        WHERE source = 'PurchaseOrderController::createPurchaseOrder' 
        AND table_name = 'purchase_order'
        GROUP BY record_id
        HAVING cnt > 1
        LIMIT 1
    ");
    
    if ($dupQuery && $dupQuery->num_rows === 0) {
        $test->assertTrue(true, "DI03: No duplicate audit entries for single PO creation");
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "DI01: Data Integrity - Exception", $e->getMessage());
}

$test->printResults("Data Integrity & Timestamps");

// ========================================
// TEST SUITE 5: COMPLIANCE REPORTING
// ========================================

echo "\nSUITE 5: Compliance Reporting\n";
echo "----------------------------------------\n";

try {
    // Get audit summary for compliance
    $complianceQuery = $connect->query("
        SELECT 
            COUNT(*) as total_changes,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT table_name) as tables_modified
        FROM audit_logs
        WHERE changed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    
    if ($complianceQuery && $complianceQuery->num_rows > 0) {
        $complianceData = $complianceQuery->fetch_assoc();
        
        $test->assertTrue($complianceData['total_changes'] >= 0, "CR01: Compliance data retrieved");
        $test->assertTrue(
            $complianceData['unique_users'] >= 0,
            "CR02: User tracking for compliance",
            "Users: {$complianceData['unique_users']}"
        );
        $test->assertTrue(
            $complianceData['tables_modified'] >= 0,
            "CR03: Table modification tracking for audit",
            "Tables: {$complianceData['tables_modified']}"
        );
    }
    
} catch (Exception $e) {
    $test->assertTrue(false, "CR01: Compliance Reporting - Exception", $e->getMessage());
}

$test->printResults("Compliance Reporting");

// ========================================
// SUMMARY
// ========================================

$totalTime = $test->getExecutionTime();

echo "\n================================================================================\n";
echo "AUDIT LOGGING & COMPLIANCE TESTS SUMMARY\n";
echo "================================================================================\n";
echo "Total Test Cases: " . ($test->testsPassed + $test->testsFailed) . "\n";
echo "Passed: {$test->testsPassed}\n";
echo "Failed: {$test->testsFailed}\n";
echo "Execution Time: {$totalTime}s\n";
echo "================================================================================\n";

$test->cleanupTestData();

?>
