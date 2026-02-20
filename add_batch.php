<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    header('Location: manage_medicine.php');
    exit;
}

// Get product information
$productSql = "
SELECT 
  p.product_id,
  p.product_name,
  p.content,
  p.pack_size,
  b.brand_name
FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
WHERE p.product_id = ?
";

$productStmt = $connect->prepare($productSql);
$productStmt->bind_param("i", $product_id);
$productStmt->execute();
$productResult = $productStmt->get_result();

if ($productResult->num_rows == 0) {
    header('Location: manage_medicine.php');
    exit;
}

$product = $productResult->fetch_assoc();
$productStmt->close();
?>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary">Add Batch</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="manage_batches.php?product_id=<?php echo $product_id; ?>">Batches</a></li>
        <li class="breadcrumb-item active">Add Batch</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">
              Adding batch for: <strong><?php echo htmlspecialchars($product['product_name']); ?></strong> 
              (<?php echo htmlspecialchars($product['brand_name']); ?>)
            </h5>
          </div>
          <div class="card-body">

            <form method="POST"
                  id="addBatchForm"
                  action="php_action/createBatch.php"
                  class="row">

              <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

              <!-- ================= BATCH INFORMATION ================= -->
              <div class="col-md-12">
                <h5 class="mb-3 text-primary">Batch Information</h5>
              </div>

              <!-- Batch Number -->
              <div class="form-group col-md-6">
                <label>Batch Number *</label>
                <input type="text"
                       class="form-control"
                       name="batch_number"
                       placeholder="e.g. BATCH20240101"
                       required>
              </div>

              <!-- Manufacturing Date -->
              <div class="form-group col-md-6">
                <label>Manufacturing Date</label>
                <input type="date"
                       class="form-control"
                       name="manufacturing_date">
              </div>

              <!-- Expiry Date -->
              <div class="form-group col-md-6">
                <label>Expiry Date *</label>
                <input type="date"
                       class="form-control"
                       name="expiry_date"
                       required>
              </div>

              <!-- Supplier -->
              <div class="form-group col-md-6">
                <label>Supplier</label>
                <select class="form-control" name="supplier_id">
                  <option value="">~~ SELECT ~~</option>
                  <?php
                  $sql = "SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status = 'Active' ORDER BY supplier_name";
                  $result = $connect->query($sql);
                  while ($row = $result->fetch_array()) {
                    echo "<option value='{$row[0]}'>{$row[1]}</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- ================= STOCK INFORMATION ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Stock Information</h5>
              </div>

              <!-- Available Quantity -->
              <div class="form-group col-md-6">
                <label>Available Quantity *</label>
                <input type="number"
                       class="form-control"
                       name="available_quantity"
                       min="0"
                       required>
              </div>

              <!-- Reserved Quantity -->
              <div class="form-group col-md-6">
                <label>Reserved Quantity</label>
                <input type="number"
                       class="form-control"
                       name="reserved_quantity"
                       min="0"
                       value="0">
              </div>

              <!-- Damaged Quantity -->
              <div class="form-group col-md-6">
                <label>Damaged Quantity</label>
                <input type="number"
                       class="form-control"
                       name="damaged_quantity"
                       min="0"
                       value="0">
              </div>

              <!-- ================= PRICING INFORMATION ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Pricing Information</h5>
              </div>

              <!-- Purchase Rate -->
              <div class="form-group col-md-6">
                <label>Purchase Rate (₹) *</label>
                <input type="number"
                       class="form-control"
                       name="purchase_rate"
                       step="0.01"
                       min="0"
                       placeholder="0.00"
                       required>
              </div>

              <!-- MRP -->
              <div class="form-group col-md-6">
                <label>MRP - Maximum Retail Price (₹) *</label>
                <input type="number"
                       class="form-control"
                       name="mrp"
                       step="0.01"
                       min="0"
                       placeholder="0.00"
                       required>
              </div>

              <!-- ================= STATUS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Status</h5>
              </div>

              <!-- Status -->
              <div class="form-group col-md-6">
                <label>Status</label>
                <select class="form-control" name="status" required>
                  <option value="Active" selected>Active</option>
                  <option value="Blocked">Blocked</option>
                  <option value="Damaged">Damaged</option>
                </select>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center mt-4">
                <button type="submit"
                        name="create"
                        class="btn btn-primary">
                  Save Batch
                </button>
                <a href="manage_batches.php?product_id=<?php echo $product_id; ?>" class="btn btn-secondary">
                  Cancel
                </a>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
