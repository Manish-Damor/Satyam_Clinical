<?php
$query = $_GET;
if (!isset($query['type']) || trim((string) $query['type']) === '') {
    $query['type'] = 'inventory_summary';
}

$target = 'inventory_reports.php';
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}

header('Location: ' . $target);
exit;
