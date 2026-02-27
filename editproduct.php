<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
  header('location:manage_medicine.php');
  exit;
}

$stmt = $connect->prepare("SELECT * FROM product WHERE product_id = ?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
  header('location:manage_medicine.php');
  exit;
}

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

$brandRows = [];
$brandRes = $connect->query("SELECT brand_id, brand_name FROM brands WHERE brand_status = 1 ORDER BY brand_name ASC");
if ($brandRes) {
  while ($row = $brandRes->fetch_assoc()) {
    $brandRows[] = $row;
  }
}

$categoryRows = [];
$categoryRes = $connect->query("SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 ORDER BY categories_name ASC");
if ($categoryRes) {
  while ($row = $categoryRes->fetch_assoc()) {
    $categoryRows[] = $row;
  }
}

$errorMsg = isset($_GET['error']) ? trim($_GET['error']) : '';
?>

<style>
.edit-section-card { border: 1px solid #e8eef5; border-radius: 10px; }
.edit-section-card .card-header { background: #f8fbff; border-bottom: 1px solid #e8eef5; }
</style>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-6 align-self-center">
      <h3 class="text-primary mb-0">Edit Medicine</h3>
      <small class="text-muted">Update medicine master details and image</small>
    </div>
    <div class="col-md-6 align-self-center text-right">
      <a href="manage_medicine.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i> Back to Medicines</a>
    </div>
  </div>

  <div class="container-fluid">
    <?php if ($errorMsg !== ''): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars(str_replace('_', ' ', $errorMsg)); ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-4 mb-3">
        <div class="card edit-section-card">
          <div class="card-header"><strong>Medicine Image</strong></div>
          <div class="card-body">
            <div class="text-center mb-3">
              <img src="assets/myimages/<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>" alt="Medicine Image" style="width:220px;height:220px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;" />
            </div>
            <form action="php_action/editProductImage.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>">
              <div class="form-group">
                <label>Select New Image</label>
                <input type="file" class="form-control" name="productImage">
              </div>
              <button type="submit" name="btn" class="btn btn-primary btn-block">Save Photo</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8 mb-3">
        <div class="card edit-section-card">
          <div class="card-header"><strong>Medicine Information</strong></div>
          <div class="card-body">
            <form method="POST" id="submitProductForm" action="php_action/editProduct.php?id=<?php echo $productId; ?>">
              <div class="row">
                <div class="form-group col-md-6">
                  <label>Medicine Name</label>
                  <input type="text" class="form-control" name="editProductName" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                </div>
                <div class="form-group col-md-6">
                  <label>Composition / Content</label>
                  <input type="text" class="form-control" name="editContent" value="<?php echo htmlspecialchars($product['content']); ?>" required>
                </div>

                <div class="form-group col-md-6">
                  <label>Product Type</label>
                  <select class="form-control" name="editProductType" required>
                    <?php foreach ($productTypeOptions as $type): ?>
                      <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($product['product_type'] === $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label>Unit Type</label>
                  <select class="form-control" name="editUnitType" required>
                    <?php foreach ($unitTypeOptions as $unit): ?>
                      <option value="<?php echo htmlspecialchars($unit); ?>" <?php echo ($product['unit_type'] === $unit) ? 'selected' : ''; ?>><?php echo htmlspecialchars($unit); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group col-md-6">
                  <label>Pack Size</label>
                  <input type="text" class="form-control" name="editPackSize" value="<?php echo htmlspecialchars($product['pack_size']); ?>" required>
                </div>
                <div class="form-group col-md-6">
                  <label>HSN Code</label>
                  <input type="text" class="form-control" name="editHsnCode" value="<?php echo htmlspecialchars($product['hsn_code']); ?>" required>
                </div>

                <div class="form-group col-md-6">
                  <label>GST Rate (%)</label>
                  <select class="form-control" name="editGstRate" required>
                    <?php foreach ($gstRateOptions as $rate): ?>
                      <?php $rateLabel = rtrim(rtrim($rate, '0'), '.'); ?>
                      <option value="<?php echo htmlspecialchars($rate); ?>" <?php echo ((float)$product['gst_rate'] === (float)$rate) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rateLabel); ?>%</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label>Reorder Level</label>
                  <input type="number" class="form-control" name="editReorderLevel" min="0" value="<?php echo (int)$product['reorder_level']; ?>" required>
                </div>

                <div class="form-group col-md-6">
                  <label>Manufacturer</label>
                  <select name="editBrandName" class="form-control" required>
                    <?php foreach ($brandRows as $brand): ?>
                      <option value="<?php echo (int)$brand['brand_id']; ?>" <?php echo ((int)$product['brand_id'] === (int)$brand['brand_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['brand_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label>Category</label>
                  <select name="editCategoryName" class="form-control" required>
                    <?php foreach ($categoryRows as $category): ?>
                      <option value="<?php echo (int)$category['categories_id']; ?>" <?php echo ((int)$product['categories_id'] === (int)$category['categories_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['categories_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group col-md-6">
                  <label>Status</label>
                  <select class="form-control" name="editProductStatus" required>
                    <option value="1" <?php echo ((int)$product['status'] === 1) ? 'selected' : ''; ?>>Available</option>
                    <option value="2" <?php echo ((int)$product['status'] === 2) ? 'selected' : ''; ?>>Not Available</option>
                  </select>
                </div>

                <div class="col-md-12 text-center mt-2">
                  <button type="submit" class="btn btn-primary px-4">Update Medicine</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
