# Multi-Batch Allocation Testing Guide

## Overview

This guide covers testing the multi-batch allocation feature in the Sales Invoice form. When a customer orders more quantity than available in a single batch, the system automatically allocates the order across multiple batches using FIFO (First In First Out) principle - earliest expiry dates first.

## System Architecture

### Frontend Flow

1. **Product Selection** → Batches loaded and **first batch auto-selected** (FIFO)
2. **Quantity Entry** → If qty > batch available_qty, triggers allocation
3. **Allocation Detection** → Calls `getBatchAllocationPlan.php` AJAX
4. **Auto-Fill Batches** → Creates new rows, fills with allocation data
5. **Allocation Storage** → Stores JSON allocation plan in hidden field

### Backend Flow

1. **Form Submission** → Sends all form data with allocation_plan arrays
2. **Allocation Processing** → `createSalesInvoice.php` processes allocation plans
3. **Per-Batch Items** → Creates one `sales_invoice_item` per batch allocation
4. **Stock Decrement** → Updates `product_batches.available_quantity` per batch
5. **Stock Audit** → Logs `stock_movements` for each batch

## Prerequisites for Testing

### Database Requirements

Ensure you have product batches with:

- Multiple batches per product
- Different expiry dates (for FIFO testing)
- Adequate available quantities
- Some batches with limited stock (for multi-batch testing)

### Sample Test Data

```sql
-- Example: Product with 3 batches
-- Product ID: 5, Product Name: "Amoxicillin 500mg"

-- Batch 1: EARLIEST expiry (should be selected first)
INSERT INTO product_batches
(product_id, batch_number, batch_quantity, available_quantity, mrp, purchase_rate,
 expiry_date, status, created_at)
VALUES
(5, 'BATCH-2026-001', 50, 45, 25, 15, '2026-03-30', 'active', NOW());

-- Batch 2: Medium expiry
INSERT INTO product_batches
(product_id, batch_number, batch_quantity, available_quantity, mrp, purchase_rate,
 expiry_date, status, created_at)
VALUES
(5, 'BATCH-2026-002', 100, 85, 25, 15, '2026-05-30', 'active', NOW());

-- Batch 3: LATEST expiry
INSERT INTO product_batches
(product_id, batch_number, batch_quantity, available_quantity, mrp, purchase_rate,
 expiry_date, status, created_at)
VALUES
(5, 'BATCH-2026-003', 100, 100, 25, 15, '2026-07-30', 'active', NOW());
```

With this data:

- Batch 1 has 45 units, expires earliest (March 30)
- Batch 2 has 85 units, expires in May
- Batch 3 has 100 units, expires latest (July 30)

## Test Scenarios

### TEST 1: Auto-Selection of First Batch (FIFO)

**Objective**: Verify first batch (earliest expiry) is automatically selected when product is selected

**Steps**:

1. Open Sales Invoice Form (`sales_invoice_form.php`)
2. Add Medicine: Select "Amoxicillin 500mg"
3. **EXPECTED**: Batch dropdown auto-selects "BATCH-2026-001" (earliest expiry)
4. **EXPECTED**: Available Qty shows "45"
5. **EXPECTED**: MRP and PTR auto-fill from batch data

**Browser Check**:

- Open DevTools Console (F12)
- Look for "Product selected:" message
- No JavaScript errors should appear

---

### TEST 2: Single Batch Within Available Qty

**Objective**: Verify normal order within single batch doesn't trigger multi-batch allocation

**Steps**:

1. From Test 1 state, enter Quantity: 40 (less than available 45)
2. **EXPECTED**: No allocation warning
3. **EXPECTED**: No new rows created
4. **EXPECTED**: Line total calculates correctly
5. Change Rate to 20
6. **EXPECTED**: Line total = 40 × 20 = 800

**Console Check**:

- No AJAX call should be logged to `getBatchAllocationPlan.php`

---

### TEST 3: Multi-Batch Allocation - Basic

**Objective**: Verify system auto-allocates across multiple batches when qty > single batch

**Setup**:

- Have "Amoxicillin 500mg" form row with Batch 1 (45 available)

**Steps**:

