<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
if (!isset($connect) || !$connect) {
    die('Database connection error');
}

date_default_timezone_set('Asia/Kolkata');

function ih($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeDateValue($value, $fallback)
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

function fetchScalar($connect, $sql, $default = 0)
{
    $result = $connect->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (float) reset($row);
    }
    return $default;
}

$today = date('Y-m-d');
$reportType = isset($_GET['type']) ? trim((string) $_GET['type']) : 'inventory_summary';
$allowedTypes = ['inventory_summary', 'low_stock', 'expiry_tracking', 'stock_movements', 'batch_analysis', 'supplier_performance'];
if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = 'inventory_summary';
}

$dateFrom = normalizeDateValue($_GET['date_from'] ?? date('Y-m-01'), date('Y-m-01'));
$dateTo = normalizeDateValue($_GET['date_to'] ?? $today, $today);
if (strtotime($dateFrom) > strtotime($dateTo)) {
    $tmp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $tmp;
}

$daysToExpiry = isset($_GET['days_to_expiry']) ? (int) $_GET['days_to_expiry'] : 30;
if ($daysToExpiry < 1) {
    $daysToExpiry = 30;
}
if ($daysToExpiry > 365) {
    $daysToExpiry = 365;
}

$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
$movementType = isset($_GET['movement_type']) ? trim((string) $_GET['movement_type']) : '';
$allowedMovementTypes = ['Purchase', 'Sales', 'Adjustment', 'Return', 'Damage', 'Sample', 'Expiry', 'Opening'];
if (!in_array($movementType, $allowedMovementTypes, true)) {
    $movementType = '';
}

$stockState = isset($_GET['stock_state']) ? trim((string) $_GET['stock_state']) : '';
$allowedStockStates = ['', 'healthy', 'low', 'out'];
if (!in_array($stockState, $allowedStockStates, true)) {
    $stockState = '';
}

$productKeyword = trim((string) ($_GET['product'] ?? ''));

$categoryOptions = [];
$categoryRes = $connect->query('SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 ORDER BY categories_name ASC');
if ($categoryRes) {
    while ($row = $categoryRes->fetch_assoc()) {
        $categoryOptions[] = $row;
    }
}

$supplierOptions = [];
$supplierRes = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status = 'Active' ORDER BY supplier_name ASC");
if ($supplierRes) {
    while ($row = $supplierRes->fetch_assoc()) {
        $supplierOptions[] = $row;
    }
}

$startEsc = $connect->real_escape_string($dateFrom);
$endEsc = $connect->real_escape_string($dateTo);
$productEsc = $connect->real_escape_string($productKeyword);

$totalProducts = (int) fetchScalar($connect, 'SELECT COUNT(*) FROM product WHERE status = 1', 0);
$totalActiveBatches = (int) fetchScalar($connect, "SELECT COUNT(*) FROM product_batches WHERE status = 'Active'", 0);
$totalStockUnits = (float) fetchScalar($connect, "SELECT COALESCE(SUM(available_quantity), 0) FROM product_batches WHERE status = 'Active'", 0);
$totalStockValue = (float) fetchScalar($connect, "SELECT COALESCE(SUM(available_quantity * mrp), 0) FROM product_batches WHERE status = 'Active'", 0);

$stockHealthSql = "
    SELECT
        SUM(CASE WHEN stock_qty <= 0 THEN 1 ELSE 0 END) AS out_items,
        SUM(CASE WHEN stock_qty > 0 AND stock_qty <= reorder_level THEN 1 ELSE 0 END) AS low_items,
        SUM(CASE WHEN stock_qty > reorder_level THEN 1 ELSE 0 END) AS healthy_items
    FROM (
        SELECT
            p.product_id,
            COALESCE(p.reorder_level, 0) AS reorder_level,
            COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS stock_qty
        FROM product p
        LEFT JOIN product_batches pb ON pb.product_id = p.product_id
        WHERE p.status = 1
        GROUP BY p.product_id, p.reorder_level
    ) s
";

