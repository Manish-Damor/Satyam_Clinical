<?php
header('Content-Type: application/json');
require_once 'core.php';

$valid = ['success' => false, 'messages' => ''];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// If no JSON input, try POST data (for backwards compatibility)
if (!$input) {
    $input = $_POST ?? [];
}

if ($input) {

  // Collect inputs
  $poNumber = $input['poNumber'] ?? '';
  $poDate = $input['poDate'] ?? '';
  $vendorName = $input['vendorName'] ?? '';
  $vendorContact = $input['vendorContact'] ?? '';
  $vendorEmail = $input['vendorEmail'] ?? '';
  $vendorAddress = $input['vendorAddress'] ?? '';
  $deliveryDate = $input['deliveryDate'] ?? '';
  $poStatus = $input['poStatus'] ?? 'Pending';
  $subTotal = floatval($input['subTotal'] ?? 0);
  $discount = floatval($input['discount'] ?? 0);
  $gst = floatval($input['gst'] ?? 0);
  $grandTotal = floatval($input['grandTotal'] ?? 0);
  $paymentStatus = $input['paymentStatus'] ?? 'Pending';
  $notes = $input['notes'] ?? '';
  $items = $input['items'] ?? [];

  // Validation (same rules as your code)
  if (empty($poNumber) || empty($poDate) || empty($vendorName) || empty($vendorContact) || empty($deliveryDate)) {
    echo json_encode(['success'=>false,'messages'=>'Please fill all required fields']);
    exit;
  }

  if (empty($items) || count($items) == 0) {
    echo json_encode(['success'=>false,'messages'=>'Please add at least one item']);
    exit;
  }

  try {

    // ✅ START TRANSACTION
    $connect->begin_transaction();

    // ================= MASTER INSERT =================
    $stmt = $connect->prepare("
      INSERT INTO purchase_orders 
      (po_id, po_date, vendor_name, vendor_contact, vendor_email, vendor_address,
       expected_delivery_date, po_status, sub_total, discount, gst, grand_total,
       payment_status, notes, delete_status, created_at)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,NOW())
    ");

    if (!$stmt) {
      throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param(
      "ssssssssddddss",
      $poNumber,
      $poDate,
      $vendorName,
      $vendorContact,
      $vendorEmail,
      $vendorAddress,
      $deliveryDate,
      $poStatus,
      $subTotal,
      $discount,
      $gst,
      $grandTotal,
      $paymentStatus,
      $notes
    );

    if (!$stmt->execute()) {
      throw new Exception("Error creating Purchase Order: " . $stmt->error);
    }

    // Get the auto-increment ID of the inserted purchase order
    $poMasterId = $connect->insert_id;
    
    if (!$poMasterId) {
      throw new Exception("Failed to retrieve inserted Purchase Order ID");
    }

    // ================= ITEMS INSERT =================
    $itemStmt = $connect->prepare("
      INSERT INTO po_items 
      (po_master_id, product_id, quantity, unit_price, total, added_date)
      VALUES (?,?,?,?,?,NOW())
    ");

    if (!$itemStmt) {
      throw new Exception("Prepare failed (items): " . $connect->error);
    }

    foreach ($items as $item) {

      $productId = intval($item['productId'] ?? 0);
      $quantity  = intval($item['quantity'] ?? 0);
      $unitPrice = floatval($item['unitPrice'] ?? 0);
      $total     = floatval($item['total'] ?? 0);

      // Same logic but safer (unit price can be 0)
      if ($productId > 0 && $quantity > 0) {

        $itemStmt->bind_param(
          "iiidd",
          $poMasterId,
          $productId,
          $quantity,
          $unitPrice,
          $total
        );

        if (!$itemStmt->execute()) {
          throw new Exception("Error adding item: " . $itemStmt->error);
        }
      }
    }

    // ✅ COMMIT TRANSACTION
    $connect->commit();

    echo json_encode(['success'=>true,'messages'=>'Purchase Order created successfully']);

  } catch (Exception $e) {

    // ❌ ROLLBACK ON ERROR
    $connect->rollback();

    echo json_encode(['success'=>false,'messages'=>$e->getMessage()]);
  }

  $connect->close();
}
