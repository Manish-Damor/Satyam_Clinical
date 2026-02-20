<?php
namespace Controllers;

use Services\StockService;
use Services\ApprovalEngine;
use Services\AuditLogger;
use Helpers\DatabaseHelper;

class GRNController {
    
    private $db;
    private $stockService;
    private $approvalEngine;
    private $auditLogger;
    private $userId;
    private $userRole;
    
    public function __construct($connect, $userId = 0, $userRole = 'user') {
        $this->db = new DatabaseHelper($connect);
        $this->stockService = new StockService($connect, null, $userId);
        $this->auditLogger = new AuditLogger($connect, $userId);
        $this->approvalEngine = new ApprovalEngine($connect, $userId, $userRole, $this->auditLogger);
        $this->userId = $userId;
        $this->userRole = $userRole;
    }
    
    /**
     * Create Goods Received Note (GRN) against a Purchase Order
     * Includes quality check validation workflow
     * Adds received stock to warehouse inventory
     * 
     * @param array $grnData GRN header (po_id, grn_date, warehouse_id, etc)
     * @param array $items Items received (po_item_id, quantity_received, batch_info, etc)
     * @param array $qualityChecks Quality inspection results (item_id, check_result, notes, etc)
     * @return array Success/Error response with grn_id on success
     */
    public function createGRN($grnData, $items, $qualityChecks = []) {
        
        $response = [
            'success' => false,
            'grn_id' => null,
            'grn_number' => null,
            'message' => '',
            'errors' => [],
            'warnings' => [],
            'quality_summary' => [
                'total_items' => 0,
                'passed_items' => 0,
                'failed_items' => 0,
                'hold_items' => 0
            ]
        ];
        
        try {
            // ========================================
            // 1. VALIDATE INPUT DATA
            // ========================================
            $validation = $this->validateGRNData($grnData, $items);
            if (!$validation['valid']) {
                $response['errors'] = $validation['errors'];
                $response['message'] = 'Validation failed: ' . implode(', ', $validation['errors']);
                return $response;
            }
            
            // ========================================
            // 2. VERIFY PURCHASE ORDER
            // ========================================
            $poInfo = $this->getPurchaseOrderInfo($grnData['po_id']);
            if (!$poInfo) {
                $response['errors'][] = 'PO not found';
                $response['message'] = 'Invalid Purchase Order';
                return $response;
            }
            
            // ========================================
            // 3. START TRANSACTION
            // ========================================
            $this->db->begin_transaction();
            
            // ========================================
            // 4. INSERT GRN MASTER RECORD
            // ========================================
            $grnId = $this->insertGRNMaster($grnData, $poInfo);
            
            if (!$grnId) {
                throw new \Exception('Failed to create GRN master record');
            }
            
            $grnNumber = $this->getGRNNumber($grnId);
            $response['grn_number'] = $grnNumber;
            
            // ========================================
            // 5. PROCESS GRN ITEMS WITH QUALITY CHECKS
            // ========================================
            $itemCount = 0;
            $qualityData = [];
            
            foreach ($items as $item) {
                if (empty($item['po_item_id']) || empty($item['quantity_received'])) {
                    continue;
                }
                
                // Get PO item details
                $poItem = $this->getPOItemInfo($item['po_item_id']);
                if (!$poItem) {
                    $response['warnings'][] = "PO Item {$item['po_item_id']} not found, skipping";
                    continue;
                }
                
                // Check quality for this item
                $itemQuality = $qualityChecks[$item['po_item_id']] ?? null;
                $qualityStatus = 'passed'; // Default
                $qualityNotes = '';
                
                if ($itemQuality) {
                    $qualityStatus = strtolower($itemQuality['check_result'] ?? 'passed');
                    $qualityNotes = $itemQuality['notes'] ?? '';
                    
                    // Update summary
                    $response['quality_summary']['total_items']++;
                    
                    if ($qualityStatus === 'passed') {
                        $response['quality_summary']['passed_items']++;
                    } elseif ($qualityStatus === 'failed') {
                        $response['quality_summary']['failed_items']++;
                        $response['warnings'][] = "Item {$poItem['medicine_id']} failed quality check";
                    } elseif ($qualityStatus === 'hold') {
                        $response['quality_summary']['hold_items']++;
                        $response['warnings'][] = "Item {$poItem['medicine_id']} placed on hold pending review";
                    }
                }
                
                // Insert GRN item
                $grnItemId = $this->insertGRNItem($grnId, $grnNumber, $item, $poItem, $qualityStatus, $qualityNotes);
                
                // Only add to stock if quality passed and quantity received is valid
                if ($qualityStatus === 'passed' && (int)$item['quantity_received'] > 0) {
                    
                    // Add stock through StockService
                    $stockResult = $this->stockService->increaseStock(
                        $item['product_id'] ?? $poItem['medicine_id'],
                        (int)$item['quantity_received'],
                        'goods_received',
                        $grnId,
                        $this->userId,
                        [
                            'batch_number' => $item['batch_number'] ?? $poItem['batch_number'],
                            'expiry_date' => $item['expiry_date'] ?? $poItem['expiry_date'],
                            'warehouse_id' => $grnData['warehouse_id'] ?? 1
                        ]
                    );
                    
                    if (!$stockResult) {
                        throw new \Exception("Failed to add stock for item {$poItem['medicine_id']}");
                    }
                }
                
                $itemCount++;
            }
            
            if ($itemCount === 0) {
                throw new \Exception('No valid GRN items were processed');
            }
            
            // ========================================
            // 6. INITIALIZE APPROVAL WORKFLOW
            // ========================================
            // Determine initial status based on quality results
            $grnStatus = 'pending_approval';
            if ($response['quality_summary']['failed_items'] > 0) {
                $grnStatus = 'rejected';
            }
            
            $this->approvalEngine->initializeApprovalWorkflow(
                'goods_received',
                $grnId,
                $grnStatus,
                $this->userId,
                "GRN {$grnNumber}: {$itemCount} items received, Quality: {$response['quality_summary']['passed_items']} passed, {$response['quality_summary']['failed_items']} failed"
            );
            
            // ========================================
            // 7. LOG AUDIT TRAIL
            // ========================================
            $this->auditLogger->logChange(
                'goods_received',
                $grnId,
                'INSERT',
                null,
                $grnData + ['items_count' => $itemCount],
                'GRNController::createGRN',
                $this->userId,
                [
                    'quality_summary' => $response['quality_summary'],
                    'po_id' => $grnData['po_id']
                ]
            );
            
            // ========================================
            // 8. COMMIT TRANSACTION
            // ========================================
            $this->db->commit();
            
            $response['success'] = true;
            $response['grn_id'] = $grnId;
            $response['message'] = "GRN {$grnNumber} created successfully with $itemCount items. Quality Check: {$response['quality_summary']['passed_items']} passed, {$response['quality_summary']['failed_items']} rejected";
            
        } catch (\Exception $e) {
            
            // Rollback on any error
            $this->db->rollback();
            
            $response['success'] = false;
            $response['message'] = 'Error: ' . $e->getMessage();
            
            // Log the error
            $this->auditLogger->logChange(
                'goods_received',
                null,
                'INSERT',
                null,
                $grnData ?? [],
                'GRNController::createGRN [ERROR]',
                $this->userId,
                ['error' => $e->getMessage()]
            );
        }
        
        return $response;
    }
    
