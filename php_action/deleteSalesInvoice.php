<?php
/**
 * DELETE SALES INVOICE
 * Soft delete with prepared statements
 */

header('Content-Type: application/json');
require '../constant/connect.php';

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
    $userId = $_SESSION['userId'] ?? null;
    
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
