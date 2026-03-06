# AUDIT ISSUES - QUICK REFERENCE GUIDE

**6 Critical Issues Identified & Documented**

---

## ISSUE GROUPS

### GROUP A: BATCH TRACKING (2 Issues)

#### Issue #2: Sales Invoice Missing Batch Selection ⚠️ CRITICAL

**What:** Can't select which batch to sell from  
**Where:** add-order.php product table (lines 130-160)  
**Why:** Original UI designed before batch tracking added  
**Risk:** Cannot track which batch sold; Cannot recall batches; No FIFO/LIFO  
**Fix:** Add batch dropdown selector column to product table  
**Time:** 30 min

#### Issue #5: Stock Deduction is Product-Level, Not Batch-Level ⚠️ CRITICAL

**What:** Stock deduction doesn't specify which batch to deduct from  
**Where:** SalesOrderController.php line 125  
**Why:** StockService called with only product_id, not batch_id  
**Risk:** No batch tracking; Cannot enforce batch-specific inventory accuracy  
**Fix:** Pass batch_id to StockService.decreaseStock()  
**Time:** 20 min

**Combined Fix Time:** 50 min  
**Combined Impact:** Complete batch tracking in sales orders

---

### GROUP B: EXPIRY VALIDATION (1 Issue)

#### Issue #6: Expired Batch Validation Not Executing ⚠️ CRITICAL

