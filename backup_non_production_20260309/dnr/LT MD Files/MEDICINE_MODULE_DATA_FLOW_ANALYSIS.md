# MEDICINE MODULE - COMPLETE DATA FLOW ANALYSIS

## Executive Summary

The medicine module has a **FRAGMENTED ARCHITECTURE** with 3 conflicting batch tables causing stock tracking to fail across modules. The system is currently **BROKEN FOR SALES INVOICES**.

---

## PART 1: THE THREE BATCH TABLES PROBLEM

### Table 1: `product_batches` (MODERN - 14 Columns)

```sql
COLUMNS: batch_id, product_id, supplier_id, batch_number, manufacturing_date,
         expiry_date, available_quantity, reserved_quantity, damaged_quantity,
         purchase_rate, mrp, status, created_at, updated_at
```

- **Status**: ✅ Exists in database
- **Design**: Modern, properly normalized
- **Usage**:
  - ✅ `manage_medicine.php` (reads correctly)
  - ✅ `viewStock.php` (reads correctly)
  - ✅ `createStock.php` (writes correctly)
- **Purpose**: Track individual batch inventory with reserved/damaged quantities

---

### Table 2: `stock_batches` (LEGACY - 13 Columns)

```sql
COLUMNS: id, product_id, batch_no, manufacture_date, expiry_date, qty, mrp,
         cost_price, created_at, supplier_id, invoice_id, gst_rate_applied,
         unit_cost_with_tax, created_by
```

- **Status**: ✅ Exists in database
- **Design**: Legacy, denormalized, missing quantity management columns
- **Usage**:
  - ⚠️ `purchase_invoice_action.php` (writes to this table - WRONG!)
  - ⚠️ `po_edit_action.php` (writes to this table)
- **Problem**: Duplicates data with product_batches, lacks reserved/damaged quantity tracking

---

### Table 3: `medicine_batch` (PHANTOM - DOESN'T EXIST)

```sql
REFERENCED BUT NEVER CREATED:
COLUMNS: batch_id, batch_number, product_id, expiry_date, status, ...
```

- **Status**: ❌ DOESN'T EXIST in database
- **Design**: Ghost table from migration/planning that was never created
- **Usage**:
  - ❌ `fetchProductInvoice.php` line 62 (ATTEMPTS TO READ - WILL CRASH!)
- **Impact**: **BLOCKS ENTIRE SALES INVOICE WORKFLOW**

---

## PART 2: THE BROKEN DATA FLOW

### Current (BROKEN) Purchase Invoice Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. USER CREATES PURCHASE INVOICE                                │
│    File: purchase_invoice.php                                   │
│    - Fills supplier, invoice details, items table               │
│    - Each item captures:                                        │
│      * Product ID, HSN, Batch#, Mfg/Expiry dates               │
│      * Qty, Free Qty, Cost, MRP, GST%                           │
│    - Calculates margins, GST split (intrastate/interstate)      │
│    - Submits via AJAX to create_purchase_invoice.php           │
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. BACKEND VALIDATION & RECALCULATION                           │
│    File: purchase_invoice_action.php                            │
│    - validateInvoiceHeader(): Checks supplier, invoice#, dates  │
│    - validateInvoiceItems(): Validates each item, expiry > date │
│    - recalculateInvoice(): Backend recalculates ALL totals      │
│      * Recalculates line amounts, discounts, tax                │
│      * Splits GST: Intrastate (CGST+SGST) or Interstate (IGST)  │
│      * Never trusts client calculations                         │
│    - Denormalizes supplier state, GST#, calculations           │
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. DATABASE TRANSACTION BEGINS (Line 160+)                      │
│    - START TRANSACTION                                          │
│    - INSERT into purchase_invoices (header with 32 fields)     │
│    - INSERT into purchase_invoice_items (24 fields per line)   │
│    - CREATE STOCK → Insert/Update stock_batches ⚠️  WRONG TABLE│
│    - MISSING: Insert into stock_movements (NOT LOGGED!)        │
│    - COMMIT on success                                         │
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. STOCK BATCH CREATION (Lines 468-494 of purchase_invoice_    │
│    action.php - updateOrCreateStockBatch method)                │
│                                                                 │
│    FOR EACH ITEM:                                              │
│    ┌─────────────────────────────────────────────────────────┐│
│    │ Check if batch_no exists in stock_batches               ││
│    │                                                         ││
│    │ IF YES: UPDATE stock_batches                            ││
│    │   SET qty = qty + $total_qty                            ││
│    │   WHERE product_id = ? AND batch_no = ?                 ││
│    │                                                         ││
│    │ IF NO: INSERT into stock_batches                        ││
│    │   INSERT VALUES (product_id, batch_no, mfg_date,       ││
│    │                   expiry_date, total_qty, mrp,         ││
│    │                   cost_price, supplier_id, invoice_id, ││
│    │                   gst_rate, user_id)                   ││
│    │                                                         ││
│    │ ⚠️ PROBLEM: Uses LEGACY table (stock_batches)           ││
│    │ ⚠️ PROBLEM: Doesn't distinguish reserved vs available  ││
│    │ ⚠️ MISSING: No entry to stock_movements table           ││
│    │ ⚠️ MISSING: No sync to product_batches table            ││
│    └─────────────────────────────────────────────────────────┘│
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
              DATA STORED IN:
              stock_batches (✓ Added)
              product_batches (✗ NOT UPDATED)
              stock_movements (✗ NOT LOGGED)
