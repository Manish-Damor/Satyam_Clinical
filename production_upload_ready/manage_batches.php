<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// ============================================================
// BATCH MANAGEMENT - PROFESSIONAL ERP SYSTEM
// ============================================================

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
  p.hsn_code,
  p.gst_rate,
  b.brand_name,
  c.categories_name
FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
LEFT JOIN categories c ON c.categories_id = p.categories_id
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

// Get batch filter
$batch_status = isset($_GET['status']) ? $_GET['status'] : '';
$where = "WHERE pb.product_id = {$product_id}";

if (!empty($batch_status)) {
    $status_escaped = $connect->real_escape_string($batch_status);
    $where .= " AND pb.status = '{$status_escaped}'";
}

// Get batches
$batchesSql = "
SELECT 
  pb.batch_id,
  pb.batch_number,
  pb.manufacturing_date,
  pb.expiry_date,
  pb.available_quantity,
  pb.reserved_quantity,
  pb.damaged_quantity,
  pb.purchase_rate,
  pb.mrp,
  pb.status,
  s.supplier_name,
  pb.created_at,
  
  DATEDIFF(pb.expiry_date, CURDATE()) AS days_until_expiry,
  CASE 
    WHEN pb.expiry_date < CURDATE() THEN 'EXPIRED'
    WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
    WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 90 THEN 'WARNING'
    ELSE 'OK'
  END AS expiry_alert

FROM product_batches pb
LEFT JOIN suppliers s ON s.supplier_id = pb.supplier_id
{$where}
ORDER BY pb.expiry_date ASC
";

$batchesResult = $connect->query($batchesSql);
?>

