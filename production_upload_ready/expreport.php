<?php
$query = $_GET;
if (!isset($query['type']) || trim((string) $query['type']) === '') {
    $query['type'] = 'expiry_tracking';
}
if (!isset($query['days_to_expiry'])) {
    $query['days_to_expiry'] = 90;
}

$target = 'inventory_reports.php?' . http_build_query($query);
header('Location: ' . $target);
exit;
