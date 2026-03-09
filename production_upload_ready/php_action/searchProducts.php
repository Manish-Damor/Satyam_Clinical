<?php 
header('Content-Type: application/json');
include('../constant/connect.php');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if(empty($search)) {
    echo json_encode([]);
    exit;
}

// Search products
$search = '%' . $connect->real_escape_string($search) . '%';

$sql = "SELECT product_id, product_name, hsn_code, pack_size, gst_rate, rate 
        FROM product 
        WHERE product_name LIKE ? 
        AND product_status = 'Active'
        ORDER BY product_name ASC 
        LIMIT 15";

$stmt = $connect->prepare($sql);
if(!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();

$products = [];

if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = [
            'product_id' => intval($row['product_id']),
                'product_name' => htmlspecialchars($row['product_name']),
                'hsn_code' => $row['hsn_code'],
                'pack_size' => $row['pack_size'],
                'gst_rate' => floatval($row['gst_rate']),
                'rate' => floatval($row['rate'])
        ];
    }
}
echo json_encode($products);
$stmt->close();
?>



<?php 
// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// require_once 'core.php';

// $search = isset($_GET['q']) ? trim($_GET['q']) : '';

// if(empty($search)) {
//     echo json_encode([]);
//     exit;
// }

// // 🔥 Normalize input: remove spaces, lowercase
// $normalized = strtolower(preg_replace('/\s+/', '', $search));

// // Prepare SQL (ignore spaces & case in DB column)
// $sql = "SELECT product_id AS id, 
//                product_name AS productName, 
//                rate AS price, 
//                quantity 
//         FROM product 
//         WHERE status = 1 
//         AND REPLACE(LOWER(product_name), ' ', '') LIKE ?
//         ORDER BY product_name ASC 
//         LIMIT 10";

// $stmt = $connect->prepare($sql);
// if(!$stmt) {
//     echo json_encode(['error' => 'Prepare failed: ' . $connect->error]);
//     exit;
// }

// // Add wildcards after normalization
// $like = "%" . $normalized . "%";

// $stmt->bind_param('s', $like);
// $stmt->execute();
// $result = $stmt->get_result();

// $products = [];

// while($row = $result->fetch_assoc()) {
//     $products[] = [
//         'id' => intval($row['id']),
//         'productName' => $row['productName'],
//         'price' => floatval($row['price']),
//         'quantity' => intval($row['quantity'])
//     ];
// }

// echo json_encode($products);

// $stmt->close();
// $connect->close();
?>



<?php 
// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// require_once 'core.php';

// $search = isset($_GET['q']) ? trim($_GET['q']) : '';

// if(empty($search)) {
//     echo json_encode([]);
//     exit;
// }

// // Sanitize search term
// $search = '%' . $connect->real_escape_string($search) . '%';

// $sql = "SELECT product_id as id, product_name as productName, rate as price FROM product WHERE status = 1 AND product_name LIKE ? ORDER BY product_name ASC LIMIT 10";

// $stmt = $connect->prepare($sql);
// if(!$stmt) {
//     echo json_encode(['error' => 'Prepare failed: ' . $connect->error]);
//     exit;
// }

// $stmt->bind_param('s', $search);
// $stmt->execute();
// $result = $stmt->get_result();

// $products = array();
// if($result && $result->num_rows > 0) {
//     while($row = $result->fetch_assoc()) {
//         $products[] = array(
//             'id' => intval($row['id']),
//             'productName' => htmlspecialchars($row['productName']),
//             'price' => floatval($row['price'])
//         );
//     }
// }

// echo json_encode($products);
// $stmt->close();
?>
