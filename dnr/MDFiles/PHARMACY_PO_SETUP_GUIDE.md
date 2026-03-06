# Pharmacy Purchase Order System - Setup Guide

## Overview

This is a professional, production-ready pharmacy purchase order management system designed for efficient daily operations with real pharmaceutical data handling.

## Features

### ✅ Core Features

1. **Supplier Management**
   - Add/Edit/Delete suppliers with complete details
   - Track supplier statistics (orders, amounts)
   - Multiple contact points and addresses
   - Payment terms and bank details storage

2. **Medicine/Product Management**
   - Store medicines with HSN codes, batch numbers, expiry dates
   - Track MRP and PTR (for internal use, hidden in print)
   - Auto-population of medicine details in PO
   - Search with autocomplete

3. **Purchase Order Creation**
   - Auto-generated PO numbers (PO-YYYYMM-XXXX format)
   - Professional invoice-like interface
   - Auto-calculation of taxes (CGST/SGST/IGST)
   - Line item discounts
   - Real-time total calculations
   - PTR visible in form but NOT printed

4. **PO Management**
   - View all active POs
   - Edit PO details (draft status)
   - Print professional invoices
   - Status tracking (Draft, Sent, Pending, Confirmed, Received)

5. **PO Cancellation System**
   - Non-destructive cancellation (marks as cancelled)
   - Detailed cancellation reason tracking
   - Refund status monitoring
   - Complete audit trail in cancellation log
   - Supplier statistics auto-update

6. **Reporting & Analytics**
   - Cancelled PO history
   - Supplier performance tracking
   - Payment tracking
   - Goods receipt tracking

## Database Setup

### Step 1: Create Tables

Run the following SQL files in sequence:

```bash
1. dbFile/pharmacy_po_schema.sql    # Main PO system tables
2. dbFile/sample_medicines.sql       # Sample medicine data
```

### Step 2: Tables Created

- `suppliers` - Supplier/vendor master
- `medicine_details` - Medicine/product details
- `purchase_order` - PO master
- `purchase_order_items` - PO line items
- `po_cancellation_log` - Cancellation tracking
- `po_payment_log` - Payment tracking
- `po_receipt` - Goods receipt tracking
- `po_amendments` - Amendment history

## File Structure

```
root/
├── create_po.php                    # Create new PO
├── po_list.php                      # Active POs listing
├── po_cancelled.php                 # Cancelled POs history
├── cancel_po.php                    # Cancel PO form
├── supplier.php                     # Supplier management
├── print_po.php                     # PO print (PTR hidden)
├── view_po.php                      # View PO details
├── edit_po.php                      # Edit PO
│
├── php_action/
│   ├── saveSupplier.php            # Save supplier
│   ├── getSupplier.php             # Fetch supplier details
│   ├── deleteSupplier.php          # Delete supplier
│   ├── searchMedicines.php         # Search medicines (autocomplete)
│   ├── createPurchaseOrder.php     # Create new PO
│   ├── cancelPO.php                # Cancel PO
│   ├── getCancellationDetails.php  # Get cancellation info
│   └── ...
│
└── dbFile/
    ├── pharmacy_po_schema.sql      # Main schema
    └── sample_medicines.sql         # Sample data
```

## Key Features in Detail

### 1. Purchase Order Creation (create_po.php)

- **Auto-populated fields**: Once supplier is selected, all details auto-fill
- **Medicine search**: Type 2+ characters to search medicines
- **Auto-fill on selection**: Medicine details (batch, expiry, MRP, PTR, rate) auto-fill
- **Real-time calculations**: All amounts calculated on the fly
- **Tax handling**:
  - Individual line item tax percentage
  - CGST/SGST for intra-state, IGST for inter-state
  - Auto-calculation based on quantities and rates
- **Discount handling**: Both percentage and fixed discounts
- **Status tracking**: Draft → Sent → Pending → Confirmed → Received

### 2. Supplier Management (supplier.php)

- Complete supplier profile with:
  - Company details (GST, PAN)
  - Contact information
  - Billing & Shipping addresses
  - Payment terms & credit limits
  - Bank account details (optional)
  - Statistics (total orders, amounts)

### 3. PO Cancellation (cancel_po.php)

- **Non-destructive**: POs are never deleted, only marked cancelled
- **Detailed tracking**:
  - Cancellation reason (predefined + custom)
  - Detailed notes/explanation
  - Expected refund amount
  - Refund status tracking
- **Audit trail**: Complete log of who cancelled and when
- **Supplier updates**: Automatically reverses supplier statistics

### 4. Print/Export (print_po.php)

- **Professional layout**: Matches real pharmacy invoices
- **PTR handling**:
  - Visible in creation form (for pharmacist reference)
  - HIDDEN in printed version (PTR is internal)
  - Light yellow background in form for easy identification
- **Cancelled status**: Shows "CANCELLED" watermark on cancelled POs
- **Complete details**:
  - Supplier info
  - Delivery address
  - All line items with taxes
  - Tax calculations
  - Terms & conditions
  - Signature blocks
  - Cancellation details (if applicable)

## Database Schema Highlights