**What:** Can sell medicines from expired batches  
**Where:** add-order.php → order.php (no validation before checkout)  
**Why:** Validation code exists in StockService but is never called  
**Risk:** PHARMACY REGULATORY/COMPLIANCE RISK ⚠️  
**Fix:** Add expiry check in order.php before stock deduction  
**Time:** 20 min (combined with Issue #5)

**Impact:** Prevent pharmacy from selling expired medicines

---

### GROUP C: TAX CALCULATION (2 Issues)

#### Issue #3: PO Has Hardcoded Tax Rates ⚠️ HIGH

**What:** PO shows CGST 9% / SGST 9% / IGST 18% hardcoded for all products  
**Where:** create_po.php lines 410-480 (Totals Section) + JavaScript  
**Why:** Original PO didn't use per-product GST rates from master  
**Why #2:** GST tax is actually determined at Invoice stage, not PO stage  
**Risk:** Wrong tax display; Confusion about GST responsibility  
**Fix:** Remove entire tax calculation section from PO  
**Time:** 5 min

#### Issue #4: Sales Invoice Has Single Global GST Dropdown ⚠️ CRITICAL

**What:** Single dropdown applies same GST % to entire invoice  
**Where:** add-order.php lines 235-243, 410-430  
**Why:** Frontend doesn't use per-product GST rates  
**Risk:** Incorrect tax if products have different rates (e.g., 5% + 18%)  
**Fix:** Remove dropdown; use per-product GST from master  
**Time:** 10 min

**Combined Fix Time:** 15 min  
**Combined Impact:** Correct tax calculation per product's GST rate

---

### GROUP D: DATA CLEANLINESS (1 Issue)

#### Issue #1: PO Contains Batch/Expiry Fields ⚠️ HIGH

**What:** Readonly batch number and expiry date fields in PO form  
**Where:** create_po.php lines 256-259 (table headers) + addRow() JavaScript  
**Why:** UI template copied from purchase invoice without cleanup  
**Why #2:** PO shouldn't contain batch info (created during Invoice, not PO)  
**Risk:** Confusing user experience; Wrong expectation about what PO captures  
**Fix:** Remove batch and expiry columns from form  
**Time:** 5 min

**Impact:** Clear, correct UI - PO only captures commitment, not stock details

---

## ISSUE SUMMARY TABLE

| Issue# | Category          | Severity | Component            | Fix Time | Status     |
| ------ | ----------------- | -------- | -------------------- | -------- | ---------- |
| #1     | Data Cleanliness  | HIGH     | create_po.php        | 5 min    | Documented |
| #2     | Batch Tracking    | CRITICAL | add-order.php        | 30 min   | Documented |
| #3     | Tax Calculation   | HIGH     | create_po.php        | 5 min    | Documented |
| #4     | Tax Calculation   | CRITICAL | add-order.php        | 10 min   | Documented |
| #5     | Batch Tracking    | CRITICAL | SalesOrderController | 20 min   | Documented |
| #6     | Expiry Validation | CRITICAL | order.php            | (w/ #5)  | Documented |

**Total Time to Fix All:** ~70 minutes  
**Critical Issues:** 4 out of 6  
**High Priority Issues:** 2 out of 6

---

## REMEDIATION ROADMAP

### Execution Order (Recommended)

```
Minute 0-5     Fix #1: Remove batch/expiry from PO
    ↓
Minute 5-10    Fix #3: Remove hardcoded tax from PO
    ↓
Minute 10-20   Fix #4: Remove global GST from Sales Invoice
    ↓
Minute 20-50   Fix #2: Add batch selector to Sales Invoice
    ↓
Minute 50-70   Fix #5 & #6: Implement batch-level deduction + expiry validation

Total: ~70 minutes
```

### Why This Order?

1. Start with simpler removals (Fix #1, #3)
2. Then UI additions (Fix #4, #2)
3. Finally complex backend logic (Fix #5, #6)

---

## RISK ASSESSMENT

### Pre-Refactoring Risks

| Risk                            | Severity | Mitigation                     |
| ------------------------------- | -------- | ------------------------------ |
| Can sell expired medicines      | CRITICAL | Issue #6 fix prevents this     |
| No batch tracking in orders     | CRITICAL | Issue #2 & #5 fix provide this |
| Wrong tax if mixed GST products | HIGH     | Issue #4 fix resolves this     |
| User confusion about PO scope   | MEDIUM   | Issue #1 fix clarifies UI      |
| Hardcoded tax in PO             | MEDIUM   | Issue #3 fix removes it        |

### Post-Refactoring Risks

| Risk                       | Status     |
| -------------------------- | ---------- |
| Can sell expired medicines | ✅ BLOCKED |
| No batch tracking          | ✅ ENABLED |
| Wrong tax calculation      | ✅ FIXED   |
| Confusing UI               | ✅ CLEARED |
| Hardcoded tax              | ✅ REMOVED |

---

## SUCCESS CRITERIA

### Fix #1: Remove PO Batch/Expiry

- ✅ Batch column not in table
- ✅ Expiry column not in table
- ✅ No batch inputs in form
- ✅ Form still submits without errors

### Fix #2: Add Sales Invoice Batch Selector

- ✅ Batch dropdown visible in product table
- ✅ Dropdown populates with non-expired batches when product selected
- ✅ Expired batches marked or hidden
- ✅ Batch selection required (form validates)
- ✅ Batch ID stored in database

### Fix #3: Remove PO Tax Calculation

- ✅ CGST/SGST/IGST fields removed from form
- ✅ No tax calculation in JavaScript
- ✅ Simple subtotal/discount/net display remains
- ✅ Form still works without GST fields

### Fix #4: Remove Sales Invoice Global GST

- ✅ GST dropdown removed from form
- ✅ Tax calculated per product from master
- ✅ Tax summary shows sum of item-level taxes
- ✅ Invoice grand total correct

### Fix #5 & #6: Batch Deduction + Expiry Validation

- ✅ Expired batch rejects at checkout (order.php validation)
- ✅ Stock deducted from specific batch
- ✅ stock_batches.available_quantity decreases
- ✅ order_item.batch_id populated
- ✅ Batch expiry display in cart warning

---

## IMPLEMENTATION CHECKLIST

```
Phase 1: Code Review (✅ DONE)
  ✅ create_po.php analyzed
  ✅ purchase_invoice.php analyzed
  ✅ add-order.php analyzed
  ✅ Backend handlers reviewed
  ✅ Database schema verified
  ✅ JavaScript calculations reviewed

Phase 2: Documentation (✅ DONE)
  ✅ AUDIT_REPORT_COMPLETE.md created
  ✅ REFACTORING_PLAN.md created
  ✅ AUDIT_SUMMARY.md created
  ✅ This guide created

Phase 3: Implementation (⏳ READY)
  ⬜ Fix #1: Remove PO batch/expiry
  ⬜ Fix #3: Remove PO hardcoded tax
  ⬜ Fix #4: Remove Sales GST dropdown
  ⬜ Fix #2: Add batch selector to Sales
  ⬜ Fix #5 & #6: Batch-level deduction + expiry check
  ⬜ Test all 6 fixes
  ⬜ Run end-to-end workflow test

Phase 4: Testing (⏳ READY)
  ⬜ Create PO (no batch/no tax shown)
  ⬜ Create Purchase Invoice (with batch) → stock created
  ⬜ Create Sales Invoice (select batch) → stock deducted
  ⬜ Try to sell expired batch → blocked ✅
  ⬜ Try to oversell batch → blocked ✅
  ⬜ Verify all tax calculations correct
  ⬜ Verify totals match frontend to backend
```

---

## VISUAL WORKFLOW COMPARISON

### CURRENT WORKFLOW (BROKEN)

```
┌─────────────────────────────────────────┐
│ CREATE PURCHASE ORDER                  │
│ - Shows batch fields (readonly) ❌     │
│ - Shows hardcoded 9%/18% tax ❌        │
│ - No actual batch created here         │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ CREATE PURCHASE INVOICE                │
│ - Inputs batch number & expiry ✅       │
│ - Creates stock_batches entry ✅        │
│ - Per-item GST calculation ✅           │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ CREATE SALES INVOICE                   │
│ - NO batch selection ❌                 │
│ - Single global GST dropdown ❌         │
│ - Product-level stock deduction ❌     │
│ - No expiry validation ❌              │
│ - Can sell expired batch ⚠️            │
└─────────────────────────────────────────┘
```

### POST-REFACTORING WORKFLOW (FIXED)

```
┌─────────────────────────────────────────┐
│ CREATE PURCHASE ORDER                  │
│ - No batch/expiry fields ✅            │
│ - No tax calculation ✅                │
│ - Simple commitment document ✅        │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ CREATE PURCHASE INVOICE                │
│ - Inputs batch number & expiry ✅       │
│ - Creates stock_batches entry ✅        │
│ - Per-item GST calculation ✅           │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ CREATE SALES INVOICE                   │
│ - SELECT BATCH ✅                       │
│ - Batch selector populated ✅          │
│ - Expiry validation on selection ✅    │
│ - Per-product GST rate ✅              │
│ - Batch-level stock deduction ✅       │
│ - Cannot sell expired ✅               │
└─────────────────────────────────────────┘
```

---

## DOCUMENTS FOR REFERENCE

| Document                                             | Purpose                                      | Size  | Key Info                            |
| ---------------------------------------------------- | -------------------------------------------- | ----- | ----------------------------------- |
| [AUDIT_REPORT_COMPLETE.md](AUDIT_REPORT_COMPLETE.md) | Full detailed audit findings per requirement | 7 KB  | All 6 issues with detailed analysis |
| [REFACTORING_PLAN.md](REFACTORING_PLAN.md)           | Before/after code, implementation steps      | 25 KB | Exact code to implement each fix    |
| [AUDIT_SUMMARY.md](AUDIT_SUMMARY.md)                 | Executive summary                            | 4 KB  | High-level overview                 |
| This Guide                                           | Quick reference                              | 3 KB  | Issue grouping & roadmap            |

---

## QUICK START

**To understand the issues:**

1. Read this guide (5 min) ← You are here
2. Read AUDIT_SUMMARY.md (5 min)

**To understand how to fix them:**

1. Read REFACTORING_PLAN.md for your specific issue number(s)
2. Copy the "Corrected Code" section
3. Apply to your codebase

**To implement all fixes:**

1. Follow the Execution Order in this guide
2. Each fix has detailed code examples
3. Estimated time per fix shown

---

**Status:** ✅ AUDIT COMPLETE - Awaiting approval to proceed to Phase 3 (Implementation)
