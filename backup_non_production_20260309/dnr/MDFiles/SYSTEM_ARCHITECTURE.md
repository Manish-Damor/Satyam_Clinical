# Professional PO & Tax Invoice System - Architecture & Design

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER INTERFACE LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  add-purchase-order-new.php     purchase_order-new.php         │
│  (Create PO)                     (List POs)                    │
│      ↓                               ↓                          │
│  ┌─────────────────────┬────────────────────────┐              │
│  │ Document Details    │                        │              │
│  │ Vendor Info         │ View All POs           │              │
│  │ Delivery Terms      │ Edit, Print, Cancel    │              │
│  │ Line Items          │ Status Tracking        │              │
│  │ Calculations        │ Payment Tracking       │              │
│  │ Notes & T&Cs        │                        │              │
│  └─────────────────────┴────────────────────────┘              │
│      ↓                     ↓            ↓         ↓             │
│  cancel-purchase-order.php  print-purchase-order-new.php       │
│  (Cancel PO Form)           (Tax Invoice Print)                 │
│                                                                 │
└────────┬───────────────────────────────┬──────────────────────┘
         │                               │
         ↓                               ↓
┌─────────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  createPurchaseOrder-new.php    cancelPurchaseOrder.php        │
│  ├─ Validation                  ├─ Validate PO exists          │
│  ├─ Transaction start           ├─ Transaction start           │
│  ├─ Insert PO header            ├─ Update PO status            │
│  ├─ Insert line items           ├─ Update items status         │
│  ├─ Calculate totals            ├─ Insert cancellation record  │
│  ├─ Create amendment log        ├─ Create amendment record     │
│  ├─ Commit/Rollback             ├─ Create refund tracking      │
│  └─ Return result               └─ Commit/Rollback             │
│                                                                 │
│  Other PHP Action Files:                                       │
│  ├─ fetchProducts.php (search)                                 │
│  ├─ editPurchaseOrder.php (update)                             │
│  ├─ removePurchaseOrder.php (soft delete)                      │
│  └─ [Other supporting functions]                               │
│                                                                 │
└────────┬───────────────────────────────┬──────────────────────┘
         │                               │
         ↓                               ↓
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Core Tables:                  Support Tables:                 │
│  ┌──────────────────┐         ┌──────────────────┐            │
│  │purchase_orders   │         │po_payments       │            │
│  │ ├─ po_id         │         │ ├─ po_id         │            │
│  │ ├─ vendor_id     │         │ ├─ payment_date  │            │
│  │ ├─ bill_number   │         │ ├─ amount_paid   │            │
│  │ ├─ po_status     │         │ └─ payment_method│            │
│  │ ├─ po_type       │         └──────────────────┘            │
│  │ ├─ grand_total   │         ┌──────────────────┐            │
│  │ ├─ sgst_amount   │         │po_amendments     │            │
│  │ ├─ cgst_amount   │         │ ├─ amendment_type│            │
│  │ ├─ igst_amount   │         │ ├─ old_value     │            │
│  │ ├─ cancelled_by  │         │ ├─ new_value     │            │
│  │ ├─ created_by    │         │ └─ amended_by    │            │
│  │ └─ updated_by    │         └──────────────────┘            │
│  └──────────────────┘         ┌──────────────────┐            │
│  ┌──────────────────┐         │po_cancellations  │            │
│  │po_items          │         │ ├─ po_id         │            │
│  │ ├─ po_master_id  │         │ ├─ reason        │            │
│  │ ├─ product_id    │         │ ├─ refund_amount │            │
│  │ ├─ quantity      │         │ ├─ refund_status │            │
│  │ ├─ unit_price    │         │ └─ cancelled_by  │            │
│  │ ├─ batch_number  │         └──────────────────┘            │
│  │ ├─ expiry_date   │         ┌──────────────────┐            │
│  │ ├─ tax_percent   │         │po_receipts       │            │
│  │ ├─ tax_amount    │         │ ├─ receipt_number│            │
│  │ └─ item_status   │         │ ├─ received_qty  │            │
│  └──────────────────┘         │ └─ verified_by   │            │
│  ┌──────────────────┐         └──────────────────┘            │
│  │vendors           │                                          │
│  │ ├─ vendor_id     │         Master Data:                     │
│  │ ├─ vendor_name   │         ┌──────────────────┐            │
│  │ ├─ gst_number    │         │company_details   │            │
│  │ ├─ billing_addr  │         │ ├─ company_name  │            │
│  │ ├─ payment_terms │         │ ├─ gst_number    │            │
│  │ └─ is_active     │         │ └─ bank_details  │            │
│  └──────────────────┘         └──────────────────┘            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagram

### PO Creation Flow

```
User Opens Form
     ↓
  Fill Details
  ├─ Document info
  ├─ Vendor info
  ├─ Delivery details
  └─ Line items (with product search)
     ↓
JavaScript Validation
     ↓
  Real-time Calculations
  ├─ Item totals
  ├─ Discount calculations
  └─ Tax calculations
     ↓
  Submit to Backend
     ↓
  Backend Validation
     ├─ Required fields
     ├─ Minimum items
     └─ Valid amounts
     ↓
  Begin Transaction
     ↓
  Insert PO Header
     ├─ All PO details
     └─ Get auto_increment ID
     ↓
  Insert Line Items
     ├─ For each item
     └─ Link to PO ID
     ↓
  Commit Transaction
     ↓
  Return Success
     ↓
  User Redirected to List
     ↓
  PO Created Successfully ✅
```

### PO Cancellation Flow

```
User Clicks Cancel
     ↓
Load Cancellation Form
     ├─ Show PO details
     ├─ Show amount to refund
     └─ Show warnings
     ↓
Fill Cancellation Details
  ├─ Select reason
  ├─ Enter detailed reason
  ├─ Confirm refund amount
  ├─ Select refund status
  └─ Confirm approvals
     ↓
Form Validation
  ├─ All fields filled
  ├─ Manager approval checked
  └─ Confirmation checked
     ↓
Submit to Backend
     ↓
Backend Processing
     ↓
Begin Transaction
  ├─ Check PO not already cancelled
  ├─ Check PO not already received
  └─ Check user authorization
     ↓
Update PO Status → Cancelled
     ↓
Update All Items → Cancelled
     ↓
Insert Cancellation Record
  ├─ Reason details
  ├─ Refund amount
  └─ User tracking
     ↓
Insert Amendment Record
  └─ Status change history
     ↓
Create Refund Record (if refund initiated)
  ├─ Payment record
  ├─ Refund amount
  └─ Refund tracking
     ↓
Commit Transaction
     ↓
Return Success
     ↓
Redirect to List
     ↓
PO Cancelled ✅
```

### Print/Invoice Flow

```
User Opens PO
     ↓
Select Print Invoice
     ↓
Fetch PO Data
  ├─ PO header
  ├─ All items
  └─ Company details
     ↓
Render Template
  ├─ Company header
  ├─ PO details
  ├─ Vendor information
  ├─ Items table
  ├─ Tax calculations
  ├─ Terms & conditions
  └─ Signature blocks
     ↓
Display in Browser
     ↓
User Options
  ├─ Print to PDF (browser)
  └─ Print to Printer
     ↓
Professional Invoice Ready ✅
```

---

## Database Relationship Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      VENDORS                                │
│  (vendor_id, vendor_name, gst_number, contact, ...)        │
└─────────────────┬───────────────────────────────────────────┘
                  │ 1:N
                  │ vendor_id
                  │
┌─────────────────┴───────────────────────────────────────────┐
│                    PURCHASE_ORDERS                          │
│  (id, po_id, po_date, vendor_id, bill_number, po_status,  │
│   sgst_amount, cgst_amount, igst_amount, grand_total,      │
│   cancelled_by, created_by, updated_by, ...)               │
└──────┬──────────────────────────┬──────────────────────────┘
       │ 1:N                       │ 1:1
       │ po_master_id              │
       │                           │
   ┌───┴──────────┐         ┌─────┴──────────┐
   │              │         │                │
