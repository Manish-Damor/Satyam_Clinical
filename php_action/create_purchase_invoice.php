<?php
require_once 'core.php';
require_once 'purchase_invoice_action.php';
header('Content-Type: application/json');

// Ensure session
if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Read JSON payload properly
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON received']);
    exit;
}

// Ensure items array exists
if (!isset($data['items'])) {
    $data['items'] = [];
}

// Call business logic with validation
$result = PurchaseInvoiceAction::createInvoice($data, $data['items']);
echo json_encode($result);
exit;

?>
