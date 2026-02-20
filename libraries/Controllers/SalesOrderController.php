<?php
namespace Controllers;

use Services\StockService;
use Services\CreditControl;
use Services\AuditLogger;
use Services\ApprovalEngine;
use Helpers\DatabaseHelper;

class SalesOrderController {
    
    private $db;
    private $stockService;
    private $creditControl;
    private $auditLogger;
    private $approvalEngine;
    private $userId;
    private $userRole;
    
    public function __construct($connect, $userId = 0, $userRole = 'user') {
        $this->db = new DatabaseHelper($connect);
        $this->stockService = new StockService($connect, null, $userId);
        $this->auditLogger = new AuditLogger($connect, $userId);
        $this->creditControl = new CreditControl($connect, $userId, $this->auditLogger);
        $this->approvalEngine = new ApprovalEngine($connect, $userId, $userRole, $this->auditLogger);
        $this->userId = $userId;
        $this->userRole = $userRole;
    }
    
    /**
     * Create a new Sales Order with credit control and stock validation
     * Integrates CreditControl service for customer credit eligibility
     * Uses StockService for inventory management
     * 
     * @param array $orderData Order header data (clientName, clientContact, totalAmount, etc)
     * @param array $items Item array with product_id, quantity, rate, etc
     * @return array Success/Error response with order_id and credit_warning on success
     */
    public function createSalesOrder($orderData, $items) {
        
        $response = [
            'success' => false,
            'order_id' => null,
            'order_number' => null,
            'message' => '',
            'errors' => [],
            'warnings' => [],
            'credit_analysis' => []
        ];
        
        try {
            // ========================================
            // 1. VALIDATE INPUT DATA
            // ========================================
            $validation = $this->validateOrderData($orderData, $items);
            if (!$validation['valid']) {
                $response['errors'] = $validation['errors'];
                $response['message'] = 'Validation failed: ' . implode(', ', $validation['errors']);
                return $response;
            }
            
            // ========================================
            // 2. GET OR CREATE CUSTOMER (if needed)
            // ========================================
            $customerId = $this->getOrCreateCustomer($orderData);
            
            // ========================================
            // 3. CHECK CUSTOMER CREDIT ELIGIBILITY
            // ========================================
            $creditAnalysis = $this->creditControl->checkCreditEligibility(
                $customerId,
                (float)$orderData['grandTotalValue']
            );
            
            $response['credit_analysis'] = $creditAnalysis;
            
            if (!$creditAnalysis['eligible']) {
                $response['warnings'][] = 'Customer credit limit exceeded: ' . $creditAnalysis['reason'];
                
                // Allow order creation but flag it for review
                $orderData['requires_approval'] = 1;
                $response['warnings'][] = 'Order will require manager approval';
            }
            
            // ========================================
            // 4. START TRANSACTION
            // ========================================
            $this->db->begin_transaction();
            
            // ========================================
            // 5. INSERT SALES ORDER
            // ========================================
            $orderId = $this->insertSalesOrder($orderData, $customerId);
            
            if (!$orderId) {
                throw new \Exception('Failed to create sales order');
            }
            
            // Get the order number
            $orderNumber = $this->getOrderNumber($orderId);
            $response['order_number'] = $orderNumber;
            
            // ========================================
            // 6. INSERT ORDER ITEMS & DEDUCT STOCK
            // ========================================
            $itemCount = 0;
            foreach ($items as $item) {
                if (empty($item['product_id']) || empty($item['quantity']) || empty($item['batch_id'])) {
                    continue;
                }
                
                $batchId = (int)$item['batch_id'];
                $quantity = (int)$item['quantity'];
                $productId = (int)$item['product_id'];
                
                // Check total product stock availability
                $stockCheck = $this->stockService->getStockStatus($productId);
                
                if ($stockCheck['available'] < $quantity) {
                    throw new \Exception("Insufficient stock for product {$productId}. Available: {$stockCheck['available']}, Requested: {$quantity}");
                }
                
                // Insert order item (with batch_id)
                $this->insertOrderItem($orderId, $orderNumber, $item);
                
                // Deduct stock from SPECIFIC BATCH - StockService validates expiry
                // and prevents selling from expired batches
                $deductResult = $this->stockService->decreaseStock(
                    $productId,
                    $batchId,
                    $quantity,
                    'SALES_ORDER',  // Reference type used for expiry validation
                    $orderId,
                    []  // options array
                );
                
                if (!$deductResult) {
                    throw new \Exception("Failed to deduct stock for product {$item['product_id']}");
                }
                
                $itemCount++;
            }
            
            if ($itemCount === 0) {
                throw new \Exception('No valid items were added to the order');
            }
            
            // ========================================
            // 7. RECORD CUSTOMER PAYMENT & CREDIT
            // ========================================
            if ((float)$orderData['paid'] > 0) {
                $this->creditControl->recordPayment(
                    $customerId,
                    (float)$orderData['paid'],
                    $orderData['paymentType'] ?? 'manual',
                    'Order payment: ' . $orderNumber
                );
            }
            
            // Update customer credit
            $dueAmount = (float)$orderData['dueValue'];
            if ($dueAmount > 0) {
                $this->creditControl->updateCustomerCredit(
                    $customerId,
                    $dueAmount,
                    'sales_order',
                    $orderId
                );
            }
            
            // ========================================
            // 8. LOG AUDIT TRAIL
            // ========================================
            $this->auditLogger->logChange(
                'orders',
                $orderId,
                'INSERT',
                null,
                $orderData,
                'SalesOrderController::createSalesOrder',
                $this->userId
            );
            
            // ========================================
            // 9. COMMIT TRANSACTION
            // ========================================
            $this->db->commit();
            
            $response['success'] = true;
            $response['order_id'] = $orderId;
            $response['message'] = "Sales Order {$orderNumber} created successfully with $itemCount items";
            
        } catch (\Exception $e) {
            
            // Rollback on any error
            $this->db->rollback();
            
            $response['success'] = false;
            $response['message'] = 'Error: ' . $e->getMessage();
            
            // Log the error
            $this->auditLogger->logChange(
                'orders',
                null,
                'INSERT',
                null,
                $orderData ?? [],
                'SalesOrderController::createSalesOrder [ERROR]',
                $this->userId,
                ['error' => $e->getMessage()]
            );
        }
        
        return $response;
    }
    
