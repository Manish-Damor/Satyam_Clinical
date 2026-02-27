<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>
<?php include('./constant/connect.php');?>

<?php
header('Location: purchase_invoice.php?error=manual_stock_entry_disabled');
exit;
?>

<div class="page-wrapper">
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary">Add Stock / Purchase Medicine</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Add Stock</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-body">

            <form method="POST"
                  action="php_action/createStock.php"
                  class="row"
                  id="addStockForm">

              <!-- Medicine -->
              <div class="form-group col-md-6">
                <label>Medicine</label>
                <select name="product_id" class="form-control" required>
                  <option value="">~~ SELECT MEDICINE ~~</option>
                  <?php
                  $sql = "SELECT product_id, product_name 
                          FROM product 
                      WHERE status = 1";
                  $result = $connect->query($sql);
                  while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['product_id']}'>
                            {$row['product_name']}
                          </option>";
                  }
                  ?>
                </select>
              </div>

              <!-- Batch No -->
              <div class="form-group col-md-6">
                <label>Batch Number</label>
                <input type="text"
                       name="batch_number"
                       class="form-control"
                       placeholder="Batch No"
                       required>
              </div>

              <!-- Expiry -->
              <div class="form-group col-md-6">
                <label>Expiry Date</label>
                <input type="date"
                       name="expiry_date"
                       class="form-control"
                       required>
              </div>

              <!-- Quantity -->
              <div class="form-group col-md-6">
                <label>Quantity</label>
                <input type="number"
                       name="quantity"
                       class="form-control"
                       min="1"
                       required>
              </div>

              <!-- Purchase Rate -->
              <div class="form-group col-md-6">
                <label>Purchase Rate</label>
                <input type="number"
                       step="0.01"
                       name="purchase_rate"
                       class="form-control"
                       required>
              </div>

              <!-- MRP -->
              <div class="form-group col-md-6">
                <label>MRP</label>
                <input type="number"
                       step="0.01"
                       name="mrp"
                       class="form-control"
                       required>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-center">
                <button type="submit"
                        name="add_stock"
                        class="btn btn-primary mt-3">
                  Add Stock
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
