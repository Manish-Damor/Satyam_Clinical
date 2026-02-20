<?php
/**
 * EXAMPLE: Sales Order Controller - With Credit Control
 * 
 * Demonstrates:
 * - Credit eligibility checking
 * - Batch expiry validation
 * - Stock deduction with StockService
 * - Approval for credit override
 * - Transaction safety
 * 
 * @package Controllers
 * @version 2.0
 * @date February 2026
 */

namespace Controllers;

use Services\StockService;
use Services\CreditControl;
use Services\AuditLogger;
use Services\ApprovalEngine;
use Middleware\PermissionMiddleware;

class SalesOrderController
{
    private $db;
    private $stock_service;
    private $credit_control;
    private $audit_logger;
    private $approval_engine;
    private $permission;
    private $user_id;
    private $user_role;

    public function __construct($database)
    {
        $this->db = $database;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['user_role'] ?? 'USER';

        $this->stock_service = new StockService($database, null, $this->user_id);
        $this->credit_control = new CreditControl($database, $this->user_id);
        $this->audit_logger = new AuditLogger($database, $this->user_id);
        $this->approval_engine = new ApprovalEngine($database, $this->user_id, $this->user_role);
        $this->permission = new PermissionMiddleware($this->user_role, $this->user_id);
    }

    /**
     * Create Sales Order with Credit Control
     * 
     * Expected POST:
     * {
     *   "customer_id": 10,
     *   "payment_type": "CREDIT",  // CASH or CREDIT
     *   "items": [
     *     {"product_id": 1, "batch_id": 5, "quantity": 10}
     *   ],
     *   "notes": "Special order"
     * }
     */
    public function createSalesOrder()
    {
        try {
            if (!$this->permission->hasPermission('sales.create_order')) {
                return response_error("Permission denied", 403);
            }

            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input || !isset($input['customer_id']) || !isset($input['items'])) {
                return response_error("Invalid input: customer_id and items required");
            }

            $customer_id = intval($input['customer_id']);
            $payment_type = $input['payment_type'] ?? 'CASH';
            $items = $input['items'];

            if (empty($items)) {
                return response_error("Order must have at least one item");
            }

            // Validate customer exists
            $customer = $this->getCustomer($customer_id);
            if (!$customer) {
                return response_error("Customer not found");
            }

            // ===== CRITICAL: START TRANSACTION =====
            $this->db->begin_transaction();

            try {
                // STEP 1: Calculate order total
                $order_total = 0;
                $prepared_items = [];

                foreach ($items as $item) {
                    $product_id = intval($item['product_id']);
                    $batch_id = intval($item['batch_id']);
                    $quantity = floatval($item['quantity']);

                    if ($quantity <= 0) {
                        throw new \Exception("Quantity must be positive");
                    }

                    // Get batch details (includes expiry validation)
                    $batch = $this->getBatchDetails($batch_id, $product_id);
                    if (!$batch) {
                        throw new \Exception("Batch not found or invalid product");
                    }

                    // **CRITICAL**: Check batch not expired
                    if (strtotime($batch['exp_date']) < time()) {
                        throw new \Exception(
                            "Batch {$batch['batch_number']} expired on {$batch['exp_date']}"
                        );
                    }

                    // Check sufficient stock
                    if ($batch['current_qty'] < $quantity) {
                        throw new \Exception(
                            "Insufficient stock for product {$batch['product_name']}. " .
                            "Available: {$batch['current_qty']}, Requested: {$quantity}"
                        );
                    }

                    $line_total = $quantity * $batch['purchase_rate']; // Use cost for now
                    $order_total += $line_total;

                    $prepared_items[] = [
                        'product_id' => $product_id,
                        'batch_id' => $batch_id,
                        'quantity' => $quantity,
                        'unit_price' => $batch['purchase_rate'],
                        'line_total' => $line_total,
                        'batch_number' => $batch['batch_number']
                    ];
                }

                // STEP 2: Check Credit (if credit sale)
                $credit_approval_required = false;
                $credit_approved_by = null;

                if ($payment_type == 'CREDIT') {
                    $credit_check = $this->credit_control->checkCreditEligibility(
                        $customer_id,
                        $order_total,
                        'CREDIT'
                    );

                    if (!$credit_check['eligible']) {
                        // Cannot proceed without approval
                        if ($credit_check['status'] == 'BLOCKED') {
                            throw new \Exception($credit_check['message']);
                        }

                        // Requires approval
                        $credit_approval_required = true;
                    } elseif ($credit_check['requires_approval']) {
                        $credit_approval_required = true;
                    }
                }

                // STEP 3: Create Order Record
                $invoice_number = $this->generateInvoiceNumber();

                $order_sql = "INSERT INTO orders 
                             (invoice_no, customer_id, order_date, total_amount, payment_type,
                              payment_status, order_status, credit_approval_required,
                              created_by, created_at)
                             VALUES (?, ?, CURDATE(), ?, ?, 'UNPAID', 
                                     'DRAFT', ?, ?, NOW())";

                if (!$this->db->execute_query($order_sql, [
                    $invoice_number,
                    $customer_id,
                    $order_total,
                    $payment_type,
                    $credit_approval_required ? 1 : 0,
                    $this->user_id
                ])) {
                    throw new \Exception("Failed to create order");
                }

                $order_id = $this->db->get_last_insert_id();

                // STEP 4: Insert Line Items and DEDUCT STOCK
                $item_sql = "INSERT INTO order_item 
                            (order_id, product_id, quantity, unit_price, line_total, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())";

                foreach ($prepared_items as $item) {
                    // Insert order item
                    if (!$this->db->execute_query($item_sql, [
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['line_total']
                    ])) {
                        throw new \Exception("Failed to create order item");
                    }

                    // **CRITICAL**: Deduct from stock immediately (transactional safety)
                    $stock_result = $this->stock_service->decreaseStock(
                        $item['product_id'],
                        $item['batch_id'],
                        $item['quantity'],
                        'SALES_ORDER',
                        $order_id
                    );

                    if (!$stock_result['success']) {
                        throw new \Exception("Stock deduction failed: " . $stock_result['message'] ?? '');
                    }
                }

                // STEP 5: Update Customer Outstanding Balance
                if ($payment_type == 'CREDIT') {
                    $balance_sql = "UPDATE customers 
                                   SET outstanding_balance = outstanding_balance + ?,
                                       updated_at = NOW()
                                   WHERE id = ?";
                    
                    if (!$this->db->execute_query($balance_sql, [$order_total, $customer_id])) {
                        throw new \Exception("Failed to update customer balance");
                    }
                }

                // STEP 6: Audit Log
                $this->audit_logger->logInsert('orders', $order_id, [
                    'invoice_no' => $invoice_number,
                    'customer_id' => $customer_id,
                    'total_amount' => $order_total,
                    'payment_type' => $payment_type,
                    'credit_approval_required' => $credit_approval_required
                ]);

                // ===== COMMIT TRANSACTION =====
                $this->db->commit();

                $response = [
                    'message' => "Order created successfully",
                    'order_id' => $order_id,
                    'invoice_number' => $invoice_number,
                    'total_amount' => $order_total,
                    'items_count' => count($prepared_items),
                    'status' => 'DRAFT'
                ];

                // Add approval requirement warning
                if ($credit_approval_required) {
                    $response['warning'] = "This order requires credit approval from manager";
                    $response['status'] = 'PENDING_CREDIT_APPROVAL';
                }

                return response_success($response);

            } catch (\Exception $e) {
                // ===== ROLLBACK ON ANY ERROR =====
                $this->db->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            error_log("Sales order creation failed: " . $e->getMessage());
            return response_error($e->getMessage(), 500);
        }
    }

