# Professional Tax Invoice & PO System - Complete Documentation

## Overview

A comprehensive Purchase Order and Tax Invoice system designed for real-world business operations with professional invoice printing capabilities matching GST compliance standards.

---

## 📊 Database Schema Design

### 1. Core Tables (New/Enhanced)

#### `purchase_orders` - Main PO Header

Stores all purchase order master details with invoice information.

**Key Fields:**

- `po_id` - Unique PO number (auto-generated)
- `po_date` - PO creation date
- `bill_number` - Invoice bill number
- `bill_date` - Invoice date
- `challan_number` - Challan/Delivery note number
- `vendor_id` - Foreign key to vendors table
- `vendor_*` - Vendor details (name, contact, GST, address)
- `delivery_*` - Delivery address details
- `po_status` - Status tracking (Draft, Sent, Pending, Approved, Partially Received, Received, Cancelled, Rejected)
- `po_type` - Regular, Express, Urgent
- Tax fields: `sgst_percent`, `sgst_amount`, `cgst_percent`, `cgst_amount`, `igst_percent`, `igst_amount`
- `round_off` - Rounding adjustment
- `grand_total` - Final amount
- `payment_status` - Pending, Partial, Paid, Overdue
- `terms_conditions` - T&C text
- Cancellation fields: `cancelled_by`, `cancelled_date`, `cancellation_reason`
- Audit fields: `created_by`, `created_at`, `updated_by`, `updated_at`

#### `po_items` - Line Items

Detailed line items with batch and expiry tracking.

**Key Fields:**

- `po_master_id` - FK to purchase_orders
- `product_id` - FK to products
- `product_name`, `product_code` - Product details
- `hsn_code` - HSN/SAC code for tax classification
- `batch_number`, `expiry_date`, `manufacturing_date` - Batch tracking
- `quantity_ordered`, `quantity_received`, `quantity_rejected` - Quantity tracking
- `unit_price`, `line_amount` - Pricing
- `item_discount` - Line-level discount
- `tax_percent`, `tax_amount` - Line-level tax
- `item_total` - Total amount for line
- `item_status` - Pending, Partial, Received, Cancelled, Rejected

#### `vendors` - Vendor Master

Complete vendor information with payment terms.

**Key Fields:**

- `vendor_name`, `vendor_type`
- `gst_number`, `contact_person`
- `primary_contact`, `secondary_contact`, `email`
- `billing_address`, `shipping_address` - Separate addresses
- `payment_terms`, `payment_terms_days`
- `credit_limit`, `total_ordered`, `total_paid`
- `is_active` - Active/Inactive status

#### `company_details` - Company Information

Store company details for invoice header.

**Key Fields:**

- `company_name`, `company_logo`
- `gst_number`, `pan_number`
- `company_address`, `company_city`, `company_state`, `company_pincode`
- `company_contact`, `company_email`, `company_website`
- `bank_*` - Bank details for payment

### 2. Supporting Tables

#### `po_payments` - Payment Tracking

Track multiple payments against PO.

**Key Fields:**

- `po_id` - FK to purchase_orders
- `payment_date`, `payment_method`
- `payment_reference`, `cheque_number`, `transaction_id`
- `amount_paid` - Amount in this payment
- `notes` - Payment notes

#### `po_amendments` - Change Log

Track all changes/amendments to PO.

**Key Fields:**

- `amendment_type` - Type of change
- `old_value`, `new_value` - Before/after values
- `reason` - Why change was made
- `amended_by` - User who made change
- `created_at` - When change was made

#### `po_cancellations` - Cancellation Details

Dedicated table for cancellation workflow.

**Key Fields:**

- `cancellation_reason` - Reason enum
- `reason_details` - Detailed explanation
- `refund_status` - Pending, Initiated, Completed
- `refund_amount`, `refund_date`
- `cancelled_by_*`, `approval_by_*` - User tracking

#### `po_receipts` - Goods Receipt

Track goods received against PO.

**Key Fields:**

- `receipt_number`, `receipt_date`
- `received_quantity`, `rejected_quantity`
- `received_by`, `verified_by`

---

## 🖥️ File Structure