```

---

### Current (BROKEN) Sales Invoice Batch Selection Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. USER OPENS SALES INVOICE FORM                                │
│    File: sales_invoice_form.php                                 │
│    - Selects product from dropdown                              │
│    - Form requests available batches via AJAX                   │
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. AJAX HANDLER REQUESTS BATCH LIST                             │
│    File: fetchProductInvoice.php                                │
│    - Receives: product_id                                       │
│    - Executes query (Line 62):                                  │
│                                                                 │
│      SELECT b.batch_id, b.batch_number, b.expiry_date,         │
│      FROM medicine_batch b                 ← ❌ TABLE DOESN'T   │
│      WHERE b.product_id = ?                   EXIST!             │
│      AND b.status = 'active'                                   │
│                                                                 │
└─────────────────┬───────────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. DATABASE ERROR                                               │
│                                                                 │
│    MySQL Error: "Table 'satyam_clinical_new.medicine_batch'     │
│                 doesn't exist"                                  │
│                                                                 │
│    JavaScript Error: AJAX response is null/error                │
│    User sees: Batch dropdown empty or error message            │
│                                                                 │
│    RESULT: ❌ CANNOT CREATE SALES INVOICE                       │
│             ❌ INVOICE MODULE IS BLOCKED                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## PART 3: THE ROOT CAUSES

### Root Cause #1: Three Conflicting Table Designs

| Aspect                   | product_batches  | stock_batches    | medicine_batch |
| ------------------------ | ---------------- | ---------------- | -------------- |
| **Exists**               | ✅ Yes           | ✅ Yes           | ❌ NO          |
| **Design**               | Modern (14 cols) | Legacy (13 cols) | N/A            |
| **Tracks Reserved Qty**  | ✅ Yes           | ❌ No            | N/A            |
| **Tracks Damaged Qty**   | ✅ Yes           | ❌ No            | N/A            |
| **Includes Supplier FK** | ✅ Yes           | ✅ Yes           | N/A            |
| **Includes Invoice FK**  | ❌ No            | ✅ Yes           | N/A            |

### Root Cause #2: Module-to-Module Misalignment

```
Purchase Invoice writes:     stock_batches (LEGACY)
                                    ↓
                                   [NO SYNC]
                                    ↓
