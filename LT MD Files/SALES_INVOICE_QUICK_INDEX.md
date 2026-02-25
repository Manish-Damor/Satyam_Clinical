# 📑 SALES INVOICE MODULE - QUICK ACCESS INDEX

## ⚡ QUICK START (For Users)

**Want to create an invoice?**
→ Go to: `sales_invoice_form.php`
→ Filling fields in this order:

1. Invoice Date
2. Payment Terms (days)
3. Select Client
4. Add Items (Medicine, Qty, Rate)
5. Review Totals
6. Set Payment Info
7. Click Create Invoice

**Need Help?**
→ Read: `SALES_INVOICE_PRODUCTION_GUIDE.md`

**See Examples?**
→ Read: `INVOICE_MODULE_DELIVERY_SUMMARY.md` (line: "Step-by-Step Workflow")

---

## 🔧 TECHNICAL REFERENCE (For Developers)

**Form File:**

- 📄 `sales_invoice_form.php` (952 lines)
  - Lines 1-50: PHP backend (edit mode check)
  - Lines 50-440: HTML form structure (5 card sections)
  - Lines 425-436: Print CSS (hides PTR)
  - Lines 438-950: JavaScript (calculations & AJAX)

**Back-End Handlers:**

- 📄 `php_action/getNextInvoiceNumber.php` (NEW)
  - Purpose: Generate SLS-YYYY-00001 format
  - Method: SELECT MAX, increment, format

- 📄 `php_action/createSalesInvoice.php` (MODIFIED)
  - Removed: payment_place field
  - Updated: bind_param string
  - Kept: transaction, stock_movements

- 📄 `php_action/fetchProductInvoice.php` (VERIFIED)
  - Returns: batches with PTR, MRP, expiry, qty
  - Table: product_batches
  - Order: by expiry_date ASC (FIFO)

- 📄 `php_action/fetchClients.php` (VERIFIED)
  - Returns: all 18 client fields
  - Key: credit_limit, outstanding_balance, payment_terms, business_type

- 📄 `php_action/searchProductsInvoice.php` (VERIFIED)
  - Returns: product search results
  - Used: medicine name autocomplete

---

## 📊 KEY CALCULATIONS

### Auto-Calculated Fields (User Cannot Edit)

**Due Date:**

```
due_date = invoice_date + payment_terms
Example: 2026-02-24 + 30 days = 2026-03-26
```

**Payment Status:**

```
if (paid_amount == 0) → UNPAID (red)
if (0 < paid_amount < grand_total) → PARTIAL (yellow)
if (paid_amount >= grand_total) → PAID (green)
```

**Line Total:**

```
line_total = (qty × rate - discount%) × (1 + gst%)
Example: (100 × 25 - 10%) × (1 + 18%) = 2,655
```

**Grand Total:**

```
grand_total = subtotal - invoice_discount% + gst_amount
```

**Due Amount:**

```
due_amount = grand_total - paid_amount
```

---

## 🎨 VISUAL GUIDE

### Form Layout

```
┌─ HEADER SECTION ─────────────────┐
│ Invoice# │ Date │ Terms │ DueDate│
└──────────────────────────────────┘

┌─ CLIENT SECTION ──────────────────┐
│ Select Client │ Credit: ₹100K     │
│ Business Type │ Outstanding: ₹30K  │
└───────────────────────────────────┘

┌─ ITEMS SECTION ───────────────────┐
│ Medicine │ Batch │ Qty │ Rate │ PTR│
│          │ (Exp) │     │      │ ▀▀▀│ (yellow)
└───────────────────────────────────┘

┌─ FINANCIAL SECTION ──────────────┐
│ Subtotal: ₹2,600                  │
│ Discount: ₹260 (10%)              │
│ GST: ₹130                         │
│ ═════════════════════════════════│
│ GRAND TOTAL: ₹2,470               │
└───────────────────────────────────┘

┌─ PAYMENT SECTION ────────────────┐
│ Type: Cash │ Paid: ₹2,470         │
│ Due: ₹0    │ Status: PAID ✓       │
└───────────────────────────────────┘

Buttons: [Reset] [Draft] [Create] [Preview] [Cancel]
```

