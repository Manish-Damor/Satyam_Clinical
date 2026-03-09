<?php
/**
 * EXAMPLE: Purchase Order Controller - Production Grade
 * 
 * Demonstrates:
 * - Database transactions with try/catch/rollback
 * - Permission checking
 * - Input validation
 * - Use of StockService, ApprovalEngine, and AuditLogger
 * 
 * @package Controllers
 * @version 2.0
 * @date February 2026
 */

namespace Controllers;

use Services\StockService;
use Services\ApprovalEngine;
use Services\AuditLogger;
use Middleware\PermissionMiddleware;

class PurchaseOrderController
{
    private $db;
    private $stock_service;
    private $approval_engine;
    private $audit_logger;
    private $permission;
    private $user_id;
    private $user_role;

    public function __construct($database)
    {
        $this->db = $database;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['user_role'] ?? 'USER';

        // Initialize services
        $this->stock_service = new StockService($database, null, $this->user_id);
        $this->approval_engine = new ApprovalEngine($database, $this->user_id, $this->user_role);
        $this->audit_logger = new AuditLogger($database, $this->user_id);
        $this->permission = new PermissionMiddleware($this->user_role, $this->user_id);
    }

    /**
     * Create new Purchase Order (DRAFT status)
     * 
     * Expected POST parameters:
     * {
     *   "supplier_id": 5,
     *   "po_date": "2026-02-17",
     *   "expected_delivery_date": "2026-02-25",
     *   "reference_number": "REF-123",
     *   "items": [
     *     {"product_id": 1, "quantity": 50, "unit_price": 100.00},
     *     {"product_id": 2, "quantity": 30, "unit_price": 150.00}
     *   ],
     *   "freight_charges": 500,
     *   "discount_percent": 5
     * }
     */
    public function createPurchaseOrder()
    {
        try {
            // STEP 1: Permission Check
            if (!$this->permission->hasPermission('po.create')) {
                return response_error("Permission denied: Cannot create PO", 403);
            }

            // STEP 2: Input Validation
            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input || !isset($input['supplier_id']) || !isset($input['items'])) {
                return response_error("Invalid input: supplier_id and items required");
            }

            if (empty($input['items'])) {
                return response_error("PO must have at least one item");
            }

            $supplier_id = intval($input['supplier_id']);
            $po_date = $input['po_date'] ?? date('Y-m-d');
            $expected_delivery = $input['expected_delivery_date'] ?? '';
            $reference_number = $input['reference_number'] ?? '';

            // Validate supplier exists and is active
            $supplier = $this->getActiveSupplier($supplier_id);
            if (!$supplier) {
                return response_error("Supplier not found or inactive");
            }

            // STEP 3: Start Transaction
            $this->db->begin_transaction();

            try {
                // Generate PO number
                $po_number = $this->generatePONumber();

                // Calculate totals
                $subtotal = 0;
                $tax_amount = 0;
                $line_items = [];

                foreach ($input['items'] as $item) {
                    // Validate product
                    $product = $this->getProduct($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product ID {$item['product_id']} not found");
                    }

                    $qty = floatval($item['quantity']);
                    $rate = floatval($item['unit_price']);
                    
                    if ($qty <= 0 || $rate <= 0) {
                        throw new \Exception("Quantity and price must be positive");
                    }

                    $line_total = $qty * $rate;
                    $tax_rate = floatval($product['gst_rate'] ?? 0) / 100;
                    $line_tax = $line_total * $tax_rate;

                    $subtotal += $line_total;
                    $tax_amount += $line_tax;

                    $line_items[] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['product_name'],
                        'quantity' => $qty,
                        'unit_price' => $rate,
                        'line_total' => $line_total,
                        'tax_rate' => $product['gst_rate'],
                        'tax_amount' => $line_tax,
                        'discount' => floatval($item['discount'] ?? 0)
                    ];
                }

                // Apply freight and discount
                $freight = floatval($input['freight_charges'] ?? 0);
                $discount_percent = floatval($input['discount_percent'] ?? 0);
                $discount_amount = ($subtotal * $discount_percent) / 100;

                $total_amount = $subtotal + $tax_amount + $freight - $discount_amount;

                // Insert into purchase_orders
                $po_sql = "INSERT INTO purchase_orders 
                          (number, supplier_id, po_date, expected_delivery_date, 
                           reference_number, subtotal, discount_amount, tax_amount, 
                           freight_charges, total_amount, status, created_by, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?, NOW())";

                if (!$this->db->execute_query($po_sql, [
                    $po_number,
                    $supplier_id,
                    $po_date,
                    $expected_delivery,
                    $reference_number,
                    $subtotal,
                    $discount_amount,
                    $tax_amount,
                    $freight,
                    $total_amount,
                    $this->user_id
                ])) {
                    throw new \Exception("Failed to create PO");
                }

                $po_id = $this->db->get_last_insert_id();

                // Insert line items
                $item_sql = "INSERT INTO po_items 
                            (po_id, product_id, quantity, unit_price, discount, 
                             tax_rate, line_total, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

                foreach ($line_items as $item) {
                    if (!$this->db->execute_query($item_sql, [
                        $po_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['discount'],
                        $item['tax_rate'],
                        $item['line_total']
                    ])) {
                        throw new \Exception("Failed to create PO item");
                    }
                }

                // Audit log
                $this->audit_logger->logInsert('purchase_orders', $po_id, [
                    'number' => $po_number,
                    'supplier_id' => $supplier_id,
                    'total_amount' => $total_amount,
                    'status' => 'DRAFT'
                ]);

                $this->db->commit();

                return response_success([
                    'message' => "PO created successfully",
                    'po_id' => $po_id,
                    'po_number' => $po_number,
                    'status' => 'DRAFT',
                    'total_amount' => $total_amount,
                    'next_action' => 'Submit for approval'
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
     * Submit PO for Approval (DRAFT → SUBMITTED)
     * 
     * POST /po/{id}/submit
     */
    public function submitPO($po_id)
    {
        try {
            if (!$this->permission->hasPermission('po.submit')) {
                return response_error("Permission denied", 403);
            }

            $po_id = intval($po_id);

            $this->db->begin_transaction();

            try {
                $result = $this->approval_engine->submitForApproval(
                    'PO',
                    $po_id,
                    $_POST['remarks'] ?? ''
                );

                $this->db->commit();

                return response_success([
                    'message' => "PO submitted for approval",
                    'po_id' => $po_id,
                    'new_status' => 'SUBMITTED',
                    'awaiting_approval_from' => 'Manager'
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
     * Approve PO (SUBMITTED → APPROVED)
     * 
     * POST /po/{id}/approve
     */
    public function approvePO($po_id)
    {
        try {
            if (!$this->permission->hasPermission('po.approve')) {
                return response_error("Permission denied", 403);
            }

            $po_id = intval($po_id);

            $this->db->begin_transaction();

            try {
                $result = $this->approval_engine->approveEntity(
                    'PO',
                    $po_id,
                    $_POST['remarks'] ?? ''
                );

                // Optionally post to ledger on approval
                // (Some systems post on approval, others wait for GRN)

                $this->db->commit();

                return response_success([
                    'message' => "PO approved successfully",
                    'po_id' => $po_id,
                    'new_status' => 'APPROVED',
                    'next_step' => 'Await goods receipt'
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
     * Get PO Details with Items and Status
     */
    public function getPODetails($po_id)
    {
        try {
            $po_id = intval($po_id);

            // Main PO query
            $sql = "SELECT 
                        po.*,
                        s.name as supplier_name,
                        s.gst_no as supplier_gst,
                        u.name as created_by_name,
                        u2.name as approved_by_name
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                    LEFT JOIN users u ON po.created_by = u.id
                    LEFT JOIN users u2 ON po.approved_by = u2.id
                    WHERE po.id = ? AND po.deleted_at IS NULL";

            $result = $this->db->execute_query($sql, [$po_id]);
            if (!$result) {
                return response_error("PO not found", 404);
            }

            $po = $result->fetch_assoc();

            // Get items
            $items_sql = "SELECT * FROM po_items WHERE po_id = ?";
            $items_result = $this->db->execute_query($items_sql, [$po_id]);
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }

            // Get approval history
            $approval_history = $this->approval_engine->getApprovalHistory('PO', $po_id);

            return response_success([
                'po' => $po,
                'items' => $items,
                'approval_history' => $approval_history
            ]);

        } catch (\Exception $e) {
            return response_error($e->getMessage(), 500);
        }
    }

    // ======================== PRIVATE METHODS ========================

    private function generatePONumber()
    {
        $year = date('Y');
        // Get max PO number for this year
        $sql = "SELECT MAX(CAST(SUBSTRING(number, -4) AS INT)) as max_num 
                FROM purchase_orders 
                WHERE YEAR(created_at) = {$year}";
        
        $result = $this->db->execute_query($sql);
        $row = $result->fetch_assoc();
        $next = ($row['max_num'] ?? 0) + 1;
        
        return "PO-{$year}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function getActiveSupplier($supplier_id)
    {
        $sql = "SELECT * FROM suppliers WHERE id = ? AND status = 'Active'";
        $result = $this->db->execute_query($sql, [$supplier_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    private function getProduct($product_id)
    {
        $sql = "SELECT * FROM product WHERE id = ? AND status = 'Active'";
        $result = $this->db->execute_query($sql, [$product_id]);
        return $result ? $result->fetch_assoc() : null;
    }
}

// ======================== RESPONSE HELPER FUNCTIONS ========================

function response_success($data, $status = 200)
{
    http_response_code($status);
    return json_encode(['success' => true, 'data' => $data]);
}

function response_error($message, $status = 400)
{
    http_response_code($status);
    return json_encode(['success' => false, 'error' => $message]);
}
