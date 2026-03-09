<?php
/**
 * DELETE SALES INVOICE
 * Soft delete with prepared statements
 */

header('Content-Type: application/json');
require_once 'json_core.php';

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (empty($_POST['invoice_id'])) {
        throw new Exception('Invoice ID is required');
    }
    
    $invoiceId = intval($_POST['invoice_id']);
    $sessionUserId = $_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null);
    $userId = (is_numeric($sessionUserId) && intval($sessionUserId) > 0)
        ? intval($sessionUserId)
        : 0;

    if ($userId <= 0 && PHP_SAPI === 'cli') {
        $userId = 1;
    }

    if ($userId <= 0) {
        throw new Exception('Unauthorized user session');
    }
    
    // Soft delete: set deleted_at timestamp
    $deleteInvoice = $connect->prepare("
        UPDATE sales_invoices 
        SET deleted_at = NOW(), updated_by = ?
        WHERE invoice_id = ?
    ");
    
    $deleteInvoice->bind_param('ii', $userId, $invoiceId);
    
    if ($deleteInvoice->execute()) {
        if ($deleteInvoice->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Invoice deleted successfully';
        } else {
            throw new Exception('Invoice not found');
        }
    } else {
        throw new Exception('Failed to delete invoice: ' . $deleteInvoice->error);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
