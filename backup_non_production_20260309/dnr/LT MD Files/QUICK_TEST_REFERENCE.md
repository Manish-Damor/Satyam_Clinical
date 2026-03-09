# Quick Testing Reference Card

## ✅ What's Been Completed

1. **Database Migration** - Batch fields removed from po_items table
2. **PO Form Cleanup** - Batch/expiry inputs removed (only at invoice stage)
3. **Convert PO→Invoice** - New action handler created
4. **UI Updates** - Convert buttons added to po_list and po_view
5. **Code Quality** - All files syntax-checked and validated
6. **Automated Test** - End-to-end test passed (2 items, conversion successful)

---

## 🚀 Next Steps: Manual Testing in Browser

### Quick Test Flow (5 minutes)

1. **Open PO List**
   - URL: `http://localhost/Satyam_Clinical/po_list.php`

2. **Create New PO**
   - Click "Create New PO"
   - Select Supplier: "Cipla Limited"
   - Add items: Paracetamol (Qty: 10, Price: 100), Aspirin (Qty: 5, Price: 50)
   - **Verify:** NO batch/expiry fields visible
   - Click "Save PO"

3. **Approve PO**
   - Find created PO in list
   - Click "View"
   - Click "Approve PO"
   - Confirm dialog

4. **Convert to Invoice**
   - On PO view page, click "Convert to Invoice"
   - Confirm dialog
   - **Verify:** Redirects to invoice page with new invoice number

5. **Check Invoice**
   - Verify all items copied with same prices
   - **Verify:** NOW batch/expiry fields ARE visible (different from PO)
   - Can edit batch details and save

6. **Approve Invoice**
   - Click "Approve Invoice"
   - Confirm

7. **Verify Stock Created**
   - Go to: `http://localhost/Satyam_Clinical/viewStock.php`
   - New batch entries should appear for products from invoice

---

## 📋 Testing Checklist

- [ ] PO form shows NO batch fields
- [ ] Invoice form DOES show batch fields
- [ ] Convert button only appears on Approved POs
- [ ] Items copy correctly from PO to Invoice
- [ ] GST calculated correctly on invoice
- [ ] Stock batches created after invoice approval
- [ ] PO status changes to "Converted"

---

## 🔧 If Something Breaks

**Check:**

1. Browser console for JavaScript errors (F12)
2. Database: `SELECT po_status FROM purchase_orders WHERE po_id = X`
3. Database: `DESCRIBE po_items;` (should NOT show batch_number column)
4. Server logs in XAMPP console

**Reset Test Data:**

```sql
DELETE FROM po_items WHERE po_id IN (SELECT po_id FROM purchase_orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR));
DELETE FROM purchase_orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
DELETE FROM purchase_invoices WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

---

## 📞 Key Files to Check

| What           | File                                                |
| -------------- | --------------------------------------------------- |
| PO Creation    | `create_po.php`                                     |
| PO List        | `po_list.php`                                       |
| PO View        | `po_view.php`                                       |
| Convert Logic  | `php_action/convert_po_to_invoice.php`              |
| Schema Changes | `migrations/alter_po_items_remove_batch_fields.php` |

---

## 💡 Key Points to Remember

- **Batch entry is NOW at Invoice stage** (not PO stage)
- **PO only collects:** Product, Qty, Unit Price, Discount
- **Invoice collects:** Batch Number, Manufacture Date, Expiry Date
- **Stock batches created** only after invoice approval
- **Sales invoices pick** specific batches (for FIFO compliance)

---

## ✨ Success Indicators

- ✅ Create PO → no batch fields
- ✅ Approve PO → button changes
- ✅ Convert PO → new invoice created
- ✅ Edit Invoice → batch fields appear
- ✅ Approve Invoice → stock batches created

---

**Status:** Ready for User Acceptance Testing  
**Time to Test:** ~15 minutes  
**Expected Result:** All tests pass ✅
