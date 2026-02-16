<?php
header('Content-Type: application/json');
require_once 'core.php';

// echo $_POST['supplier_id'];
// exit;

$supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
// $supplierCode = isset($_POST['supplier_code']) ? trim($_POST['supplier_code']) : '';
$supplierName = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : '';

// Validate required fields
if(!$supplierName || !$supplierId) {
    echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
    exit;
}

try {
    if($supplierId > 0) {
        // Update existing supplier
        // $stmt = $connect->prepare("
        //     UPDATE suppliers SET 
        //     supplier_code = ?,
        //     supplier_name = ?,
        //     supplier_type = ?,
        //     gst_number = ?,
        //     contact_person = ?,
        //     primary_contact = ?,
        //     email = ?,
        //     billing_address = ?,
        //     billing_city = ?,
        //     billing_state = ?,
        //     billing_pincode = ?,
        //     payment_terms = ?,
        //     payment_days = ?,
        //     updated_at = NOW()
        //     WHERE supplier_id = ?
        // ");
        
        $stmt = $connect->prepare("
            UPDATE suppliers SET 
            
            supplier_name = ?,
            
            
            gst_number = ?,
            contact_person = ?,
            primary_contact = ?,
            email = ?,
            billing_address = ?,
            billing_city = ?,
            billing_state = ?,
            billing_pincode = ?,
            payment_terms = ?,
            payment_days = ?,
            updated_at = NOW()
            WHERE supplier_id = ?
        ");
        
        $stmt->bind_param(
            "sssssssssssii",
            // $_POST['supplier_code'],
            $_POST['supplier_name'],
            // $_POST['supplier_type'],
            $_POST['gst_number'],
            // $_POST['contact_person'],
            $_POST['phone_number'],
            $_POST['email'],
            $_POST['billing_address'],
            $_POST['billing_city'],
            $_POST['billing_state'],
            $_POST['billing_pincode'],
            $_POST['payment_terms'],
            $_POST['payment_days'],
            $supplierId
        );
    } else {
        // Create new supplier
        $stmt = $connect->prepare("
            INSERT INTO suppliers (
            supplier_name, phone_number,gst_number, pan_number,
            dl_number, fsssai_number,
             email, billing_address, billing_city, billing_state,
            billing_pincode, payment_terms, payment_days, is_active
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)
        ");
        
        $stmt->bind_param(
            "ssssssssssssi",
            // $_POST['supplier_code'],
            $_POST['supplier_name'],
            // $_POST['supplier_type'],
            $_POST['phone_number'],
            $_POST['gst_number'],
            $_POST['pan_number'],
            // $_POST['contact_person'],


            $_POST['dl_number'],
            $_POST['fsssai_number'],
            $_POST['email'],
            $_POST['billing_address'],
            $_POST['billing_city'],
            $_POST['billing_state'],
            $_POST['billing_pincode'],
            $_POST['payment_terms'],
            $_POST['payment_days']
        );
    }
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Supplier saved successfully']);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$connect->close();
?>
