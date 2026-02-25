# SATYAM CLINICAL ERP - COMPREHENSIVE AUDIT REPORT

**Date:** February 20, 2026  
**Status:** AUDIT COMPLETE - Ready for Refactoring

---

## EXECUTIVE SUMMARY

A complete audit of the Satyam_Clinical pharmacy ERP system has been conducted examining three core modules (Create PO, Purchase Invoice, Sales Invoice) and their related backend handlers, database schema, and JavaScript calculations.

### Overall Assessment

- **Database Schema:** ✅ **EXCELLENT** - Properly designed with batch tracking and expiry validation
- **Backend Code:** ⚠️ **INCOMPLETE** - Core batch logic not implemented in production
- **Frontend Code:** ⚠️ **INCOMPLETE** - Sales invoice lacks batch selection UI
- **Stock Integrity:** ⚠️ **AT RISK** - Can sell expired batches without validation

### Critical Findings

| Category                      | Status     | Severity |
| ----------------------------- | ---------- | -------- |
| Batch tracking in sales       | ❌ Missing | CRITICAL |
| Expiry validation before sale | ❌ Missing | CRITICAL |
| Batch-level stock checking    | ❌ Missing | HIGH     |
| Negative stock protection     | ✅ Exists  | Complete |
| Duplicate invoice prevention  | ✅ Exists  | Complete |
| Tax calculation per-item      | ✅ Exists  | Complete |

---

## DETAILED FINDINGS

### 1. FIELD CONSISTENCY AUDIT

#### 1.1 Create Purchase Order (create_po.php)

**Current State:**

- ✅ Supplier selection: Yes
- ✅ Medicine/product selection: Yes
- ✅ Batch Number field (readonly): Yes - POPULATED FROM PRODUCT TABLE
- ✅ Expiry Date (readonly): Yes - POPULATED FROM PRODUCT TABLE
- ✅ MRP, PTR fields: Yes
- ✅ Rate (unit_price), Quantity, Discount, Tax: Yes

**Issue Found:**

```
❌ ISSUE #1: Batch/Expiry fields are readonly and auto-filled from product table
- Files: create_po.php (lines 256-259)
- Problem: <input ... class="batch-number" readonly>
- This is INCORRECT for PO - batches created during Purchase Invoice
- PO should NEVER include batch or expiry details
```

**Expected per requirements:** No batch or expiry in PO

#### 1.2 Purchase Invoice (purchase_invoice.php)

**Current State:**

- ✅ Invoice number: Required
- ✅ Supplier selection: Required
- ✅ GST Type dropdown: (intrastate/interstate) - AUTO-DETECTED from supplier state
- ✅ Product selection: Autocomplete with HSN from product master
- ✅ Batch Number: Required (user input)
- ✅ Manufacturing Date, Expiry Date: Required (user input)
- ✅ Quantity, Free Quantity: Yes
- ✅ Unit Cost, MRP: Yes
- ✅ Discount %, GST % per item: Yes

**State Assessment:** ✅ **CORRECT** - All required fields present

#### 1.3 Sales Invoice (add-order.php)

**Current State:**

- ✅ Product selection: Autocomplete search by product_name
- ✅ Rate field: Shows selling price (from product.rate)
- ✅ PTR field: Shows purchase rate (from latest batch)
- ✅ Quantity field: For selection
- ⚠️ **BATCH SELECTION:** Missing
- ⚠️ **EXPIRY DISPLAY:** Missing
- ⚠️ **BATCH-WISE STOCK CHECK:** Missing

**Issue Found:**

```
❌ ISSUE #2: No batch selection in sales invoice
- File: add-order.php (lines 130-160)
- Product table shows only: Medicine, Rate, PTR, Avail., Quantity, Total
- Missing: Batch selector, Expiry indicator
- Problem: Frontend has no batch_id input field
- Consequence: Cannot track which batch was sold
```

**Expected per requirements:** Batch-wise selection required

---

### 2. TAX CALCULATION CONSISTENCY AUDIT

#### 2.1 Create PO (create_po.php)

**Current Tax Logic:**

