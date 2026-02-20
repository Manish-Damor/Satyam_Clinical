<?php
require_once 'core.php';

$valid = array('success' => false, 'message' => '');

if ($_POST) {
    // Sanitize inputs
    $product_id = intval($_POST['product_id'] ?? 0);
    $batch_number = trim($_POST['batch_number'] ?? '');
    $manufacturing_date = $_POST['manufacturing_date'] ?? null;
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
        echo json_encode($valid);
        exit;
    }

    if (empty($batch_number)) {
        $valid['message'] = 'Batch number is required.';
        echo json_encode($valid);
        exit;
    }

    if (empty($expiry_date)) {
        $valid['message'] = 'Expiry date is required.';
        echo json_encode($valid);
        exit;
    }

    if ($available_quantity <= 0) {
        $valid['message'] = 'Available quantity must be greater than 0.';
        echo json_encode($valid);
        exit;
    }

    if ($purchase_rate <= 0 || $mrp <= 0) {
        $valid['message'] = 'Purchase rate and MRP must be greater than 0.';
        echo json_encode($valid);
        exit;
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
        echo json_encode($valid);
        exit;
    }
    $checkStmt->close();

    // Prepare supplier_id (0 if not selected)
    $supplier_id_val = ($supplier_id > 0) ? $supplier_id : null;

    // Insert batch
    $insertSql = "INSERT INTO product_batches 
        (product_id, supplier_id, batch_number, manufacturing_date, expiry_date, 
         available_quantity, reserved_quantity, damaged_quantity,
         purchase_rate, mrp, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($insertSql);

    if (!$stmt) {
        $valid['message'] = 'Database error: ' . $connect->error;
        echo json_encode($valid);
        exit;
    }

    // Handle null supplier_id
    if ($supplier_id_val === null) {
        $stmt->bind_param(
            "issssiiiidis",
            $product_id,
            $supplier_id_null,
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
        $supplier_id_null = null;
    } else {
        $stmt->bind_param(
            "iisssiiiidis",
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
    }

    if ($stmt->execute()) {
        $batch_id = $stmt->insert_id;

        // Record stock movement
        $movementSql = "INSERT INTO stock_movements 
            (product_id, batch_id, movement_type, quantity, 
             reference_number, reference_type, reason, created_at)
            VALUES (?, ?, 'Purchase', ?, ?, 'Batch', 'New batch added', NOW())";
        
        $movementStmt = $connect->prepare($movementSql);
        $movementStmt->bind_param("iis", $product_id, $batch_id, $batch_number);
        $movementStmt->execute();
        $movementStmt->close();

        $valid['success'] = true;
        $valid['message'] = 'Batch added successfully.';
        $valid['redirect'] = 'manage_batches.php?product_id=' . $product_id;
    } else {
        $valid['message'] = 'Error while adding batch: ' . $stmt->error;
    }

    $stmt->close();
    $connect->close();
}

header('Content-Type: application/json');
echo json_encode($valid);
?>
