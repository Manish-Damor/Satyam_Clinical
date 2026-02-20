<?php
/**
 * Service Factory - ServiceContainer
 * Initializes and manages all ERP service instances
 * 
 * Usage:
 *   $services = ServiceContainer::getInstance($db, $user_id, $user_role);
 *   $stock_service = $services->getStockService();
 *   $approval_engine = $services->getApprovalEngine();
 */

namespace Config;

// Import service classes
use Services\StockService;
use Services\ApprovalEngine;
use Services\AuditLogger;
use Services\CreditControl;
use Middleware\PermissionMiddleware;
use Helpers\DatabaseHelper;

class ServiceContainer
{
    private static $instance = null;
    private $services = [];
    private $db;
    private $db_helper;
    private $user_id;
    private $user_role;

    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct($database, $user_id, $user_role)
    {
        $this->db = $database;
        $this->db_helper = new DatabaseHelper($database);
        $this->user_id = $user_id ?? 1;  // Default to user 1 if not provided
        $this->user_role = $user_role ?? 'USER';
    }

    /**
     * Get singleton instance of ServiceContainer
     * @param \mysqli $database Database connection
     * @param int $user_id Current user ID (optional, from session)
     * @param string $user_role Current user role (optional, from session)
     * @return ServiceContainer
     */
    public static function getInstance($database, $user_id = null, $user_role = null)
    {
        if (self::$instance === null) {
            self::$instance = new self(
                $database,
                $user_id ?? ($_SESSION['user_id'] ?? 1),
                $user_role ?? ($_SESSION['user_role'] ?? 'USER')
            );
        }
        return self::$instance;
    }

    /**
     * Reset singleton (useful for testing)
     */
    public static function reset()
    {
        self::$instance = null;
    }

    /**
     * Get StockService instance
     * @param int $warehouse_id Default warehouse (1 = main store)
     * @return StockService
     */
    public function getStockService($warehouse_id = 1)
    {
        $key = "stock_service_{$warehouse_id}";
        if (!isset($this->services[$key])) {
            $this->services[$key] = new StockService(
                $this->db,
                $this->getAuditLogger(),
                $this->user_id,
                $warehouse_id
            );
        }
        return $this->services[$key];
    }

    /**
     * Get ApprovalEngine instance
     * @return ApprovalEngine
     */
    public function getApprovalEngine()
    {
        if (!isset($this->services['approval_engine'])) {
            $this->services['approval_engine'] = new ApprovalEngine(
                $this->db,
                $this->user_id,
                $this->user_role,
                $this->getAuditLogger()
            );
        }
        return $this->services['approval_engine'];
    }

    /**
     * Get AuditLogger instance
     * @return AuditLogger
     */
    public function getAuditLogger()
    {
        if (!isset($this->services['audit_logger'])) {
            $this->services['audit_logger'] = new AuditLogger($this->db, $this->user_id);
        }
        return $this->services['audit_logger'];
    }

    /**
     * Get CreditControl instance
     * @return CreditControl
     */
    public function getCreditControl()
    {
        if (!isset($this->services['credit_control'])) {
            $this->services['credit_control'] = new CreditControl(
                $this->db,
                $this->user_id,
                $this->getAuditLogger()
            );
        }
        return $this->services['credit_control'];
    }

    /**
     * Get PermissionMiddleware instance
     * @return PermissionMiddleware
     */
    public function getPermissionMiddleware()
    {
        if (!isset($this->services['permission'])) {
            $this->services['permission'] = new PermissionMiddleware(
                $this->user_role,
                $this->user_id
            );
        }
        return $this->services['permission'];
    }

    /**
     * Get DatabaseHelper instance
     * @return DatabaseHelper
     */
    public function getDatabaseHelper()
    {
        return $this->db_helper;
    }

    /**
     * Get all registered services (for testing/debugging)
     * @return array
     */
    public function getAllServices()
    {
        return $this->services;
    }

    /**
     * Get current user ID
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get current user role
     * @return string
     */
    public function getUserRole()
    {
        return $this->user_role;
    }
}

/**
 * Global helper function to get ServiceContainer
 * Add to your bootstrap/init file to use: $services = getServices();
 */
function getServices($db = null)
{
    global $connect;
    $db = $db ?? $connect;
    return ServiceContainer::getInstance($db);
}
?>
