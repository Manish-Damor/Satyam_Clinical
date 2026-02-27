<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => array()

);

function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function finishResponse($valid)
{
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode($valid);
        exit;
    }

    if (!empty($valid['success'])) {
        header('Location: ../manage_medicine.php?success=created');
    } else {
        header('Location: ../add_medicine.php?error=' . urlencode((string) ($valid['messages'] ?? 'create_failed')));
    }
    exit;
}

function getAllowedValues($connect, $table, $column, $default)
{
    $allowed = $default;
    $check = $connect->query("SHOW TABLES LIKE '{$table}'");
    if ($check && $check->num_rows > 0) {
        $sql = "SELECT {$column} AS v FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC";
        $res = $connect->query($sql);
        if ($res && $res->num_rows > 0) {
            $allowed = [];
            while ($row = $res->fetch_assoc()) {
                $allowed[] = (string) $row['v'];
            }
        }
    }
    return $allowed;
}

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

    $allowedProductTypes = getAllowedValues($connect, 'master_product_types', 'type_code', ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Drops', 'Others']);
    $allowedUnitTypes = getAllowedValues($connect, 'master_unit_types', 'unit_code', ['Strip', 'Box', 'Bottle', 'Vial', 'Tube', 'Piece', 'Sachet']);
    $allowedGstRates = array_map('floatval', getAllowedValues($connect, 'master_gst_slabs', 'gst_rate', ['0.00', '5.00', '12.00', '18.00', '28.00']));

    if (
        $productName === '' ||
        $content === '' ||
        !in_array((string) $productType, $allowedProductTypes, true) ||
        !in_array((string) $unitType, $allowedUnitTypes, true) ||
        !in_array((float) $gstRate, $allowedGstRates, true) ||
        (int) $reorderLevel < 0 ||
        !in_array((int) $productStatus, [1, 2], true)
    ) {
        $valid['messages'] = 'Invalid medicine data provided.';
        finishResponse($valid);
    }

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
        finishResponse($valid);
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
        "ssiissssdiis",
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

    finishResponse($valid);
}
?>
