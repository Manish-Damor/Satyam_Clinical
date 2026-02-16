<?php
include('../constant/connect.php');

if (isset($_POST['add_stock'])) {

  $product_id     = $_POST['product_id'];
  $batch_number   = trim($_POST['batch_number']);
  $expiry_date    = $_POST['expiry_date'];
  $quantity       = (int) $_POST['quantity'];
  $purchase_rate  = (float) $_POST['purchase_rate'];
  $mrp            = (float) $_POST['mrp'];

  // 1️⃣ Validate expiry
  if ($expiry_date <= date('Y-m-d')) {
    echo "Invalid expiry date";
    exit;
  }

  // 2️⃣ Check if batch already exists for same product
  $check = $connect->prepare(
    "SELECT batch_id, available_quantity
     FROM product_batches
     WHERE product_id = ?
       AND batch_number = ?
       AND expiry_date = ?"
  );
  $check->bind_param("iss", $product_id, $batch_number, $expiry_date);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    // Batch exists → update quantity
    $row = $result->fetch_assoc();

    $updateBatch = $connect->prepare(
      "UPDATE product_batches
       SET available_quantity = available_quantity + ?
       WHERE batch_id = ?"
    );
    $updateBatch->bind_param("ii", $quantity, $row['batch_id']);
    $updateBatch->execute();

  } else {
    // New batch → insert
    $insertBatch = $connect->prepare(
      "INSERT INTO product_batches
       (product_id, batch_number, expiry_date,
        available_quantity, purchase_rate, mrp, status)
       VALUES (?, ?, ?, ?, ?, ?, 'Active')"
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
  }

  // 3️⃣ Update total stock in product
  $updateProduct = $connect->prepare(
    "UPDATE product
     SET quantity = quantity + ?
     WHERE product_id = ?"
  );
  $updateProduct->bind_param("ii", $quantity, $product_id);
  $updateProduct->execute();

  header("Location: ../manage_medicine.php?success=1");
}
?>
