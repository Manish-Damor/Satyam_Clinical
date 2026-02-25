# 🔧 SYSTEM MAINTENANCE & TROUBLESHOOTING GUIDE

## Overview

This document explains how to maintain the Phase 2 purchase invoice system, troubleshoot issues, and make safe modifications.

---

## 1. Database Structure

### **New Tables Added (Phase 2)**

#### **company_settings**

```sql
Purpose: Store global configuration
Columns:
  - setting_key (VARCHAR): Key name
  - setting_value (VARCHAR): Value
  - description (TEXT): What it does

Current Values:
  - company_state: Gujarat
  - company_gstin: 24AABZZ1234F1Z0
  - tax_authority: GSTIN
```

### **Tables Modified (Phase 2)**

#### **product table** (Added 2 columns)

```
Column Name           Type               Purpose
─────────────────────────────────────────────────
expected_mrp          DECIMAL(14,2)      Supplier's quoted MRP (optional)
gst_rate              DECIMAL(5,2)       Product's standard GST rate (5, 12, 18)
```

**Impact:** When user selects product, system fetches this GST rate automatically

---

#### **purchase_invoices table** (Added 9 columns)

```
Column Name               Type           Purpose
──────────────────────────────────────────────────
company_location_state    VARCHAR(50)    Where company is located (Gujarat)
supplier_location_state   VARCHAR(50)    Where supplier is located (for auto-GST)
gst_determination_type    ENUM           'intrastate' or 'interstate'
supplier_gstin           VARCHAR(30)    Supplier's GSTIN (denormalized)
freight_charges          DECIMAL(14,2)  Shipping/logistics cost
round_off_adjustment     DECIMAL(14,2)  Adjustment for rounding
payment_mode             ENUM           'credit', 'cash', 'bank', 'cheque'
paid_amount              DECIMAL(14,2)  How much paid now
outstanding_amount       DECIMAL(14,2)  How much still owed
```

**Impact:** All invoice calculations now depend on gst_determination_type

---

#### **purchase_invoice_items table** (Added 10 columns)

```
Column Name              Type            Purpose
─────────────────────────────────────────────────────
hsn_code                VARCHAR(20)     Product's HSN code (from product master)
gst_rate_applied        DECIMAL(5,2)    Which rate was applied to this item
margin_percent          DECIMAL(8,2)    Profit margin % = (MRP-Cost)/Cost*100
discount_percent        DECIMAL(5,2)    Discount % given by supplier
discount_amount         DECIMAL(14,2)   Discount amount = Cost × Discount%
cgst_amount             DECIMAL(14,2)   Central GST (intrastate only)
sgst_amount             DECIMAL(14,2)   State GST (intrastate only)
igst_amount             DECIMAL(14,2)   Integrated GST (interstate only)
tax_amount              DECIMAL(14,2)   Total tax (CGST+SGST or IGST)
line_total              DECIMAL(14,2)   Final amount for this item
```

**Impact:** Each item now stores complete tax breakdown for audit trail

---

#### **stock_batches table** (Added 5 columns)

```
Column Name         Type            Purpose
─────────────────────────────────────────
supplier_id         INT             Which supplier provided this
invoice_id          INT             Which invoice created this batch
gst_rate_applied    DECIMAL(5,2)    What tax rate was paid for this batch
created_by          INT             Which user created this
created_at          TIMESTAMP       When batch was created
```

**Impact:** Can now trace batch → invoice → supplier for recall/audit

---

## 2. How Calculations Work (Backend)

### **GST Type Auto-Detection**

```php
// Location Comparison
if (supplier_state == company_state) {
    gst_determination_type = 'intrastate'
    apply_cgst_and_sgst = true
} else {
    gst_determination_type = 'interstate'
    apply_igst_only = true
}
```

### **Per-Item Tax Calculation (Intrastate)**

```
For each item:
  taxable_amount = cost_price - discount_amount
  gst_rate = 5% (or 12% or 18%)

  CGST = taxable_amount × (gst_rate / 2)
  SGST = taxable_amount × (gst_rate / 2)
  tax_amount = CGST + SGST

  line_total = taxable_amount + tax_amount
```

### **Per-Item Tax Calculation (Interstate)**

```
For each item:
  taxable_amount = cost_price - discount_amount
  gst_rate = 5% (or 12% or 18%)

  IGST = taxable_amount × gst_rate
  tax_amount = IGST

  line_total = taxable_amount + tax_amount
```

### **Margin Calculation**

