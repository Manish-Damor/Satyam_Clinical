# Professional Pharmacy Inventory ERP System - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

### Step 1: Import Database Schema

1. Open PhpMyAdmin
2. Select database: `satyam_clinical`
3. Go to **Import** tab
4. Browse and select: `dbFile/pharmacy_erp_schema.sql`
5. Click **Import**

✅ All tables will be created automatically

---

## 📝 Core Features Overview

### 1️⃣ Manage Medicines (`manage_medicine.php`)

**What it does:**

- View all medicines with real-time stock
- See low stock alerts
- Track expiry dates
- Filter by brand, category, or stock status

**How to use:**

```
Dashboard → Manage Medicines
↓
See statistics at top
↓
Use filters to find specific medicines
↓
Click batch icon to manage batches
```

### 2️⃣ Add Medicine (`add_medicine.php`)

**Required Fields:**

- Medicine Name (e.g., "Crocin 500mg")
- Composition (e.g., "Paracetamol 500mg")
- Manufacturer
- Category
- Product Type (Tablet, Capsule, Syrup, etc.)
- Unit Type (Strip, Box, etc.)
- Pack Size
- HSN Code
- GST Rate
- Reorder Level

**Best Practice:**
Always set a reorder level to track low stock automatically.

### 3️⃣ Manage Suppliers (`manage_suppliers.php`)

**What it shows:**

- All suppliers with contact details
- Total POs and purchase amounts
- Verification status
- Performance metrics

**Key Information to Add:**

- Supplier name & code
- GST number (15 digits)
- Credit terms (default: 30 days)
- Contact person & phone

### 4️⃣ Add Supplier (`add_supplier.php`)

**Sections to Fill:**

```
1. COMPANY INFO
   ├─ Supplier Code (optional but recommended)
   ├─ Supplier Name *
   ├─ Company Name
   └─ Contact Person

2. CONTACT INFO
   ├─ Email
   ├─ Phone *
   └─ Alternate Phone

3. ADDRESS
   ├─ Full Address *
   ├─ City, State, Pincode
   └─ Country (default: India)

4. TAX & COMPLIANCE
   ├─ GST Number (15 chars)
   └─ PAN Number (10 chars)

5. PAYMENT TERMS
   ├─ Credit Days (default: 30)
   └─ Payment Terms
```

### 5️⃣ Manage Batches (`manage_batches.php`)

**Shows for Each Medicine:**

- All batches with batch numbers
- Stock breakdown:
  - Available (can sell)
  - Reserved (on hold)
  - Damaged (unusable)
- MRP and purchase rate
- Expiry status with color codes:
  - 🟢 Green (OK)
  - 🟡 Yellow (Warning - 90 days)
  - 🔴 Red (Critical - 30 days or Expired)
- Supplier information

### 6️⃣ Add Batch (`add_batch.php`)

**Critical Information:**

```
BATCH INFORMATION
├─ Batch Number (UNIQUE per medicine) *
├─ Manufacturing Date
├─ Expiry Date * (most important)
└─ Supplier

STOCK INFORMATION
├─ Available Quantity * (can sell)
├─ Reserved Quantity (on order/hold)
└─ Damaged Quantity

PRICING
├─ Purchase Rate * (what you paid)
└─ MRP * (selling price)

STATUS
└─ Active/Blocked/Damaged
```

**Pro Tip:** Always add the supplier so you can track performance.

### 7️⃣ Inventory Reports (`inventory_reports.php`)

**6 Report Types:**

**A) Inventory Summary**

- All medicines with current stock
- Stock values in ₹
- Batch count per medicine
- Total inventory value

**B) Low Stock Alert**

- Medicines below reorder level
- How much to order
- Preferred supplier
- Urgency indicators

**C) Expiry Tracking**

- All batches by expiry date
- Days remaining per batch
- Alert levels
- Quantity at risk

**D) Stock Movements**

- All in/out transactions
- Date range filter
- Movement type (Purchase, Sales, Adjustment, etc.)
- Audit trail

