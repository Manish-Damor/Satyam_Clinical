<?php
$query = $_GET;
$target = 'sales_report.php';
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}

header('Location: ' . $target);
exit;
