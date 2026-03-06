# 📋 PRODUCTION-READY SALES INVOICE MODULE - COMPLETE GUIDE

## ✅ DEPLOYMENT SUMMARY

**Date:** February 24, 2026  
**Status:** ✅ PRODUCTION READY  
**Module:** Sales Invoice (Create & Edit)

---

## 🎯 KEY FEATURES IMPLEMENTED

### 1. **Invoice Header Section (Reorganized)**

- **Invoice Number:** Auto-generated (Format: `SLS-YYYY-XXXXX`)
- **Invoice Date:** User selectable
- **Payment Terms (Days):** User can set manually (e.g., 30, 60, 90 days)
- **Due Date:** AUTO-CALCULATED from invoice date + payment terms
- **Invoice Status:** Draft/Submitted/Fulfilled

**Example:** If Invoice Date = 2026-02-24 and Payment Terms = 30 days → Due Date = 2026-03-26

### 2. **Client Information Display (Professional Layout)**

Shows comprehensive client info organized in two panels:

**Left Panel - Billing Address:**

- Client name, phone, email
- Complete billing address with city/state/postal code

**Right Panel - Business & Credit Information:**

- Business Type (with color badge: Green=Wholesale, Blue=Retail)
- GSTIN number
- Credit Limit (₹)
- Outstanding Balance (₹)
- **Available Credit** (Credit Limit - Outstanding) in green

**Wholesale Feature:** For wholesale clients with credit accounts, the system shows credit limits and warnings if invoice exceeds available credit.

### 3. **Medicine Item Selection (Enhanced UI)**

**Table Columns:**

1. **Medicine Name** - Searchable field with autocomplete
2. **HSN Code** - Auto-filled from product master
3. **Batch (Expiry)** - Dropdown shows batches with expiry dates
4. **Available Qty** - Shows quantity in selected batch (in blue)
5. **Quantity** - How much to sell
6. **MRP** - List price from batch (read-only, for reference)
7. **Rate** - **SELLING RATE** (user editable, overrides MRP)
8. **PTR** - **Purchase Rate** (in **YELLOW**, visible to biller only, hidden in print)
9. **Disc %** - Line discount percentage
10. **GST %** - GST rate (default 18%)
11. **Line Total** - Auto-calculated (Qty × Rate + GST - Discount)
12. **Action** - Delete row button

### 4. **Batch Selection Features**

- Dropdown shows: `Batch Number (Exp: DD-MMM-YYYY, Qty: XXX)`
- Sorted by expiry date (oldest first for FIFO)
- When batch selected:
  - Available quantity updates
  - MRP and PTR auto-populate
  - All calculations recalculate automatically

### 5. **Financial Summary Section**

- **Subtotal:** Sum of all (Qty × Rate) for all items
- **Line-level Discounts:** Already applied at item level
- **Invoice-level Discount (%):** Optional additional discount on entire subtotal
- **Discount Amount:** Calculated automatically
- **GST Amount:** Total tax from all items
- **Grand Total:** Subtotal - Discounts + GST (BOLD & BIG for visibility)

### 6. **Payment Details (Smart Auto-Calculation)**

**Payment Type Options:**

- 💵 Cash
- 🏦 Cheque
- 💳 Card
- 🌐 Online Transfer
- 📋 Credit (for wholesale clients)

**Payment Status - AUTO-CALCULATED based on this logic:**

```
if (Paid Amount = 0) → Status = "UNPAID" (red)
if (0 < Paid Amount < Grand Total) → Status = "PARTIAL" (yellow)
if (Paid Amount ≥ Grand Total) → Status = "PAID" (green)
```

**NO MANUAL DROPDOWN!** Payment Status updates automatically as user changes the Paid Amount.

**Payment Notes:** Optional field for cheque number, reference details, etc.

### 7. **Action Buttons (Bottom of Form)**

