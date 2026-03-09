<?php
// Debug test file to check AJAX POST
header('Content-Type: application/json');

// Log all incoming data
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'server_info' => [
        'php_version' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
];

// Log to file
$logFile = dirname(__FILE__) . '/debug.log';
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Test database connection
require_once 'core.php';

$dbTest = [
    'database' => 'Connected',
    'tables' => []
];

// Check tables
foreach(['purchase_orders', 'po_items', 'product'] as $table) {
    $result = $connect->query("SHOW TABLES LIKE '$table'");
    $dbTest['tables'][$table] = ($result && $result->num_rows > 0) ? 'EXISTS' : 'MISSING';
}

// If POST data exists, echo success
if($_POST) {
    echo json_encode([
        'success' => true,
        'message' => 'POST received successfully',
        'items_count' => count($_POST['items'] ?? []),
        'database_test' => $dbTest,
        'po_number' => $_POST['poNumber'] ?? 'NO PO NUMBER'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No POST data received',
        'database_test' => $dbTest
    ]);
}
?>
