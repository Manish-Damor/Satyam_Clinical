<?php
require_once 'core.php';

header('Location: ../purchase_invoice.php?error=manual_batch_entry_disabled');
exit;

$valid = array('success' => false, 'message' => '');

function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function finishResponse($valid, $product_id = 0)
{
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode($valid);
        exit;
    }

    if (!empty($valid['success'])) {
        header('Location: ../manage_batches.php?product_id=' . (int) $product_id . '&success=batch_saved');
    } else {
        header('Location: ../add_batch.php?product_id=' . (int) $product_id . '&error=' . urlencode($valid['message'] ?: 'batch_save_failed'));
    }
    exit;
}

if ($_POST) {
    // Sanitize inputs
    $product_id = intval($_POST['product_id'] ?? 0);
    $batch_number = trim($_POST['batch_number'] ?? '');
    $manufacturing_date = $_POST['manufacturing_date'] ?? null;
    if ($manufacturing_date === '') {
        $manufacturing_date = null;
    }
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    
    $available_quantity = intval($_POST['available_quantity'] ?? 0);
    $reserved_quantity = intval($_POST['reserved_quantity'] ?? 0);
    $damaged_quantity = intval($_POST['damaged_quantity'] ?? 0);
    
    $purchase_rate = floatval($_POST['purchase_rate'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if ($product_id <= 0) {
        $valid['message'] = 'Invalid product selected.';
        finishResponse($valid, $product_id);
    }

    if (empty($batch_number)) {
        $valid['message'] = 'Batch number is required.';
        finishResponse($valid, $product_id);
    }

    if (empty($expiry_date)) {
        $valid['message'] = 'Expiry date is required.';
        finishResponse($valid, $product_id);
    }

    if ($expiry_date <= date('Y-m-d')) {
        $valid['message'] = 'Expiry date must be in the future.';
        finishResponse($valid, $product_id);
    }

    if (!empty($manufacturing_date) && $manufacturing_date > $expiry_date) {
        $valid['message'] = 'Manufacturing date cannot be after expiry date.';
        finishResponse($valid, $product_id);
    }

    if ($available_quantity <= 0) {
        $valid['message'] = 'Available quantity must be greater than 0.';
        finishResponse($valid, $product_id);
    }

    if ($purchase_rate <= 0 || $mrp <= 0) {
        $valid['message'] = 'Purchase rate and MRP must be greater than 0.';
        finishResponse($valid, $product_id);
    }

    // Check for duplicate batch number for this product
    $checkSql = "SELECT batch_id FROM product_batches
                 WHERE product_id = ? AND batch_number = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("is", $product_id, $batch_number);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $valid['message'] = 'This batch number already exists for this medicine.';
        $checkStmt->close();
        finishResponse($valid, $product_id);
    }
    $checkStmt->close();

    $productCheckStmt = $connect->prepare("SELECT product_id FROM product WHERE product_id = ? AND status = 1");
    $productCheckStmt->bind_param('i', $product_id);
    $productCheckStmt->execute();
    $productExists = $productCheckStmt->get_result();
    $productCheckStmt->close();

    if (!$productExists || $productExists->num_rows === 0) {
        $valid['message'] = 'Product is not active or does not exist.';
        finishResponse($valid, $product_id);
    }

    // Prepare supplier_id (nullable)
    $supplier_id_val = ($supplier_id > 0) ? $supplier_id : null;

    $connect->begin_transaction();

    // Insert batch
    $insertSql = "INSERT INTO product_batches
        (product_id, supplier_id, batch_number, manufacturing_date, expiry_date, 
         available_quantity, reserved_quantity, damaged_quantity,
         purchase_rate, mrp, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($insertSql);

    if (!$stmt) {
        $valid['message'] = 'Database error: ' . $connect->error;
        $connect->rollback();
        finishResponse($valid, $product_id);
    }

    $stmt->bind_param(
        "iisssiiidds",
        $product_id,
        $supplier_id_val,
        $batch_number,
        $manufacturing_date,
        $expiry_date,
        $available_quantity,
        $reserved_quantity,
        $damaged_quantity,
        $purchase_rate,
        $mrp,
        $status
    );

    if ($stmt->execute()) {
        $batch_id = $stmt->insert_id;

        // Record stock movement
        $recordedBy = isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : null;
        $movementSql = "INSERT INTO stock_movements
            (product_id, batch_id, movement_type, quantity,
             reference_number, reference_type, reason, created_by, created_at)
            VALUES (?, ?, 'Purchase', ?, ?, 'Batch', 'New batch added', ?, NOW())";
        
        $movementStmt = $connect->prepare($movementSql);
        $movementStmt->bind_param("iiisi", $product_id, $batch_id, $available_quantity, $batch_number, $recordedBy);
        if (!$movementStmt->execute()) {
            $movementStmt->close();
            $stmt->close();
            $connect->rollback();
            $valid['message'] = 'Batch saved, but stock movement failed: ' . $connect->error;
            finishResponse($valid, $product_id);
        }
        $movementStmt->close();

        $connect->commit();

        $valid['success'] = true;
        $valid['message'] = 'Batch added successfully.';
        $valid['redirect'] = 'manage_batches.php?product_id=' . $product_id;
    } else {
        $connect->rollback();
        $valid['message'] = 'Error while adding batch: ' . $stmt->error;
    }

    $stmt->close();
    $connect->close();
}

finishResponse($valid, isset($product_id) ? $product_id : 0);
?>
