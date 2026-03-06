# 🔧 Troubleshooting Guide - Pages Not Loading

## Issue

Pages show a spinning loading bar but never complete loading.

## What This Means

This typically indicates one of these issues:

1. **PHP syntax error** - Code is broken
2. **Database connection failed** - Can't connect to MySQL
3. **Database query hanging** - Query takes too long or fails silently
4. **Missing required files** - Includes not found
5. **Infinite loop** - Code keeps looping

---

## 🔍 Step-by-Step Diagnosis

### Step 1: Run Diagnostics Page

Open this in your browser:

```
http://localhost/Satyam_Clinical/DIAGNOSE.php
```

This will show you:

- ✅ PHP is working
- ✅ Database connection status
- ✅ Database tables exist
- ✅ Query results
- ✅ All required files present

**If you see all green checkmarks** → Go to Step 2
**If you see any red X** → Go to the corresponding section below

---

## ⚠️ Common Issues & Fixes

### Issue 1: Database Connection Failed

**Error Message:**

```
❌ Database Connection Failed
Error: Connection refused / Access denied for user 'root'
```

**Fixes:**

1. Make sure XAMPP MySQL is running

   - Open XAMPP Control Panel
   - Start "MySQL" service

2. Verify credentials in `constant/connect.php`:

   ```php
   $localhost = "localhost";  // Should be localhost
   $username = "root";         // Should be root
   $password = "";             // Should be empty (no password)
   $dbname = "satyam_clinical"; // Database name
   ```

3. If still not working, try connecting via phpMyAdmin:
   - Open: http://localhost/phpmyadmin
   - If phpMyAdmin won't open, MySQL isn't running

---

### Issue 2: Tables Not Found

**Error Message:**

```
❌ purchase_orders table NOT FOUND
❌ po_items table NOT FOUND
```

**Fixes:**

1. Import the database schema:

   - Go to phpMyAdmin: http://localhost/phpmyadmin
   - Select your database: `satyam_clinical`
   - Click "Import" tab
   - Upload file: `dbFile/purchase_order_tables.sql`
   - Click "Go" button

2. If database doesn't exist:

   - In phpMyAdmin, create new database called `satyam_clinical`
   - Then import the SQL file as above

3. Verify tables were created:
   - In phpMyAdmin, select database
   - Should see `purchase_orders` and `po_items` in table list

---

### Issue 3: Products Table Not Found

**Error Message:**

```
❌ products table NOT FOUND
```

**Fixes:**
This table should already exist from your original setup. If not:

1. In phpMyAdmin, run this SQL:
   ```sql
   CREATE TABLE IF NOT EXISTS products (
     id INT AUTO_INCREMENT PRIMARY KEY,
     productName VARCHAR(255),
     category INT,
     quantity INT,
     status INT
   );
   ```

---

### Issue 4: Query Fails

**Error Message:**

```
❌ Query failed: [Error message here]
```

**Common Errors:**

- `Unknown column` → Column doesn't exist in table
- `Syntax error` → SQL query is malformed
- `Access denied` → User permissions issue

**Fixes:**

1. Check column names in purchase_orders table:

   - In phpMyAdmin, select database
   - Click `purchase_orders` table
   - Should have these columns:
     - id
     - po_id
     - po_date
     - vendor_name
     - vendor_contact
     - vendor_email
     - vendor_address
     - expected_delivery_date
     - po_status
     - sub_total
     - discount
     - gst
     - grand_total
     - payment_status
     - notes
     - delete_status
     - created_at
     - updated_at

2. If columns are missing, recreate the table:
   - Delete the purchase_orders and po_items tables
   - Import the SQL file again

---

## 🛠️ Manual Testing

If the DIAGNOSE page works, test the pages one by one:

### Test 1: List Page

```
http://localhost/Satyam_Clinical/purchase_order.php
```

Should show: Table with purchase orders (may be empty if no data)

### Test 2: Create Page

```
http://localhost/Satyam_Clinical/add-purchase-order.php
```

Should show: Form with fields to create a purchase order

### Test 3: Check Browser Console

If page shows spinner but doesn't load:

1. Press **F12** (Open Developer Tools)
2. Click "Console" tab
3. Look for red error messages
4. Share the exact error message

---

## 🧪 Advanced Diagnostics

### Check Apache Error Log

XAMPP Apache error log location:

```
C:\xampp\apache\logs\error.log
```

1. Open in Notepad
2. Look for recent PHP errors
3. Share the errors with support

### Check PHP Error Log

MySQL error log:

```
C:\xampp\mysql\data\
```

Look for recent errors

### Test Database Directly

In phpMyAdmin:

```sql
SELECT po_id, po_date, vendor_name
FROM purchase_orders
WHERE delete_status = 0
ORDER BY po_date DESC
LIMIT 5;
```

Should return records if any exist.

---

## 📋 Pre-Flight Checklist

Before proceeding, verify:

- [ ] XAMPP MySQL service is **running**
- [ ] XAMPP Apache service is **running**
- [ ] Database `satyam_clinical` **exists**
- [ ] Tables `purchase_orders` and `po_items` **exist**
- [ ] All PO files are in correct locations
- [ ] Browser can access http://localhost/Satyam_Clinical/DIAGNOSE.php
- [ ] DIAGNOSE.php shows all green checkmarks

---

## 🚨 Still Not Working?

1. **Restart XAMPP**

   - Stop all services
   - Wait 5 seconds
   - Start MySQL and Apache again

2. **Clear Browser Cache**

   - Press Ctrl+Shift+Delete
   - Clear "Cached images and files"
   - Try again

3. **Check File Permissions**

   - Right-click folder: C:\xampp\htdocs\Satyam_Clinical
   - Properties → Security → Edit
   - Give "NETWORK SERVICE" full permissions
   - Apply to this folder and all subfolders

4. **Reinstall Database Schema**
   - Delete purchase_orders and po_items tables from phpMyAdmin
   - Re-import dbFile/purchase_order_tables.sql

---

## 📞 Error Reference

| Error                    | Cause                | Solution                         |
| ------------------------ | -------------------- | -------------------------------- |
| `Connection refused`     | MySQL not running    | Start MySQL in XAMPP             |
| `Access denied for user` | Wrong password       | Check credentials in connect.php |
| `Unknown database`       | Database not created | Create database in phpMyAdmin    |
| `Table doesn't exist`    | SQL not imported     | Import purchase_order_tables.sql |
| `Undefined variable`     | Include file missing | Check all includes exist         |
| `Syntax error in SQL`    | Malformed query      | Check SQL in query               |
| `Infinite loading`       | Hanging query        | Check database connectivity      |

---

## ✅ Success Indicators

Once everything works, you'll see:

- ✅ DIAGNOSE.php shows all green checks
- ✅ purchase_order.php loads and shows table (even if empty)
- ✅ add-purchase-order.php loads and shows form
- ✅ Can click "Add Purchase Order" button
- ✅ Form submits successfully
- ✅ New PO appears in list

---

**Created:** January 16, 2026  
**Last Updated:** January 16, 2026  
**Status:** Ready to Use
