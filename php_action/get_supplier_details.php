<?php
header('Content-Type: application/json');

include('../constant/connect.php');

try {
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    
    if ($supplier_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid supplier ID']);
        exit;
    }

    $query = "SELECT supplier_id, company_name, contact_person, email, phone, address, city, state, gst_number, credit_days, payment_terms, supplier_status 
              FROM suppliers WHERE supplier_id = ?";
    
    $stmt = $connect->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare error: ' . $connect->error]);
        exit;
    }

    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Supplier not found']);
        exit;
    }
    
    $data = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'company_name' => $data['company_name'] ?? '',
            'contact_person' => $data['contact_person'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'gst_number' => $data['gst_number'] ?? '',
            'credit_days' => intval($data['credit_days'] ?? 30),
            'payment_terms' => $data['payment_terms'] ?? ''
        ]
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
}

$connect->close();
?>