### New Files Created

```
add-purchase-order-new.php          ← Professional PO form with all fields
purchase_order-new.php              ← Enhanced PO list with Cancel option
print-purchase-order-new.php        ← Professional tax invoice print template
cancel-purchase-order.php           ← Cancel PO workflow form
php_action/createPurchaseOrder-new.php    ← Backend PO creation logic
php_action/cancelPurchaseOrder.php  ← Cancel PO backend logic
dbFile/po_invoice_schema.sql        ← Complete schema design
dbFile/migration_po_schema.sql      ← Database migration script
```

---

## 📝 Form Features

### Add Purchase Order Form (`add-purchase-order-new.php`)

#### Section 1: Document Details

- PO Number (auto-generated)
- PO Date
- Bill Number & Date
- Challan Number & Date
- PO Type (Regular/Express/Urgent)
- PO Status

#### Section 2: Vendor Details

- Vendor Name, Contact Person
- Primary & Secondary Contact
- Email
- GSTIN, Vendor Type
- Billing Address (Address, City, State, Pincode)
- Shipping Address (with checkbox to auto-fill from billing)

#### Section 3: Delivery & Payment

- Expected Delivery Date
- Payment Method
- Payment Terms
- Payment Status

#### Section 4: Line Items

- Product Name (with autocomplete search)
- HSN Code
- Quantity & Unit of Measure
- Unit Price
- Item-level Discount %
- Amount & Tax %
- Total

#### Section 5: Calculations

- Sub Total
- Discount (Fixed + %)
- Taxable Amount
- SGST (% & Amount)
- CGST (% & Amount)
- IGST (% & Amount)
- Round Off
- **Grand Total**

#### Section 6: Notes & Terms

- Notes/Remarks
- Terms & Conditions (pre-filled with standard terms)

### Cancel PO Form (`cancel-purchase-order.php`)

#### Information Display

- Current PO details
- Vendor information
- Amount summary

#### Cancellation Form

- **Reason Selection** (Vendor Request, Incorrect Order, Product Discontinued, Duplicate Order, Budget Issue, Other)
- **Detailed Reason** (mandatory text area)
- **Refund Information**
  - Refund Amount (pre-filled with PO total)
  - Refund Status (Pending, Initiated, Completed)
  - Refund Date
  - Refund Notes

#### Approvals

- Manager/Supervisor approval confirmation
- Approver Name
- Supporting Document Reference

#### Confirmation

- Mandatory confirmation checkbox

---

## 🖨️ Print Template Features

### Professional Tax Invoice (`print-purchase-order-new.php`)

The print template matches the invoice image provided with:

1. **Header Section**
   - Company name, logo, address
   - GST Number, PAN, Contact details

2. **Invoice Title & Details**
   - Invoice Number
   - PO Number
   - Invoice Date & Due Date

3. **Addresses**
   - Bill To (Vendor details with GSTIN)
   - Ship To (Delivery address with expected delivery date)

4. **Items Table**
   - S.N., Product Name
   - HSN Code
   - Quantity, Pack Size
   - Rate, Amount
   - Tax %, Total

5. **Terms & Conditions**
   - Standard or custom T&Cs
   - Auto-formatted

6. **Calculations**
   - Sub Total
   - Discount
   - SGST, CGST, IGST breakdown
   - Round Off
   - **Grand Total**

7. **Payment Status**
   - Current payment status display

8. **Footer**
   - Signature boxes for: Prepared By, Authorized By, Accepted By
   - Generated timestamp
   - Disclaimer

### Print Features

- ✅ Print button (browser print)
- ✅ Download PDF option
- ✅ Professional styling
- ✅ GST compliant format
- ✅ Status badges with colors
- ✅ Responsive design

---

## 🚫 Cancel PO Workflow

### Process Flow

1. **Navigate to PO List**
   - User selects PO to cancel
   - Only non-cancelled/non-received POs can be cancelled

2. **Fill Cancellation Form**
   - Select cancellation reason
   - Provide detailed explanation
   - Enter refund information
   - Confirm manager approval
   - Final confirmation

