<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
if (!isset($connect) || !$connect) {
    die('Database connection error');
}

date_default_timezone_set('Asia/Kolkata');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeDateInput($value, $fallback)
{
    if (!$value) {
        return $fallback;
    }
    $parsed = DateTime::createFromFormat('Y-m-d', (string) $value);
    if (!$parsed) {
        return $fallback;
    }
    return $parsed->format('Y-m-d');
}

$today = date('Y-m-d');
$dateFrom = normalizeDateInput($_GET['date_from'] ?? date('Y-m-01'), date('Y-m-01'));
$dateTo = normalizeDateInput($_GET['date_to'] ?? $today, $today);

if (strtotime($dateFrom) > strtotime($dateTo)) {
    $tmp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $tmp;
}

$maxSpanDays = 365;
if ((strtotime($dateTo) - strtotime($dateFrom)) / 86400 > $maxSpanDays) {
    $dateFrom = date('Y-m-d', strtotime($dateTo . ' -' . $maxSpanDays . ' days'));
}

$clientId = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;
$paymentStatus = strtoupper(trim((string) ($_GET['payment_status'] ?? '')));
$allowedPaymentStatuses = ['UNPAID', 'PARTIAL', 'PAID'];
if (!in_array($paymentStatus, $allowedPaymentStatuses, true)) {
    $paymentStatus = '';
}

$includeCancelled = isset($_GET['include_cancelled']) && $_GET['include_cancelled'] === '1';

$clientOptions = [];
$clientQuery = $connect->query("SELECT client_id, name FROM clients WHERE status = 'ACTIVE' OR status IS NULL ORDER BY name ASC");
if ($clientQuery) {
    while ($clientRow = $clientQuery->fetch_assoc()) {
        $clientOptions[] = $clientRow;
    }
}

$startEsc = $connect->real_escape_string($dateFrom);
$endEsc = $connect->real_escape_string($dateTo);

$conditions = [
    "si.invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'",
];

if (!$includeCancelled) {
    $conditions[] = "COALESCE(si.is_cancelled, 0) = 0";
}

if ($clientId > 0) {
    $conditions[] = "si.client_id = {$clientId}";
}

if ($paymentStatus !== '') {
    $statusEsc = $connect->real_escape_string($paymentStatus);
    $conditions[] = "si.payment_status = '{$statusEsc}'";
}

$whereClause = implode(' AND ', $conditions);

$invoiceSql = "
    SELECT
        si.invoice_id,
        si.invoice_number,
        si.invoice_date,
        si.due_date,
        si.grand_total,
        COALESCE(si.paid_amount, 0) AS paid_amount,
        COALESCE(si.due_amount, si.grand_total - COALESCE(si.paid_amount, 0)) AS due_amount,
        COALESCE(si.payment_status, 'UNPAID') AS payment_status,
        COALESCE(si.is_cancelled, 0) AS is_cancelled,
        c.name AS client_name
    FROM sales_invoices si
    LEFT JOIN clients c ON c.client_id = si.client_id
    WHERE {$whereClause}
    ORDER BY si.invoice_date DESC, si.invoice_id DESC
";

$invoiceRows = [];
$totalSales = 0.0;
$totalPaid = 0.0;
$totalDue = 0.0;
$cancelledCount = 0;

$invoiceRes = $connect->query($invoiceSql);
if ($invoiceRes) {
    while ($row = $invoiceRes->fetch_assoc()) {
        $row['grand_total'] = (float) $row['grand_total'];
        $row['paid_amount'] = (float) $row['paid_amount'];
        $row['due_amount'] = (float) $row['due_amount'];
        $row['is_cancelled'] = (int) $row['is_cancelled'];

        $invoiceRows[] = $row;
        $totalSales += $row['grand_total'];
        $totalPaid += $row['paid_amount'];
        $totalDue += $row['due_amount'];

        if ($row['is_cancelled'] === 1) {
            $cancelledCount++;
        }
    }
}

$invoiceCount = count($invoiceRows);
$averageInvoice = $invoiceCount > 0 ? $totalSales / $invoiceCount : 0;
$recoveryRate = $totalSales > 0 ? ($totalPaid / $totalSales) * 100 : 0;

$topProductsSql = "
    SELECT
        sii.product_id,
        COALESCE(p.product_name, CONCAT('Product #', sii.product_id)) AS product_name,
        COALESCE(b.brand_name, '-') AS brand_name,
        SUM(sii.quantity) AS qty_sold,
        SUM(sii.line_total) AS sales_amount,
        SUM(COALESCE(sii.purchase_rate, 0) * sii.quantity) AS purchase_amount
    FROM sales_invoice_items sii
    INNER JOIN sales_invoices si ON si.invoice_id = sii.invoice_id
    LEFT JOIN product p ON p.product_id = sii.product_id
    LEFT JOIN brands b ON b.brand_id = p.brand_id
    WHERE {$whereClause}
    GROUP BY sii.product_id, p.product_name, b.brand_name
    ORDER BY qty_sold DESC, sales_amount DESC
    LIMIT 25
