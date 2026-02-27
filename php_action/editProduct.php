<?php

require_once 'core.php';

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

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0 || !$_POST) {
    header('location:../manage_medicine.php?error=invalid_product');
    exit;
}

$productName    = trim($_POST['editProductName'] ?? '');
$content        = trim($_POST['editContent'] ?? '');
$productType    = trim($_POST['editProductType'] ?? 'Tablet');
$unitType       = trim($_POST['editUnitType'] ?? 'Strip');
$packSize       = trim($_POST['editPackSize'] ?? '');
$hsnCode        = trim($_POST['editHsnCode'] ?? '');
$gstRate        = (float) ($_POST['editGstRate'] ?? 0);
$reorderLevel   = (int) ($_POST['editReorderLevel'] ?? 0);
$brandName      = (int) ($_POST['editBrandName'] ?? 0);
$categoryName   = (int) ($_POST['editCategoryName'] ?? 0);
$productStatus  = (int) ($_POST['editProductStatus'] ?? 1);

$allowedProductTypes = getAllowedValues($connect, 'master_product_types', 'type_code', ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Drops', 'Others']);
$allowedUnitTypes = getAllowedValues($connect, 'master_unit_types', 'unit_code', ['Strip', 'Box', 'Bottle', 'Vial', 'Tube', 'Piece', 'Sachet']);
$allowedGstRates = array_map('floatval', getAllowedValues($connect, 'master_gst_slabs', 'gst_rate', ['0.00', '5.00', '12.00', '18.00', '28.00']));

if (
    $productName === '' ||
    $content === '' ||
    $packSize === '' ||
    $hsnCode === '' ||
    $brandName <= 0 ||
    $categoryName <= 0 ||
    !in_array($productType, $allowedProductTypes, true) ||
    !in_array($unitType, $allowedUnitTypes, true) ||
    !in_array((float) $gstRate, $allowedGstRates, true) ||
    $reorderLevel < 0 ||
    !in_array($productStatus, [1, 2], true)
) {
    header('location:../editproduct.php?id=' . $productId . '&error=invalid_input');
    exit;
}

$sql = "
    UPDATE product
    SET product_name = ?,
        content = ?,
        brand_id = ?,
        categories_id = ?,
        product_type = ?,
        unit_type = ?,
        pack_size = ?,
        hsn_code = ?,
        gst_rate = ?,
        reorder_level = ?,
        status = ?
    WHERE product_id = ?
";

$stmt = $connect->prepare($sql);
if (!$stmt) {
    header('location:../editproduct.php?id=' . $productId . '&error=db_prepare_failed');
    exit;
}

$stmt->bind_param(
    'ssiissssdiii',
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
    $productId
);

if ($stmt->execute()) {
    $stmt->close();
    $connect->close();
    header('location:../manage_medicine.php?success=updated');
    exit;
}

$stmt->close();
$connect->close();
header('location:../editproduct.php?id=' . $productId . '&error=update_failed');
exit;
