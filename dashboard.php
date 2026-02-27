<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
if (!isset($connect) || !$connect) {
    die('Database connection error');
}

date_default_timezone_set('Asia/Kolkata');

function getSingleValue($connect, $sql, $default = 0)
{
    $res = $connect->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return reset($row);
    }
    return $default;
}

function normalizeDate($value, $fallback)
{
    if (!$value) {
        return $fallback;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt) {
        return $fallback;
    }
    return $dt->format('Y-m-d');
}

$today = date('Y-m-d');
$range = isset($_GET['range']) ? trim($_GET['range']) : '30d';
$allowedRanges = ['today', '7d', '30d', 'this_month', 'custom'];
if (!in_array($range, $allowedRanges, true)) {
    $range = '30d';
}

$filterStartDate = $today;
$filterEndDate = $today;

switch ($range) {
    case 'today':
        $filterStartDate = $today;
        $filterEndDate = $today;
        break;
    case '7d':
        $filterStartDate = date('Y-m-d', strtotime('-6 days'));
        $filterEndDate = $today;
        break;
    case '30d':
        $filterStartDate = date('Y-m-d', strtotime('-29 days'));
        $filterEndDate = $today;
        break;
    case 'this_month':
        $filterStartDate = date('Y-m-01');
        $filterEndDate = $today;
        break;
    case 'custom':
        $filterStartDate = normalizeDate($_GET['start_date'] ?? null, date('Y-m-d', strtotime('-29 days')));
        $filterEndDate = normalizeDate($_GET['end_date'] ?? null, $today);
        break;
}

if (strtotime($filterStartDate) > strtotime($filterEndDate)) {
    $temp = $filterStartDate;
    $filterStartDate = $filterEndDate;
    $filterEndDate = $temp;
}

if ((strtotime($filterEndDate) - strtotime($filterStartDate)) / 86400 > 120) {
    $filterStartDate = date('Y-m-d', strtotime($filterEndDate . ' -120 days'));
}

$startEsc = $connect->real_escape_string($filterStartDate);
$endEsc = $connect->real_escape_string($filterEndDate);

$totalMedicines = (int) getSingleValue($connect, "SELECT COUNT(*) FROM product WHERE status = 1", 0);
$totalStockUnits = (float) getSingleValue($connect, "SELECT COALESCE(SUM(available_quantity), 0) FROM product_batches WHERE status = 'Active'", 0);

