# Purchase Order Module - Setup Guide

## Overview

This Purchase Order module has been added to the Satyam Clinical project to manage purchase orders with vendors, track inventory orders, and generate printable PO documents.

## Files Created

### Main Pages

1. **purchase_order.php** - Main page to view all purchase orders with list table
2. **add-purchase-order.php** - Form to create new purchase orders
3. **edit-purchase-order.php** - Form to edit existing purchase orders
4. **print-purchase-order.php** - Printable purchase order document

### PHP Action Files (php_action/)

1. **createPurchaseOrder.php** - Handles creating new purchase orders
2. **editPurchaseOrder.php** - Handles updating purchase orders
3. **removePurchaseOrder.php** - Handles soft delete of purchase orders

### JavaScript Files (custom/js/)

1. **purchase_order.js** - JavaScript utilities for purchase order operations

### Database Files (dbFile/)

1. **purchase_order_tables.sql** - SQL script to create required database tables

## Database Setup

Run the SQL script to create the required tables:

```sql
-- Purchase Order Master Table
CREATE TABLE `purchase_orders` (
  `id` - Primary key
  `po_id` - Unique Purchase Order Number (e.g., PO-202501-0001)
  `po_date` - Date of PO creation
  `vendor_name` - Name of vendor
  `vendor_contact` - Contact number
  `vendor_email` - Email address
  `vendor_address` - Full address
  `expected_delivery_date` - Expected delivery date
  `po_status` - Status (Pending, Approved, Received, Cancelled)
  `sub_total` - Total before discount/tax
  `discount` - Discount percentage
  `gst` - GST percentage
  `grand_total` - Final amount
  `payment_status` - Payment status (Pending, Partial, Paid)
  `notes` - Additional notes
  `delete_status` - Soft delete flag
  `created_at` - Timestamp
  `updated_at` - Update timestamp
)

-- Purchase Order Items Table
CREATE TABLE `po_items` (
  `id` - Primary key
  `po_master_id` - Foreign key to purchase_orders
  `product_id` - Product/Medicine ID
  `quantity` - Quantity ordered
  `unit_price` - Price per unit
  `total` - Line total (quantity × unit_price)
  `added_date` - Timestamp
)
```

## Project Structure

The module follows the existing project structure:

```
├── purchase_order.php (Main list page)
├── add-purchase-order.php (Create form)
├── edit-purchase-order.php (Edit form)
├── print-purchase-order.php (Print document)
├── php_action/
│   ├── createPurchaseOrder.php
│   ├── editPurchaseOrder.php
│   └── removePurchaseOrder.php
└── custom/js/
    └── purchase_order.js
```

## Features

### Create Purchase Order

- Auto-generated PO number based on year/month
- Vendor details management
- Add multiple items from product list
- Automatic calculation of totals
- Discount and GST calculation
- Notes section for special instructions

### View Purchase Orders

- List all purchase orders
- Filter by status
- View total amount per PO
- Quick actions (Edit, Delete, Print)

### Edit Purchase Order

- Modify all PO details
- Add/remove items
- Recalculate totals

### Print Purchase Order

- Professional formatted document
- Company header
- Vendor and PO details
- Itemized list
- Calculated totals
- Signature area

## Navigation

The Purchase Order module is accessible from the sidebar:

- **Main Menu**: Purchase Order
  - Add Purchase Order → Create new PO
  - Manage Purchase Orders → View all POs

## User Permissions

Only admin users (userId == 1) can access the Purchase Order module as per the sidebar configuration.

## Integration Notes

- The module integrates with the existing products table
- Follows the same coding style as the invoices/orders module
- Uses jQuery AJAX for form submissions
- Implements soft delete for data integrity
- Uses currency format: ₹ (Indian Rupee)

## How to Use

1. **Create a PO**: Click "Add Purchase Order" → Fill in vendor details → Add items → Click Save
2. **View POs**: Click "Manage Purchase Orders" to see all POs
3. **Edit a PO**: Click edit icon next to any PO → Modify details → Save
4. **Delete a PO**: Click delete icon (soft delete)
5. **Print a PO**: Click print icon to open printable version

## Next Steps for Customization

1. Adjust GST percentage based on your state/products
2. Add company details to print-purchase-order.php header
3. Customize email notifications when PO is created
4. Add purchase order approval workflow
5. Integrate with inventory/stock management
6. Add PO to PDF conversion functionality
