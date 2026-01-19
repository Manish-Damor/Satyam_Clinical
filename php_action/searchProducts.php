<?php 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'core.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if(empty($search)) {
    echo json_encode([]);
    exit;
}

// Sanitize search term
$search = '%' . $connect->real_escape_string($search) . '%';

$sql = "SELECT product_id as id, product_name as productName, rate as price FROM product WHERE status = 1 AND product_name LIKE ? ORDER BY product_name ASC LIMIT 10";

$stmt = $connect->prepare($sql);
if(!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $connect->error]);
    exit;
}

$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();

$products = array();
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = array(
            'id' => intval($row['id']),
            'productName' => htmlspecialchars($row['productName']),
            'price' => floatval($row['price'])
        );
    }
}

echo json_encode($products);
$stmt->close();
?>