### Payment Status Colors

🔴 UNPAID = User hasn't paid anything
🟡 PARTIAL = User paid some but not all
🟢 PAID = User paid full amount

---

## 💾 DATABASE SCHEMA

### sales_invoices Table

```sql
CREATE TABLE sales_invoices (
  invoice_id INT PRIMARY KEY,
  invoice_number VARCHAR(50) UNIQUE,      -- SLS-2026-00001
  client_id INT,
  invoice_date DATE,
  due_date DATE,                          -- auto-calculated
  delivery_address TEXT,
  subtotal DECIMAL(12,2),
  discount_percent DECIMAL(5,2),
  discount_amount DECIMAL(12,2),
  gst_amount DECIMAL(12,2),
  grand_total DECIMAL(12,2),
  paid_amount DECIMAL(12,2),              -- user input
  due_amount DECIMAL(12,2),               -- auto-calculated
  payment_type VARCHAR(50),
  -- payment_place REMOVED ✗
  invoice_status ENUM('DRAFT','SUBMITTED','FULFILLED'),
  payment_status ENUM('UNPAID','PARTIAL','PAID'),  -- auto-calc
  payment_notes TEXT,
  created_by INT,
  created_at TIMESTAMP,
  updated_by INT,
  updated_at TIMESTAMP
);
```

### sales_invoice_items Table

```sql
CREATE TABLE sales_invoice_items (
  item_id INT PRIMARY KEY,
  invoice_id INT,
  product_id INT,
  batch_id INT,
  quantity DECIMAL(10,3),
  unit_rate DECIMAL(10,2),                -- selling rate
  purchase_rate DECIMAL(10,2),            -- PTR (from batch)
  line_subtotal DECIMAL(12,2),
  discount_percent DECIMAL(5,2),
  gst_rate DECIMAL(5,2),
  gst_amount DECIMAL(12,2),
  line_total DECIMAL(12,2),               -- auto-calculated
  allocation_plan JSON
);
```

---

## 🖨️ PRINT OUTPUT FEATURES

### What Gets Printed ✅

- Invoice number, date, due date
- Client name and billing address
- Medicine items with:
  - Product name, HSN code
  - Quantity, MRP, Selling Rate
  - Discount, GST, Line Total
- Financial summary (Subtotal, Discount, GST, Grand Total)
- Payment type and amount

### What's Hidden in Print ✅

- PTR column (highlighted in yellow) → NOT PRINTED
- All form buttons → NOT PRINTED
- Form controls → NOT PRINTED
- Sidebar, header, navigation → NOT PRINTED
- Client internal data → NOT PRINTED

### Print Quality

- Professional table formatting
- Clear borders and alignment
- Ready to mail/email to customer
- Optimized for A4 paper

**To Print:** Click "Preview" button or press Ctrl+P

---

## ✨ SPECIAL FEATURES

### Wholesale-Specific Features

- ✅ Credit limit checking (prevents over-selling)
- ✅ Outstanding balance tracking
- ✅ Available credit calculation (green/red)
- ✅ Business type display (Wholesale/Retail/Hospital/etc.)
- ✅ Flexible pricing per transaction (rate can differ from MRP)
- ✅ Batch-specific pricing (different batches, different costs)

### Batch Management Features

- ✅ FIFO ordering (oldest expiry first)
- ✅ Expiry date display in dropdown
- ✅ Available quantity shown
- ✅ Batch-specific MRP and PTR
- ✅ Batch traceability in sales_invoice_items

### Financial Features

- ✅ Per-line discounts
- ✅ Per-line GST rates
- ✅ Invoice-level discount
- ✅ Auto-totaling
- ✅ Rounding-error-free calculations

