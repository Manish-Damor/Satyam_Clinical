<?php
/**
 * CreditControl - Customer Credit Management & Risk Control
 * 
 * Manages:
 * - Credit limits and outstanding balances
 * - Credit approval workflows
 * - Overdue invoice tracking
 * - Payment collection
 * - Credit status management
 * 
 * Prevents over-extension of credit and manages risk
 * 
 * @package Services
 * @version 2.0
 * @date February 2026
 */

namespace Services;

class CreditControl
{
    private $db;
    private $user_id;
    private $audit_logger;

    public function __construct($database, $user_id = null, $audit_logger = null)
    {
        $this->db = $database;
        $this->user_id = $user_id ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
        $this->audit_logger = $audit_logger;
    }

    /**
     * Check customer credit eligibility for order
     * 
     * @param int $customer_id Customer ID
     * @param decimal $order_amount Order total amount
     * @param string $payment_type Payment type (CASH, CREDIT, etc)
     * @return array Status array with eligibility and warnings
     */
    public function checkCreditEligibility($customer_id, $order_amount, $payment_type = 'CREDIT')
    {
        if ($payment_type == 'CASH') {
            return ['eligible' => true, 'requires_approval' => false, 'message' => 'Cash payment - no credit check needed'];
        }

        try {
            // Get customer credit info
            $customer = $this->getCustomerCreditInfo($customer_id);
            if (!$customer) {
                throw new \Exception("Customer not found");
            }

            // Check if credit is blocked
            if ($customer['credit_status'] == 'BLOCKED') {
                return [
                    'eligible' => false,
                    'requires_approval' => false,
                    'message' => "Customer credit is BLOCKED. Reason: {$customer['credit_notes']}",
                    'status' => 'BLOCKED'
                ];
            }

            // Calculate available credit
            $outstanding = floatval($customer['outstanding_balance'] ?? 0);
            $credit_limit = floatval($customer['credit_limit'] ?? 0);
            $available_credit = $credit_limit - $outstanding;
            $order_amount = floatval($order_amount);

            // Check basic eligibility
            if ($credit_limit <= 0) {
                return [
                    'eligible' => false,
                    'requires_approval' => true,
                    'message' => "No credit limit set. Requires manager approval.",
                    'status' => 'NO_CREDIT_LIMIT'
                ];
            }

            // Check if within limit
            if ($order_amount <= $available_credit) {
                return [
                    'eligible' => true,
                    'requires_approval' => false,
                    'message' => "Within credit limit",
                    'status' => 'APPROVED',
                    'credit_limit' => $credit_limit,
                    'outstanding_balance' => $outstanding,
                    'available_credit' => $available_credit,
                    'credit_utilization_percent' => round(($outstanding / $credit_limit) * 100, 2)
                ];
            }

            // Would exceed limit
            $excess = $order_amount - $available_credit;

            // Check if restricted status allows override
            if ($customer['credit_status'] == 'RESTRICTED') {
                return [
                    'eligible' => true,
                    'requires_approval' => true,
                    'message' => "Would exceed credit limit by {$excess}. Requires manager approval (customer is RESTRICTED).",
                    'status' => 'NEEDS_APPROVAL',
                    'credit_limit' => $credit_limit,
                    'outstanding_balance' => $outstanding,
                    'available_credit' => $available_credit,
                    'excess_amount' => $excess
                ];
            }

            // Active but would exceed
            return [
                'eligible' => false,
                'requires_approval' => true,
                'message' => "Would exceed credit limit by {$excess}. Requires approval.",
                'status' => 'EXCEEDS_LIMIT',
                'credit_limit' => $credit_limit,
                'outstanding_balance' => $outstanding,
                'available_credit' => $available_credit,
                'excess_amount' => $excess
            ];

        } catch (\Exception $e) {
            return [
                'eligible' => false,
                'requires_approval' => false,
                'message' => "Credit check failed: " . $e->getMessage(),
                'error' => true
            ];
        }
    }

