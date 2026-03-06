# 🏥 PHARMACY MANAGER'S QUICK REFERENCE

## What Changed? (In Simple Terms)

### **Before Phase 2:**

- You manually selected "Intra-state" or "Inter-state" ❌
- You manually entered tax rate for each item ❌
- Tax rates could be wrong ❌
- No profit margin shown ❌

### **After Phase 2:**

- System auto-detects from supplier location ✅
- Tax rate auto-fills from product master ✅
- Multi-rate invoices work (5%, 12%, 18% mixed) ✅
- Profit margin shown for every item ✅

---

## How to Use (Step by Step)

### **Step 1: Open Purchase Invoice Form**

```
Menu → Purchases → Create Purchase Invoice
```

### **Step 2: Select Supplier**

```
✓ System AUTOMATICALLY detects if intra-state or interstate
  (No need to select - it's smart!)
✓ Supplier details appear below
✓ GST number visible
✓ Payment terms auto-fill
✓ Due date auto-calculate
```

### **Step 3: Add First Item**

```
1. Type product name in "Product Name" field
2. You'll see dropdown: "Aspirin (ID:1) GST:5%"
3. Click the product
4. Form auto-fills:
   ✓ Product Name: Aspirin
   ✓ HSN Code: 30041090
   ✓ GST %: 5 (with "(auto)" badge)
   ✓ Batch, Expiry fields ready for input

5. Enter batch number (required): B001
6. Enter manufacturing date (optional if not available)
7. Enter expiry date (required)
8. Enter quantity (required)
9. Enter cost price (required)
10. Enter MRP (required - supplier's quoted price)

11. System calculates:
    ✓ Margin % = (MRP - Cost) / Cost × 100
    ✓ Discount amount (if you enter discount %)
    ✓ Tax (CGST + SGST for intra-state)
    ✓ Line total
```

### **Step 4: Add More Items (Different Tax Rates)**

```
Example: Buy from suppliers with mixed rates

Item 1: Aspirin Strip → Auto-fetched as 5% GST
Item 2: Antibiotic Tab → Auto-fetched as 12% GST
Item 3: Injection → Auto-fetched as 18% GST

All calculated correctly per item!
You don't need to manage this - system handles it.
```

### **Step 5: Review Summary**

```
For Gujarat supplier (Intra-state):
- Subtotal: ₹10,000
- Discount: -₹500
- Taxable: ₹9,500
- CGST: ₹475 (2.5%)
- SGST: ₹475 (2.5%)
- Freight: ₹500
- Grand Total: ₹10,950

For Out-of-State supplier (Interstate):
- Subtotal: ₹10,000
- Discount: -₹500
- Taxable: ₹9,500
- IGST: ₹950 (10%)
- Freight: ₹500
- Grand Total: ₹10,950
```

### **Step 6: Enter Payment Information**

```
Payment Mode: Select from dropdown
  ✓ Credit (most common for wholesale)
  ✓ Cash
  ✓ Bank
  ✓ Cheque

Amount Paid:
  ✗ Leave 0 if not paid (credit)
  ✗ Enter amount if partial payment
  ✓ System auto-calculates Outstanding

Example:
  Grand Total: ₹10,950
  Paid Amount: ₹5,000
  Outstanding: ₹5,950 (shown in yellow)

  This ₹5,950 is what you owe this supplier
```

### **Step 7: Save Invoice**

```
Click: "Save as Draft" → Saved but not approved
Or
Click: "Save & Approve" → Saved and ready

Invoice gets ID, say INV-12345
System creates batches in stock for inventory tracking
```

---

## What System Auto-Does (You Don't Need To)

| What               | System Does                      | You Just             |
| ------------------ | -------------------------------- | -------------------- |
| **GST Type**       | Auto-detects from supplier state | Select supplier      |
| **Tax Rate**       | Auto-fetches from product        | Click product        |
| **Calculations**   | All math done automatically      | Enter basic data     |
| **Margin %**       | Calculated from MRP & Cost       | Leave it alone       |
| **Due Date**       | Calculated from credit days      | Just review          |
| **Batch Tracking** | Creates entries automatically    | Focus on master data |

---

## Quick Tips

### **Tip 1: Different Tax Rates in One Invoice**

You can buy from a supplier with multiple products at different tax rates:

```
Item 1: 5% item (e.g., Aspirin)
Item 2: 12% item (e.g., Antibiotic)
Item 3: 18% item (e.g., Injection)

System handles all calculations. No extra steps needed.
```

### **Tip 2: Multi-Supplier Same Invoice**

❌ Not supported currently

```
One invoice = One supplier
If buying from multiple suppliers → Create separate invoices
```

### **Tip 3: Override Tax Rate**

If supplier gives different rate than product master:

```
Product master says: 5%
Supplier says: 6% (special rate)

System auto-fills 5%, you can change to 6%
Click on field and modify
System recalculates everything automatically
```

### **Tip 4: Batch Numbers**

```
System needs: Unique batch number per product per invoice
Format: Whatever supplier gives (e.g., "B001", "LOT-2026-001")
Purpose: Track which batch expires when
```

### **Tip 5: MRP Entry**

```
This is Supplier's quoted Maximum Retail Price
NOT your selling price (you set that later)
Used to: Calculate margin % (profit visibility)
```

### **Tip 6: Manufacturing Date**

```
Optional field (good practice if supplier provides)
If supplier doesn't give → Leave empty
If supplier gives → Record it (helps with disputes)
```

### **Tip 7: Discount**

```
Can be percent discount: 5%
- If item cost ₹100, discount ₹5
Applied before tax calculation
Tax calculated on: (Cost - Discount)
```

