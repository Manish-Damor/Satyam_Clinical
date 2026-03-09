# Executive Summary: Database Schema & Batch Implementation

**Date**: February 20, 2026  
**Analysis**: Complete database schema review for Satyam Clinical ERP  
**Focus**: Batch selection in sales orders, expiry tracking, invoice uniqueness

---

## SEARCH RESULTS OVERVIEW

### Files Analyzed

- ✅ 20 SQL schema files found in dbFile/ directory
- ✅ Complete CREATE TABLE statements located
- ✅ Migrations with batch tracking (005_batch_recall_soft_deletes.sql)
- ✅ Production code reviewed (SalesOrderController.php)
- ✅ Example implementations examined

### Documents Created

1. **DATABASE_SCHEMA_ANALYSIS.md** - Detailed 10-section analysis with full CREATE statements
2. **BATCH_SCHEMA_QUICK_REFERENCE.md** - Quick lookup for key details
3. **CODE_ISSUES_AND_FIXES.md** - Code problems and solutions

---

## KEY FINDINGS SUMMARY

### ✅ WHAT'S CORRECT IN DATABASE DESIGN

| Item                        | Status     | Details                                       |
| --------------------------- | ---------- | --------------------------------------------- |
| order_item.batch_id         | ✅ Exists  | INT UNSIGNED, Foreign Key to product_batches  |
| product_batches.expiry_date | ✅ Exists  | DATE column, indexed for fast queries         |
| orders.order_number UNIQUE  | ✅ Exists  | UNIQUE KEY enforces invoice number uniqueness |
| po_number UNIQUE            | ✅ Exists  | UNIQUE KEY for purchase orders                |
| purchase_invoices UNIQUE    | ✅ Exists  | Composite key (supplier_id, invoice_no)       |
| Foreign key relationships   | ✅ Correct | Proper CASCADE/RESTRICT/SET NULL rules        |
| batch_sales_map table       | ✅ Exists  | Created in migration 005 for recall tracking  |
| Stock tracking columns      | ✅ Exists  | available_qty, reserved_qty, damaged_qty      |

### ❌ WHAT'S MISSING IN IMPLEMENTATION

| Item                    | Status        | Details                                        |
| ----------------------- | ------------- | ---------------------------------------------- |
| batch_id INSERT         | ❌ Missing    | SalesOrderController doesn't populate batch_id |
| Batch expiry validation | ❌ Missing    | No check if batch is expired before sale       |
| Batch-level stock check | ❌ Missing    | Only checks product-level total                |
| batch_sales_map entry   | ❌ Missing    | Never populated when orders created            |
| Batch selection logic   | ❌ Incomplete | Client doesn't send batch_id                   |

---

## UNIQUE CONSTRAINTS FOUND

### Sales Orders (orders table)

```sql
UNIQUE KEY uk_order_number (order_number)
```

- **Type**: Single column
- **Constraint**: Prevents duplicate order_number
- **Used for**: Invoice number uniqueness

### Purchase Orders (purchase_orders table)

```sql
UNIQUE KEY uk_po_number (po_number)
```

- **Type**: Single column
- **Used for**: PO number uniqueness

### Purchase Invoices (purchase_invoices table)

```sql
UNIQUE KEY uq_supplier_invoice (supplier_id, invoice_no)
```

- **Type**: Composite (2 columns)
- **Constraint**: Supplier + Invoice number combination must be unique
- **Used for**: Multiple suppliers can use same invoice numbers, but each supplier's invoice must be unique

### Product Batches (product_batches table)

```sql
UNIQUE KEY unique_batch (product_id, batch_number)
```

- **Type**: Composite (2 columns)
- **Used for**: Cannot have duplicate batch_number per product

---

## BATCH COLUMNS IN ORDER_ITEM

### Current State:

```
Column          | In Table? | Has Data? | How to Get
----------------+-----------+-----------+---
batch_id        |    ✅     |    ❌     | Create order with batch_id
batch_number    |    ❌     |    —      | JOIN product_batches WHERE batch_id=?
expiry_date     |    ❌     |    —      | JOIN product_batches WHERE batch_id=?
batch_status    |    ❌     |    —      | JOIN product_batches status WHERE batch_id=?
```

### Query to Get Complete Order Item Data:

```sql
SELECT
    oi.id, oi.order_id, oi.product_id, oi.quantity, oi.rate, oi.total,
    pb.batch_id, pb.batch_number, pb.expiry_date,
    pb.available_quantity, pb.status,
    DATEDIFF(pb.expiry_date, CURDATE()) AS days_until_expiry
FROM order_item oi
LEFT JOIN product_batches pb ON oi.batch_id = pb.batch_id;
```

---

## BATCH SELECTION LOGIC ISSUE

### The Problem:

