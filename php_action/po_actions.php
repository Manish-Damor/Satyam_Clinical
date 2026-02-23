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

// helper to fetch current status
function get_po_status($connect, $poId) {
    $stmt = $connect->prepare("SELECT po_status FROM purchase_orders WHERE po_id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $poId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res && $res->num_rows ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row['po_status'] ?? null;
}

// helper to recalc PO status from item quantities
function recalc_po_status_from_items($connect, $poId) {
    $sql = "SELECT SUM(quantity_ordered) AS total_ordered, SUM(quantity_received) AS total_received FROM po_items WHERE po_id = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $poId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $ordered = floatval($row['total_ordered'] ?? 0);
    $received = floatval($row['total_received'] ?? 0);

    if ($ordered <= 0) return 'Draft';
    if ($received <= 0) return 'Approved';
    if ($received < $ordered) return 'PartialReceived';
    if ($received >= $ordered) return 'Received';
    return null;
}

// ============================================
// SUBMIT PURCHASE ORDER (Draft -> Submitted)
// ============================================
if ($action === 'submit_po') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) {
        echo json_encode(['success'=>false,'error'=>'PO not found']); exit;
    }
    if ($currentStatus !== 'Draft') {
        echo json_encode(['success'=>false,'error'=>'Only Draft POs can be submitted']); exit;
    }
    $stmt = $connect->prepare("UPDATE purchase_orders SET po_status='Submitted', updated_at=NOW() WHERE po_id=?");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'DB error']); exit; }
    $stmt->bind_param('i',$poId);
    if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'PO submitted','new_status'=>'Submitted']); } else { echo json_encode(['success'=>false,'error'=>'Failed to submit']); }
    $stmt->close();
    exit;
}

// ============================================
// REJECT PURCHASE ORDER (Submitted -> Draft)
// ============================================
if ($action === 'reject_po') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) { echo json_encode(['success'=>false,'error'=>'PO not found']); exit; }
    if ($currentStatus !== 'Submitted') { echo json_encode(['success'=>false,'error'=>'Only Submitted POs can be rejected']); exit; }
    $stmt = $connect->prepare("UPDATE purchase_orders SET po_status='Draft', updated_at=NOW() WHERE po_id=?");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'DB error']); exit; }
    $stmt->bind_param('i',$poId);
    if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'PO rejected to Draft','new_status'=>'Draft']); } else { echo json_encode(['success'=>false,'error'=>'Failed to reject PO']); }
    $stmt->close();
    exit;
}

// ============================================
// APPROVE PURCHASE ORDER (Submitted -> Approved)
// ============================================
if ($action === 'approve_po') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) { echo json_encode(['success'=>false,'error'=>'PO not found']); exit; }
    if ($currentStatus !== 'Submitted') { echo json_encode(['success'=>false,'error'=>'Only Submitted POs can be approved']); exit; }
    $stmt = $connect->prepare("UPDATE purchase_orders SET po_status='Approved', updated_at=NOW() WHERE po_id=?");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'DB error']); exit; }
    $stmt->bind_param('i',$poId);
    if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'PO approved','new_status'=>'Approved']); } else { echo json_encode(['success'=>false,'error'=>'Failed to approve PO']); }
    $stmt->close();
    exit;
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
    
    // Use centralised helper which updates status and handles stock
    require_once 'purchase_invoice_action.php';
    $ok = PurchaseInvoiceAction::approveInvoice($id, $_SESSION['userId'] ?? 0);
    if ($ok) {
        echo json_encode([
            'success' => true, 
            'message' => 'Invoice approved successfully',
            'new_status' => 'Approved'
        ]);
    } else {
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
    
    // Cannot cancel if already approved/received/partial/closed/cancelled
    if (in_array($currentStatus, ['Approved','PartialReceived','Received','Closed','Cancelled'])) {
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
// MARK AS RECEIVED (full receive) - requires Approved or PartialReceived
// Sets each item's quantity_received = quantity_ordered and marks items received
// ============================================
else if ($action === 'mark_received') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) { echo json_encode(['success'=>false,'error'=>'PO not found']); exit; }
    if (!in_array($currentStatus, ['Approved','PartialReceived'])) { echo json_encode(['success'=>false,'error'=>'PO must be Approved or PartialReceived to mark as received']); exit; }

    // set quantity_received = quantity_ordered for all items
    $updateItems = $connect->prepare("UPDATE po_items SET quantity_received = quantity_ordered, item_status = 'Received' WHERE po_id = ?");
    if (!$updateItems) { echo json_encode(['success'=>false,'error'=>'DB error updating items']); exit; }
    $updateItems->bind_param('i',$poId);
    $okItems = $updateItems->execute();
    $updateItems->close();

    if (!$okItems) { echo json_encode(['success'=>false,'error'=>'Failed to update items']); exit; }

    $updatePo = $connect->prepare("UPDATE purchase_orders SET po_status='Received', updated_at=NOW() WHERE po_id=?");
    if (!$updatePo) { echo json_encode(['success'=>false,'error'=>'DB error']); exit; }
    $updatePo->bind_param('i',$poId);
    if ($updatePo->execute()) { echo json_encode(['success'=>true,'message'=>'PO fully received','new_status'=>'Received']); } else { echo json_encode(['success'=>false,'error'=>'Failed to update PO status']); }
    $updatePo->close();
    exit;
}

