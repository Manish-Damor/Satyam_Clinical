<?php
/**
 * PermissionMiddleware - Role-Based Access Control
 * 
 * Enforces role-based permissions for:
 * - Approval actions (PO approval, GRN approval, etc)
 * - Stock adjustments (requires STORE_MANAGER role)
 * - Credit limit changes (requires MANAGER role)
 * - Payment processing (requires ACCOUNTANT role)
 * - View audit logs (requires MANAGER or ADMIN)
 * 
 * @package Middleware
 * @version 2.0
 * @date February 2026
 */

namespace Middleware;

class PermissionMiddleware
{
    // Define role permissions
    const ROLE_PERMISSIONS = [
        'SUPER_ADMIN' => [
            'all' // Unrestricted access
        ],
        'ADMIN' => [
            'approve_po',
            'approve_grn',
            'approve_invoice',
            'approve_adjustment',
            'record_payment',
            'post_to_ledger',
            'view_audit_logs',
            'manage_users',
            'create_po',
            'create_grn',
            'create_invoice',
            'manage_credit_limits'
        ],
        'MANAGER' => [
            'approve_po',
            'approve_grn',
            'approve_invoice',
            'reject_documents',
            'view_audit_logs',
            'create_po',
            'create_grn',
            'create_invoice',
            'manage_credit_limits',
            'approve_credit_override',
            'cancel_orders'
        ],
        'FINANCE_MANAGER' => [
            'approve_invoice',
            'record_payment',
            'post_to_ledger',
            'view_audit_logs',
            'view_financial_reports',
            'manage_supplier_payments'
        ],
        'ACCOUNTANT' => [
            'create_invoice',
            'record_payment',
            'post_to_ledger',
            'view_financial_reports',
            'manage_supplier_payments',
            'view_audit_logs'
        ],
        'QC_MANAGER' => [
            'approve_grn',
            'mark_quality_check',
            'view_audit_logs'
        ],
        'STORE_MANAGER' => [
            'create_po',
            'create_grn',
            'submit_po',
            'submit_grn',
            'adjust_stock',
            'create_adjustment',
            'view_stock_reports'
        ],
        'SALES_EXEC' => [
            'create_sales_order',
            'view_stock_levels',
            'view_customer_credit'
        ],
        'USER' => [
            'view_reports', // Basic read-only access
            'view_own_profile'
        ]
    ];

    // Define permission requirements for specific actions
    const ACTION_PERMISSIONS = [
        // PO Actions
        'po.create' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'po.submit' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'po.approve' => ['MANAGER', 'ADMIN'],
        'po.post' => ['ADMIN'],
        'po.cancel' => ['MANAGER', 'ADMIN'],

        // GRN Actions
        'grn.create' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'grn.submit' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'grn.quality_check' => ['QC_MANAGER', 'MANAGER', 'ADMIN'],
        'grn.approve' => ['QC_MANAGER', 'MANAGER', 'ADMIN'],
        'grn.post' => ['ADMIN'],

        // Invoice Actions
        'invoice.create' => ['ACCOUNTANT', 'MANAGER', 'ADMIN'],
        'invoice.submit' => ['ACCOUNTANT', 'MANAGER', 'ADMIN'],
        'invoice.approve' => ['MANAGER', 'FINANCE_MANAGER', 'ADMIN'],
        'invoice.post' => ['ACCOUNTANT', 'ADMIN'],

        // Payment Actions
        'payment.record' => ['ACCOUNTANT', 'FINANCE_MANAGER', 'ADMIN'],
        'payment.reconcile' => ['ACCOUNTANT', 'ADMIN'],
        'payment.reverse' => ['FINANCE_MANAGER', 'ADMIN'],

        // Stock Actions
        'stock.adjust' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'stock.create_adjustment' => ['STORE_MANAGER', 'MANAGER', 'ADMIN'],
        'stock.approve_adjustment' => ['MANAGER', 'ADMIN'],

        // Credit Actions
        'credit.set_limit' => ['MANAGER', 'ADMIN'],
        'credit.change_status' => ['MANAGER', 'ADMIN'],
        'credit.approve_override' => ['MANAGER', 'ADMIN'],

        // Sales Actions
        'sales.create_order' => ['SALES_EXEC', 'MANAGER', 'ADMIN'],
        'sales.approve_credit_order' => ['MANAGER', 'ADMIN'],
        'sales.cancel_order' => ['MANAGER', 'ADMIN'],

        // Audit/Admin Actions
        'audit.view_logs' => ['MANAGER', 'ADMIN'],
        'users.manage' => ['ADMIN'],
        'report.financial' => ['ACCOUNTANT', 'FINANCE_MANAGER', 'MANAGER', 'ADMIN']
    ];

