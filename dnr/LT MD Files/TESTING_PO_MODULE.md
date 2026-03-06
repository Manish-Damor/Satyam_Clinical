# PO Module Testing & Workflow Guide

## Overview

This document provides comprehensive testing steps for the refactored Purchase Order (PO) module. The PO module has been redesigned to:

- **Remove batch-related fields** from PO items (batches are collected only at invoice stage)
- **Implement PO→Invoice conversion** workflow
- **Enable clean procurement flow** without batch collection at PO stage

---

## Test Workflow: PO → Approval → Invoice → Stock

### Pre-requisites

- XAMPP running (`http://localhost/Satyam_Clinical/`)
- Active suppliers in database
- Active products with pricing and HSN codes
- User logged in

---

## TEST PLAN

### **Test 1: Create Purchase Order**

#### Steps:

1. Navigate to **Procurement → Purchase Orders** (or `po_list.php`)
2. Click **Create New PO** button
3. Form should display:
   - PO Number (auto-generated)
   - PO Date (today)
   - Supplier dropdown (required)
   - Expected Delivery Date
   - Delivery Location
   - **Items table with columns:**
     - Medicine Name (searchable)
     - HSN Code (readonly)
     - Pack Size (readonly)
     - ~~Batch Number~~ ❌ (REMOVED - should NOT appear)
     - ~~Expiry Date~~ ❌ (REMOVED - should NOT appear)
     - MRP (readonly)
     - PTR (readonly)
     - Rate/Unit Price (editable)
     - Qty (editable)
     - Discount % (editable)
     - Line Amount (readonly, calculated)
     - Tax % (editable, default 18%)
     - Item Total (readonly, calculated)
     - Delete button

4. **Add items:**
   - Search and select first product
   - Verify HSN, Pack Size, MRP, PTR auto-populate
   - Verify batch/expiry fields are NOT present
   - Enter Qty = 10, Unit Price = 100
   - Click "Add row" button
   - Select second product
   - Enter Qty = 5, Unit Price = 50

5. **Verify calculations:**
   - Line Amount = Qty × Unit Price
   - Item Total = Line Amount + (Line Amount × Tax%)
   - Grand Total should sum all items

6. Click **Save PO** button

#### Expected Result:

✅ PO created successfully without batch fields
✅ Display success message with PO Number and ID
✅ Redirect to PO View page

---

### **Test 2: Approve Purchase Order**

#### Steps:

1. Navigate to **PO List** → Find recently created PO
2. Click **View** button to open PO details
3. Verify PO status shows as **Draft**
4. Click **Approve PO** button
5. Confirm dialog should appear: "Mark this PO as Approved?"
6. Click OK

#### Expected Result:

✅ PO status changes to **Approved**
✅ Page reloads
✅ **Convert to Invoice** button now appears (visible only for Approved POs)
✅ **Approve PO** button disappears

---

### **Test 3: Convert PO to Invoice**

#### Steps:

1. Stay on PO View page (from Test 2)
2. Click **Convert to Invoice** button
3. Confirm dialog: "Convert this PO to Invoice?"
4. Click OK

#### Expected Result:

✅ Success message: "PO converted to invoice successfully"
✅ Browser redirects to **Invoice View** page (purchase_invoices.php?id=X)
✅ Invoice displays:

- New Invoice Number (auto-generated)
- Supplier information
- All items copied from PO with same prices
- Draft status
- Batch Number and Expiry Date fields are NOW visible (for invoice stage)

---

### **Test 4: Verify Invoice has Batch Fields**

#### Steps:

1. On Invoice View page (from Test 3)
2. Look at invoice items table
3. Verify columns include:
   - Batch No (editable now, unlike PO)
   - Manufacture Date (editable)
   - Expiry Date (editable)
   - Other invoice-specific fields

#### Expected Result:

✅ Invoice has full batch entry capability
✅ Batch fields are NOT readonly
✅ Batch fields CAN be edited before approval

---

### **Test 5: Edit Invoice - Add Batch Details**

#### Steps:

1. On Invoice View page
2. Click **Edit Invoice** button
3. For each item, enter:
   - Batch No: (e.g., "BATCH-001")
   - Manufacture Date: (e.g., "01-12-2024")
   - Expiry Date: (e.g., "30-12-2026")