┌──┴────────────┐ │    ┌────┴──────────────┐ │
│  PO_ITEMS     │ │    │COMPANY_DETAILS    │ │
│(po_master_id, │ │    │(id, company_name, │ │
│ product_id,   │ │    │ gst_number, logo) │ │
│ qty, price,   │ │    └───────────────────┘ │
│ tax_amount)   │ │                          │
└────────────────┘ │                          │
                   │ 1:N                      │
                   │ po_id                    │
   ┌───────────────┴──────────────────────────┐
   │
   ├──→ ┌──────────────────────────┐
   │    │  PO_PAYMENTS             │
   │    │(po_id, payment_date,     │
   │    │ payment_method, amount)  │
   │    └──────────────────────────┘
   │
   ├──→ ┌──────────────────────────┐
   │    │  PO_AMENDMENTS           │
   │    │(po_id, amendment_type,   │
   │    │ old_value, new_value)    │
   │    └──────────────────────────┘
   │
   └──→ ┌──────────────────────────┐
        │  PO_CANCELLATIONS        │
        │(po_id, reason, refund_   │
        │ amount, cancelled_by)    │
        └──────────────────────────┘
```

---

## Status Management

### PO Status Lifecycle

```
        ┌─────────────────────────────────────────────┐
        │                                             │
        v                                             │
    ┌───────┐    ┌─────┐    ┌────────┐   ┌──────────┐
    │ DRAFT │───→│SENT │───→│PENDING │──→│APPROVED  │
    └───────┘    └─────┘    └────────┘   └──────────┘
        ^                        │            │
        │                        │            v
        │                        │        ┌──────────┐
        └────────────────────────┴────────│PARTIALLY │
                                          │ RECEIVED │
                                          └──────────┘
                                              │
                                              v
                                          ┌──────────┐
                                          │ RECEIVED │
                                          └──────────┘

Cancellation Point:
                        ┌──────────────┐
                        │  CANCELLED   │
                        └──────────────┘
                        Can be cancelled from:
                        Draft, Sent, Pending, Approved, Partially Received
```

### Item Status Lifecycle

```
    ┌─────────┐    ┌─────────┐    ┌──────────┐
    │PENDING  │───→│PARTIAL  │───→│RECEIVED  │
    └─────────┘    └─────────┘    └──────────┘
        │
        └──→ ┌──────────┐
             │CANCELLED │
             └──────────┘

        Or

             ┌──────────┐
             │REJECTED  │
             └──────────┘
```

### Refund Status Lifecycle

```
    ┌─────────┐    ┌──────────┐    ┌──────────┐
    │PENDING  │───→│INITIATED │───→│COMPLETED │
    └─────────┘    └──────────┘    └──────────┘
```

---

## Security & Validation Layers

```
┌─────────────────────────────────────────────────────────┐
│ CLIENT SIDE VALIDATION                                  │
│  ├─ Required fields check                               │
│  ├─ Email format validation                             │
│  ├─ Positive number validation                          │
│  ├─ Date format validation                              │
│  └─ Real-time calculations                              │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ↓ AJAX Request
                        │
┌───────────────────────┴─────────────────────────────────┐
│ SERVER SIDE VALIDATION                                  │
│  ├─ Required fields check                               │
│  ├─ Data type validation                                │
│  ├─ Business logic validation                           │
│  ├─ Authorization check                                 │
│  └─ Database constraints                                │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ↓ Prepared Statement
                        │
┌───────────────────────┴─────────────────────────────────┐
│ DATABASE LEVEL SECURITY                                 │
│  ├─ Prepared statements (prevent SQL injection)         │
│  ├─ Foreign key constraints                             │
│  ├─ Unique constraints                                  │
│  ├─ Check constraints (if supported)                    │
│  └─ Data type enforcement                               │
└─────────────────────────────────────────────────────────┘
```

---

## Calculation Flow

```
Line Item Calculation:
────────────────────

Quantity × Unit Price = Line Amount
    │         │           │
    └─────┬───┘           │
          │               │
    Discount (%) Applied to Line Amount
          │               │
          └─────┬─────────┘
                │
            = Taxable Amount
                │
    Tax (%) Applied to Taxable Amount
                │
            = Tax Amount
                │
        Line Amount - Discount + Tax Amount
                │
            = Item Total

