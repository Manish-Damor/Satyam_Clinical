# REFACTORING IMPLEMENTATION PLAN

**Phase 2: Corrections & Fixes**  
**Status:** Ready to Execute

---

## OVERVIEW

This document details the **6 critical fixes** to be implemented to resolve audit findings. Each fix includes:

- Issue description
- Root cause
- Impact
- Solution approach
- Code changes needed
- Testing criteria

---

## FIX #1: Remove Batch/Expiry Fields from Purchase Order

### Issue

PO form incorrectly shows batch number and expiry date as readonly fields, misleading users that PO captures batch details (it shouldn't).

### Root Cause

Frontend template was copied from stock entry form without removing batch-specific fields.

### Impact

- User confusion about PO's purpose
- Violates requirement: "No batch in PO"
- Readonly fields display junk data from product master

### Solution

**File:** `create_po.php`

**Change:** Remove columns from items table and hide them from display

### Current Code (Lines 280-310)

```html
<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th>Medicine Name</th>
      <th>HSN Code</th>
      <th>Pack Size</th>
      <th style="width:8%;">Batch No.</th>
      <!-- ❌ REMOVE -->
      <th style="width:8%;">Expiry</th>
      <!-- ❌ REMOVE -->
      <th>MRP</th>
      <th>PTR</th>
      <th>Rate</th>
      <th>Qty</th>
      <th>Disc %</th>
      <th>Amt</th>
      <th>Tax %</th>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <tr class="item-row">
      <td><!-- Medicine search --></td>
      <td><input ... class="hsn-code" readonly /></td>
      <td><input ... class="pack-size" readonly /></td>
      <td><input ... class="batch-number" readonly /></td>
      <!-- ❌ REMOVE -->
      <td><input type="date" class="expiry-date" readonly /></td>
      <!-- ❌ REMOVE -->
      <!-- ... rest -->
    </tr>
  </tbody>
</table>
```

### Corrected Code

```html
<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th>Medicine Name</th>
      <th>HSN Code</th>
      <th>Pack Size</th>
      <!-- Batch and Expiry columns REMOVED -->
      <th>MRP</th>
      <th>PTR</th>
      <th>Rate</th>
      <th>Qty</th>
      <th>Disc %</th>
      <th>Amt</th>
      <th>Tax %</th>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <tr class="item-row">
      <td><!-- Medicine search --></td>
      <td><input ... class="hsn-code" readonly /></td>
      <td><input ... class="pack-size" readonly /></td>
      <!-- Batch and Expiry inputs REMOVED -->
      <td><input ... class="mrp-value" readonly /></td>
      <!-- ... rest -->
    </tr>
  </tbody>
</table>
```

### Also Update

**Edit JavaScript row building in addRow() function** (around line 761):

```javascript
// OLD: Includes batch and expiry inputs
var tr = '<tr id="row'+count+'" class="'+arrayNumber+'">' +
    '<td><!-- medicine search --></td>' +
    '<td><input ... class="hsn-code" /></td>' +
    '<td><input ... class="batch-number" /></td>' +    // ❌ REMOVE ENTIRE LINE
    '<td><input type="date" class="expiry-date" /></td>' + // ❌ REMOVE ENTIRE LINE
    '<td><input ... class="mrp-value" /></td>' +
    // ... rest

// NEW: Removes those lines
var tr = '<tr id="row'+count+'" class="'+arrayNumber+'">' +
    '<td><!-- medicine search --></td>' +
    '<td><input ... class="hsn-code" /></td>' +
    '<td><input ... class="mrp-value" /></td>' +
    // ... rest
```

### Files to Modify

1. `create_po.php` - Remove table columns and field definitions (6 locations)
2. Backend: `php_action/createPurchaseOrder.php` - If it processes batch_number, remove it
3. Backend: `php_action/updatePurchaseOrder.php` - If it processes batch_number, remove it

### Validation

- ✅ HTML table has exactly 13 columns (not 15)
- ✅ No batch input fields in form
- ✅ No expiry date input fields in form
- ✅ PO can be created without error
- ✅ PHP backend still works (doesn't fail on missing POST values)

---

## FIX #2: Add Batch Selection to Sales Invoice

### Issue

Sales invoice (add-order.php) has no batch selector. Products show only: Medicine, Rate, PTR, Avail., Quantity. Cannot track which batch was sold.

### Root Cause

Original product table in add-order.php was designed for simple quantity deduction, not batch-aware inventory management.

### Impact

- **Pharmacy Compliance Risk:** Cannot perform batch recalls
- **Inventory Risk:** No batch-level tracking
- **Expiry Risk:** Cannot prevent sales of specific expired batches

### Solution

Add a **Batch Selector Column** between Quantity and Total columns in the sales invoice table.

### Current Code (add-order.php, lines 130-160)

```html
<table class="table" id="productTable">
  <thead>
    <tr>
      <th>Medicine</th>
      <th>Rate</th>
      <th class="no-print">PTR</th>
      <th>Avail.</th>
      <th>Quantity</th>
      <!-- Batch selection should come here -->
      <th>Total</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <tr id="row1">
      <td><!-- medicine search --></td>
      <td><input id="rate1" /></td>
      <td><input id="ptr1" /></td>
      <td><!-- quantity display --></td>
      <td><input id="quantity1" /></td>
      <td><input id="total1" /></td>
      <td><!-- action buttons --></td>
    </tr>
  </tbody>
</table>
```

### Corrected Code

```html
<table class="table" id="productTable">
  <thead>
    <tr>
      <th style="width:35%;">Medicine</th>
      <th style="width:10%;">Rate</th>
      <th style="width:10%;" class="no-print">PTR</th>
      <th style="width:8%;">Avail.</th>
      <th style="width:12%;">Batch</th>
      <!-- ✅ NEW COLUMN -->
      <th style="width:12%;">Qty</th>
      <th style="width:13%;">Total</th>
      <th style="width:8%;">Action</th>
    </tr>
  </thead>
  <tbody>
    <tr id="row1">
      <td style="margin-left:20px;">
        <div class="form-group" style="position: relative;">
          <input
            type="text"
            class="form-control invoice-product-input"
            name="productName[]"
            id="productName1"
            placeholder="Type to search medicines..."
            autocomplete="off"
            data-row-id="1"
          />
          <input
            type="hidden"
            class="invoice-product-id"
            name="productId[]"
            id="productId1"
          />
          <div
            class="invoice-product-dropdown"
            id="dropdown1"
            style="position: absolute; ..."
          ></div>
        </div>
      </td>
      <td>
        <input
          type="text"
          name="rate[]"
          id="rate1"
          disabled="true"
          class="form-control"
        />
        <input type="hidden" name="rateValue[]" id="rateValue1" />
      </td>
      <td class="no-print">
        <input
          type="text"
          name="ptr[]"
          id="ptr1"
          disabled="true"
          class="form-control"
        />
        <input type="hidden" name="ptrValue[]" id="ptrValue1" />
      </td>
      <td>
        <p style="padding-left:4%;" id="available_quantity1"></p>
      </td>
      <!-- ✅ NEW: Batch Selector -->
      <td>
        <select name="batchId[]" id="batchId1" class="form-control">
          <option value="">-- Select Batch --</option>
        </select>
        <small class="text-muted" id="batchExpiry1"></small>
      </td>
      <!-- END NEW -->
      <td>
        <input
          type="number"
          name="quantity[]"
          id="quantity1"
          onkeyup="getTotal(1)"
          class="form-control"
          min="1"
        />
      </td>
      <td>
        <input
          type="text"
          name="total[]"
          id="total1"
          disabled="true"
          class="form-control"
        />
        <input type="hidden" name="totalValue[]" id="totalValue1" />
      </td>
      <td>
        <button
          type="button"
          class="btn btn-danger removeProductRowBtn"
          onclick="removeProductRow(1)"
        >
          <i class="fa fa-trash"></i>
        </button>
      </td>
    </tr>
  </tbody>
</table>
```

### JavaScript Changes - Update getProductData()

**Old Code (lines 875-915):**

```javascript
function getProductData(row = null) {
  if (row) {
    var productId = $("#productId" + row).val();
    if (productId == "") {
      $("#rate" + row).val("");
      $("#quantity" + row).val("");
      $("#total" + row).val("");
    } else {
      $.ajax({
        url: "php_action/fetchSelectedProduct.php",
        type: "post",
        data: { productId: productId },
        dataType: "json",
        success: function (response) {
          $("#rate" + row).val(response.rate);
          $("#rateValue" + row).val(response.rate);
          $("#ptr" + row).val(response.purchase_rate ?? "");
          $("#ptrValue" + row).val(response.purchase_rate ?? 0);
          $("#quantity" + row).val(1);
          $("#available_quantity" + row).text(response.quantity);

          var total = Number(response.rate) * 1;
          total = total.toFixed(2);
          $("#total" + row).val(total);
          $("#totalValue" + row).val(total);

          subAmount();
        },
      });
    }
  }
}
```

**New Code:**

```javascript
function getProductData(row = null) {
  if (row) {
    var productId = $("#productId" + row).val();
    if (productId == "") {
      $("#rate" + row).val("");
      $("#quantity" + row).val("");
      $("#total" + row).val("");
      $("#batchId" + row).html('<option value="">-- Select Batch --</option>'); // ✅ CLEAR BATCH
    } else {
      $.ajax({
        url: "php_action/fetchSelectedProduct.php",
        type: "post",
        data: { productId: productId },
        dataType: "json",
        success: function (response) {
          $("#rate" + row).val(response.rate);
          $("#rateValue" + row).val(response.rate);
          $("#ptr" + row).val(response.purchase_rate ?? "");
          $("#ptrValue" + row).val(response.purchase_rate ?? 0);
          $("#quantity" + row).val(1);
          $("#available_quantity" + row).text(response.quantity);

          // ✅ NEW: Populate batch dropdown
          if (response.batches && response.batches.length > 0) {
            var batchOptions = '<option value="">-- Select Batch --</option>';
            response.batches.forEach(function (batch) {
              var expiryClass = isExpired(batch.expiry_date)
                ? "text-danger"
                : "text-success";
              var expiryLabel = isExpired(batch.expiry_date)
                ? " (EXPIRED)"
                : "";
              batchOptions +=
                '<option value="' +
                batch.batch_id +
                '" data-expiry="' +
                batch.expiry_date +
                '">' +
                batch.batch_number +
                " - " +
                batch.available_quantity +
                " avail." +
                expiryLabel +
                "</option>";
            });
            $("#batchId" + row).html(batchOptions);
          } else {
            $("#batchId" + row).html(
              '<option value="">-- No Batches --</option>',
            );
          }

          var total = Number(response.rate) * 1;
          total = total.toFixed(2);
          $("#total" + row).val(total);
          $("#totalValue" + row).val(total);

          subAmount();
        },
      });
    }
  }
}

// ✅ NEW: Check if batch is expired
function isExpired(expiryDate) {
  var exp = new Date(expiryDate);
  return exp < new Date();
}

// ✅ NEW: Update expiry display when batch changes
$(document).on("change", '[id^="batchId"]', function () {
  var row = $(this).attr("id").replace("batchId", "");
  var expiryDate = $(this).find("option:selected").data("expiry");
  if (expiryDate) {
    var isExp = isExpired(expiryDate);
    if (isExp) {
      $("#batchExpiry" + row).html(
        '<strong class="text-danger">⚠️ EXPIRED</strong>',
      );
    } else {
      var days = Math.floor(
        (new Date(expiryDate) - new Date()) / (1000 * 60 * 60 * 24),
      );
      if (days < 90) {
        $("#batchExpiry" + row).html(
          '<small class="text-warning">⚠️ Expires in ' + days + " days</small>",
        );
      } else {
        $("#batchExpiry" + row).html(
          '<small class="text-success">✓ Valid</small>',
        );
      }
    }
  }
});
```

### Update addRow() Function (around line 761)

**Add batch selector to new row HTML:**

```javascript
var tr =
  '<tr id="row' +
  count +
  '" class="' +
  arrayNumber +
  '">' +
  // ... existing columns ...
  "<td>" +
  '<select name="batchId[]" id="batchId' +
  count +
  '" class="form-control">' +
  '<option value="">-- Select Batch --</option>' +
  "</select>" +
  '<small class="text-muted" id="batchExpiry' +
  count +
  '"></small>' +
  "</td>" +
  // ... quantity and total columns ...
  "</tr>";
```

### Update fetchSelectedProduct.php Backend

**Current Code:**

```php
$sql = "SELECT product_id, product_name, product_image, brand_id, categories_id, quantity, rate, active, status FROM product WHERE product_id = $productId";
// ... no batch fetching
$row['purchase_rate'] = $purchase_rate;
echo json_encode($row);
```

**New Code:**

```php
$sql = "SELECT product_id, product_name, product_image, brand_id, categories_id, quantity, rate, active, status FROM product WHERE product_id = $productId";

// Fetch batches for this product  ✅ NEW
$batchSql = "SELECT batch_id, batch_number, available_quantity, expiry_date
             FROM stock_batches
             WHERE product_id = $productId
               AND status = 'Active'
               AND expiry_date >= CURDATE()  // Only non-expired
             ORDER BY expiry_date ASC";  // FIFO: nearest expiry first
$batchRes = $connect->query($batchSql);
$batches = [];
while ($batchRow = $batchRes->fetch_assoc()) {
    $batches[] = $batchRow;
}

$result = $connect->query($sql);
if($result->num_rows > 0) {
    $row = $result->fetch_array();
} else {
    $row = [];
}
$row['purchase_rate'] = $purchase_rate;
$row['batches'] = $batches;  // ✅ NEW: Include batch list

echo json_encode($row);
```

### Update order.php to Collect Batch IDs

**Current Code (lines 50-80):**

```php
$items = [];
$itemCount = count($_POST['productId'] ?? []);

for ($i = 0; $i < $itemCount; $i++) {
    $productId = $_POST['productId'][$i] ?? 0;
    $quantity = $_POST['quantity'][$i] ?? 0;
    $rate = $_POST['rateValue'][$i] ?? 0;
    $purchaseRate = $_POST['ptrValue'][$i] ?? 0;

    // ... validation ...

    $items[] = [
        'product_id' => (int)$productId,
        'productName' => $_POST['productName'][$i] ?? '',
        'quantity' => (int)$quantity,
        'rate' => (float)$rate,
        'purchase_rate' => (float)$purchaseRate
    ];
}
```

**New Code:**

```php
$items = [];
$itemCount = count($_POST['productId'] ?? []);

for ($i = 0; $i < $itemCount; $i++) {
    $productId = $_POST['productId'][$i] ?? 0;
    $quantity = $_POST['quantity'][$i] ?? 0;
    $rate = $_POST['rateValue'][$i] ?? 0;
    $purchaseRate = $_POST['ptrValue'][$i] ?? 0;
    $batchId = $_POST['batchId'][$i] ?? 0;  // ✅ NEW

    // Validate batch selected
    if (!$batchId) {
        throw new Exception("Batch selection is required for item " . ($i+1));  // ✅ NEW
    }

    // ... rest of validation ...

    $items[] = [
        'product_id' => (int)$productId,
        'productName' => $_POST['productName'][$i] ?? '',
        'quantity' => (int)$quantity,
        'rate' => (float)$rate,
        'purchase_rate' => (float)$purchaseRate,
        'batch_id' => (int)$batchId  // ✅ NEW
    ];
}
```

### Update insertOrderItem() in SalesOrderController

**Current Code (lines 347-365):**

```php
private function insertOrderItem($orderId, $orderNumber, $item) {
    $sql = "
        INSERT INTO order_item (
            order_id, order_number, product_id,
            productName, quantity, rate, purchase_rate, total,
            added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $quantity = (int)$item['quantity'];
    $rate = (float)$item['rate'];
    $purchaseRate = (float)($item['purchase_rate'] ?? 0);
    $total = $quantity * $rate;

    $params = [
        $orderId,
        $orderNumber,
        (int)$item['product_id'],
        $item['productName'] ?? '',
        $quantity,
        $rate,
        $purchaseRate,
        $total,
        date('Y-m-d')
    ];
}
```

**New Code:**

```php
private function insertOrderItem($orderId, $orderNumber, $item) {
    $sql = "
        INSERT INTO order_item (
            order_id, order_number, product_id, batch_id,
            productName, quantity, rate, purchase_rate, total,
            added_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)  // ✅ Added batch_id
    ";

    $quantity = (int)$item['quantity'];
    $rate = (float)$item['rate'];
    $purchaseRate = (float)($item['purchase_rate'] ?? 0);
    $batchId = (int)($item['batch_id'] ?? 0);  // ✅ NEW
    $total = $quantity * $rate;

    $params = [
        $orderId,
        $orderNumber,
        (int)$item['product_id'],
        $batchId,  // ✅ NEW: Include batch_id
        $item['productName'] ?? '',
        $quantity,
        $rate,
        $purchaseRate,
        $total,
        date('Y-m-d')
    ];
}
```

### Testing Criteria

- ✅ Batch dropdown appears and populates when product selected
- ✅ Only non-expired batches shown (CURRENT DATE <= Expiry)
- ✅ Expired batches either hidden OR marked red with "(EXPIRED)"
- ✅ Batch selection required - form won't submit without it
- ✅ Batch ID stored in order_item.batch_id in database
- ✅ Multiple products can have different selected batches
- ✅ Addition calculation still works correctly

---

## FIX #3: Remove Hardcoded Tax from PO and Use Per-Product GST

### Issue

Purchase Order (create_po.php) has hardcoded 9% CGST / 9% SGST / 18% IGST values that apply to all products, ignoring per-product GST rates.

### Root Cause

PO was designed before per-product GST rates were added to product master.

### Impact

- Incorrect tax calculation if products have different GST rates
- Tax doesn't match what was actually paid (according to product master)
- Violates GST compliance

### Solution

**Remove hardcoded tax percentages and instead:**

1. Fetch per-product GST rate from product master
2. Display per-item tax calculation
3. Remove global CGST/SGST/IGST dropdowns

### Current Issues in create_po.php

**Line 385-400 (JavaScript):**

```javascript
function calculateTax() {
  var tableLength = $("#itemsTable tbody tr").length;

  for (var x = 0; x < tableLength; x++) {
    var rate =
      parseFloat(
        $("#itemsTable tbody tr").eq(x).find("input.unit-price").val(),
      ) || 0;
    var qty =
      parseFloat(
        $("#itemsTable tbody tr").eq(x).find("input.quantity").val(),
      ) || 0;
    var discPct =
      parseFloat(
        $("#itemsTable tbody tr").eq(x).find("input.discount-percent").val(),
      ) || 0;
    var taxPct = 18; // ❌ HARDCODED

    // ... calculation using hardcoded rate
  }
}
```

**Also affects:** Backend totals calculation

### Recommended Approach: **Remove PO Tax Calculation Entirely**

**Rationale:**

- PO is just a commitment document
- Actual GSTis determined at Purchase Invoice stage (when goods received)
- Per INR (GST Act), tax is calculated on actual invoice, not PO

### Corrected Code

**File:** `create_po.php`

**Action:** Remove the entire "Totals Section" (lines 410-480 that shows CGST/SGST/IGST calculations)

Remove this entire card:

```html
<!-- Totals Section -->
<div class="card mt-3">
  <div class="card-header bg-warning">
    <h5 class="text-dark m-0">Calculations</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <label>CGST (9%)</label>
        <input type="number" class="form-control" id="cgstAmount" readonly />
      </div>
      <!-- ... SGST, IGST, Grand Total sections ... -->
    </div>
  </div>
</div>
```

**Replace with simplified totals:**

```html
<!-- Simple Totals Section -->
<div class="card mt-3">
  <div class="card-header bg-warning">
    <h5 class="text-dark m-0">Order Summary</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <label>Sub Total</label>
        <input
          type="number"
          class="form-control"
          id="subTotal"
          name="subtotal"
          readonly
          style="font-weight: bold;"
        />
      </div>
      <div class="col-md-3">
        <label>Discount</label>
        <input
          type="number"
          class="form-control"
          id="totalDiscount"
          name="discount"
          readonly
          style="font-weight: bold;"
        />
      </div>
      <div class="col-md-3">
        <label>Net Amount (Before Tax)</label>
        <input
          type="number"
          class="form-control"
          id="netAmount"
          name="net_amount"
          readonly
          style="font-weight: bold;"
        />
      </div>
      <div class="col-md-3">
        <label><strong>Note: GST will be calculated at Invoice</strong></label>
      </div>
    </div>
  </div>
</div>
```

**Remove JavaScript tax calculation function entirely** and replace with simpler calculation:

```javascript
function calculateTotals() {
  let subTotal = 0;
  let totalDiscount = 0;

  $("#itemsTable tbody tr").each(function () {
    let qty = parseFloat($(this).find(".quantity").val()) || 0;
    let rate = parseFloat($(this).find(".unit-price").val()) || 0;
    let discPct = parseFloat($(this).find(".discount-percent").val()) || 0;

    let lineAmount = qty * rate;
    let discountAmt = (lineAmount * discPct) / 100;

    subTotal += lineAmount;
    totalDiscount += discountAmt;
  });

  let netAmount = subTotal - totalDiscount;

  $("#subTotal").val(subTotal.toFixed(2));
  $("#totalDiscount").val(totalDiscount.toFixed(2));
  $("#netAmount").val(netAmount.toFixed(2));

  // GST will be added during Purchase Invoice, not here
}

// Trigger on input changes
$(document).on(
  "input",
  ".quantity, .unit-price, .discount-percent",
  calculateTotals,
);
```

### Also Check Backend

**File:** `php_action/createPurchaseOrder.php`

Ensure it does NOT calculate GST/CGST/SGST and just stores order information:

```php
// Should only store:
INSERT INTO purchase_orders
(po_number, po_date, supplier_id, subtotal, discount_amount, net_total, notes, ...)
VALUES (?, ?, ?, ?, ?, ?, ?, ...)

// Should NOT store: CGST, SGST, IGST (these are calculated at invoice)
```

### Testing Criteria

- ✅ PO form has only simple subtotal, discount, net amount fields
- ✅ No CGST/SGST/IGST fields in PO
- ✅ PO can be created and saved without errors
- ✅ Database PO record doesn't have GST columns (or they're NULL)
- ✅ Tax is correctly calculated later in Purchase Invoice

---

## FIX #4: Remove Global GST Dropdown from Sales Invoice & Use Per-Product GST

### Issue

Sales invoice (add-order.php) has a single GST percentage dropdown that applies to the entire invoice, overriding per-product GST rates which should be used instead.

### Root Cause

Original sales invoice was designed before product master gained per-product GST rate field.

### Impact

- Incorrect tax if products have different GST rates
- Example: 5% medicine + 18% medicine = invoice can only use one rate
- Compliance issue

### Solution

**Remove the global GST dropdown and instead:**

1. Show per-product GST rate (from product master)
2. Calculate tax per item
3. Sum total tax from all items

### Current Problematic Code (add-order.php, lines 233-251)

```html
<div class="form-group">
  <div class="row">
    <label for="gstPercentage" class="col-sm-2 control-label">GST %</label>
    <div class="col-sm-4">
      <select
        class="form-control"
        id="gstPercentage"
        name="gstPercentage"
        onchange="updateGSTLabel(); subAmount();"
      >
        <option value="0">0%</option>
        <option value="5" selected>5%</option>
        <!-- ❌ User can override -->
        <option value="12">12%</option>
        <option value="18">18%</option>
        <option value="24">24%</option>
      </select>
    </div>
  </div>
</div>
```

**JavaScript Calculation (lines 410-430):**

```javascript
function subAmount() {
  var gstPercentage = $("#gstPercentage").val(); // ❌ Single value
  var vat = (subTotal * gstPercentage) / 100; // ❌ Applied to entire total
  // ... rest
}
```

### Corrected Code

**Step 1: Remove the GST dropdown from HTML**

Delete this section entirely from add-order.php (lines 233-251):

```html
<div class="form-group">
  <div class="row">
    <label for="gstPercentage" class="col-sm-2 control-label">GST %</label>
    <!-- ENTIRE SECTION REMOVED -->
  </div>
</div>
```

**Step 2: Update add-order.php display to show GST breakdown**

Replace with informational display:

```html
<div class="form-group">
  <div class="row">
    <label class="col-sm-2 control-label">Tax Summary</label>
    <div class="col-sm-10">
      <div class="alert alert-info">
        <p>
          <strong>Note:</strong> Tax is calculated per product based on its GST
          rate from the product master.
        </p>
        <p id="gstBreakdown">Taxes will be shown when items are added.</p>
      </div>
    </div>
  </div>
</div>
```

**Step 3: Update JavaScript to use per-product GST rates**

Modify `getProductData()` function (around line 875):

```javascript
function getProductData(row = null) {
  if (row) {
    var productId = $("#productId" + row).val();
    if (productId == "") {
      $("#rate" + row).val("");
      $("#quantity" + row).val("");
      $("#total" + row).val("");
      $("#gstRate" + row).val(""); // ✅ NEW: Store product's GST rate
    } else {
      $.ajax({
        url: "php_action/fetchSelectedProduct.php",
        type: "post",
        data: { productId: productId },
        dataType: "json",
        success: function (response) {
          $("#rate" + row).val(response.rate);
          $("#rateValue" + row).val(response.rate);
          $("#ptr" + row).val(response.purchase_rate ?? "");
          $("#ptrValue" + row).val(response.purchase_rate ?? 0);

          // ✅ NEW: Store product's GST rate
          $("#gstRate" + row).val(response.gst_rate ?? 0);

          $("#quantity" + row).val(1);
          $("#available_quantity" + row).text(response.quantity);

          var total = Number(response.rate) * 1;
          total = total.toFixed(2);
          $("#total" + row).val(total);
          $("#totalValue" + row).val(total);

          subAmount(); // Recalculate with per-product tax
        },
      });
    }
  }
}
```

**Step 4: Update subAmount() to calculate tax per item**

Replace entire subAmount() function (around line 500):

```javascript
function subAmount() {
  var subtotalAmount = 0;
  var totalGST = 0;
  var totalAmount = 0;

  // Iterate through all rows to calculate per-item taxes
  $("#productTable tbody tr").each(function () {
    var rate = parseFloat($(this).find("[id^=rateValue]").val()) || 0;
    var quantity = parseFloat($(this).find("[id^=quantity]").val()) || 0;
    var gstRate = parseFloat($(this).find("[id^=gstRate]").val()) || 0; // ✅  Per-product rate

    var itemSubtotal = rate * quantity;
    var itemGST = (itemSubtotal * gstRate) / 100; // ✅ Per-item calculation
    var itemTotal = itemSubtotal + itemGST;

    subtotalAmount += itemSubtotal;
    totalGST += itemGST;
    totalAmount += itemTotal;
  });

  // Get discount
  var discount = parseFloat($("#discount").val()) || 0;
  var afterDiscount = subtotalAmount - discount;
  var grandTotal = afterDiscount + totalGST;

  // Display results
  $("#subTotal").val(subtotalAmount.toFixed(2));
  $("#subTotalValue").val(subtotalAmount.toFixed(2));

  $("#totalAmount").val(afterDiscount.toFixed(2));
  $("#totalAmountValue").val(afterDiscount.toFixed(2));

  $("#vat").val(totalGST.toFixed(2)); // ✅ Show actual calculated tax
  $("#vatValue").val(totalGST.toFixed(2));

  $("#grandTotal").val(grandTotal.toFixed(2));
  $("#grandTotalValue").val(grandTotal.toFixed(2));

  // Update GST breakdown display
  var breakdown = "Total GST: ₹" + totalGST.toFixed(2);
  $("#gstBreakdown").text(breakdown);
}
```

**Step 5: Add hidden field to store GST rates**

In the initial row and addRow() function, add:

```html
<input type="hidden" name="gstRate[]" id="gstRate1" value="0" />
```

**Step 6: Update order.php backend to use per-item GST**

Current code (lines 50-80):

```php
$items[] = [
    'product_id' => (int)$productId,
    'productName' => $_POST['productName'][$i] ?? '',
    'quantity' => (int)$quantity,
    'rate' => (float)$rate,
    'purchase_rate' => (float)$purchaseRate
];
```

Updated:

```php
$items[] = [
    'product_id' => (int)$productId,
    'productName' => $_POST['productName'][$i] ?? '',
    'quantity' => (int)$quantity,
    'rate' => (float)$rate,
    'purchase_rate' => (float)$purchaseRate,
    'gst_rate' => (float)($_POST['gstRate'][$i] ?? 0)  // ✅ PASS GST RATE
];
```

**Step 7: Verify fetchSelectedProduct.php returns GST rate**

```php
// Already should have per revision, but verify:
$sql = "SELECT product_id, product_name, rate, gst_rate, ... FROM product WHERE product_id = $productId";
```

### Testing Criteria

- ✅ No global GST dropdown visible
- ✅ When product selected, its GST rate is fetched and stored
- ✅ Tax calculation shown in summary
- ✅ Multiple products with different GST rates calculate correctly
- ✅ Tax breakdown shows sum of all item-level taxes
- ✅ Form submission includes per-item GST rates
- ✅ Payment calculation uses correct total including all item taxes

---

## FIX #5 & #6: Implement Batch-Level Stock Deduction & Expiry Validation

### Issue #5: Stock Deduction is Product-Level, Not Batch-Level

- File: SalesOrderController.php (line 125)
- Problem: `decreaseStock($productId, $quantity, ...)` only knows product, not batch
- Impact: Cannot track which batch sold; Cannot enforce FIFO/LIFO

### Issue #6: Expired Batch Validation Not Executing

- File: add-order.php → order.php has no expiry check
- Problem: Batch expiry validation exists in StockService but is never called
- Impact: Can sell expired medicines ⚠️

### Solution: Use StockService with Batch-Aware Deduction

**Current Flow (Broken):**

```
add-order (batch_id selected)
  → order.php (collects batch_id)
  → SalesOrderController.createSalesOrder()
    → insertOrderItem() [stores batch_id]
    → stockService.decreaseStock($productId, $qty)  ❌ No batch specified
```

**Corrected Flow:**

```
add-order (batch_id selected)
  → order.php (validates batch selected, validates batch not expired)
  → SalesOrderController.createSalesOrder()
    → insertOrderItem() [stores batch_id]
    → stockService.decreaseStockFromBatch($batch_id, $qty)  ✅ Batch-aware
      → Check batch not expired ✅
      → Check batch has sufficient qty ✅
      → Deduct specifically from this batch
```

### Implementation

**Step 1: Update order.php to validate batch and expiry**

Add before controller call (around line 100):

```php
// ✅ NEW: Validate batch selection and expiry
for ($i = 0; $i < count($items); $i++) {
    $batchId = $items[$i]['batch_id'] ?? 0;

    if(!$batchId) {
        throw new Exception("Batch selection required for all items");
    }

    // Verify batch exists and is not expired
    $batchCheck = $connect->prepare("
        SELECT batch_id, expiry_date, available_quantity
        FROM stock_batches
        WHERE batch_id = ?
          AND status = 'Active'
    ");
    $batchCheck->bind_param('i', $batchId);
    $batchCheck->execute();
    $batchResult = $batchCheck->get_result();

    if($batchResult->num_rows === 0) {
        throw new Exception("Invalid or inactive batch selected for item " . ($i+1));
    }

    $batch = $batchResult->fetch_assoc();

    // ✅ Check expiry
    if(strtotime($batch['expiry_date']) < time()) {
        throw new Exception("Batch expired for item " . ($i+1) . ": " . $batch['expiry_date']);
    }

    // ✅ Check stock in batch
    if($batch['available_quantity'] < $items[$i]['quantity']) {
        throw new Exception("Insufficient stock in selected batch for item " . ($i+1));
    }

    $batchCheck->close();
}
```

**Step 2: Modify SalesOrderController to use batch-aware deduction**

Update createSalesOrder() method (around line 120):

```php
// BEFORE (broken):
foreach ($items as $item) {
    // ... insert item ...
    $deductResult = $this->stockService->decreaseStock(
        $item['product_id'],  // ❌ Only product!
        $item['quantity'],
        'sales_order',
        $orderId,
        $this->userId
    );
}

// AFTER (fixed):
foreach ($items as $item) {
    // ... insert item ...

    // ✅ Pass batch_id for batch-aware deduction
    $deductResult = $this->stockService->decreaseStock(
        $item['product_id'],
        $item['quantity'],
        'sales_order',
        $orderId,
        $this->userId,
        [
            'batch_id' => $item['batch_id'],  // ✅ NEW
            'check_expiry' => true            // ✅ NEW: Validate expiry
        ]
    );

    if (!$deductResult) {
        throw new \Exception("Failed to deduct stock for product {$item['product_id']} from batch {$item['batch_id']}");
    }
}
```

**Step 3: Update StockService.decreaseStock() to handle batch-aware deduction**

File: `libraries/Services/StockService.php`

Current signature (line 136):

```php
public function decreaseStock($product_id, $qty, $ref_type, $ref_id, $user_id, $options = [])
```

This method already HAS batch support - need to verify it's being used:

```php
// Inside decreaseStock() - should already exist around line 150:
if (isset($options['batch_id'])) {
    // Use batch-specific deduction
    $batch_id = $options['batch_id'];

    // Get batch with expiry validation
    if (isset($options['check_expiry']) && $options['check_expiry']) {
        $batchRes = $this->db->query("
            SELECT id, exp_date, current_qty
            FROM product_batches
            WHERE id = ?
              AND status = 'Active'
        ");

        if (!$batchRes || $batchRes->num_rows === 0) {
            throw new \Exception("Batch expired or inactive");
        }

        $batch = $batchRes->fetch_assoc();

        // Check expiry
        if (strtotime($batch['exp_date']) < time()) {
            throw new \Exception("Cannot sell from expired batch");
        }

        // Check stock
        if ($batch['current_qty'] < $qty) {
            throw new \Exception("Insufficient stock in batch");
        }
    }

    // Deduct from this specific batch
    $updateSql = "UPDATE product_batches SET current_qty = current_qty - ? WHERE id = ?";
    // ... execute UPDATE

    // Record movement linking to batch
    $this->recordMovement(
        $product_id, $batch_id, 'OUTBOUND', $qty, $ref_type, $ref_id, $options['notes'] ?? ''
    );
} else {
    // Fall back to product-level deduction (old behavior)
    // ... existing code
}
```

### Testing Criteria

**Batch Fix #5 (Batch-Level Stock):**

- ✅ When batch selected in sales invoice, its batch_id is stored
- ✅ Stock is deducted from selected batch specifically
- ✅ stock_batches.available_quantity decreases
- ✅ order_item.batch_id contains the batch ID
- ✅ Multiple sales from same product but different batches deduct correctly

**Batch Fix #6 (Expiry Validation):**

- ✅ Cannot select expired batch in sales invoice
- ✅ If somehow expired batch ID submitted, backend rejects with error
- ✅ Sales cannot complete if batch is expired
- ✅ Expiry warning shown in frontend when batch selected
- ✅ Batch marked as "(EXPIRED)" if expiry_date <= today

---

## IMPLEMENTATION CHECKLIST

### Priority Order

1. **Fix #1:** Remove batch/expiry from PO (5 min)
2. **Fix #2:** Add batch selector to Sales Invoice (30 min)
3. **Fix #4:** Remove global GST dropdown (10 min)
4. **Fix #3:** Remove tax from PO (5 min)
5. **Fix #5 & #6:** Batch-level stock deduction (20 min)

### Files to Modify

- [ ] create_po.php
- [ ] add-order.php
- [ ] editorder.php (apply same batch/GST fixes)
- [ ] php_action/order.php
- [ ] php_action/fetchSelectedProduct.php
- [ ] libraries/Controllers/SalesOrderController.php
- [ ] libraries/Services/StockService.php
- [ ] php_action/createPurchaseOrder.php (remove GST)
- [ ] php_action/updatePurchaseOrder.php (remove GST)

### Files to Review (No Changes Expected)

- [ ] purchase_invoice.php (verify tax calculation is correct)
- [ ] purchase_invoice_action.php (verify stock creation works)

### Total Estimated Time: ~70 minutes

---

**Next:** Proceed to Phase 3 for Detailed Code Implementation
