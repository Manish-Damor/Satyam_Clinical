<?php
namespace Controllers;

use Services\StockService;
use Services\ApprovalEngine;
use Services\AuditLogger;
use Helpers\DatabaseHelper;

class PurchaseOrderController {
    
    private $db;
    private $stockService;
    private $approvalEngine;
    private $auditLogger;
    private $userId;
    private $userRole;
    
    public function __construct($connect, $userId = 0, $userRole = 'user') {
        $this->db = new DatabaseHelper($connect);
        $this->stockService = new StockService($connect);
        $this->auditLogger = new AuditLogger($connect, $userId);
        $this->approvalEngine = new ApprovalEngine($connect, $userId, $userRole, $this->auditLogger);
        $this->userId = $userId;
        $this->userRole = $userRole;
    }
    
    /**
     * Create a new Purchase Order with full transaction support
     * Uses service layer for audit logging and approval workflow
     * 
     * @param array $poData PO header data (po_number, po_date, supplier_id, etc)
     * @param array $items Item array with medicine_id, quantity, price, tax, etc
     * @return array Success/Error response with po_id on success
     */
    public function createPurchaseOrder($poData, $items) {
        
        $response = [
            'success' => false,
            'po_id' => null,
            'message' => '',
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // ========================================
            // 1. VALIDATE INPUT DATA
            // ========================================
            $validation = $this->validatePOData($poData, $items);
            if (!$validation['valid']) {
                $response['errors'] = $validation['errors'];
                $response['message'] = 'Validation failed: ' . implode(', ', $validation['errors']);
                return $response;
            }
            
            // ========================================
            // 2. START TRANSACTION
            // ========================================
            $this->db->begin_transaction();
            
            // ========================================
            // 3. INSERT PO MASTER RECORD
            // ========================================
            $poId = $this->insertPOMaster($poData);
            
            if (!$poId) {
                throw new \Exception('Failed to create PO master record');
            }
            
            // ========================================
            // 4. INSERT PO ITEMS & LOCK SUPPLIER STOCK
            // ========================================
            $itemCount = 0;
            foreach ($items as $item) {
                if (empty($item['medicine_id']) || empty($item['quantity'])) {
                    continue;
                }
                
                $this->insertPOItem($poId, $poData['po_number'], $item);
                $itemCount++;
            }
            
            if ($itemCount === 0) {
                throw new \Exception('No valid items were added to the PO');
            }
            
            // ========================================
            // 5. CREATE INITIAL WORKFLOW STATE
            // ========================================
            $this->approvalEngine->initializeApprovalWorkflow(
                'purchase_order',
                $poId,
                $poData['po_status'] ?? 'draft',
                $this->userId,
                'PO Created: ' . $poData['po_number']
            );
            
            // ========================================
            // 6. LOG AUDIT TRAIL
            // ========================================
            $this->auditLogger->logChange(
                'purchase_order',
                $poId,
                'INSERT',
                null,
                $poData,
                'PurchaseOrderController::createPurchaseOrder',
                $this->userId
            );
            
            // ========================================
            // 7. COMMIT TRANSACTION
            // ========================================
            $this->db->commit();
            
            $response['success'] = true;
            $response['po_id'] = $poId;
            $response['message'] = "Purchase Order {$poData['po_number']} created successfully with $itemCount items";
            
        } catch (\Exception $e) {
            
            // Rollback on any error
            $this->db->rollback();
            
            $response['success'] = false;
            $response['message'] = 'Error: ' . $e->getMessage();
            
            // Log the error to audit trail
            $this->auditLogger->logError(
                'PurchaseOrderController::createPurchaseOrder [ERROR] - ' . $e->getMessage()
            );
        }
        
        return $response;
    }
    
    /**
     * Validate PO data before insertion
     */
    private function validatePOData($poData, $items) {
        $validation = ['valid' => true, 'errors' => []];
        
        // Check required fields
        if (empty($poData['po_number'])) {
            $validation['errors'][] = 'PO Number is required';
        }
        
        if (empty($poData['supplier_id']) || $poData['supplier_id'] <= 0) {
            $validation['errors'][] = 'Valid Supplier ID is required';
        } else {
            // Verify supplier exists
            $supplierCheck = $this->db->execute_query(
                "SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND supplier_status = 'Active'",
                [$poData['supplier_id']]
            );
            if (!$supplierCheck || $supplierCheck->num_rows === 0) {
                $validation['errors'][] = 'Supplier not found or inactive';
            }
        }
        
        if (empty($poData['po_date'])) {
            $validation['errors'][] = 'PO Date is required';
        }
        
        // Check items array
        if (empty($items) || !is_array($items)) {
            $validation['errors'][] = 'At least one item is required';
        }
        
        $validation['valid'] = count($validation['errors']) === 0;
        return $validation;
    }
    