$stockHealth = ['out_items' => 0, 'low_items' => 0, 'healthy_items' => 0];
$stockHealthRes = $connect->query($stockHealthSql);
if ($stockHealthRes && $row = $stockHealthRes->fetch_assoc()) {
    $stockHealth = [
        'out_items' => (int) $row['out_items'],
        'low_items' => (int) $row['low_items'],
        'healthy_items' => (int) $row['healthy_items'],
    ];
}

$expiringSoon = (int) fetchScalar(
    $connect,
    "SELECT COUNT(*) FROM product_batches WHERE status = 'Active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$daysToExpiry} DAY)",
    0
);

$reportTitleMap = [
    'inventory_summary' => 'Inventory Summary Report',
    'low_stock' => 'Low Stock Alert Report',
    'expiry_tracking' => 'Expiry Tracking Report',
    'stock_movements' => 'Stock Movements Report',
    'batch_analysis' => 'Batch Analysis Report',
    'supplier_performance' => 'Supplier Performance Report',
];
$activeTitle = $reportTitleMap[$reportType];

$reportRows = [];
$tableHeaders = [];
$movementMix = [
    'Purchase' => 0,
    'Sales' => 0,
    'Adjustment' => 0,
    'Return' => 0,
    'Damage' => 0,
    'Sample' => 0,
    'Expiry' => 0,
    'Opening' => 0,
];