- **🔄 Reset** - Clear all form data (with confirmation)
- **💾 Save as Draft** - Saves invoice as DRAFT status for later editing
- **✅ Create Invoice** - Final submission (on create mode)
- **👁️ Preview** - Opens print preview (Ctrl+P)
- **❌ Cancel** - Go back to invoice list

---

## 🖨️ PRINT FEATURES

### What's Hidden in Print (Internal Use Only):

- PTR (Purchase Rate) column - NOT printed
- Action buttons and form controls
- Client metadata (internal address details)
- PTR background color (yellow) not visible

### What's Shown in Print (Customer View):

- Invoice number, date, due date
- Client billing address
- Medicine items with MRP, Selling Rate, Qty, Total
- Financial summary
- Payment details
- Professional layout for mailing/submission

**Print Trigger:** Click "Preview" button or press Ctrl+P

---

## 💡 CALCULATION EXAMPLES

### Example 1: Wholesale with Multiple Batches & Credit

```
CLIENT: Apollo Pharma (Wholesale)
Credit Limit: ₹500,000
Outstanding: ₹120,000
Available: ₹380,000 ✓

LINE 1: Paracetamol 500mg
  Batch: PCM-202602-001 (Exp: 2026-05-23)
  Batch MRP: ₹18, Batch PTR: ₹12
  Selling Rate: ₹16 (custom wholesale rate)
  Qty: 100
  Line Subtotal: 100 × ₹16 = ₹1,600
  GST (5%): ₹80
  Line Total: ₹1,680

LINE 2: Aspirin 400mg
  Batch: ASP-202602-002 (Exp: 2026-08-15)
  Batch MRP: ₹21, Batch PTR: ₹14
  Selling Rate: ₹20
  Qty: 50
  Line Subtotal: 50 × ₹20 = ₹1,000
  GST (5%): ₹50
  Line Total: ₹1,050

FINANCIAL SUMMARY:
  Subtotal: ₹2,600
  Invoice Discount (10%): ₹260
  GST Total: ₹130
  ↓↓↓ GRAND TOTAL: ₹2,470

PAYMENT:
  Type: Credit
  Paid Amount: ₹0
  Due Amount: ₹2,470
  Status: UNPAID ← Auto-calculated

CREDIT CHECK: ₹2,470 < ₹380,000 available ✅ OK
```

### Example 2: Retail with Full Payment

```
CLIENT: Sunrise Pharmacy (Retail)
No credit = immediate payment required

LINE 1: Vitamin C Tablet
  Qty: 200
  MRP: ₹25
  Selling Rate: ₹25
  Line Total: ₹5,000

GRANDTOTAL: ₹5,000

PAYMENT:
  Type: Cash
  Paid Amount: ₹5,000
  Due Amount: ₹0
  Status: PAID ← Auto-calculated immediately
```

---

## 🔧 TECHNICAL DETAILS

### Backend Handlers:

| File                         | Purpose                                 | Updated    |
| ---------------------------- | --------------------------------------- | ---------- |
| `createSalesInvoice.php`     | Create new invoice                      | ✅ Yes     |
| `updateSalesInvoice.php`     | Update existing invoice                 | ✅ Yes     |
| `fetchClients.php`           | Load all clients with credit info       | ✅ Yes     |
| `searchProductsInvoice.php`  | Search products for autocomplete        | ✅ Yes     |
| `fetchProductInvoice.php`    | Fetch product batches with pricing      | ✅ Yes     |
| **getNextInvoiceNumber.php** | ** NEW** - Generate next invoice number | ✅ Created |

### Database Columns Used:

