<?php
/**
 * FETCH SALES INVOICES
 * Returns all invoices with client details for listing
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'data' => []
];

try {
    // Fetch invoices with client details
    $stmt = $connect->prepare("
        SELECT 
            si.invoice_id,
            si.invoice_number,
            si.invoice_date,
            si.grand_total,
            si.invoice_status,
            si.payment_status,
            si.paid_amount,
            si.due_amount,
            c.name as client_name,
            c.contact_phone
        FROM sales_invoices si
        LEFT JOIN clients c ON si.client_id = c.client_id
        WHERE si.deleted_at IS NULL
        ORDER BY si.invoice_date DESC
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $invoices;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
