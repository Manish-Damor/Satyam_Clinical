<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// ============================================================
// SUPPLIER MANAGEMENT - PROFESSIONAL ERP SYSTEM
// ============================================================

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where = "1=1";

if (!empty($search)) {
    $search_escaped = $connect->real_escape_string($search);
    $where .= " AND (s.supplier_name LIKE '%{$search_escaped}%' 
                     OR s.supplier_code LIKE '%{$search_escaped}%'
                     OR s.phone LIKE '%{$search_escaped}%'
                     OR s.gst_number LIKE '%{$search_escaped}%')";
}

if (!empty($filter_status)) {
    $status_escaped = $connect->real_escape_string($filter_status);
    $where .= " AND s.supplier_status = '{$status_escaped}'";
}

// Get suppliers list
$sql = "
SELECT 
  s.supplier_id,
  s.supplier_name,
  s.supplier_code,
  s.company_name,
  s.contact_person,
  s.phone,
  s.email,
  s.address,
  s.city,
  s.state,
  s.gst_number,
  s.credit_days,
  s.supplier_status,
  s.is_verified,
  s.created_at,
  
  COUNT(DISTINCT po.po_id) AS total_pos,
  COALESCE(SUM(po.grand_total), 0) AS total_purchase_amount,
  COUNT(DISTINCT pb.batch_id) AS supplied_batches
  
FROM suppliers s
LEFT JOIN purchase_orders po ON po.supplier_id = s.supplier_id
LEFT JOIN product_batches pb ON pb.supplier_id = s.supplier_id
WHERE {$where}
GROUP BY s.supplier_id
ORDER BY s.created_at DESC
";

$result = $connect->query($sql);

if (!$result) {
    die("Query Error: " . $connect->error);
}

// Get statistics
$stats_sql = "
SELECT 
  COUNT(*) AS total_suppliers,
  COUNT(CASE WHEN supplier_status = 'Active' THEN supplier_id END) AS active_suppliers,
  COUNT(CASE WHEN is_verified = 1 THEN supplier_id END) AS verified_suppliers
FROM suppliers
";

