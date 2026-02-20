<?php
require_once 'core.php';

$valid = array('success' => false, 'message' => '');

if ($_POST) {
    // Sanitize inputs
    $supplier_code = trim($_POST['supplier_code'] ?? '');
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $alternate_phone = trim($_POST['alternate_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $gst_number = trim($_POST['gst_number'] ?? '');
    $pan_number = trim($_POST['pan_number'] ?? '');
    $credit_days = intval($_POST['credit_days'] ?? 30);
    $payment_terms = trim($_POST['payment_terms'] ?? '');
    $supplier_status = $_POST['supplier_status'] ?? 'Active';
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;

    // Validation
    if (empty($supplier_name)) {
        $valid['message'] = 'Supplier name is required.';
        echo json_encode($valid);
        exit;
    }

    if (empty($phone)) {
        $valid['message'] = 'Phone number is required.';
        echo json_encode($valid);
        exit;
    }

    if (empty($address)) {
        $valid['message'] = 'Address is required.';
        echo json_encode($valid);
        exit;
    }

    // Check for duplicate supplier code
    if (!empty($supplier_code)) {
        $checkSql = "SELECT supplier_id FROM suppliers WHERE supplier_code = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("s", $supplier_code);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $valid['message'] = 'Supplier code already exists.';
            echo json_encode($valid);
            exit;
        }
        $checkStmt->close();
    }

    // Insert supplier
    $insertSql = "INSERT INTO suppliers 
        (supplier_code, supplier_name, company_name, contact_person, 
         email, phone, alternate_phone, 
         address, city, state, pincode, 
         gst_number, pan_number, 
         credit_days, payment_terms, 
         supplier_status, is_verified, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($insertSql);

    if (!$stmt) {
        $valid['message'] = 'Database error: ' . $connect->error;
        echo json_encode($valid);
        exit;
    }

    $stmt->bind_param(
        "sssssssssssssssii",
        $supplier_code,
        $supplier_name,
        $company_name,
        $contact_person,
        $email,
        $phone,
        $alternate_phone,
        $address,
        $city,
        $state,
        $pincode,
        $gst_number,
        $pan_number,
        $credit_days,
        $payment_terms,
        $supplier_status,
        $is_verified
    );

    if ($stmt->execute()) {
        $valid['success'] = true;
        $valid['message'] = 'Supplier added successfully.';
        $valid['redirect'] = 'manage_suppliers.php';
    } else {
        $valid['message'] = 'Error while adding supplier: ' . $stmt->error;
    }

    $stmt->close();
    $connect->close();
}

header('Content-Type: application/json');
echo json_encode($valid);
?>