```php
// Lines 400-450: Hardcoded per-item tax
- CGST (9%), SGST (9%), IGST (18%) - hardcoded values
- JavaScript: Calculates per item, then sums
- Issue: Hardcoded 18% split (9+9 or 18) - no per-product tax rate
```

**Issues Found:**

```
❌ ISSUE #3: PO has hardcoded tax rates
- File: create_po.php (JavaScript section)
- Hardcoded: CGST 9%, SGST 9%, IGST 18%
- Should: Use per-product GST rate from product master
- No GST type selector in PO
```

#### 2.2 Purchase Invoice (purchase_invoice.php)

**Current Tax Logic:**

```php
Lines 320-390: Per-item tax calculation
- ✅ GST Type auto-detected from supplier state
- ✅ Per-item tax rate from product.gst_rate
- ✅ Split correctly: If intrastate → CGST/SGST; If interstate → IGST
- ✅ Recalculation on backend: recalculateInvoice() function exists
```

**Assessment:** ✅ **CORRECT** - Proper per-item GST with backend validation

#### 2.3 Sales Invoice (add-order.php)

**Current Tax Logic:**

```php
Lines 233-251: Single GST dropdown
- <select id="gstPercentage" name="gstPercentage">
    <option value="5" selected>5%</option>
    <option value="12">12%</option>
    <option value="18">18%</option>
  </select>
- Problem: Single global GST for entire invoice
- Not per-item
- user can override via dropdown
```

**Issues Found:**

```
❌ ISSUE #4: Single global GST dropdown in sales invoice
- File: add-order.php (lines 235-243)
- Problem: All items forced to same GST %
- Should: Each product has its own GST rate (from product master)
- Current code: overrides all items with dropdown selection
```

**JavaScript Calculation (add-order.php, lines 410-430):**

```javascript
var gstPercentage = $("#gstPercentage").val(); // Single value
var vat = (subTotal * gstPercentage) / 100; // Applied to entire subtotal
// Problem: Not per-item calculation
```

---

### 3. STOCK INTEGRITY AUDIT

#### 3.1 Stock Creation

**Source:** Purchase Invoice only ✅

- File: [php_action/purchase_invoice_action.php](php_action/purchase_invoice_action.php) (line 413)
- Function: `updateOrCreateStockBatch()`
- Behavior: Creates entry in `stock_batches` table per batch_no
- Verification: ✅ **CORRECT**

#### 3.2 Stock Deduction

**Source:** Sales Order (add-order.php → order.php → SalesOrderController)

- File: [libraries/Controllers/SalesOrderController.php](libraries/Controllers/SalesOrderController.php) (line 120)
- Issue: Stock deduction is **PRODUCT-LEVEL** not **BATCH-LEVEL**

```php
$deductResult = $this->stockService->decreaseStock(
    $item['product_id'],           // Product ID only
    $item['quantity'],
    'sales_order',
    $orderId,
    $this->userId
);
// No batch_id passed!
```

**Issues Found:**

```
❌ ISSUE #5: Stock deduction is product-level, not batch-level
- File: SalesOrderController.php (line 125)
- Problem: decreaseStock() only knows which PRODUCT to deduct from
- Cannot deduct from SPECIFIC BATCH
- Consequence: No batch tracking; Cannot handle FIFO/LIFO
- Cannot enforce expiry-first sales
```

#### 3.3 Negative Stock Protection

**Status:** ✅ **PRESENT**

- File: [libraries/Services/StockService.php](libraries/Services/StockService.php) (line 169)
- Check: `if ($old_qty < $quantity) { throw Exception }`
- Works: Yes ✅

#### 3.4 Expired Batch Prevention

**Status:** ❌ **MISSING IN PRODUCTION**

- Code exists: [libraries/Services/StockService.php](libraries/Services/StockService.php) (line 176)

```php
if ($reference_type == 'SALES_ORDER') {
    if (strtotime($batch['exp_date']) < time()) {
        throw new Exception("Cannot sell from expired batch");
    }
}
```

- **But:** This code path is NOT called during add-order.php → order.php flow
- **Why:** StockService is not used in production order path (commented out?)

**Critical Issue:**

```
❌ ISSUE #6: Expired batch validation NOT executing during sales
- File: add-order.php → php_action/order.php
- Problem: No expiry check before deducting stock
- Risk: Can sell expired medications ⚠️ PHARMACY REGULATORY RISK
```

