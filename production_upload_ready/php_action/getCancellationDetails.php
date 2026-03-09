<?php
header('Content-Type: application/json');
require_once 'core.php';

$poId = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if(!$poId) {
    echo json_encode(['error' => 'PO ID not provided']);
    exit;
}

try {
    $sql = "SELECT po.*, cl.cancellation_date, cl.cancellation_reason, cl.reason_details as cancellation_details, 
            cl.refund_status, cl.refund_amount
            FROM purchase_order po 
            LEFT JOIN po_cancellation_log cl ON po.po_id = cl.po_id
            WHERE po.po_id = $poId";
    
    $result = $connect->query($sql);
    
    if($result->num_rows == 0) {
        throw new Exception("PO not found");
    }
    
    $po = $result->fetch_assoc();
    
    // Get items
    $itemsSql = "SELECT medicine_name, quantity_ordered, unit_price, item_total 
                FROM purchase_order_items 
                WHERE po_id = $poId";
    
    $itemsResult = $connect->query($itemsSql);
    $items = [];
    
    while($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    
    $po['items'] = $items;
    $po['grand_total'] = number_format($po['grand_total'], 2);
    
    echo json_encode($po);
    
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$connect->close();
?>
