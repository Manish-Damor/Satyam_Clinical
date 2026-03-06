# PTR (Purchase Rate) Feature - Testing Checklist ✓

## Implementation Status: COMPLETE ✅

All database changes and code modifications have been successfully implemented.

---

## Database Changes ✅

- [x] Column added: `order_item.purchase_rate` (DECIMAL 10,2)
- [x] Column exists in `product_batches.purchase_rate` (source of data)
- [x] Schema verified via check_order_item_columns.php

---

## Frontend Changes - add-order.php ✅

### Table Structure

- [x] Table header includes "PTR" column between "Rate" and "Avail."
- [x] PTR column marked with `class="no-print"` (hidden from print)
- [x] Column width: 12%

### Initial Row Input Fields

- [x] PTR display input: `<input id="ptr1" disabled>`
- [x] PTR hidden value input: `<input id="ptrValue1" type="hidden">`
- [x] Fields styled with `no-print` class

### Dynamic Row Addition (addRow function)

- [x] New rows include PTR column with identical structure
- [x] PTR fields generated with correct naming pattern: `ptr{n}`, `ptrValue{n}`
- [x] Hidden values maintain numeric values for posting

### Product Data Population (getProductData function)

- [x] Fetches response.purchase_rate from fetchSelectedProduct.php
- [x] Populates display field: `$("#ptr" + row).val(response.purchase_rate ?? '')`
- [x] Populates hidden field: `$("#ptrValue" + row).val(response.purchase_rate ?? 0)`

---

## Frontend Changes - editorder.php ✅

### Table Structure

- [x] Table header includes "PTR" column (same as add-order.php)
- [x] PTR column marked with `class="no-print"`
- [x] Column width: 12%

### SQL Query Update

- [x] SELECT now includes: `order_item.purchase_rate`
- [x] Correctly joins with product table
- [x] Query retrieves existing PTR values from database

### Initial Row Rendering (Edit Mode)

- [x] PTR display field populated: `value="<?php echo $orderItemData['purchase_rate'] ?? ''; ?>"`
- [x] PTR hidden field populated: `value="<?php echo $orderItemData['purchase_rate'] ?? 0; ?>"`
- [x] Existing order items display their stored PTR values

### Dynamic Row Addition (addRow function)

- [x] New rows include PTR column with identical structure to add-order.php
- [x] PTR input fields generated correctly
- [x] Proper naming convention for form submission

### Product Data Population (getProductData function)

- [x] Populates display field from response.purchase_rate
- [x] Populates hidden field for POST submission
- [x] Same logic as add-order.php for consistency

---

## Backend Changes ✅

### fetchSelectedProduct.php

- [x] Queries product_batches for latest purchase_rate
- [x] Query: `SELECT purchase_rate FROM product_batches WHERE product_id = ? ORDER BY batch_id DESC LIMIT 1`
- [x] Appends `purchase_rate` to JSON response
- [x] Defaults to 0 if no batch found

### order.php (Form Submission Handler)

- [x] Collects `ptrValue[]` from POST data
- [x] Line: `$purchaseRate = $_POST['ptrValue'][$i] ?? 0`
- [x] Includes in items array: `'purchase_rate' => (float)$purchaseRate`
- [x] Passed to SalesOrderController

### SalesOrderController.php (insertOrderItem method)

- [x] SQL INSERT includes purchase_rate column
- [x] Parameter binding includes $purchaseRate value
- [x] Stored in correct position in params array
- [x] Column order: order_id, order_number, product_id, productName, quantity, rate, **purchase_rate**, total, added_date

---

## Sidebar Navigation ✅

- [x] "Invoices" menu label renamed to "Sales Invoice"
- [x] Submenu items updated:
  - "Add Invoice" → "Add Sales Invoice"
  - "Manage Invoices" → "Manage Sales Invoices"

---

## Testing Procedure

### Phase 1: Create New Sales Invoice (add-order.php)

1. **Open Create Invoice Page**
   - Navigate to: http://localhost/Satyam_Clinical/add-order.php
   - Verify sidebar shows "Sales Invoice" > "Add Sales Invoice"
   - ✓ Check page loads without errors

2. **Verify Table Structure**
   - Look for column headers: Medicine | Rate | **PTR** | Avail. | Quantity | Total | Action
   - ✓ Confirm PTR column is visible
   - ✓ Check PTR column width looks reasonable

3. **Select a Product**
   - Click on first row's Medicine field
   - Type medicine name
   - Select from dropdown
   - ✓ Verify Rate field populates
   - ✓ **Verify PTR field populates** ← KEY TEST
   - ✓ Verify Availability shows quantity

4. **Add Quantity and Calculate Total**
   - Enter quantity
   - Tab out or click elsewhere
   - ✓ Verify Total calculates: (Rate × Quantity)
   - ✓ Check PTR value persists

5. **Add Multiple Rows**
   - Click "Add Row" button
   - Select different product in new row
   - ✓ Verify PTR column appears in new row
   - ✓ Verify PTR populates from fetchSelectedProduct
   - ✓ Add 2-3 items total

6. **Complete and Submit Invoice**
   - Fill in client name and contact
   - Enter payment terms
   - Click "Save Invoice" button
   - ✓ Verify success message appears
   - ✓ Verify order number generated

