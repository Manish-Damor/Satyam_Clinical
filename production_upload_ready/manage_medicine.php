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
$filter_expiry = isset($_GET['expiry_status']) ? $_GET['expiry_status'] : '';
$filter_expired_qty = isset($_GET['expired_qty']) ? (int) $_GET['expired_qty'] : 0;
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
SELECT *
FROM (
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
      WHEN COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) = 0 THEN 'OUT_OF_STOCK'
      WHEN COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) <= p.reorder_level THEN 'LOW_STOCK'
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
) inv
";

$hasOuterFilter = false;

if (in_array($filter_status, ['IN_STOCK', 'LOW_STOCK', 'OUT_OF_STOCK'], true)) {
    $filter_status_escaped = $connect->real_escape_string($filter_status);
    $sql .= " WHERE inv.stock_status = '{$filter_status_escaped}'";
    $hasOuterFilter = true;
}

if (in_array($filter_expiry, ['NO_BATCH', 'EXPIRED', 'CRITICAL', 'WARNING', 'OK'], true)) {
  $filter_expiry_escaped = $connect->real_escape_string($filter_expiry);
  $sql .= $hasOuterFilter
    ? " AND inv.expiry_status = '{$filter_expiry_escaped}'"
    : " WHERE inv.expiry_status = '{$filter_expiry_escaped}'";
  $hasOuterFilter = true;
}

if ($filter_expired_qty === 1) {
  $sql .= $hasOuterFilter
    ? " AND inv.expired_stock > 0"
    : " WHERE inv.expired_stock > 0";
  $hasOuterFilter = true;
}

$sql .= " ORDER BY inv.product_name ASC";

$result = $connect->query($sql);

if (!$result) {
    die("Query Error: " . $connect->error);
}

// Get statistics
$stats_sql = "
SELECT
  COUNT(*) AS total_medicines,
  COALESCE(SUM(ps.active_stock), 0) AS total_stock_units,
  SUM(CASE WHEN ps.active_stock = 0 THEN 1 ELSE 0 END) AS out_of_stock_items,
  SUM(CASE WHEN ps.active_stock > 0 AND ps.active_stock <= ps.reorder_level THEN 1 ELSE 0 END) AS low_stock_items
FROM (
  SELECT
    p.product_id,
    p.reorder_level,
    COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS active_stock
  FROM product p
  LEFT JOIN product_batches pb ON pb.product_id = p.product_id
  WHERE p.status = 1
  GROUP BY p.product_id, p.reorder_level
) ps
";

$stats_result = $connect->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$isQuickLowStock = ($filter_status === 'LOW_STOCK' && $filter_expiry === '');
$isQuickCriticalExpiry = ($filter_expiry === 'CRITICAL');
$isQuickOutOfStock = ($filter_status === 'OUT_OF_STOCK' && $filter_expiry === '');
$isQuickExpiredQty = ($filter_expired_qty === 1);

$contextParams = [];
if ($search !== '') {
  $contextParams['search'] = $search;
}
if ($filter_brand > 0) {
  $contextParams['brand'] = $filter_brand;
}
if ($filter_category > 0) {
  $contextParams['category'] = $filter_category;
}

$buildManageMedicineUrl = function (array $override) use ($contextParams) {
  $params = array_merge($contextParams, $override);

  if (isset($params['stock_status']) && $params['stock_status'] === '') {
    unset($params['stock_status']);
  }
  if (isset($params['expiry_status']) && $params['expiry_status'] === '') {
    unset($params['expiry_status']);
  }
  if (isset($params['expired_qty']) && (int) $params['expired_qty'] !== 1) {
    unset($params['expired_qty']);
  }

  $query = http_build_query($params);
  return 'manage_medicine.php' . ($query !== '' ? ('?' . $query) : '');
};

$urlLowStock = $buildManageMedicineUrl([
  'stock_status' => 'LOW_STOCK',
  'expiry_status' => '',
  'expired_qty' => 0
]);

$urlOutOfStock = $buildManageMedicineUrl([
  'stock_status' => 'OUT_OF_STOCK',
  'expiry_status' => '',
  'expired_qty' => 0
]);

$urlCriticalExpiry = $buildManageMedicineUrl([
  'stock_status' => '',
  'expiry_status' => 'CRITICAL',
  'expired_qty' => 0
]);

$urlExpiredQty = $buildManageMedicineUrl([
  'stock_status' => '',
  'expiry_status' => '',
  'expired_qty' => 1
]);

$urlClearQuickFilters = $buildManageMedicineUrl([
  'stock_status' => '',
  'expiry_status' => '',
  'expired_qty' => 0
]);

