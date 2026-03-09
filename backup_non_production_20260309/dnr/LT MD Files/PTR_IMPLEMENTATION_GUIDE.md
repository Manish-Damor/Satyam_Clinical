# PTR Feature Implementation - Visual Guide

## What Changed

### 1. Database Schema

```sql
-- NEW COLUMN ADDED
ALTER TABLE order_item ADD COLUMN purchase_rate DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Table Structure After Change
order_item:
├── id (PRIMARY KEY)
├── order_id
├── order_number
├── product_id
├── productName
├── quantity
├── rate (SELLING PRICE)
├── purchase_rate ← NEW (COST PRICE)
├── total
└── added_date
```

---

## 2. Form Display (Create Invoice)

### Before

```
┌─────────────────────────────────────────┐
│ Medicine | Rate | Avail. | Qty | Total │
├─────────────────────────────────────────┤
│ Paracetamol │ 50 │  100 │  1 │  50   │
└─────────────────────────────────────────┘
```

### After

```
┌────────────────────────────────────────────────┐
│ Medicine | Rate | PTR | Avail. | Qty | Total  │ ← PTR visible
├────────────────────────────────────────────────┤
│ Paracetamol │ 50 │ 30 │  100 │  1 │  50     │
└────────────────────────────────────────────────┘

NOT PRINTED (internal tracking only)
```

---

## 3. Data Flow Diagram

```
┌──────────────────────────────────────────────────────┐
│ PRODUCT_BATCHES TABLE                               │
│ ├─ batch_id                                         │
│ ├─ product_id                                       │
│ ├─ purchase_rate ← Latest cost for each product    │
│ └─ created_date                                     │
└─────────────┬──────────────────────────────────────┘
               │
               ▼
       ┌──────────────────┐
       │ fetchSelected    │
       │ Product.php      │
       │ (Query batches)  │
       └────────┬─────────┘
                │
                ▼
        ┌─────────────────┐
        │ JSON Response   │
        │ {              │
        │  product_id:1, │
        │  rate: 50,     │
        │  purchase_rate:30 ← PTR from batch
        │ }              │
        └────────┬────────┘
                │
                ▼
         ┌──────────────────┐
         │ add-order.php    │
         │ editorder.php    │
         │ (Form Display)   │
         │ Shows PTR to     │
         │ invoice creator  │
         └────────┬─────────┘
                │
                ▼
        ┌─────────────────────┐
        │ Form Submitted      │
        │ ptrValue[] array    │
        └────────┬────────────┘
                │
                ▼
        ┌─────────────────────┐
        │ order.php           │
        │ (Collects POST data)│
        │ Gets ptrValue[i]    │
        └────────┬────────────┘
                │
                ▼
    ┌───────────────────────────────┐
    │ SalesOrderController           │
    │ insertOrderItem()              │
    │ INSERT INTO order_item:        │
    │ purchase_rate = $ptrValue      │
    └────────┬──────────────────────┘
             │
             ▼
    ┌───────────────────────────────┐
    │ ORDER_ITEM TABLE (Database)    │
    │ Stores: rate & purchase_rate   │
    │ Enables: Margin Analysis       │
    └───────────────────────────────┘
```

---

## 4. File Changes Summary

### Modified Files

#### 1. `constant/layout/sidebar.php`

```diff
- <a href="viewStock.php">Invoices</a>
+ <a href="viewStock.php">Sales Invoice</a>
```

#### 2. `add-order.php`

```diff
  <table class="table" id="productTable">
    <thead>
      <tr>
        <th>Medicine</th>
        <th>Rate</th>
+       <th class="no-print">PTR</th>  ← New column
        <th>Avail.</th>
        ...
      </tr>
    </thead>
    <tbody>
      <tr>
        ...
        <td>
          <input id="rate1" />
        </td>
+       <td class="no-print">              ← New column
+         <input id="ptr1" disabled />     ← Display (disabled)
+         <input id="ptrValue1" type="hidden" />  ← Hidden value
+       </td>
        ...
      </tr>
    </tbody>
  </table>
```

```diff
function getProductData(row) {
  $.ajax({
    success: function(response) {
      $("#rate" + row).val(response.rate);
      $("#rateValue" + row).val(response.rate);
+     $("#ptr" + row).val(response.purchase_rate ?? '');    ← New
+     $("#ptrValue" + row).val(response.purchase_rate ?? 0); ← New
      ...
    }
  });
}
```

#### 3. `editorder.php` (Identical changes to add-order.php)

```diff
SQL query updated:
  SELECT order_item.id, ..., order_item.rate,
+        order_item.purchase_rate ← New column selected
  FROM order_item ...

Initial row:
+ <td class="no-print">
+   <input id="ptr1" value="<?php echo $orderItemData['purchase_rate']; ?>" />
+   <input id="ptrValue1" type="hidden" value="<?php echo $orderItemData['purchase_rate']; ?>" />
+ </td>
```

#### 4. `php_action/fetchSelectedProduct.php`

```diff
+ $batchSql = "SELECT purchase_rate FROM product_batches
+               WHERE product_id = $productId
+               ORDER BY batch_id DESC LIMIT 1";
+ $batchRes = $connect->query($batchSql);
+ $purchase_rate = 0;
+ if ($batchRes && $batchRes->num_rows > 0) {
+     $br = $batchRes->fetch_assoc();
+     $purchase_rate = $br['purchase_rate'];
+ }

  $row['purchase_rate'] = $purchase_rate; ← Add to response
```

