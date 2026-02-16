<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => array()

);

if ($_POST) {

    // ===========================
    // Collect & Sanitize Inputs
    // ===========================

    $productName   = trim($_POST['productName']);
    $content       = trim($_POST['content']);
    $brandName     = $_POST['brandName'];
    $categoryName  = $_POST['categoryName'];
    $productType   = $_POST['product_type'];
    $unitType      = $_POST['unit_type'];
    $packSize      = trim($_POST['pack_size']);
    $hsnCode       = trim($_POST['hsn_code']);
    $gstRate       = $_POST['gst_rate'];
    $reorderLevel  = $_POST['reorder_level'];
    $productStatus = $_POST['productStatus'];

    $createdDate = date('Y-m-d H:i:s');

    // ===========================
    // Duplicate Check
    // ===========================

    $checkSql = "SELECT product_id FROM product 
                 WHERE product_name = ? AND brand_id = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("si", $productName, $brandName);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $valid['messages'] = "Medicine already exists for this manufacturer.";
        echo json_encode($valid);
        exit;
    }

    // ===========================
    // Insert Product
    // ===========================

    $insertSql = "INSERT INTO product 
        (product_name, content, brand_id, categories_id, 
         product_type, unit_type, pack_size, 
         hsn_code, gst_rate, reorder_level, 
         status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $connect->prepare($insertSql);

    $stmt->bind_param(
        "ssiisssdiiss",
        $productName,
        $content,
        $brandName,
        $categoryName,
        $productType,
        $unitType,
        $packSize,
        $hsnCode,
        $gstRate,
        $reorderLevel,
        $productStatus,
        $createdDate
    );

    if ($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Medicine Added Successfully.";
    } else {
        $valid['messages'] = "Error while adding medicine.";
    }

    $stmt->close();
    $checkStmt->close();
    $connect->close();

    echo json_encode($valid);
}
?>
