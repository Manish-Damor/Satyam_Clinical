# MEDICINE MODULE - QUICK START GUIDE

## ✅ What's Working Now

### 1. PERFECT AUTOFILL ON SALES INVOICE

**File**: [sales_invoice_enhanced.php](sales_invoice_enhanced.php)

When you select a product:

- ✅ MRP auto-fills (from product master)
- ✅ GST% auto-fills (from product master)
- ✅ Available batches show instantly
- ✅ Batch numbers & quantities display

```
[Select Product] → MRP auto-fills → GST% auto-fills → Batches appear
```

---

### 2. MULTI-BATCH ALLOCATION

**When you enter a quantity**, the system automatically:

- ✅ Checks if quantity available
- ✅ Suggests multiple batches if single batch insufficient
- ✅ Allocates by expiry date (oldest first - FIFO)
- ✅ Shows exact batches to use

**Example**:

```
Product: Paracetamol 650mg
Need: 200 units
Available Batches:
  - Batch1: 50 units (expires 2026-May)    → Allocate 50
  - Batch2: 100 units (expires 2026-Aug)   → Allocate 100
  - Batch3: 150 units (expires 2026-Nov)   → Allocate 50
Total: 3 batches, 200 units allocated ✓
```

---

### 3. EDGE CASE ALERTS

#### Case 1: Insufficient Stock

```
Need: 500 units
Total Available: 350 units
Alert: ⚠ 150 units short - Cannot fulfill
Action: Add another batch or reduce qty
```

#### Case 2: Batch Expiring Soon

```
Allocated from: Batch123 (expires 2026-May, 25 days)
Alert: ⚠ This batch expiring soon
Action: Consider using later expiring batch if available
```

#### Case 3: Multiple Batches Needed

```
Need: 200 units
Batches Selected:
  [✓] Batch1: 50 units
  [✓] Batch2: 100 units
  [✓] Batch3: 50 units
Alert: ✓ 3 batches allocated, ready to invoice
```

---

## 🔄 How It Works Behind the Scenes

### Batch Allocation Algorithm (FIFO)

When you enter a quantity, the system:

1. **Fetches all available batches** for the product

   ```sql
   SELECT * FROM product_batches
   WHERE product_id = ?
     AND available_quantity > 0
     AND status = 'active'
     AND expiry_date > TODAY
   ORDER BY expiry_date ASC  ← Oldest expiry first
   ```

2. **Sorts by expiry date** (First Expiring First)
   - Batch A: Expires 2026-May (allocate first)
   - Batch B: Expires 2026-Aug (allocate second)
   - Batch C: Expires 2026-Nov (allocate last)

3. **Allocates quantity** FIFO until fulfilled

   ```
   Required: 200 units

   Batch A (50 available): Allocate 50 → Needed: 150
   Batch B (100 available): Allocate 100 → Needed: 50
   Batch C (150 available): Allocate 50 → Needed: 0

   Result: ✓ Fully allocated from 3 batches
   ```

4. **Generates warnings**
   - Insufficient stock: if needed > total available
   - Expiry warning: if batch expiring < 30 days

---

## 📋 Sample Data Provided

### 8 Medicines with 32 Batches

| Product           | Batches | Min Qty | Max Qty | Total Stock |
| ----------------- | ------- | ------- | ------- | ----------- |
| Paracetamol 650mg | 4       | 50      | 250     | 550         |
| Ibuprofen         | 4       | 50      | 250     | 550         |
| Amoxicillin 500mg | 4       | 50      | 250     | 550         |
| Azithromycin      | 4       | 50      | 250     | 550         |
| Metformin         | 4       | 50      | 250     | 550         |
| Aspirin 500mg     | 4       | 50      | 250     | 550         |
| Cetirizine        | 4       | 50      | 250     | 550         |
| Omeprazole        | 4       | 50      | 250     | 550         |

**Total**: 32 batches, 4,400 units across 8 products

**Quantities vary per batch** to test edge cases:

- 50 unit batches → forces multi-batch allocation
- 100, 150, 250 unit batches → normal & surplus cases
- Expiry dates: 3mo, 6mo, 9mo, 12mo from today

---

## 🧪 Test Scenarios Ready to Use

### Test 1: Simple Single-Batch Sale

```
Product: Paracetamol 650mg
Qty: 75 units (< single batch of 100 available)
Expected: Uses Batch1 (75/100), leaves 25 remaining
Status: ✓ Allocate from 1 batch
```

### Test 2: Required Multi-Batch Allocation

```
Product: Ibuprofen
Qty: 200 units (> any single batch)
Batches: 50 + 100 + 150 + 250 available
Expected: Allocate 50 + 100 + 50 from earliest expiring = 200
Status: ✓ Allocate from 3 batches
```

### Test 3: Insufficient Total Stock

```
Product: Amoxicillin 500mg
Qty: 800 units (total available = 550)
Expected: Alert "⚠ 250 units short"
Action: Cannot create invoice, adjust qty down
Status: ❌ Insufficient stock
```

### Test 4: Expiry Warning Stock Available

```
Product: Azithromycin
Qty: 50 units (available in all batches)
Batch Selected: Batch1 (expires in 20 days)
Expected: Alert "⚠ Batch expiring soon"
Action: User can proceed or select different batch
Status: ⚠ Available but flagged for alert
```

