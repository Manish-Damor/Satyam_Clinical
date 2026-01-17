<?php 
header('Content-Type: application/json');
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// If no JSON input, try POST data (for backwards compatibility)
if (!$input) {
    $input = $_POST ?? [];
}

if($input) {
  // Sanitize and validate inputs
  $poId = isset($input['poId']) ? intval($input['poId']) : 0;
  $poDate = isset($input['poDate']) ? $connect->real_escape_string($input['poDate']) : '';
  $vendorName = isset($input['vendorName']) ? $connect->real_escape_string($input['vendorName']) : '';
  $vendorContact = isset($input['vendorContact']) ? $connect->real_escape_string($input['vendorContact']) : '';
  $vendorEmail = isset($input['vendorEmail']) ? $connect->real_escape_string($input['vendorEmail']) : '';
  $vendorAddress = isset($input['vendorAddress']) ? $connect->real_escape_string($input['vendorAddress']) : '';
  $deliveryDate = isset($input['deliveryDate']) ? $connect->real_escape_string($input['deliveryDate']) : '';
  $poStatus = isset($input['poStatus']) ? $connect->real_escape_string($input['poStatus']) : 'Pending';
  $subTotal = isset($input['subTotal']) ? floatval($input['subTotal']) : 0;
  $discount = isset($input['discount']) ? floatval($input['discount']) : 0;
  $gst = isset($input['gst']) ? floatval($input['gst']) : 0;
  $grandTotal = isset($input['grandTotal']) ? floatval($input['grandTotal']) : 0;
  $paymentStatus = isset($input['paymentStatus']) ? $connect->real_escape_string($input['paymentStatus']) : 'Pending';
  $notes = isset($input['notes']) ? $connect->real_escape_string($input['notes']) : '';
  $items = isset($input['items']) ? $input['items'] : array();

  // Validate required fields
  if(!$poId || empty($poDate) || empty($vendorName) || empty($vendorContact) || empty($deliveryDate)) {
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
    // Update purchase_orders table
    $sql = "UPDATE purchase_orders SET 
            po_date = '$poDate', 
            vendor_name = '$vendorName', 
            vendor_contact = '$vendorContact', 
            vendor_email = '$vendorEmail', 
            vendor_address = '$vendorAddress', 
            expected_delivery_date = '$deliveryDate', 
            po_status = '$poStatus', 
            sub_total = $subTotal, 
            discount = $discount, 
            gst = $gst, 
            grand_total = $grandTotal, 
            payment_status = '$paymentStatus', 
            notes = '$notes',
            updated_at = NOW()
            WHERE id = $poId";

    if($connect->query($sql) === TRUE) {
      // Delete old items
      $deleteSql = "DELETE FROM po_items WHERE po_master_id = $poId";
      $connect->query($deleteSql);

      // Insert new items
      foreach($items as $item) {
        $productId = isset($item['productId']) ? intval($item['productId']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $unitPrice = isset($item['unitPrice']) ? floatval($item['unitPrice']) : 0;
        $total = isset($item['total']) ? floatval($item['total']) : 0;

        if($productId && $quantity && $unitPrice) {
          $itemSql = "INSERT INTO po_items (po_master_id, product_id, quantity, unit_price, total, added_date) 
                      VALUES ($poId, $productId, $quantity, $unitPrice, $total, NOW())";
          
          if($connect->query($itemSql) === FALSE) {
            throw new Exception("Error adding item: " . $connect->error);
          }
        }
      }

      $valid['success'] = true;
      $valid['messages'] = "Purchase Order updated successfully";
    } else {
      throw new Exception("Error updating Purchase Order: " . $connect->error);
    }
  } catch(Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
  }

  echo json_encode($valid);
}
$connect->close();
?>
