# 🏗️ Production-Grade Pharmacy ERP - Implementation Guide

## Complete Folder Structure

```
Satyam_Clinical/
├── dbFile/
│   └── migrations/                          # SQL Migrations (version controlled)
│       ├── 001_create_approval_logs.sql
│       ├── 002_create_audit_logs.sql
│       ├── 003_enhance_stock_movements.sql
│       ├── 004_implement_credit_control.sql
│       ├── 005_batch_recall_soft_deletes.sql
│       └── 006_status_workflow.sql
│
├── libraries/
│   ├── Services/                            # Core business logic services
│   │   ├── StockService.php                # Centralized inventory management
│   │   ├── ApprovalEngine.php              # Workflow state machine
│   │   ├── AuditLogger.php                 # Comprehensive audit trail
│   │   └── CreditControl.php               # Customer credit management
│   │
│   ├── Middleware/                          # Request/response middleware
│   │   ├── PermissionMiddleware.php        # Role-based access control
│   │   ├── TransactionMiddleware.php       # Auto transaction management
│   │   └── ValidationMiddleware.php        # Input validation
│   │
│   └── Helpers/                             # Utility functions
│       ├── DatabaseHelper.php              # Database wrapper extending existing
│       ├── ResponseFormatter.php           # Standardized response format
│       └── ValidationRules.php             # Reusable validation rules
│
├── php_action/
│   ├── examples/                            # Reference implementations
│   │   ├── PurchaseOrderController.php
│   │   ├── SalesOrderController.php
│   │   ├── GoodsReceiptController.php      # (needs to be created)
│   │   └── SupplierPaymentController.php   # (needs to be created)
│   │
│   │── create_po.php                       # Updated with new architecture
│   │── order.php                           # Updated with credit control
│   └── OTHER_EXISTING_FILES...
│
├── config/
│   └── services.php                        # Service configuration & factory
│
├── constant/
│   ├── connect.php                         # Main DB connection
│   ├── check.php                           # Existing auth/permission checks
│   └── env.php                             # Database & system constants
│
└── assets/
    └── docs/
        └── PRODUCTION_IMPLEMENTATION.md    # This document
```

---

## Step-by-Step Implementation

### Phase 1: Database (2-3 hours)

#### 1.1 Backup Existing Database

```sql
-- Export current database
mysqldump -u root -p satyam_clinical_new > backup_2026_02_17.sql
```

#### 1.2 Run Migrations

```sql
-- Execute migrations in order:
mysql -u root -p satyam_clinical_new < dbFile/migrations/001_create_approval_logs.sql
mysql -u root -p satyam_clinical_new < dbFile/migrations/002_create_audit_logs.sql
mysql -u root -p satyam_clinical_new < dbFile/migrations/003_enhance_stock_movements.sql
mysql -u root -p satyam_clinical_new < dbFile/migrations/004_implement_credit_control.sql
mysql -u root -p satyam_clinical_new < dbFile/migrations/005_batch_recall_soft_deletes.sql
mysql -u root -p satyam_clinical_new < dbFile/migrations/006_status_workflow.sql
```

#### 1.3 Verify Tables Created

```php
// Quick verification script
<?php
$tables = [
    'approval_logs', 'audit_logs', 'customer_credit_log',
    'customer_payments', 'supplier_payments', 'batch_recalls',
    'batch_sales_map', 'inventory_adjustments', 'invoice_payments'
];

foreach ($tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    echo $table . ": " . (mysqli_num_rows($check) ? "✓" : "✗") . "\n";
}
?>
```

---

### Phase 2: Service Layer (4-5 hours)

#### 2.1 Create Service Factory

Create `config/services.php`:

```php
<?php
/**
 * Service Factory - Initializes all ERP services
 */

namespace Config;

use Services\StockService;
use Services\ApprovalEngine;
use Services\AuditLogger;
use Services\CreditControl;
use Middleware\PermissionMiddleware;

class ServiceContainer
{
    private static $instance = null;
    private $services = [];
    private $db;
    private $user_id;
    private $user_role;

    private function __construct($database, $user_id, $user_role)
    {
        $this->db = $database;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
    }

    public static function getInstance($database, $user_id = null, $user_role = null)
    {
        if (self::$instance === null) {
            self::$instance = new self(
                $database,
                $user_id ?? ($_SESSION['user_id'] ?? null),
                $user_role ?? ($_SESSION['user_role'] ?? 'USER')
            );
        }
        return self::$instance;
    }

    /**
     * Get StockService instance
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
}
?>
```

#### 2.2 Create DatabaseHelper Wrapper

Create `libraries/Helpers/DatabaseHelper.php`:

