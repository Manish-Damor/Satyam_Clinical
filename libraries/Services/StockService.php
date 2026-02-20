<?php
/**
 * StockService - Centralized Stock Management Engine
 * 
 * Handles all inventory movements with ACID guarantees
 * - Prevents concurrent stock issues with row-level locking
 * - Records complete audit trail
 * - Ensures batch expiry validation
 * - Maintains inventory balance integrity
 * 
 * @package Services
 * @version 2.0
 * @date February 2026
 */

namespace Services;

class StockService
{
    private $db;
    private $logger;
    private $user_id;
    private $warehouse_id;

    public function __construct($database, $audit_logger = null, $user_id = null, $warehouse_id = 1)
    {
        $this->db = $database;
        $this->logger = $audit_logger;
        $this->user_id = $user_id ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
        $this->warehouse_id = $warehouse_id;
    }

    /**
     * Increase Stock - Inbound Movement (GRN, Returns, etc)
     * 
     * @param int $product_id Product ID
     * @param int $batch_id Batch ID
     * @param decimal $quantity Quantity to add
     * @param string $reference_type Reference type (GRN, RETURN, etc)
     * @param int $reference_id Reference entity ID
     * @param array $options Additional options
     * @return array Status array with movement_id and new_balance
     * @throws Exception On validation/database failure
     */
    public function increaseStock(
        $product_id,
        $batch_id,
        $quantity,
        $reference_type,
        $reference_id,
        $options = []
    ) {
        try {
            // Start transaction
            $this->db->begin_transaction();

            // Validate inputs
            $this->validateInputs($product_id, $batch_id, $quantity);
            $quantity = floatval($quantity);

            // Fetch batch with lock - prevents concurrent modifications
            $batch = $this->fetchBatchWithLock($batch_id);
            if (!$batch) {
                throw new \Exception("Batch ID {$batch_id} not found");
            }

            // Verify batch belongs to product
            if ($batch['product_id'] != $product_id) {
                throw new \Exception("Batch {$batch_id} does not belong to product {$product_id}");
            }

            // Check batch not expired (if date validation enabled)
            if (isset($options['check_expiry']) && $options['check_expiry']) {
                if (strtotime($batch['exp_date']) < time()) {
                    throw new \Exception("Cannot add to expired batch: {$batch['batch_number']}");
                }
            }

            $old_qty = $batch['current_qty'];
            $new_qty = $old_qty + $quantity;

            // Update batch
            $update_sql = "UPDATE product_batches 
                          SET current_qty = current_qty + ?, 
                              updated_at = NOW(),
                              updated_by = ?
                          WHERE id = ?";
            
            if (!$this->db->execute_query($update_sql, [$quantity, $this->user_id, $batch_id])) {
                throw new \Exception("Failed to update batch quantity");
            }

            // Record stock movement
            $movement = $this->recordMovement(
                $product_id,
                $batch_id,
                'INBOUND',
                $quantity,
                $old_qty,
                $new_qty,
                $reference_type,
                $reference_id
            );

            // Update customer credit balance if payment-related (optional)
            if (isset($options['update_credit']) && $options['update_credit']) {
                $this->updateCustomerCredit($reference_id, -$quantity);
            }

            $this->db->commit();

            return [
                'success' => true,
                'movement_id' => $movement,
                'batch_id' => $batch_id,
                'quantity_added' => $quantity,
                'new_balance' => $new_qty,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logError("Stock increase failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Decrease Stock - Outbound Movement (Sales, Adjustments, etc)
     * 
     * @param int $product_id Product ID
     * @param int $batch_id Batch ID
     * @param decimal $quantity Quantity to remove
     * @param string $reference_type Reference type (SALES_ORDER, DAMAGE, etc)
     * @param int $reference_id Reference entity ID
     * @param array $options Additional options
     * @return array Status array with movement_id and new_balance
     * @throws Exception On validation/database failure
     */
    public function decreaseStock(
        $product_id,
        $batch_id,
        $quantity,
        $reference_type,
        $reference_id,
        $options = []
    ) {
        try {
            $this->db->begin_transaction();

            // Validate inputs
            $this->validateInputs($product_id, $batch_id, $quantity);
            $quantity = floatval($quantity);

            // Fetch batch with lock
            $batch = $this->fetchBatchWithLock($batch_id);
            if (!$batch) {
                throw new \Exception("Batch ID {$batch_id} not found");
            }

            if ($batch['product_id'] != $product_id) {
                throw new \Exception("Batch does not belong to product");
            }

            $old_qty = $batch['current_qty'];

            // Check sufficient quantity
            if ($old_qty < $quantity) {
                throw new \Exception(
                    "Insufficient stock. Available: {$old_qty}, Requested: {$quantity}"
                );
            }

            // Validate batch not expired (critical for sales)
            if ($reference_type == 'SALES_ORDER') {
                if (strtotime($batch['exp_date']) < time()) {
                    throw new \Exception(
                        "Cannot sell from expired batch: {$batch['batch_number']} (Exp: {$batch['exp_date']})"
                    );
                }

                // Warn if < 90 days to expiry
                $days_to_expiry = floor((strtotime($batch['exp_date']) - time()) / 86400);
                if ($days_to_expiry < 90) {
                    error_log("WARNING: Batch {$batch['batch_number']} expires in {$days_to_expiry} days");
                }
            }

            $new_qty = $old_qty - $quantity;

            // Update batch
            $update_sql = "UPDATE product_batches 
                          SET current_qty = current_qty - ?, 
                              updated_at = NOW(),
                              updated_by = ?
                          WHERE id = ?";
            
            if (!$this->db->execute_query($update_sql, [$quantity, $this->user_id, $batch_id])) {
                throw new \Exception("Failed to update batch quantity");
            }

            // Record stock movement
            $movement = $this->recordMovement(
                $product_id,
                $batch_id,
                'OUTBOUND',
                -$quantity, // Negative for outbound
                $old_qty,
                $new_qty,
                $reference_type,
                $reference_id
            );

            // If sales order, map batch to customer
            if ($reference_type == 'SALES_ORDER') {
                $this->mapBatchToSale($batch_id, $reference_id, $quantity);
            }

            $this->db->commit();

            return [
                'success' => true,
                'movement_id' => $movement,
                'batch_id' => $batch_id,
                'quantity_removed' => $quantity,
                'new_balance' => $new_qty,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logError("Stock decrease failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Adjust Stock - For Physical Count Corrections
     * 
     * @param int $product_id Product ID
     * @param int $batch_id Batch ID
     * @param decimal $new_quantity New absolute quantity
     * @param string $reason Reason for adjustment
     * @param string $notes Detailed notes
     * @return array Status array
     * @throws Exception On validation/database failure
     */
    public function adjustStock(
        $product_id,
        $batch_id,
        $new_quantity,
        $reason,
        $notes = ''
    ) {
        try {
            $this->db->begin_transaction();

            $this->validateInputs($product_id, $batch_id, 0);
            $new_quantity = floatval($new_quantity);

            if ($new_quantity < 0) {
                throw new \Exception("Adjusted quantity cannot be negative");
            }

            // Fetch batch with lock
            $batch = $this->fetchBatchWithLock($batch_id);
            if (!$batch) {
                throw new \Exception("Batch ID {$batch_id} not found");
            }

            if ($batch['product_id'] != $product_id) {
                throw new \Exception("Batch does not belong to product");
            }

            $old_qty = $batch['current_qty'];
            $adjustment_qty = $new_quantity - $old_qty;

            // Update batch
            $update_sql = "UPDATE product_batches 
                          SET current_qty = ?, 
                              updated_at = NOW(),
                              updated_by = ?
                          WHERE id = ?";
            
            if (!$this->db->execute_query($update_sql, [$new_quantity, $this->user_id, $batch_id])) {
                throw new \Exception("Failed to update batch quantity");
            }

            // Record movement
            $movement = $this->recordMovement(
                $product_id,
                $batch_id,
                'ADJUSTMENT',
                $adjustment_qty,
                $old_qty,
                $new_quantity,
                'PHYSICAL_COUNT',
                0,
                $notes
            );

            $this->db->commit();

            return [
                'success' => true,
                'movement_id' => $movement,
                'batch_id' => $batch_id,
                'old_quantity' => $old_qty,
                'new_quantity' => $new_quantity,
                'adjustment' => $adjustment_qty,
                'reason' => $reason,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logError("Stock adjustment failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Current Stock by Product
     * 
     * @param int $product_id Product ID
     * @return array Product with batches and total stock
     */
    public function getProductStock($product_id)
    {
        $sql = "SELECT 
                    p.product_id, p.product_name, p.reorder_level,
                    SUM(pb.available_quantity) as total_stock,
                    COUNT(pb.batch_id) as active_batches,
                    MIN(pb.expiry_date) as earliest_expiry
                FROM product p
                LEFT JOIN product_batches pb ON p.product_id = pb.product_id AND pb.status = 'Active'
                WHERE p.product_id = ?
                GROUP BY p.product_id";
        
        $result = $this->db->execute_query($sql, [$product_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get Product Stock Details by Batch
     * 
     * @param int $product_id Product ID
     * @return array Array of batch details
     */
    public function getProductBatches($product_id)
    {
        $sql = "SELECT 
                    pb.batch_id,
                    pb.batch_number,
                    pb.product_id,
                    pb.available_quantity,
                    pb.purchase_rate,
                    pb.manufacturing_date,
                    pb.expiry_date,
                    (pb.available_quantity * pb.purchase_rate) as batch_value,
                    DATEDIFF(pb.expiry_date, CURDATE()) as days_to_expiry,
                    CASE 
                        WHEN pb.expiry_date < CURDATE() THEN 'EXPIRED'
                        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 30 THEN 'CRITICAL'
                        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 90 THEN 'WARNING'
                        ELSE 'OK'
                    END as expiry_status
                FROM product_batches pb
                WHERE pb.product_id = ? AND pb.status = 'Active' AND pb.available_quantity > 0
                ORDER BY pb.expiry_date ASC";
        
        $result = $this->db->execute_query($sql, [$product_id]);
        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        return $batches;
    }

    /**
     * Get Stock Movement History
     * 
     * @param int $product_id Product ID
     * @param int|null $batch_id Optional batch filter
     * @param int $limit Limit results
     * @return array Movement history
     */
    public function getMovementHistory($product_id, $batch_id = null, $limit = 100)
    {
        $sql = "SELECT 
                    sm.id,
                    sm.movement_type,
                    sm.quantity_moved,
                    sm.balance_before,
                    sm.balance_after,
                    sm.reference_type,
                    sm.reference_id,
                    u.name as recorded_by_name,
                    sm.recorded_at
                FROM stock_movements sm
                LEFT JOIN users u ON sm.recorded_by = u.id
                WHERE sm.product_id = ?";
        
        $params = [$product_id];
        
        if ($batch_id) {
            $sql .= " AND sm.batch_id = ?";
            $params[] = $batch_id;
        }
        
        $sql .= " ORDER BY sm.recorded_at DESC LIMIT " . intval($limit);
        
        $result = $this->db->execute_query($sql, $params);
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }

    /**
     * Get Low Stock Products
     * 
     * @return array Products below reorder level
     */
    public function getLowStockProducts()
    {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.reorder_level,
                    COALESCE(SUM(pb.available_quantity), 0) as current_stock,
                    (p.reorder_level - COALESCE(SUM(pb.available_quantity), 0)) as shortage_qty
                FROM product p
                LEFT JOIN product_batches pb ON p.product_id = pb.product_id AND pb.status = 'Active'
                WHERE p.status = 1
                GROUP BY p.product_id
                HAVING current_stock <= p.reorder_level
                ORDER BY shortage_qty DESC";
        
        $result = $this->db->execute_query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    /**
     * Get Expiring Soon Batches
     * 
     * @param int $days_threshold Days until expiry
     * @return array Batches expiring soon
     */
    public function getExpiringBatches($days_threshold = 90)
    {
        $sql = "SELECT 
                    pb.id,
                    pb.batch_number,
                    p.product_name,
                    pb.current_qty,
                    pb.exp_date,
                    DATEDIFF(pb.exp_date, CURDATE()) as days_to_expiry,
                    s.name as supplier_name
                FROM product_batches pb
                JOIN product p ON pb.product_id = p.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                WHERE DATEDIFF(pb.exp_date, CURDATE()) BETWEEN 0 AND ?
                  AND pb.current_qty > 0
                  AND pb.deleted_at IS NULL
                ORDER BY pb.exp_date ASC";
        
        $result = $this->db->execute_query($sql, [$days_threshold]);
        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        return $batches;
    }

    /**
     * Get Batch Sales Map (for recalls)
     * 
     * @param int $batch_id Batch ID
     * @return array Customers who purchased this batch
     */
    public function getBatchSalesMap($batch_id)
    {
        $sql = "SELECT 
                    bsm.id,
                    bsm.order_id,
                    bsm.customer_name,
                    bsm.customer_contact,
                    bsm.quantity_sold,
                    bsm.sale_date
                FROM batch_sales_map bsm
                WHERE bsm.batch_id = ?
                ORDER BY bsm.sale_date DESC";
        
        $result = $this->db->execute_query($sql, [$batch_id]);
        $sales = [];
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
        return $sales;
    }

    // ======================== PRIVATE METHODS ========================

    /**
     * Fetch batch with row-level lock for concurrency safety
     */
    private function fetchBatchWithLock($batch_id)
    {
        $sql = "SELECT * FROM product_batches WHERE id = ? FOR UPDATE";
        $result = $this->db->execute_query($sql, [$batch_id]);
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Record stock movement
     */
    private function recordMovement(
        $product_id,
        $batch_id,
        $movement_type,
        $quantity_moved,
        $balance_before,
        $balance_after,
        $reference_type,
        $reference_id,
        $notes = ''
    ) {
        $sql = "INSERT INTO stock_movements 
                (product_id, batch_id, warehouse_id, movement_type, quantity_moved, 
                 balance_before, balance_after, reference_type, reference_id, 
                 recorded_by, recorded_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $product_id,
            $batch_id,
            $this->warehouse_id,
            $movement_type,
            $quantity_moved,
            $balance_before,
            $balance_after,
            $reference_type,
            $reference_id,
            $this->user_id
        ];

        if (!$this->db->execute_query($sql, $params)) {
            throw new \Exception("Failed to record stock movement");
        }

        return $this->db->get_last_insert_id();
    }

    /**
     * Map batch to sales order (for recalls)
     */
    private function mapBatchToSale($batch_id, $order_id, $quantity)
    {
        $sql = "INSERT INTO batch_sales_map 
                (batch_id, order_id, quantity_sold, sale_date)
                VALUES (?, ?, ?, CURDATE())";
        
        return $this->db->execute_query($sql, [$batch_id, $order_id, $quantity]);
    }

    /**
     * Update customer outstanding balance
     */
    private function updateCustomerCredit($customer_id, $amount_change)
    {
        $sql = "UPDATE customers 
                SET outstanding_balance = outstanding_balance + ?
                WHERE id = ?";
        
        return $this->db->execute_query($sql, [$amount_change, $customer_id]);
    }

    /**
     * Validate input parameters
     */
    private function validateInputs($product_id, $batch_id, $quantity)
    {
        if (!$product_id || !is_numeric($product_id)) {
            throw new \Exception("Invalid product ID");
        }

        if (!$batch_id || !is_numeric($batch_id)) {
            throw new \Exception("Invalid batch ID");
        }

        if ($quantity < 0 || !is_numeric($quantity)) {
            throw new \Exception("Invalid quantity");
        }
    }

    /**
     * Log error messages
     */
    private function logError($message)
    {
        error_log("[StockService] " . $message);
        if ($this->logger) {
            $this->logger->logError($message);
        }
    }
}
