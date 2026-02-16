<?php
header('Content-Type: application/json');
require_once 'core.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if(strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $connect->real_escape_string($search) . '%';
$sql = "SELECT medicine_id, medicine_code, medicine_name, pack_size, manufacturer_name,
        hsn_code, mrp, ptr, current_batch_number, current_expiry_date, gst_rate
        FROM medicine_details 
        WHERE is_active = 1 AND (medicine_name LIKE ? OR medicine_code LIKE ? OR hsn_code LIKE ?)
        ORDER BY medicine_name ASC 
        LIMIT 30";

$stmt = $connect->prepare($sql);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while($row = $result->fetch_assoc()) {
    $medicines[] = [
        'medicine_id' => intval($row['medicine_id']),
        'medicine_code' => $row['medicine_code'],
        'medicine_name' => $row['medicine_name'],
        'pack_size' => $row['pack_size'],
        'manufacturer_name' => $row['manufacturer_name'],
        'hsn_code' => $row['hsn_code'],
        'mrp' => floatval($row['mrp']),
        'ptr' => floatval($row['ptr']),
        'current_batch_number' => $row['current_batch_number'],
        'current_expiry_date' => $row['current_expiry_date'],
        'gst_rate' => floatval($row['gst_rate'])
    ];
}

echo json_encode($medicines);
$stmt->close();
$connect->close();
?>