    /**
     * Insert PO master record
     */
    private function insertPOMaster($poData) {
        
        $poStatus = $this->normalizePoStatus($poData['po_status'] ?? 'Draft');
        $status = strtoupper($poStatus);

        $sql = "
            INSERT INTO purchase_orders (
                po_number, po_date, supplier_id,
                expected_delivery_date, delivery_location,
                subtotal, discount_percentage, discount_amount,
                gst_percentage, gst_amount, other_charges,
                grand_total, po_status, payment_status,
                notes, created_by, submitted_at, status
            ) VALUES (
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?
            )
        ";
        
        $params = [
            $poData['po_number'] ?? '',
            $poData['po_date'] ?? date('Y-m-d'),
            (int)($poData['supplier_id'] ?? 0),
            $poData['expected_delivery_date'] ?? null,
            $poData['delivery_location'] ?? 'Main Warehouse',
            (float)($poData['sub_total'] ?? 0),
            (float)($poData['discount_percent'] ?? 0),
            (float)($poData['total_discount'] ?? 0),
            (float)($poData['gst_percent'] ?? 0),
            (float)($poData['gst_amount'] ?? 0),
            (float)($poData['other_charges'] ?? 0),
            (float)($poData['grand_total'] ?? 0),
            $poStatus,
            $poData['payment_status'] ?? 'NotDue',
            $poData['notes'] ?? '',
            (int)$this->userId,
            date('Y-m-d H:i:s'),
            $status
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if ($result && $result !== false) {
            return $this->db->get_last_insert_id();
        }
        
        throw new \Exception('Failed to insert PO master: ' . $this->db->get_last_error());
    }
    
    /**
     * Insert a single PO item
     */
    private function insertPOItem($poId, $poNumber, $item) {

        $productId = (int)($item['product_id'] ?? ($item['medicine_id'] ?? 0));
        if ($productId <= 0) {
            throw new \Exception('Missing product ID');
        }
        
        // Verify medicine exists
        $medicineCheck = $this->db->execute_query(
            "SELECT product_id FROM product WHERE product_id = ? AND status = 1",
            [$productId]
        );
        
        if (!$medicineCheck || $medicineCheck->num_rows === 0) {
            throw new \Exception("Product ID {$productId} not found");
        }
        
        $sql = "
            INSERT INTO po_items (
                po_id, product_id,
                quantity_ordered, quantity_received,
                unit_price, total_price,
                item_status, notes, gst_percentage
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $quantity = (int)($item['quantity'] ?? 0);
        $unitPrice = (float)($item['unit_price'] ?? 0);
        $totalPrice = $quantity * $unitPrice;
        $taxPercent = (float)($item['gst_percentage'] ?? ($item['tax_percent'] ?? 0));
        
        $params = [
            $poId,
            $productId,
            $quantity,
            0,
            $unitPrice,
            $totalPrice,
            'Pending',
            $item['notes'] ?? ($item['medicine_name'] ?? ''),
            $taxPercent
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if (!$result || $result === false) {
            throw new \Exception('Failed to insert PO item: ' . $this->db->get_last_error());
        }
    }
    
    /**
     * Submit PO for approval (changes status and triggers workflow)
     */
    public function submitForApproval($poId) {
        
        $response = ['success' => false, 'message' => ''];
        
        try {
            $this->db->begin_transaction();
            
            // Update PO status
            $sql = "UPDATE purchase_orders SET po_status = 'Submitted', submitted_at = ?, status = 'SUBMITTED' WHERE po_id = ?";
            $this->db->execute_query($sql, [date('Y-m-d H:i:s'), $poId]);
            
            // Update approval workflow status
            $this->approvalEngine->updateApprovalStatus(
                'purchase_order',
                $poId,
                'submitted',
                $this->userId,
                'PO submitted for approval'
            );
            
            $this->db->commit();
            
            $response['success'] = true;
            $response['message'] = 'PO submitted for approval successfully';
            
        } catch (\Exception $e) {
            $this->db->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $response;
    }

    /**
     * Normalize PO status to enum values expected by purchase_orders.po_status.
     */
    private function normalizePoStatus($status)
    {
        $value = strtolower(trim((string)$status));
        $map = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'partialreceived' => 'PartialReceived',
            'partial_received' => 'PartialReceived',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled'
        ];

        return $map[$value] ?? 'Draft';
    }
    
}
?>
