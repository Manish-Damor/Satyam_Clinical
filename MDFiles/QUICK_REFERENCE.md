# Quick Reference Guide - PO & Tax Invoice System

## 🎯 Quick Start (5 minutes)

### 1. Database Migration

```bash
# Backup first
mysqldump -u root satyam_clinical > backup.sql

# Run migration
mysql -u root satyam_clinical < migration_po_schema.sql

# Verify tables exist
SHOW TABLES;  # Should see vendors, company_details, po_payments, etc.
```

### 2. Insert Company Details

```sql
INSERT INTO company_details
(company_name, company_address, company_city, company_state,
 company_pincode, company_contact, company_email, gst_number, pan_number)
VALUES
('SATYAM CLINICAL SUPPLIES', '123 Medical Lane', 'Mumbai', 'Maharashtra',
 '400001', '9876543210', 'info@satyam.com', '27AABCU9603R1Z0', 'AABCU9603R');
```

### 3. Deploy Files

```bash
# Copy to your web root
cp add-purchase-order-new.php /var/www/html/satyam_clinical/
cp purchase_order-new.php /var/www/html/satyam_clinical/
cp print-purchase-order-new.php /var/www/html/satyam_clinical/
cp cancel-purchase-order.php /var/www/html/satyam_clinical/
cp php_action/createPurchaseOrder-new.php /var/www/html/satyam_clinical/php_action/
cp php_action/cancelPurchaseOrder.php /var/www/html/satyam_clinical/php_action/
```

### 4. Update Navigation

Edit your sidebar/menu to link to:

- `add-purchase-order-new.php` (Add PO)
- `purchase_order-new.php` (View POs)

### 5. Test

1. Create test PO
2. Print invoice
3. Cancel PO
4. Check database for records

### Step 2: Access Module

```
Sidebar → Purchase Order
  ├── Add Purchase Order (create)
  └── Manage Purchase Orders (view/edit/delete)
```

### Step 3: Create Your First PO

```
1. Click "Add Purchase Order"
2. Fill vendor details (required)
3. Add items from product list
4. Set discount & GST (optional)
5. Click "Save Purchase Order"
```

---

## 📁 File Structure Summary

```
Satyam_Clinical/
├── purchase_order.php ..................... List page
├── add-purchase-order.php ................. Create form
├── edit-purchase-order.php ................ Edit form
├── print-purchase-order.php ............... Print view
├── php_action/
│   ├── createPurchaseOrder.php ............ Create handler
│   ├── editPurchaseOrder.php .............. Edit handler
│   ├── removePurchaseOrder.php ............ Delete handler
│   └── fetchProducts.php .................. Product fetch
├── custom/js/
│   └── purchase_order.js .................. Helper JS
├── dbFile/
│   └── purchase_order_tables.sql .......... Database schema
└── Documentation/
    ├── IMPLEMENTATION_COMPLETE.md ......... Full details
    ├── PURCHASE_ORDER_SETUP.md ........... Setup guide
    └── PURCHASE_ORDER_TESTING.md ......... Testing guide
```

---

## 🔒 Security Features

✅ SQL Injection Prevention  
✅ XSS Prevention  
✅ Input Validation  
✅ Type Casting  
✅ Secure Deletion  
✅ Error Handling

---

## 💻 Features

✅ Auto-generated PO Numbers  
✅ Multiple Line Items  
✅ Dynamic Calculations  
✅ Discount & GST Support  
✅ Professional Printing  
✅ Soft Delete  
✅ Responsive Design  
✅ AJAX Operations

---

## 📊 Data Validation

| Field          | Validation             |
| -------------- | ---------------------- |
| PO Date        | Required, valid date   |
| Vendor Name    | Required, min 1 char   |
| Vendor Contact | Required, phone format |
| Quantity       | Integer, > 0           |
| Unit Price     | Decimal, >= 0          |
| Items Count    | Minimum 1 item         |

---

## 🔄 Database Operations

### Create PO

```
POST → php_action/createPurchaseOrder.php
Data: PO details + items array
Response: {success: true/false, messages: string}
```

### Edit PO

```
POST → php_action/editPurchaseOrder.php
Data: PO ID + updated details + items array
Response: {success: true/false, messages: string}
```

### Delete PO

```
POST → php_action/removePurchaseOrder.php
Data: PO ID
Response: {success: true/false, messages: string}
```

### Get Products

```
GET → php_action/fetchProducts.php
Response: [{id: 1, productName: "..."}, ...]
```

---

## 🧮 Calculation Formula

```
Item Total = Quantity × Unit Price

SubTotal = Σ Item Totals

Discount₁ = SubTotal × Discount% ÷ 100
After Discount = SubTotal - Discount₁

GST₁ = After Discount × GST% ÷ 100

Grand Total = After Discount + GST₁
```

**Example:**

- Item 1: 10 qty × 500 = 5000
- Item 2: 5 qty × 800 = 4000
- SubTotal = 9000
- Discount = 9000 × 10% = 900
- After Discount = 8100
- GST = 8100 × 5% = 405
- **Grand Total = 8505**

---

## 🎯 Common Tasks

### Create Purchase Order

```
1. Sidebar → Purchase Order → Add Purchase Order
2. Fill all required fields
3. Add items
4. Set discount/GST
5. Save
```

### View All POs

```
1. Sidebar → Purchase Order → Manage Purchase Orders
2. See list with totals and status
```

### Edit Existing PO

```
1. Click Edit button on PO row
2. Modify details
3. Update
```

### Print PO

```
1. Click Print button
2. View formatted document
3. Use browser print (Ctrl+P) to save as PDF
```

### Delete PO

```
1. Click Delete button
2. Confirm deletion
3. PO removed from view (soft deleted)
```

---

## ⚠️ Important Notes

1. **Database Tables Must Be Created First**
   - Import purchase_order_tables.sql before using module

2. **Products Must Exist**
   - Products table must have active products (delete_status = 0)

3. **Admin Access Only**
   - Module restricted to userId = 1 (admin)

4. **Soft Delete**
   - Deleted POs are marked as deleted, not permanently removed
   - Query uses: WHERE delete_status = 0

5. **Auto-Generated PO Numbers**
   - Format: PO-YYYYMM-#### (e.g., PO-202501-0001)
   - Based on current year/month

---

## 🐛 Troubleshooting

| Issue                 | Solution                                 |
| --------------------- | ---------------------------------------- |
| Tables not found      | Import SQL file to database              |
| Products not loading  | Check products table (delete_status = 0) |
| Edit page shows blank | Verify PO ID in URL is valid             |
| Print page blank      | Check PO exists and is not deleted       |
| Calculations wrong    | Verify numeric values are valid          |
| Can't save            | Check all required fields filled         |

---

## 📞 Quick Links

- 📖 Full Documentation: [IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md)
- 🧪 Testing Guide: [PURCHASE_ORDER_TESTING.md](PURCHASE_ORDER_TESTING.md)
- 🛠️ Setup Guide: [PURCHASE_ORDER_SETUP.md](PURCHASE_ORDER_SETUP.md)

---

**Status:** ✅ Production Ready  
**Last Updated:** January 16, 2026  
**Version:** 1.0
