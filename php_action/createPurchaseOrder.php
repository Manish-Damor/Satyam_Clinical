<?php

require_once 'core.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     echo "<pre>"; // Makes the output human-readable in a browser
//     print_r($_POST);
//     echo "</pre>";

//     exit;
// }


/* ============================================
   HANDLE FORM SUBMISSION (POST REQUEST)
   ============================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* SESSION VALIDATION */
    if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
        $_SESSION['po_error'] = 'Session expired. Please login again.';
        header('Location: ../create_po.php');
        exit;
    }

    $userId = intval($_SESSION['userId']);

    /* GET BASIC PO DATA FROM FORM */
    $poNumber       = isset($_POST['po_number']) ? trim($_POST['po_number']) : '';
    $poDate         = isset($_POST['po_date']) ? trim($_POST['po_date']) : date('Y-m-d');
    $poType         = isset($_POST['po_type']) ? trim($_POST['po_type']) : 'Regular';
    $expectedDelivery = (isset($_POST['expected_delivery_date']) && !empty($_POST['expected_delivery_date'])) 
                        ? trim($_POST['expected_delivery_date']) 
                        : null;

    $supplierId     = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $paymentMethod  = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Online Transfer';
    $poStatus       = isset($_POST['po_status']) ? trim($_POST['po_status']) : 'Draft';

    /* TOTALS */
    $subTotal       = isset($_POST['sub_total']) ? floatval($_POST['sub_total']) : 0;
    $totalDiscount  = isset($_POST['total_discount']) ? floatval($_POST['total_discount']) : 0;
    $discountPercent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;
    $taxableAmount  = isset($_POST['taxable_amount']) ? floatval($_POST['taxable_amount']) : 0;
    $cgstAmount     = isset($_POST['cgst_amount']) ? floatval($_POST['cgst_amount']) : 0;
    $sgstAmount     = isset($_POST['sgst_amount']) ? floatval($_POST['sgst_amount']) : 0;
    $igstAmount     = isset($_POST['igst_amount']) ? floatval($_POST['igst_amount']) : 0;
    $roundOff       = isset($_POST['round_off']) ? floatval($_POST['round_off']) : 0;
    $grandTotal     = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0;
    $paymentStatus = "pending";

    /* VALIDATION */
    if (!$poNumber) {
        $_SESSION['po_error'] = 'PO Number is missing';
        header('Location: ../create_po.php');
        exit;
    }

    if ($supplierId <= 0) {
        $_SESSION['po_error'] = 'Please select a supplier';
        header('Location: ../create_po.php');
        exit;
    }

    /* FETCH SUPPLIER INFO */
    $supStmt = $connect->prepare("
        SELECT supplier_name, primary_contact, email,
               gst_number, billing_address,
               billing_city, billing_state, billing_pincode,
               payment_terms
        FROM suppliers WHERE supplier_id = ?
    ");
    $supStmt->bind_param("i", $supplierId);
    $supStmt->execute();
    $supplier = $supStmt->get_result()->fetch_assoc();  

    
    $supplier_name = $supplier['supplier_name'];
    $supplier_contact = $supplier['primary_contact'];
    $supplier_email = $supplier['email'];
    $supplier_gst = $supplier['gst_number'];
    $supplier_address = $supplier['billing_address'];
    $supplier_city = $supplier['billing_city'];
    $supplier_state = $supplier['billing_state'];
    $supplier_pincode = $supplier['billing_pincode'];
    $payment_terms = $supplier['payment_terms'];
    
    $supStmt->close();

    if (!$supplier) {
        $_SESSION['po_error'] = 'Supplier not found';
        header('Location: ../create_po.php');
        exit;
    }

    /* GET ITEMS FROM FORM */
    $itemCount = isset($_POST['item_count']) ? intval($_POST['item_count']) : 0;
    
    if ($itemCount <= 0) {
        $_SESSION['po_error'] = 'Please add at least one medicine item';
        header('Location: ../create_po.php');
        exit;
    }

    /* START TRANSACTION */
    $connect->begin_transaction();

    try {

        /* INSERT PO MASTER */

        //26 items
        $sqlPo = "
            INSERT INTO purchase_order (
                po_number,
                po_date, 
                po_type,
                supplier_id,
                supplier_name,
                supplier_contact, 
                supplier_email,
                supplier_gst,
                supplier_address, 
                supplier_city, 
                supplier_state,
                supplier_pincode,
                expected_delivery_date,
                sub_total, 
                total_discount,
                discount_percent,
                taxable_amount,
                cgst_amount,
                sgst_amount,
                igst_amount,
                round_off,
                grand_total,
                payment_terms,
                payment_method,
                po_status,
                created_by  
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                ?,?,
                ?,?,
                ?,?,
                ?
            )
        ";

        $stmtPo = $connect->prepare($sqlPo);
        if (!$stmtPo) {
            throw new Exception("Prepare failed: " . $connect->error);
        }

        $stmtPo->bind_param(
            'sssisssssssssdddddddddsssi',
            $poNumber,
            $poDate,
            $poType,
            $supplierId,
            $supplier_name,
            $supplier_contact,
            $supplier_email,
            $supplier_gst,
            $supplier_address,
            $supplier_city,
            $supplier_state,
            $supplier_pincode,
            $expectedDelivery,
            $subTotal,
            $totalDiscount,
            $discountPercent,
            $taxableAmount,
            $cgstAmount,
            $sgstAmount,
            $igstAmount,
            $roundOff,
            $grandTotal,
            $payment_terms,
            $paymentMethod,
            $poStatus,            
            $userId
        );

        if (!$stmtPo->execute()) {
            throw new Exception("Execute failed: " . $stmtPo->error);
        }

        $poId = $stmtPo->insert_id;
        $stmtPo->close();

        /* INSERT ITEMS */
        $sqlItem = "
            INSERT INTO purchase_order_items (
                po_id, po_number,
                medicine_id, medicine_name,
                pack_size, hsn_code,
                batch_number, expiry_date,
                quantity_ordered,
                mrp, ptr, unit_price,
                line_amount,
                item_discount_percent,
                taxable_amount,
                tax_percent, tax_amount,
                item_total
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ";

        $stmtItem = $connect->prepare($sqlItem);
        if (!$stmtItem) {
            throw new Exception("Item prepare failed: " . $connect->error);
        }

        $itemsSaved = 0;

        for ($i = 0; $i < $itemCount; $i++) {
            
            $medicineId = isset($_POST['medicine_id'][$i]) ? intval($_POST['medicine_id'][$i]) : 0;
            $qty = isset($_POST['quantity'][$i]) ? intval($_POST['quantity'][$i]) : 0;

            /* Skip empty rows */
            if ($medicineId <= 0 || $qty <= 0) {
                continue;
            }

            /* GET ITEM VALUES */
            $medicineName = $_POST['medicine_name'][$i] ?? '';
            $packSize = $_POST['pack_size'][$i] ?? '';
            $hsnCode = $_POST['hsn_code'][$i] ?? '';
            $batchNumber = $_POST['batch_number'][$i] ?? '';
            $expiryDate = (isset($_POST['expiry_date'][$i]) && !empty($_POST['expiry_date'][$i])) 
                         ? $_POST['expiry_date'][$i] 
                         : null;
            $mrp = isset($_POST['mrp'][$i]) ? floatval($_POST['mrp'][$i]) : 0;
            $ptr = isset($_POST['ptr'][$i]) ? floatval($_POST['ptr'][$i]) : 0;
            $unitPrice = isset($_POST['unit_price'][$i]) ? floatval($_POST['unit_price'][$i]) : 0;
            $discountPercent = isset($_POST['discount_percent'][$i]) ? floatval($_POST['discount_percent'][$i]) : 0;
            $taxPercent = isset($_POST['tax_percent'][$i]) ? floatval($_POST['tax_percent'][$i]) : 18;

            /* CALCULATE ITEM TOTAL */
            $lineAmount = $qty * $unitPrice;
            $lineDiscountAmt = ($lineAmount * $discountPercent) / 100;
            $itemTaxable = $lineAmount - $lineDiscountAmt;
            $taxAmt = ($itemTaxable * $taxPercent) / 100;
            $itemTotal = $itemTaxable + $taxAmt;

            /* BIND AND INSERT */
            $stmtItem->bind_param(
                'isisssssiddddddddd',
                $poId,
                $poNumber,
                $medicineId,
                $medicineName,
                $packSize,
                $hsnCode,
                $batchNumber,
                $expiryDate,
                $qty,
                $mrp,
                $ptr,
                $unitPrice,
                $lineAmount,
                $discountPercent,
                $itemTaxable,
                $taxPercent,
                $taxAmt,
                $itemTotal
            );

            if (!$stmtItem->execute()) {
                throw new Exception("Item execute failed: " . $stmtItem->error);
            }

            $itemsSaved++;
        }

        $stmtItem->close();

        if ($itemsSaved === 0) {
            throw new Exception("No valid items were added to the purchase order");
        }

        $connect->commit();

        $_SESSION['po_success'] = 'Purchase Order created successfully!';
        header('Location: ../po_list.php');
        exit;

    } catch (Exception $e) {

        $connect->rollback();
        $_SESSION['po_error'] = 'Error: ' . $e->getMessage();
        header('Location: ../create_po.php');
        exit;
    }

} else {
    /* NOT A POST REQUEST */
    $_SESSION['po_error'] = 'Invalid request method';
    header('Location: ../create_po.php');
    exit;
}

?>