```
┌─────────────────────────────────┐
│    Client Creates Order         │
│  (sends product_id + quantity)  │
└──────────────┬──────────────────┘
               │
               ├─ ❌ NO batch_id sent
               │
┌──────────────▼──────────────────┐
│   SalesOrderController          │
│  createSalesOrder()             │
│                                 │
│  $item['product_id']   ✅       │
│  $item['batch_id']     ❌ NULL  │
│  $item['quantity']     ✅       │
└──────────────┬──────────────────┘
               │
               ├─ Check stock (PRODUCT-level) ⚠️
               │  Not batch-specific
               │
               ├─ Insert order_item
               │  batch_id = NULL  ❌
               │
               ├─ Deduct stock (which batch?) ⚠️
               │  No batch_id to track
               │
└──────────────▼──────────────────┘
   Order created with NULL batch_id
   Cannot validate expiry
   Cannot track recall
```

### The Solution:

```
┌────────────────────────────────────────┐
│   Client Creates Order                 │
│   (sends product_id + batch_id + qty)  │
└──────────────┬─────────────────────────┘
               │
               ├─ batch_id sent ✅
               │
┌──────────────▼──────────────────────┐
│  SalesOrderController               │
│  validateBatchForSale()             │
│                                     │
│  1. Check: batch exists for product │
│  2. Check: NOT EXPIRED              │
│  3. Check: Status = Active          │
│  4. Check: Stock >= quantity        │
│                                     │
│  Return: Batch details              │
└──────────────┬──────────────────────┘
               │
               ├─ insertOrderItem(batch_id) ✅
               │
               ├─ decreaseStock(batch_id)  ✅
               │  Specific batch deducted
               │
               ├─ mapBatchToSale()  ✅
               │  batch_sales_map updated
               │
└──────────────▼──────────────────────┘
   Order created with batch_id
   Expiry validated
   Stock tracked
   Recall enabled
```

---

## TABLE STRUCTURE COMPARISON

### For Sales Orders:

| Feature            | orders                  | order_item       | product_batches  |
| ------------------ | ----------------------- | ---------------- | ---------------- |
| **Invoice Number** | ✅ order_number         | —                | —                |
| **Unique Invoice** | ✅ UNIQUE(order_number) | —                | —                |
| **Batch Tracking** | —                       | ✅ batch_id FK   | ✅ batch_id PK   |
| **Expiry Info**    | —                       | ❌ NULL          | ✅ expiry_date   |
| **Stock Level**    | —                       | quantity (sold)  | ✅ available_qty |
| **Batch Number**   | —                       | ❌ NULL          | ✅ batch_number  |
| **Cost/Profit**    | —                       | purchase_rate ⚠️ | ✅ purchase_rate |

### For Purchase Orders (Similar Pattern):

| Feature            | purchase_orders      | po_items       | product_batches |
| ------------------ | -------------------- | -------------- | --------------- |
| **PO Number**      | ✅ po_number         | —              | —               |
| **Unique PO**      | ✅ UNIQUE(po_number) | —              | —               |
| **Batch Tracking** | —                    | batch_number   | ✅ batch_id     |
| **Expiry Info**    | —                    | ✅ expiry_date | ✅ expiry_date  |

---

## FOREIGN KEY RELATIONSHIPS

### order_item Relationships:

```
order_item.order_id
    ↓ (FK)
    orders.id
    [ON DELETE CASCADE]
    ✅ If order deleted, items deleted

order_item.product_id
    ↓ (FK)
    product.product_id
    [ON DELETE RESTRICT]
    ✅ Cannot delete product if items exist

order_item.batch_id
    ↓ (FK)
    product_batches.batch_id
    [ON DELETE SET NULL]
    ✅ If batch deleted, batch_id becomes NULL
```

### product_batches Relationships:

```
product_batches.product_id
    ↓ (FK)
    product.product_id
    [ON DELETE RESTRICT]

product_batches.supplier_id
    ↓ (FK)
    suppliers.supplier_id
    [ON DELETE SET NULL]
```

---

## MIGRATION 005: BATCH TRACKING

**File**: dbFile/migrations/005_batch_recall_soft_deletes.sql

Adds critical table:

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

**Purpose**: Enables queries like:

```sql
-- "Which customers bought batch XYZ?"
SELECT DISTINCT customer_name, customer_contact
FROM batch_sales_map
WHERE batch_id = 5;

-- "What batches did customer ABC buy?"
SELECT DISTINCT batch_id, batch_number
FROM batch_sales_map bsm
JOIN product_batches pb ON bsm.batch_id = pb.batch_id
WHERE bsm.customer_id = 123;
```

---

## DOCUMENTATION FILES CREATED

### 1. DATABASE_SCHEMA_ANALYSIS.md

- Full CREATE TABLE statements (all 6 main tables)
- UNIQUE constraint details
- Foreign key relationships
- Stock tracking columns
- Batch handling explanation
- Migration enhancements
- SQL view for order items with batch data
- File references with line numbers

### 2. BATCH_SCHEMA_QUICK_REFERENCE.md