";

$topProductRows = [];
$estimatedProfit = 0.0;
$topProductsRes = $connect->query($topProductsSql);
if ($topProductsRes) {
    while ($row = $topProductsRes->fetch_assoc()) {
        $row['qty_sold'] = (float) $row['qty_sold'];
        $row['sales_amount'] = (float) $row['sales_amount'];
        $row['purchase_amount'] = (float) $row['purchase_amount'];
        $row['profit_amount'] = $row['sales_amount'] - $row['purchase_amount'];
        $topProductRows[] = $row;
        $estimatedProfit += $row['profit_amount'];
    }
}

$trendSql = "
    SELECT
        si.invoice_date AS sales_day,
        COUNT(*) AS invoice_count,
        SUM(si.grand_total) AS sales_amount,
        SUM(COALESCE(si.paid_amount, 0)) AS paid_amount,
        SUM(COALESCE(si.due_amount, si.grand_total - COALESCE(si.paid_amount, 0))) AS due_amount
    FROM sales_invoices si
    WHERE {$whereClause}
    GROUP BY si.invoice_date
    ORDER BY si.invoice_date ASC
";

$trendMap = [];
$trendRes = $connect->query($trendSql);
if ($trendRes) {
    while ($row = $trendRes->fetch_assoc()) {
        $trendMap[$row['sales_day']] = [
            'sales' => (float) $row['sales_amount'],
            'paid' => (float) $row['paid_amount'],
            'due' => (float) $row['due_amount'],
            'count' => (int) $row['invoice_count'],
        ];
    }
}

$trendChartRows = [];
$fromTs = strtotime($dateFrom);
$toTs = strtotime($dateTo);
for ($ts = $fromTs; $ts <= $toTs; $ts += 86400) {
    $day = date('Y-m-d', $ts);
    $trendChartRows[] = [
        date('d M', $ts),
        isset($trendMap[$day]) ? $trendMap[$day]['sales'] : 0,
        isset($trendMap[$day]) ? $trendMap[$day]['paid'] : 0,
        isset($trendMap[$day]) ? $trendMap[$day]['due'] : 0,
    ];
}

$paymentMix = [
    'PAID' => 0,
    'PARTIAL' => 0,
    'UNPAID' => 0,
];
$paymentMixAmount = [
    'PAID' => 0,
    'PARTIAL' => 0,
    'UNPAID' => 0,
];

$paymentMixSql = "
    SELECT
        COALESCE(si.payment_status, 'UNPAID') AS payment_status,
        COUNT(*) AS invoice_count,
        SUM(si.grand_total) AS total_amount
    FROM sales_invoices si
    WHERE {$whereClause}
    GROUP BY COALESCE(si.payment_status, 'UNPAID')
";

$paymentRes = $connect->query($paymentMixSql);
if ($paymentRes) {
    while ($row = $paymentRes->fetch_assoc()) {
        $statusKey = strtoupper((string) $row['payment_status']);
        if (!isset($paymentMix[$statusKey])) {
            $statusKey = 'UNPAID';
        }
        $paymentMix[$statusKey] += (int) $row['invoice_count'];
        $paymentMixAmount[$statusKey] += (float) $row['total_amount'];
    }
}

$topClientName = '-';
$topClientAmount = 0.0;
$topClientSql = "
    SELECT
        COALESCE(c.name, 'Walk-in Client') AS client_name,
        SUM(si.grand_total) AS total_amount
    FROM sales_invoices si
    LEFT JOIN clients c ON c.client_id = si.client_id
    WHERE {$whereClause}
    GROUP BY si.client_id, c.name
    ORDER BY total_amount DESC
    LIMIT 1
";
$topClientRes = $connect->query($topClientSql);
if ($topClientRes && $topClientRow = $topClientRes->fetch_assoc()) {
    $topClientName = $topClientRow['client_name'];
    $topClientAmount = (float) $topClientRow['total_amount'];
}

?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<style>
.sales-kpi-card {
    min-height: 126px;
}

.sales-kpi-value {
    font-size: 1.55rem;
    font-weight: 700;
    line-height: 1.1;
}

.sales-kpi-label {
    font-size: .88rem;
    opacity: .86;
}

.sales-filter .form-control,
.sales-filter .custom-select {
    height: 36px;
}

.report-caption {
    font-size: .86rem;
    color: #6c757d;
}

.datatable-tools .dt-buttons {
    margin-bottom: .75rem;
}