```php
<?php
namespace Helpers;

class DatabaseHelper
{
    private $connection;

    public function __construct(&$mysqli_connection)
    {
        $this->connection = $mysqli_connection;
    }

    /**
     * Execute query with parameter binding
     */
    public function execute_query($sql, $params = [])
    {
        try {
            if (!$params) {
                return $this->connection->query($sql);
            }

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Prepare failed: " . $this->connection->error);
            }

            if (count($params) > 0) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) $types .= 'i';
                    elseif (is_float($param)) $types .= 'd';
                    else $types .= 's';
                }

                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new \Exception("Execute failed: " . $stmt->error);
            }

            return $stmt->get_result();

        } catch (\Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function begin_transaction()
    {
        return $this->connection->begin_transaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollback();
    }

    public function get_last_insert_id()
    {
        return $this->connection->insert_id;
    }
}
?>
```

---

### Phase 3: Integration into Existing Controllers (6-7 hours)

#### 3.1 Update create_po.php

**Location**: `php_action/create_po.php`

Replace the entire file with production-grade implementation:

```php
<?php
session_start();
header('Content-Type: application/json');

// Include existing dependencies
require_once '../constant/connect.php';
require_once '../constant/check.php';  // For existing auth

// Include new services and middleware
require_once '../config/services.php';
require_once '../libraries/Services/StockService.php';
require_once '../libraries/Services/ApprovalEngine.php';
require_once '../libraries/Services/AuditLogger.php';
require_once '../libraries/Middleware/PermissionMiddleware.php';
require_once '../php_action/examples/PurchaseOrderController.php';

use Config\ServiceContainer;
use Middleware\PermissionMiddleware;
use Controllers\PurchaseOrderController;

try {
    // Initialize services
    $container = ServiceContainer::getInstance($conn);
    $permission = $container->getPermissionMiddleware();

    // Check permission
    if (!$permission->hasPermission('po.create')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }

    // Route to controller
    $controller = new PurchaseOrderController($conn);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $po_id = $_GET['po_id'] ?? $_POST['po_id'] ?? null;

    switch ($action) {
        case 'create':
            echo $controller->createPurchaseOrder();
            break;
        case 'submit':
            echo $controller->submitPO($po_id);
            break;
        case 'approve':
            echo $controller->approvePO($po_id);
            break;
        case 'get_details':
            echo $controller->getPODetails($po_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
```

#### 3.2 Update order.php (Sales)

**Location**: `php_action/order.php`

Similar integration with SalesOrderController with credit control checks.

---

### Phase 4: Testing & Validation (4-5 hours)

#### 4.1 Unit Tests for Services

Create `tests/StockServiceTest.php`:

```php
<?php
class StockServiceTest extends \PHPUnit\Framework\TestCase
{
    private $db;
    private $stock_service;

    protected function setUp(): void
    {
        // Setup test database
        $this->db = new \Helpers\DatabaseHelper($GLOBALS['test_db']);
        $this->stock_service = new \Services\StockService($this->db, null, 1);
    }

    public function testIncreaseStock()
    {
        $result = $this->stock_service->increaseStock(
            1,      // product_id
            1,      // batch_id
            10,     // quantity
            'GRN',  // reference_type
            1       // reference_id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['new_balance']);
    }

    public function testExpiredBatchValidation()
    {
        $this->expectException(\Exception::class);

        // Try to decrement expired batch
        $this->stock_service->decreaseStock(999, 999, 5, 'SALES_ORDER', 1);
    }

    public function testInsufficientStockCheck()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient stock");

        $this->stock_service->decreaseStock(1, 1, 1000, 'SALES_ORDER', 1);
    }
}
?>
```

Run: `vendor/bin/phpunit tests/`

#### 4.2 API Testing Scenarios

**Test Scenario 1: Full PO Lifecycle**

```bash
# 1. Create PO (DRAFT)
curl -X POST http://localhost/api/po/create \
  -H "Content-Type: application/json" \
  -d '{
    "supplier_id": 1,
    "items": [{"product_id": 1, "quantity": 50, "unit_price": 100}]
  }'

# Response:
# {
#   "success": true,
#   "data": {"po_id": 1, "status": "DRAFT"}
# }

# 2. Submit PO (DRAFT → SUBMITTED)
curl -X POST http://localhost/api/po/1/submit

# 3. Approve PO (SUBMITTED → APPROVED)
curl -X POST http://localhost/api/po/1/approve

# 4. Verify approval history
curl http://localhost/api/po/1/approvals
```

**Test Scenario 2: Sales Order with Credit Control**

```bash
# Test credit check before order
curl -X POST http://localhost/api/order/create \
  -d '{
    "customer_id": 5,
    "payment_type": "CREDIT",
    "items": [{"product_id": 1, "batch_id": 1, "quantity": 100}]
  }'

# Expected: { "credit_approval_required": true, "status": "PENDING_CREDIT_APPROVAL" }

# Approve credit override
curl -X POST http://localhost/api/order/123/approve-credit
```

---

## Database Indexes for Performance

