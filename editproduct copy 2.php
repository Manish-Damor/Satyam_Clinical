<form method="POST"
      action="php_action/editProduct.php?id=<?php echo $_GET['id']; ?>"
      enctype="multipart/form-data">

  <fieldset>
    <h1>Medicine Information</h1>

    <!-- Medicine Name -->
    <div class="form-group col-md-6">
      <label class="control-label">Medicine Name</label>
      <input type="text"
             class="form-control"
             name="editProductName"
             value="<?php echo $result['product_name']; ?>"
             required>
    </div>

    <!-- Manufacturer -->
    <div class="form-group col-md-6">
      <label class="control-label">Manufacturer</label>
      <select name="editBrandName" class="form-control" required>
        <?php
        $sql = "SELECT brand_id, brand_name
                FROM brands
                WHERE brand_status = 1";
        $brands = $connect->query($sql);
        while ($b = $brands->fetch_assoc()) {
          $selected = ($result['brand_id'] == $b['brand_id']) ? 'selected' : '';
          echo "<option value='{$b['brand_id']}' $selected>
                  {$b['brand_name']}
                </option>";
        }
        ?>
      </select>
    </div>

    <!-- Category -->
    <div class="form-group col-md-6">
      <label class="control-label">Category</label>
      <select name="editCategoryName" class="form-control" required>
        <?php
        $sql = "SELECT categories_id, categories_name
                FROM categories
                WHERE categories_status = 1";
        $categories = $connect->query($sql);
        while ($c = $categories->fetch_assoc()) {
          $selected = ($result['categories_id'] == $c['categories_id']) ? 'selected' : '';
          echo "<option value='{$c['categories_id']}' $selected>
                  {$c['categories_name']}
                </option>";
        }
        ?>
      </select>
    </div>

    <!-- Status -->
    <div class="form-group col-md-6">
      <label class="control-label">Status</label>
      <select class="form-control" name="editProductStatus" required>
        <option value="1" <?php if ($result['active'] == 1) echo "selected"; ?>>
          Available
        </option>
        <option value="2" <?php if ($result['active'] == 2) echo "selected"; ?>>
          Not Available
        </option>
      </select>
    </div>

    <!-- Submit -->
    <div class="col-md-12 text-center">
      <button type="submit"
              name="update_medicine"
              class="btn btn-primary mt-3">
        Update Medicine
      </button>
    </div>

  </fieldset>
</form>