$lowStockItems = (int) getSingleValue($connect, "
    SELECT COUNT(*) FROM (
        SELECT p.product_id
        FROM product p
        LEFT JOIN product_batches pb
            ON pb.product_id = p.product_id
           AND pb.status = 'Active'
        WHERE p.status = 1
        GROUP BY p.product_id, p.reorder_level
        HAVING COALESCE(SUM(pb.available_quantity), 0) < COALESCE(p.reorder_level, 0)
    ) t
", 0);

$expiringSoon = (int) getSingleValue($connect, "
    SELECT COUNT(*)
    FROM product_batches
    WHERE status = 'Active'
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
", 0);

$purchaseThisMonth = (float) getSingleValue($connect, "
    SELECT COALESCE(SUM(grand_total), 0)
    FROM purchase_invoices
    WHERE invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
", 0);

$salesThisMonth = (float) getSingleValue($connect, "
    SELECT COALESCE(SUM(grand_total), 0)
    FROM sales_invoices
    WHERE invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
      AND (is_cancelled IS NULL OR is_cancelled = 0)
", 0);

$pendingPurchaseInvoices = (int) getSingleValue($connect, "
    SELECT COUNT(*)
    FROM purchase_invoices
    WHERE status IN ('Draft', 'Approved')
      AND invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
", 0);

$supplierPayables = (float) getSingleValue($connect, "
    SELECT COALESCE(SUM(grand_total - COALESCE(paid_amount, 0)), 0)
    FROM purchase_invoices
    WHERE invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
", 0);

$customerReceivables = (float) getSingleValue($connect, "
    SELECT COALESCE(SUM(COALESCE(due_amount, grand_total - COALESCE(paid_amount, 0))), 0)
    FROM sales_invoices
    WHERE (is_cancelled IS NULL OR is_cancelled = 0)
      AND invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
", 0);

$activeClients = (int) getSingleValue($connect, "
    SELECT COUNT(*)
    FROM clients
    WHERE status = 'ACTIVE' OR status IS NULL
", 0);

$recentTransactionsSql = "
    SELECT * FROM (
        SELECT
            'PO' AS txn_type,
            po.po_number AS txn_number,
            s.supplier_name AS party_name,
            po.po_date AS txn_date,
            po.grand_total AS amount,
            po.payment_status AS payment_status,
            CONCAT('po_view.php?id=', po.po_id) AS view_link
        FROM purchase_orders po
        LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
        WHERE po.po_date BETWEEN '{$startEsc}' AND '{$endEsc}'

        UNION ALL

        SELECT
            'PI' AS txn_type,
            pi.invoice_no AS txn_number,
            s.supplier_name AS party_name,
            pi.invoice_date AS txn_date,
            pi.grand_total AS amount,
            pi.status AS payment_status,
            CONCAT('invoice_view.php?id=', pi.id) AS view_link
        FROM purchase_invoices pi
        LEFT JOIN suppliers s ON s.supplier_id = pi.supplier_id
        WHERE pi.invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'

        UNION ALL

        SELECT
            'SI' AS txn_type,
            si.invoice_number AS txn_number,
            c.name AS party_name,
            si.invoice_date AS txn_date,
            si.grand_total AS amount,
            si.payment_status AS payment_status,
            CONCAT('print_invoice.php?id=', si.invoice_id) AS view_link
        FROM sales_invoices si
        LEFT JOIN clients c ON c.client_id = si.client_id
        WHERE si.invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
    ) tx
    ORDER BY tx.txn_date DESC
    LIMIT 20
";
$recentTransactions = $connect->query($recentTransactionsSql);

$lowStockAlertSql = "
    SELECT p.product_name, COALESCE(SUM(pb.available_quantity), 0) AS stock_qty, COALESCE(p.reorder_level, 0) AS reorder_level
    FROM product p
    LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
    WHERE p.status = 1
    GROUP BY p.product_id, p.product_name, p.reorder_level
    HAVING COALESCE(SUM(pb.available_quantity), 0) < COALESCE(p.reorder_level, 0)
    ORDER BY stock_qty ASC
    LIMIT 8
";
$lowStockAlerts = $connect->query($lowStockAlertSql);

$expiringAlertSql = "
    SELECT p.product_name, pb.batch_number, pb.expiry_date, pb.available_quantity
    FROM product_batches pb
    INNER JOIN product p ON p.product_id = pb.product_id
    WHERE pb.status = 'Active'
      AND pb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY pb.expiry_date ASC
    LIMIT 8
";
$expiringAlerts = $connect->query($expiringAlertSql);

$overdueSalesSql = "
    SELECT si.invoice_number, c.name AS client_name, si.due_date, COALESCE(si.due_amount, 0) AS due_amount
    FROM sales_invoices si
    LEFT JOIN clients c ON c.client_id = si.client_id
    WHERE si.due_date < CURDATE()
      AND COALESCE(si.due_amount, 0) > 0
      AND COALESCE(si.payment_status, 'UNPAID') <> 'PAID'
      AND (si.is_cancelled IS NULL OR si.is_cancelled = 0)
            AND si.invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
    ORDER BY si.due_date ASC
    LIMIT 8
";
$overdueSales = $connect->query($overdueSalesSql);

$trendSql = "
    SELECT dt,
           SUM(purchase_amt) AS purchase_amt,
           SUM(sales_amt) AS sales_amt
    FROM (
        SELECT DATE(invoice_date) AS dt, SUM(grand_total) AS purchase_amt, 0 AS sales_amt
        FROM purchase_invoices
        WHERE invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
        GROUP BY DATE(invoice_date)

        UNION ALL

        SELECT DATE(invoice_date) AS dt, 0 AS purchase_amt, SUM(grand_total) AS sales_amt
        FROM sales_invoices
                WHERE invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
          AND (is_cancelled IS NULL OR is_cancelled = 0)
        GROUP BY DATE(invoice_date)
    ) t
    GROUP BY dt
    ORDER BY dt ASC
";

$trendMap = [];
$trendRes = $connect->query($trendSql);
if ($trendRes) {
    while ($row = $trendRes->fetch_assoc()) {
        $trendMap[$row['dt']] = [
            'purchase' => (float) $row['purchase_amt'],
            'sales' => (float) $row['sales_amt']
        ];
    }
}

$trendChartRows = [];
$loopStart = strtotime($filterStartDate);
$loopEnd = strtotime($filterEndDate);
for ($dayTs = $loopStart; $dayTs <= $loopEnd; $dayTs += 86400) {
    $day = date('Y-m-d', $dayTs);
    $trendChartRows[] = [
        date('d M', strtotime($day)),
        isset($trendMap[$day]) ? $trendMap[$day]['purchase'] : 0,
        isset($trendMap[$day]) ? $trendMap[$day]['sales'] : 0,
    ];
}

$stockHealthSql = "
    SELECT
      SUM(CASE WHEN COALESCE(stock_qty,0) = 0 THEN 1 ELSE 0 END) AS critical_items,
      SUM(CASE WHEN COALESCE(stock_qty,0) > 0 AND COALESCE(stock_qty,0) <= COALESCE(reorder_level,0) THEN 1 ELSE 0 END) AS low_items,
      SUM(CASE WHEN COALESCE(stock_qty,0) > COALESCE(reorder_level,0) THEN 1 ELSE 0 END) AS healthy_items
    FROM (
      SELECT p.product_id, p.reorder_level, COALESCE(SUM(pb.available_quantity),0) AS stock_qty
      FROM product p
      LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
      WHERE p.status = 1
      GROUP BY p.product_id, p.reorder_level
    ) x
";

$stockHealth = ['critical_items' => 0, 'low_items' => 0, 'healthy_items' => 0];
$stockHealthRes = $connect->query($stockHealthSql);
if ($stockHealthRes && $row = $stockHealthRes->fetch_assoc()) {
    $stockHealth = $row;
}

?>

<style>
.kpi-card { min-height: 126px; }
.kpi-value { font-size: 1.8rem; font-weight: 700; line-height: 1.1; }
.kpi-label { font-size: .92rem; opacity: .9; }
.mini-list { max-height: 260px; overflow-y: auto; }
.mini-list .list-group-item { padding: .55rem .75rem; }
.dashboard-filter-card .form-control,
.dashboard-filter-card .custom-select { height: 36px; }
.quick-btn { min-width: 92px; margin-left: 4px; margin-bottom: 4px; }
</style>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary">Dashboard V2</h3>
                <small class="text-muted">Purchases, invoices, stock, and priority alerts (<?php echo htmlspecialchars(date('d M Y', strtotime($filterStartDate))); ?> - <?php echo htmlspecialchars(date('d M Y', strtotime($filterEndDate))); ?>)</small>
            </div>
            <div class="col-md-4 text-right align-self-center">
                <a href="add_medicine.php" class="btn btn-sm btn-info quick-btn"><i class="fa fa-plus"></i> Medicine</a>
                <a href="create_po.php" class="btn btn-sm btn-primary quick-btn"><i class="fa fa-file"></i> PO</a>
                <a href="purchase_invoice.php" class="btn btn-sm btn-warning quick-btn"><i class="fa fa-file-text"></i> PI</a>
                <a href="sales_invoice_form.php" class="btn btn-sm btn-success quick-btn"><i class="fa fa-shopping-cart"></i> SI</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card dashboard-filter-card">
                    <div class="card-body py-2">
                        <form method="GET" class="form-row align-items-end">
                            <div class="col-md-3">
                                <label class="mb-1"><small><strong>Date Range</strong></small></label>
                                <select name="range" id="rangeSelect" class="custom-select">
                                    <option value="today" <?php echo $range === 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="7d" <?php echo $range === '7d' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30d" <?php echo $range === '30d' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="this_month" <?php echo $range === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="custom" <?php echo $range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-3 custom-date-wrap" style="display: <?php echo $range === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="mb-1"><small><strong>Start Date</strong></small></label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($filterStartDate); ?>">
                            </div>
                            <div class="col-md-3 custom-date-wrap" style="display: <?php echo $range === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="mb-1"><small><strong>End Date</strong></small></label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($filterEndDate); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Apply</button>
                                <a href="dashboard.php?range=30d" class="btn btn-light btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-info kpi-card"><div class="card-body">
                    <div class="kpi-value"><?php echo number_format($totalMedicines); ?></div>
                    <div class="kpi-label">Total Medicines</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-primary kpi-card"><div class="card-body">
                    <div class="kpi-value"><?php echo number_format($totalStockUnits); ?></div>
                    <div class="kpi-label">Total Stock Units</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-danger kpi-card"><div class="card-body">
                    <div class="kpi-value"><?php echo number_format($lowStockItems); ?></div>
                    <div class="kpi-label">Low Stock Items</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-warning kpi-card"><div class="card-body">
                    <div class="kpi-value"><?php echo number_format($expiringSoon); ?></div>
                    <div class="kpi-label">Expiring in 30 Days</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-secondary kpi-card"><div class="card-body">
                    <div class="kpi-value">₹<?php echo number_format($purchaseThisMonth, 0); ?></div>
                    <div class="kpi-label">Purchase in Range</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-success kpi-card"><div class="card-body">
                    <div class="kpi-value">₹<?php echo number_format($salesThisMonth, 0); ?></div>
                    <div class="kpi-label">Sales in Range</div>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card kpi-card"><div class="card-body">
                    <div class="h4 mb-1"><?php echo number_format($pendingPurchaseInvoices); ?></div>
                    <div class="text-muted">Pending Purchase Invoices</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card kpi-card"><div class="card-body">
                    <div class="h4 mb-1">₹<?php echo number_format($supplierPayables, 0); ?></div>
                    <div class="text-muted">Supplier Payables</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card kpi-card"><div class="card-body">
                    <div class="h4 mb-1">₹<?php echo number_format($customerReceivables, 0); ?></div>
                    <div class="text-muted">Customer Receivables</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card kpi-card"><div class="card-body">
                    <div class="h4 mb-1"><?php echo number_format($activeClients); ?></div>
                    <div class="text-muted">Active Clients</div>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Recent Transactions</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Number</th>
                                        <th>Party</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentTransactions && $recentTransactions->num_rows > 0): ?>
                                        <?php while ($tx = $recentTransactions->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($tx['txn_type']); ?></span></td>
                                                <td><?php echo htmlspecialchars($tx['txn_number']); ?></td>
                                                <td><?php echo htmlspecialchars($tx['party_name'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($tx['txn_date']))); ?></td>
                                                <td class="text-right">₹<?php echo number_format((float)$tx['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($tx['payment_status'] ?? '-'); ?></td>
                                                <td><a href="<?php echo htmlspecialchars($tx['view_link']); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center text-muted">No recent transactions found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card mb-3">
                    <div class="card-header"><strong>Low Stock Alerts</strong></div>
                    <ul class="list-group list-group-flush mini-list">
                        <?php if ($lowStockAlerts && $lowStockAlerts->num_rows > 0): ?>
                            <?php while ($row = $lowStockAlerts->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($row['product_name']); ?></span>
                                    <span class="badge badge-danger"><?php echo (int)$row['stock_qty']; ?>/<?php echo (int)$row['reorder_level']; ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No low stock items.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Expiring Soon (30 Days)</strong></div>
                    <ul class="list-group list-group-flush mini-list">
                        <?php if ($expiringAlerts && $expiringAlerts->num_rows > 0): ?>
                            <?php while ($row = $expiringAlerts->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                    <small class="text-muted">Batch: <?php echo htmlspecialchars($row['batch_number']); ?> | Exp: <?php echo htmlspecialchars(date('d M Y', strtotime($row['expiry_date']))); ?></small>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No near-expiry batches.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header"><strong>Overdue Sales Invoices</strong></div>
                    <ul class="list-group list-group-flush mini-list">
                        <?php if ($overdueSales && $overdueSales->num_rows > 0): ?>
                            <?php while ($row = $overdueSales->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($row['invoice_number']); ?> (<?php echo htmlspecialchars($row['client_name'] ?? 'Client'); ?>)</div>
                                    <small class="text-muted">Due: <?php echo htmlspecialchars(date('d M Y', strtotime($row['due_date']))); ?> | ₹<?php echo number_format((float)$row['due_amount'], 2); ?></small>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No overdue sales invoices.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Purchase vs Sales Trend</strong></div>
                    <div class="card-body">
                        <div id="trendChart" style="width:100%; height:340px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Stock Health</strong></div>
                    <div class="card-body">
                        <div id="stockHealthChart" style="width:100%; height:340px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script>
$(function() {
    $('.preloader').fadeOut();

    $('#rangeSelect').on('change', function () {
        var isCustom = $(this).val() === 'custom';
        $('.custom-date-wrap').toggle(isCustom);
    });
});

google.charts.load('current', {'packages':['corechart', 'bar']});
google.charts.setOnLoadCallback(drawDashboardCharts);

function drawDashboardCharts() {
    var trendData = google.visualization.arrayToDataTable([
        ['Day', 'Purchase', 'Sales'],
        <?php
            $trendRowsJs = [];
            foreach ($trendChartRows as $r) {
                $trendRowsJs[] = "['" . $r[0] . "', " . round($r[1], 2) . ", " . round($r[2], 2) . "]";
            }
            echo implode(',', $trendRowsJs);
        ?>
    ]);

    var trendOptions = {
        legend: { position: 'top' },
        colors: ['#6c757d', '#28a745'],
        chartArea: {width: '82%', height: '70%'}
    };

    var trendChart = new google.visualization.ColumnChart(document.getElementById('trendChart'));
    trendChart.draw(trendData, trendOptions);

    var stockData = google.visualization.arrayToDataTable([
        ['Status', 'Items'],
        ['Healthy', <?php echo (int)$stockHealth['healthy_items']; ?>],
        ['Low', <?php echo (int)$stockHealth['low_items']; ?>],
        ['Critical', <?php echo (int)$stockHealth['critical_items']; ?>]
    ]);

    var stockOptions = {
        pieHole: 0.45,
        colors: ['#28a745', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' },
        chartArea: {width: '90%', height: '75%'}
    };

    var stockChart = new google.visualization.PieChart(document.getElementById('stockHealthChart'));
    stockChart.draw(stockData, stockOptions);
}

window.addEventListener('resize', drawDashboardCharts);
</script>