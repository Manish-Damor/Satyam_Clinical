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
                  enctype="multipart/form-data"
                  class="row">

              <!-- Medicine Image -->
              <!-- <div class="form-group col-md-6">
                <label>Medicine Image</label>
                <input type="file"
                       class="form-control"
                       name="Medicine"
                       accept="image/*">
              </div> -->

              <!-- Medicine Name -->
              <div class="form-group col-md-6">
                <label>Medicine Name</label>
                <input type="text"
                       class="form-control"
                       name="productName"
                       placeholder="Medicine Name"
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

              <!-- Pack Size -->
              <div class="form-group col-md-6">
                <label>Pack Size</label>
                <input type="text"
                       class="form-control"
                       name="pack_size"
                       placeholder="e.g. 10x10, 10x3ml"
                       required>
              </div>

              <!-- HSN Code -->
              <div class="form-group col-md-6">
                <label>HSN Code</label>
                <input type="text"
                       class="form-control"
                       name="hsn_code"
                       placeholder="HSN Code"
                       required>
              </div>

              <!-- GST Rate -->
              <div class="form-group col-md-6">
                <label>GST Rate (%)</label>
                <select class="form-control" name="gst_rate" required>
                  <option value="0">0%</option>
                  <option selected value="5">5%</option>
                  <option value="12">12%</option>
                  <option value="18">18%</option>
                  <option value="24">24%</option>
                </select>
              </div>

              <!-- Status -->
              <div class="form-group col-md-6">
                <label>Status</label>
                <select class="form-control" name="productStatus" required>
                  <option value="1">Available</option>
                  <option value="2">Not Available</option>
                </select>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center">
                <button type="submit"
                        name="create"
                        class="btn btn-primary mt-3">
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
