<?php
$dateFrom = isset($_POST['startDate']) ? trim((string) $_POST['startDate']) : '';
$dateTo = isset($_POST['endDate']) ? trim((string) $_POST['endDate']) : '';
$reportType = isset($_POST['report_type']) ? trim((string) $_POST['report_type']) : '';

$allowedTypes = ['inventory_summary', 'low_stock', 'expiry_tracking', 'stock_movements', 'batch_analysis', 'supplier_performance'];
if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = isset($_GET['type']) ? trim((string) $_GET['type']) : 'inventory_summary';
}
if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = 'inventory_summary';
}

$params = [
    'type' => $reportType,
];

if (DateTime::createFromFormat('Y-m-d', $dateFrom) !== false) {
    $params['date_from'] = $dateFrom;
}
if (DateTime::createFromFormat('Y-m-d', $dateTo) !== false) {
    $params['date_to'] = $dateTo;
}

$target = 'inventory_reports.php?' . http_build_query($params);
header('Location: ' . $target);
exit;