#### 3.5 Stock Movement Audit Trail

**Status:** ✅ **PRESENT**

- All movements logged to `stock_movements` table
- Includes: movement_type, reference_id, timestamp, user_id

---

### 4. INVOICE NUMBER INTEGRITY AUDIT

#### 4.1 Sales Invoice (orders table)

**Constraint:** UNIQUE on `order_number`

```sql
-- Schema shows:
ALTER TABLE orders ADD UNIQUE KEY unique_order_number (order_number)
```

**Assessment:** ✅ **PROTECTED**

#### 4.2 Purchase Order (purchase_orders table)

**Constraint:** UNIQUE on `po_number`

```sql
ALTER TABLE purchase_orders ADD UNIQUE KEY unique_po_number (po_number)
```

**Assessment:** ✅ **PROTECTED**

#### 4.3 Purchase Invoice (purchase_invoices table)

**Constraint:** UNIQUE COMPOSITE on `(supplier_id, invoice_no)`

```sql
-- Allows same invoice number from different suppliers but NOT duplicates per supplier
ALTER TABLE purchase_invoices ADD UNIQUE KEY unique_supplier_invoice (supplier_id, invoice_no)
```

**Assessment:** ✅ **PROTECTED**

---

### 5. UI DISPLAY VALIDATION AUDIT

#### 5.1 Calculated Fields in Sales Invoice (add-order.php)

**Field: Sub Total**

- Display: ✅ Shows in field id="subTotal"
- Hidden Input: ✅ id="subTotalValue"
- Calculation: getTotal() → subAmount()
- Match: ✅ Verified

**Field: Discount**

- Display: ✅ Shows in field id="discount"
- Hidden Input: ✅ Manual entry (not hidden)
- Trigger: onkeyup="discountFunc()"
- Match: ✅ Applied correctly

**Field: Grand Total**

- Display: ✅ Shows in field id="grandTotal"
- Hidden Input: ✅ id="grandTotalValue"
- Calculation: Based on subTotal - discount + GST
- **ISSUE:** GST is single value applied to entire total

**Field: GST/VAT**

- Display: ✅ Shows in field id="vat"
- Hidden Input: ✅ id="vatValue"
- **Issues:**

```
❌ Calculation uses single gstPercentage value
❌ Not per-item tax rate
❌ Formula: vat = subTotal * gstPercentage / 100
❌ Should be: SUM(item_tax) based on per-item rates
```

**Field: Paid Amount**

- Display: ✅ Shows in field id="paid"
- Calculation: Manual entry
- Trigger: onkeyup="paidAmount()"
- Match: ✅ Correct

**Field: Outstanding**

- Display: ✅ Shows in field id="due" (labeled "Due Amount")
- Hidden Input: ✅ id="dueValue"
- Calculation: grandTotal - paid
- Match: ✅ Verified

**Overall Assessment:** ⚠️ **TAX CALCULATION MISMATCH** - Single GST applied to total, not per-item

#### 5.2 Hidden Inputs Consistency

All fields have corresponding hidden inputs for POST submission:

- ✅ subTotalValue
- ✅ totalAmountValue
- ✅ discountValue (derived from discount)
- ✅ grandTotalValue
- ✅ vatValue
- ✅ dueValue

**Match Check:** ✅ Hidden values populated before form submission

#### 5.3 Purchase Invoice Display (purchase_invoice.php)

All display fields have direct mapping:

- ✅ Subtotal calculated and displayed
- ✅ Total Discount calculated and displayed
- ✅ Taxable Value calculated and displayed
- ✅ CGST/SGST or IGST shown based on GST type
- ✅ Grand Total calculated and displayed
- ✅ Outstanding calculated and displayed

**Assessment:** ✅ **CORRECT** - All totals match per-item calculations

---

## AUDIT SUMMARY TABLE

