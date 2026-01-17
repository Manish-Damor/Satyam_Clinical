<?php 
header('Content-Type: application/json');
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

if($_POST) {
  // Sanitize and validate inputs
  $poNumber = isset($_POST['poNumber']) ? $connect->real_escape_string($_POST['poNumber']) : '';
  $poDate = isset($_POST['poDate']) ? $connect->real_escape_string($_POST['poDate']) : '';
  $vendorName = isset($_POST['vendorName']) ? $connect->real_escape_string($_POST['vendorName']) : '';
  $vendorContact = isset($_POST['vendorContact']) ? $connect->real_escape_string($_POST['vendorContact']) : '';
  $vendorEmail = isset($_POST['vendorEmail']) ? $connect->real_escape_string($_POST['vendorEmail']) : '';
  $vendorAddress = isset($_POST['vendorAddress']) ? $connect->real_escape_string($_POST['vendorAddress']) : '';
  $deliveryDate = isset($_POST['deliveryDate']) ? $connect->real_escape_string($_POST['deliveryDate']) : '';
  $poStatus = isset($_POST['poStatus']) ? $connect->real_escape_string($_POST['poStatus']) : 'Pending';
  $subTotal = isset($_POST['subTotal']) ? floatval($_POST['subTotal']) : 0;
  $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
  $gst = isset($_POST['gst']) ? floatval($_POST['gst']) : 0;
  $grandTotal = isset($_POST['grandTotal']) ? floatval($_POST['grandTotal']) : 0;
  $paymentStatus = isset($_POST['paymentStatus']) ? $connect->real_escape_string($_POST['paymentStatus']) : 'Pending';
  $notes = isset($_POST['notes']) ? $connect->real_escape_string($_POST['notes']) : '';
  $items = isset($_POST['items']) ? $_POST['items'] : array();

  // Validate required fields
  if(empty($poNumber) || empty($poDate) || empty($vendorName) || empty($vendorContact) || empty($deliveryDate)) {
    $valid['messages'] = 'Please fill all required fields';
    echo json_encode($valid);
    exit();
  }

  if(empty($items) || count($items) == 0) {
    $valid['messages'] = 'Please add at least one item';
    echo json_encode($valid);
    exit();
  }

  try {
    // Insert into purchase_orders table
    $sql = "INSERT INTO purchase_orders (po_id, po_date, vendor_name, vendor_contact, vendor_email, vendor_address, expected_delivery_date, po_status, sub_total, discount, gst, grand_total, payment_status, notes, delete_status, created_at) 
            VALUES ('$poNumber', '$poDate', '$vendorName', '$vendorContact', '$vendorEmail', '$vendorAddress', '$deliveryDate', '$poStatus', $subTotal, $discount, $gst, $grandTotal, '$paymentStatus', '$notes', 0, NOW())";

    if($connect->query($sql) === TRUE) {
      $lastid = mysqli_insert_id($connect);

      // Insert items
      foreach($items as $item) {
        $productId = isset($item['productId']) ? intval($item['productId']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $unitPrice = isset($item['unitPrice']) ? floatval($item['unitPrice']) : 0;
        $total = isset($item['total']) ? floatval($item['total']) : 0;

        if($productId && $quantity && $unitPrice) {
          $itemSql = "INSERT INTO po_items (po_master_id, product_id, quantity, unit_price, total, added_date) 
                      VALUES ('$lastid', '$productId', '$quantity', $unitPrice, $total, NOW())";
          
          if($connect->query($itemSql) === FALSE) {
            throw new Exception("Error adding item: " . $connect->error);
          }
        }
      }

      $valid['success'] = true;
      $valid['messages'] = "Purchase Order created successfully";
    } else {
      throw new Exception("Error creating Purchase Order: " . $connect->error);
    }
  } catch(Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
  }

  echo json_encode($valid);
  $connect->close();