.status-chip {
    min-width: 76px;
    display: inline-block;
    text-align: center;
}
</style>

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-7 align-self-center">
                <h3 class="text-primary mb-1">Sales Report Center</h3>
                <small class="text-muted">Track sales, collections, dues, product velocity, and payment health from one place.</small>
            </div>
            <div class="col-md-5 text-right align-self-center">
                <a href="sales_invoice_form.php" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> New Sales Invoice</a>
                <a href="sales_invoice_list.php" class="btn btn-sm btn-info"><i class="fa fa-list"></i> Invoice List</a>
                <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>

        <div class="card sales-filter mb-3">
            <div class="card-body py-3">
                <form method="GET" class="form-row align-items-end">
                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>From</strong></label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo h($dateFrom); ?>">
                    </div>
                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>To</strong></label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo h($dateTo); ?>">
                    </div>
                    <div class="col-xl-3 col-md-4 mb-2">
                        <label class="mb-1"><strong>Client</strong></label>
                        <select name="client_id" class="custom-select">
                            <option value="0">All Clients</option>
                            <?php foreach ($clientOptions as $client): ?>
                                <option value="<?php echo (int) $client['client_id']; ?>" <?php echo (int) $client['client_id'] === $clientId ? 'selected' : ''; ?>>
                                    <?php echo h($client['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>Payment</strong></label>
                        <select name="payment_status" class="custom-select">
                            <option value="">All</option>
                            <option value="PAID" <?php echo $paymentStatus === 'PAID' ? 'selected' : ''; ?>>Paid</option>
                            <option value="PARTIAL" <?php echo $paymentStatus === 'PARTIAL' ? 'selected' : ''; ?>>Partial</option>
                            <option value="UNPAID" <?php echo $paymentStatus === 'UNPAID' ? 'selected' : ''; ?>>Unpaid</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-2">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" id="includeCancelled" name="include_cancelled" value="1" <?php echo $includeCancelled ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="includeCancelled">Include Cancelled</label>
                        </div>
                    </div>
                    <div class="col-xl-1 col-md-4 mb-2 text-right">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-filter"></i> Apply</button>
                        <a href="sales_report.php" class="btn btn-light btn-sm btn-block mt-1">Reset</a>
                    </div>
                </form>
                <div class="report-caption mt-1">
                    Range applied: <?php echo h(date('d M Y', strtotime($dateFrom))); ?> to <?php echo h(date('d M Y', strtotime($dateTo))); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-primary text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value"><?php echo number_format($invoiceCount); ?></div>
                    <div class="sales-kpi-label">Invoices</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-success text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value">INR <?php echo number_format($totalSales, 0); ?></div>
                    <div class="sales-kpi-label">Gross Sales</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-info text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value">INR <?php echo number_format($totalPaid, 0); ?></div>
                    <div class="sales-kpi-label">Collections</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-warning text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value">INR <?php echo number_format($totalDue, 0); ?></div>
                    <div class="sales-kpi-label">Outstanding</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-secondary text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value"><?php echo number_format($recoveryRate, 1); ?>%</div>
                    <div class="sales-kpi-label">Recovery Rate</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-dark text-white sales-kpi-card"><div class="card-body">
                    <div class="sales-kpi-value">INR <?php echo number_format($averageInvoice, 0); ?></div>
                    <div class="sales-kpi-label">Average Ticket</div>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card sales-kpi-card"><div class="card-body">
                    <div class="h5 mb-1">Top Client</div>
                    <div class="font-weight-bold mb-1"><?php echo h($topClientName); ?></div>
                    <small class="text-muted">Sales contribution: INR <?php echo number_format($topClientAmount, 2); ?></small>
                </div></div>
            </div>
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card sales-kpi-card"><div class="card-body">
                    <div class="h5 mb-1">Estimated Product Margin</div>
                    <div class="font-weight-bold mb-1">INR <?php echo number_format($estimatedProfit, 2); ?></div>
                    <small class="text-muted">Based on invoice item purchase rate snapshots.</small>
                </div></div>
            </div>
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card sales-kpi-card"><div class="card-body">
                    <div class="h5 mb-1">Cancelled Invoices</div>
                    <div class="font-weight-bold mb-1"><?php echo number_format($cancelledCount); ?></div>
                    <small class="text-muted">Visible only when "Include Cancelled" is enabled.</small>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Sales vs Collection Trend</strong></div>
                    <div class="card-body"><div id="salesTrendChart" style="width:100%;height:340px;"></div></div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Payment Status Mix</strong></div>
                    <div class="card-body"><div id="paymentMixChart" style="width:100%;height:340px;"></div></div>
                </div>
            </div>
        </div>

        <div class="card mb-3 datatable-tools">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Top Selling Products</strong>
                <small class="text-muted">By quantity and sales amount</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="topProductsTable" class="table table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Brand</th>
                                <th class="text-right">Qty Sold</th>
                                <th class="text-right">Sales (INR)</th>
                                <th class="text-right">Purchase (INR)</th>
                                <th class="text-right">Margin (INR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topProductRows)): ?>
                                <?php foreach ($topProductRows as $row): ?>
                                    <tr>
                                        <td><?php echo h($row['product_name']); ?></td>
                                        <td><?php echo h($row['brand_name']); ?></td>
                                        <td class="text-right"><?php echo number_format($row['qty_sold'], 2); ?></td>
                                        <td class="text-right"><?php echo number_format($row['sales_amount'], 2); ?></td>
                                        <td class="text-right"><?php echo number_format($row['purchase_amount'], 2); ?></td>
                                        <td class="text-right <?php echo $row['profit_amount'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo number_format($row['profit_amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No product-level sales found for selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card datatable-tools">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Invoice-level Sales Report</strong>
                <small class="text-muted">Filter, export, and print from table controls</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesInvoiceTable" class="table table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Invoice No.</th>
                                <th>Client</th>
                                <th>Payment</th>
                                <th>Cancelled</th>
                                <th class="text-right">Gross (INR)</th>
                                <th class="text-right">Paid (INR)</th>
                                <th class="text-right">Due (INR)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoiceRows)): ?>
                                <?php foreach ($invoiceRows as $idx => $row): ?>
                                    <?php
                                        $payment = strtoupper((string) $row['payment_status']);
                                        $paymentClass = 'badge-secondary';
                                        if ($payment === 'PAID') {
                                            $paymentClass = 'badge-success';
                                        } elseif ($payment === 'PARTIAL') {
                                            $paymentClass = 'badge-warning';
                                        } elseif ($payment === 'UNPAID') {
                                            $paymentClass = 'badge-danger';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo h(date('d M Y', strtotime($row['invoice_date']))); ?></td>
                                        <td><?php echo h($row['invoice_number']); ?></td>
                                        <td><?php echo h($row['client_name'] ?: 'Walk-in Client'); ?></td>
                                        <td><span class="badge <?php echo $paymentClass; ?> status-chip"><?php echo h($payment); ?></span></td>
                                        <td>
                                            <?php if ((int) $row['is_cancelled'] === 1): ?>
                                                <span class="badge badge-danger">YES</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">NO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($row['grand_total'], 2); ?></td>
                                        <td class="text-right"><?php echo number_format($row['paid_amount'], 2); ?></td>
                                        <td class="text-right <?php echo $row['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo number_format($row['due_amount'], 2); ?></td>
                                        <td>
                                            <a href="print_invoice.php?id=<?php echo (int) $row['invoice_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No sales invoices found for selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script>
$(function () {
    if ($.fn.DataTable) {
        if ($('#topProductsTable tbody tr').length > 0) {
            $('#topProductsTable').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'print']
            });
        }

        if ($('#salesInvoiceTable tbody tr').length > 0) {
            $('#salesInvoiceTable').DataTable({
                pageLength: 25,
                order: [[1, 'desc']],
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'print']
            });
        }
    }
});