```
margin_percent = ((MRP - cost_price) / cost_price) × 100

Example:
  Cost: ₹100
  MRP: ₹150
  Margin: ((150-100)/100) × 100 = 50%
```

### **Invoice Total Calculation**

```
Step 1: Sum all line_totals = subtotal
Step 2: Subtract discount (if any) = discounted_subtotal
Step 3: Sum all tax_amounts = total_tax
Step 4: Add freight_charges
Step 5: Add/subtract round_off_adjustment
Step 6: = grand_total

Example (Intrastate):
  Item 1 Line Total: ₹5,250 (includes tax)
  Item 2 Line Total: ₹5,600 (includes tax)
  Subtotal: ₹10,850
  Freight: ₹500
  Round Off: -₹5
  Grand Total: ₹11,345

Outstanding = Grand Total - Paid Amount
```

---

## 3. Code Locations

### **Frontend Code (User Interface)**

**File:** `purchase_invoice.php`

**Key Functions:**

```javascript
// 1. Auto-GST Detection (On supplier select)
$('#supplier_id').on('change', function() {
  fetch supplier state from server
  auto-detect gst_determination_type
  update form display
})

// 2. Product Auto-Complete (On product name type)
$('#product_name').autocomplete({
  fetch products with gst_rate
  show "Product Name (GST: X%)"
})

// 3. Product Selection (When user clicks autocomplete)
$('#productList').on('click', 'li', function() {
  auto-populate:
    - product_id
    - hsn_code
    - gst_percent (marked as auto with badge)
})

// 4. Real-time Calculation (When qty/cost/rate changes)
$('[name="quantity"], [name="cost_price"], [name="mrp"]').on('change',
  function() {
    recalcTotals()  // Recalculate entire invoice
})

// 5. Recalculate Totals Function
function recalcTotals() {
  for each row:
    calculate margin_percent = (MRP - cost) / cost * 100
    if gst_determination_type = 'intrastate':
      CGST = cost × (gst_rate/2)
      SGST = cost × (gst_rate/2)
    else (interstate):
      IGST = cost × gst_rate
    line_total = cost + tax

  sum all line_totals
  add freight
  apply round_off
  = grand_total
}

// 6. Form Submission (On Save)
$('#invoiceForm').on('submit', function() {
  validate all required fields
  validate batch dates
  send to create_purchase_invoice.php
})
```

**Where to Modify:** If you want to change calculation logic, modify `recalcTotals()` function (around line 250).

---

### **Backend Code (Business Logic)**

**File:** `php_action/purchase_invoice_action.php`

**Key Functions:**

```php
// 1. Create Invoice (Main Entry Point)
public static function createInvoice($data)
  → Fetch supplier details
  → Validate invoice header
  → Validate invoice items
  → Recalculate all values (backend validation)
  → Begin transaction
  → Insert invoice header
  → Insert all items
  → Update/create stock batches
  → Commit transaction

// 2. Validate Invoice Header
private static function validateInvoiceHeader($data)
  → Check supplier exists
  → Check invoice number is unique per supplier
  → Check dates are valid
  → Return array(isValid, errorMessage)

// 3. Validate Invoice Items
private static function validateInvoiceItems($data)
  → For each item:
    → Check batch number provided
    → Check quantity > 0
    → Check cost > 0
    → Check MRP > 0
    → Check expiry > invoice_date
  → Return array(isValid, errorMessage)

// 4. Recalculate Invoice (Backend Validation)
private static function recalculateInvoice($data)
  → This is the TRUTH SOURCE - recalculates everything from raw data
  → For each item:
    → Fetch product GST rate from database
    → Determine if intrastate or interstate
    → Calculate CGST+SGST or IGST for this item
    → Calculate margin
    → Calculate line_total
  → Sum all items
  → Add freight
  → Calculate grand_total
  → Return calculated array

// 5. Update or Create Stock Batch
private static function updateOrCreateStockBatch($data)
  → Check if (product_id, batch_no) exists
  → If exists: Add quantity to existing batch
  → If not exists: Create new batch with supplier/invoice tracking
```

**Where to Modify:** If you want to change tax logic, modify `recalculateInvoice()` function (around line 150).

---

### **API Endpoint**

**File:** `php_action/create_purchase_invoice.php`

**What it does:**

```php
1. Read JSON from $_POST
2. Pass to PurchaseInvoiceAction::createInvoice()
3. Return JSON response with success/error
```

**Do NOT modify** - it's just a pass-through layer.

---

## 4. Common Maintenance Tasks