3. **Backend Processing**
   - Update PO status to "Cancelled"
   - Update all line items to "Cancelled"
   - Create cancellation record in `po_cancellations` table
   - Create amendment record in `po_amendments` table
   - If refund initiated, create payment record in `po_payments` table
   - All within a transaction (all-or-nothing)

4. **Audit Trail**
   - User ID, name, timestamp recorded
   - Cancellation reason stored
   - All amendments tracked
   - Refund details maintained

### Features

- ✅ Warning messages
- ✅ Validation checks
- ✅ Approval workflow
- ✅ Refund tracking
- ✅ Audit trail
- ✅ Cannot cancel already cancelled POs
- ✅ Item status updates
- ✅ Transaction rollback on error

---

## ⚙️ Backend Logic

### Create Purchase Order (`createPurchaseOrder-new.php`)

**Process:**

1. Validate all required fields
2. Validate items list (minimum 1 item)
3. Begin transaction
4. Insert PO header in `purchase_orders`
5. Get auto-increment ID
6. Insert each item in `po_items` with product details
7. Commit transaction
8. Return success with PO ID

**Error Handling:**

- Rollback on any error
- Clear error messages
- Transaction safety

### Cancel Purchase Order (`cancelPurchaseOrder.php`)

**Process:**

1. Validate PO exists and not already cancelled
2. Begin transaction
3. Update `purchase_orders` status to "Cancelled"
4. Insert cancellation record in `po_cancellations`
5. Update all items status to "Cancelled"
6. Create amendment record
7. If refund initiated, create payment record
8. Commit transaction

**Audit Fields Updated:**

- `cancelled_by`, `cancelled_date`
- `updated_by`, `updated_at`

---

## 🔒 Data Validation

### Form Validation

- Required fields marked with \*
- Email format validation
- Phone number format
- Positive numbers only
- Date format validation

### Backend Validation

- Required fields check
- Minimum items check (at least 1)
- Positive quantity & rate
- Valid PO status
- Transaction safety

---

## 📈 Real-World Features

### 1. **Batch & Expiry Tracking**

- Track batch numbers
- Monitor expiry dates
- Manufacturing date
- Essential for pharmaceutical/clinical products

### 2. **Tax Compliance**

- SGST, CGST, IGST support
- HSN/SAC code field
- GST number for vendor
- Tax invoice format compliance

### 3. **Multi-Level Pricing**

- Line-level discounts
- Item-level tax
- Fixed + Percentage discounts
- Round-off adjustment

### 4. **Quantity Tracking**

- Ordered vs Received vs Rejected
- Item status tracking
- Partial receipt support

### 5. **Payment Tracking**

- Multiple payment support
- Payment method tracking
- Payment reference/cheque/transaction ID
- Payment terms & due date calculation

### 6. **Audit & Compliance**

- User tracking (created_by, updated_by)
- Change log (amendments table)
- Cancellation tracking with reasons
- Full audit trail

### 7. **Address Management**

- Separate billing and shipping addresses
- City, State, Pincode tracking
- GST jurisdiction support

### 8. **Status Management**

- Multiple statuses (Draft → Sent → Pending → Approved → Received/Cancelled)
- Item-level status
- Payment status tracking
- Visual status badges

---

## 🚀 Installation & Migration Steps

### Step 1: Run Migration Script

```sql
-- Execute migration_po_schema.sql
-- This will:
-- - Create new tables (vendors, company_details, etc.)
-- - Enhance existing tables (purchase_orders, po_items)
-- - Create support tables (po_payments, po_amendments, etc.)
```

### Step 2: Update Company Details

```sql
-- Insert your company details
INSERT INTO company_details
(company_name, company_address, ...)
VALUES ('Your Company', ..., );
```

### Step 3: Create Vendors

- Use UI or direct SQL to create vendor records
- Enter vendor details, GST numbers, addresses

### Step 4: Test the System

- Create a test PO
- Check print output
- Test cancel functionality
- Verify audit trail

---

## 📋 API Endpoints

### Create PO

- **Endpoint**: `php_action/createPurchaseOrder-new.php`
- **Method**: POST (JSON)
- **Returns**: Success status, PO ID, PO number

### Cancel PO

