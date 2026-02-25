<?php
require_once 'core.php';

$supplierId = isset($_GET['id']) ? intval($_GET['id']) : 0;
// redirect target for legacy callers
$redirect = '../manage_suppliers.php';

if(!$supplierId) {
    header("Location: {$redirect}");
    exit;
}

try {
    // mark supplier as inactive rather than deleting row
    $sql = "UPDATE suppliers SET supplier_status = 'Inactive' WHERE supplier_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $supplierId);
    if($stmt->execute()) {
        header('Location: ' . $redirect . '?msg=Supplier deleted successfully');
    } else {
        header('Location: ' . $redirect . '?error=Error deleting supplier');
    }
    $stmt->close();
} catch(Exception $e) {
    header('Location: ' . $redirect . '?error=' . urlencode($e->getMessage()));
}

$connect->close();

$connect->close();
?>