$stats_result = $connect->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="page-wrapper">

  <!-- PAGE HEADER -->
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary"><i class="fa fa-building"></i> Supplier Management</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Suppliers</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">

    <!-- ============================================================
         STATISTICS DASHBOARD
         ============================================================ -->
    <div class="row">
      <div class="col-lg-4 col-md-6">
        <div class="card border-left-primary shadow">
          <div class="card-body">
            <div class="text-primary font-weight-bold text-uppercase mb-1">
              Total Suppliers
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['total_suppliers']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="card border-left-success shadow">
          <div class="card-body">
            <div class="text-success font-weight-bold text-uppercase mb-1">
              Active Suppliers
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['active_suppliers']); ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="card border-left-info shadow">
          <div class="card-body">
            <div class="text-info font-weight-bold text-uppercase mb-1">
              Verified Suppliers
            </div>
            <div class="h3 mb-0">
              <strong><?php echo intval($stats['verified_suppliers']); ?></strong>
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
                   placeholder="Supplier name, code, phone, GST..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <!-- Status Filter -->
          <div class="form-group mr-3 mb-2">
            <label for="status" class="mr-2">Status:</label>
            <select id="status" name="status" class="form-control">
              <option value="">All</option>
              <option value="Active" <?php echo ($filter_status == 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo ($filter_status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
              <option value="Blocked" <?php echo ($filter_status == 'Blocked') ? 'selected' : ''; ?>>Blocked</option>
            </select>
          </div>

          <!-- Buttons -->
          <button type="submit" class="btn btn-primary mr-2">
            <i class="fa fa-search"></i> Search
          </button>
          <a href="manage_suppliers.php" class="btn btn-secondary mr-2">
            <i class="fa fa-times"></i> Reset
          </a>
        </form>
      </div>
    </div>

    <!-- ============================================================
         SUPPLIERS TABLE
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h5 class="mb-0">Suppliers List (<?php echo $result->num_rows; ?>)</h5>
          </div>
          <div class="col-md-6 text-right">
            <a href="add_supplier.php" class="btn btn-primary btn-sm">
              <i class="fa fa-plus"></i> Add Supplier
            </a>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table id="suppliersTable" class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Supplier Name</th>
                <th style="width: 15%;">Contact</th>
                <th style="width: 15%;">Location</th>
                <th style="width: 10%;">GST/TAN</th>
                <th style="width: 8%;">POs</th>
                <th style="width: 8%;">Batches</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 16%;">Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  // Determine status badge
                  if ($row['supplier_status'] == 'Active') {
                    $status_badge = '<span class="badge badge-success">Active</span>';
                  } elseif ($row['supplier_status'] == 'Inactive') {
                    $status_badge = '<span class="badge badge-secondary">Inactive</span>';
                  } else {
                    $status_badge = '<span class="badge badge-danger">Blocked</span>';
                  }

                  // Verification badge
                  $verified_badge = $row['is_verified'] ? '<span class="badge badge-info">Verified</span>' : '<span class="badge badge-warning">Unverified</span>';
              ?>
              <tr>
                <td class="text-center"><strong><?php echo $row['supplier_id']; ?></strong></td>
                
                <td>
                  <strong><?php echo htmlspecialchars($row['supplier_name']); ?></strong>
                  <br>
                  <small class="text-muted"><?php echo htmlspecialchars($row['supplier_code']); ?></small>
                  <?php if ($row['company_name']): ?>
                    <br>
                    <small><?php echo htmlspecialchars($row['company_name']); ?></small>
                  <?php endif; ?>
                </td>

                <td>
                  <small>
                    <strong><?php echo htmlspecialchars($row['contact_person']); ?></strong>
                    <br>
                    <i class="fa fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?>
                    <?php if ($row['email']): ?>
                      <br>
                      <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                    <?php endif; ?>
                  </small>
                </td>

                <td>
                  <small>
                    <?php echo htmlspecialchars($row['city']); ?>, 
                    <?php echo htmlspecialchars($row['state']); ?>
                    <br>
                    <small class="text-muted"><?php echo htmlspecialchars($row['address']); ?></small>
                  </small>
                </td>

                <td>
                  <small>
                    <?php echo $row['gst_number'] ? htmlspecialchars($row['gst_number']) : 'N/A'; ?>
                  </small>
                </td>

                <td class="text-center">
                  <strong><?php echo number_format($row['total_pos']); ?></strong>
                  <br>
                  <small class="text-muted">₹<?php echo number_format($row['total_purchase_amount'], 2); ?></small>
                </td>

                <td class="text-center">
                  <span class="badge badge-info"><?php echo number_format($row['supplied_batches']); ?></span>
                </td>

                <td class="text-center">
                  <?php echo $status_badge; ?>
                  <br>
                  <?php echo $verified_badge; ?>
                </td>

                <td class="text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="edit_supplier.php?id=<?php echo $row['supplier_id']; ?>"
                       class="btn btn-primary"
                       title="Edit Supplier"
                       data-toggle="tooltip">
                      <i class="fa fa-pencil"></i>
                    </a>

                    <a href="view_supplier_po.php?id=<?php echo $row['supplier_id']; ?>"
                       class="btn btn-info"
                       title="View Purchase Orders"
                       data-toggle="tooltip">
                      <i class="fa fa-file"></i>
                    </a>

                    <a href="php_action/deleteSupplier.php?id=<?php echo $row['supplier_id']; ?>"
                       class="btn btn-danger"
                       onclick="return confirm('Are you sure?');"
                       title="Delete Supplier"
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
                  <p>No suppliers found</p>
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
  .border-left-info {
    border-left: 4px solid #17a2b8;
  }
  .table-hover tbody tr:hover {
    background-color: #f5f5f5;
  }
</style>

<script>
  $(document).ready(function() {
    $('#suppliersTable').DataTable({
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
