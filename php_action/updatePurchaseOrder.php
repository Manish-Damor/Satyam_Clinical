<?php
require_once 'core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../po_list.php');
    exit;
}

try {
    if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
        throw new Exception('Session expired');
    }
    $userId = intval($_SESSION['userId']);
    $connect;

    $poId = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;
    if ($poId <= 0) throw new Exception('Missing PO ID');

    // Ensure PO is still Draft before allowing update
    $statusRes = $connect->query("SELECT po_status FROM purchase_orders WHERE po_id = $poId LIMIT 1");
    if ($statusRes && $statusRes->num_rows) {
        $row = $statusRes->fetch_assoc();
        if ($row['po_status'] !== 'Draft') {
            throw new Exception('Only Draft POs can be edited');
        }
    } else {
        throw new Exception('PO not found');
    }

    // collect header same as create
    $poNumber = isset($_POST['po_number']) ? trim($_POST['po_number']) : '';
    $poDate = isset($_POST['po_date']) ? trim($_POST['po_date']) : date('Y-m-d');
    // po_type and reference_number still retrieved for compatibility but not saved
    $poType = isset($_POST['po_type']) ? trim($_POST['po_type']) : 'Regular';
    $referenceNumber = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
    $supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $expectedDeliveryDate = isset($_POST['expected_delivery_date']) ? trim($_POST['expected_delivery_date']) : null;
    $deliveryLocation = isset($_POST['delivery_location']) ? trim($_POST['delivery_location']) : 'Main Warehouse';
    $subtotal = isset($_POST['sub_total']) ? floatval($_POST['sub_total']) : 0;
    $discountPercent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;
    $discountAmount = isset($_POST['total_discount']) ? floatval($_POST['total_discount']) : 0;
    // header fields may not all be present in simplified form
    $gstPercent = isset($_POST['gst_percent']) ? floatval($_POST['gst_percent']) : 0;
    $gstAmount = isset($_POST['gst_amount']) ? floatval($_POST['gst_amount']) : 0;
    $otherCharges = isset($_POST['other_charges']) ? floatval($_POST['other_charges']) : 0;
    $grandTotal = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0;
    $poStatus = isset($_POST['po_status']) ? trim($_POST['po_status']) : 'Draft';
    $paymentStatus = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'NotDue';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // update header
    $stmt = $connect->prepare("UPDATE purchase_orders SET 
        po_number = ?, po_date = ?, supplier_id = ?, expected_delivery_date = ?, delivery_location = ?,
        subtotal = ?, discount_percentage = ?, discount_amount = ?, gst_percentage = ?, gst_amount = ?,
        other_charges = ?, grand_total = ?, po_status = ?, payment_status = ?, notes = ?, updated_at = NOW()
        WHERE po_id = ?");
    if (!$stmt) throw new Exception('Prepare failed');

    $stmt->bind_param('ssissdddddddsssi',
        $poNumber, $poDate, $supplierId, $expectedDeliveryDate, $deliveryLocation,
        $subtotal, $discountPercent, $discountAmount, $gstPercent, $gstAmount,
        $otherCharges, $grandTotal, $poStatus, $paymentStatus, $notes, $poId
    );
    if (!$stmt->execute()) throw new Exception('Update header failed: ' . $stmt->error);
    $stmt->close();

    // clear existing items
    $connect->query("DELETE FROM po_items WHERE po_id = $poId");

    // insert items similar to create
    $itemCount = isset($_POST['item_count']) ? intval($_POST['item_count']) : 0;
    $itemsAdded = 0;
    for ($i = 0; $i < $itemCount; $i++) {
        // Accept medicine_id[] and quantity[] from simplified form
        $productId = isset($_POST['medicine_id'][$i]) ? intval($_POST['medicine_id'][$i]) : 0;
        $quantity = isset($_POST['quantity'][$i]) ? intval($_POST['quantity'][$i]) : 0;
        $unitPrice = isset($_POST['unit_price'][$i]) ? floatval($_POST['unit_price'][$i]) : 0;
        if ($productId<=0 || $quantity<=0) continue;
        $totalPrice = $quantity * $unitPrice;
        $itemStatus = 'Pending';
        // include gst_percentage for each item
        $itemSql = "INSERT INTO po_items (
            po_id, product_id, quantity_ordered, quantity_received,
            unit_price, total_price, gst_percentage, item_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $itemStmt = $connect->prepare($itemSql);
        if (!$itemStmt) throw new Exception('Prepare item failed');
        $qty_received = 0;
        $gstPct = isset($_POST['gst_percentage'][$i]) ? floatval($_POST['gst_percentage'][$i]) : 0;
        $itemStmt->bind_param('iiiiddds', $poId, $productId, $quantity, $qty_received, $unitPrice, $totalPrice, $gstPct, $itemStatus);
        if ($itemStmt->execute()) $itemsAdded++;
        $itemStmt->close();
    }
    
    if ($itemsAdded===0) {
        throw new Exception('No items provided');
    }

    $_SESSION['success'] = "PO updated successfully";
    header('Location: ../po_list.php');
    exit;

} catch(Exception $e) {
    // keep user input so it can be repopulated
    $_SESSION['old_post'] = $_POST;
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header('Location: ../po_list.php');
    exit;
}
?>