<?php

require_once 'core.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
	header('location:../manage_medicine.php?error=invalid_product');
	exit;
}

$checkSql = "
	SELECT COUNT(*) AS active_batches
	FROM product_batches
	WHERE product_id = ?
	  AND status = 'Active'
	  AND available_quantity > 0
";
$checkStmt = $connect->prepare($checkSql);
$checkStmt->bind_param('i', $productId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$checkRow = $checkRes ? $checkRes->fetch_assoc() : ['active_batches' => 0];
$checkStmt->close();

if ((int) $checkRow['active_batches'] > 0) {
	header('location:../manage_medicine.php?error=has_active_stock');
	exit;
}

$sql = "UPDATE product SET status = 2 WHERE product_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $productId);

if ($stmt->execute()) {
	header('location:../manage_medicine.php?success=removed');
} else {
	header('location:../manage_medicine.php?error=remove_failed');
}

$stmt->close();
$connect->close();
exit;