$value_sql = "
SELECT
  COALESCE(SUM(CASE WHEN status = 'Active' THEN available_quantity * purchase_rate ELSE 0 END), 0) AS stock_cost_value,
  COALESCE(SUM(CASE WHEN status = 'Active' THEN available_quantity * mrp ELSE 0 END), 0) AS stock_mrp_value,
  SUM(CASE WHEN status = 'Active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_batches_30,
  SUM(CASE WHEN status = 'Expired' AND available_quantity > 0 THEN 1 ELSE 0 END) AS expired_batches_with_stock
FROM product_batches
";

$value_result = $connect->query($value_sql);
$value_stats = $value_result ? $value_result->fetch_assoc() : [
  'stock_cost_value' => 0,
  'stock_mrp_value' => 0,
  'expiring_batches_30' => 0,
  'expired_batches_with_stock' => 0
];
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

    <div class="alert alert-info mb-3">
      <strong>Process Control:</strong> This page is <strong>read-only</strong> for inventory. Stock increases only via <a href="purchase_invoice.php">Purchase Invoice</a> and decreases only via <a href="sales_invoice_form.php">Sales Invoice</a>.
    </div>

    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="d-flex align-items-center flex-wrap quick-filter-wrap">
          <span class="mr-2 font-weight-bold text-muted">Quick Filters:</span>
          <a href="<?php echo htmlspecialchars($urlLowStock); ?>" class="btn btn-sm mr-2 mb-1 <?php echo $isQuickLowStock ? 'btn-warning' : 'btn-outline-warning'; ?>">Low Stock</a>
          <a href="<?php echo htmlspecialchars($urlCriticalExpiry); ?>" class="btn btn-sm mr-2 mb-1 <?php echo $isQuickCriticalExpiry ? 'btn-danger' : 'btn-outline-danger'; ?>">Critical Expiry (≤30D)</a>
          <a href="<?php echo htmlspecialchars($urlOutOfStock); ?>" class="btn btn-sm mr-2 mb-1 <?php echo $isQuickOutOfStock ? 'btn-danger' : 'btn-outline-danger'; ?>">Out of Stock</a>
          <a href="<?php echo htmlspecialchars($urlExpiredQty); ?>" class="btn btn-sm mr-2 mb-1 <?php echo $isQuickExpiredQty ? 'btn-danger' : 'btn-outline-danger'; ?>">Expired Batches With Qty</a>
          <a href="<?php echo htmlspecialchars($urlClearQuickFilters); ?>" class="btn btn-sm btn-outline-secondary mb-1">Clear Quick Filters</a>
        </div>
      </div>
    </div>

    <!-- ============================================================
         STATISTICS DASHBOARD
         ============================================================ -->
    <div class="row kpi-grid">
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-primary shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Total Medicines
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($stats['total_medicines']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-success shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Total Stock Units
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($stats['total_stock_units']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Low Stock Items
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($stats['low_stock_items']); ?></strong>
            </div>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($urlLowStock); ?>" class="btn btn-sm btn-outline-warning">View</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-danger shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Out of Stock
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($stats['out_of_stock_items']); ?></strong>
            </div>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($urlOutOfStock); ?>" class="btn btn-sm btn-outline-danger">View</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-info shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Stock @ Cost Value
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong>₹<?php echo number_format((float)$value_stats['stock_cost_value'], 2); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-secondary shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Stock @ MRP Value
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong>₹<?php echo number_format((float)$value_stats['stock_mrp_value'], 2); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-warning shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Batches Expiring ≤30D
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($value_stats['expiring_batches_30']); ?></strong>
            </div>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($urlCriticalExpiry); ?>" class="btn btn-sm btn-outline-warning">View</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-left-danger shadow">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="kpi-label text-dark font-weight-bold text-uppercase mb-1">
              Expired Batches With Qty
            </div>
            <div class="h3 mb-0 kpi-number">
              <strong><?php echo intval($value_stats['expired_batches_with_stock']); ?></strong>
            </div>
            <div class="mt-2">
              <a href="<?php echo htmlspecialchars($urlExpiredQty); ?>" class="btn btn-sm <?php echo $isQuickExpiredQty ? 'btn-danger' : 'btn-outline-danger'; ?>">View</a>
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
        <form method="GET" class="form-inline filters-form">
          <input type="hidden" name="expired_qty" value="<?php echo $filter_expired_qty === 1 ? 1 : 0; ?>">
          
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

          <!-- Expiry Status Filter -->
          <div class="form-group mr-3 mb-2">
            <label for="expiry_status" class="mr-2">Expiry:</label>
            <select id="expiry_status" name="expiry_status" class="form-control">
              <option value="">All</option>
              <option value="CRITICAL" <?php echo ($filter_expiry == 'CRITICAL') ? 'selected' : ''; ?>>Critical (≤30D)</option>
              <option value="WARNING" <?php echo ($filter_expiry == 'WARNING') ? 'selected' : ''; ?>>Warning (31-90D)</option>
              <option value="EXPIRED" <?php echo ($filter_expiry == 'EXPIRED') ? 'selected' : ''; ?>>Expired</option>
              <option value="OK" <?php echo ($filter_expiry == 'OK') ? 'selected' : ''; ?>>OK</option>
              <option value="NO_BATCH" <?php echo ($filter_expiry == 'NO_BATCH') ? 'selected' : ''; ?>>No Batch</option>
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
        <div class="mb-2 filter-chip-wrap">
          <?php if ($filter_status !== ''): ?>
            <span class="badge badge-primary mr-1">Stock: <?php echo htmlspecialchars(str_replace('_', ' ', $filter_status)); ?></span>
          <?php endif; ?>
          <?php if ($filter_expiry !== ''): ?>
            <span class="badge badge-danger mr-1">Expiry: <?php echo htmlspecialchars($filter_expiry); ?></span>
          <?php endif; ?>
          <?php if ($search !== ''): ?>
            <span class="badge badge-info mr-1">Search: <?php echo htmlspecialchars($search); ?></span>
          <?php endif; ?>
          <?php if ($filter_expired_qty === 1): ?>
            <span class="badge badge-danger mr-1">Expired Stock: Yes</span>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table id="medicinesTable" class="table table-bordered table-striped table-hover">
            <thead class="medicine-table-head">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 18%;">Medicine Name</th>
                <th style="width: 12%;">Composition</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 10%;">Stock</th>
                <th style="width: 10%;">Batches</th>
                <th style="width: 8%;">Reorder</th>
                <th style="width: 8%;">MRP</th>
                <th style="width: 10%;">Nearest Expiry</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 11%;">Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $isHighRisk = (
                    in_array($row['stock_status'], ['LOW_STOCK', 'OUT_OF_STOCK'], true)
                    && in_array($row['expiry_status'], ['CRITICAL', 'EXPIRED'], true)
                  );

                  // Determine status badge
                  if ($isHighRisk) {
                    $stock_badge = '<span class="badge badge-danger">HIGH RISK</span>';
                    $row_class = 'row-risk-high';
                  } elseif ($row['stock_status'] == 'OUT_OF_STOCK') {
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
                <td class="text-center number-cell"><strong><?php echo $row['product_id']; ?></strong></td>
                
                <td>
                  <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                  <?php if ($isHighRisk): ?>
                    <span class="badge badge-danger ml-1">Priority</span>
                  <?php endif; ?>
                  <br>
                  <small class="text-muted"><?php echo htmlspecialchars($row['brand_name']) . ' | ' . htmlspecialchars($row['categories_name']); ?></small>
                </td>

                <td>
                  <small><?php echo htmlspecialchars($row['content']); ?></small>
                </td>

                <td>
                  <small><?php echo htmlspecialchars($row['product_type']) . '<br>' . htmlspecialchars($row['unit_type']) . '<br>' . htmlspecialchars($row['pack_size']); ?></small>
                </td>

                <td class="text-center number-cell">
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

                <td class="text-center number-cell">
                  <strong><?php echo intval($row['reorder_level']); ?></strong>
                </td>

                <td class="text-right number-cell">
                  <?php if (!empty($row['current_mrp']) && (float)$row['current_mrp'] > 0): ?>
                    ₹<?php echo number_format((float)$row['current_mrp'], 2); ?>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <?php echo $expiry_display; ?>
                </td>

                <td class="text-center">
                  <?php echo $stock_badge; ?>
                </td>

                <td class="text-center">
                  <div class="btn-group" role="group">
                    <a href="viewStock.php?id=<?php echo $row['product_id']; ?>"
                       class="btn btn-sm btn-info"
                       title="View Batches"
                       data-toggle="tooltip">
                      <i class="fa fa-cubes"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php 
                }
              } else {
              ?>
              <tr>
                <td colspan="11" class="text-center text-muted py-4">
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
  .border-left-info {
    border-left: 4px solid #17a2b8;
  }
  .border-left-secondary {
    border-left: 4px solid #6c757d;
  }
  .kpi-grid .card {
    min-height: 120px;
  }
  .kpi-label {
    font-size: 12px;
    letter-spacing: .4px;
  }
  .kpi-number {
    color: #212529;
    font-size: 1.85rem;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
  }
  .filters-form {
    align-items: flex-end;
  }
  .filters-form .form-group {
    margin-bottom: .6rem;
  }
  #medicinesTable th {
    vertical-align: middle;
    text-align: center;
  }
  #medicinesTable.medicine-table,
  #medicinesTable {
    border-color: #d9e2ec;
  }
  .medicine-table-head th {
    background: linear-gradient(180deg, #2f3d4a 0%, #26323d 100%);
    color: #ffffff;
    border-color: #3b4a59;
    font-weight: 600;
    letter-spacing: .2px;
  }
  #medicinesTable.table-striped tbody tr:nth-of-type(odd) {
    background-color: #fafbfd;
  }
  .number-cell {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
  }
  .row-risk-high td {
    background: #fff1f1 !important;
    border-top: 1px solid #f3b3b3;
    border-bottom: 1px solid #f3b3b3;
  }
  .row-risk-high td:first-child {
    border-left: 4px solid #dc3545;
  }
  .table-hover tbody tr:hover {
    background-color: #f5f5f5;
  }
  .quick-filter-wrap .btn {
    min-width: 140px;
  }
  .filter-chip-wrap .badge {
    font-size: 12px;
    padding: .45em .65em;
  }
</style>

<script>
  $(document).ready(function() {
    $('#medicinesTable').DataTable({
      "pageLength": 25,
      "order": [[1, "asc"]],
      "columnDefs": [
        {"orderable": false, "targets": [10]}
      ]
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>

<?php include('./constant/layout/footer.php'); ?>
