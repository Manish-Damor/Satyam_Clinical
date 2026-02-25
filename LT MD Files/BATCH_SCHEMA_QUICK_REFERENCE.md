# Quick Reference: Key Schema Details

## UNIQUE CONSTRAINTS ON INVOICE NUMBERS

### Sales Orders (orders table)

- **Column**: `order_number`
- **Type**: UNIQUE KEY
- **Constraint Name**: `uk_order_number`
- **Scope**: Single column (varchar(50))
- **Format**: Typically populated with invoice numbers like "INV-001"

### Purchase Invoices (purchase_invoices table)

- **Columns**: `supplier_id` + `invoice_no` (COMPOSITE)
- **Type**: UNIQUE KEY
- **Constraint Name**: `uq_supplier_invoice`
- **Scope**: Combination of supplier and invoice number
- **Constraint Definition**:
  ```sql
  UNIQUE KEY `uq_supplier_invoice` (`supplier_id`,`invoice_no`)
  ```

### Purchase Orders (purchase_orders table)

- **Column**: `po_number`
- **Type**: UNIQUE KEY
- **Constraint Name**: `uk_po_number`

---

## ORDER_ITEM TABLE - BATCH COLUMNS

```
Column Name      | Type              | Has Data? | Notes
-----------------+-------------------+-----------+---
batch_id         | INT UNSIGNED      | ✅ YES    | Foreign key to product_batches.batch_id
expiry_date      | (NOT IN TABLE)    | ❌ NO     | Must JOIN to product_batches to get
batch_number     | (NOT IN TABLE)    | ❌ NO     | Must JOIN to product_batches to get
```

### Columns that ARE in order_item:

- `id`, `order_id`, `product_id`, `batch_id`, `quantity`, `rate`, `total`, `order_item_state`, `added_date`

### To Get Expiry Date for Order Item:

```sql
SELECT pb.expiry_date
FROM order_item oi
LEFT JOIN product_batches pb ON oi.batch_id = pb.batch_id
WHERE oi.id = ?;
```

---

## ORDERS TABLE - COLUMNS

```
Column Name         | Type                        | Notes
--------------------+-----------------------------+---
id                  | INT UNSIGNED (PK)           | Auto increment
order_number        | VARCHAR(50) UNIQUE          | **UNIQUE - Invoice Number**
orderDate           | DATE                        | Order date
clientName          | VARCHAR(255)                | Customer name
projectName         | VARCHAR(100)                | Project reference
clientContact       | VARCHAR(20)                 | Customer phone
address             | TEXT                        | Customer address
subTotal            | DECIMAL(12,2)               | Total before discount/tax
discount            | DECIMAL(10,2)               | Discount amount
discountPercent     | DECIMAL(5,2)                | Discount percentage
gstPercent          | INT                         | GST percentage (default 18)
gstn                | DECIMAL(12,2)               | GST amount
grandTotalValue     | DECIMAL(12,2)               | Final total
paid                | DECIMAL(12,2)               | Amount paid
dueValue            | DECIMAL(12,2)               | Outstanding amount
paymentType         | VARCHAR(50)                 | Payment method
paymentStatus       | ENUM (4 values)             | Pending, PartialPaid, Paid, Cancelled
paymentPlace        | VARCHAR(100)                | Where payment made
delete_status       | TINYINT(1)                  | Soft delete flag
created_by          | INT UNSIGNED                | User who created
created_at          | TIMESTAMP                   | Creation time
updated_at          | TIMESTAMP                   | Last update time
```

---

## PRODUCT_BATCHES TABLE - COLUMNS

```
Column Name         | Type                        | Notes
--------------------+-----------------------------+---
batch_id            | INT UNSIGNED (PK)           | Auto increment
product_id          | INT UNSIGNED (FK)           | Product reference
supplier_id         | INT UNSIGNED (FK)           | Supplier reference
batch_number        | VARCHAR(50) UNIQUE          | Batch identifier
manufacturing_date  | DATE                        | Mfg date
expiry_date         | DATE NOT NULL               | **EXPIRY DATE - USE FOR VALIDATION**
available_quantity  | INT UNSIGNED                | Stock available
reserved_quantity   | INT UNSIGNED                | Stock reserved
damaged_quantity    | INT UNSIGNED                | Damaged stock
purchase_rate       | DECIMAL(10,2)               | Cost price
mrp                 | DECIMAL(10,2)               | Selling price
status              | ENUM (4 values)             | Active, Expired, Blocked, Damaged
created_at          | TIMESTAMP                   | Creation time
updated_at          | TIMESTAMP                   | Last update time
```

### Composite UNIQUE Key:

```sql
UNIQUE KEY unique_batch (product_id, batch_number)
```

Meaning: Cannot have duplicate batch_number for same product

---

## BATCH SELECTION LOGIC - WHAT'S MISSING

### Current Production Code (WRONG):

File: `libraries/Controllers/SalesOrderController.php` Line 346-379

```php
INSERT INTO order_item (
    order_id, order_number, product_id,
    productName, quantity, rate, purchase_rate, total,
    added_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
// ❌ MISSING batch_id parameter
```

