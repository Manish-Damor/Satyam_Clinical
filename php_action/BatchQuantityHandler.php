<?php
/**
 * SALES INVOICE BATCH QUANTITY HANDLER
 * Handles multi-batch allocation when single batch qty is insufficient
 * - Suggests additional batches to fulfill order
 * - Validates total available quantity
 * - Creates batch allocation plan
 * - Generates user alerts for insufficient stock scenarios
 */

class BatchQuantityHandler {
    private $conn;
    private $product_id;
    private $required_quantity;
    private $selected_batches = [];
    private $allocation_plan = [];
    private $warnings = [];
    private $insufficient = false;

    public function __construct($conn, $product_id, $required_quantity) {
        $this->conn = $conn;
        $this->product_id = intval($product_id);
        $this->required_quantity = floatval($required_quantity);
    }

    /**
     * Get all available batches for a product, sorted by expiry (FIFO)
     */
    public function getAvailableBatches() {
        $sql = "
            SELECT 
                batch_id,
                batch_number,
                available_quantity,
                expiry_date,
                purchase_rate,
                mrp,
                supplier_id,
                DATEDIFF(expiry_date, CURDATE()) as days_to_expiry,
                CASE 
                    WHEN DATEDIFF(expiry_date, CURDATE()) < 30 THEN 'expiring_soon'
                    WHEN DATEDIFF(expiry_date, CURDATE()) < 0 THEN 'expired'
                    ELSE 'ok'
                END as expiry_status
            FROM product_batches
            WHERE product_id = ?
            AND available_quantity > 0
            AND LOWER(status) = 'active'
            AND expiry_date > CURDATE()
            ORDER BY expiry_date ASC, available_quantity DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $this->product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        $stmt->close();

        return $batches;
    }

    /**
     * Check if required quantity can be fulfilled from available batches
     */
    public function canFulfill() {
        $batches = $this->getAvailableBatches();
        $total_available = array_sum(array_column($batches, 'available_quantity'));

        if ($total_available < $this->required_quantity) {
            $this->insufficient = true;
            $this->warnings[] = [
                'type' => 'INSUFFICIENT_STOCK',
                'message' => "Required: {$this->required_quantity} units | Available: {$total_available} units",
                'shortfall' => $this->required_quantity - $total_available
            ];
            return false;
        }

        return true;
    }

    /**
     * Generate automatic allocation plan for multi-batch fulfillment
     * Uses FIFO principle (earliest expiry first)
     */
    public function generateAllocationPlan() {
        $batches = $this->getAvailableBatches();
        $remaining_qty = $this->required_quantity;
        $allocation = [];
        $expiry_warning = false;

        foreach ($batches as $batch) {
            if ($remaining_qty <= 0) break;

            $allocate_qty = min($remaining_qty, $batch['available_quantity']);
            
            $allocation[] = [
                'batch_id' => $batch['batch_id'],
                'batch_number' => $batch['batch_number'],
                'allocated_quantity' => $allocate_qty,
                'available_quantity' => $batch['available_quantity'],
                'expiry_date' => $batch['expiry_date'],
                'days_to_expiry' => $batch['days_to_expiry'],
                'expiry_status' => $batch['expiry_status'],
                'mrp' => $batch['mrp'],
                'purchase_rate' => $batch['purchase_rate']
            ];

            if ($batch['expiry_status'] === 'expiring_soon') {
                $expiry_warning = true;
            }

            $remaining_qty -= $allocate_qty;
        }

        // Check if we can fulfill
        if ($remaining_qty > 0) {
            $this->insufficient = true;
            $this->warnings[] = [
                'type' => 'INSUFFICIENT_STOCK',
                'message' => "Cannot allocate full quantity. Shortfall: {$remaining_qty} units",
                'shortfall' => $remaining_qty
            ];
        }

        if ($expiry_warning) {
            $this->warnings[] = [
                'type' => 'EXPIRY_WARNING',
                'message' => "One or more allocated batches are expiring within 30 days. Review allocation."
            ];
        }

        $this->allocation_plan = $allocation;
        return $allocation;
    }

    /**
     * Get warnings/alerts
     */
    public function getWarnings() {
        return $this->warnings;
    }

    /**
     * Check if allocation is insufficient
     */
    public function isInsufficient() {
        return $this->insufficient;
    }

    /**
     * Get allocation summary for display
     */
    public function getAllocationSummary() {
        $total_allocated = array_sum(array_column($this->allocation_plan, 'allocated_quantity'));
        $batch_count = count($this->allocation_plan);

        return [
            'required_quantity' => $this->required_quantity,
            'total_allocated' => $total_allocated,
            'batch_count' => $batch_count,
            'is_complete' => $total_allocated == $this->required_quantity,
            'allocation_plan' => $this->allocation_plan,
            'warnings' => $this->warnings
        ];
    }
}

?>
