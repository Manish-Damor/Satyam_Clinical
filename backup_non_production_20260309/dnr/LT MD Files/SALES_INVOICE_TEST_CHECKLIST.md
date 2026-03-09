# 📝 SALES INVOICE FORM - QUICK TEST GUIDE

**Test Date:** February 24, 2026  
**Objective:** Verify all fixes are working correctly

---

## 🧪 TEST SCENARIO

### Test 1: Medicine Search Dropdown Visibility ✓

**Step 1:** Open `sales_invoice_form.php`  
**Step 2:** In first item row, click "Medicine Name" field  
**Step 3:** Type "Paracetamol" (or any medicine name)

**Expected Result:**

- ✅ Dropdown appears BELOW the input field (not hidden under table)
- ✅ Dropdown shows matching medicines with HSN and GST
- ✅ Dropdown is fully visible and clickable
- ✅ Input field width is NOT affected

**Actual Result:** **********\_\_**********

---

### Test 2: PTR Column Order and Styling

**Step 1:** Look at the table headers

**Expected Result:**

- ✅ Column order: MRP → **PTR (BLACK text, light orange bg)** → Rate
- ✅ PTR heading is clearly readable (BLACK color, NOT dark)
- ✅ PTR data cell has yellow background (#ffe082)
- ✅ PTR column clearly identifies your cost

**Actual Result:** **********\_\_**********

---

### Test 3: Line Total Auto-Calculation

**Step 1:** Select a medicine (from search dropdown)  
**Step 2:** Select a batch  
**Step 3:** Enter Quantity: **100**  
**Step 4:** Rate should auto-fill with batch MRP (or you can change it)  
**Step 5:** Enter Rate: **₹25**  
**Step 6:** Leave Discount at **0%**  
**Step 7:** GST auto-fills at **18%**

**Manual Calculation:**

```
Line Total = (100 × 25) × (1 + 18%)
           = 2,500 × 1.18
           = ₹2,950
```

**Expected Result:**

- ✅ Line Total field shows: **₹2,950.00**
- ✅ Updates immediately when you tab/click away from Rate field
- ✅ Calculation is correct

**Actual Result:** **********\_\_**********  
**Calculated Value:** **********\_\_**********

---

### Test 4: Grand Total Calculation

**Step 1:** Continue from Test 3 (item with ₹2,950 line total)

**Expected in Financial Summary:**

- ✅ Subtotal: ₹2,500 (100 × 25, before tax)
- ✅ Invoice Discount: ₹0 (no discount %)
- ✅ GST Amount: ₹450 (2,500 × 18%)
- ✅ **Grand Total: ₹2,950** (Subtotal + GST)

**Actual Result:**

- Subtotal: **********\_\_**********
- Discount: **********\_\_**********
- GST: **********\_\_**********
- **Grand Total: **********\_\_************

---

### Test 5: Due Date Auto-Calculation

**Step 1:** Select Invoice Date: **2026-02-24**  
**Step 2:** Enter Payment Terms: **30** (days)  
**Step 3:** Click away from field or press Tab

**Expected Result:**

- ✅ Due Date auto-fills: **2026-03-26** (24 Feb + 30 days)
- ✅ Due Date is read-only (user cannot edit)
- ✅ If you change Payment Terms, Due Date updates automatically

**Actual Result:** **********\_\_**********

---

### Test 6: Payment Status Auto-Calculation

**Step 1:** Ensure Grand Total = ₹2,950 (from Test 4)  
**Step 2:** Enter Paid Amount: **₹0**

**Expected:** Payment Status = **UNPAID** (red)

**Step 3:** Enter Paid Amount: **₹1,475** (half)

**Expected:** Payment Status = **PARTIAL** (yellow)

**Step 4:** Enter Paid Amount: **₹2,950** (full)

**Expected:** Payment Status = **PAID** (green)

**Actual Results:**

- Paid ₹0 → Status: **********\_\_********** (Color: **\_\_**)
- Paid ₹1,475 → Status: **********\_\_********** (Color: **\_\_**)
- Paid ₹2,950 → Status: **********\_\_********** (Color: **\_\_**)

---

### Test 7: Multiple Items

**Step 1:** Reset form (click Reset button)  
**Step 2:** Add 3 items with different medicines/batches

**Item 1:**

- Medicine: Paracetamol
- Qty: 100, Rate: ₹20, GST: 18%
- Expected Line Total: ₹2,360

**Item 2:**

- Medicine: Aspirin
- Qty: 50, Rate: ₹15, GST: 5%
- Expected Line Total: ₹787.50

**Item 3:**

- Medicine: Vitamin C
- Qty: 200, Rate: ₹10, GST: 0%
- Expected Line Total: ₹2,000

**Expected Grand Total:**

```
Subtotal = 2,000 + 750 + 2,000 = ₹4,750
Tax = (2,000 × 0.18) + (750 × 0.05) + (2,000 × 0) = ₹360 + ₹37.50 = ₹397.50
Grand Total = 4,750 + 397.50 = ₹5,147.50
```

**Actual Grand Total:** **********\_\_**********

---

### Test 8: Batch Expiry Order

**Step 1:** Add an item with multiple batches available  
**Step 2:** Click batch dropdown

**Expected Result:**

- ✅ Batches listed in order of earliest expiry date first (FIFO)
- ✅ Each shows: Batch# (Exp: DD-MMM-YYYY, Qty: available)
- ✅ Easiest to select correct batch for proper rotation

**Actual Result:** **********\_\_**********

---

### Test 9: Client Credit Display

**Step 1:** Select a client (e.g., "Apollo Pharma" if wholesale)

**Expected Result - Client Info Panel:**

- ✅ Shows: Client Name, Phone, Email
- ✅ Shows: Billing Address, Business Type badge
- ✅ Shows: Credit Limit (e.g., ₹500,000)
- ✅ Shows: Outstanding Balance (e.g., ₹120,000)
- ✅ Shows: Available Credit = Credit Limit - Outstanding (e.g., ₹380,000)
- ✅ Available Credit colored GREEN if positive, RED if negative

**Actual Result:** **********\_\_**********

---

### Test 10: Form Submission

**Step 1:** Fill complete invoice:

- Date: Today
- Payment Terms: 30
- Client: Select any
- Items: Add 2-3 items
- Payment Type: Cash
- Paid Amount: Full amount

**Step 2:** Click "Create Invoice" button

**Expected Result:**

- ✅ Success message shows
- ✅ Redirect to invoice list
- ✅ New invoice visible in list
- ✅ All data saved correctly

**Actual Result:** **********\_\_**********

---

## ✅ FINAL SUMMARY

After completing all tests, check:

| Test                        | Status | Notes |
| --------------------------- | ------ | ----- |
| 1. Search dropdown visible  | ⬜     |       |
| 2. PTR column before Rate   | ⬜     |       |
| 3. Line total calculation   | ⬜     |       |
| 4. Grand total calculation  | ⬜     |       |
| 5. Due date auto-calc       | ⬜     |       |
| 6. Payment status auto-calc | ⬜     |       |
| 7. Multiple items           | ⬜     |       |
| 8. Batch FIFO order         | ⬜     |       |
| 9. Client credit display    | ⬜     |       |
| 10. Form submission         | ⬜     |       |

**Overall Status:** 🟢 ALL WORKING / 🟡 MINOR ISSUES / 🔴 MAJOR ISSUES

---

## 🐛 IF SOMETHING ISN'T WORKING

**Check Console for Errors:**

1. Press F12 (Developer Tools)
2. Click "Console" tab
3. Look for any red error messages
4. Note: **********\_\_**********

**Check Network for Failed AJAX:**

1. Click "Network" tab
2. Perform the action (search, select batch, etc.)
3. Look for any requests with red X
4. Click to see details

---

## 📞 TROUBLESHOOTING

### Search dropdown not showing?

- [ ] Check if searchProductsInvoice.php exists
- [ ] Check browser console for JavaScript errors
- [ ] Verify database has products

### Line total not calculating?

- [ ] Check that qty and rate are filled
- [ ] Verify GST % is set (default 18%)
- [ ] Check console for JavaScript errors

### Grand total showing wrong?

- [ ] Verify all line totals are correct first
- [ ] Check discount % (should be 0 if test says 0)
- [ ] Check that GST % values are set correctly

### Due date not calculating?

- [ ] Ensure both Invoice Date and Payment Terms are filled
- [ ] Payment Terms should be a number (30, 60, etc.)
- [ ] Due date should be read-only (can't edit manually)

---

**Document prepared for thorough testing and validation**  
**Date:** Feb 24, 2026
