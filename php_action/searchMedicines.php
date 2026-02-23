<?php
header('Content-Type: application/json');
require_once 'core.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if(strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $connect->real_escape_string($search) . '%';
// Use `product` table for medicine/product lookup — product holds canonical medicine data
// product table columns: product_id, product_name, content, pack_size, hsn_code, gst_rate, expected_mrp
$sql = "SELECT product_id AS medicine_id, product_name, pack_size, content AS manufacturer_name,
    hsn_code, expected_mrp AS mrp, expected_mrp AS ptr, '' AS current_batch_number, '' AS current_expiry_date, gst_rate
    FROM product
    WHERE status = 1 AND (product_name LIKE ? OR product_name LIKE ? OR hsn_code LIKE ?)
    ORDER BY product_name ASC
    LIMIT 30";

$stmt = $connect->prepare($sql);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while($row = $result->fetch_assoc()) {
    $medicines[] = [
        'medicine_id' => intval($row['medicine_id']),
        'medicine_code' => '',
        'medicine_name' => $row['product_name'],
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
