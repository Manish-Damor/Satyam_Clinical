<?php
include('../constant/connect.php');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => null,
    'error' => null
];

$supplierId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$supplierId) {
    http_response_code(400);
    $response['error'] = 'Invalid supplier ID';
    echo json_encode($response);
    exit;
}

$sql = "SELECT contact_person, email, phone, gst_number,
                    address, city, state, pincode, payment_terms
            FROM suppliers WHERE supplier_id = $supplierId";
$result = $connect->query($sql);

if ($result && $result->num_rows > 0) {
    $response['success'] = true;
    $response['data'] = $result->fetch_assoc();
} else {
    http_response_code(404);
    $response['error'] = 'Supplier not found';
}

echo json_encode($response);
exit;
?>
