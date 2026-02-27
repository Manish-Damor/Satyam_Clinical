<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php
$productTypeOptions = ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Drops', 'Others'];
$unitTypeOptions = ['Strip', 'Box', 'Bottle', 'Vial', 'Tube', 'Piece', 'Sachet'];
$gstRateOptions = ['0.00', '5.00', '12.00', '18.00', '28.00'];

$checkTable = $connect->query("SHOW TABLES LIKE 'master_product_types'");
if ($checkTable && $checkTable->num_rows > 0) {
  $res = $connect->query("SELECT type_code FROM master_product_types WHERE is_active = 1 ORDER BY sort_order ASC, type_code ASC");
  if ($res && $res->num_rows > 0) {
    $productTypeOptions = [];
    while ($row = $res->fetch_assoc()) {
      $productTypeOptions[] = $row['type_code'];
    }
  }
}

$checkTable = $connect->query("SHOW TABLES LIKE 'master_unit_types'");
if ($checkTable && $checkTable->num_rows > 0) {
  $res = $connect->query("SELECT unit_code FROM master_unit_types WHERE is_active = 1 ORDER BY sort_order ASC, unit_code ASC");
  if ($res && $res->num_rows > 0) {
    $unitTypeOptions = [];
    while ($row = $res->fetch_assoc()) {
      $unitTypeOptions[] = $row['unit_code'];
    }
  }
}

$checkTable = $connect->query("SHOW TABLES LIKE 'master_gst_slabs'");
if ($checkTable && $checkTable->num_rows > 0) {
  $res = $connect->query("SELECT gst_rate FROM master_gst_slabs WHERE is_active = 1 ORDER BY sort_order ASC, gst_rate ASC");
  if ($res && $res->num_rows > 0) {
    $gstRateOptions = [];
    while ($row = $res->fetch_assoc()) {
      $gstRateOptions[] = number_format((float)$row['gst_rate'], 2, '.', '');
    }
  }
}
?>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary">Add Medicine</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Add Medicine</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-body">

            <form method="POST"
                  id="submitProductForm"
                  action="php_action/createProduct.php"
                  class="row">

              <!-- ================= BASIC INFORMATION ================= -->
              <div class="col-md-12">
                <h5 class="mb-3 text-primary">Basic Information</h5>
              </div>

              <!-- Medicine Name -->
              <div class="form-group col-md-6">
                <label>Medicine Name</label>
                <input type="text"
                       class="form-control"
                       name="productName"
                       placeholder="Enter Brand Name (e.g. Crocin 500)"
                       required>
              </div>

              <!-- Composition / Content -->
              <div class="form-group col-md-6">
                <label>Composition / Content</label>
                <input type="text"
                       class="form-control"
                       name="content"
                       placeholder="e.g. Paracetamol 500mg"
                       required>
              </div>

              <!-- Manufacturer -->
              <div class="form-group col-md-6">
                <label>Manufacturer</label>
                <select class="form-control" name="brandName" required>
                  <option value="">~~ SELECT ~~</option>
                  <?php
                  $sql = "SELECT brand_id, brand_name
                          FROM brands
                          WHERE brand_active = 1 AND brand_status = 1";
                  $result = $connect->query($sql);
                  while ($row = $result->fetch_array()) {
                    echo "<option value='{$row[0]}'>{$row[1]}</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- Category -->
              <div class="form-group col-md-6">
                <label>Category</label>
                <select class="form-control" name="categoryName" required>
                  <option value="">~~ SELECT ~~</option>
                  <?php
                  $sql = "SELECT categories_id, categories_name
                          FROM categories
                          WHERE categories_active = 1 AND categories_status = 1";
                  $result = $connect->query($sql);
                  while ($row = $result->fetch_array()) {
                    echo "<option value='{$row[0]}'>{$row[1]}</option>";
                  }
                  ?>
                </select>
              </div>


              <!-- ================= PACKAGING DETAILS ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Packaging Details</h5>
              </div>

              <!-- Product Type -->
              <div class="form-group col-md-6">
                <label>Product Type</label>
                <select class="form-control" name="product_type" required>
                  <option value="">~~ SELECT ~~</option>
                  <?php foreach ($productTypeOptions as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Unit Type -->
              <div class="form-group col-md-6">
                <label>Unit Type</label>
                <select class="form-control" name="unit_type" required>
                  <option value="">~~ SELECT ~~</option>
                  <?php foreach ($unitTypeOptions as $unit): ?>
                    <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Pack Size -->
              <div class="form-group col-md-6">
                <label>Pack Size</label>
                <input type="text"
                       class="form-control"
                       name="pack_size"
                       placeholder="e.g. 10x10, 100ml"
                       required>
              </div>


              <!-- ================= TAX & INVENTORY ================= -->
              <div class="col-md-12 mt-4">
                <h5 class="mb-3 text-primary">Tax & Inventory Settings</h5>
              </div>

              <!-- HSN Code -->
              <div class="form-group col-md-6">
                <label>HSN Code</label>
                <input type="text"
                       class="form-control"
                       name="hsn_code"
                       placeholder="Enter HSN Code"
                       required>
              </div>

              <!-- GST Rate -->
              <div class="form-group col-md-6">
                <label>GST Rate (%)</label>
                <select class="form-control" name="gst_rate" required>
                  <?php foreach ($gstRateOptions as $gst): ?>
                    <option value="<?php echo htmlspecialchars($gst); ?>" <?php echo ((float)$gst === 5.00) ? 'selected' : ''; ?>><?php echo rtrim(rtrim($gst, '0'), '.'); ?>%</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Reorder Level -->
              <div class="form-group col-md-6">
                <label>Reorder Level</label>
                <input type="number"
                       class="form-control"
                       name="reorder_level"
                       placeholder="Minimum stock before alert"
                       min="0"
                       required>
              </div>

              <!-- Status -->
              <div class="form-group col-md-6">
                <label>Status</label>
                <select class="form-control" name="productStatus" required>
                  <option value="1">Active</option>
                  <option value="2">Inactive</option>
                </select>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center mt-4">
                <button type="submit"
                        name="create"
                        class="btn btn-primary">
                  Save Medicine
                </button>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('./constant/layout/footer.php');?>
