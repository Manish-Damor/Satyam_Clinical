<?php
/**
 * FETCH CLIENTS HANDLER
 * Returns JSON for AJAX requests
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'data' => []
];

try {
    // Fetch all active clients (not deleted)
    $stmt = $connect->prepare("
        SELECT 
            client_id, client_code, name, contact_phone, email,
            billing_address, shipping_address, city, state, postal_code, country,
            gstin, pan, credit_limit, outstanding_balance, payment_terms,
            business_type, status, notes,
            DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
        FROM clients
        ORDER BY name ASC
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $clients;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