google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(drawSalesCharts);

function drawSalesCharts() {
    var trendRows = <?php echo json_encode($trendChartRows, JSON_UNESCAPED_UNICODE); ?>;
    var trendData = new google.visualization.DataTable();
    trendData.addColumn('string', 'Date');
    trendData.addColumn('number', 'Gross Sales');
    trendData.addColumn('number', 'Collections');
    trendData.addColumn('number', 'Outstanding');

    for (var i = 0; i < trendRows.length; i++) {
        trendData.addRow([
            trendRows[i][0],
            Number(trendRows[i][1]),
            Number(trendRows[i][2]),
            Number(trendRows[i][3])
        ]);
    }

    var trendOptions = {
        legend: { position: 'top' },
        chartArea: { width: '84%', height: '70%' },
        colors: ['#198754', '#0dcaf0', '#dc3545']
    };

    var trendChart = new google.visualization.LineChart(document.getElementById('salesTrendChart'));
    trendChart.draw(trendData, trendOptions);

    var paymentData = google.visualization.arrayToDataTable([
        ['Status', 'Invoices'],
        ['Paid', <?php echo (int) $paymentMix['PAID']; ?>],
        ['Partial', <?php echo (int) $paymentMix['PARTIAL']; ?>],
        ['Unpaid', <?php echo (int) $paymentMix['UNPAID']; ?>]
    ]);

    var paymentOptions = {
        pieHole: 0.5,
        legend: { position: 'bottom' },
        chartArea: { width: '90%', height: '78%' },
        colors: ['#28a745', '#ffc107', '#dc3545']
    };

    var paymentChart = new google.visualization.PieChart(document.getElementById('paymentMixChart'));
    paymentChart.draw(paymentData, paymentOptions);
}

window.addEventListener('resize', function () {
    if (typeof drawSalesCharts === 'function') {
        drawSalesCharts();
    }
});
</script>
