<?php
/**
 * UPDATE CLIENT HANDLER
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
    
    if (empty($_POST['name'])) {
        throw new Exception('Client name is required');
    }
    
    if (empty($_POST['business_type'])) {
        throw new Exception('Business type is required');
    }
    
    // Prepare data
    $clientId = (int)$_POST['client_id'];
    $clientCode = $_POST['client_code'] ?? null;
    $name = trim($_POST['name']);
    $contactPhone = $_POST['contact_phone'] ?? null;
    $email = $_POST['email'] ?? null;
    $billingAddress = $_POST['billing_address'] ?? null;
    $shippingAddress = $_POST['shipping_address'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $postalCode = $_POST['postal_code'] ?? null;
    $country = $_POST['country'] ?? 'India';
    $gstin = $_POST['gstin'] ?? null;
    $pan = $_POST['pan'] ?? null;
    $creditLimit = (float)($_POST['credit_limit'] ?? 0);
    $paymentTerms = (int)($_POST['payment_terms'] ?? 30);
    $businessType = $_POST['business_type'];
    $status = $_POST['status'] ?? 'ACTIVE';
    $notes = $_POST['notes'] ?? null;
    $updatedBy = $_SESSION['userId'] ?? null;
    
    // Update in database using prepared statement
    $stmt = $connect->prepare("
        UPDATE clients 
        SET client_code = ?, name = ?, contact_phone = ?, email = ?, 
            billing_address = ?, shipping_address = ?, city = ?, state = ?, 
            postal_code = ?, country = ?, gstin = ?, pan = ?, credit_limit = ?, 
            payment_terms = ?, business_type = ?, status = ?, notes = ?, 
            updated_by = ?, updated_at = NOW()
        WHERE client_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->bind_param(
        'ssssssssssssddssii',
        $clientCode,
        $name,
        $contactPhone,
        $email,
        $billingAddress,
        $shippingAddress,
        $city,
        $state,
        $postalCode,
        $country,
        $gstin,
        $pan,
        $creditLimit,
        $paymentTerms,
        $businessType,
        $status,
        $notes,
        $updatedBy,
        $clientId
    );
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Client '{$name}' updated successfully";
        } else {
            throw new Exception('No changes made or client not found');
        }
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
