<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
if (!isset($_GET['id'])) {
  header('Location: manage_medicine.php');
  exit;
}

$product_id = (int) $_GET['id'];

/* Fetch product info */
$productSql = "SELECT product_name FROM product WHERE product_id = ?";
$productStmt = $connect->prepare($productSql);
$productStmt->bind_param("i", $product_id);
$productStmt->execute();
$productResult = $productStmt->get_result();
$product = $productResult->fetch_assoc();

if (!$product) {
  header('Location: manage_medicine.php');
  exit;
}

/* Fetch batch list */
$batchSql = "
SELECT 
  batch_id,
  batch_number,
  expiry_date,
  available_quantity,
  purchase_rate,
  mrp,
  status
FROM product_batches
WHERE product_id = ?
ORDER BY expiry_date ASC
";

$batchStmt = $connect->prepare($batchSql);
$batchStmt->bind_param("i", $product_id);
$batchStmt->execute();
$batches = $batchStmt->get_result();
?>

<div class="page-wrapper">

  <div class="row page-titles">
    <div class="col-md-6 align-self-center">
      <h3 class="text-primary">Batch Stock</h3>
      <p class="text-muted mb-0">
        Medicine: <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
      </p>
    </div>
    <div class="col-md-6 align-self-center text-right">
      <a href="manage_medicine.php" class="btn btn-secondary">
        ← Back to Medicines
      </a>
    </div>
  </div>

  <div class="container-fluid">

    <div class="card">
      <div class="card-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead class="bg-light">
              <tr>
                <th>#</th>
                <th>Batch No</th>
                <th>Expiry Date</th>
                <th>Available Qty</th>
                <th>Purchase Rate</th>
                <th>MRP</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <?php
              if ($batches->num_rows > 0) {
                $i = 1;
                while ($row = $batches->fetch_assoc()) {

                  $rowClass = '';
                  if ($row['expiry_date'] < date('Y-m-d')) {
                    $rowClass = 'table-danger';
                  }
              ?>
              <tr class="<?php echo $rowClass; ?>">
                <td><?php echo $i++; ?></td>

                <td><?php echo htmlspecialchars($row['batch_number']); ?></td>

                <td>
                  <?php echo date('d-m-Y', strtotime($row['expiry_date'])); ?>
                </td>

                <td class="text-center">
                  <?php
                  if ($row['available_quantity'] > 0) {
                    echo "<span class='label label-success'>{$row['available_quantity']}</span>";
                  } else {
                    echo "<span class='label label-danger'>0</span>";
                  }
                  ?>
                </td>

                <td><?php echo number_format($row['purchase_rate'], 2); ?></td>

                <td><?php echo number_format($row['mrp'], 2); ?></td>

                <td>
                  <?php
                  if ($row['status'] === 'Active') {
                    echo "<span class='label label-success'>Active</span>";
                  } elseif ($row['status'] === 'Expired') {
                    echo "<span class='label label-danger'>Expired</span>";
                  } else {
                    echo "<span class='label label-warning'>Blocked</span>";
                  }
                  ?>
                </td>
              </tr>
              <?php
                }
              } else {
                echo "<tr>
                        <td colspan='7' class='text-center'>
                          No batches found
                        </td>
                      </tr>";
              }
              ?>
            </tbody>

          </table>
        </div>

      </div>
    </div>

  </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