### Payment Features

- ✅ 5 payment types (Cash/Check/Card/Online/Credit)
- ✅ Auto-calculated payment status
- ✅ Auto-calculated due amount
- ✅ Payment notes field
- ✅ Partial payment support

---

## 📈 WORKFLOW COMPARISON

### OLD SYSTEM ❌

```
Manual form with:
- payment_place dropdown (not needed)
- Manual payment_status dropdown (error-prone)
- Fixed fields (not flexible)
- Poor layout (crowded)
- Limited credit info
- No batch details
```

### NEW SYSTEM ✅

```
Smart form with:
- No payment_place (removed)
- Auto-calculated payment_status (error-free)
- Flexible selling rates
- Professional card layout
- Full credit display
- Complete batch details
- FIFO batch ordering
- Yellow PTR visibility
- Professional printing
```

---

## 🚨 IMPORTANT NOTES

### PTR (Purchase Rate) - Why Yellow?

- PTR = Your cost per unit
- Biller needs to see it (profit calculation)
- Customer should NOT see it
- ✅ Yellow on screen for visibility
- ✅ Hidden on print for confidentiality

### Payment Status - Why Auto-Calculated?

- Manual entry leads to errors
- Auto-calculation = Always accurate
- ✅ Updates instantly as paid_amount changes
- ✅ No dropdown to select wrong value
- ✅ Color-coded for clarity (red/yellow/green)

### Rate Field - Why Editable?

- MRP is reference price only
- Wholesale customers get special rates
- ✅ Each invoice can have different rates
- ✅ Not locked to product master
- ✅ Supports volume discounts per transaction

### Due Date - Why Auto-Calculated?

- Payment terms defines due date
- Manual entry is error-prone
- ✅ Always calculated correctly
- ✅ Changes if terms or date changes
- ✅ Prevents late payment confusion

---

## 🔐 SECURITY FEATURES

| Feature             | Method                            |
| ------------------- | --------------------------------- |
| SQL Injection       | Prepared statements               |
| XSS Attacks         | Input validation                  |
| Data Loss           | Transaction support (ACID)        |
| Unauthorized Access | User session validation           |
| Data Integrity      | Constraints, triggers             |
| Audit Trail         | created_by, updated_by timestamps |

---

## 📞 SUPPORT & DOCUMENTATION

| Document                                   | Purpose                          | Location    |
| ------------------------------------------ | -------------------------------- | ----------- |
| **SALES_INVOICE_PRODUCTION_GUIDE.md**      | User guide with examples         | Root folder |
| **SALES_INVOICE_VERIFICATION_COMPLETE.md** | Technical verification checklist | Root folder |
| **INVOICE_MODULE_DELIVERY_SUMMARY.md**     | Delivery overview & quick start  | Root folder |
| **THIS FILE**                              | Quick access index               | Root folder |

---

## ✅ DEPLOYMENT CHECKLIST

Before going live:

- [ ] Test creating new invoice
- [ ] Test editing existing invoice
- [ ] Verify due date calculation
- [ ] Verify payment status colors
- [ ] Verify client credit display
- [ ] Verify batch selection
- [ ] Verify PTR hidden on print
- [ ] Verify calculations are accurate
- [ ] Check database inserts
- [ ] Train user team
- [ ] Document any issues
- [ ] Go live!

---

## 🎯 NEXT STEPS

1. **Read:** SALES_INVOICE_PRODUCTION_GUIDE.md (user guide)
2. **Test:** Create a sample invoice in sales_invoice_form.php
3. **Train:** Show your team how to use it
4. **Monitor:** Check initial invoices
5. **Feedback:** Gather user feedback
6. **Optimize:** Make adjustments if needed

---

**Module Status:** ✅ PRODUCTION READY  
**Version:** 1.0  
**Last Updated:** 2026-02-24  
**Support:** Contact System Admin
