<?php
/**
 * DELETE CLIENT HANDLER
 * Prepared statements for security
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
    
    // Validate required fields
    if (empty($_POST['client_id'])) {
        throw new Exception('Client ID is required');
    }
    
    $clientId = (int)$_POST['client_id'];
    
    // Check if client has any active invoices
    $checkStmt = $connect->prepare("
        SELECT COUNT(*) as invoice_count 
        FROM sales_invoices 
        WHERE client_id = ? AND invoice_status != 'CANCELLED'
    ");
    
    $checkStmt->bind_param('i', $clientId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['invoice_count'] > 0) {
        throw new Exception('Cannot delete client with active invoices. Please cancel or complete all invoices first.');
    }
    
    // Delete the client using prepared statement
    $stmt = $connect->prepare("DELETE FROM clients WHERE client_id = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->bind_param('i', $clientId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Client deleted successfully';
        } else {
            throw new Exception('Client not found');
        }
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
