<?php
/**
 * Phase 4 Test Framework - Test Utilities
 * Provides helper functions for comprehensive system testing
 */

class TestFramework {
    
    public $connect;
    public $testResults = [];
    public $testsPassed = 0;
    public $testsFailed = 0;
    public $startTime;
    
    public function __construct($connect) {
        $this->connect = $connect;
        $this->startTime = microtime(true);
    }
    
    /**
     * Assert that a condition is true
     */
    public function assertTrue($condition, $testName, $message = "") {
        if ($condition === true) {
            $this->recordPass($testName);
            return true;
        } else {
            $this->recordFail($testName, $message);
            return false;
        }
    }
    
    /**
     * Assert that a condition is false
     */
    public function assertFalse($condition, $testName, $message = "") {
        if ($condition === false) {
            $this->recordPass($testName);
            return true;
        } else {
            $this->recordFail($testName, $message);
            return false;
        }
    }
    
    /**
     * Assert that two values are equal
     */
    public function assertEqual($actual, $expected, $testName, $message = "") {
        if ($actual == $expected) {
            $this->recordPass($testName);
            return true;
        } else {
            $msg = $message ? $message . " | " : "";
            $msg .= "Expected: $expected, Got: $actual";
            $this->recordFail($testName, $msg);
            return false;
        }
    }
    
    /**
     * Assert that a value is not null
     */
    public function assertNotNull($value, $testName, $message = "") {
        if ($value !== null) {
            $this->recordPass($testName);
            return true;
        } else {
            $this->recordFail($testName, $message ?: "Value is null");
            return false;
        }
    }
    
    /**
     * Assert that a database table has a record
     */
    public function assertDatabaseHasRecord($table, $where, $testName, $message = "") {
        try {
            $sql = "SELECT COUNT(*) as count FROM `$table` WHERE ";
            $conditions = [];
            foreach ($where as $column => $value) {
                $conditions[] = "`$column` = '" . $this->connect->real_escape_string($value) . "'";
            }
            $sql .= implode(" AND ", $conditions);
            
            $result = $this->connect->query($sql);
            if (!$result) {
                $this->recordFail($testName, "SQL Error: " . $this->connect->error);
                return false;
            }
            
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $this->recordPass($testName);
                return true;
            } else {
                $this->recordFail($testName, $message ?: "Record not found in $table");
                return false;
            }
        } catch (Exception $e) {
            $this->recordFail($testName, $message . " - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record a passing test
     */
    private function recordPass($testName) {
        $this->testsPassed++;
        $this->testResults[] = [
            'name' => $testName,
            'status' => 'PASS',
            'message' => ''
        ];
    }
    
    /**
     * Record a failing test
     */
    private function recordFail($testName, $message) {
        $this->testsFailed++;
        $this->testResults[] = [
            'name' => $testName,
            'status' => 'FAIL',
            'message' => $message
        ];
    }
    
    /**
     * Create test data - Insert a supplier for testing
     */
    public function createTestSupplier($supplierId = 999) {
        $name = "Test Supplier " . date('His');
        $code = "TEST" . $supplierId;
        $contact = "9876543210";
        $email = "test" . $supplierId . "@supplier.com";
        
        $sql = "
            INSERT INTO suppliers (supplier_id, supplier_name, supplier_code, contact_person, email, phone, supplier_status)
            VALUES (?, ?, ?, ?, ?, ?, 'Active')
            ON DUPLICATE KEY UPDATE supplier_status = 'Active'
        ";
        
        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            return $supplierId;
        }
        
        $stmt->bind_param(
            "isssss",
            $supplierId,
            $name,
            $code,
            $contact,
            $email,
            $contact
        );
        
        @$stmt->execute();
        $stmt->close();
        
        return $supplierId;
    }
    
    /**
     * Create test data - Insert a test order (no customers table in schema)
     */
    public function createTestCustomer($customerId = 999) {
        try {
            $name = "Test Customer " . date('His');
            $contact = "9999999999";
            $orderNum = "TEST-" . $customerId;
            
            $sql = "
                INSERT IGNORE INTO orders (id, order_number, clientName, clientContact, paymentStatus, order_status)
                VALUES (?, ?, ?, ?, 'UNPAID', 'DRAFT')
            ";
            
            $stmt = $this->connect->prepare($sql);
            if (!$stmt) {
                return $customerId;
            }
            
            $stmt->bind_param(
                "isss",
                $customerId,
                $orderNum,
                $name,
                $contact
            );
            
            @$stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Silently continue
        }
        
        return $customerId;
    }
    
    /**
     * Clean up test data
     */
    public function cleanupTestData() {
        try {
            // Delete in correct order (foreign key constraints)
            $this->connect->query("DELETE FROM po_items WHERE po_id IN (SELECT id FROM purchase_orders WHERE po_number LIKE 'TEST%')");
            $this->connect->query("DELETE FROM purchase_orders WHERE po_number LIKE 'TEST%'");
            $this->connect->query("DELETE FROM grn_items WHERE grn_id IN (SELECT id FROM goods_received WHERE grn_number LIKE 'TEST%')");
            $this->connect->query("DELETE FROM goods_received WHERE grn_number LIKE 'TEST%'");
            $this->connect->query("DELETE FROM order_item WHERE order_id IN (SELECT id FROM orders WHERE order_number LIKE 'TEST%')");
            $this->connect->query("DELETE FROM orders WHERE order_number LIKE 'TEST%'");
            $this->connect->query("DELETE FROM suppliers WHERE supplier_id IN (999, 998, 888)");
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
    
    /**
     * Print test results
     */
    public function printResults($sectionName = "Test Results") {
        $total = $this->testsPassed + $this->testsFailed;
        $passPercent = $total > 0 ? round(($this->testsPassed / $total) * 100, 1) : 0;
        
        echo "\n";
        echo "================================================================================\n";
        echo "TEST RESULTS: {$sectionName}\n";
        echo "================================================================================\n";
        echo "Total Tests: {$total} | Passed: {$this->testsPassed} | Failed: {$this->testsFailed} | {$passPercent}%\n";
        echo "--------------------------------------------------------------------------------\n";
        
        foreach ($this->testResults as $result) {
            $status = $result['status'] === 'PASS' ? '✓' : '✗';
            echo "[{$status}] {$result['name']}";
            if ($result['message']) {
                echo " - {$result['message']}";
            }
            echo "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Get execution time
     */
    public function getExecutionTime() {
        return round(microtime(true) - $this->startTime, 3);
    }
    
}

?>