---

## 🔧 API Reference

### GET Batch Allocation

**Endpoint**: `getBatchAllocationPlan.php`
**Method**: POST
**Parameters**:

```json
{
  "product_id": 1,
  "quantity": 200
}
```

**Response Success**:

```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "required_quantity": 200,
    "allocation_plan": [
      {
        "batch_id": 10,
        "batch_number": "PAR-202602-45123",
        "allocated_quantity": 100,
        "available_quantity": 100,
        "expiry_date": "2026-05-15",
        "days_to_expiry": 81,
        "mrp": 50,
        "purchase_rate": 30
      }
    ],
    "summary": {
      "required_quantity": 200,
      "total_allocated": 200,
      "batch_count": 2,
      "is_complete": true
    }
  },
  "warnings": []
}
```

**Response Insufficient**:

```json
{
  "success": true,
  "data": {
    "required_quantity": 800,
    "total_allocated": 550,
    "is_complete": false
  },
  "canFulfill": false,
  "warnings": [
    {
      "type": "INSUFFICIENT_STOCK",
      "message": "Need 250 more units"
    }
  ]
}
```

---

## 📊 Database Schema

### product_batches Table

```sql
batch_id              INT PRIMARY KEY
product_id            INT (FK to product)
supplier_id           INT (FK to suppliers)
batch_number          VARCHAR (unique identifier)
manufacturing_date    DATE
expiry_date           DATE
available_quantity    INT (current stock)
reserved_quantity     INT (for POs)
damaged_quantity      INT (defects/returns)
purchase_rate         DECIMAL (cost per unit)
mrp                   DECIMAL (selling price)
status                VARCHAR (active/inactive)
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

### stock_movements Table (Audit Trail)

```sql
movement_id           INT PRIMARY KEY
batch_id              INT (FK to product_batches)
movement_type         VARCHAR (Purchase/Sales/Adjustment/Return)
quantity              INT (units moved)
reference_type        VARCHAR (PO/Invoice/Manual)
reference_id          INT (PO ID or Invoice ID)
notes                 VARCHAR (optional)
created_at            TIMESTAMP
```

---

## 🎯 Key Files

| File                        | Purpose                                | Status     |
| --------------------------- | -------------------------------------- | ---------- |
| sales_invoice_enhanced.php  | Sales form with autofill & multi-batch | ✅ Ready   |
| BatchQuantityHandler.php    | Allocation logic                       | ✅ Ready   |
| getBatchAllocationPlan.php  | AJAX endpoint                          | ✅ Ready   |
| purchase_invoice_action.php | Create PO with batch tracking          | ✅ Updated |
| fetchProductInvoice.php     | Get batch list for sales               | ✅ Updated |
| seed_medicine_data.php      | Sample data (32 batches)               | ✅ Ready   |

---

## ⚠️ Important Notes

1. **Autofill is automatic**
   - No need to manually enter MRP or GST%
   - Just select product → everything auto-fills

2. **Multi-batch is transparent**
   - System handles it automatically
   - Shows user which batches will be used

3. **FIFO is enforced**
   - Oldest expiring batches allocated first
   - Prevents waste of soon-to-expire stock

4. **All changes logged**
   - Every in/out tracked in stock_movements
   - Complete audit trail available

5. **No data loss**
   - All old data backed up before consolidation
   - Can restore if needed

---

## ✨ Workflow Example

### Creating a Sales Invoice

1. **Click 'New Sales Invoice'** → Opens enhanced form
2. **Select Customer** → Auto-loads customer details
3. **Add Product** → Type or select from dropdown
   - MRP auto-fills ✓
   - GST% auto-fills ✓
4. **Enter Quantity** → System checks batch availability
   - Shows "Checking available batches..."
   - Displays allocation suggestion
   - Shows "✓ 3 batches allocated" or "⚠ 150 units short"
5. **Review Bill** → Grand total calculated in real-time
6. **Submit** → Invoice created with batch allocations
   - Batches decremented by allocated qty
   - Movements logged to audit trail
   - Invoice saved in Draft status

---

## 🆘 If Something Doesn't Work

### Products not showing in dropdown

→ Check product table has entries
→ Run: `seed_medicine_data.php`

### Allocation shows 0 available

→ Check product_batches table for product
→ Verify batch status = 'active'
→ Check available_quantity > 0

### Old form still showing (not enhanced version)

→ Clear browser cache
→ Ctrl+F5 hard refresh
→ Use: `sales_invoice_enhanced.php` directly

### Wrong batch selected

→ Check BatchQuantityHandler allocation logic
→ Verify expiry_date sorting (oldest first)
→ Run test scenario #1 to debug

---

## 📞 Support

All issues resolved through these files:

- **Database**: Restore from backup
- **Logic**: Check BatchQuantityHandler.php
- **UI**: Check sales_invoice_enhanced.php
- **API**: Check getBatchAllocationPlan.php

Complete documentation: [MEDICINE_MODULE_CONSOLIDATION_COMPLETE.md](MEDICINE_MODULE_CONSOLIDATION_COMPLETE.md)