    /**
     * Validate sales order data
     */
    private function validateOrderData($orderData, $items) {
        $validation = ['valid' => true, 'errors' => []];
        
        // Check required fields
        if (empty($orderData['clientName']) || strlen($orderData['clientName']) < 2) {
            $validation['errors'][] = 'Valid client name is required';
        }
        
        if (empty($orderData['clientContact']) || !preg_match('/^[0-9]{10}$/', $orderData['clientContact'])) {
            $validation['errors'][] = 'Valid 10-digit contact number is required';
        }
        
        if (empty($orderData['orderDate'])) {
            $validation['errors'][] = 'Order date is required';
        }
        
        if (empty($items) || !is_array($items)) {
            $validation['errors'][] = 'At least one item is required';
        }
        
        // Check totals make sense
        $grandTotal = (float)($orderData['grandTotalValue'] ?? 0);
        if ($grandTotal <= 0) {
            $validation['errors'][] = 'Grand total must be greater than zero';
        }
        
        $validation['valid'] = count($validation['errors']) === 0;
        return $validation;
    }
    
    /**
     * Get existing customer ID or create new one
     */
    private function getOrCreateCustomer($orderData) {
        
        // Try to find customer by contact
        $customerQuery = $this->db->execute_query(
            "SELECT customer_id FROM customers WHERE contact_number = ? LIMIT 1",
            [$orderData['clientContact']]
        );
        
        if ($customerQuery && $customerQuery->num_rows > 0) {
            $row = $customerQuery->fetch_assoc();
            return $row['customer_id'];
        }
        
        // Create new customer
        $sql = "
            INSERT INTO customers (
                customer_name, contact_number, email,
                address, city, state, pincode,
                gst_number, credit_limit, created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $orderData['clientName'],
            $orderData['clientContact'],
            $orderData['email'] ?? '',
            $orderData['address'] ?? '',
            $orderData['city'] ?? '',
            $orderData['state'] ?? '',
            $orderData['pincode'] ?? '',
            $orderData['gstn'] ?? '',
            5000, // Default credit limit
            $this->userId,
            date('Y-m-d H:i:s')
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if ($result && $result !== false) {
            return $this->db->get_last_insert_id();
        }
        
        throw new \Exception('Failed to create customer record');
    }
    
    /**
     * Insert sales order master record
     */
    private function insertSalesOrder($orderData, $customerId) {
        
        $sql = "
            INSERT INTO orders (
                uno, orderDate, clientName, gstPercents, gstn,
                clientContact, subTotal, totalAmount, discount,
                grandTotalValue, paid, dueValue, paymentType,
                paymentStatus, paymentPlace, customer_id, created_by,
                requires_approval
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";
        
        // Generate order number if not provided
        $orderNumber = $orderData['uno'] ?? $this->generateOrderNumber();
        
        $params = [
            $orderNumber,
            $orderData['orderDate'] ?? date('Y-m-d'),
            $orderData['clientName'],
            (float)($orderData['gstPercentage'] ?? 0),
            $orderData['gstn'] ?? '',
            $orderData['clientContact'],
            (float)($orderData['subTotalValue'] ?? 0),
            (float)($orderData['totalAmountValue'] ?? 0),
            (float)($orderData['discount'] ?? 0),
            (float)($orderData['grandTotalValue'] ?? 0),
            (float)($orderData['paid'] ?? 0),
            (float)($orderData['dueValue'] ?? 0),
            $orderData['paymentType'] ?? 'cash',
            $orderData['paymentStatus'] ?? 'pending',
            $orderData['paymentPlace'] ?? 'counter',
            $customerId,
            $this->userId,
            $orderData['requires_approval'] ? 1 : 0
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if ($result && $result !== false) {
            return $this->db->get_last_insert_id();
        }
        
        throw new \Exception('Failed to insert sales order: ' . $this->db->get_last_error());
    }
    
    /**
     * Insert order item
     */
    private function insertOrderItem($orderId, $orderNumber, $item) {
        
        $sql = "
            INSERT INTO order_item (
                order_id, order_number, product_id, batch_id,
                productName, quantity, rate, purchase_rate, total,
                added_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $quantity = (int)$item['quantity'];
        $rate = (float)$item['rate'];
        $purchaseRate = (float)($item['purchase_rate'] ?? 0);
        $batchId = (int)($item['batch_id'] ?? 0);
        $total = $quantity * $rate;
        
        $params = [
            $orderId,
            $orderNumber,
            (int)$item['product_id'],
            $batchId,  // Include batch_id
            $item['productName'] ?? '',
            $quantity,
            $rate,
            $purchaseRate,  // Store the PTR
            $total,
            date('Y-m-d')
        ];
        
        $result = $this->db->execute_query($sql, $params);
        
        if (!$result || $result === false) {
            throw new \Exception('Failed to insert order item: ' . $this->db->get_last_error());
        }
    }
    
    /**
     * Generate order number
     */
    private function generateOrderNumber() {
        $year = date('y');
        $month = date('m');
        
        $result = $this->db->execute_query(
            "SELECT MAX(CAST(SUBSTRING(uno, -4) AS UNSIGNED)) as maxOrder FROM orders WHERE YEAR(orderDate) = YEAR(NOW())",
            []
        );
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nextNum = ($row['maxOrder'] ?? 0) + 1;
        } else {
            $nextNum = 1;
        }
        
        return 'ORD-' . $year . $month . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get order number from order ID
     */
    private function getOrderNumber($orderId) {
        $result = $this->db->execute_query(
            "SELECT uno FROM orders WHERE order_id = ? LIMIT 1",
            [$orderId]
        );
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['uno'];
        }
        
        return 'UNK-' . $orderId;
    }
    
}
?>