**E) Batch Analysis**

- Performance by product
- Active vs expired batches
- Average prices
- Batch trends

**F) Supplier Performance**

- Total POs per supplier
- Total purchase amount
- On-time delivery
- Completion rate

**Export Features:**

- 📥 **CSV Export** - for Excel analysis
- 🖨 **Print** - formatted for printing

---

## 🎯 Daily Workflow Example

### Morning (Check Alerts)

1. Open **Manage Medicines**
2. Look at statistics:
   - Out of Stock items → Order immediately
   - Low Stock items → Send PO to supplier
   - Check expiry alerts

### Mid-Day (Receiving Stock)

1. Go to **Manage Batches** for medicine
2. Click **Add Batch**
3. Enter batch details from supplier invoice
4. System automatically tracks stock movements

### End of Day (Reports)

1. Run **Low Stock Alert** report
2. Run **Expiry Tracking** report
3. Plan next day's orders

### Weekly

1. Generate **Inventory Summary** report
2. Calculate stock value
3. Check **Supplier Performance**
4. Plan PO requirements

---

## 📊 Status Indicators & Colors

### Stock Status

| Status          | Meaning             | Action       |
| --------------- | ------------------- | ------------ |
| 🟢 IN STOCK     | Normal              | Monitor      |
| 🟡 LOW STOCK    | Below reorder level | Order soon   |
| 🔴 OUT OF STOCK | Zero quantity       | Order URGENT |

### Expiry Status

| Status      | Days Left  | Action           |
| ----------- | ---------- | ---------------- |
| 🟢 OK       | > 90 days  | Normal           |
| 🟡 WARNING  | 30-90 days | Plan clearance   |
| 🔴 CRITICAL | < 30 days  | Urgent clearance |
| ⚫ EXPIRED  | Past date  | Remove & audit   |

### Batch Status

| Status  | Meaning            |
| ------- | ------------------ |
| Active  | Available for use  |
| Blocked | On hold, don't use |
| Damaged | Unusable           |
| Expired | Past expiry date   |

---

## 🔧 Configuration Settings

### For Each Medicine, Set:

1. **Reorder Level** - When to alert low stock
   - Example: For high-demand medicines: 50 units
   - For slow-movers: 10 units

2. **Preferred Supplier** - Default supplier (set in Reorder Management)

### For Each Supplier, Set:

1. **Credit Days** - Payment terms
2. **GST Number** - For compliance
3. **Verification Status** - Mark as verified after first PO

---

## 📈 Analytics Dashboard (Home Page)

**Key Metrics Displayed:**

```
┌─────────────────┬──────────────┬─────────────┬──────────────┐
│ Total Medicines │ Total Stock  │ Low Stock   │ Out of Stock │
│      45         │    15,240    │     8       │      2       │
└─────────────────┴──────────────┴─────────────┴──────────────┘
```

**Use these to:**

- ✅ Identify critical stock situations
- ✅ Plan purchasing
- ✅ Allocate inventory
- ✅ Monitor overall inventory health

---

## 🛡️ Data Entry Best Practices

### Batch Numbers

❌ Wrong: "B1", "NEW", Random numbers  
✅ Right: "BATCH20260215", "BZ2406001", "CIPLA2026FEB"

**Format Suggestion:** `SUPPLIER_CODE + YYYYMMDD + SEQ`

### HSN Codes

- Always use official HSN codes (8 digits in India)
- Required for GST compliance
- Find in official GST portal

### Reorder Levels

- Consider: Lead time, demand, storage space
- Formula: `(Avg Daily Usage × Lead Time in Days) × 1.5`

### Expiry Dates

- Enter actual expiry date from medicine packet
- System will auto-calculate days remaining
- Critical threshold: 30 days
- Warning threshold: 90 days

---

## 🆘 Troubleshooting Guide

### Problem: Medicine not appearing in reports

**Solution:**

1. Check if `status = Active`
2. Wait 24 hours for cache refresh
3. Manually refresh page with F5

