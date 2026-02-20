<?php
/**
 * EXAMPLE: Goods Receipt Controller with Quality Control
 * 
 * Demonstrates:
 * - GRN creation linked to PO
 * - Batch creation with expiry validation
 * - Quality check workflow
 * - Stock movement recording
 * - Approval workflow
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

class GoodsReceiptController
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

        $this->stock_service = new StockService($database, null, $this->user_id);
        $this->approval_engine = new ApprovalEngine($database, $this->user_id, $this->user_role);
        $this->audit_logger = new AuditLogger($database, $this->user_id);
        $this->permission = new PermissionMiddleware($this->user_role, $this->user_id);
    }

    /**
     * Create Goods Receipt Note (GRN)
     * 
     * Expected POST:
     * {
     *   "po_id": 5,
     *   "supplier_invoice_no": "SUP-001",
     *   "invoice_date": "2026-02-17",
     *   "items": [
     *     {
     *       "po_item_id": 1,
     *       "product_id": 1,
     *       "qty_received": 45,
     *       "qty_rejected": 5,
     *       "batch_number": "BAT-2025-001",
     *       "mfg_date": "2025-01-15",
     *       "exp_date": "2027-01-15",
     *       "purchase_rate": 100.00
     *     }
     *   ],
     *   "quality_check_required": true
     * }
     */
    public function createGRN()
    {
        try {
            if (!$this->permission->hasPermission('grn.create')) {
                return response_error("Permission denied", 403);
            }

            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input || !isset($input['po_id']) || !isset($input['items'])) {
                return response_error("Invalid input: po_id and items required");
            }

            $po_id = intval($input['po_id']);
            if (empty($input['items'])) {
                return response_error("GRN must have at least one item");
            }

            // Verify PO exists and is approved
            $po = $this->getPurchaseOrder($po_id);
            if (!$po) {
                return response_error("PO not found");
            }

            if ($po['status'] != 'APPROVED' && $po['status'] != 'POSTED') {
                return response_error("PO must be APPROVED before GRN. Current status: {$po['status']}");
            }

            $this->db->begin_transaction();

            try {
                // Generate GRN number
                $grn_number = $this->generateGRNNumber();

                // Insert GRN Header
                $grn_sql = "INSERT INTO goods_received 
                           (grn_number, po_id, supplier_id, supplier_invoice_no, 
                            invoice_date, quality_check_required, status, created_by, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, 'DRAFT', ?, NOW())";

                if (!$this->db->execute_query($grn_sql, [
                    $grn_number,
                    $po_id,
                    $po['supplier_id'],
                    $input['supplier_invoice_no'] ?? '',
                    $input['invoice_date'] ?? date('Y-m-d'),
                    $input['quality_check_required'] ? 1 : 0,
                    $this->user_id
                ])) {
                    throw new \Exception("Failed to create GRN");
                }

                $grn_id = $this->db->get_last_insert_id();

                // Process each item
                foreach ($input['items'] as $item) {
                    // Validate data
                    $product_id = intval($item['product_id']);
                    $qty_received = floatval($item['qty_received']);
                    $qty_rejected = floatval($item['qty_rejected'] ?? 0);
                    $batch_number = trim($item['batch_number']);

                    if ($qty_received <= 0) {
                        throw new \Exception("Received quantity must be positive");
                    }

                    // Validate batch dates
                    $mfg_date = $item['mfg_date'];
                    $exp_date = $item['exp_date'];

                    if (strtotime($exp_date) <= strtotime($mfg_date)) {
                        throw new \Exception("Expiry date must be after manufacturing date");
                    }

                    // Validate expiry is reasonable (>6 months from today)
                    $days_to_expiry = floor((strtotime($exp_date) - time()) / 86400);
                    if ($days_to_expiry < 180) {
                        // Warning but allow (can configure this rule)
                        error_log("WARNING: Batch {$batch_number} expires in {$days_to_expiry} days");
                    }

                    // CRITICAL: Check batch number uniqueness
                    $batch_check = $this->getBatchByNumber($batch_number);
                    if ($batch_check) {
                        throw new \Exception("Batch number {$batch_number} already exists");
                    }

                    // Insert GRN Item
                    $item_sql = "INSERT INTO grn_items 
                                (grn_id, product_id, po_item_id, qty_expected, qty_received, 
                                 qty_rejected, status, created_at)
                                VALUES (?, ?, ?, 
                                  (SELECT quantity FROM po_items WHERE id = ?),
                                  ?, ?, 'RECEIVED', NOW())";

                    if (!$this->db->execute_query($item_sql, [
                        $grn_id,
                        $product_id,
                        $item['po_item_id'] ?? null,
                        $item['po_item_id'] ?? null,
                        $qty_received,
                        $qty_rejected
                    ])) {
                        throw new \Exception("Failed to create GRN item");
                    }

                    // Create BATCH record
                    $batch_sql = "INSERT INTO product_batches 
                                 (product_id, batch_number, supplier_id, mfg_date, exp_date,
                                  current_qty, purchase_rate, grn_id, created_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                    if (!$this->db->execute_query($batch_sql, [
                        $product_id,
                        $batch_number,
                        $po['supplier_id'],
                        $mfg_date,
                        $exp_date,
                        $qty_received,
                        floatval($item['purchase_rate'] ?? 0),
                        $grn_id
                    ])) {
                        throw new \Exception("Failed to create batch");
                    }

                    $batch_id = $this->db->get_last_insert_id();

                    // Record stock movement (INBOUND - GRN)
                    $movement = $this->stock_service->increaseStock(
                        $product_id,
                        $batch_id,
                        $qty_received,
                        'GRN',
                        $grn_id
                    );

                    if (!$movement['success']) {
                        throw new \Exception("Failed to record stock movement");
                    }
                }

                // Audit log
                $this->audit_logger->logInsert('goods_received', $grn_id, [
                    'grn_number' => $grn_number,
                    'po_id' => $po_id,
                    'items_count' => count($input['items']),
                    'status' => 'DRAFT'
                ]);

                $this->db->commit();

                return response_success([
                    'message' => "GRN created successfully",
                    'grn_id' => $grn_id,
                    'grn_number' => $grn_number,
                    'status' => 'DRAFT',
                    'next_action' => $input['quality_check_required'] ? 
                        'Submit for quality check' : 'Submit for approval'
                ]);

            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            error_log("GRN creation failed: " . $e->getMessage());
            return response_error($e->getMessage(), 500);
        }
    }

    /**
     * Quality Check on GRN Items
     * 
     * PUT /grn/{id}/quality-check
     * {
     *   "items": [
     *     {
     *       "grn_item_id": 1,
     *       "quality_status": "PASSED",  // PASSED, FAILED, CONDITIONAL
     *       "remarks": "All items OK"
     *     }
     *   ]
     * }
     */
    public function performQualityCheck($grn_id)
    {
        try {
            if (!$this->permission->hasPermission('grn.quality_check')) {
                return response_error("Permission denied", 403);
            }

            $input = json_decode(file_get_contents("php://input"), true);
            if (!$input || !isset($input['items'])) {
                return response_error("Invalid input: items required");
            }

            $grn_id = intval($grn_id);

            $this->db->begin_transaction();

            try {
                $grn = $this->getGRN($grn_id);
                if (!$grn) {
                    throw new \Exception("GRN not found");
                }

                if ($grn['quality_check_status'] != 'PENDING') {
                    throw new \Exception("Quality check already performed on this GRN");
                }

                // Update items
                foreach ($input['items'] as $item) {
                    $status = $item['quality_status'] ?? 'PASSED';
                    $remarks = $item['remarks'] ?? '';

                    // Validate status
                    if (!in_array($status, ['PASSED', 'FAILED', 'CONDITIONAL'])) {
                        throw new \Exception("Invalid quality status: {$status}");
                    }

                    // Update GRN item
                    $item_sql = "UPDATE grn_items 
                                SET status = ?, quality_remarks = ?
                                WHERE id = ? AND grn_id = ?";

                    if (!$this->db->execute_query($item_sql, [
                        $status,
                        $remarks,
                        intval($item['grn_item_id']),
                        $grn_id
                    ])) {
                        throw new \Exception("Failed to update GRN item");
                    }

                    // If FAILED, mark batch for review
                    if ($status == 'FAILED') {
                        $batch_update = "UPDATE product_batches 
                                        SET quality_status = 'FAILED'
                                        WHERE grn_id = ?";
                        $this->db->execute_query($batch_update, [$grn_id]);
                    }
                }

                // Update GRN quality check
                $grn_update = "UPDATE goods_received 
                              SET quality_check_status = 'PASSED',
                                  quality_checked_by = ?,
                                  updated_at = NOW()
                              WHERE id = ?";

                if (!$this->db->execute_query($grn_update, [$this->user_id, $grn_id])) {
                    throw new \Exception("Failed to update GRN");
                }

                $this->db->commit();

                return response_success([
                    'message' => "Quality check completed",
                    'grn_id' => $grn_id,
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
     * Submit GRN for Approval
     */
    public function submitGRN($grn_id)
    {
        try {
            if (!$this->permission->hasPermission('grn.submit')) {
                return response_error("Permission denied", 403);
            }

            $this->db->begin_transaction();

            try {
                $result = $this->approval_engine->submitForApproval(
                    'GRN',
                    intval($grn_id),
                    $_POST['remarks'] ?? ''
                );

                $this->db->commit();

                return response_success([
                    'message' => "GRN submitted for approval",
                    'grn_id' => $grn_id,
                    'new_status' => 'SUBMITTED'
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
     * Approve GRN (Stock becomes available)
     */
    public function approveGRN($grn_id)
    {
        try {
            if (!$this->permission->hasPermission('grn.approve')) {
                return response_error("Permission denied", 403);
            }

            $this->db->begin_transaction();

            try {
                $result = $this->approval_engine->approveEntity(
                    'GRN',
                    intval($grn_id),
                    $_POST['remarks'] ?? ''
                );

                // Stock is already added on GRN creation, this just finalizes

                $this->db->commit();

                return response_success([
                    'message' => "GRN approved - stock is now available",
                    'grn_id' => $grn_id,
                    'new_status' => 'APPROVED'
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

    private function generateGRNNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(grn_number, -4) AS INT)) as max_num 
                FROM goods_received 
                WHERE YEAR(created_at) = {$year} AND MONTH(created_at) = {$month}";
        
        $result = $this->db->execute_query($sql);
        $row = $result->fetch_assoc();
        $next = ($row['max_num'] ?? 0) + 1;
        
        return "GRN-{$year}{$month}-" . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function getPurchaseOrder($po_id)
    {
        $sql = "SELECT * FROM purchase_orders WHERE id = ? AND deleted_at IS NULL";
        $result = $this->db->execute_query($sql, [$po_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    private function getGRN($grn_id)
    {
        $sql = "SELECT * FROM goods_received WHERE id = ? AND deleted_at IS NULL";
        $result = $this->db->execute_query($sql, [$grn_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    private function getBatchByNumber($batch_number)
    {
        $sql = "SELECT * FROM product_batches WHERE batch_number = ? AND deleted_at IS NULL";
        $result = $this->db->execute_query($sql, [$batch_number]);
        return $result ? $result->fetch_assoc() : null;
    }
}
