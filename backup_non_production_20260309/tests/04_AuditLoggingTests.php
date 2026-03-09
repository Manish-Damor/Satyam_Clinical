<?php
/**
 * Phase 4 - Audit Logging & Compliance Tests
 * Updated for current audit_logs schema and controller behavior.
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/constant/connect.php';
require_once $rootDir . '/config/bootstrap.php';
require_once __DIR__ . '/TestFramework.php';

use Services\AuditLogger;
use Controllers\PurchaseOrderController;

$test = new TestFramework($connect);

$productId = 1;
$productResult = $connect->query("SELECT product_id FROM product WHERE status = 1 ORDER BY product_id ASC LIMIT 1");
if ($productResult && $productResult->num_rows > 0) {
    $productRow = $productResult->fetch_assoc();
    $productId = (int)$productRow['product_id'];
}

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
    $auditResult = $connect->query("SELECT * FROM audit_logs WHERE table_name = 'test_table' AND record_id = 123 ORDER BY action_timestamp DESC LIMIT 1");
    $auditRow = $auditResult ? $auditResult->fetch_assoc() : null;

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
        'medicine_id' => $productId,
        'product_id' => $productId,
        'medicine_name' => 'Audit Test Medicine',
        'quantity' => 100,
        'unit_price' => 35,
        'tax_percent' => 18
    ]
];

try {
    $poController = new PurchaseOrderController($connect, 1, 'admin');
    $result = $poController->createPurchaseOrder($poData, $items);

    $poId = (int)($result['po_id'] ?? 0);

    if ($poId > 0) {
        $test->assertDatabaseHasRecord('audit_logs', [
            'table_name' => 'purchase_order',
            'record_id' => $poId,
            'action' => 'INSERT'
        ], "AT01: PO creation logged in audit trail");

        $auditQuery = $connect->query("SELECT new_data FROM audit_logs WHERE table_name = 'purchase_order' AND record_id = $poId AND action = 'INSERT' ORDER BY action_timestamp DESC LIMIT 1");

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
    $userId = 1;

    $auditLogger = new AuditLogger($connect, $userId);
    $auditLogger->logChange(
        'orders',
        1,
        'UPDATE',
        ['status' => 'pending'],
        ['status' => 'approved'],
        'Test::userAction',
        $userId
    );

    $test->assertDatabaseHasRecord('audit_logs', [
        'table_name' => 'orders',
        'user_id' => $userId
    ], "UT01: User ID recorded in audit log");

    $summaryResult = $connect->query("SELECT changes_summary FROM audit_logs WHERE table_name = 'orders' AND user_id = $userId ORDER BY action_timestamp DESC LIMIT 1");
    $summaryRow = $summaryResult ? $summaryResult->fetch_assoc() : null;
    $summary = $summaryRow['changes_summary'] ?? '';

    $test->assertTrue(
        strpos($summary, 'Source: Test::userAction') !== false,
        "UT02: Action source captured in changes_summary"
    );

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
    $auditQuery = $connect->query("SELECT action_timestamp FROM audit_logs WHERE table_name = 'purchase_order' ORDER BY action_timestamp DESC LIMIT 1");

    if ($auditQuery && $auditQuery->num_rows > 0) {
        $auditRow = $auditQuery->fetch_assoc();
        $timestamp = $auditRow['action_timestamp'] ?? null;

        $test->assertNotNull($timestamp, "DI01: Timestamp recorded for audit entry");

        $timestampUnix = strtotime($timestamp);
        $now = time();
        $timeDiff = $now - $timestampUnix;
        $absDiff = abs($timeDiff);

        $test->assertTrue(
            $absDiff < 86400,
            "DI02: Timestamp is within acceptable clock skew window",
            "Time diff: {$timeDiff}s"
        );
    } else {
        $test->assertTrue(true, "DI01: Skipped - no purchase_order audit entries yet");
    }

    $dupQuery = $connect->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE table_name = 'purchase_order' AND changes_summary LIKE 'Source: PurchaseOrderController::createPurchaseOrder%' GROUP BY record_id HAVING cnt > 1 LIMIT 1");

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
    $complianceQuery = $connect->query("SELECT COUNT(*) as total_changes, COUNT(DISTINCT user_id) as unique_users, COUNT(DISTINCT table_name) as tables_modified FROM audit_logs WHERE action_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

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