<div class="page-wrapper">

  <!-- PAGE HEADER -->
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary"><i class="fa fa-boxes"></i> Batch Management</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="manage_medicine.php">Medicines</a></li>
        <li class="breadcrumb-item active">Batches</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">

    <!-- ============================================================
         PRODUCT INFORMATION CARD
         ============================================================ -->
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">Product Information</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p>
              <strong>Medicine:</strong> <?php echo htmlspecialchars($product['product_name']); ?>
            </p>
            <p>
              <strong>Composition:</strong> <?php echo htmlspecialchars($product['content']); ?>
            </p>
            <p>
              <strong>Manufacturer:</strong> <?php echo htmlspecialchars($product['brand_name']); ?>
            </p>
          </div>
          <div class="col-md-6">
            <p>
              <strong>Category:</strong> <?php echo htmlspecialchars($product['categories_name']); ?>
            </p>
            <p>
              <strong>Pack Size:</strong> <?php echo htmlspecialchars($product['pack_size']); ?>
            </p>
            <p>
              <strong>HSN Code:</strong> <?php echo htmlspecialchars($product['hsn_code']); ?> 
              | <strong>GST:</strong> <?php echo $product['gst_rate']; ?>%
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         FILTERS
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <h5 class="mb-0">Filter Batches</h5>
      </div>
      <div class="card-body">
        <form method="GET" class="form-inline">
          <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
          
          <div class="form-group mr-3 mb-2">
            <label for="status" class="mr-2">Status:</label>
            <select id="status" name="status" class="form-control">
              <option value="">All</option>
              <option value="Active" <?php echo ($batch_status == 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Expired" <?php echo ($batch_status == 'Expired') ? 'selected' : ''; ?>>Expired</option>
              <option value="Blocked" <?php echo ($batch_status == 'Blocked') ? 'selected' : ''; ?>>Blocked</option>
              <option value="Damaged" <?php echo ($batch_status == 'Damaged') ? 'selected' : ''; ?>>Damaged</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary mr-2">
            <i class="fa fa-filter"></i> Filter
          </button>
          <a href="manage_batches.php?product_id=<?php echo $product_id; ?>" class="btn btn-secondary">
            <i class="fa fa-refresh"></i> Reset
          </a>
        </form>
      </div>
    </div>

    <!-- ============================================================
         BATCHES TABLE
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <h5 class="mb-0">Batches (<?php echo $batchesResult->num_rows; ?>)</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="batchesTable" class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Batch Number</th>
                <th style="width: 10%;">Mfg Date</th>
                <th style="width: 10%;">Expiry Date</th>
                <th style="width: 10%;">Stock</th>
                <th style="width: 10%;">MRP</th>
                <th style="width: 10%;">Purchase Rate</th>
                <th style="width: 10%;">Supplier</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 15%;">View</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              if ($batchesResult->num_rows > 0) {
                while ($batch = $batchesResult->fetch_assoc()) {
                  // Determine status badge
                  if ($batch['status'] == 'Active') {
                    $status_badge = '<span class="badge badge-success">Active</span>';
                  } elseif ($batch['status'] == 'Expired') {
                    $status_badge = '<span class="badge badge-danger">Expired</span>';
                  } elseif ($batch['status'] == 'Damaged') {
                    $status_badge = '<span class="badge badge-warning">Damaged</span>';
                  } else {
                    $status_badge = '<span class="badge badge-secondary">Blocked</span>';
                  }

                  // Determine expiry alert
                  if ($batch['expiry_alert'] == 'EXPIRED') {
                    $expiry_badge = '<span class="badge badge-danger">EXPIRED</span>';
                    $row_class = 'table-danger';
                  } elseif ($batch['expiry_alert'] == 'CRITICAL') {
                    $expiry_badge = '<span class="badge badge-danger">CRITICAL</span>';
                    $row_class = 'table-danger';
                  } elseif ($batch['expiry_alert'] == 'WARNING') {
                    $expiry_badge = '<span class="badge badge-warning">WARNING</span>';
                    $row_class = 'table-warning';
                  } else {
                    $expiry_badge = '<span class="badge badge-success">OK</span>';
                    $row_class = '';
                  }

                  $total_stock = $batch['available_quantity'] + $batch['reserved_quantity'] + $batch['damaged_quantity'];
              ?>
              <tr class="<?php echo $row_class; ?>">
                <td class="text-center"><strong><?php echo $batch['batch_id']; ?></strong></td>
                
                <td>
                  <strong><?php echo htmlspecialchars($batch['batch_number']); ?></strong>
                </td>

                <td class="text-center">
                  <?php echo $batch['manufacturing_date'] ? date('d-M-Y', strtotime($batch['manufacturing_date'])) : 'N/A'; ?>
                </td>

                <td class="text-center">
                  <?php echo $expiry_badge; ?>
                  <br>
                  <small><?php echo date('d-M-Y', strtotime($batch['expiry_date'])); ?></small>
                </td>

                <td class="text-center">
                  <strong><?php echo number_format($total_stock); ?></strong>
                  <br>
                  <small class="text-success">A: <?php echo number_format($batch['available_quantity']); ?></small>
                  <?php if ($batch['reserved_quantity'] > 0): ?>
                    <br>
                    <small class="text-warning">R: <?php echo number_format($batch['reserved_quantity']); ?></small>
                  <?php endif; ?>
                  <?php if ($batch['damaged_quantity'] > 0): ?>
                    <br>
                    <small class="text-danger">D: <?php echo number_format($batch['damaged_quantity']); ?></small>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <strong>₹<?php echo number_format($batch['mrp'], 2); ?></strong>
                </td>

                <td class="text-center">
                  ₹<?php echo number_format($batch['purchase_rate'], 2); ?>
                </td>

                <td>
                  <small><?php echo htmlspecialchars($batch['supplier_name'] ?? 'Not Specified'); ?></small>
                </td>

                <td class="text-center">
                  <?php echo $status_badge; ?>
                </td>

                <td class="text-center">
                  <span class="badge badge-light">Read Only</span>
                </td>
              </tr>
              <?php 
                }
              } else {
              ?>
              <tr>
                <td colspan="10" class="text-center text-muted py-4">
                  <i class="fa fa-inbox fa-3x mb-3"></i>
                  <p>No batches found.</p>
                </td>
              </tr>
              <?php 
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
  .table-hover tbody tr:hover {
    background-color: #f5f5f5;
  }
</style>

<script>
  $(document).ready(function() {
    $('#batchesTable').DataTable({
      "pageLength": 25,
      "columnDefs": [
        {"orderable": false, "targets": [9]}
      ]
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>

<?php include('./constant/layout/footer.php'); ?>
