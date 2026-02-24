<?php
require '../constant/connect.php';
$batch = $connect->query("SELECT batch_id, product_id, available_quantity FROM product_batches WHERE available_quantity>0 LIMIT 1")->fetch_assoc();
var_export($batch);
