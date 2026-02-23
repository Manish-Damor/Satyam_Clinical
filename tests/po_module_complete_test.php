<?php
/**
 * Comprehensive PO Module Test Suite
 * Validates the simplified pharmacy PO module with current schema
 */

chdir(__DIR__ . '/..');
require_once 'php_action/db_connect.php';
if (session_status() == PHP_SESSION_NONE) session_start();
$_SESSION['userId'] = 1;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

class POModuleTest {
    private $connect;
    private $passed = 0;
    private $failed = 0;
    private $testsPassed = [];
    private $testsFailed = [];
    
    public function __construct($conn) {
        $this->connect = $conn;
    }
    
    public function assert($condition, $testName, $message = '') {
        if ($condition) {
            $this->passed++;
            $this->testsPassed[] = $testName;
            echo "[✓] $testName\n";
        } else {
            $this->failed++;
            $this->testsFailed[] = "$testName: $message";
            echo "[✗] $testName: $message\n";
        }
    }
    
    public function testCreatePO() {
        echo "\n=== Test: Create Purchase Order ===\n";
        
        // Get supplier
        $supRes = $this->connect->query("SELECT supplier_id FROM suppliers WHERE supplier_status='Active' LIMIT 1");
        $sup = $supRes ? $supRes->fetch_assoc() : null;
        $this->assert($sup, "Supplier exists", "No active suppliers");
        if (!$sup) return;
        
        // Get product
        $prodRes = $this->connect->query("SELECT product_id, expected_mrp FROM product WHERE status=1 LIMIT 1");
        $prod = $prodRes ? $prodRes->fetch_assoc() : null;
        $this->assert($prod, "Product exists", "No active products");
        if (!$prod) return;
        
        // Prepare PO data
        $poNumber = 'TEST-' . time();
        $_POST = [
            'po_number' => $poNumber,
            'po_date' => date('Y-m-d'),
            'supplier_id' => $sup['supplier_id'],
            'expected_delivery_date' => date('Y-m-d', strtotime('+7 days')),
            'delivery_location' => 'Main Warehouse',
            'sub_total' => 0,
            'discount_percent' => 0,
            'total_discount' => 0,
            'gst_percent' => 0,
            'gst_amount' => 0,
            'other_charges' => 0,
            'grand_total' => 0,
            'item_count' => 1,
            'medicine_id' => [$prod['product_id']],
            'quantity' => [5],
            'unit_price' => [floatval($prod['expected_mrp'] ?? 10)],
            'gst_percentage' => [18]
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        $success = include 'php_action/createPurchaseOrder.php' ?: false; // check for successful execution
        ob_end_clean();
        
        // Check if PO was created
        $result = $this->connect->query("SELECT po_id FROM purchase_orders WHERE po_number = '$poNumber'");
        $po = $result ? $result->fetch_assoc() : null;
        $this->assert($po, "PO created and recorded", "PO not in database");
        
        if ($po) {
            $itemRes = $this->connect->query("SELECT COUNT(*) as cnt FROM po_items WHERE po_id = {$po['po_id']}");
            $itemCount = $itemRes ? $itemRes->fetch_assoc()['cnt'] : 0;
            $this->assert($itemCount > 0, "PO items inserted", "No items in PO");
            return $po['po_id'];
        }
        return null;
    }
    
    public function testPOWorkflow($poId) {
        echo "\n=== Test: PO Status Workflow ===\n";
        
        if (!$poId) {
            $this->assert(false, "PO workflow", "No PO ID provided");
            return;
        }
        
        // Test submit: Draft -> Submitted
        $_POST = ['action' => 'submit_po', 'po_id' => $poId];
        ob_start();
        include 'php_action/po_actions.php';
        $json = ob_get_clean();
        $resp = json_decode($json, true);
        $this->assert($resp['success'] ?? false, "Submit PO", $resp['error'] ?? 'Unknown');
        
        // Test approve: Submitted -> Approved
        $_POST = ['action' => 'approve_po', 'po_id' => $poId];
        ob_start();
        include 'php_action/po_actions.php';
        $json = ob_get_clean();
        $resp = json_decode($json, true);
        $this->assert($resp['success'] ?? false, "Approve PO", $resp['error'] ?? 'Unknown');
        
        // Test partial receive via update_received
        $itemRes = $this->connect->query("SELECT po_item_id, quantity_ordered FROM po_items WHERE po_id = $poId LIMIT 1");
        $item = $itemRes ? $itemRes->fetch_assoc() : null;
        if ($item) {
            $_POST = [
                'action' => 'update_received',
                'po_id' => $poId,
                'item_id' => [$item['po_item_id']],
                'quantity_received' => [floatval($item['quantity_ordered']) / 2]
            ];
            ob_start();
            include 'php_action/po_actions.php';
            $json = ob_get_clean();
            $resp = json_decode($json, true);
            $this->assert($resp['success'] ?? false, "Update received (partial)", $resp['error'] ?? 'Unknown');
            
            // Check status is PartialReceived
            $res = $this->connect->query("SELECT po_status FROM purchase_orders WHERE po_id = $poId");
            $row = $res ? $res->fetch_assoc() : null;
            $this->assert(($row['po_status'] ?? '') === 'PartialReceived', "PO status is PartialReceived", "Status: " . $row['po_status']);
        }
        
        // Test mark all received: PartialReceived -> Received
        $_POST = ['action' => 'mark_received', 'po_id' => $poId];
        ob_start();
        include 'php_action/po_actions.php';
        $json = ob_get_clean();
        $resp = json_decode($json, true);
        $this->assert($resp['success'] ?? false, "Mark all received", $resp['error'] ?? 'Unknown');
        
        // Test close: Received -> Closed
        $_POST = ['action' => 'close_po', 'po_id' => $poId];
        ob_start();
        include 'php_action/po_actions.php';
        $json = ob_get_clean();
        $resp = json_decode($json, true);
        $this->assert($resp['success'] ?? false, "Close PO", $resp['error'] ?? 'Unknown');
    }
    
    public function testInvalidTransitions($poId) {
        echo "\n=== Test: Invalid Transitions (should fail) ===\n";
        
        if (!$poId) return;
        
        // Try to submit a closed PO (should fail)
        $_POST = ['action' => 'submit_po', 'po_id' => $poId];
        ob_start();
        include 'php_action/po_actions.php';
        $json = ob_get_clean();
        $resp = json_decode($json, true);
        $this->assert(!($resp['success'] ?? false), "Reject invalid submit on closed PO", "Should have failed");
    }
    
    public function testPOListing() {
        echo "\n=== Test: PO Listing ===\n";
        
        $res = $this->connect->query("SELECT COUNT(*) as cnt FROM purchase_orders LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        $this->assert($row && $row['cnt'] > 0, "PO list query returns results", "No POs found");
    }
    
    public function printSummary() {
        echo "\n\n================================================================================\n";
        echo "PO MODULE TEST SUMMARY\n";
        echo "================================================================================\n";
        echo "Total Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        if ($this->failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->testsFailed as $fail) {
                echo "  - $fail\n";
            }
        }
        echo "================================================================================\n";
    }
}

// Run tests
$test = new POModuleTest($connect);
$test->testPOListing();
$poId = $test->testCreatePO();
if ($poId) {
    $test->testPOWorkflow($poId);
    $test->testInvalidTransitions($poId);
}
$test->printSummary();
?>
