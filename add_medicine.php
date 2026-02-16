<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

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
                  <option value="Tablet">Tablet</option>
                  <option value="Capsule">Capsule</option>
                  <option value="Syrup">Syrup</option>
                  <option value="Injection">Injection</option>
                  <option value="Ointment">Ointment</option>
                  <option value="Drops">Drops</option>
                  <option value="Others">Others</option>
                </select>
              </div>

              <!-- Unit Type -->
              <div class="form-group col-md-6">
                <label>Unit Type</label>
                <select class="form-control" name="unit_type" required>
                  <option value="">~~ SELECT ~~</option>
                  <option value="Strip">Strip</option>
                  <option value="Box">Box</option>
                  <option value="Bottle">Bottle</option>
                  <option value="Vial">Vial</option>
                  <option value="Tube">Tube</option>
                  <option value="Piece">Piece</option>
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
                  <option value="0">0%</option>
                  <option value="5" selected>5%</option>
                  <option value="12">12%</option>
                  <option value="18">18%</option>
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
