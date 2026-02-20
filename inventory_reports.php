<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php'); ?>

<?php
// ============================================================
// INVENTORY REPORTS - PROFESSIONAL ERP SYSTEM
// ============================================================

// Get report type
$report_type = isset($_GET['type']) ? $_GET['type'] : 'inventory_summary';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

?>

<div class="page-wrapper">

  <!-- PAGE HEADER -->
  <div class="row page-titles">
    <div class="col-md-5 align-self-center">
      <h3 class="text-primary"><i class="fa fa-bar-chart"></i> Inventory Reports</h3>
    </div>
    <div class="col-md-7 align-self-center">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
        <li class="breadcrumb-item active">Reports</li>
      </ol>
    </div>
  </div>

  <div class="container-fluid">

    <!-- ============================================================
         REPORT TYPE SELECTOR
         ============================================================ -->
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">Select Report Type</h5>
      </div>
      <div class="card-body">
        <form method="GET" class="form-inline">
          
          <div class="form-group mr-3 mb-2">
            <label for="type" class="mr-2">Report Type:</label>
            <select id="type" name="type" class="form-control" onchange="this.form.submit()">
              <option value="inventory_summary" <?php echo ($report_type == 'inventory_summary') ? 'selected' : ''; ?>>
                Inventory Summary
              </option>
              <option value="low_stock" <?php echo ($report_type == 'low_stock') ? 'selected' : ''; ?>>
                Low Stock Alert
              </option>
              <option value="expiry_tracking" <?php echo ($report_type == 'expiry_tracking') ? 'selected' : ''; ?>>
                Expiry Tracking
              </option>
              <option value="stock_movements" <?php echo ($report_type == 'stock_movements') ? 'selected' : ''; ?>>
                Stock Movements
              </option>
              <option value="batch_analysis" <?php echo ($report_type == 'batch_analysis') ? 'selected' : ''; ?>>
                Batch Analysis
              </option>
              <option value="supplier_performance" <?php echo ($report_type == 'supplier_performance') ? 'selected' : ''; ?>>
                Supplier Performance
              </option>
            </select>
          </div>

          <?php if ($report_type == 'stock_movements'): ?>
          <div class="form-group mr-3 mb-2">
            <label for="date_from" class="mr-2">From:</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
          </div>

          <div class="form-group mr-3 mb-2">
            <label for="date_to" class="mr-2">To:</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary">
            <i class="fa fa-refresh"></i> Generate
          </button>
        </form>
      </div>
    </div>

    <!-- ============================================================
         REPORT CONTENT
         ============================================================ -->
    <div class="card mt-4">
      <div class="card-header bg-light">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h5 class="mb-0">
              <?php 
              $report_titles = array(
                'inventory_summary' => 'Inventory Summary Report',
                'low_stock' => 'Low Stock Alert Report',
                'expiry_tracking' => 'Expiry Tracking Report',
                'stock_movements' => 'Stock Movements Report',
                'batch_analysis' => 'Batch Analysis Report',
                'supplier_performance' => 'Supplier Performance Report'
              );
              echo $report_titles[$report_type] ?? 'Report';
              ?>
            </h5>
          </div>
          <div class="col-md-4 text-right">
            <button class="btn btn-sm btn-success" onclick="window.print()">
              <i class="fa fa-print"></i> Print
            </button>
            <button class="btn btn-sm btn-info" onclick="exportToCSV()">
              <i class="fa fa-download"></i> Export
            </button>
          </div>
        </div>
      </div>
      <div class="card-body">

        <?php 
        // ========== INVENTORY SUMMARY REPORT ==========
        if ($report_type == 'inventory_summary'):
          $sql = "
          SELECT 
            p.product_id,
            p.product_name,
            p.content,
            p.pack_size,
            b.brand_name,
            c.categories_name,
            COALESCE(SUM(pb.available_quantity), 0) AS total_stock,
            COUNT(DISTINCT pb.batch_id) AS batch_count,
            MIN(pb.expiry_date) AS expiry,
            p.reorder_level,
            COALESCE(SUM(pb.mrp * pb.available_quantity), 0) AS stock_value
          FROM product p
          LEFT JOIN brands b ON b.brand_id = p.brand_id
          LEFT JOIN categories c ON c.categories_id = p.categories_id
          LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
          WHERE p.status = 1
          GROUP BY p.product_id
          ORDER BY p.product_name ASC
          ";
          
          $result = $connect->query($sql);
          $total_value = 0;
        ?>

        <div class="table-responsive">
          <table id="reportTable" class="table table-bordered table-sm">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Medicine Name</th>
                <th style="width: 15%;">Manufacturer</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 8%;">Total Stock</th>
                <th style="width: 8%;">Reorder Level</th>
                <th style="width: 8%;">Batches</th>
                <th style="width: 10%;">Stock Value (₹)</th>
                <th style="width: 8%;">Nearest Expiry</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              while ($row = $result->fetch_assoc()) {
                $total_value += $row['stock_value'];
              ?>
              <tr>
                <td><?php echo $row['product_id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                <td><?php echo htmlspecialchars($row['categories_name']); ?></td>
                <td class="text-center"><strong><?php echo number_format($row['total_stock']); ?></strong></td>
                <td class="text-center"><?php echo number_format($row['reorder_level']); ?></td>
                <td class="text-center"><?php echo $row['batch_count']; ?></td>
                <td class="text-right">₹<?php echo number_format($row['stock_value'], 2); ?></td>
                <td class="text-center"><?php echo $row['expiry'] ? date('d-M-Y', strtotime($row['expiry'])) : 'N/A'; ?></td>
              </tr>
              <?php } ?>
            </tbody>
            <tfoot class="table-dark">
              <tr>
                <td colspan="7" class="text-right"><strong>Total Stock Value:</strong></td>
                <td class="text-right"><strong>₹<?php echo number_format($total_value, 2); ?></strong></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <?php 
        // ========== LOW STOCK ALERT REPORT ==========
        elseif ($report_type == 'low_stock'):
          $sql = "
          SELECT 
            p.product_id,
            p.product_name,
            b.brand_name,
            p.reorder_level,
            COALESCE(SUM(pb.available_quantity), 0) AS current_stock,
            (p.reorder_level - COALESCE(SUM(pb.available_quantity), 0)) AS quantity_needed,
            s.supplier_name
          FROM product p
          LEFT JOIN brands b ON b.brand_id = p.brand_id
          LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
          LEFT JOIN reorder_management rm ON rm.product_id = p.product_id
          LEFT JOIN suppliers s ON s.supplier_id = rm.preferred_supplier_id
          WHERE p.status = 1
          GROUP BY p.product_id
          HAVING current_stock <= p.reorder_level
          ORDER BY current_stock ASC
          ";
          
          $result = $connect->query($sql);
        ?>

        <div class="table-responsive">
          <table id="reportTable" class="table table-bordered table-sm">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Medicine Name</th>
                <th style="width: 15%;">Manufacturer</th>
                <th style="width: 10%;">Reorder Level</th>
                <th style="width: 10%;">Current Stock</th>
                <th style="width: 10%;">Need to Order</th>
                <th style="width: 15%;">Supplier</th>
                <th style="width: 15%;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $status = $row['current_stock'] <= 0 ? 'URGENT' : 'WARNING';
                  $badge = $row['current_stock'] <= 0 ? '<span class="badge badge-danger">URGENT</span>' : '<span class="badge badge-warning">LOW</span>';
              ?>
              <tr>
                <td><?php echo $row['product_id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                <td class="text-center"><?php echo number_format($row['reorder_level']); ?></td>
                <td class="text-center"><strong><?php echo number_format($row['current_stock']); ?></strong></td>
                <td class="text-center text-danger"><strong><?php echo number_format($row['quantity_needed']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                <td class="text-center"><?php echo $badge; ?></td>
              </tr>
              <?php 
                }
              } else {
              ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  All medicines are in stock.
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php 
        // ========== EXPIRY TRACKING REPORT ==========
        elseif ($report_type == 'expiry_tracking'):
          $sql = "
          SELECT 
            pb.batch_id,
            p.product_id,
            p.product_name,
            b.brand_name,
            pb.batch_number,
            pb.expiry_date,
            DATEDIFF(pb.expiry_date, CURDATE()) AS days_remaining,
            pb.available_quantity,
            pb.mrp,
            CASE 
              WHEN pb.expiry_date < CURDATE() THEN 'EXPIRED'
              WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
              WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 90 THEN 'WARNING'
              ELSE 'OK'
            END AS alert_status
          FROM product_batches pb
          JOIN product p ON p.product_id = pb.product_id
          LEFT JOIN brands b ON b.brand_id = p.brand_id
          WHERE pb.status IN ('Active', 'Expired')
          ORDER BY pb.expiry_date ASC
          ";
          
          $result = $connect->query($sql);
        ?>

        <div class="table-responsive">
          <table id="reportTable" class="table table-bordered table-sm">
            <thead class="table-dark">
              <tr>
                <th style="width: 5%;">Batch ID</th>
                <th style="width: 15%;">Medicine</th>
                <th style="width: 15%;">Manufacturer</th>
                <th style="width: 15%;">Batch Number</th>
                <th style="width: 12%;">Expiry Date</th>
                <th style="width: 10%;">Days Left</th>
                <th style="width: 8%;">Quantity</th>
                <th style="width: 10%;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              while ($row = $result->fetch_assoc()) {
                if ($row['days_remaining'] < 0) {
                  $badge = '<span class="badge badge-danger">EXPIRED</span>';
                } elseif ($row['days_remaining'] <= 30) {
                  $badge = '<span class="badge badge-danger">CRITICAL</span>';
                } elseif ($row['days_remaining'] <= 90) {
                  $badge = '<span class="badge badge-warning">WARNING</span>';
                } else {
                  $badge = '<span class="badge badge-success">OK</span>';
                }
              ?>
              <tr>
                <td><?php echo $row['batch_id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                <td class="text-center"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></td>
                <td class="text-center"><strong><?php echo max(0, $row['days_remaining']); ?></strong></td>
                <td class="text-center"><?php echo number_format($row['available_quantity']); ?></td>
                <td class="text-center"><?php echo $badge; ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php 
        // ========== BATCH ANALYSIS REPORT ==========
        elseif ($report_type == 'batch_analysis'):
          $sql = "
          SELECT 
            p.product_id,
            p.product_name,
            COUNT(pb.batch_id) AS total_batches,
            COUNT(CASE WHEN pb.status = 'Active' THEN pb.batch_id END) AS active_batches,
            COUNT(CASE WHEN pb.status = 'Expired' THEN pb.batch_id END) AS expired_batches,
            COALESCE(SUM(pb.available_quantity), 0) AS total_quantity,
            COALESCE(AVG(pb.mrp), 0) AS avg_mrp,
            MIN(pb.expiry_date) AS nearest_expiry,
            MAX(pb.created_at) AS latest_batch
          FROM product p
          LEFT JOIN product_batches pb ON pb.product_id = p.product_id
          WHERE p.status = 1
          GROUP BY p.product_id
          ORDER BY total_batches DESC
          ";
          
          $result = $connect->query($sql);
        ?>

        <div class="table-responsive">
          <table id="reportTable" class="table table-bordered table-sm">
            <thead class="table-dark">
              <tr>
                <th style="width: 20%;">Medicine Name</th>
                <th style="width: 10%;">Total Batches</th>
                <th style="width: 10%;">Active</th>
                <th style="width: 10%;">Expired</th>
                <th style="width: 10%;">Total Qty</th>
                <th style="width: 10%;">Avg MRP</th>
                <th style="width: 15%;">Nearest Expiry</th>
                <th style="width: 15%;">Latest Batch Date</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              while ($row = $result->fetch_assoc()) {
              ?>
              <tr>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td class="text-center"><?php echo $row['total_batches']; ?></td>
                <td class="text-center"><span class="badge badge-success"><?php echo $row['active_batches']; ?></span></td>
                <td class="text-center"><span class="badge badge-danger"><?php echo $row['expired_batches']; ?></span></td>
                <td class="text-center"><?php echo number_format($row['total_quantity']); ?></td>
                <td class="text-center">₹<?php echo number_format($row['avg_mrp'], 2); ?></td>
                <td class="text-center"><?php echo date('d-M-Y', strtotime($row['nearest_expiry'])); ?></td>
                <td class="text-center"><?php echo date('d-M-Y', strtotime($row['latest_batch'])); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php 
        // ========== SUPPLIER PERFORMANCE REPORT ==========
        elseif ($report_type == 'supplier_performance'):
          $sql = "
          SELECT 
            s.supplier_id,
            s.supplier_name,
            s.supplier_code,
            COUNT(DISTINCT po.po_id) AS total_pos,
            COUNT(DISTINCT pb.batch_id) AS supplied_batches,
            COALESCE(SUM(po.grand_total), 0) AS total_amount,
            AVG(DATEDIFF(po.created_at, po.po_date)) AS avg_delivery_days,
            COUNT(CASE WHEN po.po_status = 'Received' THEN po.po_id END) AS completed_pos,
            COUNT(CASE WHEN po.po_status = 'Cancelled' THEN po.po_id END) AS cancelled_pos
          FROM suppliers s
          LEFT JOIN purchase_orders po ON po.supplier_id = s.supplier_id
          LEFT JOIN product_batches pb ON pb.supplier_id = s.supplier_id
          WHERE s.supplier_status = 'Active'
          GROUP BY s.supplier_id
          ORDER BY total_amount DESC
          ";
          
          $result = $connect->query($sql);
        ?>

        <div class="table-responsive">
          <table id="reportTable" class="table table-bordered table-sm">
            <thead class="table-dark">
              <tr>
                <th style="width: 15%;">Supplier Name</th>
                <th style="width: 10%;">Code</th>
                <th style="width: 10%;">Total POs</th>
                <th style="width: 10%;">Batches</th>
                <th style="width: 12%;">Total Amount (₹)</th>
                <th style="width: 10%;">Completed</th>
                <th style="width: 10%;">Cancelled</th>
                <th style="width: 13%;">Avg Delivery Days</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              while ($row = $result->fetch_assoc()) {
                $completion_rate = $row['total_pos'] > 0 ? round(($row['completed_pos'] / $row['total_pos']) * 100) : 0;
              ?>
              <tr>
                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($row['supplier_code']); ?></td>
                <td class="text-center"><?php echo $row['total_pos']; ?></td>
                <td class="text-center"><?php echo $row['supplied_batches']; ?></td>
                <td class="text-right">₹<?php echo number_format($row['total_amount'], 2); ?></td>
                <td class="text-center"><?php echo $row['completed_pos']; ?></td>
                <td class="text-center"><?php echo $row['cancelled_pos']; ?></td>
                <td class="text-center"><?php echo round($row['avg_delivery_days'] ?? 0); ?> days</td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<script>
  function exportToCSV() {
    var csv = "data:text/csv;charset=utf-8,";
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
      var row = [], cols = rows[i].querySelectorAll("td, th");
      
      for (var j = 0; j < cols.length; j++) {
        row.push(cols[j].innerText);
      }
      
      csv += row.join(",") + "\n";
    }
    
    var link = document.createElement("a");
    link.setAttribute("href", encodeURI(csv));
    link.setAttribute("download", "report_<?php echo date('Y-m-d'); ?>.csv");
    link.click();
  }
</script>

<?php include('./constant/layout/footer.php'); ?>