### Phase 2: Verify Data Stored in Database

```sql
-- Check that purchase_rate was stored correctly
SELECT
    o.order_number,
    oi.productName,
    oi.quantity,
    oi.rate,
    oi.purchase_rate,
    (oi.rate - oi.purchase_rate) as margin
FROM orders o
INNER JOIN order_item oi ON o.order_id = oi.order_id
WHERE o.order_id = [LAST_ORDER_ID]
ORDER BY oi.id DESC;
```

- ✓ Verify all PTR values are populated (not NULL, not 0 unless intended)
- ✓ Check margin calculation works: rate - purchase_rate should be positive

### Phase 3: Edit Existing Invoice (editorder.php)

1. **Open Manage Invoices**
   - Navigate to: Sales Invoice > Manage Sales Invoices
   - Or direct: http://localhost/Satyam_Clinical/viewStock.php (adjust link if needed)
   - ✓ Verify page loads

2. **Edit Recent Invoice**
   - Click Edit button on the invoice created in Phase 1
   - ✓ Verify page loads without errors
   - ✓ Verify PTR column appears in table

3. **Verify Existing PTR Values Load**
   - Check each product row
   - ✓ **Verify PTR field is populated with saved value** ← KEY TEST
   - ✓ Check Rate field has value
   - ✓ Verify all fields show correct data

4. **Add New Item to Existing Invoice**
   - Click "Add Row" button
   - Select a product
   - ✓ Verify PTR field populates from product batch
   - ✓ Verify other fields populate correctly

5. **Save Changes**
   - Click "Update Order" or "Save" button
   - ✓ Verify success message
   - ✓ Check database stored new PTR value

### Phase 4: Print Layout Verification

1. **Print from add-order.php**
   - Open an invoice in add-order.php
   - Press Ctrl+P or use browser Print menu
   - ✓ **Verify PTR column does NOT appear in print preview** ← CHECK NO-PRINT CLASS
   - ✓ Verify other columns print correctly

2. **Print from editorder.php**
   - Open edit mode for an invoice
   - Press Ctrl+P
   - ✓ **Verify PTR column hidden in print** ← CHECK NO-PRINT CLASS
   - ✓ Customer should only see: Medicine, Rate, Avail., Qty, Total

### Phase 5: Edge Cases

1. **Product with No Batch**
   - Add a product that has no batches
   - ✓ Verify PTR shows as empty or 0 (not error)
   - ✓ Invoice should still save successfully

2. **Multiple Products**
   - Add 5+ products in one invoice
   - ✓ Verify each has correct PTR
   - ✓ Check database stores all values

3. **Zero PTR Values**
   - If a product has PTR = 0 or NULL
   - ✓ Verify form doesn't error
   - ✓ Check database accepts the value

---

## Expected Database State After Testing

Example of a properly created invoice:

| Order ID | Order Number    | Product           | Rate  | PTR   | Quantity | Total  | Margin |
| -------- | --------------- | ----------------- | ----- | ----- | -------- | ------ | ------ |
| 42       | ORD-240102-0015 | Paracetamol 500mg | 50.00 | 30.00 | 10       | 500.00 | 20.00  |
| 42       | ORD-240102-0015 | Aspirin 100mg     | 75.00 | 45.00 | 5        | 375.00 | 30.00  |

---

## Rollback Instructions (If Needed)

If you need to remove the PTR feature:

```sql
-- Remove the column
ALTER TABLE order_item DROP COLUMN purchase_rate;

-- Revert the SQL queries in editorder.php to not select purchase_rate
-- Revert form fields in add-order.php and editorder.php
```

---

## Success Criteria

- [ ] All items in "Phase 1" pass
- [ ] All items in "Phase 2" pass
- [ ] All items in "Phase 3" pass
- [ ] All items in "Phase 4" pass (print hides PTR)
- [ ] All items in "Phase 5" pass
- [ ] No JavaScript console errors
- [ ] No PHP errors in logs
- [ ] Database stores correct PTR values
- [ ] Can calculate margin: (Rate - PTR) / Rate × 100

---

## Notes

- **PTR = Purchase To Rate** (cost at which medicine was purchased)
- **Margin = (Selling Rate - Purchase Rate) / Selling Rate × 100%**
- PTR is hidden from customer-facing prints (internal cost tracking only)
- PTR comes from latest batch's purchase_rate, not hardcoded

---

## Support Commands

```bash
# Check column exists
SHOW COLUMNS FROM order_item;

# View orders with PTR
SELECT o.order_number, COUNT(oi.id) as items,
       AVG(oi.purchase_rate) as avg_ptr
FROM orders o
LEFT JOIN order_item oi ON o.order_id = oi.order_id
GROUP BY o.order_id
ORDER BY o.order_id DESC LIMIT 10;

# Calculate margins
SELECT
    order_number,
    productName,
    rate,
    purchase_rate,
    ROUND(((rate - purchase_rate) / rate * 100), 2) as margin_percent
FROM order_item oi
INNER JOIN orders o ON oi.order_id = o.order_id
WHERE rate > 0
ORDER BY o.order_id DESC;
```

---

**STATUS: Ready for Testing** ✅

All code changes verified and in place. Begin with Phase 1 testing!