```sql
CREATE TABLE sales_invoices (
  invoice_id INT PRIMARY KEY,
  invoice_number VARCHAR(50) UNIQUE,
  client_id INT,
  invoice_date DATE,
  due_date DATE,
  delivery_address TEXT,
  subtotal DECIMAL(12,2),
  discount_amount DECIMAL(12,2),
  discount_percent DECIMAL(5,2),
  gst_amount DECIMAL(12,2),
  grand_total DECIMAL(12,2),
  paid_amount DECIMAL(12,2),
  due_amount DECIMAL(12,2),
  payment_type VARCHAR(50),
  -- REMOVED: payment_place (no longer used)
  invoice_status ENUM('DRAFT','SUBMITTED','FULFILLED'),
  payment_status ENUM('UNPAID','PARTIAL','PAID'),
  created_by INT,
  created_at TIMESTAMP,
  -- ... audit fields ...
);
```

**Note:** `payment_place` column has been removed from form. If it exists in DB, it will be automatically set to NULL.

### Client Credit Info Fields:

```sql
SELECT
  client_id,
  name,
  business_type,          -- Retail/Wholesale/Hospital/Clinic/Distributor
  credit_limit,           -- Max credit allowed
  outstanding_balance,    -- How much they currently owe
  payment_terms,          -- Default payment terms in days
  gstin,                  -- Tax ID
  -- ... and other address fields ...
FROM clients
```

---

## 🎨 UI/UX FEATURES

### Color Coding:

- **PTR Column:** Yellow background (`#ffe082`) - for identification
- **Grand Total:** Bold, large font, prominent display
- **Available Credit:** Green text if positive, red if negative
- **Payment Status:**
  - Green background if PAID
  - Yellow background if PARTIAL
  - Red background if UNPAID
- **Batch Selection:** Shows expiry dates in format (Exp: DD-MMM-YYYY)

### Responsive Design:

- Form works on desktop, tablet, and mobile
- Table scrolls horizontally on small screens
- All inputs have appropriate touch-friendly sizing

### Auto-Calculations (Real-time):

- Press Tab or click elsewhere to trigger automatic calculations
- All totals update as you type quantity, rate, GST, discount
- Due date auto-calculates when payment terms change
- Payment status updates instantly when paid amount changes

---

## 📝 USAGE WORKFLOW

### Step 1: Invoice Header

1. Invoice Number (auto-generated) ✓
2. Select Invoice Date
3. Set Payment Terms in days (e.g., 30)
4. Due Date auto-fills
5. Select Status (usually leave as DRAFT initially)

### Step 2: Client Selection

1. Click "Select Client" dropdown
2. Type client name or code to find
3. Select from list
4. **Billing address and credit info display automatically**

### Step 3: Add Items

1. Click "Add Item" button to add row
2. In "Medicine Name" field:
   - Type medicine name (e.g., "Paracetamol")
   - Click on matching suggestion
   - HSN Code auto-fills
3. In "Batch" dropdown:
   - Select batch with expiry date
   - Available quantity shows
   - MRP and PTR auto-populate