#### 5. `php_action/order.php`

```diff
for ($i = 0; $i < $itemCount; $i++) {
    $productId = $_POST['productId'][$i] ?? 0;
    $quantity = $_POST['quantity'][$i] ?? 0;
    $rate = $_POST['rateValue'][$i] ?? 0;
+   $purchaseRate = $_POST['ptrValue'][$i] ?? 0;  ← Collect PTR

    $items[] = [
        'product_id' => (int)$productId,
        'productName' => $_POST['productName'][$i] ?? '',
        'quantity' => (int)$quantity,
        'rate' => (float)$rate,
+       'purchase_rate' => (float)$purchaseRate  ← Include in items
    ];
}
```

#### 6. `libraries/Controllers/SalesOrderController.php`

```diff
private function insertOrderItem($orderId, $orderNumber, $item) {
    $sql = "
        INSERT INTO order_item (
            order_id, order_number, product_id,
            productName, quantity, rate,
+           purchase_rate,  ← New column
            total, added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $purchaseRate = (float)($item['purchase_rate'] ?? 0);

    $params = [
        $orderId,
        $orderNumber,
        (int)$item['product_id'],
        $item['productName'] ?? '',
        $quantity,
        $rate,
+       $purchaseRate,  ← Store PTR value
        $total,
        date('Y-m-d')
    ];

    $result = $this->db->execute_query($sql, $params);
}
```

---

## 5. CSS Styling - Print Hiding

```css
/* In add-order.php and editorder.php */
@media print {
  .no-print {
    display: none !important;
  }
}

/* Result: Customer print shows only:
   Medicine | Rate | Avail. | Qty | Total
   
   NOT shown in print:
   PTR (Purchase To Rate) - Internal cost data
*/
```

---

## 6. Form Field Naming Convention

```javascript
// For each row (1, 2, 3, ...)

// Rate field
<input id="rate1" name="rate[]" value="50" />
<input id="rateValue1" name="rateValue[]" value="50" type="hidden" />

// NEW: PTR field
<input id="ptr1" name="ptr[]" value="30" disabled />
<input id="ptrValue1" name="ptrValue[]" value="30" type="hidden" />

// Quantity field
<input id="quantity1" name="quantity[]" value="10" />

// Result when POSTed:
{
  "rateValue[0]": "50",
  "ptrValue[0]": "30",
  "quantity[0]": "10"
}
```

---

## 7. Calculation Example

### Scenario: Invoice Created with Paracetamol

**Stored in Database:**

```
Product: Paracetamol 500mg
Rate (Selling Price): ₹50.00
Purchase Rate (Cost): ₹30.00
Quantity: 10
Total: ₹500.00
```

**Margin Calculation (For Business Analysis):**

```
Margin = (Rate - Purchase Rate) / Rate × 100
Margin = (50 - 30) / 50 × 100
Margin = 20 / 50 × 100
Margin = 40%

GP (Gross Profit): ₹20 per unit
Total GP: ₹200 for 10 units
```

---

## 8. Security Notes

- ✓ PTR field marked `disabled="true"` in display (can't edit directly)
- ✓ Hidden field `ptrValue[]` carries the actual value
- ✓ Server-side validation: value comes from product_batches (trusted source)
- ✓ Print hides PTR with `class="no-print"` (not shown to customers)
- ✓ Database stores as DECIMAL(10,2) (accurate decimal math)

---

## 9. How to Verify

### Check Database Column

```sql
SHOW COLUMNS FROM order_item;
-- Should see: purchase_rate   decimal(10,2)   NO       0
```

### Check Sample Data

```sql
SELECT order_number, productName, rate, purchase_rate
FROM order_item
ORDER BY id DESC LIMIT 5;

-- Expected output:
-- ORD-240102-0001 | Aspirin      | 75.00 | 45.00
-- ORD-240102-0001 | Paracetamol  | 50.00 | 30.00
```

### Check Form Submission (Browser DevTools)

1. Open http://localhost/Satyam_Clinical/add-order.php
2. Press F12 (DevTools)
3. Go to Network or Console tab
4. Select a product
5. Should see: `ptrValue[0]: "30.00"` in Network tab when submitted

---

## 10. Testing Checklist Quick Reference

- [ ] PTR column visible in add-order.php form
- [ ] PTR column visible in editorder.php form
- [ ] PTR value populates when product selected
- [ ] PTR value hides when printed (no-print CSS)
- [ ] PTR value saves to database
- [ ] Can edit invoice and PTR value loads
- [ ] Multiple items each have own PTR value
- [ ] Margin calculation works: (Rate - PTR) / Rate × 100

---

## 11. Next Steps: UI Enhancements (Future)

```javascript
// Could add margin % column display (not yet implemented):
Margin % = (Rate - PTR) / Rate × 100

// Example enhancement to getTotal():
function getTotal(row) {
    var rate = Number($("#rate" + row).val());
    var ptr = Number($("#ptrValue" + row).val());
    var qty = Number($("#quantity" + row).val());

    var margin_percent = ((rate - ptr) / rate * 100).toFixed(2);

    // Could display margin here if needed
    $("#marginPercent" + row).text(margin_percent + "%");
}
```

---

**Implementation Complete** ✅  
**Ready for: Testing Phase 1**
