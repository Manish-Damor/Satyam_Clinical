# MEDICINE MODULE INSPECTION REPORT

**Date:** February 23, 2026  
**Status:** ⚠️ CRITICAL ARCHITECTURE ISSUES FOUND

---

## 🔴 CRITICAL ISSUE SUMMARY

### **Problem 1: THREE Different Batch/Stock Tables (Data Duplication)**

| Table               | Columns          | Status       | Usage                                                          |
| ------------------- | ---------------- | ------------ | -------------------------------------------------------------- |
| **product_batches** | 14 cols (modern) | Active       | Main production table (used by stock_movements, batch_recalls) |
| **stock_batches**   | 13 cols (legacy) | ⚠️ Duplicate | Legacy/old implementation (should be deprecated)               |
| **medicine_batch**  | ???              | ❌ MISSING   | Referenced in code but DOESN'T EXIST in DB                     |

### **Problem 2: Reference Error in New Modules**

- `sales_invoice_form.php` tries to fetch data from **medicine_batch** table (line ~450)
- But **medicine_batch table DOESN'T EXIST** in database
- This will cause the invoice form to **CRASH** when selecting products with batches
- Currently points to non-existent FK relationships

### **Problem 3: Data Integrity & Consistency**

Multiple tracking tables with overlapping concerns:

- `stock_movements` - Tracks all movements (Purchase, Sales, Adjustment, Return, Damage, Sample, Expiry)
- `batch_sales_map` - Maps sold batches to orders (duplication of sales_invoice_items?)
- `inventory_adjustments` - Separate adjustment tracking
- `batch_recalls` - Batch recall management

**Question:** Are batch sales being tracked in both `batch_sales_map` AND `stock_movements` + `sales_invoice_items`?

---

## 📊 CURRENT DATABASE STRUCTURE

### **Core Tables:**

```
product                  ← Master product data (8 records)
├── product_batches     ← Active batch/stock tracking
├── stock_batches       ← Legacy batch tracking (outdated)
├── stock_movements     ← All stock movement history
├── batch_sales_map     ← Batch-to-sale mapping
├── inventory_adjustments ← Inventory adjustments
├── batch_recalls       ← Batch recall tracking
└── expiry_tracking     ← Expiry date tracking

sales_invoices          ← New ERP invoices
└── sales_invoice_items ← References product_batches.batch_id
    └── product_batches ← Batch info

purchase_invoices       ← Purchase invoices
└── purchase_invoice_items
    └── product         ← Product info
```

### **Integration Problem:**

```
EXPECTED (Modern ERP Design):
sales_invoice_items → product_batches → stock_movements

ACTUAL (Current State):
sales_invoice_form.php → tries medicine_batch (MISSING!)
Old code → stock_batches (legacy)
New code → product_batches (correct)
```

---

## 📁 MEDICINE MODULE FILES & CURRENT STATE

### **1. add_medicine.php** ✅

- **Purpose:** Add new medicines to product master
- **Database:** Writes to `product` table
- **Status:** Functional
- **Uses:** product_name, content, brand_id, category_id, hsn_code, gst_rate, reorder_level

### **2. manage_medicine.php** ✅

- **Purpose:** List and manage medicines
- **Database:** Queries `product` table with LEFT JOIN `product_batches`
- **Shows:** Total stock, active stock, expired stock, batch count, stock status
- **Status:** Functional but queries old schema
- **Query:** Aggregates available_quantity from `product_batches` per product

### **3. manage_batches.php** ⚠️

- **Purpose:** Manage medicine batches
- **Database:** Should manage `product_batches` table
- **Status:** Need to verify - may reference old `stock_batches` or `medicine_batch`
- **Concern:** Unclear which batch table it actually uses

### **4. addProductStock.php** ⚠️

- **Purpose:** Add stock/batch for products
- **Database:** Writes to batch table (need to verify which one)
- **Status:** May create records in wrong table or mix old/new tables
- **Issue:** Likely writes to legacy `stock_batches` instead of `product_batches`

### **5. check_product_batches.php** ✅

- **Purpose:** Verify product batches
- **Database:** Queries `product_batches`
- **Status:** Likely works correctly

### **6. viewStock.php** ⚠️

- **Purpose:** View inventory/stock
- **Database:** Unknown (need to inspect)
- **Status:** May reference wrong tables

---

## 🔴 WHAT WILL BREAK

### **Immediate Issue: Sales Invoice Form**

```
When user selects product in sales_invoice_form.php:
1. Form calls fetchProductInvoice.php (AJAX)
2. Handler tries to query medicine_batch table
3. ERROR: Table doesn't exist!
4. Form crashes, batch dropdown shows nothing
5. User cannot complete invoice creation
```

**Current Code In fetchProductInvoice.php (Around line ~50):**

```php
// Likely query like:
$stmt = $stmt->prepare("SELECT batch_id, batch_number FROM medicine_batch
                       WHERE product_id = ?");

// Result: ERROR - table doesn't exist!
```

**Should Be:**

```php
$stmt = $connect->prepare("SELECT batch_id, batch_number FROM product_batches
                          WHERE product_id = ? AND status = 'Active'");
```

---

## 🏗️ RECOMMENDED RESTRUCTURING PLAN

### **PHASE 1: Consolidate Batch Tables (Fix Duplication)**

**Option A: Use product_batches (RECOMMENDED)**