4. Enter **Selling Rate** (user's custom price)
5. Enter Quantity needed
6. Adjust GST % if needed
7. Adjust discount % if needed
8. **Line Total calculates automatically**
9. Repeat for more items or click "Add Item"

### Step 4: Review Financial Summary

- Check Subtotal, Discounts, GST
- Verify Grand Total
- (Optional) Apply invoice-level discount %

### Step 5: Payment Details

1. Select Payment Type (Cash/Cheque/Credit/etc.)
2. Enter Paid Amount
3. **Payment Status auto-calculates**
4. **Due Amount auto-calculates**
5. Add payment notes if needed (cheque number, reference, etc.)

### Step 6: Submit or Save

- Click **"Save as Draft"** to save without finalizing
- Click **"Create Invoice"** to finalize and create in system
- Click **"Preview"** to see print version
- Drafts can be edited later

---

## ⚠️ IMPORTANT NOTES

### Payment Place Field Removed ✅

- Previously: "In India" / "Out Of India"
- Reason: For domestic wholesale pharmacy, all are in India
- GST determination handled separately in PO module
- **Form no longer sends this field**

### Payment Status Auto-Calculation ✅

- No more manual dropdown selection
- Automatically calculated from paid_amount vs grand_total
- More accurate and prevents data entry errors
- Displayed as read-only field with color coding

### Batch-Specific Pricing ✅

- Each batch can have different MRP and PTR
- MRP = Manufacturer's Recommended Price (from batch)
- PTR = Purchase Rate (your cost, from batch)
- Selling Rate = What you charge (user can override MRP)

### PTR Visibility ✅

- Shown to biller (yellow column)
- HIDDEN in print (customers don't see your cost)
- Helps invoice creator see profit margin at a glance

### Credit Limit Warning ✅

- For wholesale clients, shows available credit
- Works with outstanding_balance from clients table
- Helps prevent over-selling on credit

---

## 🚀 DEPLOYMENT CHECKLIST

- [x] Form HTML redesigned and reorganized
- [x] Payment terms calculation (Due Date = Invoice Date + Terms)
- [x] Payment status auto-calculation (UNPAID/PARTIAL/PAID)
- [x] Payment place field removed
- [x] Client credit information display
- [x] Batch selection with expiry dates
- [x] PTR visibility (yellow) + hidden in print
- [x] Rate (selling price) editable per item
- [x] All calculations re-verified
- [x] Print stylesheet updated (hides internal data)
- [x] Backend handlers updated (createSalesInvoice.php)
- [x] New handler created (getNextInvoiceNumber.php)
- [x] Form validation
- [x] Error handling
- [x] Mobile responsive design

---

## 📞 QUICK REFERENCE

### Form Fields Summary:

```
HEADER:
├─ Invoice Number (auto)
├─ Invoice Date (user)
├─ Payment Terms Days (user)
├─ Due Date (auto)
└─ Status (Draft/Submitted/Fulfilled)

CLIENT:
├─ Select Client (required)
├─ Billing Address (auto-populate)
├─ Business Type (info only)
├─ Credit Limit (wholesale info)
├─ Outstanding Balance (credit tracking)
└─ Delivery Address (optional)

ITEMS (multiple rows):
├─ Medicine Name (search)
├─ HSN Code (auto)
├─ Batch (dropdown with expiry)
├─ Available Qty (info)
├─ Quantity (user input, required)
├─ MRP (reference, read-only)
├─ Selling Rate (user input, required)
├─ PTR (reference, yellow, internal only)
├─ Discount % (line level)
├─ GST % (default 18%)
└─ Line Total (auto-calc)

FINANCIAL:
├─ Subtotal (auto-calc)
├─ Discount Amount (auto-calc)
├─ Discount % Invoice Level (optional)
├─ GST Amount (auto-calc)
└─ GRAND TOTAL (bold display)

PAYMENT:
├─ Payment Type (Cash/Cheque/Card/Online/Credit)
├─ Paid Amount (user input)
├─ Due Amount (auto-calc)
├─ Payment Status (auto-calc, display only)
└─ Payment Notes (optional)

BUTTONS:
├─ Reset (clear all)
├─ Save as Draft (DRAFT status)
├─ Create Invoice (finalize)
├─ Preview (print view)
└─ Cancel (go back)
```

---

**Version:** 1.0  
**Last Updated:** 2026-02-24  
**Status:** ✅ PRODUCTION READY  
**Tested:** Yes  
**Mobile:** Responsive  
**Print:** Professional

---

## 🎓 TRAINING NOTES FOR USERS

The new invoice system is designed for **wholesale pharmacy operations**:

1. **Credit Management:** Easily see if customer has available credit
2. **Flexible Pricing:** Set custom selling rates per transaction (not fixed by batch MRP)
3. **Batch Tracking:** Always know which batch items came from
4. **Profit Visibility:** PTR (cost) visible to billing staff for profit margin checking
5. **Smart Calculations:** All math done automatically, no manual calculations needed
6. **Print-Ready:** Professional invoice ready for customer delivery
7. **Draft Saving:** Start an invoice today, finish it tomorrow

Go ahead and test the form! Everything is production-ready.