    /**
     * Validate GRN data
     */
    private function validateGRNData($grnData, $items) {
        $validation = ['valid' => true, 'errors' => []];
        
        if (empty($grnData['po_id']) || $grnData['po_id'] <= 0) {
            $validation['errors'][] = 'Valid Purchase Order ID is required';
        }
        
        if (empty($grnData['grn_date'])) {
            $validation['errors'][] = 'GRN date is required';
        }
        
        if (empty($items) || !is_array($items)) {
            $validation['errors'][] = 'At least one item is required';
        }
        
        $validation['valid'] = count($validation['errors']) === 0;
        return $validation;
    }
    
    /**
     * Get PO info
     */
    private function getPurchaseOrderInfo($poId) {
        $result = $this->db->execute_query(
            "SELECT po_id, po_number, supplier_id, supplier_name, po_status FROM purchase_order WHERE po_id = ?",
            [$poId]
        );
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get PO item info
     */
    private function getPOItemInfo($poItemId) {
        $result = $this->db->execute_query(
            "SELECT po_item_id, po_id, medicine_id, medicine_name, batch_number, expiry_date, quantity_ordered FROM po_items WHERE po_item_id = ?",
            [$poItemId]
        );
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Insert GRN master record
     */
    private function insertGRNMaster($grnData, $poInfo) {
        
        $sql = "
            INSERT INTO goods_received (
                po_id, po_number, grn_date,
                supplier_id, supplier_name,
                warehouse_id, received_by,
                status, quality_check_status,
                notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $grnData['po_id'],
            $poInfo['po_number'],
            $grnData['grn_date'] ?? date('Y-m-d'),
            $poInfo['supplier_id'] ?? 0,
            $poInfo['supplier_name'] ?? '',
            $grnData['warehouse_id'] ?? 1,
            $grnData['received_by'] ?? $this->userId,
            'pending_approval',
            'pending',
            $grnData['notes'] ?? '',
            $this->userId
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if ($result && $result !== false) {
            return $this->db->get_last_insert_id();
        }
        
        throw new \Exception('Failed to insert GRN master: ' . $this->db->get_last_error());
    }
    
    /**
     * Insert GRN item
     */
    private function insertGRNItem($grnId, $grnNumber, $item, $poItem, $qualityStatus, $qualityNotes) {
        
        $sql = "
            INSERT INTO grn_items (
                grn_id, grn_number, po_item_id,
                medicine_id, medicine_name,
                batch_number, expiry_date,
                quantity_ordered, quantity_received,
                quality_check_status, quality_notes,
                added_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $grnId,
            $grnNumber,
            $item['po_item_id'],
            $poItem['medicine_id'],
            $poItem['medicine_name'],
            $item['batch_number'] ?? $poItem['batch_number'],
            $item['expiry_date'] ?? $poItem['expiry_date'],
            $poItem['quantity_ordered'],
            (int)$item['quantity_received'],
            $qualityStatus,
            $qualityNotes,
            date('Y-m-d')
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if ($result && $result !== false) {
            return $this->db->get_last_insert_id();
        }
        
        throw new \Exception('Failed to insert GRN item: ' . $this->db->get_last_error());
    }
    
    /**
     * Get GRN number from ID
     */
    private function getGRNNumber($grnId) {
        $result = $this->db->execute_query(
            "SELECT grn_number FROM goods_received WHERE grn_id = ? LIMIT 1",
            [$grnId]
        );
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['grn_number'] ?? 'GRN-' . $grnId;
        }
        
        return 'GRN-' . $grnId;
    }
    
    /**
     * Approve GRN and finalize stock allocation
     */
    public function approveGRN($grnId, $approverNotes = '') {
        
        $response = ['success' => false, 'message' => ''];
        
        try {
            $this->db->begin_transaction();
            
            // Update GRN status
            $sql = "UPDATE goods_received SET status = 'approved', quality_check_status = 'approved' WHERE grn_id = ?";
            $this->db->execute_query($sql, [$grnId]);
            
            // Update approval workflow
            $this->approvalEngine->updateApprovalStatus(
                'goods_received',
                $grnId,
                'approved',
                $this->userId,
                'GRN approved: ' . $approverNotes
            );
            
            $this->db->commit();
            
            $response['success'] = true;
            $response['message'] = 'GRN approved successfully';
            
        } catch (\Exception $e) {
            $this->db->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $response;
    }
    
    /**
     * Reject GRN item (for quality failures)
     */
    public function rejectGRNItem($grnItemId, $rejectReason) {
        
        $response = ['success' => false, 'message' => ''];
        
        try {
            $sql = "UPDATE grn_items SET quality_check_status = 'rejected', quality_notes = ? WHERE grn_item_id = ?";
            $this->db->execute_query($sql, [$rejectReason, $grnItemId]);
            
            $response['success'] = true;
            $response['message'] = 'Item rejected successfully';
            
        } catch (\Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $response;
    }
    
}
?>
