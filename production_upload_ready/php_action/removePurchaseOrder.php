<?php 
header('Content-Type: application/json');
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

if($_POST) {
  // Validate input
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

  if(!$id) {
    $valid['messages'] = 'Invalid ID';
    echo json_encode($valid);
    exit();
  }

  try {
    // Soft delete - mark as deleted
    $sql = "UPDATE purchase_orders SET delete_status = 1, updated_at = NOW() WHERE id = $id";

    if($connect->query($sql) === TRUE) {
      $valid['success'] = true;
      $valid['messages'] = "Purchase Order deleted successfully";
    } else {
      throw new Exception("Error deleting Purchase Order: " . $connect->error);
    }
  } catch(Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
  }

  echo json_encode($valid);
}
$connect->close();
?>