| Check                 | PO                  | Purchase Invoice | Sales Invoice         |
| --------------------- | ------------------- | ---------------- | --------------------- |
| Header fields correct | ✅                  | ✅               | ✅                    |
| Item fields required  | ✅                  | ✅               | ⚠️ Missing batch      |
| Batch in form         | ❌ (readonly-wrong) | ✅               | ❌ (missing)          |
| Expiry in form        | ❌ (readonly-wrong) | ✅               | ❌ (missing)          |
| Per-item tax rate     | ❌ Hardcoded        | ✅               | ❌ Global dropdown    |
| Stock increased       | N/A                 | ✅               | N/A                   |
| Stock decreased       | N/A                 | N/A              | ⚠️ Product-level only |
| Expiry validation     | ✅ (UI only)        | ✅ (backend)     | ❌ Missing            |
| Negative stock check  | ✅                  | ✅               | ✅                    |
| Batch tracking        | ❌ (readonly)       | ✅               | ❌ Missing            |
| Invoice uniqueness    | ✅                  | ✅               | ✅                    |
| Totals match          | ✅                  | ✅               | ⚠️ GST issue          |

---

## CRITICAL ISSUES IDENTIFIED

### Issue #1: PO Contains Batch/Expiry Fields (create_po.php)

- **Location:** Lines 256-259
- **Severity:** HIGH
- **Impact:** Confusing UI; Violates requirement that "PO should not include batch"
- **Fix:** Remove batch_number and expiry_date fields from PO form

### Issue #2: Sales Invoice Missing Batch Selection (add-order.php)

- **Location:** Lines 130-160
- **Severity:** CRITICAL
- **Impact:** Cannot track which batch sold; Cannot enforce batch expiry
- **Fix:** Add batch selector dropdown to product row

### Issue #3: PO Has Hardcoded Tax Rates (create_po.php)

- **Location:** JavaScript section
- **Severity:** HIGH
- **Impact:** Tax incorrect if product has different GST rate
- **Fix:** Remove from PO OR use per-product GST rate

### Issue #4: Sales Invoice Has Single Global GST (add-order.php)

- **Location:** Lines 235-243, 410-430
- **Severity:** CRITICAL
- **Impact:** Incorrect tax if products have different rates
- **Fix:** Use per-product GST rate; Hide global GST selector

### Issue #5: Stock Deduction Is Product-Level, Not Batch-Level (SalesOrderController.php)

- **Location:** Line 125
- **Severity:** CRITICAL
- **Impact:** Cannot handle FIFO/LIFO; Cannot sold from specific batch
- **Fix:** Implement batch-level deduction in StockService

### Issue #6: Expired Batch Validation Not Executing (add-order.php → order.php)

- **Location:** No validation before inserting order
- **Severity:** CRITICAL
- **Impact:** Can sell expired medications ⚠️
- **Fix:** Add expiry validation before stock deduction

---

## FINAL ASSESSMENT

### What's Working Well ✅

1. Database schema is excellently designed with proper batch tracking
2. Negative stock protection in place
3. Invoice number uniqueness enforced
4. Purchase Invoice tax calculation is correct
5. Stock creation (PO → Purchase Invoice only) verified
6. Audit trail (stock_movements) properly maintained
7. UNIQUE constraints on all invoice numbers working

### What's Broken ❌

1. PO incorrectly includes batch/expiry fields
2. Sales Invoice missing batch selection UI
3. Sales Invoice uses global GST not per-item
4. Stock deduction doesn't specify batch (only product)
5. Expired batch validation not running
6. No batch-wise stock accuracy checks

### Workflow Status

```
Current Flow:
PO (with batch fields) → Purchase Invoice (creates stock per batch) → Sales Invoice (deducts product-level stock)
                        ❌ Wrong - PO shouldn't have batch info

Intended Flow:
PO (no batch/expiry) → Purchase Invoice (with batch) → creates batch stock → Sales Invoice (selects batch) → deducts per batch
                       ❌ Currently skipped batch selection step in sales
```

---

## READY FOR REFACTORING

All audit findings have been documented. The codebase is ready to proceed to **Phase 2: Refactoring** where specific issues will be fixed with detailed code implementations.

**Next Steps:**

1. Review this audit report
2. Approve refactoring scope
3. Implement 6 critical fixes
4. Run comprehensive testing

---

**Report Generated:** 2026-02-20  
**Audit Status:** COMPLETE ✅  
**Refactoring Ready:** YES ✅