### **Task 1: Change Company State**

**Current:** Company state hardcoded in `purchase_invoice.php` line 5

```php
const COMPANY_STATE = 'Gujarat';
```

**To Change:**

1. Open `purchase_invoice.php`
2. Find line: `const COMPANY_STATE = 'Gujarat';`
3. Change to your state
4. Also update `php_action/purchase_invoice_action.php` line 50

**Or Better:** Store in `company_settings` table

```sql
UPDATE company_settings
SET setting_value = 'Delhi'
WHERE setting_key = 'company_state';
```

Then modify code to fetch from database instead of hardcoded constant.

---

### **Task 2: Add New GST Rate**

**Current Rates:** 5%, 12%, 18%

**To Add:** Let's say you need 28% rate

**Step 1:** Update product master

```php
// In product creation form, add to dropdown:
<option value="28">28%</option>
```

**Step 2:** System will auto-detect and calculate

- No other changes needed
- Backend calculates: 14% CGST + 14% SGST (intrastate)
- Backend calculates: 28% IGST (interstate)

---

### **Task 3: Change Tax Calculation Method**

**Current:** CGST = SGST = GST%/2 for intrastate

**To Change:** Different split (e.g., 60% central, 40% state)

**Where to Modify:** `php_action/purchase_invoice_action.php` in `recalculateInvoice()` function

```php
// Current (line 180):
$cgst = $line_cost * ($gst_rate / 200);
$sgst = $line_cost * ($gst_rate / 200);

// Change to (example: 60/40 split):
$cgst = $line_cost * ($gst_rate * 0.6 / 100);
$sgst = $line_cost * ($gst_rate * 0.4 / 100);
```

---

### **Task 4: Add New Payment Mode**

**Current Modes:** cash, credit, bank, cheque

**To Add:** UPI

**Step 1:** Modify table

```sql
ALTER TABLE purchase_invoices
MODIFY payment_mode ENUM('cash', 'credit', 'bank', 'cheque', 'upi');
```

**Step 2:** Update form dropdown

```html
<!-- In purchase_invoice.php -->
<select name="payment_mode">
  <option value="cash">Cash</option>
  <option value="credit">Credit</option>
  <option value="bank">Bank</option>
  <option value="cheque">Cheque</option>
  <option value="upi">UPI</option>
  <!-- Add this -->
</select>
```

---

### **Task 5: Modify Margin Calculation**

**Current:** (MRP - Cost) / Cost × 100

**To Change:** Different formula

**Where to Modify:** `purchase_invoice.php` line 210 in `recalcTotals()` function

```javascript
// Current:
const marginPercent = ((mrp - cost) / cost) * 100;

// Change to:
const marginPercent = ((mrp - cost) / mrp) * 100; // Markup on MRP instead
```

---

## 5. Troubleshooting

### **Problem 1: GST Type Not Auto-Detecting**

**Symptom:** Always shows intrastate, even for out-of-state suppliers

**Cause:** Supplier's state field is empty

**Fix:**

```
1. Go to Supplier Master
2. Check supplier record
3. Ensure "State" field is filled (e.g., "Delhi", "Mumbai")
4. Save supplier
5. Try again in invoice form
```

**Check in Database:**

```sql
SELECT supplier_id, supplier_name, state
FROM suppliers
WHERE state IS NULL OR state = '';
-- Should return 0 rows
```

---

### **Problem 2: Tax Amount Wrong**

**Symptom:** Tax calculated incorrectly (e.g., shows ₹500 but should be ₹250)

**Cause:** Check these possibilities:

1. Wrong GST rate on product
2. Discount applied incorrectly
3. Frontend/backend mismatch

**Debug:**

```
1. Open browser Developer Tools (F12)
2. Go to Network tab
3. Submit invoice form
4. Check request JSON to backend
5. Check response from backend
6. Compare calculations
```

**Query:**

```sql
SELECT invoice_id, item_number, cost_price, gst_rate_applied,
       cgst_amount, sgst_amount, igst_amount, tax_amount
FROM purchase_invoice_items
WHERE invoice_id = 123;
-- Verify calculations match
```

---

### **Problem 3: Batch Not Created**

**Symptom:** Invoice saved but batch doesn't appear in stock

**Cause:** Check these:

1. Stock batch table populated but not displayed in UI
2. Batch record exists but with wrong quantity

**Check:**

```sql
SELECT batch_id, product_id, batch_no, quantity,
       supplier_id, invoice_id
FROM stock_batches
WHERE invoice_id = 123;
```

