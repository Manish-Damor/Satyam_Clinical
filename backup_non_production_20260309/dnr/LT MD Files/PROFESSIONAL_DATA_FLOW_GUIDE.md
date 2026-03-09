# 🏥 Professional Pharmacy ERP - Complete Data Entry Flow Guide

> **Comprehensive guide for operating the Satyam Clinical pharmacy inventory and purchase system with professional ERP standards**

---

## 📋 Table of Contents

1. [Master Data Setup](#1-master-data-setup)
2. [Product Master Creation](#2-product-master-creation)
3. [Purchasing Cycle](#3-purchasing-cycle)
4. [Inventory Management](#4-inventory-management)
5. [Sales Cycle](#5-sales-cycle)
6. [Reports & Analytics](#6-reports--analytics)

---

## 1. MASTER DATA SETUP

### 1.1 Supplier Registration

**File**: `manage_suppliers.php` → `add_supplier.php`

**Data Entry Fields:**

```
├── Supplier Identification
│   ├── Supplier Code (Auto/Manual) - UNIQUE
│   ├── Supplier Name - Required
│   └── Company Name - Optional
├── Contact Information
│   ├── Contact Person Name
│   ├── Email Address
│   ├── Phone Number (Primary)
│   └── Alternative Phone
├── Address Details
│   ├── Street Address
│   ├── City
│   ├── State
│   ├── Postal Code
│   └── Country (Default: India)
├── Tax & Compliance
│   ├── GST Registration Number (Validate Format)
│   ├── PAN Number (Validate Format)
│   └── Verified Flag (Yes/No)
├── Business Terms
│   ├── Credit Days Allowed (Numeric)
│   ├── Payment Terms (COD/NET15/NET30/NET45/NET60)
│   └── Preferred Payment Mode (Cheque/Bank Transfer/NEFT)
└── Status
    ├── Supplier Status (Active/Inactive/Blocked)
    └── Notes/Comments
```

**Database Affected:**

- `suppliers` table (PRIMARY)
- Linked to: `purchase_orders`, `product_batches`, `purchase_invoices`

**Key Validations:**

- ✓ Supplier name must be unique
- ✓ GST number format validation (15 digits for India)
- ✓ Email format validation
- ✓ Phone number format (10 digits)
- ✓ Credit days must be numeric (0-180 range)

---

### 1.2 Product Master

**File**: `product.php` → `add-product.php`

**Data Entry Fields:**

```
├── Basic Information
│   ├── Product Name - Required, UNIQUE
│   ├── Product Description
│   └── Generic Name / Salt Composition
├── Classification
│   ├── Brand Selection (Dropdown) - FK to brands
│   ├── Category Selection (Dropdown) - FK to categories
│   └── Product Type (Tablet/Syrup/Injection/Ointment/Capsule)
├── Regulatory Information
│   ├── HSN Code (Harmonized System of Nomenclature) - 6-8 digits
│   ├── GST Tax Rate (0%, 5%, 12%, 18%, 28%)
│   └── Composition / Content
├── Packaging Details
│   ├── Unit Type (Strip/Box/Bottle/Vial/Tube/Piece)
│   ├── Pack Size (e.g., 10 tablets, 100ml)
│   └── MRP - Maximum Retail Price
├── Inventory Settings
│   ├── Reorder Level (Minimum stock trigger)
│   ├── Reorder Quantity (Qty to order when below level)
│   └── Safety Stock (Extra buffer)
├── Warehouse Setup
│   ├── Storage Location (Shelf/Bin)
│   └── Storage Conditions (Room Temp/Cold/Dry)
└── Status
    ├── Status (Active/Inactive)
    ├── Manufacturing License Info
    └── Notes
```

**Database Affected:**

- `product` table (PRIMARY)
- Linked to: `brands`, `categories`, `product_batches`, `purchase_orders`, `orders`

**Key Validations:**

- ✓ Product name must be unique
- ✓ HSN code format (6-8 digits)
- ✓ GST rate must be valid percentage
- ✓ Reorder level > 0 for active products
- ✓ MRP must be greater than cost

---

### 1.3 Brand & Category Management

**File**: `brand.php` / `categories.php`

**Brand Entry:**

```
├── Brand Name (UNIQUE, Required)
├── Brand Status (Active/Inactive)
└── Logo/Image (Optional)
```

**Category Entry:**

```
├── Category Name (UNIQUE, Required)
├── Category Type (Therapeutic/Dosage Form)
└── Category Status (Active/Inactive)
```

---

## 2. PRODUCT MASTER CREATION

### 2.1 Add Batches (After Product Listed)

**File**: `manage_batches.php` → `add_batch.php`

**Data Entry Fields:**

```
├── Batch Identification
│   ├── Product Selection (Dropdown)
│   ├── Batch Number (UNIQUE) - From supplier invoice/PO
│   └── Supplier Selection
├── Batch Details
│   ├── Manufacturing Date
│   ├── Expiry Date - Must be > Mfg Date
│   └── Quantity Received (Initial Stock)
├── Cost Information
│   ├── Purchase Rate / Unit Cost
│   └── MRP (from product or override)
├── Lot Information
│   ├── Packing Details (Strip/Box/Bottle)
│   ├── Seal/Label Verification
│   └── Quality Check Status
└── GRN Linkage (Optional)
    ├── Link to GRN ID
    └── Link to PO ID
```

**Database Affected:**

- `product_batches` table (PRIMARY)
- Updates: `stock_movements`, `stock_batches`
- Used by: `purchase_invoices`, `orders`

**Key Validations:**

- ✓ Batch number must be unique
- ✓ Expiry date > Manufacturing date
- ✓ Manufacturing date <= Today
- ✓ Quantity must be positive
- ✓ Purchase rate must be ≤ MRP

---

## 3. PURCHASING CYCLE

### 3.1 Create Purchase Order

**File**: `add-purchase-order.php` → `php_action/create_po.php`

**Data Entry Workflow:**

```
STEP 1: Order Header
├── Order Number (Auto-generated: PO-YYYY-00001)
├── PO Date (Current Date default)
├── Supplier Selection (Dropdown - Active suppliers only)
├── Reference Number (Customer/Internal ref)
└── Delivery/Expected Date

STEP 2: Item Selection
├── Product Search (Auto-complete)
├── Quantity (UOM based)
├── Unit Price (Supplier rate)
├── Discount % (Optional)
├── Line Total (Auto-calculated)
└── Add More Items

STEP 3: Order Summary
├── Subtotal (Sum of all line items)
├── Discount Amount (If any line discounts)
├── Freight Charge (Optional)
├── Tax Amount (Based on GST rates)
├── Round-off Adjustment
└── Grand Total (Auto-calculated)

STEP 4: Terms & Conditions
├── Payment Terms (from supplier master)
├── Delivery Terms
├── Quality Inspection Required (Y/N)
├── Special Instructions
└── Attached Documents (Optional)

STEP 5: Approval
├── Save as Draft or Submit for Approval
├── Status: Draft → Submitted → Approved → Cancelled
└── Approval Notes
```

**Database Affected:**

- `purchase_orders` table (PRIMARY)
- `po_items` table (Line items)
- Links: `suppliers`, `product`

**Key Validations:**

- ✓ Supplier must be Active status
- ✓ Quantity > 0 for all items
- ✓ Unit price > 0
- ✓ Delivery date >= PO date
- ✓ At least 1 item required

---

### 3.2 Goods Receipt (GRN)

**File**: `create_po.php` / Create new `manage_grn.php`

**Data Entry Workflow:**

```
STEP 1: Link to Purchase Order
├── Select PO Number
├── Auto-populate supplier & items
└── Verify expected quantities

STEP 2: Receive Items
├── For Each Item:
│   ├── Quantity Received (vs. Expected)
│   ├── Quantity Rejected
│   ├── Damage/Defect Notes
│   └── Quality Check Status (Pass/Fail)
├── Partial Receipt Flag (Y/N)
└── Over-receipt Flag (if qty > PO)

STEP 3: Batch Information
├── Create Batch Record
│   ├── Batch Number (from package)
│   ├── Manufacturing Date
│   ├── Expiry Date
│   ├── Serial Number (if applicable)
│   └── Received Quantity
└── Link to PO Item

STEP 4: Storage
├── Warehouse Location
├── Rack/Shelf Assignment
├── Environmental Conditions
└── Quarantine (if needed for inspection)

STEP 5: Documentation
├── GRN Number (Auto: GRN-YYYY-00001)
├── Received Date
├── Received By (User)
├── Supplier Invoice Reference
└── Inspection Report (Attach if any issues)

STEP 6: Approval
├── Quality Check Status
├── Final Approval (Accept/Reject/Conditional)
└── Status: Draft → Approved → Posted
```

**Database Affected:**

- `goods_received` table (PRIMARY)
- `grn_items` table (Line items)
- `product_batches` table (Create new batch)
- `stock_movements` table (Record In-stock movement)
- Updates: `purchase_orders` (Mark items received)

**Key Validations:**

- ✓ Linked PO must exist and be Approved
- ✓ Received qty <= PO qty (unless over-receipt allowed)
- ✓ Batch number must be unique
- ✓ Expiry date must be valid (>6 months from current)
- ✓ Manufacturing date <= Current date

---

### 3.3 Purchase Invoice

**File**: `purchase_invoice.php` → `php_action/create_purchase_invoice.php`

**Data Entry Workflow:**

```
STEP 1: Invoice Header
├── Invoice Number (Supplier invoice number) - UNIQUE
├── Invoice Date
├── Supplier Selection (Dropdown)
├── Auto-populated Supplier Details
│   ├── Company Name
│   ├── GST Number
│   ├── Contact Person
│   └── Address
├── PO Reference (Link to PO)
├── GRN Reference (Link to GRN - if exists)
└── Due Date (Auto-calculated from terms)

STEP 2: Line Items
├── Product Name (Search/Autocomplete)
├── HSN Code (Auto-filled from product)
├── Batch Number
├── Expiry Date
├── Quantity (from GRN or manual entry)
├── Free Quantity (Bonus stock)
├── Unit Cost (Purchase rate)
├── Discount % (Line level)
├── Tax Rate % (Based on HSN/GST)
└── Line Total (Auto-calculated)

STEP 3: Summary Calculations
├── Subtotal (Sum of all qty × unit cost)
├── Total Line Discounts
├── Total Taxable Amount
├── Total Tax Amount (by tax rate)
├── Freight Charges (Optional)
├── Round-off (Adjustment)
└── Grand Total (Final invoice amount)

STEP 4: Payment Information
├── Payment Terms (from supplier master)
├── Due Date (Auto from terms or manual)
├── Payment Mode (Cheque/NEFT/Bank Transfer)
├── Bank Account Selection
└── Payment Status (Pending/Partial/Full)

STEP 5: Notes & Attachment
├── Remarks/Special Instructions
├── Attached Supplier Invoice (PDF/Image)
└── Internal Notes

STEP 6: Approval & Posting
├── Save as Draft or Submit
├── Status: Draft → Submitted → Approved → Posted
└── Approval Comments
```

**Database Affected:**

- `purchase_invoices` table (PRIMARY)
- `purchase_invoice_items` table (Line items)
- `supplier_payments` table (Payment tracking)
- Links: `suppliers`, `goods_received`, `purchase_orders`

**Key Validations:**

- ✓ Supplier must exist and be Active
- ✓ Invoice number must be unique per supplier per month
- ✓ Invoice date <= Today
- ✓ Due date > Invoice date
- ✓ GST calculation must be correct
- ✓ Total = Subtotal - Discount + Tax + Freight ± RoundOff

---

### 3.4 Supplier Payment

**File**: Create new `manage_supplier_payments.php`

**Data Entry Workflow:**

```
STEP 1: Payment Header
├── Invoice Selection (Dropdown - pending invoices)
├── Auto-populated Invoice Details
│   ├── Invoice Number
│   ├── Invoice Date
│   ├── Grand Total
│   └── Outstanding Amount
├── Payment Date
└── Payment Reference Number (Check/Txn)

STEP 2: Payment Details
├── Amount to Pay (Full/Partial)
├── Payment Mode (Cheque/NEFT/Bank Transfer/Cash)
├── Cheque Details (if payment mode = Cheque)
│   ├── Cheque Number
│   ├── Cheque Date
│   ├── Bank Name
│   └── Account Number
├── Bank Transfer Details (if mode = NEFT)
│   ├── Supplier Bank Name
│   ├── Account Number
│   ├── IFSC Code
│   └── UTR/Reference
└── Remarks

STEP 3: Reconciliation
├── Match Against Invoice
├── Early Payment Discount (if applicable)
├── Payment Mode Charges (if any)
└── Net Payment

STEP 4: Approval & Recording
├── Payment Status (Pending → Processed → Reconciled)
├── Save & Print Cheque/Txn Receipt
└── Status Update
```

**Database Affected:**

- `supplier_payments` table (PRIMARY)
- Updates: `purchase_invoices` (Mark paid)
- Links: `suppliers`, `accounts` (if using)

**Key Validations:**

- ✓ Invoice must exist and not be fully paid
- ✓ Payment amount <= Outstanding amount
- ✓ Payment date >= Invoice date
- ✓ Cheque/Bank details required for non-cash payments

---

## 4. INVENTORY MANAGEMENT

### 4.1 Stock Tracking Dashboard

**File**: `viewStock.php` / Create new `inventory_dashboard.php`

**Display Fields:**

```
Product Level View:
├── Product Name
├── Current Stock (Total Available)
├── Stock Status (In Stock/Low Stock/Out of Stock)
├── Reorder Level (Threshold)
├── Stock Value (Qty × Rate calculation)
├── Last Received Date
├── Next Expected Delivery
└── Status (Active/Discontinued)

Batch Level View:
├── Batch Number
├── Manufacturing Date
├── Expiry Date (with alert if <90 days)
├── Quantity Available
├── Quantity Reserved (in SO)
├── Quantity Damaged
├── Storage Location
├── Supplier Name
└── Purchase Rate
```

**Database Used:**

- `product` table (Current read)
- `product_batches` table (Batch details)
- `stock_movements` table (Movement history)
- VIEW: `v_inventory_summary` (For dashboard)
- VIEW: `v_batch_expiry_alerts`
- VIEW: `v_low_stock_alerts`

---

### 4.2 Expiry Management

**File**: `expreport.php` / Create new `expiry_management.php`

**Features:**

```
Expiry Alert System:
├── Approaching Expiry (< 90 days) - WARNING
├── Critical Expiry (< 30 days) - ALERT
├── Already Expired - BLOCKED
└── Expiry by Batch Report

Batch-Level Expiry:
├── By Product
├── By Supplier
├── By Storage Location
└── By Expiry Month

Actions Available:
├── Mark as Damaged/Return
├── Apply Expiry Hold
├── Generate Return Note (to supplier)
├── Scrap Record
└── Adjustment Entry
```

**Database Affected:**

- `product_batches` table (Status update to Expired)
- `expiry_tracking` table (Tracking records)
- `inventory_adjustments` table (Scrap/return adjustments)

---

### 4.3 Stock Adjustments

**File**: Create new `stock_adjustments.php`

**Data Entry:**

```
Adjustment Type:
├── Stock In (Received, Transfer In, Return from Damage)
├── Stock Out (Sale, Transfer Out, Damage, Expired)
└── Stock Correction (Physical count variance)

For Each Adjustment:
├── Product Selection
├── Batch Selection
├── Adjustment Type
├── Quantity
├── Reason (Dropdown)
├── Adjustment Date
├── Recorded By (User)
├── Remarks
└── Supporting Document (Optional)

Approval Workflow:
├── Save as Draft
├── Supervisor Approval
└── Posted to Ledger
```

**Database Affected:**

- `inventory_adjustments` table (PRIMARY)
- `stock_movements` table (Movement record)
- Updates: `product_batches` (Qty adjustment)

---

## 5. SALES CYCLE

### 5.1 Create Sales Order

**File**: `add-order.php` → `php_action/order.php`

**Data Entry Workflow:**

```
STEP 1: Order Header
├── Invoice/Order Number (Auto: INV-YYYY-00001)
├── Order Date (Current Date)
├── Customer Information
│   ├── Customer Name (Dropdown/New Entry)
│   ├── Phone Number
│   ├── Contact Person
│   └── Delivery Address
└── Payment Type (Cash/Credit/Card/Cheque)

STEP 2: Order Items
├── Product Selection (Search/Autocomplete)
├── Product Details Auto-filled:
│   ├── Product Name
│   ├── Current Price/MRP
│   └── Available Stock (from batches)
├── Batch Selection (if multiple batches available)
├── Quantity (with stock validation)
├── Unit Price (MRP or negotiated)
├── Discount (% or Flat amount)
├── Line Total (Auto-calculated)
└── Repeat for Multiple Items

STEP 3: Order Summary
├── Subtotal (Sum of line items)
├── Total Discount
├── GST Amount (if applicable)
├── Final Total / Grand Total
└── Amount Due (if credit sale)

STEP 4: Payment Details
├── Payment Mode (Cash/Credit/Card/Cheque)
├── If Cash:
│   ├── Amount Received
│   └── Change (Auto-calculated)
├── If Credit:
│   ├── Due Date
│   └── Credit Terms
└── If Cheque:
    ├── Cheque Number
    └── Cheque Date

STEP 5: Approval
├── Payment Status (Paid/Unpaid/Partial)
├── Fulfillment Status (Pending/Fulfilled)
├── Save Order
└── Auto-trigger Fulfillment Process
```

**Database Affected:**

- `orders` table (PRIMARY)
- `order_item` table (Line items)
- Links: `product`, `product_batches`

**Key Validations:**

- ✓ Customer name must be provided
- ✓ At least 1 item required
- ✓ Order quantity <= Available batch quantity
- ✓ Order date <= Today
- ✓ Payment amount = Total (for cash sales)
- ✓ GST calculation correct

---

### 5.2 Order Fulfillment & Picking

**File**: Create new `order_fulfillment.php`

**Fulfillment Process:**

```
STEP 1: Order Picking
├── Order Selection (List pending orders)
├── For Each Item:
│   ├── Product & Batch Identification
│   ├── Pick Quantity (vs Ordered)
│   ├── Verify Batch Expiry
│   ├── Verify Product Quality
│   └── Warehouse Location Pull
└── Packing Details

STEP 2: Verification
├── Order Items Verification (count all items)
├── Batch Expiry Verification
├── Product Quality Check
├── Price Verification Against Invoice
└── Customer Details Verification

STEP 3: Generate Sales Invoice
├── Invoice Number
├── Customer Details
├── Item Details with Batch Info
├── Total Amount
├── Payment Status
└── Print/Digital Invoice

STEP 4: Dispatch
├── Dispatch Date
├── Delivery Mode (Counter/Delivery)
├── Delivery Partner (if applicable)
└── Tracking Reference
```

**Database Affected:**

- Updates: `orders` (Status to Fulfilled)
- Updates: `product_batches` (Qty reduction)
- `stock_movements` table (Out-stock record)

---

## 6. REPORTS & ANALYTICS

### 6.1 Inventory Reports

**File**: `inventory_reports.php`

**Available Reports:**

```
1. Stock Summary Report
   ├── Current Stock by Product
   ├── Stock Value (Qty × Rate)
   ├── Stock Movement (In/Out)
   └── Stock Aging

2. Batch Summary Report
   ├── Active Batches by Product
   ├── Batch Expiry Status
   ├── Batch Stock Level
   └── Supplier-wise Batches

3. Low Stock Alert Report
   ├── Products below reorder level
   ├── Days to stockout (at current usage)
   └── Suggested order quantities

4. Excess Stock Report
   ├── Products with overstocking
   ├── Slow-moving items
   └── Value of excess stock

5. Stock Movement Report
   ├── Inbound (by period/supplier)
   ├── Outbound (by period/customer)
   ├── Adjustments
   └── Net Movement
```

---

### 6.2 Purchase Analytics

**File**: `report.php`

**Available Reports:**

```
1. Supplier Performance
   ├── Total Orders
   ├── On-time Delivery %
   ├── Quality Score (defects)
   ├── Payment Terms Compliance
   └── Price Comparison

2. Purchase Summary
   ├── Orders by supplier
   ├── Orders by period
   ├── Total expenditure
   └── Average order value

3. Invoice Status Report
   ├── Pending invoices
   ├── Pending payments
   ├── Overdue invoices
   └── Payment terms analysis

4. Goods Receipt Analysis
   ├── GRN vs PO matching
   ├── Rejection rate
   ├── Quantity variances
   └── Receipt delay analysis
```

---

### 6.3 Sales Analytics

**File**: `salesreport.php` / `sales_report.php`

**Available Reports:**

```
1. Daily Sales Summary
   ├── Orders created
   ├── Amount collected (cash)
   ├── Amount due (credit)
   └── Payment collection

2. Product-wise Sales
   ├── Quantity sold
   ├── Revenue
   ├── Profit margin
   └── Stock consumption rate

3. Customer Analysis
   ├── Regular customers
   ├── Credit customers
   ├── Credit exposure
   └── Payment behavior

4. Batch-wise Sales Report
   ├── Which batches sold
   ├── Remaining stock
   ├── Expiry monitoring
   └── Stock turnover rate
```

---

### 6.4 Expiry Management Report

**File**: `expreport.php`

**Report Features:**

```
Approaching Expiry (90 days):
├── By Product
├── By Batch
├── By Storage Location
├── Recommended Actions (Sale, Return, Destroy)
└── Financial Impact (potential loss)

Critical Expiry (30 days):
├── Urgent action items
├── Loss calculation
└── Return/Destruction options
```

---

## 📊 KEY DATA RELATIONSHIPS

```
MASTER DATA LAYER
├── brands (5 entries)
├── categories (5 entries)
├── suppliers (n entries)
└── product (master list)

PURCHASING LAYER
├── purchase_orders (1 PO can have many items)
├── po_items (each item tracks qty, rate, status)
├── goods_received (GRN for each PO)
├── grn_items (items received)
└── purchase_invoices (linked to GRN)

INVENTORY LAYER
├── product_batches (1 product can have many batches)
├── stock_movements (track in/out)
├── stock_batches (batch lot management)
├── expiry_tracking (automated alerts)
├── reorder_management (trigger levels)
└── inventory_adjustments (corrections)

SALES LAYER
├── orders (1 order has many items)
├── order_item (product + batch selection)
└── [auto updates product_batches qty]

PAYMENTS LAYER
└── supplier_payments (linked to invoices)
```

---

## 🎯 RECOMMENDED IMPLEMENTATION SEQUENCE

### Phase 1: Foundation (Week 1-2)

1. ✅ Setup Master Data
   - Create Suppliers (minimum 3-5)
   - Create Brands (5-10)
   - Create Categories (5-10)

2. ✅ Setup Products
   - Add 20-50 products with HSN codes
   - Set reorder levels
   - Link to brands and categories

3. ✅ Initial Stock Entry
   - Add first batches for each product
   - Record opening stock with mfg/expiry dates

### Phase 2: Purchasing (Week 2-3)

1. ✅ Create POs (first 5-10 orders)
2. ✅ Receive Goods (GRN process)
3. ✅ Record Invoices
4. ✅ Make Payments

### Phase 3: Sales (Week 3-4)

1. ✅ Create Customer List
2. ✅ Enter First Sales Orders
3. ✅ Fulfillment & Invoicing
4. ✅ Payment Collection

### Phase 4: Analysis (Week 4+)

1. ✅ Generate Reports
2. ✅ Monitor Inventory Health
3. ✅ Track Supplier Performance
4. ✅ Analyze Sales Trends

---

## ✅ DATA QUALITY CHECKLIST

Before Going Live:

- [ ] All suppliers have complete contact information
- [ ] All products have HSN codes and correct GST rates
- [ ] All products have reorder levels set
- [ ] Initial stock entered as batches with expiry dates
- [ ] First month's purchase orders reconciled
- [ ] Supplier invoices matched to GRNs
- [ ] All payments recorded and reconciled
- [ ] Stock levels verified physically
- [ ] Expiry management system tested
- [ ] Reports generating correctly
- [ ] User access controls configured
- [ ] Backup system in place

---

## 📞 FIELD DEFINITIONS QUICK REFERENCE

| Field              | Type     | Format                | Example      | Validation                 |
| ------------------ | -------- | --------------------- | ------------ | -------------------------- |
| **HSN Code**       | String   | 6-8 digits            | 300110       | Must be numeric, 6-8 chars |
| **GST Rate**       | Decimal  | 0%, 5%, 12%, 18%, 28% | 18.00        | Must be valid GST rate     |
| **Credit Days**    | Number   | 1-180                 | 30           | Numeric, >0                |
| **Batch Number**   | String   | Alphanumeric, UNIQUE  | BAT-2024-001 | Cannot be repeated         |
| **Expiry Date**    | Date     | YYYY-MM-DD            | 2025-12-31   | Must be > Mfg Date         |
| **MRP**            | Currency | 2 decimals            | 150.00       | Must be > Purchase Rate    |
| **GIN Number**     | String   | Auto-generated        | GRN-2024-001 | System generated           |
| **Invoice Number** | String   | Auto-generated        | INV-2024-001 | System generated           |

---

## 🚨 CRITICAL ALERTS

**System will warn you for:**

1. ⚠️ Products approaching expiry (<90 days)
2. ⚠️ Products with expiry <30 days
3. ⚠️ Stock below reorder level
4. ⚠️ Overdue supplier payments
5. ⚠️ PO not received after expected date
6. ⚠️ Invoice not received within 15 days of GRN
7. ⚠️ Credit customers exceeding credit limit
8. ⚠️ Low stock for high-demand products

---

**End of Document**

_Last Updated: February 2026_
_Version: Professional ERP v1.0_