### Problem: Low stock alert not showing

**Solution:**

1. Edit medicine and check `Reorder Level` value
2. Verify current stock is actually below reorder level
3. Check if batch status is "Active"

### Problem: Batch not saving

**Solution:**

1. Ensure expiry date is in future
2. Batch number must be unique per medicine
3. Quantity must be > 0

### Problem: Supplier not selectable in batch form

**Solution:**

1. Check if supplier status = "Active"
2. Supplier must exist in database
3. Try refreshing page

---

## 📱 Mobile Access

All pages are **mobile-responsive**!

Access on phone:

- View inventory
- Check low stock alerts
- Add batches in warehouse
- View reports

---

## 🔐 Security Notes

**Access Control (Recommended):**

- Admin: Full access
- Manager: View + Edit
- Staff: View only
- Supplier: View own POs only

**Data Protection:**

- All transactions logged
- User audit trail maintained
- Backup database daily

---

## 📞 Need Help?

**Check These:**

1. ✅ Database schema imported? Run: `dbFile/pharmacy_erp_schema.sql`
2. ✅ Tables created? Check PhpMyAdmin
3. ✅ Suppliers added? Create at least 1 supplier
4. ✅ Medicines have reorder level? Set > 0
5. ✅ Batches have expiry dates? Must be valid date

**Common Error Messages:**

- "Medicine already exists" → Duplicate product name + brand
- "Batch number exists" → Use unique batch number
- "Invalid date" → Expiry date must be in future format (YYYY-MM-DD)

---

## 🎓 Learning Path

### Day 1: Setup

- [ ] Import database
- [ ] View system pages
- [ ] Add 1-2 medicines
- [ ] Understand batch concept

### Day 2: Operations

- [ ] Add suppliers
- [ ] Add batches for medicines
- [ ] Check low stock alerts
- [ ] View reports

### Day 3: Mastery

- [ ] Manage inventory daily
- [ ] Generate reports
- [ ] Track supplier performance
- [ ] Plan reorders

---

## 📋 System Features Checklist

**Inventory Control**

- ✅ Real-time stock tracking
- ✅ Batch-level management
- ✅ Stock status indicators
- ✅ Audit trail

**Expiry Management**

- ✅ Automatic expiry alerts
- ✅ Color-coded warnings
- ✅ Days-remaining calculator
- ✅ Expired stock visibility

**Low Stock Management**

- ✅ Reorder level tracking
- ✅ Low stock alerts
- ✅ Out of stock indicator
- ✅ Supplier assignment

**Reporting**

- ✅ 6 report types
- ✅ Date filtering
- ✅ CSV export
- ✅ Print functionality
- ✅ Stock value calculation

**Supplier Management**

- ✅ Complete supplier database
- ✅ Tax information tracking
- ✅ Performance metrics
- ✅ Credit terms management

---

## 🚀 Next Steps

1. **Import the database schema**
   - File: `dbFile/pharmacy_erp_schema.sql`

2. **Create suppliers**
   - Go to: `manage_suppliers.php`
   - Add your regular suppliers

3. **Add medicines** (if not already present)
   - Go to: `add_medicine.php`
   - Fill in all required fields

4. **Create batches**
   - For each medicine: `manage_medicines.php` → Click batch icon
   - Add current stock as batches

5. **Run first report**
   - Go to: `inventory_reports.php`
   - Generate "Inventory Summary"
   - Verify all items appear

6. **Set up alerts**
   - Edit each medicine
   - Set appropriate reorder level

7. **Start using daily**
   - Monitor alerts
   - Update stock as you receive/sell
   - Generate reports weekly

---

## 📞 Support Resources

- **Schema File:** `dbFile/pharmacy_erp_schema.sql`
- **Documentation:** `ERP_SYSTEM_DOCUMENTATION.md`
- **Database Structure:** Review in PhpMyAdmin

**Ready to start? Go to:** `manage_medicine.php`

---

**Version:** 1.0 | **Last Updated:** 2026-02-16 | **Status:** Production Ready