─────────────────────────────────────

PO Level Calculation:
────────────────────

Sum of Item Totals = Sub Total
    │
Discount (-) = Discounted Amount
    │
Line-level Tax (-) = Taxable Amount
    │
SGST + CGST + IGST Applied = Total Tax
    │
Round Off Added (+/-) = Adjustment
    │
    = GRAND TOTAL

─────────────────────────────────────

Tax Calculation:
────────────────

Taxable Amount × SGST% = SGST Amount
Taxable Amount × CGST% = CGST Amount
Taxable Amount × IGST% = IGST Amount

Total Tax = SGST Amount + CGST Amount + IGST Amount
```

---

## Error Handling & Recovery

```
Error Occurs
    │
    ├─→ Validation Error
    │       │
    │       └─→ Return validation message
    │           User corrects & resubmits
    │
    ├─→ Database Error
    │       │
    │       ├─→ Transaction Rollback
    │       │   (All changes undone)
    │       │
    │       └─→ Log error
    │           Return error message
    │
    ├─→ Authorization Error
    │       │
    │       └─→ Return auth error
    │           User not allowed
    │
    └─→ System Error
            │
            ├─→ Rollback
            ├─→ Log error details
            └─→ Return generic error
                Admin notified
```

---

## Performance Optimization

### Indexing Strategy

```
Frequently Queried Fields:
├─ purchase_orders.po_id (UNIQUE)
├─ purchase_orders.po_date (INDEX)
├─ purchase_orders.po_status (INDEX)
├─ purchase_orders.vendor_id (INDEX)
├─ purchase_orders.delete_status (INDEX)
├─ po_items.po_master_id (INDEX)
├─ po_items.product_id (INDEX)
└─ vendors.is_active (INDEX)
```

### Query Optimization

```
Avoid N+1 Queries:
├─ Use JOINs for related data
├─ Fetch product details in one query
└─ Batch operations where possible

Caching Opportunities:
├─ Company details (rarely changes)
├─ Vendor list (semi-static)
└─ Product list (semi-static)
```

---

## Audit Trail & Compliance

```
Every Transaction Recorded:
└─ PO Creation
   ├─ created_by (User ID)
   ├─ created_at (Timestamp)
   └─ All PO details

Every Update Recorded:
└─ PO Modification
   ├─ updated_by (User ID)
   ├─ updated_at (Timestamp)
   ├─ Amendment record created
   └─ Old & new values stored

Every Cancellation Recorded:
└─ po_cancellations table
   ├─ cancelled_by_id
   ├─ cancellation_date
   ├─ reason_details
   ├─ refund_amount
   ├─ refund_date
   └─ Approval tracking

Every Payment Recorded:
└─ po_payments table
   ├─ payment_date
   ├─ payment_method
   ├─ amount_paid
   ├─ reference details
   └─ recorded_by

Full Traceability:
└─ Query any PO's complete history
   ├─ When created
   ├─ Who created it
   ├─ All changes made
   ├─ When cancelled
   ├─ Why cancelled
   ├─ Refund status
   └─ All payments received
```

---

## System Integration Points

```
Future Integration Ready:
├─ Email Notifications
│  ├─ PO created → Send to vendor
│  ├─ PO cancelled → Send cancellation notification
│  └─ Payment received → Send receipt
│
├─ Accounting System
│  ├─ PO created → Create journal entry
│  ├─ Payment received → Update GL
│  └─ Cancellation → Reverse entry
│
├─ Inventory Management
│  ├─ PO created → Reserve stock (if needed)
│  ├─ Goods received → Update inventory
│  └─ Cancellation → Release reservation
│
├─ Reporting & Analytics
│  ├─ PO trends
│  ├─ Vendor performance
│  ├─ Payment analytics
│  └─ Tax summary
│
└─ Mobile App
   ├─ PO approval workflow
   ├─ Print from mobile
   └─ Payment tracking
```

---

**This architecture ensures a robust, scalable, and maintainable Professional PO & Tax Invoice System!**
