# Purchase Order Creation - Testing & Debug Guide

## ✅ What Was Fixed

### 1. **CRITICAL BUG: Invalid MySQL Type Character**

- **File:** `php_action/createPurchaseOrder.php`
- **Issue:** Type string `'iddrddddd'` contained invalid character `'r'` (not a valid MySQL type)
- **Fix:** Changed to `'idddddddd'` (all doubles are `'d'`)
- **Impact:** This was causing "Type definition size does not match parameter count" errors

### 2. **Added Comprehensive Debugging**

- Request validation logging
- Parameter type verification
- Item-by-item processing logs
- Database operation confirmation
- Transaction status tracking
- Detailed error messages

### 3. **Fixed Type Consistency**

- PO Master: 33 parameters with correct types
- Items: 19 parameters per item with correct types
- All numeric fields verified as double (`d`)
- All string fields verified as string (`s`)
- All IDs verified as integer (`i`)

---

## 🧪 How to Test

### Step 1: Open Create PO Form

- Navigate to: `create_po.php`
- Fill in the form with test data

### Step 2: Fill Form Fields

```
PO Date:               Today's date
PO Type:              Regular
Supplier:             Select any supplier
Expected Delivery:    Any future date
Reference Number:     Leave blank or enter value
Reference Date:       Leave blank or enter value

At least ONE medicine:
  - Search and select medicine
  - Qty: 10
  - Unit Price: 100
  - Discount %: 0
  - Tax %: 18
```

### Step 3: Submit and Monitor

1. Click "Create PO"
2. Open Browser Console (F12 → Console tab)
3. Watch the Network tab for API response

### Step 4: Review Debug Output

In the browser console, you'll see either:

**Success Response:**

```javascript
{
  "success": true,
  "message": "Purchase Order created successfully",
  "po_id": 123,
  "po_number": "PO-202601-0001",
  "items_count": 1,
  "debug": [
    "Starting PO creation process...",
    "✓ PO Number: PO-202601-0001",
    "✓ Transaction started",
    "✓ PO Master inserted successfully",
    "✓ All 1 items inserted successfully",
    "✓ Supplier stats updated",
    "=== PO CREATION SUCCESSFUL ==="
  ]
}
```

**Error Response Example:**

```javascript
{
  "success": false,
  "message": "Missing required field: supplier_id",
  "debug": [
    "Starting PO creation process...",
    "!!! ERROR OCCURRED !!!",
    "Error Message: Missing required field: supplier_id"
  ]
}
```

---

## 🔍 Debug Output Interpretation

### Success Indicators (✓)

- `✓ Transaction started` → Database ready
- `✓ Statement prepared successfully` → SQL syntax correct
- `✓ Parameters bound successfully` → Type matching correct
- `✓ PO Master inserted successfully` → Main record created
- `✓ All X items inserted successfully` → Line items created
- `✓ Supplier stats updated` → Totals calculated
- `✓ Transaction committed successfully` → Data saved

### Error Indicators (!!!)

- `!!! ERROR OCCURRED !!!` → Something went wrong
- Check the "Error Message" field for details
- Look for which step failed in debug array

---

## 🛠️ Common Issues & Solutions

### Issue: "Type definition size does not match parameter count"

**Status:** ✅ FIXED

- **Was:** Invalid type character `'r'` in binding
- **Now:** Corrected to valid type characters

### Issue: "Parameter count mismatch"

**Solution:** Check debug output for:

- Parameter count in type string
- Number of bind_param() arguments
- All must be equal

### Issue: "Field not found" errors

**Check:**

- Database table schema matches field names
- All fields in INSERT statement exist in table
- Type definitions match field types (INT, VARCHAR, DECIMAL, etc.)

### Issue: "Empty debug array"

**Check:**

- JavaScript console is open (F12)
- Network tab shows the request/response
- Response Content-Type is "application/json"

---

## 📊 Parameter Reference

### Form → JavaScript → API Data Flow

**Form Field** → **JS Variable** → **API Key** → **PHP Type**

- PO Date → poDate → po_date → string
- Supplier → supplierId → supplier_id → integer
- Qty → quantity → quantity → integer
- Unit Price → unitPrice → unit_price → double
- etc.

### Bind Parameter Order

**PO Master (33 total):**

1. po_number (s)
2. po_date (s)
3. po_type (s)
4. supplier_id (i)
   5-12. supplier details (s)
   13-15. reference fields (s)
   16-27. numeric totals (d)
   28-31. statuses (s)
5. terms (s)
6. user_id (i)

**Item (19 total per item):**

1. po_id (i)
2. po_number (s)
3. medicine_id (i)
   4-11. medicine details (s)
4. qty (i)
   13-19. pricing (d)

---

## 🔧 Manual Testing Checklist

- [ ] **Test 1:** Create PO with 1 medicine
  - Expected: Success, 1 item logged
- [ ] **Test 2:** Create PO with 3 medicines
  - Expected: Success, 3 items logged in debug
- [ ] **Test 3:** Submit without supplier
  - Expected: Error - "Missing required field: supplier_id"
- [ ] **Test 4:** Submit with invalid JSON
  - Expected: Error - "No data received"
- [ ] **Test 5:** Check database directly
  - Query: `SELECT * FROM purchase_order WHERE po_number = 'PO-XXX-0001'`
  - Verify all fields populated
- [ ] **Test 6:** Verify supplier totals updated
  - Query: `SELECT * FROM suppliers WHERE supplier_id = X`
  - Check: total_orders increased, total_amount_ordered updated

---

## 📝 Database Verification

After successful PO creation, verify:

```sql
-- Check PO Master
SELECT po_id, po_number, po_status, created_by, created_at
FROM purchase_order
WHERE po_number = 'PO-202601-0001';

-- Check PO Items
SELECT po_id, medicine_id, quantity_ordered, unit_price, item_total
FROM purchase_order_items
WHERE po_id = 123;

-- Check Supplier Stats
SELECT supplier_id, total_orders, total_amount_ordered
FROM suppliers
WHERE supplier_id = 5;
```

---

## 📋 Fields Being Validated

**Required:**

- po_number
- supplier_id
- items (array with at least 1 item)

**Optional (have defaults):**

- po_date (defaults to today)
- po_type (defaults to 'Regular')
- expected_delivery_date
- reference_number
- reference_date
- po_status (defaults to 'Draft')
- payment_status (defaults to 'Pending')
- payment_method (defaults to 'Online Transfer')
- notes (defaults to '')
- terms_conditions (defaults to '')

---

## 🎯 Success Criteria

✅ PO created successfully when:

1. Debug shows "✓ Transaction started"
2. Debug shows "✓ PO Master inserted successfully"
3. Debug shows "✓ All X items inserted successfully"
4. PO appears in database
5. Supplier stats updated
6. Response includes po_id and po_number

---

**Last Updated:** January 28, 2026  
**Status:** Ready for Testing  
**PHP Syntax Check:** ✅ PASSED
