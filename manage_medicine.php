<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// ============================================================
// PROFESSIONAL PHARMACY INVENTORY MANAGEMENT
// ============================================================

// Get filter parameters
$filter_brand = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filter_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where = "WHERE p.status = 1";

if (!empty($search)) {
    $search_escaped = $connect->real_escape_string($search);
    $where .= " AND (p.product_name LIKE '%{$search_escaped}%' 
                     OR p.content LIKE '%{$search_escaped}%'
                     OR p.hsn_code LIKE '%{$search_escaped}%')";
}

if ($filter_brand > 0) {
    $where .= " AND p.brand_id = {$filter_brand}";
}

if ($filter_category > 0) {
    $where .= " AND p.categories_id = {$filter_category}";
}

// Get inventory summary with batch information
$sql = "
SELECT 
  p.product_id,
  p.product_name,
  p.content,
  p.product_type,
  p.unit_type,
  p.pack_size,
  p.hsn_code,
  p.gst_rate,
  p.reorder_level,
  p.status,
  
  b.brand_name,
  c.categories_name,
  
  COALESCE(SUM(pb.available_quantity), 0) AS total_stock,
  COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS active_stock,
  COALESCE(SUM(CASE WHEN pb.status = 'Expired' THEN pb.available_quantity ELSE 0 END), 0) AS expired_stock,
  
  COUNT(DISTINCT CASE WHEN pb.status = 'Active' THEN pb.batch_id END) AS batch_count,
  
  MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END) AS nearest_expiry,
  MAX(CASE WHEN pb.status = 'Active' THEN pb.mrp END) AS current_mrp,
  
  CASE 
    WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN 'OUT_OF_STOCK'
    WHEN COALESCE(SUM(pb.available_quantity), 0) <= p.reorder_level THEN 'LOW_STOCK'
    ELSE 'IN_STOCK'
  END AS stock_status,
  
  CASE 
    WHEN MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END) IS NULL THEN 'NO_BATCH'
    WHEN MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END) < CURDATE() THEN 'EXPIRED'
    WHEN DATEDIFF(MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END), CURDATE()) <= 30 THEN 'CRITICAL'
    WHEN DATEDIFF(MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END), CURDATE()) <= 90 THEN 'WARNING'
    ELSE 'OK'
  END AS expiry_status

FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
LEFT JOIN categories c ON c.categories_id = p.categories_id
LEFT JOIN product_batches pb ON pb.product_id = p.product_id
{$where}
GROUP BY p.product_id
ORDER BY p.product_name ASC
";

$result = $connect->query($sql);

if (!$result) {
    die("Query Error: " . $connect->error);
}

// Get statistics
$stats_sql = "
SELECT 
  COUNT(DISTINCT p.product_id) AS total_medicines,
  COALESCE(SUM(COALESCE(pb.available_quantity, 0)), 0) AS total_stock_units,
  COUNT(DISTINCT CASE WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN p.product_id END) AS out_of_stock_items,
  COUNT(DISTINCT CASE WHEN COALESCE(SUM(pb.available_quantity), 0) <= p.reorder_level THEN p.product_id END) AS low_stock_items
FROM product p
LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
WHERE p.status = 1
";

$stats_result = $connect->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>


