<?php
/**
 * CREATE CLIENT HANDLER
 * Prepared statements for security
 */

header('Content-Type: application/json');
require_once 'json_core.php';

$response = [
    'success' => false,
    'message' => ''
];

function generateNextClientCode(mysqli $connect): string
{
    $year = date('Y');
    $prefix = 'CL-' . $year . '-';
    $like = $prefix . '%';

    $stmt = $connect->prepare("\n        SELECT MAX(CAST(SUBSTRING_INDEX(client_code, '-', -1) AS UNSIGNED)) AS max_seq\n        FROM clients\n        WHERE client_code LIKE ?\n    ");
    if (!$stmt) {
        throw new Exception('Failed to generate client code: ' . $connect->error);
    }

    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $nextNum = ((int)($row['max_seq'] ?? 0)) + 1;
    return $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
}

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
    $clientCode = generateNextClientCode($connect);
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
    $businessType = trim((string)$_POST['business_type']);
    $status = isset($_POST['status']) ? trim((string)$_POST['status']) : 'ACTIVE';
    if (!in_array($status, ['ACTIVE', 'INACTIVE', 'SUSPENDED'], true)) {
        $status = 'ACTIVE';
    }
    $notes = $_POST['notes'] ?? null;
    $createdBy = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : null;
    
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
        'ssssssssssssdisssi',
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
    
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
