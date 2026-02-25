# 🚀 Test Save Purchase Order - Step by Step

## ✅ Before You Start

Make sure:

- [ ] XAMPP Apache is running
- [ ] XAMPP MySQL is running
- [ ] You have products in the database (at least 1 active product with status = 1)

---

## 📋 Step-by-Step Test

### Step 1: Open Purchase Order Form

Navigate to:

```
http://localhost/Satyam_Clinical/add-purchase-order.php
```

**What you should see:**

- ✅ Form loads with:
  - PO Number (auto-filled)
  - PO Date (today's date)
  - Vendor Name field
  - Vendor Contact field
  - Email, Address fields
  - Delivery Date field
  - Status dropdown
  - Items table (empty initially)
  - "Add Row" button
  - "Save Purchase Order" button

---

### Step 2: Fill Vendor Information

Complete these required fields:

| Field                  | Example Value         |
| ---------------------- | --------------------- |
| PO Date                | (Auto-filled - today) |
| Vendor Name            | ABC Pharma Ltd        |
| Vendor Contact         | 9876543210            |
| Expected Delivery Date | 2026-01-20            |

Leave other fields as default - they're optional.

---

### Step 3: Add Products/Items

1. **Click "Add Row"** button

   - New row appears in Items table
   - Product dropdown shows your products

2. **Select a Product**

   - Click the dropdown
   - Select any product (e.g., "DOLO 650mg")

3. **Enter Quantity**

   - Click Quantity field
   - Enter a number (e.g., 5)

4. **Enter Unit Price**

   - Click Unit Price field
   - Enter price (e.g., 25)
   - Item total should auto-calculate (5 × 25 = 125)

5. **Verify Calculations**
   - Sub Total should update at bottom
   - Discount, GST, Grand Total should calculate

**Add at least 1 item, but you can add more by clicking "Add Row" again**

---

### Step 4: Save the Purchase Order

1. **Click "Save Purchase Order" button**
2. **Expected Alert:** "Purchase Order created successfully"
3. **Expected Redirect:** Browser goes to purchase_order.php
4. **Expected Result:** Your new PO appears in the list

---

## ⚠️ If Something Goes Wrong

### Symptom 1: Nothing Happens When Clicking Save

**Debug Steps:**

1. Open Developer Tools (F12)
2. Click "Console" tab
3. Look for red error message
4. Common errors:
   - "Cannot read property 'productId'" → No items added
   - "Cannot read property 'val()'" → Missing form field
   - Network error → Server not responding

**Fix:**

- Make sure you added at least 1 item
- Make sure all required fields filled
- Restart XAMPP Apache

### Symptom 2: Alert Shows "Error: Please fill all required fields"

**Check:**

- [ ] PO Date filled
- [ ] Vendor Name filled
- [ ] Vendor Contact filled
- [ ] Expected Delivery Date filled
- [ ] At least 1 item added

**If all filled but still shows error:**

- Check browser console for more details
- Restart XAMPP

### Symptom 3: Form Submits But Doesn't Redirect

**Check:**

1. Open Developer Tools (F12)
2. Click "Network" tab
3. Click Save button again
4. Look for "createPurchaseOrder.php" request
5. Click it and check "Response" tab
6. Should see JSON with success:true

**If you see error in response:**

- Note the error message
- Check database in phpMyAdmin
- Verify purchase_orders and po_items tables exist

### Symptom 4: "Error: Invalid server response"

**Means:** Server responded but with invalid JSON

**Fix:**

1. Check browser console
2. See what response was received
3. Could be PHP error message instead of JSON
4. Check Apache error log

---

## 📊 Verify It Worked

### Method 1: Check Purchase Order List

Open:

```
http://localhost/Satyam_Clinical/purchase_order.php
```

Your new PO should appear at the top with:

- ✅ PO Number (auto-generated)
- ✅ PO Date (date you entered)
- ✅ Vendor Name (your vendor)
- ✅ Contact (your contact)
- ✅ Total Amount (calculated from items)
- ✅ Payment Status (Pending)

### Method 2: Check Database Directly

In phpMyAdmin:

1. Select database: **satyam_clinical**
2. Go to "SQL" tab
3. Run this query:

```sql
SELECT * FROM purchase_orders ORDER BY id DESC LIMIT 1;
```

You should see your new record.

4. Run this query to see items:

```sql
SELECT * FROM po_items WHERE po_master_id = (SELECT MAX(id) FROM purchase_orders);
```

You should see your added items.

---

## 🎯 Quick Checklist

Before trying to save, verify:

- [ ] Products exist in database (check DIAGNOSE.php)
- [ ] At least 1 product has status = 1 (active)
- [ ] All required fields filled (Vendor, Date, etc.)
- [ ] At least 1 item added with valid product, qty, price
- [ ] Save button is clicked (should be green/primary color)
- [ ] No JavaScript errors in console (F12 → Console)

---

## 📞 Still Having Issues?

1. **Check SAVE_PO_FIX.md** - Explains what was fixed
2. **Check browser console (F12)** - Shows actual error
3. **Run DIAGNOSE.php** - Verifies system setup
4. **Check database directly** - Verify tables created correctly
5. **Restart XAMPP** - Sometimes helps with connection issues

---

**Last Updated:** January 16, 2026  
**Status:** Ready to Test ✅
