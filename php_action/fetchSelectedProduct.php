<?php 	

require_once 'core.php';

$productId = $_POST['productId'];

// fetch basic product fields + selling rate
$sql = "SELECT product_id, product_name, product_image, brand_id, categories_id, quantity, rate, active, status FROM product WHERE product_id = $productId";

// also determine a purchase rate (PTR) from the most recent batch for this product
$batchSql = "SELECT purchase_rate FROM product_batches WHERE product_id = $productId ORDER BY batch_id DESC LIMIT 1";
$batchRes = $connect->query($batchSql);
$purchase_rate = 0;
if ($batchRes && $batchRes->num_rows > 0) {
    $br = $batchRes->fetch_assoc();
    $purchase_rate = $br['purchase_rate'];
}
$result = $connect->query($sql);

if($result->num_rows > 0) { 
 $row = $result->fetch_array();
} else {
    $row = [];
}
$row['purchase_rate'] = $purchase_rate;

$connect->close();

echo json_encode($row);