```sql
-- Critical indexes for transactions
CREATE INDEX idx_pb_product_batch ON product_batches(product_id, batch_id);
CREATE INDEX idx_pb_current_qty ON product_batches(current_qty);
CREATE INDEX idx_pb_exp_date ON product_batches(exp_date);

CREATE INDEX idx_sm_product_movement ON stock_movements(product_id, movement_type);
CREATE INDEX idx_sm_recorded_at ON stock_movements(recorded_at);

CREATE INDEX idx_orders_customer_status ON orders(customer_id, order_status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);

CREATE INDEX idx_po_status_supplier ON purchase_orders(status, supplier_id);

-- Audit indexes
CREATE INDEX idx_audit_table_record ON audit_logs(table_name, record_id);
CREATE INDEX idx_audit_user_action ON audit_logs(user_id, action);
CREATE INDEX idx_audit_timestamp ON audit_logs(action_timestamp);
```

---

## Critical Implementation Rules

### ✅ DO:

1. **Always use transactions** for multi-step operations:

   ```php
   $db->begin_transaction();
   try {
       // All operations
       $db->commit();
   } catch {
       $db->rollback();
   }
   ```

2. **Always check permissions** before critical actions:

   ```php
   $permission->enforcePermission('po.approve');
   ```

3. **Always validate batch expiry** for sales:

   ```php
   if (strtotime($batch['exp_date']) < time()) {
       throw new \Exception("Batch expired");
   }
   ```

4. **Always audit critical changes**:
   ```php
   $audit_logger->logUpdate('purchase_orders', $po_id, $old_data, $new_data);
   ```

### ❌ DON'T:

1. **Never update stock outside StockService**:

   ```php
   // WRONG:
   UPDATE product_batches SET current_qty = current_qty - 10;

   // RIGHT:
   $stock_service->decreaseStock($product_id, $batch_id, 10, 'SALES_ORDER', $order_id);
   ```

2. **Never hard-delete financial records**:

   ```php
   // WRONG:
   DELETE FROM purchase_orders WHERE id = 1;

   // RIGHT:
   UPDATE purchase_orders SET deleted_at = NOW(), status = 'CANCELLED' WHERE id = 1;
   ```

3. **Never trust user input**:

   ```php
   // WRONG:
   if ($_POST['amount'] > 100) {}

   // RIGHT:
   $amount = floatval($_POST['amount']);
   if ($amount > 0 && $amount <= 999999.99) {}
   ```

4. **Never skip approval workflows**:

   ```php
   // WRONG:
   UPDATE purchase_orders SET status = 'APPROVED' WHERE id = $po_id;

   // RIGHT:
   $approval_engine->approveEntity('PO', $po_id,  "Approved");
   ```

---

## Migration Checklist

- [ ] Backup existing database
- [ ] Run all 6 SQL migrations
- [ ] Create `/libraries/Services/` directory
- [ ] Create `/libraries/Middleware/` directory
- [ ] Create `/libraries/Helpers/` directory
- [ ] Copy all service classes to libraries
- [ ] Create `config/services.php` factory
- [ ] Update `php_action/create_po.php`
- [ ] Update `php_action/order.php`
- [ ] Add required columns to `users` table (role field)
- [ ] Test PO creation workflow
- [ ] Test Sales order with credit control
- [ ] Verify audit logs are being written
- [ ] Set up log rotation for audit_logs
- [ ] Update user documentation
- [ ] Train team on new approval workflows
- [ ] Go live on staging for 1 week
- [ ] Go live on production

---

## Support for Existing Code

**All existing functionality remains backward compatible.** The new architecture:

1. Coexists with existing controllers
2. Extends but doesn't replace current forms
3. Can be adopted incrementally per module
4. Maintains current database schema (adds new tables only)

**Gradual Migration Path:**

```
Week 1-2: Database & Services only
Week 3: Update PO module
Week 4: Update GRN module
Week 5: Update Invoice module
Week 6: Update Sales module
Week 7: Monitoring & optimization
Week 8: Full launch with audit & credit control
```

---

## Performance & Scaling

- **Stock movements**: Indexed for real-time queries
- **Audit logs**: Separate table, can be archived annually
- **Approval logs**: Compact, indexed for pending approvals
- **Credit summary**: Denormalized balance field for fast queries

**Expected transaction time:**

- PO Creation: 200-500ms
- Stock deduction: 100-200ms
- Order with credit check: 300-600ms

---

## Documentation Resources

- [StockService API](../libraries/Services/StockService.php) - Line 1-50
- [ApprovalEngine Workflow](../libraries/Services/ApprovalEngine.php) - Line 1-80
- [AuditLogger Features](../libraries/Services/AuditLogger.php) - Line 1-60
- [CreditControl Rules](../libraries/Services/CreditControl.php) - Line 1-80
- [PermissionMiddleware Setup](../libraries/Middleware/PermissionMiddleware.php) - Line 1-70

---

**Implementation Date**: February 17, 2026  
**Version**: 2.0 Production-Grade  
**Status**: Ready for Implementation