- ✅ `product_batches` is modern, feature-rich
- ✅ Already has FK relationships set up
- ✅ Already used by stock_movements
- ✅ Has all needed columns (available_qty, reserved, damaged, supplier, purchase_rate, mrp)

**Action:**

1. Archive/backup `stock_batches` data
2. Migrate any necessary data from `stock_batches` → `product_batches`
3. Delete `stock_batches` table
4. Update all code references to use `product_batches` only
5. Remove any `medicine_batch` references

### **PHASE 2: Fix Medicine Module Code**

**Issues to Fix:**

1. `add_medicine.php` - Ensure inserts go to `product` table ✓ (likely OK)
2. `manage_medicine.php` - Verify joins are correct to `product_batches` ✓ (likely OK)
3. `manage_batches.php` - **Determine current behavior** ⚠️
4. `addProductStock.php` - **Fix to use product_batches** ⚠️
5. `viewStock.php` - **Verify correct table** ⚠️

### **PHASE 3: Fix Sales Invoice Integration**

**Critical Fix:**

- `fetchProductInvoice.php` - Update to query `product_batches` instead of `medicine_batch`
- `sales_invoice_form.php` - Verify batch selection works
- Test complete invoice creation workflow

### **PHASE 4: Clean Up Stock Tracking**

**Clarify the tracking flow:**

1. When stock added (via addProductStock.php) → Should create:
   - Entry in `product_batches`
   - Entry in `stock_movements` (movement_type='Purchase')

2. When invoice created (via sales_invoice_form.php) → Should:
   - Create entry in `sales_invoice_items`
   - Create entry in `stock_movements` (movement_type='Sales')
   - Update `product_batches.reserved_quantity` and/or `available_quantity`
   - Ideally also create entry in `batch_sales_map` for traceability

3. Ensure no data duplication between tables

### **PHASE 5: Validation & Views**

Make sure all views work:

- `v_inventory_summary` - Total stock across all batches
- `v_batch_stock_summary` - Per-batch stock details
- `v_batch_expiry_alerts` - Expiry tracking
- `v_low_stock_alerts` - Reorder alerts
- `v_stock_movement_recent` - Movement history

---

## ✅ RECOMMENDED NEXT STEPS (In Order)

### **Step 1: Verify Current State**

- [ ] Check `manage_batches.php` - which table does it actually use?
- [ ] Check `addProductStock.php` - which table does it write to?
- [ ] Check `viewStock.php` - which table does it query?
- [ ] Count records in `stock_batches` vs `product_batches`

### **Step 2: Fix Critical Data Path**

- [ ] Update `fetchProductInvoice.php` to use `product_batches` (not `medicine_batch`)
- [ ] Test sales invoice batch selection works
- [ ] Test complete invoice creation

### **Step 3: Consolidate Tables**

- [ ] Decide: Keep `product_batches` as primary batch table
- [ ] Migrate any data from `stock_batches` if needed
- [ ] Delete `stock_batches` table
- [ ] Update all code references

### **Step 4: Create Medicine Module CRUD**

- [ ] Modern `medicine_list.php` (like clients_list.php pattern)
- [ ] Modern `medicine_form.php` (like clients_form.php pattern)
- [ ] Modern batch management interface
- [ ] Modern stock tracking UI

### **Step 5: Integration Tests**

- [ ] Create medicine → Add batch → Create invoice → Verify stock updated
- [ ] Test stock movements tracking
- [ ] Test inventory summaries
- [ ] Test expiry alerts

---

## 📋 DECISION REQUIRED FROM YOU

### **Question 1: Keep or Fix `stock_batches`?**

- **Option A:** Delete it and use `product_batches` only (RECOMMENDED - cleaner)
- **Option B:** Keep both and clarify which is source of truth (messy)

**My Recommendation:** DELETE `stock_batches` and consolidate on `product_batches`

### **Question 2: Restructure Medicine Module UI?**

- **Option A:** Just fix backend (minimal changes)
- **Option B:** Rebuild medicine module like we did clients & invoices (modern, professional)

**My Recommendation:** Rebuild it modern if we want consistency across app

### **Question 3: Data Flow Priority?**

Should we prioritize:

1. **Fix invoice form batch selection FIRST** (unblock current functionality) ✅ URGENT
2. **Then consolidate batch tables**
3. **Then modernize medicine module UI**
4. **Then integrate stock tracking with invoices**

**My Recommendation:** Fix in this order:

1. Fix `fetchProductInvoice.php` → `product_batches` (5 min) - UNBLOCK INVOICE FORM
2. Test sales invoice works end-to-end (15 min)
3. Then plan medicine module restructuring (1-2 hours)

---

## ACTION ITEMS FOR YOU

**Urgent (Do Now):**

1. Answer: Keep or delete `stock_batches`?
2. Answer: Rebuild medicine module UI modern style or just fix backend?
3. Let me know if you want me to:
   - [ ] Fix the invoice form immediately (5 min fix)
   - [ ] Then restructure medicine module
   - [ ] Or do full medicine module refactor first?

**Then I Will:**

1. Fix `fetchProductInvoice.php` to query `product_batches`
2. Test sales invoice batch selection works
3. Plan medicine module restructuring based on your answers
4. Create modern medicine CRUD module (if you want)

---

**Status:** ⏳ WAITING FOR YOUR DECISION

What would you like to do?
