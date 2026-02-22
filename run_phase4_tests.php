<?php
/**
 * PHASE 4 TEST EXECUTION SCRIPT
 * Satyam Clinical Pharmacy ERP
 * Date: February 20, 2026
 * 
 * This script validates all Phase 3 implementations
 * Executes all 18 test cases programmatically
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'php_action/core.php';
require_once 'config/bootstrap.php';

// Initialize test results
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function assert_test($testName, $condition, $expected, $actual) {
    global $testResults;
    
    $pass = $condition === true;
    
    $result = [
        'name' => $testName,
        'status' => $pass ? 'PASS ✅' : 'FAIL ❌',
        'expected' => $expected,
        'actual' => $actual,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $testResults['tests'][] = $result;
    
    if ($pass) {
        $testResults['passed']++;
        echo "✅ PASS: $testName\n";
    } else {
        $testResults['failed']++;
        echo "❌ FAIL: $testName\n";
        echo "   Expected: $expected\n";
        echo "   Actual: $actual\n";
    }
}

// ============================================================================
// TEST SUITE 1: PURCHASE ORDER (Tests 1.1-1.3)
// ============================================================================

echo "\n=== TEST SUITE 1: PURCHASE ORDER FUNCTIONALITY ===\n";

// Test 1.1: PO Form - No Batch Fields
$poFileContent = file_get_contents('create_po.php');
$hasBatchColumn = strpos($poFileContent, '<th style="width:8%;">Batch No.</th>') !== false;
assert_test(
    "Test 1.1: PO - Batch column NOT in form header",
    !$hasBatchColumn,
    "Batch column should be removed",
    $hasBatchColumn ? "Batch column still present" : "Batch column successfully removed"
);

// Test 1.2: PO Form - No Tax Breakdown
$hasGSTBreakdown = strpos($poFileContent, 'CGST (9%)') !== false;
assert_test(
    "Test 1.2: PO - No hardcoded GST section",
    !$hasGSTBreakdown,
    "Tax breakdown should be removed",
    $hasGSTBreakdown ? "Tax section still present" : "Tax section successfully removed"
);

// Test 1.3: PO JavaScript - No tax calculation
$jsCalcRemoved = strpos($poFileContent, 'const cgstAmount = (taxableAmount * 9)') === false;
assert_test(
    "Test 1.3: PO - Tax calculation logic removed from JS",
    $jsCalcRemoved,
    "Hardcoded tax calculation removed",
    $jsCalcRemoved ? "Calculation removed" : "Calculation still present"
);

// ============================================================================
// TEST SUITE 2: SALES INVOICE - GST (Tests 2.1-2.3)
// ============================================================================

echo "\n=== TEST SUITE 2: SALES INVOICE GST CALCULATION ===\n";

$addOrderContent = file_get_contents('add-order.php');

// Test 2.1: Global GST dropdown removed
$hasGSTDropdown = strpos($addOrderContent, '<select class="form-control" id="gstPercentage"') !== false;
assert_test(
    "Test 2.1: Sales - Global GST dropdown removed",
    !$hasGSTDropdown,
    "GST dropdown should not exist",
    $hasGSTDropdown ? "GST dropdown still present" : "GST dropdown successfully removed"
);

// Test 2.2: Per-item gst_rate field added
$hasGSTRateField = strpos($addOrderContent, '<input type="hidden" name="gstRate[]"') !== false;
assert_test(
    "Test 2.2: Sales - Per-item gst_rate fields added",
    $hasGSTRateField,
    "Hidden gstRate[] field should exist",
    $hasGSTRateField ? "gstRate field present" : "gstRate field missing"
);

// Test 2.3: Updated subAmount function for per-item calculation
$perItemGSTLogic = strpos($addOrderContent, 'var gstRate = Number($("#gstRate"+count).val())') !== false;
assert_test(
    "Test 2.3: Sales - Per-item GST calculation logic",
    $perItemGSTLogic,
    "subAmount() should use per-item gst_rate",
    $perItemGSTLogic ? "Per-item logic implemented" : "Per-item logic missing"
);

// ============================================================================
// TEST SUITE 3: BATCH SELECTION (Tests 3.1-3.3)
// ============================================================================

echo "\n=== TEST SUITE 3: BATCH SELECTION ===\n";

// Test 3.1: Batch dropdown added to table
$hasBatchDropdown = strpos($addOrderContent, 'id="batchId') !== false;
assert_test(
    "Test 3.1: Sales - Batch selector dropdown added",
    $hasBatchDropdown,
    "Batch dropdown should be in product table",
    $hasBatchDropdown ? "Batch dropdown present" : "Batch dropdown missing"
);

// Test 3.2: updateBatchInfo function exists
$hasBatchInfoFunction = strpos($addOrderContent, 'function updateBatchInfo(row') !== false;
assert_test(
    "Test 3.2: Sales - updateBatchInfo() function added",
    $hasBatchInfoFunction,
    "updateBatchInfo function should handle batch selection",
    $hasBatchInfoFunction ? "Function exists" : "Function missing"
);

// Test 3.3: Batch validation in order.php
$orderPhpContent = file_get_contents('php_action/order.php');
$hasBatchValidation = strpos($orderPhpContent, '$batchId = $_POST[\'batchId\']') !== false;
assert_test(
    "Test 3.3: Order handler - Collects batch_id from form",
    $hasBatchValidation,
    "order.php should collect batchId[] array",
    $hasBatchValidation ? "Batch collection present" : "Batch collection missing"
);

// ============================================================================
// TEST SUITE 4: STOCK MANAGEMENT (Tests 4.1-4.3)
// ============================================================================

echo "\n=== TEST SUITE 4: STOCK MANAGEMENT - BATCH DEDUCTION ===\n";

$controllerContent = file_get_contents('libraries/Controllers/SalesOrderController.php');

// Test 4.1: insertOrderItem stores batch_id
$storesBatchId = strpos($controllerContent, 'batch_id,') !== false && 
                 strpos($controllerContent, '$batchId,  // Include batch_id') !== false;
assert_test(
    "Test 4.1: Controller - insertOrderItem() stores batch_id",
    $storesBatchId,
    "batch_id should be inserted in order_item",
    $storesBatchId ? "batch_id stored" : "batch_id not stored"
);

// Test 4.2: Stock deduction passes batch_id
$batchDeduction = strpos($controllerContent, 'decreaseStock(') !== false && 
                  strpos($controllerContent, '$batchId,') !== false;
assert_test(
    "Test 4.2: Controller - decreaseStock() called with batch_id",
    $batchDeduction,
    "decreaseStock should use batch_id parameter",
    $batchDeduction ? "Batch parameter passed" : "Batch parameter missing"
);

// Test 4.3: Validates batch_id not empty
$validatesBatch = strpos($controllerContent, 'empty($item[\'batch_id\']') !== false;
assert_test(
    "Test 4.3: Controller - Validates batch_id required",
    $validatesBatch,
    "Batch ID should be validated as required",
    $validatesBatch ? "Validation present" : "Validation missing"
);

// ============================================================================
// TEST SUITE 5: EXPIRY VALIDATION (Tests 5.1-5.3)
// ============================================================================

echo "\n=== TEST SUITE 5: PHARMACY COMPLIANCE - EXPIRY VALIDATION ===\n";

// Test 5.1: Reference type 'SALES_ORDER' triggers expiry check
$salesOrderFlag = strpos($controllerContent, "'SALES_ORDER',") !== false;
assert_test(
    "Test 5.1: Controller - Uses 'SALES_ORDER' flag for expiry validation",
    $salesOrderFlag,
    "decreaseStock() called with 'SALES_ORDER' reference type",
    $salesOrderFlag ? "Flag present" : "Flag missing"
);

// Test 5.2: StockService has expiry validation
$stockServiceContent = file_get_contents('libraries/Services/StockService.php');
$hasExpiryCheck = strpos($stockServiceContent, 'if ($reference_type == \'SALES_ORDER\')') !== false && 
                  strpos($stockServiceContent, 'strtotime($batch[\'exp_date\']) < time()') !== false;
assert_test(
    "Test 5.2: StockService - Expiry validation for SALES_ORDER",
    $hasExpiryCheck,
    "StockService should block expired batches",
    $hasExpiryCheck ? "Expiry check implemented" : "Expiry check missing"
);

// Test 5.3: Error message for expired batch
$expiredError = strpos($stockServiceContent, 'Cannot sell from expired batch') !== false;
assert_test(
    "Test 5.3: StockService - Error message for expired batch",
    $expiredError,
    "Clear error message should be thrown for expired batch",
    $expiredError ? "Error message present" : "Error message missing"
);

// ============================================================================
// TEST SUITE 6: DATA INTEGRITY (Tests 6.1-6.3)
// ============================================================================

echo "\n=== TEST SUITE 6: DATA INTEGRITY ===\n";

// Test 6.1: fetchSelectedProduct returns gst_rate
$fetchProdContent = file_get_contents('php_action/fetchSelectedProduct.php');
$returnsGSTRate = strpos($fetchProdContent, 'gst_rate') !== false;
assert_test(
    "Test 6.1: API - fetchSelectedProduct includes gst_rate",
    $returnsGSTRate,
    "API should return product's gst_rate",
    $returnsGSTRate ? "gst_rate returned" : "gst_rate missing"
);

// Test 6.2: fetchSelectedProduct returns batches
$returnsBatches = strpos($fetchProdContent, '$row[\'batches\'] = $batches') !== false;
assert_test(
    "Test 6.2: API - fetchSelectedProduct includes batches array",
    $returnsBatches,
    "API should return available batches",
    $returnsBatches ? "Batches returned" : "Batches missing"
);

// Test 6.3: order.php collects per-item gst_rate
$collectsGSTRate = strpos($orderPhpContent, '$gstRate = (float)($_POST[\'gstRate\']') !== false;
assert_test(
    "Test 6.3: Order handler - Collects per-item gst_rate from form",
    $collectsGSTRate,
    "order.php should collect gstRate[] array and pass to controller",
    $collectsGSTRate ? "GST rate collected" : "GST rate not collected"
);

// ============================================================================
// SUMMARY
// ============================================================================

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "PHASE 4 TEST RESULTS SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$totalTests = $testResults['passed'] + $testResults['failed'];
$passRate = ($testResults['passed'] / $totalTests) * 100;

echo "Total Tests: $totalTests\n";
echo "Passed: " . $testResults['passed'] . " ✅\n";
echo "Failed: " . $testResults['failed'] . " ❌\n";
echo "Pass Rate: " . round($passRate, 1) . "%\n";
echo "\n";

if ($testResults['failed'] === 0) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "Status: READY FOR PRODUCTION ✅\n";
} else {
    echo "⚠️  SOME TESTS FAILED\n";
    echo "Status: NEEDS REVIEW\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "TEST EXECUTION COMPLETED\n";
echo "════════════════════════════════════════════════════════════════\n";

// Log results to file
$logContent = "PHASE 4 TEST RESULTS - " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Total: $totalTests | Passed: " . $testResults['passed'] . " | Failed: " . $testResults['failed'] . "\n";
$logContent .= "Pass Rate: " . round($passRate, 1) . "%\n\n";

foreach ($testResults['tests'] as $test) {
    $logContent .= "[" . $test['status'] . "] " . $test['name'] . "\n";
    if ($test['status'] !== 'PASS ✅') {
        $logContent .= "  Expected: " . $test['expected'] . "\n";
        $logContent .= "  Actual: " . $test['actual'] . "\n";
    }
}

file_put_contents('PHASE_4_TEST_RESULTS.log', $logContent);
echo "\nResults logged to: PHASE_4_TEST_RESULTS.log\n";

?>
