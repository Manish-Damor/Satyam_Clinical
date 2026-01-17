<?php 
header('Content-Type: application/json');
require_once 'core.php';

$sql = "SELECT product_id as id, product_name as productName FROM product WHERE status = 1 ORDER BY product_name ASC";
$result = $connect->query($sql);

$products = array();
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = array(
            'id' => intval($row['id']),
            'productName' => htmlspecialchars($row['productName'])
        );
    }
}

echo json_encode($products);
$connect->close();
?>
