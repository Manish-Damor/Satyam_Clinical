<?php 	

require_once 'core.php';

$productId = $_POST['productId'];

// fetch basic product fields + selling rate + GST rate
$sql = "SELECT product_id, product_name, product_image, brand_id, categories_id, quantity, rate, gst_rate, active, status FROM product WHERE product_id = $productId";

// also determine a purchase rate (PTR) from the most recent batch for this product
$batchSql = "SELECT purchase_rate FROM product_batches WHERE product_id = $productId ORDER BY batch_id DESC LIMIT 1";
$batchRes = $connect->query($batchSql);
$purchase_rate = 0;
if ($batchRes && $batchRes->num_rows > 0) {
    $br = $batchRes->fetch_assoc();
    $purchase_rate = $br['purchase_rate'];
}

// fetch all available batches for this product
$batchesSql = "SELECT batch_id, batch_number, expiry_date, available_quantity, status FROM product_batches 
               WHERE product_id = $productId AND status = 'active' AND available_quantity > 0 
               ORDER BY expiry_date ASC";
$batchesRes = $connect->query($batchesSql);
$batches = [];
if ($batchesRes && $batchesRes->num_rows > 0) {
    while ($batchRow = $batchesRes->fetch_assoc()) {
        $batches[] = $batchRow;
    }
}

$result = $connect->query($sql);

if($result->num_rows > 0) { 
 $row = $result->fetch_array();
} else {
    $row = [];
}
$row['purchase_rate'] = $purchase_rate;
$row['batches'] = $batches;  // Add batches array to response

$connect->close();

echo json_encode($row);