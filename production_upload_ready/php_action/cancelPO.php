<?php
/**
 * Cancel Purchase Order Processing
 * Handles POST data from cancel_po.php form
 * Matches project structure - uses POST variables
 */

require_once 'core.php';

// Initialize response (matching project pattern)
$valid['success'] = array('success' => false, 'messages' => array());
$debug = array();

// Debug mode
$DEBUG_ON = true;

if($DEBUG_ON) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/po_cancel_errors.log');
}

// START PROCESSING POST DATA
if($_POST) {
    try {
        $debug[] = "=== PO Cancellation Started ===";
        
        // Extract from POST
        $po_id = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;
        $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';
        $reason_details = isset($_POST['reason_details']) ? trim($_POST['reason_details']) : '';
        $refund_amount = isset($_POST['refund_amount']) ? floatval($_POST['refund_amount']) : 0;
        $refund_status = isset($_POST['refund_status']) ? $_POST['refund_status'] : 'Pending';
        $approver_name = isset($_POST['approver_name']) ? trim($_POST['approver_name']) : '';
        
        $debug[] = "PO ID: $po_id | Reason: $cancellation_reason | Amount: $refund_amount";
        
        // Validate required fields
        if($po_id <= 0) {
            throw new Exception("Invalid PO ID");
        }
        if(empty($cancellation_reason)) {
            throw new Exception("Cancellation reason is required");
        }
        if(empty($reason_details)) {
            throw new Exception("Reason details are required");
        }
        
        $debug[] = "✓ Validation passed";
        
        // Get user from session
        $userId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
        if($userId <= 0) {
            throw new Exception("User session expired - Please login again");
        }
        
        $debug[] = "User ID: $userId";
        
        // START TRANSACTION
        $connect->begin_transaction();
        $debug[] = "✓ Transaction started";
        
        // FETCH PO DETAILS
        $fetchSql = "SELECT * FROM purchase_order WHERE po_id = ?";
        $fetchStmt = $connect->prepare($fetchSql);
        if(!$fetchStmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $fetchStmt->bind_param('i', $po_id);
        if(!$fetchStmt->execute()) {
            throw new Exception("Fetch failed: " . $fetchStmt->error);
        }
        
        $result = $fetchStmt->get_result();
        if($result->num_rows == 0) {
            throw new Exception("PO not found");
        }
        
        $po = $result->fetch_assoc();
        $debug[] = "✓ PO found: " . $po['po_number'] . " | Status: " . $po['po_status'];
        $fetchStmt->close();
        
        // Check if already cancelled
        if($po['po_status'] == 'Cancelled') {
            throw new Exception("This PO is already cancelled");
        }
        
        // UPDATE PO STATUS TO CANCELLED
        $updatePoSql = "UPDATE purchase_order SET 
            po_status = ?,
            cancelled_status = 1,
            cancelled_by = ?,
            cancelled_date = NOW(),
            cancellation_reason = ?,
            cancellation_details = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE po_id = ?";
        
        $updatePoStmt = $connect->prepare($updatePoSql);
        if(!$updatePoStmt) {
            throw new Exception("Prepare update failed: " . $connect->error);
        }
        
        $po_status_new = 'Cancelled';
        
        // Type string: s=string, i=integer, s=string, s=string, i=integer, i=integer (6 parameters)
        $updatePoStmt->bind_param(
            'sssii',
            $po_status_new,          // s 1
            $userId,                 // i 2
            $cancellation_reason,    // s 3
            $reason_details,         // s 4
            $po_id                   // i 5
        );
        
        if(!$updatePoStmt->execute()) {
            throw new Exception("Update failed: " . $updatePoStmt->error);
        }
        
        $debug[] = "✓ PO status updated to Cancelled";
        $updatePoStmt->close();
        
        // INSERT CANCELLATION LOG/RECORD
        $insertCancelSql = "INSERT INTO po_cancellation_log (
            po_id,
            po_number,
            cancellation_date,
            cancellation_reason,
            reason_details,
            refund_amount,
            refund_status,
            approver_name,
            cancelled_by,
            created_at
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, NOW())";
        
        $insertCancelStmt = $connect->prepare($insertCancelSql);
        if(!$insertCancelStmt) {
            throw new Exception("Prepare cancel log failed: " . $connect->error);
        }
        
        // Type string: i=integer, s=string, s=string, s=string, d=double, s=string, s=string, i=integer (8 parameters)
        $insertCancelStmt->bind_param(
            'isssdssi',
            $po_id,                  // i 1
            $po['po_number'],        // s 2
            $cancellation_reason,    // s 3
            $reason_details,         // s 4
            $refund_amount,          // d 5
            $refund_status,          // s 6
            $approver_name,          // s 7
            $userId                  // i 8
        );
        
        if(!$insertCancelStmt->execute()) {
            throw new Exception("Cancel log insert failed: " . $insertCancelStmt->error);
        }
        
        $debug[] = "✓ Cancellation log created";
        $insertCancelStmt->close();
        
        // UPDATE PURCHASE ORDER ITEMS TO CANCELLED
        $updateItemsSql = "UPDATE purchase_order_items SET 
            item_status = ?
        WHERE po_id = ?";
        
        $updateItemsStmt = $connect->prepare($updateItemsSql);
        if(!$updateItemsStmt) {
            throw new Exception("Prepare items update failed: " . $connect->error);
        }
        
        $item_status = 'Cancelled';
        $updateItemsStmt->bind_param('si', $item_status, $po_id);
        
        if(!$updateItemsStmt->execute()) {
            throw new Exception("Items update failed: " . $updateItemsStmt->error);
        }
        
        $debug[] = "✓ All items marked as Cancelled";
        $updateItemsStmt->close();
        
        // UPDATE SUPPLIER STATS (reduce counts)
        $updateSupplierSql = "UPDATE suppliers SET 
            total_orders = GREATEST(0, total_orders - 1),
            total_amount_ordered = GREATEST(0, total_amount_ordered - ?)
        WHERE supplier_id = ?";
        
        $updateSupplierStmt = $connect->prepare($updateSupplierSql);
        if(!$updateSupplierStmt) {
            throw new Exception("Prepare supplier update failed: " . $connect->error);
        }
        
        $updateSupplierStmt->bind_param('di', $po['grand_total'], $po['supplier_id']);
        
        if(!$updateSupplierStmt->execute()) {
            throw new Exception("Supplier update failed: " . $updateSupplierStmt->error);
        }
        
        $debug[] = "✓ Supplier stats updated";
        $updateSupplierStmt->close();
        
        // COMMIT TRANSACTION
        $connect->commit();
        $debug[] = "✓ Transaction committed";
        
        // SUCCESS RESPONSE
        $valid['success'] = true;
        $valid['messages'] = "Purchase Order #" . $po['po_number'] . " has been cancelled successfully";
        $valid['po_id'] = $po_id;
        
        // Redirect after success
        header('location:../po_list.php');
        
    } catch(Exception $e) {
        // ROLLBACK ON ERROR
        $connect->rollback();
        $debug[] = "✗ Error: " . $e->getMessage();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }
    
    // Add debug info
    $valid['debug'] = $debug;
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode($valid);
    
} else {
    // No POST data
    $valid['success'] = false;
    $valid['messages'] = "Invalid request - POST data required";
    $valid['debug'] = array("No POST data received");
    
    header('Content-Type: application/json');
    echo json_encode($valid);
}

// CLOSE CONNECTION
if(isset($connect)) {
    $connect->close();
}

exit;

// } catch(Exception $e) {
//     $connect->rollback();
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }

// $connect->close();
?>
