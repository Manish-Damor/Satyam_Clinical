<?php
require_once 'core.php';

$supplierId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$supplierId) {
    header('Location: ../supplier.php');
    exit;
}

try {
    $sql = "UPDATE suppliers SET is_active = 0 WHERE supplier_id = $supplierId";
    
    if($connect->query($sql)) {
        header('Location: ../supplier.php?msg=Supplier deleted successfully');
    } else {
        header('Location: ../supplier.php?error=Error deleting supplier');
    }
} catch(Exception $e) {
    header('Location: ../supplier.php?error=' . urlencode($e->getMessage()));
}

$connect->close();
?>