1. Enter Quantity: 100 (exceeds Batch 1's 45 available)
2. **EXPECTED**: Console shows "Allocation plan..." message
3. **EXPECTED**: AJAX call made to `getBatchAllocationPlan.php`
4. **EXPECTED**: New row automatically created
5. **RESULT ROWS**:
   - Row 1: Batch 1 (BATCH-2026-001), Qty: 45
   - Row 2: Batch 2 (BATCH-2026-002), Qty: 55
6. **EXPECTED**: Allocation is FIFO (by expiry date)
7. **EXPECTED**: Sum of allocated quantities = 100

**Console Check**:

- Search for "allocation_plan" in console logs
- Should show allocation plan JSON with 2 entries

---

### TEST 4: Multi-Batch Allocation - Validate JSON Storage

**Objective**: Verify allocation plan is stored in hidden field for form submission

**Steps**:

1. From Test 3, open Browser DevTools (F12)
2. Go to Elements/Inspector tab
3. Search for class `allocation-plan-input`
4. **EXPECTED**: Hidden field contains JSON array:

```json
[
  {"batch_id": 1, "allocated_quantity": 45, ...},
  {"batch_id": 2, "allocated_quantity": 55, ...}
]
```

5. Check both row 1 and row 2 have the same allocation plan stored

**Reason**: When backend processes form, it checks allocation_plan for multiple items per line

---

### TEST 5: Insufficient Stock Warning

**Objective**: Verify system warns when total available qty < required qty

**Changes to Test Data**:

```sql
UPDATE product_batches
SET available_quantity = 10
WHERE batch_number IN ('BATCH-2026-001', 'BATCH-2026-002', 'BATCH-2026-003')
AND product_id = 5;
```

Now: Batch 1 (10), Batch 2 (10), Batch 3 (10) = 30 total available

**Steps**:

1. Select "Amoxicillin 500mg"
2. Enter Quantity: 50 (> 30 available)
3. **EXPECTED**: Alert/Warning message appears:
   - "Stock Allocation Warning:"
   - "Insufficient stock for this product"
4. **EXPECTED**: Rows still created with available allocations:
   - Row 1: 10 from Batch 1
   - Row 2: 10 from Batch 2
   - Row 3: 10 from Batch 3
   - Total: 30 (50 requested but only 30 available)
5. **EXPECTED**: Frontend still allows submission (backend will validate)

---

### TEST 6: Expiring Batch Warning

**Objective**: Verify system warns when batches are expiring soon (<30 days)

**Setup**:

```sql
UPDATE product_batches
SET expiry_date = DATE_ADD(NOW(), INTERVAL 15 DAY)
WHERE batch_number = 'BATCH-2026-001' AND product_id = 5;
-- This batch expires in 15 days (warning threshold < 30 days)
```

**Steps**:

1. Select "Amoxicillin 500mg" (should auto-select Batch 1 with 15-day expiry)
2. Enter Quantity: 100 (triggers multi-batch)
3. **EXPECTED**: Warning includes:
   - "Stock Allocation Warning:"
   - "Batch BATCH-2026-001 expiring in 15 days (Expiring Soon)"
4. Accept allocation and check rows:
   - Batch 1 marked as expiring soon (if visual indicator present)
   - Later batches shown as normal

---

### TEST 7: Form Submission with Allocation

**Objective**: Verify form submission correctly processes multi-batch allocation

**Setup**: Complete allocation from Test 3 (Qty 100 split into 2 batches)

**Steps**:

1. Fill remaining form fields:
   - Payment Terms: 30
   - Invoice Status: PENDING
   - Payment Type: Credit
   - Due Date should auto-calculate
2. Click Save Invoice button
3. **EXPECTED**: Success message appears
4. **EXPECTED**: Redirects to Sales Invoice List
5. **EXPECTED**: New invoice appears in list

**Backend Verification**:

```sql
SELECT
  sii.invoice_item_id,
  si.invoice_number,
  p.product_name,
  pb.batch_number,
  sii.quantity,
  sii.unit_rate,
  sii.line_total
FROM sales_invoice_items sii
JOIN sales_invoices si ON sii.invoice_id = si.invoice_id
JOIN product p ON sii.product_id = p.product_id
JOIN product_batches pb ON sii.batch_id = pb.batch_id
WHERE si.invoice_number = '[YOUR_INVOICE_NUMBER]'
ORDER BY sii.invoice_item_id;
```

**EXPECTED RESULTS**:

- 2 rows (one per batch allocation)
- Row 1: Batch 1, Qty 45, Line Total = 45 × Rate × (1 + GST%)
- Row 2: Batch 2, Qty 55, Line Total = 55 × Rate × (1 + GST%)

---

### TEST 8: Stock Movement Audit

**Objective**: Verify stock movements are logged for each batch

**After Test 7 submission**:

```sql
SELECT
  sm.movement_id,
  pb.batch_number,
  sm.movement_type,
  sm.quantity,
  sm.reference_type,
  sm.reference_id,
  sm.created_at
FROM stock_movements sm
JOIN product_batches pb ON sm.batch_id = pb.batch_id
WHERE sm.reference_type = 'Invoice'
AND sm.reference_id = [INVOICE_ID_FROM_TEST_7]
ORDER BY sm.movement_id;
```

**EXPECTED RESULTS**:

- 2 stock movement records
- Mov 1: Batch 1, Quantity -45, Type "Sales"
- Mov 2: Batch 2, Quantity -55, Type "Sales"
- Both timestamp matching invoice creation time

---

### TEST 9: Batch Quantity Decrement

**Objective**: Verify product_batches.available_quantity was decremented

**Before Test 7**:

- Batch 1: 45 available
- Batch 2: 85 available

**After Test 7 submission**:

```sql
SELECT
  batch_number,
  available_quantity,
  batch_quantity
FROM product_batches
WHERE batch_number IN ('BATCH-2026-001', 'BATCH-2026-002')
AND product_id = 5;
```

**EXPECTED RESULTS**:

- Batch 1: 0 available (was 45, ordered 45)
- Batch 2: 30 available (was 85, ordered 55)

---

### TEST 10: Edit Invoice with Multi-Batch Items

**Objective**: Verify editing invoices with allocation doesn't duplicate allocations

**Steps**:

1. From Test 7, click Edit on created invoice
2. **EXPECTED**: Form loads with allocation data
3. **EXPECTED**: 2 rows shown (one per allocation)
4. Modify Rate from 20 to 22
5. Click Save
6. **EXPECTED**: Update succeeds
7. **VERIFY in DB**:

```sql
SELECT COUNT(*) as item_count, SUM(quantity) as total_qty
FROM sales_invoice_items
WHERE invoice_id = [INVOICE_ID];
-- Should still be 2 items, 100 total qty
```

---

## Edge Case Testing

### Edge Case 1: No Batches Available

**Setup**:

```sql
UPDATE product_batches
SET available_quantity = 0
WHERE product_id = 5;
```

**Steps**:

1. Select product 5
2. **EXPECTED**: No batch options in dropdown (or only "--Select--")
3. Cannot proceed with order

---

### Edge Case 2: Exact Batch Quantity Match

**Setup**:

- Batch 1: 50 available
- Batch 2: 100 available

**Steps**:

1. Select product with Batch 1
2. Enter Quantity: 50 (exact match)
3. **EXPECTED**: No allocation triggered
4. No new rows created
5. Order on single batch only

---

### Edge Case 3: Very Large Quantity Across Many Batches

**Setup**:

- Create 5 batches for one product
- Each with 100 available quantity
- Different expiry dates

**Steps**:

1. Select product
2. Enter Quantity: 450 (needs all 4 batches + partial 5th)
3. **EXPECTED**: System creates multiple rows
4. **EXPECTED**: Batches allocated in FIFO order by expiry
5. **EXPECTED**: All available qty from Batches 1-4, partial from Batch 5

---

## Print Testing

### TEST: Print Invoice with Multi-Batch Items

**Expected Print Layout**:

- Invoice header, client details
- Items table should show:
  - ONE LINE per batch allocation
  - PTR column should NOT be visible (hidden by print CSS)
  - All batch details clearly shown
- Totals and financial summary

**Steps**:

1. Create and submit multi-batch invoice (Test 7)
2. Open saved invoice
3. Click Print button
4. **EXPECTED**: Print preview shows proper format
5. **VERIFY**: PTR column not visible in print
6. **VERIFY**: All allocation rows visible

---

## Performance Testing

### TEST: Large Batch Allocation

**Objective**: Verify system handles 10+ batch allocations efficiently

**Steps**:

1. Create product with 15 batches (each 50 available)
2. Order Quantity: 700 (needs 14 batches)
3. **Expected Performance**:
   - AJAX response < 1 second
   - Rows render smoothly
   - No UI freezing

---

## Troubleshooting Guide

### Issue: New rows not created on allocation

**Debug Steps**:

1. Open DevTools Console (F12)
2. Check for AJAX error messages
3. Verify `getBatchAllocationPlan.php` returns valid JSON
4. Check batch availability (not 0)
5. Verify `addInvoiceRow()` function exists

### Issue: Allocation plan not stored

**Debug Steps**:

1. Inspect HTML element for `.allocation-plan-input`
2. Verify hidden field has value attribute
3. Check JSON in DevTools: Should have `batch_id`, `allocated_quantity`
4. Verify form submission includes `allocation_plan[]` in POST data

### Issue: Stock not decremented after submission

**Debug Steps**:

1. Check `createSalesInvoice.php` for errors in transaction
2. Verify `sales_invoice_items` created per batch
3. Check `product_batches` update queries executing
4. Review error logs for constraint violations

### Issue: Form shows alert for insufficient stock but allows submission

**Expected Behavior**: This is correct! Frontend warns, backend validates

- Frontend: Shows warning for user awareness
- Backend: Final validation before saving
- User can submit even with warning; backend will handle or reject

---

## Success Criteria Checklist

- [✓] Auto-selection of first batch (FIFO by expiry)
- [✓] Multi-batch allocation triggered on qty > batch available
- [✓] FIFO principle applied (earliest expiry first)
- [✓] Allocation plan stored in hidden field as JSON
- [✓] New rows created for each batch allocation
- [✓] Form submission succeeds with multi-batch items
- [✓] Multiple `sales_invoice_items` created per batch allocation
- [✓] Stock movements logged for each allocation
- [✓] Batch available quantities decremented correctly
- [✓] Warnings shown for insufficient/expiring stock
- [✓] Edit mode correctly reloads allocation data
- [✓] Print view hides internal fields (PTR)

---

## Reporting Issues

If any test fails:

1. **Document**: Screenshot of issue + Browser/OS info
2. **Error Details**: What happened vs. expected
3. **Steps to Reproduce**: Exact steps to replicate
4. **Console Logs**: DevTools console errors/warnings
5. **Database State**: Query showing affected data

## Performance Baseline

Expected response times:

- AJAX allocation request: < 500ms
- Form submission: < 1s
- Row creation/rendering: < 100ms per row
- Page load: < 2s

---

**Last Updated**: Current Session
**Version**: 1.0
**Status**: Ready for Testing