- Quick lookup tables
- UNIQUE constraints summary
- Column listing for key tables
- Batch selection logic (what's missing)
- Foreign key summary
- Columns for batch/expiry/invoice tracking
- SQL view template

### 3. CODE_ISSUES_AND_FIXES.md

- 6 identified code issues
- Recommended fixes with code examples
- New functions to add (validateBatchForSale, mapBatchToSale)
- Updated insertOrderItem() code
- Testing checklist
- Database migration script
- Files to modify list

### 4. This File (EXECUTIVE_SUMMARY.md)

- Overview of all findings
- Unique constraints summary
- Problems and solutions
- Table comparisons
- Relationships diagram

---

## CRITICAL FINDINGS PRIORITY

### Priority 1 (CRITICAL - Breaks Functionality):

1. ❌ batch_id NOT inserted in order_item
   - **Impact**: Cannot track which batch was sold
   - **Fix**: Add batch_id to INSERT statement
   - **Risk**: Data integrity, audit trail lost

2. ❌ No batch expiry validation
   - **Impact**: Can sell expired medications
   - **Fix**: Add validateBatchForSale() function
   - **Risk**: Regulatory/compliance issue, patient safety

### Priority 2 (HIGH - Missing Features):

3. ❌ No batch_sales_map entries
   - **Impact**: Cannot perform batch recalls
   - **Fix**: Add mapBatchToSale() calls
   - **Risk**: Cannot identify customers if batch recalled

4. ❌ No batch-level stock validation
   - **Impact**: Stock might be overstated
   - **Fix**: Check batch.available_qty not product.total_qty
   - **Risk**: Inventory inaccuracy

### Priority 3 (MEDIUM - Code Quality):

5. ⚠️ Client doesn't send batch_id
   - **Fix**: Update order form to require batch selection
   - **Risk**: Cannot implement fix #1-4 without this

---

## IMPLEMENTATION ROADMAP

### Phase 1: Data Structures (0 issues - Already correct)

- ✅ order_item already has batch_id column
- ✅ product_batches already has expiry_date
- ✅ batch_sales_map already created in migration

### Phase 2: Code Updates (4 functions to add/modify)

1. Add validateBatchForSale() to SalesOrderController
2. Add mapBatchToSale() to SalesOrderController
3. Update insertOrderItem() signature and SQL
4. Update createSalesOrder() to use batch validation

### Phase 3: UI/Form Updates

1. Update order form to show batch selection dropdown
2. Make batch_id required field
3. Show expiry date warning in UI
4. Show batch stock availability

### Phase 4: Testing & Validation

1. Test batch expiry prevents sale
2. Test batch stock validation
3. Test batch_sales_map population
4. Test recall query functionality

### Phase 5: Data Migration (Optional)

1. Review existing NULL batch_id values
2. Decide on backfill strategy
3. Update historical orders if needed

---

## FILE LOCATIONS QUICK INDEX

| Content                  | File                                                | Lines   |
| ------------------------ | --------------------------------------------------- | ------- |
| orders CREATE            | dbFile/satyam_clinical_fresh.sql                    | 523-559 |
| order_item CREATE        | dbFile/satyam_clinical_fresh.sql                    | 563-596 |
| product_batches CREATE   | dbFile/satyam_clinical_fresh.sql                    | 194-246 |
| purchase_orders CREATE   | dbFile/satyam_clinical_fresh.sql                    | 295-335 |
| po_items CREATE          | dbFile/satyam_clinical_fresh.sql                    | 338-373 |
| purchase_invoices CREATE | dbFile/purchase_invoice_schema.sql                  | 6-32    |
| batch_sales_map CREATE   | dbFile/migrations/005_batch_recall_soft_deletes.sql | 35-53   |
| Production controller    | libraries/Controllers/SalesOrderController.php      | 346-379 |
| Example controller       | php_action/examples/SalesOrderController.php        | 98-132  |

---

## CONCLUSION

### Database Design: ✅ EXCELLENT

The schema is **properly designed** with:

- Batch FK in order_item
- Expiry tracking in product_batches
- UNIQUE invoice numbers
- Batch recall mapping
- Proper FK constraints

### Code Implementation: ❌ INCOMPLETE

Production code **does NOT use** the schema features:

- batch_id fields remain NULL
- No expiry validation
- No batch-level stock checks
- No recall mapping

### Recommendation:

Implement the fixes documented in CODE_ISSUES_AND_FIXES.md to enable:

1. ✅ Accurate batch tracking
2. ✅ Expiry date validation
3. ✅ Batch recall capabilities
4. ✅ Stock accuracy per batch
5. ✅ Regulatory compliance

**Estimated Effort**: 4-6 hours for code changes + 2 hours testing = ~1 day total

---

## KEY TAKEAWAYS

| Item                   | Current          | Target         | Gap             |
| ---------------------- | ---------------- | -------------- | --------------- |
| batch_id in order_item | ❌ NULL          | ✅ populated   | Code fix needed |
| Expiry validation      | ❌ None          | ✅ Enforced    | Add function    |
| Batch stock check      | ⚠️ Product-level | ✅ Batch-level | Improve logic   |
| Recall capability      | ❌ Impossible    | ✅ Enabled     | Add mapping     |
| Schema readiness       | ✅ 100%          | ✅ 100%        | Already done    |

---

**Analysis Date**: February 20, 2026  
**Status**: Analysis Complete - Ready for Implementation
