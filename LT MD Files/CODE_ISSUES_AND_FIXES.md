# Code Issues & Recommendations: Batch Selection in Sales Orders

**Generated**: February 20, 2026  
**Scope**: SalesOrderController batch handling

---

## ISSUE #1: Missing batch_id in order_item INSERT

### Current Code (WRONG)

**File**: [libraries/Controllers/SalesOrderController.php](libraries/Controllers/SalesOrderController.php#L346-L379)

```php
private function insertOrderItem($orderId, $orderNumber, $item) {

    $sql = "
        INSERT INTO order_item (
            order_id, order_number, product_id,
            productName, quantity, rate, purchase_rate, total,
            added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $quantity = (int)$item['quantity'];
    $rate = (float)$item['rate'];
    $purchaseRate = (float)($item['purchase_rate'] ?? 0);
    $total = $quantity * $rate;

    $params = [
        $orderId,
        $orderNumber,
        (int)$item['product_id'],
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
```

### Problems:

1. ❌ **batch_id NOT in INSERT columns**
2. ❌ **batch_id NOT in VALUES** parameters
3. ❌ **batch_id NOT being passed** at calling location (line 120)
4. ❌ **No batch validation** before insert
5. ❌ **No expiry check** before sale
6. ❌ **No stock validation** before sale

### Impact:

- ⚠️ **Every order item created has NULL batch_id**
- ⚠️ **Cannot trace which batch was sold**
- ⚠️ **Cannot validate batch expiry**
- ⚠️ **Cannot perform batch recall queries**
- ⚠️ **Violates database design** that has batch_id FK

---

## ISSUE #2: Batch Validation Not Implemented

### Current Code (Line 115-127)

```php
// Check stock availability
$stockCheck = $this->stockService->getStockStatus($item['product_id']);

if ($stockCheck['available'] < $item['quantity']) {
    throw new \Exception("Insufficient stock for product {$item['product_id']}. Available: {$stockCheck['available']}, Requested: {$item['quantity']}");
}

// Insert order item
$this->insertOrderItem($orderId, $orderNumber, $item);

// Deduct stock through StockService
$deductResult = $this->stockService->decreaseStock(
    $item['product_id'],
    $item['quantity'],
    'sales_order',
    $orderId,
    $this->userId
);
```

### Problems:

1. ❌ **Checks PRODUCT-level stock** (all batches combined)
2. ❌ **NO batch-level stock check** (which batch will be used?)
3. ❌ **NO expiry validation** (sold expired batch to customer!)
4. ❌ **NO batch_id in item array** (required to check specific batch)
5. ❌ **Deducts from unspecified batch** (shouldn't work this way)

---

## ISSUE #3: No batch_sales_map Entry Created

### Expected but Missing:

When order_item is created with batch_id, should also create entry in batch_sales_map:

```sql
INSERT INTO batch_sales_map (
    batch_id, order_id, order_item_id,
    quantity_sold, sale_date, customer_id, customer_name, customer_contact
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
```

### Current Code:

**Does NOT insert into batch_sales_map**

### Impact:

- ❌ Cannot query: "Which customers bought batch XYZ?"
- ❌ Cannot perform batch recalls
- ❌ Violates migration 005 intent

---

## RECOMMENDED FIX #1: Proper Item Structure

### What Client Should Send:

```json
{
  "items": [
    {
      "product_id": 1,
      "batch_id": 5,
      "quantity": 10,
      "rate": 100.0
    },
    {
      "product_id": 2,
      "batch_id": 8,
      "quantity": 5,
      "rate": 200.0
    }
  ]
}
```

**KEY ADDITION**: Each item must include `batch_id`

---

## RECOMMENDED FIX #2: Batch Validation Function

Add this method to SalesOrderController:

```php
/**
 * Validate batch is suitable for sale
 * @param int $batch_id Batch to check
 * @param int $product_id Expected product
 * @param int $quantity Requested quantity
 * @return array Batch details or throw exception
 */
private function validateBatchForSale($batch_id, $product_id, $quantity) {

    // 1. Check batch exists and belongs to product
    $sql = "
        SELECT batch_id, product_id, batch_number, expiry_date,
               available_quantity, purchase_rate, mrp, status
        FROM product_batches
        WHERE batch_id = ? AND product_id = ?
    ";

    $result = $this->db->execute_query($sql, [$batch_id, $product_id]);

    if (!$result || $result->num_rows === 0) {
        throw new \Exception("Batch #{$batch_id} not found for product #{$product_id}");
    }

    $batch = $result->fetch_assoc();

    // 2. Check NOT EXPIRED
    $expiry_ts = strtotime($batch['expiry_date']);
    $today_ts = strtotime(date('Y-m-d'));

    if ($expiry_ts < $today_ts) {
        throw new \Exception(
            "Batch {$batch['batch_number']} expired on {$batch['expiry_date']}. " .
            "Cannot sell expired batch."
        );
    }

    // 3. Check EXPIRING SOON (warning)
    $days_remaining = intval(($expiry_ts - $today_ts) / 86400);
    if ($days_remaining < 30) {
        // Log warning but allow
        error_log("WARNING: Batch {$batch['batch_number']} expires in {$days_remaining} days");
    }

    // 4. Check ACTIVE status
    if ($batch['status'] !== 'Active') {
        throw new \Exception(
            "Batch {$batch['batch_number']} status is {$batch['status']}. " .
            "Cannot sell non-active batches."
        );
    }

    // 5. Check SUFFICIENT STOCK
    if ($batch['available_quantity'] < $quantity) {
        throw new \Exception(
            "Batch {$batch['batch_number']} does not have sufficient stock. " .
            "Available: {$batch['available_quantity']}, Requested: {$quantity}"
        );
    }

    return $batch;
}
```

---

## RECOMMENDED FIX #3: Updated insertOrderItem

```php
private function insertOrderItem($orderId, $orderNumber, $item, $batchId) {

    $sql = "
        INSERT INTO order_item (
            order_id, product_id, batch_id,
            quantity, rate, total,
            added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $quantity = (int)$item['quantity'];
    $rate = (float)$item['rate'];
    $total = $quantity * $rate;

    $params = [
        $orderId,
        (int)$item['product_id'],
        (int)$batchId,              // ✅ ADD batch_id
        $quantity,
        $rate,
        $total,
        date('Y-m-d')
    ];

    $result = $this->db->execute_query($sql, $params);

    if (!$result || $result === false) {
        throw new \Exception('Failed to insert order item: ' . $this->db->get_last_error());
    }

    return $this->db->get_last_insert_id();
}
```

---

## RECOMMENDED FIX #4: Updated createSalesOrder Flow

Replace lines 115-130 with:

```php
// ========================================
// 6. INSERT ORDER ITEMS & DEDUCT STOCK
// ========================================
$itemCount = 0;
foreach ($items as $item) {
    if (empty($item['product_id']) || empty($item['batch_id']) || empty($item['quantity'])) {
        throw new \Exception('Each item must have product_id, batch_id, and quantity');
    }

    // ✅ VALIDATE BATCH (expiry, stock, status)
    $batch = $this->validateBatchForSale(
        $item['batch_id'],
        $item['product_id'],
        $item['quantity']
    );

    // ✅ INSERT ORDER ITEM WITH batch_id
    $orderItemId = $this->insertOrderItem($orderId, $orderNumber, $item, $item['batch_id']);

    // ✅ DEDUCT STOCK FROM SPECIFIC BATCH
    $deductResult = $this->stockService->decreaseStock(
        $item['product_id'],
        $item['batch_id'],
        $item['quantity'],
        'sales_order',
        $orderId,
        $this->userId
    );

    if (!$deductResult) {
        throw new \Exception("Failed to deduct stock for batch {$batch['batch_number']}");
    }

    // ✅ CREATE BATCH SALES MAP ENTRY (for recall queries)
    $this->mapBatchToSale(
        $item['batch_id'],
        $orderId,
        $orderItemId,
        $item['quantity'],
        $customerId
    );

    $itemCount++;
}

if ($itemCount === 0) {
    throw new \Exception('No valid items were added to the order');
}
```

---

## RECOMMENDED FIX #5: New mapBatchToSale Function

```php
/**
 * Map batch to sales order for recall tracking
 */
private function mapBatchToSale($batchId, $orderId, $orderItemId, $quantitySold, $customerId) {

    // Get batch and order details
    $batchSql = "SELECT batch_number, product_id FROM product_batches WHERE batch_id = ?";
    $batchRes = $this->db->execute_query($batchSql, [$batchId]);
    $batchData = $batchRes->fetch_assoc();

    $orderSql = "SELECT order_number, orderDate, clientName, clientContact
                 FROM orders WHERE id = ?";
    $orderRes = $this->db->execute_query($orderSql, [$orderId]);
    $orderData = $orderRes->fetch_assoc();

    // Insert into batch_sales_map
    $mapSql = "
        INSERT INTO batch_sales_map (
            batch_id, order_id, order_item_id,
            quantity_sold, sale_date, customer_id,
            customer_name, customer_contact, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $params = [
        $batchId,
        $orderId,
        $orderItemId,
        $quantitySold,
        $orderData['orderDate'],
        $customerId,
        $orderData['clientName'],
        $orderData['clientContact']
    ];

    $result = $this->db->execute_query($mapSql, $params);

    if (!$result) {
        error_log("WARNING: Failed to create batch_sales_map entry for batch {$batchData['batch_number']}");
    }
}
```

---

## RECOMMENDED FIX #6: Update Stock Deduction

If StockService.decreaseStock() is called, update it to accept batch_id:

```php
/**
 * Decrease stock from specific batch
 * @param int $product_id Product being sold
 * @param int $batch_id Specific batch to deduct from
 * @param int $quantity Amount to deduct
 */
public function decreaseStock($product_id, $batch_id, $quantity,
                             $reference_type, $reference_id, $user_id) {

    // Update product_batches available_quantity
    $sql = "
        UPDATE product_batches
        SET available_quantity = available_quantity - ?
        WHERE batch_id = ? AND product_id = ?
            AND available_quantity >= ?
    ";

    $result = $this->db->execute_query($sql, [$quantity, $batch_id, $product_id, $quantity]);

    if (!$result || $this->db->get_affected_rows() === 0) {
        return [
            'success' => false,
            'message' => "Could not deduct {$quantity} from batch #{$batch_id}"
        ];
    }

    // Create stock movement record
    $moveSql = "
        INSERT INTO stock_movements (
            product_id, batch_id, movement_type, quantity,
            reference_type, reference_id, created_by, movement_date
        ) VALUES (?, ?, 'Sales', ?, ?, ?, ?, NOW())
    ";

    $this->db->execute_query($moveSql, [
        $product_id,
        $batch_id,
        -$quantity,
        $reference_type,
        $reference_id,
        $user_id
    ]);

    return ['success' => true];
}
```

---

## TESTING CHECKLIST

After implementing fixes:

- [ ] Order item INSERT includes batch_id column
- [ ] batch_id parameter passed from controller
- [ ] Batch expiry validation prevents expired sales
- [ ] Batch stock validation prevents oversale
- [ ] Batch status checked (Active only)
- [ ] Stock deducted from correct batch
- [ ] batch_sales_map updated for each sale
- [ ] Query "SELECT \* FROM order_item WHERE batch_id = ?" returns results
- [ ] Query batch_sales_map shows which customers got which batch
- [ ] Old orders have NULL batch_id (data migration needed)

---

## DATABASE MIGRATION SCRIPT (Optional)

If you want to backfill batch_id for existing orders:

```sql
-- ⚠️ CAUTION: Only run if you have existing orders!

-- Strategy: Assign batches by FIFO (oldest batches first)
UPDATE order_item oi
JOIN (
    SELECT oi2.id,
           (SELECT batch_id FROM product_batches pb
            WHERE pb.product_id = oi2.product_id
            ORDER BY pb.expiry_date ASC
            LIMIT 1) AS suggested_batch_id
    FROM order_item oi2
    WHERE oi2.batch_id IS NULL
    LIMIT 100
) suggested ON oi.id = suggested.id
SET oi.batch_id = suggested.suggested_batch_id;
```

**IMPORTANT**: This is just a best-effort guess. Review before running in production!

---

## SUMMARY OF CHANGES

| Item                    | Current               | Recommended               | Impact                |
| ----------------------- | --------------------- | ------------------------- | --------------------- |
| batch_id in order_item  | ❌ NOT inserted       | ✅ REQUIRED               | Batch traceability    |
| Batch expiry validation | ❌ None               | ✅ validateBatchForSale() | Prevent expired sales |
| Batch stock check       | ❌ Product-level only | ✅ Batch-level check      | Inventory accuracy    |
| batch_sales_map entry   | ❌ Never created      | ✅ Created per item       | Enable recalls        |
| Item validation         | ⚠️ Minimal            | ✅ Comprehensive          | Data quality          |
| Stock deduction         | ⚠️ Generic            | ✅ Batch-specific         | Accurate tracking     |

---

## FILES TO MODIFY

1. **[libraries/Controllers/SalesOrderController.php](libraries/Controllers/SalesOrderController.php)**
   - Add validateBatchForSale() method
   - Add mapBatchToSale() method
   - Update createSalesOrder() to use batch validation
   - Update insertOrderItem() to accept and insert batch_id
2. **[libraries/Services/StockService.php](libraries/Services/StockService.php)**
   - Update decreaseStock() to accept batch_id parameter
   - Update increase stock similarly

3. **[php_action/examples/SalesOrderController.php](php_action/examples/SalesOrderController.php)**
   - Keep as reference implementation
   - Consider adopting patterns shown

---

## REFERENCE: CORRECT EXAMPLE

See working implementation at:
[php_action/examples/SalesOrderController.php](php_action/examples/SalesOrderController.php#L98-L132)

This example shows:

- ✅ Accepting batch_id from client
- ✅ Validating batch expiry
- ✅ Checking batch stock
- ✅ Preparing batch_id for insert
- ✅ Using batch details in order