    private $user_role;
    private $user_id;
    private $last_permission_error = '';

    public function __construct($user_role, $user_id = null)
    {
        $this->user_role = $user_role;
        $this->user_id = $user_id;
    }

    /**
     * Check if user has permission for specific action
     * 
     * @param string $action Action identifier (e.g., 'po.approve')
     * @return bool Permission granted
     */
    public function hasPermission($action)
    {
        // Super admin has all permissions
        if ($this->user_role === 'SUPER_ADMIN') {
            return true;
        }

        // Check if action is defined
        if (!isset(self::ACTION_PERMISSIONS[$action])) {
            $this->last_permission_error = "Action '{$action}' not recognized";
            return false;
        }

        $allowed_roles = self::ACTION_PERMISSIONS[$action];

        if (!in_array($this->user_role, $allowed_roles)) {
            $this->last_permission_error = "Role '{$this->user_role}' not authorized for '{$action}'";
            return false;
        }

        return true;
    }

    /**
     * Enforce permission - throw error if not authorized
     * 
     * @param string $action Action identifier
     * @return bool Always returns true if check passes
     * @throws \Exception If permission denied
     */
    public function enforcePermission($action)
    {
        if (!$this->hasPermission($action)) {
            throw new \Exception("Permission denied: " . $this->last_permission_error);
        }
        return true;
    }

    /**
     * Check if user can approve entity type
     * 
     * @param string $entity_type Entity type (PO, GRN, INVOICE, ADJUSTMENT)
     * @return bool Can approve
     */
    public function canApprove($entity_type)
    {
        $actions = [
            'PO' => 'po.approve',
            'GRN' => 'grn.approve',
            'INVOICE' => 'invoice.approve',
            'ADJUSTMENT' => 'stock.approve_adjustment'
        ];

        if (!isset($actions[$entity_type])) {
            return false;
        }

        return $this->hasPermission($actions[$entity_type]);
    }

    /**
     * Check if user can perform stock adjustments
     * 
     * @return bool Can adjust stock
     */
    public function canAdjustStock()
    {
        return $this->hasPermission('stock.adjust') || $this->hasPermission('stock.create_adjustment');
    }

    /**
     * Check if user can record payments
     * 
     * @return bool Can record payments
     */
    public function canRecordPayments()
    {
        return $this->hasPermission('payment.record');
    }

    /**
     * Check if user can manage credit
     * 
     * @return bool Can manage credit
     */
    public function canManageCredit()
    {
        return $this->hasPermission('credit.set_limit') || $this->hasPermission('credit.change_status');
    }

    /**
     * Check if user can view audit logs
     * 
     * @return bool Can view audit logs
     */
    public function canViewAuditLogs()
    {
        return $this->hasPermission('audit.view_logs');
    }

    /**
     * Check if user can approve credit override
     * 
     * @return bool Can approve override
     */
    public function canApproveCreditOverride()
    {
        return $this->hasPermission('credit.approve_override');
    }

    /**
     * Get all permissions for user role
     * 
     * @return array List of permissions
     */
    public function getPermissions()
    {
        if ($this->user_role === 'SUPER_ADMIN') {
            return ['all'];
        }

        return self::ROLE_PERMISSIONS[$this->user_role] ?? [];
    }

    /**
     * Get available roles
     * 
     * @return array List of roles
     */
    public static function getAvailableRoles()
    {
        return array_keys(self::ROLE_PERMISSIONS);
    }

    /**
     * Get last permission error
     * 
     * @return string Error message
     */
    public function getLastError()
    {
        return $this->last_permission_error;
    }
}

/**
 * Helper function to quickly check permission in templates/controllers
 */
function userCan($action, $user_role = null)
{
    if (!$user_role) {
        $user_role = $_SESSION['user_role'] ?? null;
    }

    if (!$user_role) {
        return false;
    }

    $middleware = new PermissionMiddleware($user_role);
    return $middleware->hasPermission($action);
}
