<?php
/**
 * CREATE CLIENT HANDLER
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
    if (empty($_POST['name'])) {
        throw new Exception('Client name is required');
    }
    
    if (empty($_POST['business_type'])) {
        throw new Exception('Business type is required');
    }
    
    // Prepare data
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
    $createdBy = $_SESSION['userId'] ?? null;
    
    // Generate client code if not provided
    if (empty($clientCode)) {
        $result = $connect->query("SELECT MAX(CAST(SUBSTRING(client_code, 3) AS UNSIGNED)) as maxNum FROM clients WHERE client_code LIKE 'CL%'");
        $row = $result->fetch_assoc();
        $nextNum = ($row['maxNum'] ?? 0) + 1;
        $clientCode = 'CL' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
    
    // Insert into database using prepared statement
    $stmt = $connect->prepare("
        INSERT INTO clients 
        (client_code, name, contact_phone, email, billing_address, shipping_address, 
         city, state, postal_code, country, gstin, pan, credit_limit, payment_terms, 
         business_type, status, notes, created_by, created_at)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->bind_param(
        'ssssssssssssddssi',
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
        $createdBy
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Client '{$name}' created successfully with code {$clientCode}";
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