- **Endpoint**: `php_action/cancelPurchaseOrder.php`
- **Method**: POST (JSON)
- **Returns**: Success status, cancellation details

### Fetch Products

- **Endpoint**: `php_action/fetchProducts.php?search=query`
- **Method**: GET
- **Returns**: Array of matching products

---

## 🎨 UI/UX Features

### Color Coding

- **Primary**: Information sections
- **Danger**: Cancel operations, warnings
- **Warning**: Important notices
- **Success**: Positive actions
- **Info**: Helpful information

### Status Badges

- Draft: Gray
- Pending: Orange
- Approved: Green
- Received: Blue
- Cancelled: Red

### Form Organization

- Grouped sections with headers
- Clear field labels
- Mandatory field indicators (\*)
- Helpful placeholder text
- Related fields grouped together

### Responsive Design

- Mobile-friendly tables
- Flexible grid layouts
- Readable font sizes
- Touch-friendly buttons

---

## ✅ Quality Features

### Data Integrity

- Foreign key constraints
- Transaction-based operations
- Rollback on error
- Unique constraints (PO number)

### Performance

- Indexes on commonly queried fields
- Efficient database queries
- Pagination ready
- Query optimization

### Security

- SQL injection prevention (prepared statements)
- Input validation & sanitization
- htmlspecialchars for output
- User session tracking

### Usability

- Clear error messages
- Confirmation dialogs
- Helpful information panels
- Status indicators

---

## 🔄 Status Flow Diagram

```
Draft → Sent → Pending → Approved → Received
                    ↓
                 Cancelled
```

**Item Status Flow:**

```
Pending → Partial → Received
    ↓
Cancelled/Rejected
```

**Refund Status Flow:**

```
Pending → Initiated → Completed
```

---

## 📊 Sample Data & Testing

### Test PO Creation

1. Navigate to "Add Purchase Order"
2. Fill all fields
3. Add 2-3 line items
4. Verify calculations
5. Submit and check creation

### Test Cancellation

1. Navigate to created PO
2. Click Cancel button
3. Fill cancellation details
4. Submit cancellation
5. Verify status changed to "Cancelled"
6. Check audit trail

### Test Print

1. Open created PO
2. Click Print button
3. Verify format matches invoice
4. Check all fields display correctly
5. Print to PDF

---

## 🛠️ Customization Guide

### Add New Field to PO

1. Add column to `purchase_orders` table
2. Update form in `add-purchase-order-new.php`
3. Update backend creation logic
4. Update print template

### Add New Status

1. Update enum in `po_status` column
2. Add status badge styling
3. Update status flow logic
4. Add to UI filter if needed

### Change Tax Structure

1. Update tax calculation logic in JavaScript
2. Modify backend calculations
3. Update print template tax display
4. Test various scenarios

### Customize Terms & Conditions

1. Update default text in form
2. Create terms template dropdown
3. Allow users to select/customize
4. Display in print output

---

## 📞 Support & Documentation

### Error Troubleshooting

- Check browser console for JavaScript errors
- Review server error logs
- Verify database migration completed
- Check user session/permissions

### Common Issues

1. **PO Number not generating**: Check database query for max PO
2. **Calculations wrong**: Verify discount/tax percentage inputs
3. **Print not showing**: Check browser print settings
4. **Cancel button disabled**: PO may already be cancelled

---

## Version Information

- **Created**: January 2026
- **Last Updated**: January 27, 2026
- **System**: Professional Tax Invoice & PO Management
- **Database**: MySQL/MariaDB
- **Technology**: PHP, JavaScript, HTML5, CSS3

---

## 📌 Key Takeaways

✅ **Complete Schema** - Professional, normalized database design
✅ **Real-World Features** - Batch tracking, tax compliance, audit trail
✅ **Professional Print** - Invoice format matching GST standards
✅ **Cancel Workflow** - Complete cancellation with refund tracking
✅ **Audit Trail** - Full tracking of all changes and cancellations
✅ **Efficiency** - Built for scalability and real-time operations
✅ **User-Friendly** - Clear UI with helpful information and validations
✅ **Secure** - Prepared statements, input validation, transaction safety
