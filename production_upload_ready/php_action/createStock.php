<?php
include('../constant/connect.php');

header('Location: ../purchase_invoice.php?error=manual_stock_entry_disabled');
exit;

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (isset($_POST['add_stock'])) {

  $product_id     = (int) ($_POST['product_id'] ?? 0);
  $batch_number   = trim($_POST['batch_number']);
  $expiry_date    = $_POST['expiry_date'];
  $quantity       = (int) $_POST['quantity'];
  $purchase_rate  = (float) $_POST['purchase_rate'];
  $mrp            = (float) $_POST['mrp'];
  $user_id        = isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : null;

  if ($product_id <= 0 || $batch_number === '' || $quantity <= 0 || $purchase_rate <= 0 || $mrp <= 0) {
    header("Location: ../addProductStock.php?error=invalid_input");
    exit;
  }

  // 1️⃣ Validate expiry
  if ($expiry_date <= date('Y-m-d')) {
    header("Location: ../addProductStock.php?error=invalid_expiry");
    exit;
  }

  $connect->begin_transaction();

  try {
    $productCheck = $connect->prepare("SELECT product_id FROM product WHERE product_id = ? AND status = 1");
    $productCheck->bind_param("i", $product_id);
    $productCheck->execute();
    $productExists = $productCheck->get_result();
    $productCheck->close();

    if (!$productExists || $productExists->num_rows === 0) {
      throw new Exception('invalid_product');
    }

    // 2️⃣ Check if batch already exists for same product and lock it
    $check = $connect->prepare(
      "SELECT batch_id, expiry_date
       FROM product_batches
       WHERE product_id = ?
         AND batch_number = ?
       FOR UPDATE"
    );
    $check->bind_param("is", $product_id, $batch_number);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      if ($row['expiry_date'] !== $expiry_date) {
        throw new Exception('batch_expiry_mismatch');
      }

      $batch_id = (int) $row['batch_id'];
      $updateBatch = $connect->prepare(
        "UPDATE product_batches
         SET available_quantity = available_quantity + ?,
             purchase_rate = ?,
             mrp = ?,
             status = 'Active',
             updated_at = CURRENT_TIMESTAMP
         WHERE batch_id = ?"
      );
      $updateBatch->bind_param("iddi", $quantity, $purchase_rate, $mrp, $batch_id);
      $updateBatch->execute();
      $updateBatch->close();
    } else {
      $insertBatch = $connect->prepare(
        "INSERT INTO product_batches
         (product_id, batch_number, expiry_date,
          available_quantity, reserved_quantity, damaged_quantity,
          purchase_rate, mrp, status)
         VALUES (?, ?, ?, ?, 0, 0, ?, ?, 'Active')"
      );
      $insertBatch->bind_param(
        "issidd",
        $product_id,
        $batch_number,
        $expiry_date,
        $quantity,
        $purchase_rate,
        $mrp
      );
      $insertBatch->execute();
      $batch_id = (int) $insertBatch->insert_id;
      $insertBatch->close();
    }
    $check->close();

    $movementSql = "
      INSERT INTO stock_movements
      (product_id, batch_id, movement_type, quantity, reference_number, reference_type, reason, created_by, created_at)
      VALUES (?, ?, 'Purchase', ?, ?, 'Stock Entry', 'Manual stock addition', ?, NOW())
    ";
    $movementStmt = $connect->prepare($movementSql);
    $movementStmt->bind_param("iiisi", $product_id, $batch_id, $quantity, $batch_number, $user_id);
    $movementStmt->execute();
    $movementStmt->close();

    $connect->commit();
    header("Location: ../manage_medicine.php?success=stock_added");
    exit;
  } catch (Exception $e) {
    $connect->rollback();

    $errorCode = $e->getMessage();
    if ($errorCode === 'invalid_product') {
      header("Location: ../addProductStock.php?error=invalid_product");
    } elseif ($errorCode === 'batch_expiry_mismatch') {
      header("Location: ../addProductStock.php?error=batch_expiry_mismatch");
    } else {
      header("Location: ../addProductStock.php?error=stock_save_failed");
    }
    exit;
  }
}
?>