switch ($reportType) {
    case 'inventory_summary':
        $summaryWhere = ["p.status = 1"];
        if ($categoryId > 0) {
            $summaryWhere[] = "p.categories_id = {$categoryId}";
        }
        if ($supplierId > 0) {
            $summaryWhere[] = "pb.supplier_id = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $summaryWhere[] = "p.product_name LIKE '%{$productEsc}%'";
        }

        $having = '';
        if ($stockState === 'low') {
            $having = 'HAVING stock_qty > 0 AND stock_qty <= reorder_level';
        } elseif ($stockState === 'out') {
            $having = 'HAVING stock_qty <= 0';
        } elseif ($stockState === 'healthy') {
            $having = 'HAVING stock_qty > reorder_level';
        }

        $sql = "
            SELECT
                p.product_id,
                p.product_name,
                COALESCE(b.brand_name, '-') AS brand_name,
                COALESCE(c.categories_name, '-') AS category_name,
                COALESCE(p.reorder_level, 0) AS reorder_level,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS stock_qty,
                COUNT(DISTINCT CASE WHEN pb.status = 'Active' THEN pb.batch_id END) AS active_batches,
                MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END) AS nearest_expiry,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity * pb.mrp ELSE 0 END), 0) AS stock_value
            FROM product p
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            LEFT JOIN categories c ON c.categories_id = p.categories_id
            LEFT JOIN product_batches pb ON pb.product_id = p.product_id
            WHERE " . implode(' AND ', $summaryWhere) . "
            GROUP BY p.product_id, p.product_name, b.brand_name, c.categories_name, p.reorder_level
            {$having}
            ORDER BY p.product_name ASC
        ";

        $tableHeaders = ['Product ID', 'Product', 'Brand', 'Category', 'Stock Qty', 'Reorder', 'Active Batches', 'Nearest Expiry', 'Stock Value (INR)', 'Stock Status'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $qty = (float) $row['stock_qty'];
                $reorder = (float) $row['reorder_level'];
                $status = 'Healthy';
                if ($qty <= 0) {
                    $status = 'Out';
                } elseif ($qty <= $reorder) {
                    $status = 'Low';
                }
                $reportRows[] = [
                    $row['product_id'],
                    $row['product_name'],
                    $row['brand_name'],
                    $row['category_name'],
                    number_format($qty, 2),
                    number_format($reorder, 2),
                    (int) $row['active_batches'],
                    $row['nearest_expiry'] ? date('d M Y', strtotime($row['nearest_expiry'])) : '-',
                    number_format((float) $row['stock_value'], 2),
                    $status,
                ];
            }
        }
        break;

    case 'low_stock':
        $where = ["p.status = 1"];
        if ($categoryId > 0) {
            $where[] = "p.categories_id = {$categoryId}";
        }
        if ($supplierId > 0) {
            $where[] = "COALESCE(rm.preferred_supplier_id, pb.supplier_id) = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $where[] = "p.product_name LIKE '%{$productEsc}%'";
        }

        $sql = "
            SELECT
                p.product_id,
                p.product_name,
                COALESCE(b.brand_name, '-') AS brand_name,
                COALESCE(p.reorder_level, 0) AS reorder_level,
                COALESCE(rm.reorder_quantity, 0) AS reorder_quantity,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS current_stock,
                COALESCE(s.supplier_name, '-') AS preferred_supplier
            FROM product p
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            LEFT JOIN product_batches pb ON pb.product_id = p.product_id
            LEFT JOIN reorder_management rm ON rm.product_id = p.product_id AND rm.is_active = 1
            LEFT JOIN suppliers s ON s.supplier_id = rm.preferred_supplier_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.product_id, p.product_name, b.brand_name, p.reorder_level, rm.reorder_quantity, s.supplier_name
            HAVING current_stock <= reorder_level
            ORDER BY current_stock ASC, p.product_name ASC
        ";

        $tableHeaders = ['Product ID', 'Product', 'Brand', 'Current Stock', 'Reorder Level', 'Suggested Reorder Qty', 'Preferred Supplier', 'Urgency'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $currentStock = (float) $row['current_stock'];
                $reorderLevel = (float) $row['reorder_level'];
                $urgency = $currentStock <= 0 ? 'Critical' : 'Low';
                $suggestedQty = (float) $row['reorder_quantity'] > 0
                    ? (float) $row['reorder_quantity']
                    : max($reorderLevel - $currentStock, 0);

                $reportRows[] = [
                    $row['product_id'],
                    $row['product_name'],
                    $row['brand_name'],
                    number_format($currentStock, 2),
                    number_format($reorderLevel, 2),
                    number_format($suggestedQty, 2),
                    $row['preferred_supplier'],
                    $urgency,
                ];
            }
        }
        break;

    case 'expiry_tracking':
        $where = [
            "pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL {$daysToExpiry} DAY)",
            "pb.status IN ('Active', 'Expired')",
        ];
        if ($categoryId > 0) {
            $where[] = "p.categories_id = {$categoryId}";
        }
        if ($supplierId > 0) {
            $where[] = "pb.supplier_id = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $where[] = "p.product_name LIKE '%{$productEsc}%'";
        }

        $sql = "
            SELECT
                pb.batch_id,
                p.product_name,
                COALESCE(b.brand_name, '-') AS brand_name,
                COALESCE(s.supplier_name, '-') AS supplier_name,
                pb.batch_number,
                pb.expiry_date,
                DATEDIFF(pb.expiry_date, CURDATE()) AS days_left,
                pb.available_quantity,
                pb.mrp,
                pb.status
            FROM product_batches pb
            INNER JOIN product p ON p.product_id = pb.product_id
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            LEFT JOIN suppliers s ON s.supplier_id = pb.supplier_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY pb.expiry_date ASC, p.product_name ASC
        ";

        $tableHeaders = ['Batch ID', 'Product', 'Brand', 'Supplier', 'Batch Number', 'Expiry Date', 'Days Left', 'Available Qty', 'MRP (INR)', 'Alert'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $daysLeft = (int) $row['days_left'];
                $alert = 'Safe';
                if ($daysLeft < 0) {
                    $alert = 'Expired';
                } elseif ($daysLeft <= 30) {
                    $alert = 'Critical';
                } elseif ($daysLeft <= 90) {
                    $alert = 'Warning';
                }

                $reportRows[] = [
                    $row['batch_id'],
                    $row['product_name'],
                    $row['brand_name'],
                    $row['supplier_name'],
                    $row['batch_number'],
                    date('d M Y', strtotime($row['expiry_date'])),
                    $daysLeft,
                    number_format((float) $row['available_quantity'], 2),
                    number_format((float) $row['mrp'], 2),
                    $alert,
                ];
            }
        }
        break;

    case 'stock_movements':
        $where = [
            "DATE(sm.movement_date) BETWEEN '{$startEsc}' AND '{$endEsc}'",
        ];
        if ($movementType !== '') {
            $movementEsc = $connect->real_escape_string($movementType);
            $where[] = "sm.movement_type = '{$movementEsc}'";
        }
        if ($categoryId > 0) {
            $where[] = "p.categories_id = {$categoryId}";
        }
        if ($supplierId > 0) {
            $where[] = "pb.supplier_id = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $where[] = "p.product_name LIKE '%{$productEsc}%'";
        }

        $sql = "
            SELECT
                sm.movement_id,
                sm.movement_date,
                sm.movement_type,
                p.product_name,
                COALESCE(pb.batch_number, '-') AS batch_number,
                sm.reference_number,
                sm.reference_type,
                sm.quantity,
                sm.balance_before,
                sm.balance_after,
                COALESCE(sm.reason, '-') AS reason
            FROM stock_movements sm
            INNER JOIN product p ON p.product_id = sm.product_id
            LEFT JOIN product_batches pb ON pb.batch_id = sm.batch_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sm.movement_date DESC, sm.movement_id DESC
        ";

        $tableHeaders = ['Movement ID', 'Date', 'Type', 'Product', 'Batch', 'Reference', 'Quantity', 'Before', 'After', 'Reason'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $type = (string) $row['movement_type'];
                if (isset($movementMix[$type])) {
                    $movementMix[$type] += (float) $row['quantity'];
                }

                $referenceText = trim((string) ($row['reference_type'] ?: ''));
                if ($row['reference_number']) {
                    $referenceText = trim($referenceText . ' ' . $row['reference_number']);
                }
                if ($referenceText === '') {
                    $referenceText = '-';
                }

                $reportRows[] = [
                    $row['movement_id'],
                    date('d M Y H:i', strtotime($row['movement_date'])),
                    $row['movement_type'],
                    $row['product_name'],
                    $row['batch_number'],
                    $referenceText,
                    number_format((float) $row['quantity'], 2),
                    number_format((float) $row['balance_before'], 2),
                    number_format((float) $row['balance_after'], 2),
                    $row['reason'],
                ];
            }
        }
        break;

    case 'batch_analysis':
        $where = ["p.status = 1"];
        if ($categoryId > 0) {
            $where[] = "p.categories_id = {$categoryId}";
        }
        if ($supplierId > 0) {
            $where[] = "pb.supplier_id = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $where[] = "p.product_name LIKE '%{$productEsc}%'";
        }

        $sql = "
            SELECT
                p.product_id,
                p.product_name,
                COALESCE(COUNT(pb.batch_id), 0) AS total_batches,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN 1 ELSE 0 END), 0) AS active_batches,
                COALESCE(SUM(CASE WHEN pb.status = 'Expired' THEN 1 ELSE 0 END), 0) AS expired_batches,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS total_qty,
                COALESCE(AVG(CASE WHEN pb.status = 'Active' THEN pb.mrp END), 0) AS avg_mrp,
                MIN(CASE WHEN pb.status = 'Active' THEN pb.expiry_date END) AS nearest_expiry,
                MAX(pb.created_at) AS last_batch_date
            FROM product p
            LEFT JOIN product_batches pb ON pb.product_id = p.product_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.product_id, p.product_name
            ORDER BY total_batches DESC, p.product_name ASC
        ";

        $tableHeaders = ['Product ID', 'Product', 'Total Batches', 'Active', 'Expired', 'Active Qty', 'Avg MRP (INR)', 'Nearest Expiry', 'Last Batch Added'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $reportRows[] = [
                    $row['product_id'],
                    $row['product_name'],
                    (int) $row['total_batches'],
                    (int) $row['active_batches'],
                    (int) $row['expired_batches'],
                    number_format((float) $row['total_qty'], 2),
                    number_format((float) $row['avg_mrp'], 2),
                    $row['nearest_expiry'] ? date('d M Y', strtotime($row['nearest_expiry'])) : '-',
                    $row['last_batch_date'] ? date('d M Y', strtotime($row['last_batch_date'])) : '-',
                ];
            }
        }
        break;

    case 'supplier_performance':
        $where = ["s.supplier_status = 'Active'"];
        if ($supplierId > 0) {
            $where[] = "s.supplier_id = {$supplierId}";
        }
        if ($productKeyword !== '') {
            $where[] = "s.supplier_name LIKE '%{$productEsc}%'";
        }

        $sql = "
            SELECT
                s.supplier_id,
                s.supplier_name,
                COALESCE(s.supplier_code, '-') AS supplier_code,
                COUNT(DISTINCT CASE WHEN pi.deleted_at IS NULL THEN pi.id END) AS purchase_invoices,
                COALESCE(SUM(CASE WHEN pi.deleted_at IS NULL AND pi.status <> 'Cancelled' THEN pi.grand_total ELSE 0 END), 0) AS purchase_value,
                COALESCE(SUM(CASE WHEN pi.deleted_at IS NULL AND pi.status <> 'Cancelled' THEN COALESCE(pi.outstanding_amount, pi.grand_total - COALESCE(pi.paid_amount, 0)) ELSE 0 END), 0) AS outstanding_value,
                COUNT(DISTINCT CASE WHEN pb.status = 'Active' THEN pb.batch_id END) AS active_batches,
                COALESCE(SUM(CASE WHEN pb.status = 'Active' THEN pb.available_quantity ELSE 0 END), 0) AS stock_units,
                MAX(pi.invoice_date) AS last_invoice_date
            FROM suppliers s
            LEFT JOIN purchase_invoices pi
                ON pi.supplier_id = s.supplier_id
               AND pi.invoice_date BETWEEN '{$startEsc}' AND '{$endEsc}'
            LEFT JOIN product_batches pb ON pb.supplier_id = s.supplier_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY s.supplier_id, s.supplier_name, s.supplier_code
            ORDER BY purchase_value DESC, s.supplier_name ASC
        ";

        $tableHeaders = ['Supplier ID', 'Supplier', 'Code', 'Invoices', 'Purchase Value (INR)', 'Outstanding (INR)', 'Active Batches', 'Stock Units', 'Last Invoice'];
        $result = $connect->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $reportRows[] = [
                    $row['supplier_id'],
                    $row['supplier_name'],
                    $row['supplier_code'],
                    (int) $row['purchase_invoices'],
                    number_format((float) $row['purchase_value'], 2),
                    number_format((float) $row['outstanding_value'], 2),
                    (int) $row['active_batches'],
                    number_format((float) $row['stock_units'], 2),
                    $row['last_invoice_date'] ? date('d M Y', strtotime($row['last_invoice_date'])) : '-',
                ];
            }
        }
        break;
}