### **Tip 8: Freight Charges**

```
If supplier adds shipping: ₹500
Enter in Freight field
Gets added to Grand Total
Applied after tax (freight itself usually not taxed)
```

### **Tip 9: Round Off**

```
If invoice = ₹10,949.75, you want ₹10,950
Enter Round Off: ₹0.25
Grand Total now = ₹10,950
```

### **Tip 10: Partial Payment**

```
Invoice: ₹10,000
You pay now: ₹6,000 (partial)
Outstanding: ₹4,000 (what you owe supplier)

This outstanding is tracked and shown for creditor management
```

---

## Common Errors & Solutions

### **Error: "Batch number is required"**

**Cause:** You left batch field empty
**Fix:** Enter batch number from supplier: B001, LOT-2026, etc.

### **Error: "Expiry date must be after invoice date"**

**Cause:** You entered expiry before invoice date
**Fix:** Expiry must be in future: e.g., if invoice = 2026-02-17, expiry must be 2026-02-18 or later

### **Error: "Quantity must be greater than 0"**

**Cause:** You entered 0 or negative quantity
**Fix:** Enter positive qty: 5, 10, 25, etc.

### **Error: "MRP must be greater than 0"**

**Cause:** You left MRP empty or entered 0
**Fix:** Enter actual MRP from supplier: 100, 250, 500, etc.

### **Error: "Invoice number already exists for this supplier"**

**Cause:** You entered duplicate bill number
**Fix:** Check supplier's invoice number (should be unique per supplier)

### **GST Type Not Auto-Detecting**

**Cause:** Supplier's state might not be set in master
**Fix:** Go to Supplier Master → Check state field is filled → Try again

---

## Important Reminders

1. **Batch Number is Mandatory** ⚠️
   - Every item must have batch number
   - Needed for stock tracking and recalls

2. **Expiry Date Must Be Valid** ⚠️
   - Cannot be before invoice date
   - Critical for inventory management

3. **MRP is Required** ⚠️
   - Helps calculate margins
   - Used for stock valuation

4. **Save Frequently** ⚠️
   - System saves on submit, not auto-save
   - Draft saved separately from Approved

5. **GST Auto-Detected** ⚠️
   - Don't try to override GST type
   - Based on supplier location
   - If wrong, fix supplier master, not this form

---

## System Behavior

### **When You Select Supplier:**

```
👀 System checks supplier's state
✅ If state = "Gujarat" → Intra-state is auto-set
✅ If state = "Delhi" → Interstate is auto-set
✅ Supplier details card appears with GSTIN
```

### **When You Type Product Name:**

```
👀 System searches product master
✅ Shows dropdown: "Product Name (Tax Rate)"
✅ You select from dropdown
✅ Tax rate auto-fills from product master
```

### **When You Enter Quantity & Cost:**

```
👀 System calculates in real-time
✅ Margin % calculated: (MRP - Cost) / Cost × 100
✅ Tax calculated per item
✅ Line total shown immediately
```

### **When You Enter Paid Amount:**

```
👀 System calculates outstanding
✅ Outstanding = Grand Total - Paid Amount
✅ Color changes: Yellow if outstanding, Green if paid
```

### **When You Save Invoice:**

```
👀 System validates everything
✅ Batch number check
✅ Expiry date check
✅ Uniqueness check
✅ Stock batch created/updated
✅ Invoice ID generated
```

---

## Real-World Example

```
Date: Feb 17, 2026
Supplier: ABC Pharma (State: Gujarat)
→ System auto-sets: Intra-state (CGST + SGST)

Item 1: Aspirin Strip
  - Quantity: 100
  - Batch: B001
  - MFG: Feb 1, 2026
  - Expiry: Feb 1, 2027
  - Cost: ₹50
  - MRP: ₹75 → Margin: 50%
  - Product GST: 5%
  - Result: ₹50 + (₹50 × 2.5% CGST) + (₹50 × 2.5% SGST) = ₹52.50

Item 2: Antibiotic Tab
  - Quantity: 50
  - Batch: B002
  - MFG: Jan 20, 2026
  - Expiry: Jan 20, 2028
  - Cost: ₹100
  - MRP: ₹150 → Margin: 50%
  - Product GST: 12%
  - Result: ₹100 + (₹100 × 6% CGST) + (₹100 × 6% SGST) = ₹112

Total:
- Subtotal: ₹5,000 + ₹5,000 = ₹10,000
- CGST: ₹125 + ₹300 = ₹425
- SGST: ₹125 + ₹300 = ₹425
- Freight: ₹500
- Grand Total: ₹11,350

Payment:
- Paid Now: ₹6,000
- Outstanding: ₹5,350 (Due in 30 days)
```

---

## What Happens After Save

1. ✅ Invoice gets ID number
2. ✅ Batches added to stock
3. ✅ GST details recorded
4. ✅ Payment tracked
5. ✅ Ready for GRN matching (future phase)
6. ✅ Ready for payment reconciliation

---

## Questions?

| Question                        | Answer                                                              |
| ------------------------------- | ------------------------------------------------------------------- |
| **Can I edit after save?**      | Currently saved as Draft or Approved (contact admin for edit rules) |
| **Can I add items after save?** | No, create new invoice                                              |
| **Can I return items later?**   | Yes, via Purchase Return (future feature)                           |
| **How to check outstanding?**   | Dashboard shows creditor balance                                    |
| **How to pay supplier?**        | Payment module (future feature)                                     |
| **Can I generate invoice PDF?** | PDF feature in Phase 3                                              |

---

**You're all set!** 🎉

This system is designed to work smart so you can focus on business decisions, not data entry.