### purchase_order Table

```sql
- po_id (Primary Key)
- po_number (Unique, Format: PO-YYYYMM-XXXX)
- supplier_id (Foreign Key)
- po_date, expected_delivery_date, actual_delivery_date
- po_status (Draft/Sent/Pending/Confirmed/Partially Received/Received/Cancelled)
- Detailed tax fields (CGST, SGST, IGST percentages & amounts)
- Cancellation fields (cancelled_status, cancelled_by, cancelled_date, reason, details)
- Audit fields (created_by, created_at, updated_by, updated_at)
```

### purchase_order_items Table

```sql
- item_id (Primary Key)
- po_id (Foreign Key)
- medicine_id (Foreign Key)
- Batch & expiry tracking
- Quantity & pricing
- Line-level tax calculation
- Item status (Pending/Partial/Received/Rejected/Cancelled)
```

### po_cancellation_log Table

```sql
- Complete cancellation record
- Reason and detailed explanation
- Refund tracking (Pending/Initiated/Completed)
- Approval workflow fields
- Audit trail
```

## API Endpoints (PHP Action Files)

### Suppliers

- `saveSupplier.php` - POST - Create/Update supplier
- `getSupplier.php` - GET - Fetch supplier details (JSON)
- `deleteSupplier.php` - GET/POST - Delete supplier

### Medicines

- `searchMedicines.php?search=query` - GET - Search medicines (JSON)

### Purchase Orders

- `createPurchaseOrder.php` - POST - Create new PO (JSON)
- `cancelPO.php` - POST - Cancel PO with reasons
- `getCancellationDetails.php?po_id=X` - GET - Fetch cancellation info (JSON)

## Calculations & Formulas

### Per Line Item:

```
Line Amount = Quantity × Unit Price
Item Discount = Line Amount × (Discount % / 100)
Taxable Amount = Line Amount - Item Discount
Tax Amount = Taxable Amount × (Tax % / 100)
Item Total = Taxable Amount + Tax Amount
```

### PO Totals:

```
Sub Total = SUM(Line Amount for all items)
Total Discount = SUM(Item Discount) + (Sub Total × Discount %)
Taxable Amount = Sub Total - Total Discount
CGST Amount = Taxable Amount × 9%  (if intra-state)
SGST Amount = Taxable Amount × 9%  (if intra-state)
IGST Amount = Taxable Amount × 18% (if inter-state)
Grand Total = Taxable Amount + CGST + SGST + IGST + Round Off
```

## Important Notes

### PTR (Pharmacy Trade Rate)

- **Stored**: In `medicine_details.ptr` column
- **Used in form**: For easy reference to pharmacist
- **Displayed**: Light yellow background in line items
- **Printed**: NOT visible in printed PO (hidden via CSS)
- **Purpose**: Internal reference for pharmacy staff

### Security

- All inputs are prepared statements (prevent SQL injection)
- Sessions required for user tracking
- Soft deletes used (cancelled_status flag instead of actual deletion)
- Complete audit trail maintained

### Performance Optimization

- Indexed foreign keys for fast lookups
- Indexed search fields (medicine_name, hsn_code, supplier_name, gst_number)
- Indexed status fields for filtering
- LIMIT used in searches (max 30 results)

### Scalability

- Supports thousands of POs efficiently
- Batch operations with transactions
- Index coverage for reporting queries
- Optimized joins for complex queries

## Usage Workflow

### Day 1: Setup

1. Run `pharmacy_po_schema.sql`
2. Run `sample_medicines.sql`
3. Go to `supplier.php` and add your suppliers
4. Update `medicine_details` with your actual medicines

### Daily Usage

1. Create PO: Go to `create_po.php`
2. Select supplier → Details auto-fill
3. Add medicines by typing name
4. Adjust rates and quantities
5. Review calculations automatically
6. Submit PO
7. View list at `po_list.php`
8. Print when needed (PTR not visible)

### If PO Needs Cancellation

1. Go to `po_list.php`
2. Click Cancel button
3. Provide reason and details
4. Confirm cancellation
5. View history at `po_cancelled.php`

## Troubleshooting

### Tables Not Found

- Ensure `pharmacy_po_schema.sql` is executed
- Check database name matches in connection

### Medicine Search Returns Empty

- Ensure `sample_medicines.sql` is executed
- Check `is_active = 1` in medicine_details

### Calculations Not Working

- Ensure JavaScript is enabled
- Check browser console for errors
- Verify decimal values are numeric

### PTR Not Showing

- Check `medicine_details` has ptr value > 0
- Reload page
- Check browser cache

## Future Enhancements

1. **PDF Export**: Direct PDF generation
2. **Email Integration**: Send PO to supplier email
3. **Mobile App**: Mobile view for field orders
4. **Barcode Scanning**: Batch barcode scanning
5. **Multi-currency**: Support for different currencies
6. **Approval Workflow**: Multi-level approvals
7. **API Integration**: Connect with accounting software
8. **Dashboard**: Analytics and insights

---

**Version**: 1.0  
**Last Updated**: January 2026  
**Author**: Satyam Clinical System