$movementChartRows = [];
foreach ($movementMix as $mixType => $qty) {
    if ($qty > 0) {
        $movementChartRows[] = [$mixType, $qty];
    }
}
?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<style>
.inv-kpi-card {
    min-height: 122px;
}

.inv-kpi-value {
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1.1;
}

.inv-kpi-label {
    font-size: .88rem;
    opacity: .88;
}

.inventory-filter .form-control,
.inventory-filter .custom-select {
    height: 36px;
}

.report-note {
    font-size: .84rem;
    color: #6c757d;
}

.table-tools .dt-buttons {
    margin-bottom: .75rem;
}
</style>

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-7 align-self-center">
                <h3 class="text-primary mb-1"><i class="fa fa-bar-chart"></i> Inventory Report Center</h3>
                <small class="text-muted">Generate stock, expiry, movement, and supplier intelligence reports with export-ready output.</small>
            </div>
            <div class="col-md-5 text-right align-self-center">
                <a href="manage_medicine.php" class="btn btn-sm btn-info"><i class="fa fa-medkit"></i> Manage Medicine</a>
                <a href="manage_batches.php" class="btn btn-sm btn-primary"><i class="fa fa-cubes"></i> Batch Ledger</a>
                <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>

        <div class="card inventory-filter mb-3">
            <div class="card-body py-3">
                <form method="GET" class="form-row align-items-end">
                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>Report Type</strong></label>
                        <select name="type" id="reportType" class="custom-select">
                            <option value="inventory_summary" <?php echo $reportType === 'inventory_summary' ? 'selected' : ''; ?>>Inventory Summary</option>
                            <option value="low_stock" <?php echo $reportType === 'low_stock' ? 'selected' : ''; ?>>Low Stock Alert</option>
                            <option value="expiry_tracking" <?php echo $reportType === 'expiry_tracking' ? 'selected' : ''; ?>>Expiry Tracking</option>
                            <option value="stock_movements" <?php echo $reportType === 'stock_movements' ? 'selected' : ''; ?>>Stock Movements</option>
                            <option value="batch_analysis" <?php echo $reportType === 'batch_analysis' ? 'selected' : ''; ?>>Batch Analysis</option>
                            <option value="supplier_performance" <?php echo $reportType === 'supplier_performance' ? 'selected' : ''; ?>>Supplier Performance</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 movement-only supplier-date-only">
                        <label class="mb-1"><strong>Date From</strong></label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo ih($dateFrom); ?>">
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 movement-only supplier-date-only">
                        <label class="mb-1"><strong>Date To</strong></label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo ih($dateTo); ?>">
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>Category</strong></label>
                        <select name="category_id" class="custom-select">
                            <option value="0">All Categories</option>
                            <?php foreach ($categoryOptions as $category): ?>
                                <option value="<?php echo (int) $category['categories_id']; ?>" <?php echo (int) $category['categories_id'] === $categoryId ? 'selected' : ''; ?>>
                                    <?php echo ih($category['categories_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>Supplier</strong></label>
                        <select name="supplier_id" class="custom-select">
                            <option value="0">All Suppliers</option>
                            <?php foreach ($supplierOptions as $supplier): ?>
                                <option value="<?php echo (int) $supplier['supplier_id']; ?>" <?php echo (int) $supplier['supplier_id'] === $supplierId ? 'selected' : ''; ?>>
                                    <?php echo ih($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2">
                        <label class="mb-1"><strong>Product Search</strong></label>
                        <input type="text" name="product" class="form-control" value="<?php echo ih($productKeyword); ?>" placeholder="Name contains...">
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 summary-only">
                        <label class="mb-1"><strong>Stock State</strong></label>
                        <select name="stock_state" class="custom-select">
                            <option value="" <?php echo $stockState === '' ? 'selected' : ''; ?>>All</option>
                            <option value="healthy" <?php echo $stockState === 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                            <option value="low" <?php echo $stockState === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="out" <?php echo $stockState === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 movement-only">
                        <label class="mb-1"><strong>Movement Type</strong></label>
                        <select name="movement_type" class="custom-select">
                            <option value="">All Types</option>
                            <?php foreach ($allowedMovementTypes as $typeOption): ?>
                                <option value="<?php echo ih($typeOption); ?>" <?php echo $movementType === $typeOption ? 'selected' : ''; ?>>
                                    <?php echo ih($typeOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 expiry-only">
                        <label class="mb-1"><strong>Days To Expiry</strong></label>
                        <input type="number" min="1" max="365" name="days_to_expiry" class="form-control" value="<?php echo (int) $daysToExpiry; ?>">
                    </div>

                    <div class="col-xl-2 col-md-4 mb-2 text-right">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-filter"></i> Generate</button>
                        <a href="inventory_reports.php?type=<?php echo ih($reportType); ?>" class="btn btn-light btn-sm btn-block mt-1">Reset</a>
                    </div>
                </form>
                <div class="report-note mt-1">
                    Active report: <?php echo ih($activeTitle); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-info text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value"><?php echo number_format($totalProducts); ?></div>
                    <div class="inv-kpi-label">Active Products</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-primary text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value"><?php echo number_format($totalActiveBatches); ?></div>
                    <div class="inv-kpi-label">Active Batches</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-success text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value"><?php echo number_format($totalStockUnits, 0); ?></div>
                    <div class="inv-kpi-label">Stock Units</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-secondary text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value">INR <?php echo number_format($totalStockValue, 0); ?></div>
                    <div class="inv-kpi-label">Stock Value</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-warning text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value"><?php echo number_format($stockHealth['low_items']); ?></div>
                    <div class="inv-kpi-label">Low Stock Products</div>
                </div></div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card bg-danger text-white inv-kpi-card"><div class="card-body">
                    <div class="inv-kpi-value"><?php echo number_format($expiringSoon); ?></div>
                    <div class="inv-kpi-label">Expiring in <?php echo (int) $daysToExpiry; ?> Days</div>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header"><strong>Stock Health Snapshot</strong></div>
                    <div class="card-body"><div id="stockHealthChart" style="width:100%;height:300px;"></div></div>
                </div>
            </div>
            <div class="col-lg-6 mb-3 movement-chart-wrap" style="display: none;">
                <div class="card">
                    <div class="card-header"><strong>Movement Type Distribution</strong></div>
                    <div class="card-body"><div id="movementTypeChart" style="width:100%;height:300px;"></div></div>
                </div>
            </div>
        </div>

        <div class="card table-tools">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo ih($activeTitle); ?></strong>
                <small class="text-muted">Rows: <?php echo number_format(count($reportRows)); ?></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportTable" class="table table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <?php foreach ($tableHeaders as $header): ?>
                                    <th><?php echo ih($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reportRows)): ?>
                                <?php foreach ($reportRows as $reportRow): ?>
                                    <tr>
                                        <?php foreach ($reportRow as $cell): ?>
                                            <td><?php echo ih($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo max(count($tableHeaders), 1); ?>" class="text-center text-muted">
                                        No records found for selected filters.
                                    </td>
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
    var reportType = $('#reportType').val();

    function toggleFilterBlocks() {
        reportType = $('#reportType').val();
        $('.movement-only').toggle(reportType === 'stock_movements');
        $('.expiry-only').toggle(reportType === 'expiry_tracking');
        $('.summary-only').toggle(reportType === 'inventory_summary');
        $('.supplier-date-only').toggle(reportType === 'stock_movements' || reportType === 'supplier_performance');
        $('.movement-chart-wrap').toggle(reportType === 'stock_movements');
    }

    toggleFilterBlocks();
    $('#reportType').on('change', toggleFilterBlocks);

    if ($.fn.DataTable && $('#reportTable tbody tr').length > 0) {
        $('#reportTable').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'print']
        });
    }
});