    /**
     * Approve Credit Override for Order
     * 
     * POST /order/{id}/approve-credit
     */
    public function approveCreditOverride($order_id)
    {
        try {
            if (!$this->permission->hasPermission('credit.approve_override')) {
                return response_error("Permission denied", 403);
            }

            $order_id = intval($order_id);

            $this->db->begin_transaction();

            try {
                // Get order
                $order_sql = "SELECT * FROM orders WHERE id = ?";
                $result = $this->db->execute_query($order_sql, [$order_id]);
                $order = $result->fetch_assoc();

                if (!$order) {
                    throw new \Exception("Order not found");
                }

                if (!$order['credit_approval_required']) {
                    throw new \Exception("Order does not require credit approval");
                }

                // Update order
                $update_sql = "UPDATE orders 
                              SET credit_approval_required = 0,
                                  credit_approved_by = ?,
                                  credit_approved_at = NOW(),
                                  updated_at = NOW()
                              WHERE id = ?";

                if (!$this->db->execute_query($update_sql, [$this->user_id, $order_id])) {
                    throw new \Exception("Failed to approve credit");
                }

                $this->db->commit();

                return response_success([
                    'message' => "Credit override approved",
                    'order_id' => $order_id,
                    'approved_by' => $this->user_id
                ]);

            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response_error($e->getMessage(), 500);
        }
    }

    /**
     * Confirm Order (fulfill and send to customer)
     * Updates order status and marks payment status
     */
    public function confirmOrder($order_id)
    {
        try {
            if (!$this->permission->hasPermission('sales.create_order')) {
                return response_error("Permission denied", 403);
            }

            $order_id = intval($order_id);

            $this->db->begin_transaction();

            try {
                // Check credit approval if needed
                $order_sql = "SELECT * FROM orders WHERE id = ?";
                $result = $this->db->execute_query($order_sql, [$order_id]);
                $order = $result->fetch_assoc();

                if (!$order) {
                    throw new \Exception("Order not found");
                }

                if ($order['credit_approval_required'] && !$order['credit_approved_by']) {
                    throw new \Exception("Order requires credit approval before confirmation");
                }

                // Process payment if CASH
                if ($order['payment_type'] == 'CASH') {
                    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
                    if ($amount_paid < $order['total_amount']) {
                        throw new \Exception("Insufficient cash payment");
                    }

                    // Record payment
                    $this->credit_control->recordPayment(
                        $order['customer_id'],
                        $order['total_amount'],
                        'CASH',
                        '',
                        $order_id
                    );
                }

                // Update order status
                $update_sql = "UPDATE orders 
                              SET order_status = 'FULFILLED',
                                  fulfilled_at = NOW(),
                                  updated_at = NOW()
                              WHERE id = ?";

                if (!$this->db->execute_query($update_sql, [$order_id])) {
                    throw new \Exception("Failed to confirm order");
                }

                $this->db->commit();

                return response_success([
                    'message' => "Order confirmed and fulfilled",
                    'order_id' => $order_id,
                    'status' => 'FULFILLED'
                ]);

            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response_error($e->getMessage(), 500);
        }
    }

    // ======================== PRIVATE METHODS ========================

    private function getCustomer($customer_id)
    {
        $sql = "SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL";
        $result = $this->db->execute_query($sql, [$customer_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    private function getBatchDetails($batch_id, $product_id)
    {
        $sql = "SELECT 
                    pb.*,
                    p.product_name
                FROM product_batches pb
                JOIN product p ON pb.product_id = p.id
                WHERE pb.id = ? AND pb.product_id = ? AND pb.deleted_at IS NULL";
        
        $result = $this->db->execute_query($sql, [$batch_id, $product_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(invoice_no, -3) AS INT)) as max_num 
                FROM orders 
                WHERE YEAR(order_date) = {$year} AND MONTH(order_date) = {$month}";
        
        $result = $this->db->execute_query($sql);
        $row = $result->fetch_assoc();
        $next = ($row['max_num'] ?? 0) + 1;
        
        return "INV-{$year}{$month}-" . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