    /**
     * Get complete customer credit profile
     * 
     * @param int $customer_id Customer ID
     * @return array Customer credit information
     */
    public function getCustomerCreditInfo($customer_id)
    {
        $sql = "SELECT 
                    id,
                    customer_name,
                    credit_limit,
                    outstanding_balance,
                    credit_status,
                    credit_notes,
                    last_payment_date,
                    DATEDIFF(CURDATE(), last_payment_date) as days_since_last_payment
                FROM customers
                WHERE id = ? AND deleted_at IS NULL";
        
        $result = $this->db->execute_query($sql, [$customer_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Set customer credit limit
     * 
     * @param int $customer_id Customer ID
     * @param decimal $new_limit New credit limit
     * @param string $reason Reason for limit change
     * @return array Result array
     */
    public function setCreditLimit($customer_id, $new_limit, $reason = '')
    {
        try {
            $this->db->begin_transaction();

            // Get current limit
            $current = $this->getCustomerCreditInfo($customer_id);
            if (!$current) {
                throw new \Exception("Customer not found");
            }

            $old_limit = $current['credit_limit'];
            $new_limit = floatval($new_limit);

            // Update credit limit
            $sql = "UPDATE customers 
                    SET credit_limit = ?,
                        credit_adjusted_by = ?,
                        credit_adjusted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            if (!$this->db->execute_query($sql, [$new_limit, $this->user_id, $customer_id])) {
                throw new \Exception("Failed to update credit limit");
            }

            // Log to credit history
            $this->logCreditChange(
                $customer_id,
                'LIMIT_UPDATED',
                $old_limit,
                $new_limit,
                null,
                null,
                $reason
            );

            // Audit log
            if ($this->audit_logger) {
                $this->audit_logger->logUpdate(
                    'customers',
                    $customer_id,
                    ['credit_limit' => $old_limit],
                    ['credit_limit' => $new_limit]
                );
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Credit limit updated from {$old_limit} to {$new_limit}",
                'old_limit' => $old_limit,
                'new_limit' => $new_limit
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Change customer credit status
     * 
     * @param int $customer_id Customer ID
     * @param string $new_status New status (ACTIVE, RESTRICTED, BLOCKED)
     * @param string $notes Reason/notes
     * @return array Result array
     */
    public function setCreditStatus($customer_id, $new_status, $notes = '')
    {
        try {
            $this->db->begin_transaction();

            // Validate status
            $valid_statuses = ['ACTIVE', 'RESTRICTED', 'BLOCKED'];
            if (!in_array($new_status, $valid_statuses)) {
                throw new \Exception("Invalid credit status: {$new_status}");
            }

            // Get current status
            $current = $this->getCustomerCreditInfo($customer_id);
            if (!$current) {
                throw new \Exception("Customer not found");
            }

            $old_status = $current['credit_status'];

            // Update status
            $sql = "UPDATE customers 
                    SET credit_status = ?,
                        credit_notes = ?,
                        credit_adjusted_by = ?,
                        credit_adjusted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            if (!$this->db->execute_query($sql, [$new_status, $notes, $this->user_id, $customer_id])) {
                throw new \Exception("Failed to update credit status");
            }

            // Log to credit history
            $this->logCreditChange(
                $customer_id,
                'STATUS_CHANGED',
                null,
                null,
                $old_status,
                $new_status,
                $notes
            );

            if ($this->audit_logger) {
                $this->audit_logger->logUpdate(
                    'customers',
                    $customer_id,
                    ['credit_status' => $old_status],
                    ['credit_status' => $new_status]
                );
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Credit status changed from {$old_status} to {$new_status}",
                'old_status' => $old_status,
                'new_status' => $new_status
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Record customer payment
     * 
     * @param int $customer_id Customer ID
     * @param decimal $payment_amount Amount paid
     * @param string $payment_method Payment method
     * @param string $payment_ref Payment reference (cheque no, txn id, etc)
     * @param int|null $order_id Optional associated order
     * @return array Result array
     */
    public function recordPayment(
        $customer_id,
        $payment_amount,
        $payment_method,
        $payment_ref = '',
        $order_id = null
    ) {
        try {
            $this->db->begin_transaction();

            $payment_amount = floatval($payment_amount);
            if ($payment_amount <= 0) {
                throw new \Exception("Payment amount must be positive");
            }

            // Record payment
            $sql = "INSERT INTO customer_payments 
                    (customer_id, order_id, payment_amount, payment_method, 
                     payment_reference, payment_date, recorded_by)
                    VALUES (?, ?, ?, ?, ?, CURDATE(), ?)";
            
            if (!$this->db->execute_query($sql, [
                $customer_id,
                $order_id,
                $payment_amount,
                $payment_method,
                $payment_ref,
                $this->user_id
            ])) {
                throw new \Exception("Failed to record payment");
            }

            $payment_id = $this->db->get_last_insert_id();

            // Update customer outstanding balance
            $sql = "UPDATE customers 
                    SET outstanding_balance = outstanding_balance - ?,
                        last_payment_date = CURDATE(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            if (!$this->db->execute_query($sql, [$payment_amount, $customer_id])) {
                throw new \Exception("Failed to update customer balance");
            }

            // If order-specific, update order payment status
            if ($order_id) {
                $this->updateOrderPaymentStatus($order_id);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Payment recorded successfully",
                'payment_id' => $payment_id,
                'payment_amount' => $payment_amount,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get customer payment history
     * 
     * @param int $customer_id Customer ID
     * @param int $limit Number of records
     * @return array Payment history
     */
    public function getPaymentHistory($customer_id, $limit = 50)
    {
        $sql = "SELECT 
                    id,
                    payment_amount,
                    payment_method,
                    payment_reference,
                    payment_date,
                    reconciled,
                    u.name as recorded_by_name
                FROM customer_payments cp
                LEFT JOIN users u ON cp.recorded_by = u.id
                WHERE cp.customer_id = ?
                ORDER BY cp.payment_date DESC
                LIMIT " . intval($limit);
        
        $result = $this->db->execute_query($sql, [$customer_id]);
        $payments = [];
        
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }

    /**
     * Get all customers with credit issues
     * 
     * @return array Customers with high utilization, overdue, or blocked
     */
    public function getCreditRiskCustomers()
    {
        $sql = "SELECT 
                    c.id,
                    c.customer_name,
                    c.credit_limit,
                    c.outstanding_balance,
                    (c.credit_limit - c.outstanding_balance) as available_credit,
                    ROUND((c.outstanding_balance / c.credit_limit) * 100, 2) as utilization_percent,
                    c.credit_status,
                    DATEDIFF(CURDATE(), c.last_payment_date) as days_since_payment,
                    CASE 
                        WHEN c.credit_status = 'BLOCKED' THEN 'CRITICAL'
                        WHEN c.outstanding_balance >= c.credit_limit THEN 'CRITICAL'
                        WHEN c.outstanding_balance >= (c.credit_limit * 0.9) THEN 'HIGH'
                        WHEN DATEDIFF(CURDATE(), c.last_payment_date) > 60 THEN 'WARNING'
                        ELSE 'LOW'
                    END as risk_level
                FROM customers c
                WHERE c.deleted_at IS NULL
                HAVING risk_level IN ('CRITICAL', 'HIGH', 'WARNING')
                ORDER BY risk_level DESC, c.outstanding_balance DESC";
        
        $result = $this->db->execute_query($sql);
        $customers = [];
        
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        
        return $customers;
    }

    /**
     * Get overdue invoices/orders
     * 
     * @param int|null $customer_id Optional filter by customer
     * @param int $days_overdue Threshold days
     * @return array Overdue invoices
     */
    public function getOverdueInvoices($customer_id = null, $days_overdue = 0)
    {
        $sql = "SELECT 
                    ip.id,
                    ip.order_id,
                    ip.invoice_date,
                    ip.due_date,
                    DATEDIFF(CURDATE(), ip.due_date) as days_overdue,
                    ip.amount_due,
                    ip.amount_paid,
                    (ip.amount_due - ip.amount_paid) as outstanding_amount,
                    c.customer_name,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), ip.due_date) > 60 THEN 'CRITICAL'
                        WHEN DATEDIFF(CURDATE(), ip.due_date) > 30 THEN 'WARNING'
                        ELSE 'OVERDUE'
                    END as severity
                FROM invoice_payments ip
                LEFT JOIN customers c ON ip.customer_id = c.id
                WHERE ip.due_date < CURDATE() 
                  AND ip.payment_status != 'PAID'
                  AND DATEDIFF(CURDATE(), ip.due_date) >= ?";
        
        $params = [$days_overdue];
        
        if ($customer_id) {
            $sql .= " AND ip.customer_id = ?";
            $params[] = $customer_id;
        }
        
        $sql .= " ORDER BY ip.due_date ASC";
        
        $result = $this->db->execute_query($sql, $params);
        $invoices = [];
        
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
        
        return $invoices;
    }

    /**
     * Get credit exposure summary
     * Total credit extended vs available credit
     * 
     * @return array Credit exposure metrics
     */
    public function getCreditExposureSummary()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT id) as total_customers,
                    SUM(credit_limit) as total_credit_limit,
                    SUM(outstanding_balance) as total_outstanding,
                    (SUM(credit_limit) - SUM(outstanding_balance)) as total_available,
                    ROUND((SUM(outstanding_balance) / SUM(credit_limit)) * 100, 2) as overall_utilization,
                    COUNT(CASE WHEN credit_status = 'BLOCKED' THEN 1 END) as blocked_customers,
                    COUNT(CASE WHEN credit_status = 'RESTRICTED' THEN 1 END) as restricted_customers
                FROM customers
                WHERE deleted_at IS NULL";
        
        $result = $this->db->execute_query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    // ======================== PRIVATE METHODS ========================

    /**
     * Log credit change to history
     */
    private function logCreditChange(
        $customer_id,
        $action,
        $old_limit,
        $new_limit,
        $old_status,
        $new_status,
        $reason = ''
    ) {
        $sql = "INSERT INTO customer_credit_log 
                (customer_id, action, old_limit, new_limit, old_status, new_status, 
                 changed_by, reason, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        return $this->db->execute_query($sql, [
            $customer_id,
            $action,
            $old_limit,
            $new_limit,
            $old_status,
            $new_status,
            $this->user_id,
            $reason
        ]);
    }

    /**
     * Update order payment status
     */
    private function updateOrderPaymentStatus($order_id)
    {
        $sql = "UPDATE orders 
                SET payment_status = 
                    CASE 
                        WHEN (SELECT COALESCE(SUM(payment_amount), 0) 
                              FROM customer_payments WHERE order_id = ?) >= total_amount 
                        THEN 'PAID'
                        WHEN (SELECT COALESCE(SUM(payment_amount), 0) 
                              FROM customer_payments WHERE order_id = ?) > 0 
                        THEN 'PARTIAL'
                        ELSE 'UNPAID'
                    END
                WHERE id = ?";
        
        return $this->db->execute_query($sql, [$order_id, $order_id, $order_id]);
    }
}
