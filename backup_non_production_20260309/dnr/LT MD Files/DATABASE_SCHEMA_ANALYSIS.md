# Database Schema Analysis - Satyam Clinical ERP

**Date**: February 20, 2026  
**Database**: satyam_clinical  
**Analysis Scope**: All schema files in dbFile/ directory and migrations

---

## 1. TABLE STRUCTURES & CREATE TABLE STATEMENTS

### 1.1 ORDERS TABLE (Sales/Invoices)

**File**: [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql#L523-L559)

```sql
CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,

  order_number VARCHAR(50) NOT NULL UNIQUE,
  orderDate DATE NOT NULL,
  clientName VARCHAR(255) NOT NULL,
  projectName VARCHAR(100),
  clientContact VARCHAR(20),
  address TEXT,

  subTotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discountPercent DECIMAL(5,2) DEFAULT 0,
  gstPercent INT DEFAULT 18,
  gstn DECIMAL(12,2) NOT NULL DEFAULT 0,
  grandTotalValue DECIMAL(12,2) NOT NULL DEFAULT 0,

  paid DECIMAL(12,2) DEFAULT 0,
  dueValue DECIMAL(12,2) DEFAULT 0,

  paymentType VARCHAR(50),
  paymentStatus ENUM('Pending','PartialPaid','Paid','Cancelled') DEFAULT 'Pending',
  paymentPlace VARCHAR(100),

  delete_status TINYINT(1) DEFAULT 0,

  created_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_order_number (order_number),
  KEY idx_orderDate (orderDate),
  KEY idx_paymentStatus (paymentStatus),
  KEY idx_delete_status (delete_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**UNIQUE Constraint**: `order_number` (column) - Enforced at database level

---

### 1.2 ORDER_ITEM TABLE (Line Items for Sales Orders)

**File**: [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql#L563-L596)

```sql
CREATE TABLE order_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,

  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  batch_id INT UNSIGNED,

  quantity INT UNSIGNED NOT NULL,
  rate DECIMAL(10,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,

  order_item_state TINYINT(1) DEFAULT 0,

  added_date DATE,

  PRIMARY KEY (id),
  KEY idx_order_id (order_id),
  KEY idx_product_id (product_id),
  KEY idx_batch_id (batch_id),

  CONSTRAINT fk_order_item_order
    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_order_item_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_order_item_batch
    FOREIGN KEY (batch_id)
    REFERENCES product_batches(batch_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Observations**:

- ✅ HAS `batch_id` column (INT UNSIGNED)
- ❌ NO `expiry_date` column in order_item itself
- ✅ HAS foreign key to product_batches
- NO UNIQUE constraint on order_number in this table (it's in orders table)

---

### 1.3 PRODUCT_BATCHES TABLE (Master Batch Data)

**File**: [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql#L194-L246)

```sql
CREATE TABLE product_batches (
  batch_id INT UNSIGNED NOT NULL AUTO_INCREMENT,

  product_id INT UNSIGNED NOT NULL,
  supplier_id INT UNSIGNED,

  batch_number VARCHAR(50) NOT NULL,
  manufacturing_date DATE,
  expiry_date DATE NOT NULL,

  available_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  reserved_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  damaged_quantity INT UNSIGNED NOT NULL DEFAULT 0,

  purchase_rate DECIMAL(10,2) NOT NULL,
  mrp DECIMAL(10,2) NOT NULL,

  status ENUM('Active','Expired','Blocked','Damaged') DEFAULT 'Active',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (batch_id),
  UNIQUE KEY unique_batch (product_id, batch_number),
  KEY idx_product_id (product_id),
  KEY idx_supplier_id (supplier_id),
  KEY idx_expiry_date (expiry_date),
  KEY idx_status (status),

  CONSTRAINT fk_batch_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_batch_supplier
    FOREIGN KEY (supplier_id)
    REFERENCES suppliers(supplier_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Columns for Batch Tracking**:

- ✅ `batch_id` - Primary Key
- ✅ `batch_number` - Unique per product
- ✅ `expiry_date` - **CRITICAL for sales invoice filtering**
- ✅ `available_quantity` - Stock level
- ✅ `purchase_rate` - Cost price
- ✅ `mrp` - Selling price

---

### 1.4 PURCHASE_INVOICES TABLE (Supplier Invoices)

**File**: [dbFile/purchase_invoice_schema.sql](dbFile/purchase_invoice_schema.sql#L6-L32)

```sql
CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `invoice_no` VARCHAR(100) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `po_reference` VARCHAR(100) DEFAULT NULL,
  `grn_reference` VARCHAR(100) DEFAULT NULL,
  `payment_terms` VARCHAR(255) DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT 'INR',
  `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_tax` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `freight` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `round_off` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Draft','Received','Matched','Approved','Paid','Cancelled') NOT NULL DEFAULT 'Draft',
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `matched_by` INT DEFAULT NULL,
  `matched_at` DATETIME DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  INDEX `idx_supplier` (`supplier_id`),
  UNIQUE KEY `uq_supplier_invoice` (`supplier_id`,`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**UNIQUE Constraint**: `uq_supplier_invoice` on (supplier_id, invoice_no) - Composite key  
**Note**: This is for **PURCHASE** invoices (supplier invoices), NOT sales invoices

---

### 1.5 PURCHASE_INVOICE_ITEMS TABLE

**File**: [dbFile/purchase_invoice_schema.sql](dbFile/purchase_invoice_schema.sql#L36-L57)

```sql
CREATE TABLE IF NOT EXISTS `purchase_invoice_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) DEFAULT NULL,
  `hsn_code` VARCHAR(50) DEFAULT NULL,
  `batch_no` VARCHAR(100) DEFAULT NULL,
  `manufacture_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `qty` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `free_qty` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `mrp` DECIMAL(14,2) DEFAULT NULL,
  `discount_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  PRIMARY KEY (`id`),
  INDEX `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Note**: This tracks batch_no as VARCHAR (not batch_id)

---

### 1.6 PURCHASE_ORDERS TABLE

**File**: [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql#L295-L335)

```sql
CREATE TABLE purchase_orders (
  po_id INT UNSIGNED NOT NULL AUTO_INCREMENT,

  po_number VARCHAR(50) NOT NULL UNIQUE,
  po_date DATE NOT NULL,
  supplier_id INT UNSIGNED NOT NULL,

  expected_delivery_date DATE,
  delivery_location VARCHAR(255),

  subtotal DECIMAL(12,2) DEFAULT 0,
  discount_percentage DECIMAL(5,2) DEFAULT 0,
  discount_amount DECIMAL(10,2) DEFAULT 0,
  gst_percentage DECIMAL(5,2) DEFAULT 0,
  gst_amount DECIMAL(10,2) DEFAULT 0,
  other_charges DECIMAL(10,2) DEFAULT 0,
  grand_total DECIMAL(12,2) DEFAULT 0,

  po_status ENUM('Draft','Submitted','Approved','PartialReceived','Received','Cancelled') DEFAULT 'Draft',
  payment_status ENUM('NotDue','Due','PartialPaid','Paid','Overdue') DEFAULT 'NotDue',

  notes TEXT,
  delete_status TINYINT(1) DEFAULT 0,

  created_by INT UNSIGNED,
  approved_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (po_id),
  UNIQUE KEY uk_po_number (po_number),
  KEY idx_supplier_id (supplier_id),
  KEY idx_po_date (po_date),
  KEY idx_po_status (po_status),
  KEY idx_delete_status (delete_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**UNIQUE Constraint**: `uk_po_number` on po_number

---

### 1.7 PO_ITEMS TABLE (Purchase Order Line Items)

**File**: [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql#L338-L373)

```sql
CREATE TABLE po_items (
  po_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,

  po_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,

  quantity_ordered INT UNSIGNED NOT NULL,
  quantity_received INT UNSIGNED DEFAULT 0,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,

  batch_number VARCHAR(50),
  expiry_date DATE,
  manufacturing_date DATE,

  item_status ENUM('Pending','PartialReceived','Received','Cancelled') DEFAULT 'Pending',
  notes VARCHAR(255),

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (po_item_id),
  KEY idx_po_id (po_id),
  KEY idx_product_id (product_id),
  KEY idx_item_status (item_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Difference from order_item**:

- Stores `batch_number` as VARCHAR
- Stores `expiry_date` directly in PO item
- NO batch_id foreign key (stores batch_number as text)

---

## 2. UNIQUE CONSTRAINTS SUMMARY

| Table             | Column(s)                  | Type             | Purpose                             |
| ----------------- | -------------------------- | ---------------- | ----------------------------------- |
| orders            | order_number               | UNIQUE KEY       | Invoice number uniqueness           |
| purchase_orders   | po_number                  | UNIQUE KEY       | Purchase Order number uniqueness    |
| purchase_invoices | (supplier_id, invoice_no)  | COMPOSITE UNIQUE | Supplier + Invoice combo uniqueness |
| product_batches   | (product_id, batch_number) | COMPOSITE UNIQUE | Batch uniqueness per product        |
| suppliers         | supplier_code              | UNIQUE           | Supplier code uniqueness            |
| product_batches   | batch_number               | VARCHAR UNIQUE   | Batch number format                 |

---

## 3. BATCH HANDLING IN ORDER_ITEM

### Current State in order_item Table:

| Column       | Type         | Has Value? | Purpose                             |
| ------------ | ------------ | ---------- | ----------------------------------- |
| batch_id     | INT UNSIGNED | ✅ YES     | Foreign key to product_batches      |
| expiry_date  | —            | ❌ NO      | Not in order_item, fetch from batch |
| batch_number | —            | ❌ NO      | Can be fetched via batch_id join    |

### How to Get Batch/Expiry Info for an Order Item:

```sql
SELECT
    oi.id,
    oi.order_id,
    oi.product_id,
    oi.batch_id,
    oi.quantity,
    oi.rate,
    pb.batch_number,
    pb.expiry_date,
    pb.available_quantity,
    pb.purchase_rate,
    pb.mrp,
    pb.status
FROM order_item oi
LEFT JOIN product_batches pb ON oi.batch_id = pb.batch_id
WHERE oi.order_id = ?;
```

---

## 4. BATCH SELECTION LOGIC FOR SALES ORDERS

### Currently Implemented (Production Code)

**File**: [libraries/Controllers/SalesOrderController.php](libraries/Controllers/SalesOrderController.php#L346-L379)

**ISSUE FOUND**: ⚠️ **batch_id is NOT being populated in order_item INSERT**

```php
private function insertOrderItem($orderId, $orderNumber, $item) {
    $sql = "
        INSERT INTO order_item (
            order_id, order_number, product_id,
            productName, quantity, rate, purchase_rate, total,
            added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    // ** MISSING: batch_id parameter **
}
```

---

### Recommended Batch Selection Logic

**File**: [php_action/examples/SalesOrderController.php](php_action/examples/SalesOrderController.php#L98-L132)

**PROPER IMPLEMENTATION** shows:

```php
// Get batch details (includes expiry validation)
$batch = $this->getBatchDetails($batch_id, $product_id);
if (!$batch) {
    throw new \Exception("Batch not found or invalid product");
}

// **CRITICAL**: Check batch not expired
if (strtotime($batch['exp_date']) < time()) {
    throw new \Exception(
        "Batch {$batch['batch_number']} expired on {$batch['exp_date']}"
    );
}

// Check sufficient stock
if ($batch['current_qty'] < $quantity) {
    throw new \Exception(
        "Insufficient stock for product {$batch['product_name']}. " .
        "Available: {$batch['current_qty']}, Requested: {$quantity}"
    );
}

// INSERT WITH batch_id
$prepared_items[] = [
    'product_id' => $product_id,
    'batch_id' => $batch_id,  // ✅ INCLUDED
    'quantity' => $quantity,
    'unit_price' => $batch['purchase_rate'],
    'line_total' => $line_total,
    'batch_number' => $batch['batch_number']
];
```

---

## 5. FOREIGN KEY RELATIONSHIPS

### order_item Foreign Keys:

```
order_item.order_id → orders.id
  ├─ ON DELETE CASCADE (if order deleted, items deleted)

order_item.product_id → product.product_id
  ├─ ON DELETE RESTRICT (cannot delete product with items)

order_item.batch_id → product_batches.batch_id
  ├─ ON DELETE SET NULL (items keep product, batch reference removed)
```

### product_batches Foreign Keys:

```
product_batches.product_id → product.product_id
  ├─ ON DELETE RESTRICT (cannot delete product with batches)

product_batches.supplier_id → suppliers.supplier_id
  ├─ ON DELETE SET NULL (batch keeps product, supplier removed)
```

---

## 6. STOCK TRACKING COLUMNS

### In product_batches (Master Data):

- `available_quantity` - Ready for sale
- `reserved_quantity` - Allocated but not sold
- `damaged_quantity` - Damaged/unusable stock

### In order_item (Transaction Data):

- `quantity` - Amount sold in this order
- **NO direct stock tracking** - calculated via stock_movements table

### In stock_movements (Audit Trail):

- `movement_type` ENUM('Purchase','Sales','Adjustment','Return','Damage','Sample','Expiry')
- `quantity` - Amount moved
- `batch_id` - Which batch was affected
- `reference_number` - Links to Order ID or PO ID

---

## 7. MIGRATION ENHANCEMENTS

### batch_sales_map Table (Migration 005)

**File**: [dbFile/migrations/005_batch_recall_soft_deletes.sql](dbFile/migrations/005_batch_recall_soft_deletes.sql#L35-L53)

```sql
CREATE TABLE IF NOT EXISTS `batch_sales_map` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `batch_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED,
    `quantity_sold` DECIMAL(10, 2) NOT NULL,
    `sale_date` DATE NOT NULL,
    `customer_id` INT UNSIGNED,
    `customer_name` VARCHAR(255),
    `customer_contact` VARCHAR(20),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_batch_id` (`batch_id`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_sale_date` (`sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Maps batches to sales orders for quick recall query';
```

**Purpose**: Enables fast batch recall queries (e.g., "Which customers bought batch XYZ?")

---

## 8. EXPIRY DATE TRACKING

### Where expiry_date is stored:

| Table                  | Column            | Purpose                               |
| ---------------------- | ----------------- | ------------------------------------- |
| product_batches        | expiry_date       | Master data for batch                 |
| po_items               | expiry_date       | Supplier PO line item level           |
| purchase_invoice_items | expiry_date       | Purchased invoice line item           |
| expiry_tracking        | expiry_date       | Alert tracking table                  |
| **order_item**         | **❌ NOT STORED** | Should be fetched from batch via join |

### Query to get expiry date for sales order item:

```sql
SELECT pb.expiry_date
FROM order_item oi
JOIN product_batches pb ON oi.batch_id = pb.batch_id
WHERE oi.id = ? AND oi.order_id = ?;
```

---

## 9. CRITICAL FINDINGS

### ✅ CORRECT SETUP:

1. ✅ order_item HAS batch_id column with FK
2. ✅ product_batches HAS expiry_date column
3. ✅ UNIQUE constraint on order_number in orders table
4. ✅ batch_sales_map created for recall tracking
5. ✅ Foreign key relationships properly configured

### ⚠️ ISSUES FOUND:

1. ❌ **Production SalesOrderController NOT populating batch_id** in order_item inserts
2. ❌ order_item table does NOT store expiry_date (must JOIN to product_batches)
3. ❌ No explicit batch selection validation in main create order flow
4. ⚠️ order_number should use po_number format for consistency with POs

### 📋 RECOMMENDATIONS:

1. **Fix insertOrderItem()** to include batch_id parameter
2. **Add batch expiry validation** before inserting order_item
3. **Update batch_sales_map** when order is created for recall tracking
4. **Create view** to quickly get order_item WITH batch/expiry details
5. **Consider storing** batch_number in order_item for audit trail

---

## 10. FILE REFERENCES

| File                                                                                                       | Purpose                 | Key Tables                       |
| ---------------------------------------------------------------------------------------------------------- | ----------------------- | -------------------------------- |
| [dbFile/pharmacy_erp_schema.sql](dbFile/pharmacy_erp_schema.sql)                                           | ERP standard schema     | All except orders, order_item    |
| [dbFile/satyam_clinical_fresh.sql](dbFile/satyam_clinical_fresh.sql)                                       | Full schema (clean)     | Complete tables with INSERT data |
| [dbFile/satyam_clinical_complete.sql](dbFile/satyam_clinical_complete.sql)                                 | Complete schema variant | Duplicate of fresh               |
| [dbFile/purchase_invoice_schema.sql](dbFile/purchase_invoice_schema.sql)                                   | Purchase invoicing      | purchase_invoices, items         |
| [dbFile/migrations/005_batch_recall_soft_deletes.sql](dbFile/migrations/005_batch_recall_soft_deletes.sql) | Batch tracking          | batch_recalls, batch_sales_map   |

---

## SQL VIEW FOR ORDER ITEMS WITH BATCH/EXPIRY

```sql
CREATE OR REPLACE VIEW v_order_items_with_batch AS
SELECT
    oi.id AS order_item_id,
    oi.order_id,
    o.order_number,
    o.orderDate,
    o.clientName,

    oi.product_id,
    p.product_name,
    p.hsn_code,

    oi.batch_id,
    pb.batch_number,
    pb.expiry_date,

    oi.quantity,
    oi.rate,
    oi.total,

    pb.available_quantity,
    pb.purchase_rate,
    pb.mrp,
    pb.status AS batch_status,

    DATEDIFF(pb.expiry_date, CURDATE()) AS days_until_expiry,
    CASE
        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 0 THEN 'EXPIRED'
        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 30 THEN 'EXPIRING_SOON'
        ELSE 'VALID'
    END AS expiry_status

FROM order_item oi
JOIN orders o ON oi.order_id = o.id
JOIN product p ON oi.product_id = p.product_id
LEFT JOIN product_batches pb ON oi.batch_id = pb.batch_id;
```