Sales Invoice reads from:   medicine_batch (PHANTOM - DOESN'T EXIST!)

Should both use:            product_batches (MODERN - CORRECT)
```

### Root Cause #3: Missing Stock Movement Logging

- When batches are created in `purchase_invoice_action.php`, **NO entry is made to `stock_movements` table**
- This breaks the audit trail and inventory reporting
- Sales invoice's `fetchProductInvoice.php` tries to calculate available qty from stock_movements (Line 63):
  ```php
  COALESCE(SUM(sm.quantity_in - sm.quantity_out), 0) as available_quantity
  ```
  But this calculation fails because:
  1. `medicine_batch` table doesn't exist (crashes immediately)
  2. Even if fixed to use `product_batches`, stock_movements would be empty (never logged)

---

## PART 4: EXACT CODE PROBLEMS

### Problem 1: CRITICAL - medicine_batch Reference

**File**: [fetchProductInvoice.php](fetchProductInvoice.php#L62)
**Lines**: 58-65

```php
    $batchStmt = $connect->prepare("
        SELECT
            b.batch_id,
            b.batch_number,
            b.expiry_date,
            COALESCE(SUM(sm.quantity_in - sm.quantity_out), 0) as available_quantity
        FROM medicine_batch b                    ← ❌ TABLE DOESN'T EXIST
        LEFT JOIN stock_movements sm ON b.batch_id = sm.batch_id
```

**Impact**: 🔴 CRITICAL - Application crashes when selecting batch in sales invoice

**Severity**: BLOCKS ENTIRE SALES INVOICE MODULE

---

### Problem 2: HIGH - Wrong Stock Table in Purchase Invoice

**File**: [purchase_invoice_action.php](purchase_invoice_action.php#L468)
**Lines**: 468-494 (updateOrCreateStockBatch method)

```php
private static function updateOrCreateStockBatch($invoice_id, $item, $supplier_id) {
    // ...
    $checkSql = "SELECT id FROM stock_batches WHERE product_id = ? AND batch_no = ?";
    // ...
    if ($result->num_rows > 0) {
        // UPDATE stock_batches ← LEGACY TABLE
        $updateSql = "UPDATE stock_batches SET qty = qty + ? WHERE id = ?";
    } else {
        // INSERT stock_batches ← LEGACY TABLE, missing quantity breakdown
        $insertSql = "INSERT INTO stock_batches (product_id, batch_no, ...
```

**Impact**: 🟡 HIGH - Stock created in wrong table, duplicates data with product_batches

**Problem**:

- Writes to LEGACY table instead of MODERN table
- Doesn't distinguish available vs reserved vs damaged quantities
- Lost data when product_batches is eventually used

---

### Problem 3: HIGH - Stock Movements Never Logged

**File**: [purchase_invoice_action.php](purchase_invoice_action.php#L240)
**Lines**: Entire transaction (160-540)

```php
// BEGIN TRANSACTION
$connect->begin_transaction();

// ✅ INSERT purchase_invoices header
// ✅ INSERT purchase_invoice_items
// ✅ INSERT/UPDATE stock_batches ← Happens here
// ❌ MISSING: INSERT INTO stock_movements ← SHOULD LOG HERE

$connect->commit();
```

**Impact**: 🟡 HIGH - No audit trail of stock creation, breaks inventory reporting

**Result**: When `fetchProductInvoice.php` tries to calculate available qty from stock_movements, it finds nothing

---

## PART 5: DATA STATE IN DATABASE

### After Purchase Invoice Created:

```
stock_batches table:
├─ Contains: batch records with qty, mrp, cost_price, supplier_id, invoice_id
└─ Data: ✅ Populated by purchase_invoice_action.php

product_batches table:
├─ Should contain: batches with available_qty, reserved_qty, damaged_qty
└─ Data: ❌ EMPTY (nobody updates it during purchase invoice)

stock_movements table:
├─ Should contain: audit log of all stock changes (Purchase, Sales, Adjustments)
└─ Data: ❌ EMPTY (never populated by purchase_invoice_action.php)

medicine_batch table:
├─ Should exist but: ❌ DOESN'T EXIST IN DATABASE
└─ Data: N/A
```

---

## PART 6: IMPACT ON OTHER MODULES

### How This Breaks Other Modules:

**Sales Invoice Module** ❌

- Needs to list available batches when user selects product
- Calls `fetchProductInvoice.php`
- Query crashes on non-existent `medicine_batch` table
- **Result**: Cannot create any sales invoice

**Inventory Reporting Module** ⚠️

- Tries to query `stock_movements` for stock history
- Table exists but is empty
- `product_batches` exists but inconsistent with `stock_batches`
- **Result**: Reports show incomplete/wrong data

**Stock Adjustment Module** ⚠️

- Should use `product_batches` for available/reserved quantities
- Gets from `stock_batches` instead (doesn't have these fields)
- **Result**: Cannot properly track reserved stock during sales

**Purchase Order Module** ⚠️

- References `stock_batches` via `po_edit_action.php`
- Also broken by same dual-table problem
- **Result**: Stock tracking in PO workflow is unreliable

---

## PART 7: THE SOLUTION

### Option A: Emergency Fix (5 minutes)

Fix only the immediate crash:

1. Change `fetchProductInvoice.php` line 62 from `medicine_batch` to `product_batches`
2. Test sales invoice module

**Pros**: Unblocks sales invoice immediately
**Cons**: Leaves underlying architecture broken, data inconsistency continues

---

### Option B: Strategic Consolidation (3 hours)

Properly align all modules to use unified batch table:

**Phase 1: Update Purchase Invoice (1 hour)**

- Change `purchase_invoice_action.php` to write to `product_batches` instead of `stock_batches`
- Add logic to populate `stock_movements` table for audit trail
- Ensure proper qty breakdown (available/reserved/damaged)

**Phase 2: Fix Sales Invoice (1 hour)**

- Point `fetchProductInvoice.php` to correct table
- Implement proper available qty calculation from `product_batches`
- Use `stock_movements` for transaction logging

**Phase 3: Clean Up (1 hour)**

- Remove references to `stock_batches` from other modules
- Verify `product_batches` is used everywhere
- Update purchase order integration

**Result**: Unified, consistent stock tracking across all modules

---

## PART 8: RECOMMENDATION

### Immediate Action Required:

The system is **COMPLETELY BROKEN** for sales invoicing. The error will occur when any user tries to create a sales invoice.

### Recommended Approach:

1. **Today**: Apply Emergency Fix to unblock sales invoices (5 min)
2. **This Sprint**: Implement Strategic Consolidation (3 hours)
3. **Verification**: Run full integration tests after consolidation

### Why This Matters:

- **Central Module**: Medicine/Product module is referenced by ALL other modules
- **Critical Path**: Block at this module blocks entire ERP workflow
- **Data Integrity**: Currently duplicating stock data across 2 tables, querying from third (phantom)

---

## Next Steps

1. **Understand**: Do you want emergency fix now + consolidation later?
2. **Or**: Do consolidation immediately (may take 3 hours)?
3. **Or**: Something else?

Please confirm your preferred approach, and I'll execute accordingly.
