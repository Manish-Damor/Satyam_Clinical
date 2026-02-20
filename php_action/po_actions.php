<?php
// Purchase Order Actions Handler (for PO operations)
require_once 'core.php';

header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

$action = $_POST['action'];
// support both po_id and invoice_id depending on action
$poId = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;
$invoiceId = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

$id = $poId > 0 ? $poId : $invoiceId;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

global $connect;

// ============================================
// APPROVE PURCHASE ORDER
// ============================================
if ($action === 'approve_po') {
    // Check if PO exists
    $checkStmt = $connect->prepare("SELECT po_status FROM purchase_orders WHERE po_id = ?");
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $checkStmt->bind_param('i', $poId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'PO not found']);
        exit;
    }
    
    $po = $result->fetch_assoc();
    $currentStatus = $po['po_status'];
    
    // Only Draft or Submitted POs can be approved
    if (!in_array($currentStatus, ['Draft', 'Submitted'])) {
        echo json_encode([
            'success' => false, 
            'error' => "Cannot approve PO with status: $currentStatus"
        ]);
        exit;
    }
    
    // Update PO status to Approved
    $updateStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Approved', updated_at = NOW() WHERE po_id = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $updateStmt->bind_param('i', $poId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'PO approved successfully',
            'new_status' => 'Approved'
        ]);
    } else {
        $updateStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to approve PO']);
    }
}

// ============================================
// APPROVE PURCHASE INVOICE
// ============================================
else if ($action === 'approve_invoice') {
    // Check if invoice exists
    $checkStmt = $connect->prepare("SELECT status FROM purchase_invoices WHERE id = ?");
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invoice not found']);
        exit;
    }
    
    $inv = $result->fetch_assoc();
    $currentStatus = $inv['status'];
    
    // Only Draft or Submitted invoices can be approved
    if (!in_array($currentStatus, ['Draft', 'Submitted'])) {
        echo json_encode([
            'success' => false, 
            'error' => "Cannot approve invoice with status: $currentStatus"
        ]);
        exit;
    }
    
    // Update invoice status to Approved
    // note: purchase_invoices table doesn't have updated_at column
    $updateStmt = $connect->prepare("UPDATE purchase_invoices SET status = 'Approved' WHERE id = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $updateStmt->bind_param('i', $poId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Invoice approved successfully',
            'new_status' => 'Approved'
        ]);
    } else {
        $updateStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to approve invoice']);
    }
}

// ============================================
// DELETE PURCHASE INVOICE
// ============================================
else if ($action === 'delete_invoice') {
    // simple deletion
    $deleteStmt = $connect->prepare("DELETE FROM purchase_invoices WHERE id = ?");
    if (!$deleteStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    $deleteStmt->bind_param('i', $id);
    if ($deleteStmt->execute()) {
        $deleteStmt->close();
        echo json_encode(['success' => true, 'message' => 'Invoice deleted']);
    } else {
        $deleteStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to delete invoice']);
    }
}

// ============================================
// CANCEL PURCHASE ORDER
// ============================================
else if ($action === 'cancel_po') {
    // Check if PO exists
    $checkStmt = $connect->prepare("SELECT po_status FROM purchase_orders WHERE po_id = ?");
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $checkStmt->bind_param('i', $poId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'PO not found']);
        exit;
    }
    
    $po = $result->fetch_assoc();
    $currentStatus = $po['po_status'];
    
    // Cannot cancel if already received or cancelled
    if ($currentStatus === 'Received' || $currentStatus === 'Cancelled') {
        echo json_encode([
            'success' => false, 
            'error' => "Cannot cancel PO with status: $currentStatus"
        ]);
        exit;
    }
    
    // Update PO status to Cancelled
    $updateStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Cancelled', updated_at = NOW() WHERE po_id = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
        exit;
    }
    
    $updateStmt->bind_param('i', $poId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'PO cancelled successfully',
            'new_status' => 'Cancelled'
        ]);
    } else {
        $updateStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to cancel PO']);
    }
}

// ============================================
// MARK AS RECEIVED
// ============================================
else if ($action === 'mark_received') {
    // Check if PO exists
    $checkStmt = $connect->prepare("SELECT po_status FROM purchase_orders WHERE po_id = ?");
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    
    $checkStmt->bind_param('i', $poId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'PO not found']);
        exit;
    }
    
    $po = $result->fetch_assoc();
    
    // Update PO and all items to Received
    $updateStmt = $connect->prepare("UPDATE purchase_orders SET po_status = 'Received', updated_at = NOW() WHERE po_id = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    
    $updateStmt->bind_param('i', $poId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Also mark all items as received
        $itemStmt = $connect->prepare("UPDATE po_items SET item_status = 'Received' WHERE po_id = ?");
        if ($itemStmt) {
            $itemStmt->bind_param('i', $poId);
            $itemStmt->execute();
            $itemStmt->close();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'PO marked as received',
            'new_status' => 'Received'
        ]);
    } else {
        $updateStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to mark PO as received']);
    }
}

// ============================================
// UPDATE PAYMENT STATUS
// ============================================
else if ($action === 'update_payment') {
    $paymentStatus = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
    
    // Validate payment status
    $validStatuses = ['NotDue', 'Due', 'PartialPaid', 'Paid', 'Overdue'];
    if (!in_array($paymentStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid payment status']);
        exit;
    }
    
    // Check if PO exists
    $checkStmt = $connect->prepare("SELECT po_id FROM purchase_orders WHERE po_id = ?");
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    
    $checkStmt->bind_param('i', $poId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'PO not found']);
        exit;
    }
    
    // Update payment status
    $updateStmt = $connect->prepare("UPDATE purchase_orders SET payment_status = ?, updated_at = NOW() WHERE po_id = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    
    $updateStmt->bind_param('si', $paymentStatus, $poId);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Payment status updated',
            'payment_status' => $paymentStatus
        ]);
    } else {
        $updateStmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to update payment status']);
    }
}

// ============================================
// UNKNOWN ACTION
// ============================================
else {
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}

?>