**Fix:**

```
If records exist: UI doesn't show them - need to query database directly
If no records: Check PHP error logs - batch creation failed
```

---

### **Problem 4: Invoices Submitting Twice**

**Symptom:** Clicking Save creates two invoices

**Cause:** Double-click or form not disabling during submission

**Fix:** In `purchase_invoice.php`, add to submitInvoice() function:

```javascript
// At start of function:
if (isSubmitting) return; // Prevent double submission
isSubmitting = true;

// At end of function (before return):
isSubmitting = false;
```

---

### **Problem 5: Decimal/Rounding Issues**

**Symptom:** Grand total shows ₹10,999.99 instead of ₹11,000

**Cause:** Accumulation of float arithmetic errors

**Fix:** Always use `DECIMAL` type in database (not float)

**Check:**

```sql
SHOW COLUMNS FROM purchase_invoices;
-- Should see: cost_price DECIMAL(14,2), NOT FLOAT
```

If wrong, fix:

```sql
ALTER TABLE purchase_invoices
MODIFY cost_price DECIMAL(14,2);
```

---

### **Problem 6: Outstanding Amount Incorrect**

**Symptom:** Shows wrong outstanding balance

**Cause:** Paid amount not saved or invoice edited

**Fix:**

```sql
-- Check what's stored
SELECT invoice_id, grand_total, paid_amount, outstanding_amount
FROM purchase_invoices
WHERE invoice_id = 123;

-- Verify calculation: outstanding = grand_total - paid
-- If wrong, update:
UPDATE purchase_invoices
SET outstanding_amount = grand_total - paid_amount
WHERE invoice_id = 123;
```

---

## 6. Safe Modification Checklist

**Before modifying code, ensure:**

- [ ] You have database backup
- [ ] You understand where change flows (frontend → backend)
- [ ] You test on copy of invoice first, not production
- [ ] You verify database values match display values
- [ ] You check both intrastate AND interstate invoices
- [ ] You test with multiple GST rates in one invoice
- [ ] You test partial payment scenarios
- [ ] You validate calculations match tax rules

---

## 7. Testing New Changes

### **Test Template**

```
Change: [Describe what you modified]

Test Steps:
1. [ ] Database backup taken
2. [ ] Created test invoice with known values
3. [ ] Verified frontend calculation
4. [ ] Verified backend database storage
5. [ ] Checked database for data integrity
6. [ ] Ran for intrastate supplier
7. [ ] Ran for interstate supplier
8. [ ] Tested with discount
9. [ ] Tested with freight
10. [ ] Tested partial payment

Result: ✓ Pass / ✗ Fail
Issues: [List any problems]
```

---

## 8. Backup & Recovery

### **Daily Backup**

```sql
-- Backup database
mysqldump -u root -p satyam_clinical_new > backup_`date +%Y%m%d_%H%M%S`.sql
```

### **Restore from Backup**

```sql
-- If you accidentally broke something
mysql -u root -p satyam_clinical_new < backup_20260217_120000.sql
```

### **Check Migration Status**

```sql
-- Verify all Phase 2 columns exist
SELECT
  IF(COUNT(*) > 0, 'Company Settings', 'Missing') as status
FROM information_schema.tables
WHERE table_name = 'company_settings';

SELECT
  IF(COLUMN_NAME IS NOT NULL, 'GST Rate Column Exists', 'Missing') as status
FROM information_schema.columns
WHERE  table_name = 'product' AND column_name = 'gst_rate';
```

---

## 9. Performance Tips

### **Add Indexes for Common Queries**

```sql
-- Already added in migration, but if missing:
ALTER TABLE purchase_invoices
ADD INDEX idx_supplier_gst (supplier_id, gst_determination_type),
ADD INDEX idx_created_date (created_date);

ALTER TABLE purchase_invoice_items
ADD INDEX idx_invoice_tax (invoice_id, gst_rate_applied);

ALTER TABLE stock_batches
ADD INDEX idx_supplier_invoice (supplier_id, invoice_id);
```

### **Monitor Query Performance**

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check slow queries
SELECT * FROM mysql.slow_log;
```

---

## 10. Emergency Contacts / Support

**If system breaks:**

1. Check PHP error logs: `C:\xampp\apache\logs\error.log`
2. Check MySQL error logs: `C:\xampp\mysql\data\*.err`
3. Run diagnostic: `DIAGNOSE.php`
4. Restore from backup
5. Contact support team

---

**Remember:** Always test changes in test environment before production!
