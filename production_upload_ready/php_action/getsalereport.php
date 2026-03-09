<?php
require_once 'core.php';

$dateFrom = isset($_POST['startDate']) ? trim((string) $_POST['startDate']) : '';
$dateTo = isset($_POST['endDate']) ? trim((string) $_POST['endDate']) : '';

$params = [];
if (DateTime::createFromFormat('Y-m-d', $dateFrom) !== false) {
    $params['date_from'] = $dateFrom;
}
if (DateTime::createFromFormat('Y-m-d', $dateTo) !== false) {
    $params['date_to'] = $dateTo;
}

$target = '../sales_report.php';
if (!empty($params)) {
    $target .= '?' . http_build_query($params);
}

header('Location: ' . $target);
exit;
