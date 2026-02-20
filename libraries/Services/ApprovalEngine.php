<?php
/**
 * ApprovalEngine - Workflow Management
 * 
 * Manages approval workflows for:
 * - Purchase Orders (PO)
 * - Goods Receipts (GRN)
 * - Purchase Invoices
 * - Sales Orders (optional)
 * - Stock Adjustments
 * 
 * Implements state machine pattern with role-based approvals
 * 
 * @package Services
 * @version 2.0
 * @date February 2026
 */

namespace Services;

class ApprovalEngine
{
    private $db;
    private $user_id;
    private $user_role;
    private $audit_logger;

    // Status workflow configuration
    const WORKFLOW_STATES = [
        'PO' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'DELIVERED', 'CANCELLED'],
        'GRN' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED'],
        'INVOICE' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'PAID', 'CANCELLED'],
        'SALES_ORDER' => ['DRAFT', 'CONFIRMED', 'FULFILLED', 'CANCELLED'],
        'ADJUSTMENT' => ['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'CANCELLED']
    ];

    // Role-based approval permissions
    const APPROVAL_RULES = [
        'PO' => [
            'DRAFT_TO_SUBMITTED' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
            'SUBMITTED_TO_APPROVED' => ['MANAGER', 'ADMIN'],  // Requires manager approval
            'APPROVED_TO_POSTED' => ['ADMIN', 'ACCOUNTANT']   // Finance posts to ledger
        ],
        'GRN' => [
            'DRAFT_TO_SUBMITTED' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
            'SUBMITTED_TO_APPROVED' => ['QC_MANAGER', 'MANAGER', 'ADMIN'],
            'APPROVED_TO_POSTED' => ['ADMIN']
        ],
        'INVOICE' => [
            'DRAFT_TO_SUBMITTED' => ['ACCOUNTANT', 'MANAGER', 'ADMIN'],
            'SUBMITTED_TO_APPROVED' => ['MANAGER', 'FINANCE_MANAGER', 'ADMIN'],
            'APPROVED_TO_POSTED' => ['ACCOUNTANT', 'ADMIN'],
            'POSTED_TO_PAID' => ['ACCOUNTANT', 'ADMIN']
        ],
        'ADJUSTMENT' => [
            'DRAFT_TO_SUBMITTED' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
            'SUBMITTED_TO_APPROVED' => ['MANAGER', 'ADMIN']
        ]
    ];

    public function __construct($database, $user_id, $user_role, $audit_logger = null)
    {
        $this->db = $database;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
        $this->audit_logger = $audit_logger;
    }

    /**
     * Submit entity for approval
     * Moves from DRAFT to SUBMITTED
     * 
     * @param string $entity_type Entity type (PO, GRN, INVOICE, etc)
     * @param int $entity_id Entity ID
     * @param string $remarks Optional remarks
     * @return array Result array
     * @throws Exception If validation fails
     */
    public function submitForApproval($entity_type, $entity_id, $remarks = '')
    {
        try {
            $this->db->begin_transaction();

            // Validate entity exists and status
            $current_status = $this->getEntityStatus($entity_type, $entity_id);
            if (!$current_status) {
                throw new \Exception("{$entity_type} ID {$entity_id} not found");
            }

            if ($current_status != 'DRAFT') {
                throw new \Exception("Can only submit DRAFT entities. Current status: {$current_status}");
            }

            // Check permission
            $transition = 'DRAFT_TO_SUBMITTED';
            if (!$this->hasApprovalPermission($entity_type, $transition)) {
                throw new \Exception("You do not have permission to submit {$entity_type}");
            }

            // Update entity status
            $this->updateEntityStatus($entity_type, $entity_id, 'SUBMITTED', null, $remarks);

            // Log approval action
            $this->logApproval($entity_type, $entity_id, 'DRAFT', 'SUBMITTED', 'SUBMIT', $remarks);

            // Notify approvers
            $this->notifyApprovers($entity_type, $entity_id, 'PENDING_APPROVAL');

            $this->db->commit();

            return [
                'success' => true,
                'message' => "{$entity_type} submitted for approval",
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'new_status' => 'SUBMITTED',
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Approve entity
     * Moves from SUBMITTED to APPROVED
     * 
     * @param string $entity_type Entity type
     * @param int $entity_id Entity ID
     * @param string $remarks Optional approval remarks
     * @return array Result array
     * @throws Exception If validation fails
     */
    public function approveEntity($entity_type, $entity_id, $remarks = '')
    {
        try {
            $this->db->begin_transaction();

            // Validate entity
            $current_status = $this->getEntityStatus($entity_type, $entity_id);
            if ($current_status != 'SUBMITTED') {
                throw new \Exception(
                    "Can only approve SUBMITTED entities. Current status: {$current_status}"
                );
            }

            // Check approval permission
            $transition = 'SUBMITTED_TO_APPROVED';
            if (!$this->hasApprovalPermission($entity_type, $transition)) {
                throw new \Exception("You do not have permission to approve {$entity_type}");
            }

            // Perform entity-specific validations
            $this->validateEntityForApproval($entity_type, $entity_id);

            // Update status
            $this->updateEntityStatus(
                $entity_type,
                $entity_id,
                'APPROVED',
                $this->user_id,
                $remarks
            );

            // Log approval
            $this->logApproval(
                $entity_type,
                $entity_id,
                'SUBMITTED',
                'APPROVED',
                'APPROVE',
                $remarks
            );

            // Notify relevant parties
            $this->notifyApproval($entity_type, $entity_id, 'APPROVED');

            $this->db->commit();

            return [
                'success' => true,
                'message' => "{$entity_type} approved successfully",
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'approved_by' => $this->user_id,
                'approved_at' => date('Y-m-d H:i:s'),
                'new_status' => 'APPROVED'
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Reject entity
     * Returns to DRAFT from SUBMITTED
     * 
     * @param string $entity_type Entity type
     * @param int $entity_id Entity ID
     * @param string $reason Rejection reason (required)
     * @return array Result array
     * @throws Exception If validation fails
     */
    public function rejectEntity($entity_type, $entity_id, $reason = '')
    {
        try {
            $this->db->begin_transaction();

            if (!$reason) {
                throw new \Exception("Rejection reason is required");
            }

            $current_status = $this->getEntityStatus($entity_type, $entity_id);
            if ($current_status != 'SUBMITTED') {
                throw new \Exception("Can only reject SUBMITTED entities");
            }

            if (!$this->hasApprovalPermission($entity_type, 'SUBMITTED_TO_APPROVED')) {
                throw new \Exception("You do not have permission to reject {$entity_type}");
            }

            // Update status back to DRAFT with rejection reason
            $this->updateEntityStatus(
                $entity_type,
                $entity_id,
                'DRAFT',
                $this->user_id,
                "REJECTED: " . $reason
            );

            // Log rejection
            $this->logApproval(
                $entity_type,
                $entity_id,
                'SUBMITTED',
                'DRAFT',
                'REJECT',
                $reason
            );

            // Notify submitter
            $this->notifyRejection($entity_type, $entity_id, $reason);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "{$entity_type} rejected and returned to draft",
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'new_status' => 'DRAFT',
                'rejection_reason' => $reason
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Cancel entity
     * Moves any status to CANCELLED
     * 
     * @param string $entity_type Entity type
     * @param int $entity_id Entity ID
     * @param string $reason Cancellation reason
     * @return array Result array
     * @throws Exception If validation fails
     */
    public function cancelEntity($entity_type, $entity_id, $reason = '')
    {
        try {
            $this->db->begin_transaction();

            $current_status = $this->getEntityStatus($entity_type, $entity_id);
            if ($current_status == 'CANCELLED') {
                throw new \Exception("{$entity_type} is already cancelled");
            }

            // Only certain roles can cancel
            if (!in_array($this->user_role, ['MANAGER', 'ADMIN'])) {
                throw new \Exception("Only managers can cancel {$entity_type}");
            }

            $this->updateEntityStatus(
                $entity_type,
                $entity_id,
                'CANCELLED',
                $this->user_id,
                "CANCELLED: " . $reason
            );

            $this->logApproval(
                $entity_type,
                $entity_id,
                $current_status,
                'CANCELLED',
                'CANCEL',
                $reason
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => "{$entity_type} cancelled successfully",
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'new_status' => 'CANCELLED'
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get pending approvals for current user
     * 
     * @return array List of entities awaiting approval
     */
    public function getPendingApprovals()
    {
        $sql = "SELECT 
                    entity_type,
                    entity_id,
                    document_number,
                    amount,
                    submitted_date,
                    supplier_name,
                    status,
                    action_required,
                    DATEDIFF(CURDATE(), DATE(submitted_date)) as pending_days
                FROM v_pending_approvals
                ORDER BY submitted_date ASC";
        
        $result = $this->db->execute_query($sql);
        $approvals = [];
        
        while ($row = $result->fetch_assoc()) {
            // Filter by user's approval rights
            if ($this->canApproveEntity($row['entity_type'])) {
                $approvals[] = $row;
            }
        }
        
        return $approvals;
    }

    /**
     * Get approval history for entity
     * 
     * @param string $entity_type Entity type
     * @param int $entity_id Entity ID
     * @return array Approval history
     */
    public function getApprovalHistory($entity_type, $entity_id)
    {
        $sql = "SELECT 
                    entity_type,
                    entity_id,
                    status_from,
                    status_to,
                    action,
                    u.name as approved_by_name,
                    approved_at,
                    remarks
                FROM approval_logs al
                LEFT JOIN users u ON al.approved_by = u.id
                WHERE al.entity_type = ? AND al.entity_id = ?
                ORDER BY al.approved_at DESC";
        
        $result = $this->db->execute_query($sql, [$entity_type, $entity_id]);
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }

    // ======================== PRIVATE METHODS ========================

    /**
     * Get current entity status
     */
    private function getEntityStatus($entity_type, $entity_id)
    {
        $table = $this->getTableName($entity_type);
        $sql = "SELECT status FROM {$table} WHERE id = ?";
        
        $result = $this->db->execute_query($sql, [$entity_id]);
        if (!$result) {
            return null;
        }
        
        $row = $result->fetch_assoc();
        return $row ? $row['status'] : null;
    }

    /**
     * Update entity status
     */
    private function updateEntityStatus($entity_type, $entity_id, $new_status, $approved_by = null, $remarks = '')
    {
        $table = $this->getTableName($entity_type);
        
        $sql = "UPDATE {$table} 
                SET status = ?";
        
        $params = [$new_status];
        
        if ($new_status == 'SUBMITTED') {
            $sql .= ", submitted_at = NOW()";
        } elseif ($new_status == 'APPROVED') {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $this->user_id;
        } elseif ($new_status == 'POSTED') {
            $sql .= ", posted_by = ?, posted_at = NOW()";
            $params[] = $this->user_id;
        }
        
        if ($remarks) {
            $sql .= ", approval_remarks = ?";
            $params[] = $remarks;
        }
        
        $sql .= ", updated_at = NOW() WHERE id = ?";
        $params[] = $entity_id;
        
        return $this->db->execute_query($sql, $params);
    }

    /**
     * Log approval/rejection action
     */
    private function logApproval($entity_type, $entity_id, $from_status, $to_status, $action, $remarks = '')
    {
        $sql = "INSERT INTO approval_logs 
                (entity_type, entity_id, status_from, status_to, action, approved_by, remarks, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        
        return $this->db->execute_query($sql, [
            $entity_type,
            $entity_id,
            $from_status,
            $to_status,
            $action,
            $this->user_id,
            $remarks,
            $ip
        ]);
    }

    /**
     * Check if user has approval permission
     */
    private function hasApprovalPermission($entity_type, $transition)
    {
        if (!isset(self::APPROVAL_RULES[$entity_type])) {
            return false;
        }

        if (!isset(self::APPROVAL_RULES[$entity_type][$transition])) {
            return false;
        }

        $allowed_roles = self::APPROVAL_RULES[$entity_type][$transition];
        return in_array($this->user_role, $allowed_roles);
    }

    /**
     * Check if user can approve any entity of given type
     */
    private function canApproveEntity($entity_type)
    {
        if (!isset(self::APPROVAL_RULES[$entity_type])) {
            return false;
        }

        $rules = self::APPROVAL_RULES[$entity_type];
        foreach ($rules as $transition => $allowed_roles) {
            if (in_array($this->user_role, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Perform entity-specific validation before approval
     */
    private function validateEntityForApproval($entity_type, $entity_id)
    {
        switch ($entity_type) {
            case 'PO':
                // Validate PO has items
                $sql = "SELECT COUNT(*) as item_count FROM po_items WHERE po_id = ?";
                $result = $this->db->execute_query($sql, [$entity_id]);
                $row = $result->fetch_assoc();
                if ($row['item_count'] == 0) {
                    throw new \Exception("PO must have at least one item");
                }
                break;

            case 'GRN':
                // Validate GRN linked to PO
                $sql = "SELECT po_id FROM goods_received WHERE id = ?";
                $result = $this->db->execute_query($sql, [$entity_id]);
                $row = $result->fetch_assoc();
                if (!$row || !$row['po_id']) {
                    throw new \Exception("GRN must be linked to a PO");
                }
                break;

            case 'INVOICE':
                // Validate invoice amount is positive
                $sql = "SELECT total_amount FROM purchase_invoices WHERE id = ?";
                $result = $this->db->execute_query($sql, [$entity_id]);
                $row = $result->fetch_assoc();
                if (!$row || $row['total_amount'] <= 0) {
                    throw new \Exception("Invoice amount must be positive");
                }
                break;
        }
    }

    /**
     * Get table name for entity type
     */
    private function getTableName($entity_type)
    {
        $mapping = [
            'PO' => 'purchase_orders',
            'GRN' => 'goods_received',
            'INVOICE' => 'purchase_invoices',
            'SALES_ORDER' => 'orders',
            'ADJUSTMENT' => 'inventory_adjustments'
        ];

        return $mapping[$entity_type] ?? null;
    }

    /**
     * Send notifications (stub - implement with email/notification service)
     */
    private function notifyApprovers($entity_type, $entity_id, $action)
    {
        // basic notification placeholder – in a real system this could create a row in
        // a notifications table or integrate with an email/sms service. for now we
        // send a simple mail to the configured admin address and log to error log.
        $subject = "Action required: {$entity_type} #{$entity_id} - {$action}";
        $message = "Please review the {$entity_type} with ID {$entity_id}.\n" .
                   "Current action: {$action}.\n" .
                   "This is an automated notification from the ApprovalEngine.";
        // pull admin email from configuration (fallback to localhost)
        $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@localhost';
        @mail($to, $subject, $message);
        error_log("[ApprovalEngine] Notify approvers: {$entity_type} #{$entity_id} - {$action} (emailed {$to})");
    }

    private function notifyApproval($entity_type, $entity_id, $status)
    {
        error_log("[ApprovalEngine] Notify approval: {$entity_type} #{$entity_id} - {$status}");
    }

    private function notifyRejection($entity_type, $entity_id, $reason)
    {
        error_log("[ApprovalEngine] Notify rejection: {$entity_type} #{$entity_id} - {$reason}");
    }
}