google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(drawInventoryCharts);

function drawInventoryCharts() {
    var healthData = google.visualization.arrayToDataTable([
        ['Status', 'Products'],
        ['Healthy', <?php echo (int) $stockHealth['healthy_items']; ?>],
        ['Low', <?php echo (int) $stockHealth['low_items']; ?>],
        ['Out', <?php echo (int) $stockHealth['out_items']; ?>]
    ]);

    var healthOptions = {
        pieHole: 0.5,
        chartArea: { width: '90%', height: '78%' },
        legend: { position: 'bottom' },
        colors: ['#28a745', '#ffc107', '#dc3545']
    };

    var stockChart = new google.visualization.PieChart(document.getElementById('stockHealthChart'));
    stockChart.draw(healthData, healthOptions);

    var movementRows = <?php echo json_encode($movementChartRows, JSON_UNESCAPED_UNICODE); ?>;
    if (movementRows.length > 0 && document.getElementById('movementTypeChart')) {
        var movementData = new google.visualization.DataTable();
        movementData.addColumn('string', 'Movement Type');
        movementData.addColumn('number', 'Quantity');
        for (var i = 0; i < movementRows.length; i++) {
            movementData.addRow([movementRows[i][0], Number(movementRows[i][1])]);
        }

        var movementOptions = {
            chartArea: { width: '75%', height: '72%' },
            legend: { position: 'none' },
            colors: ['#007bff']
        };

        var movementChart = new google.visualization.ColumnChart(document.getElementById('movementTypeChart'));
        movementChart.draw(movementData, movementOptions);
    }
}

window.addEventListener('resize', function () {
    if (typeof drawInventoryCharts === 'function') {
        drawInventoryCharts();
    }
});
</script>