4. Save invoice

#### Expected Result:

✅ Batch details saved successfully
✅ Invoice remains in Draft status

---

### **Test 6: Approve Invoice - Creates Stock**

#### Steps:

1. On Invoice View page (with batch details)
2. Click **Approve Invoice** button
3. Confirm dialog
4. Click OK

#### Expected Result:

✅ Invoice status changes to **Approved**
✅ Stock Batches created in `stock_batches` table
✅ Verification: Go to **Inventory → Stock Batches** and verify:

- New entries for products from this invoice
- Correct batch numbers
- Correct expiry dates
- Quantity matches invoice items

---

### **Test 7: Verify PO Status Updated**

#### Steps:

1. Navigate back to **PO List**
2. Find the PO from Test 1
3. Click View

#### Expected Result:

✅ PO status shows **Converted** (not Approved anymore)
✅ No Convert button visible (already converted)
✅ PO is read-only

---

## Error Scenarios Testing

### **Scenario A: Try to Convert Non-Approved PO**

1. Create a new PO
2. Try to access convert_po_to_invoice.php directly without approving
3. Expected: Error message "PO not found or not approved"

### **Scenario B: Try to Convert PO with No Items**

1. Create PO
2. Don't add any items
3. Approve it
4. Try to convert
5. Expected: Error message "PO has no items"

### **Scenario C: Duplicate Conversion Attempt**

1. Create and approve PO
2. Convert to invoice (creates INV-001)
3. Try to convert same PO again
4. Expected: Error message (PO status is now 'Converted')

---

## Database Validation

### Check Migration Applied:

```sql
-- Verify po_items table structure
DESCRIBE po_items;
-- Should NOT show: batch_number, expiry_date, manufacturing_date
-- Should show: pending_qty, po_id, product_id, quantity_ordered, unit_price, etc.
```

### Check PO to Invoice Conversion:

```sql
-- Count POs created in last hour
SELECT COUNT(*) FROM purchase_orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Count invoices created in last hour
SELECT COUNT(*) FROM purchase_invoices WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Verify items copied correctly
SELECT pi.id, pi.invoice_id, pi.product_id, pi.qty, pi.unit_cost
FROM purchase_invoice_items pi
JOIN purchase_invoices p ON pi.invoice_id = p.id
WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY pi.invoice_id;
```

---

## Performance Metrics

| Operation            | Expected Time                       |
| -------------------- | ----------------------------------- |
| Create PO            | < 2 seconds                         |
| Approve PO           | < 1 second                          |
| Convert PO → Invoice | < 2 seconds                         |
| Approve Invoice      | < 3 seconds (creates stock batches) |

---

## Files Modified

1. **create_po.php** - Removed batch/expiry input rows and JavaScript references ✅
2. **po_list.php** - Added Convert button and AJAX handler ✅
3. **po_view.php** - Added Convert button and AJAX handler ✅
4. **php_action/convert_po_to_invoice.php** - New action handler ✅
5. **migrations/alter_po_items_remove_batch_fields.php** - Schema migration ✅

---

## Sign-off Checklist

- [ ] Test 1: Create PO (no batch fields visible)
- [ ] Test 2: Approve PO (status changes to Approved)
- [ ] Test 3: Convert PO →Invoice (redirect to invoice)
- [ ] Test 4: Invoice has batch fields
- [ ] Test 5: Edit invoice and add batch details
- [ ] Test 6: Approve invoice (stock batches created)
- [ ] Test 7: Verify PO status is "Converted"
- [ ] Error Scenario A: Non-approved PO conversion fails
- [ ] Error Scenario B: Empty PO conversion fails
- [ ] Error Scenario C: Duplicate conversion fails
- [ ] Database validation: Schema correct
- [ ] Database validation: Items copied correctly

---

## Rollback Strategy

If issues found, rollback with:

```bash
# Reset PO status for testing
UPDATE purchase_orders SET po_status = 'Draft' WHERE po_status = 'Converted';

# Or drop and recreate migration
php migrations/alter_po_items_remove_batch_fields.php --rollback
```