<div class="page-wrapper">

  <!-- PAGE HEADER -->
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary"><i class="fa fa-pills"></i> Pharmacy Inventory Management</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Medicines</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">

    <!-- ============================================================
         STATISTICS DASHBOARD
         ============================================================ -->
    <div class="row">
      <div class="col-lg-3 col-md-6">
        <div class="card border-left-primary shadow">
          <div class="card-body">
            <div class="text-primary font-weight-bold text-uppercase mb-1">
              Total Medicines
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['total_medicines']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card border-left-success shadow">
          <div class="card-body">
            <div class="text-success font-weight-bold text-uppercase mb-1">
              Total Stock Units
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['total_stock_units']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card border-left-warning shadow">
          <div class="card-body">
            <div class="text-warning font-weight-bold text-uppercase mb-1">
              Low Stock Items
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['low_stock_items']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card border-left-danger shadow">
          <div class="card-body">
            <div class="text-danger font-weight-bold text-uppercase mb-1">
              Out of Stock
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['out_of_stock_items']); ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         FILTERS & SEARCH SECTION
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <h5 class="mb-0">Filters & Search</h5>
      </div>
      <div class="card-body">
        <form method="GET" class="form-inline">
          
          <!-- Search -->
          <div class="form-group mr-3 mb-2">
            <label for="search" class="mr-2">Search:</label>
            <input type="text" 
                   id="search" 
                   name="search" 
                   class="form-control" 
                   placeholder="Medicine name, content, HSN code..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <!-- Brand Filter -->
          <div class="form-group mr-3 mb-2">
            <label for="brand" class="mr-2">Manufacturer:</label>
            <select id="brand" name="brand" class="form-control">
              <option value="">All</option>
              <?php 
              $brand_result = $connect->query("SELECT brand_id, brand_name FROM brands WHERE brand_status = 1 ORDER BY brand_name");
              while ($b = $brand_result->fetch_assoc()) {
                $selected = ($filter_brand == $b['brand_id']) ? 'selected' : '';
                echo "<option value='{$b['brand_id']}' {$selected}>{$b['brand_name']}</option>";
              }
              ?>
            </select>
          </div>

          <!-- Category Filter -->
          <div class="form-group mr-3 mb-2">
            <label for="category" class="mr-2">Category:</label>
            <select id="category" name="category" class="form-control">
              <option value="">All</option>
              <?php 
              $cat_result = $connect->query("SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 ORDER BY categories_name");
              while ($cat = $cat_result->fetch_assoc()) {
                $selected = ($filter_category == $cat['categories_id']) ? 'selected' : '';
                echo "<option value='{$cat['categories_id']}' {$selected}>{$cat['categories_name']}</option>";
              }
              ?>
            </select>
          </div>

          <!-- Stock Status Filter -->
          <div class="form-group mr-3 mb-2">
            <label for="stock_status" class="mr-2">Stock Status:</label>
            <select id="stock_status" name="stock_status" class="form-control">
              <option value="">All</option>
              <option value="IN_STOCK" <?php echo ($filter_status == 'IN_STOCK') ? 'selected' : ''; ?>>In Stock</option>
              <option value="LOW_STOCK" <?php echo ($filter_status == 'LOW_STOCK') ? 'selected' : ''; ?>>Low Stock</option>
              <option value="OUT_OF_STOCK" <?php echo ($filter_status == 'OUT_OF_STOCK') ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
          </div>

          <!-- Buttons -->
          <button type="submit" class="btn btn-primary mr-2">
            <i class="fa fa-search"></i> Search
          </button>
          <a href="manage_medicine.php" class="btn btn-secondary mr-2">
            <i class="fa fa-times"></i> Reset
          </a>
        </form>
      </div>
    </div>

    <!-- ============================================================
         MEDICINES TABLE
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h5 class="mb-0">Medicines List (<?php echo $result->num_rows; ?>)</h5>
          </div>
          <div class="col-md-6 text-right">
            <a href="add_medicine.php" class="btn btn-primary btn-sm">
              <i class="fa fa-plus"></i> Add Medicine
            </a>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table id="medicinesTable" class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Medicine Name</th>
                <th style="width: 12%;">Composition</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 10%;">Stock</th>
                <th style="width: 10%;">Batches</th>
                <th style="width: 12%;">Nearest Expiry</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 13%;">Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  // Determine status badge
                  if ($row['stock_status'] == 'OUT_OF_STOCK') {
                    $stock_badge = '<span class="badge badge-danger">OUT OF STOCK</span>';
                    $row_class = 'table-danger';
                  } elseif ($row['stock_status'] == 'LOW_STOCK') {
                    $stock_badge = '<span class="badge badge-warning">LOW STOCK</span>';
                    $row_class = 'table-warning';
                  } else {
                    $stock_badge = '<span class="badge badge-success">IN STOCK</span>';
                    $row_class = '';
                  }

                  // Determine expiry status
                  if ($row['expiry_status'] == 'NO_BATCH') {
                    $expiry_display = '<span class="badge badge-secondary">No Batch</span>';
                  } elseif ($row['expiry_status'] == 'EXPIRED') {
                    $expiry_display = '<span class="badge badge-danger">Expired</span>';
                  } elseif ($row['expiry_status'] == 'CRITICAL') {
                    $expiry_display = '<span class="badge badge-danger">Critical</span><br><small>' . date('d-M-Y', strtotime($row['nearest_expiry'])) . '</small>';
                  } elseif ($row['expiry_status'] == 'WARNING') {
                    $expiry_display = '<span class="badge badge-warning">Warning</span><br><small>' . date('d-M-Y', strtotime($row['nearest_expiry'])) . '</small>';
                  } else {
                    $expiry_display = date('d-M-Y', strtotime($row['nearest_expiry']));
                  }
              ?>
              <tr class="<?php echo $row_class; ?>">
                <td class="text-center"><strong><?php echo $row['product_id']; ?></strong></td>
                
                <td>
                  <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                  <br>
                  <small class="text-muted"><?php echo htmlspecialchars($row['brand_name']) . ' | ' . htmlspecialchars($row['categories_name']); ?></small>
                </td>

                <td>
                  <small><?php echo htmlspecialchars($row['content']); ?></small>
                </td>

                <td>
                  <small><?php echo $row['product_type'] . '<br>' . $row['unit_type'] . '<br>' . $row['pack_size']; ?></small>
                </td>

                <td class="text-center">
                  <strong><?php echo number_format($row['total_stock']); ?></strong>
                  <br>
                  <small class="text-muted">Active: <?php echo number_format($row['active_stock']); ?></small>
                  <?php if ($row['expired_stock'] > 0): ?>
                    <br>
                    <small class="text-danger">Expired: <?php echo number_format($row['expired_stock']); ?></small>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <span class="badge badge-info"><?php echo $row['batch_count']; ?> Active</span>
                </td>

                <td class="text-center">
                  <?php echo $expiry_display; ?>
                </td>

                <td class="text-center">
                  <?php echo $stock_badge; ?>
                </td>

                <td class="text-center">
                  <div class="btn-group" role="group">
                    <a href="editproduct.php?id=<?php echo $row['product_id']; ?>"
                       class="btn btn-sm btn-primary"
                       title="Edit Medicine"
                       data-toggle="tooltip">
                      <i class="fa fa-pencil"></i>
                    </a>

                    <a href="viewStock.php?id=<?php echo $row['product_id']; ?>"
                       class="btn btn-sm btn-info"
                       title="View Batches"
                       data-toggle="tooltip">
                      <i class="fa fa-cubes"></i>
                    </a>

                    <a href="php_action/manageBatch.php?product_id=<?php echo $row['product_id']; ?>"
                       class="btn btn-sm btn-warning"
                       title="Manage Batches"
                       data-toggle="tooltip">
                      <i class="fa fa-boxes"></i>
                    </a>

                    <a href="php_action/deleteProduct.php?id=<?php echo $row['product_id']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure?');"
                       title="Delete Medicine"
                       data-toggle="tooltip">
                      <i class="fa fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php 
                }
              } else {
              ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-4">
                  <i class="fa fa-inbox fa-3x mb-3"></i>
                  <p>No medicines found</p>
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
  .border-left-primary {
    border-left: 4px solid #007bff;
  }
  .border-left-success {
    border-left: 4px solid #28a745;
  }
  .border-left-warning {
    border-left: 4px solid #ffc107;
  }
  .border-left-danger {
    border-left: 4px solid #dc3545;
  }
  .table-hover tbody tr:hover {
    background-color: #f5f5f5;
  }
</style>

<script>
  $(document).ready(function() {
    $('#medicinesTable').DataTable({
      "pageLength": 25,
      "order": [[1, "asc"]],
      "columnDefs": [
        {"orderable": false, "targets": [8]}
      ]
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>

<?php include('./constant/layout/footer.php'); ?>