### What Should Be Done:

1. ✅ Accept `batch_id` in item array
2. ✅ Validate batch exists for product
3. ✅ Validate batch NOT EXPIRED: `expiry_date > TODAY`
4. ✅ Validate sufficient stock: `available_quantity >= requested`
5. ✅ INSERT with batch_id
6. ✅ Update batch available_quantity
7. ✅ Create entry in batch_sales_map for recall tracking

### Example Correct Validation (from examples):

```php
$batch = $this->getBatchDetails($batch_id, $product_id);

// Check NOT EXPIRED
if (strtotime($batch['expiry_date']) < time()) {
    throw new \Exception(
        "Batch {$batch['batch_number']} expired on {$batch['expiry_date']}"
    );
}

// Check STOCK
if ($batch['available_qty'] < $quantity) {
    throw new \Exception(
        "Insufficient stock. Available: {$batch['available_qty']}, Requested: {$quantity}"
    );
}

// INSERT order_item WITH batch_id
INSERT INTO order_item (
    order_id, product_id, batch_id, quantity, rate, total, added_date
) VALUES (?, ?, ?, ?, ?, ?, ?)
```

---

## BATCH_SALES_MAP TABLE (Migration 005)

**Purpose**: Quick batch recall queries - "Which customers bought batch XYZ?"

```sql
CREATE TABLE batch_sales_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    order_item_id INT UNSIGNED,
    quantity_sold DECIMAL(10, 2) NOT NULL,
    sale_date DATE NOT NULL,
    customer_id INT UNSIGNED,
    customer_name VARCHAR(255),
    customer_contact VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

**Should be populated** whenever order_item is created with batch_id

---

## FOREIGN KEY RELATIONSHIPS

```
order_item.batch_id
    └─> product_batches.batch_id
        └─ ON DELETE SET NULL (batch deleted, order_item.batch_id becomes NULL)

order_item.product_id
    └─> product.product_id
        └─ ON DELETE RESTRICT (cannot delete product with order items)

order_item.order_id
    └─> orders.id
        └─ ON DELETE CASCADE (order deleted, all items deleted)
```

---

## WHAT COLUMNS EXIST FOR BATCH/EXPIRY/INVOICE TRACKING

### In order_item:

- ✅ batch_id (FK to product_batches)
- ❌ batch_number (not stored, get from batch_id JOIN)
- ❌ expiry_date (not stored, get from batch_id JOIN)
- ❌ batch_status (not stored, get from batch_id JOIN)

### In product_batches (via JOIN):

- ✅ batch_number
- ✅ expiry_date ⭐ **PRIMARY FOR EXPIRY VALIDATION**
- ✅ available_quantity (for stock check)
- ✅ status (Active/Expired/Blocked/Damaged)
- ✅ manufacturing_date

### In orders:

- ✅ order_number ⭐ **UNIQUE - Invoice Number**
- ✅ orderDate

### In purchase_orders:

- ✅ po_number (UNIQUE)
- ✅ po_date

---

## VIEW FOR ORDER ITEMS WITH BATCH DATA

Use this view to get order items WITH batch/expiry info:

```sql
SELECT
    oi.order_item_id,
    oi.order_id,
    o.order_number,           -- Invoice number
    o.orderDate,
    oi.quantity,
    pb.batch_number,
    pb.expiry_date,            -- Expiry for this batch
    DATEDIFF(pb.expiry_date, CURDATE()) AS days_until_expiry
FROM order_item oi
JOIN orders o ON oi.order_id = o.id
LEFT JOIN product_batches pb ON oi.batch_id = pb.batch_id;
```

---

## ALL CREATE TABLE STATEMENTS REFERENCES

| Table                  | File                                                | Lines   |
| ---------------------- | --------------------------------------------------- | ------- |
| orders                 | dbFile/satyam_clinical_fresh.sql                    | 523-559 |
| order_item             | dbFile/satyam_clinical_fresh.sql                    | 563-596 |
| product_batches        | dbFile/satyam_clinical_fresh.sql                    | 194-246 |
| product                | dbFile/satyam_clinical_fresh.sql                    | 134-191 |
| purchase_orders        | dbFile/satyam_clinical_fresh.sql                    | 295-335 |
| po_items               | dbFile/satyam_clinical_fresh.sql                    | 338-373 |
| purchase_invoices      | dbFile/purchase_invoice_schema.sql                  | 6-32    |
| purchase_invoice_items | dbFile/purchase_invoice_schema.sql                  | 36-57   |
| batch_sales_map        | dbFile/migrations/005_batch_recall_soft_deletes.sql | 35-53   |

---

## SUMMARY

✅ **Schema IS properly designed** with:

- Batch foreign key in order_item
- Expiry date tracking in product_batches
- UNIQUE invoice numbers in orders
- Proper FK relationships

❌ **BUT Production Code**:

- Does NOT populate batch_id in order_item insert
- Does NOT validate batch expiry before sale
- Does NOT update batch_sales_map for recall tracking

⚠️ **ACTION NEEDED**: Update SalesOrderController to properly handle batch selection with validation