// ============================================
// UPDATE RECEIVED QUANTITIES (partial updates)
// Expect arrays: item_id[] and quantity_received[] (absolute values)
// ============================================
else if ($action === 'update_received') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) { echo json_encode(['success'=>false,'error'=>'PO not found']); exit; }
    if (!in_array($currentStatus, ['Approved','PartialReceived','Submitted'])) { echo json_encode(['success'=>false,'error'=>'PO not in a state to receive items']); exit; }

    $itemIds = $_POST['item_id'] ?? [];
    $qtys = $_POST['quantity_received'] ?? [];
    if (!is_array($itemIds) || !is_array($qtys) || count($itemIds) !== count($qtys)) { echo json_encode(['success'=>false,'error'=>'Invalid input']); exit; }

    $changed = 0;
    for ($i=0;$i<count($itemIds);$i++) {
        $itId = intval($itemIds[$i]);
        $recv = floatval($qtys[$i]);
        if ($itId<=0) continue;
        if ($recv < 0) { echo json_encode(['success'=>false,'error'=>'Negative quantities not allowed']); exit; }

        // fetch ordered qty for validation
        $s = $connect->prepare("SELECT quantity_ordered FROM po_items WHERE po_item_id = ? LIMIT 1");
        if (!$s) continue;
        $s->bind_param('i',$itId);
        $s->execute(); $r = $s->get_result(); $row = $r->fetch_assoc(); $s->close();
        $ordered = floatval($row['quantity_ordered'] ?? 0);
        if ($recv > $ordered) { echo json_encode(['success'=>false,'error'=>"Received qty cannot exceed ordered for item $itId"]); exit; }

        $u = $connect->prepare("UPDATE po_items SET quantity_received = ?, item_status = CASE WHEN ? >= quantity_ordered THEN 'Received' ELSE 'PartialReceived' END WHERE po_item_id = ?");
        if (!$u) continue;
        $u->bind_param('dii', $recv, $recv, $itId);
        if ($u->execute()) $changed++;
        $u->close();
    }

    // recalc PO status
    $newStatus = recalc_po_status_from_items($connect, $poId);
    if ($newStatus) {
        $up = $connect->prepare("UPDATE purchase_orders SET po_status=?, updated_at=NOW() WHERE po_id=?");
        if ($up) { $up->bind_param('si',$newStatus,$poId); $up->execute(); $up->close(); }
    }

    echo json_encode(['success'=>true,'message'=>'Received quantities updated','changed'=>$changed,'new_status'=>$newStatus]);
    exit;
}

// ============================================
// CLOSE PO (Received -> Closed)
// ============================================
else if ($action === 'close_po') {
    $currentStatus = get_po_status($connect, $poId);
    if (is_null($currentStatus)) { echo json_encode(['success'=>false,'error'=>'PO not found']); exit; }
    // ensure all items fully received
    $statusFromItems = recalc_po_status_from_items($connect, $poId);
    if ($statusFromItems !== 'Received') { echo json_encode(['success'=>false,'error'=>'All items must be fully received before closing']); exit; }
    $stmt = $connect->prepare("UPDATE purchase_orders SET po_status='Closed', updated_at=NOW() WHERE po_id=?");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'DB error']); exit; }
    $stmt->bind_param('i',$poId);
    if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'PO closed','new_status'=>'Closed']); } else { echo json_encode(['success'=>false,'error'=>'Failed to close PO']); }
    $stmt->close();
    exit;
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
