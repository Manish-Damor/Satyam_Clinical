<?php
header('Content-Type: application/json');

require_once 'json_core.php';
require_once 'purchase_invoice_action.php';

try {
    $dateInput = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
    $reference = PurchaseInvoiceAction::getNextEntryReference($dateInput);
    echo json_encode(['success' => true, 'reference' => $reference]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Unable to generate reference']);
}
exit;
