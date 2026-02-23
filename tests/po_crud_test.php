<?php
// Test script to verify create/update purchase order logic programmatically.
require_once __DIR__ . '/../php_action/core.php';
session_start();
$_SESSION['userId'] = 1; // assume user 1 exists

function createPo($connect, $data) {
    // replicate createPurchaseOrder logic, simplified
    $poNumber = $data['po_number'];
    $poDate = $data['po_date'];
    $poType = $data['po_type'];
    $referenceNumber = $data['reference_number'];
    $supplierId = intval($data['supplier_id']);
    $expectedDeliveryDate = $data['expected_delivery_date'] ?: null;
    $deliveryLocation = $data['delivery_location'];

    $subtotal = floatval($data['sub_total']);
    $discountPercent = floatval($data['discount_percent']);
    $discountAmount = floatval($data['total_discount']);
    $gstPercent = floatval($data['gst_percent']);
    $gstAmount = floatval($data['gst_amount']);
    $otherCharges = floatval($data['other_charges']);
    $grandTotal = floatval($data['grand_total']);

    // simple validation
    if (empty($poNumber)) throw new Exception('PO number required');
    if ($supplierId <= 0) throw new Exception('Supplier required');

    $poStatus = 'Draft';
    $paymentStatus = 'NotDue';
    $notes = 'CLI test create';
    $userId = intval($_SESSION['userId']);

    $sql = "INSERT INTO purchase_orders (
                po_number, po_date, po_type, reference_number, supplier_id, expected_delivery_date, delivery_location,
                subtotal, discount_percentage, discount_amount, gst_percentage, gst_amount,
                other_charges, grand_total, po_status, payment_status, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception('Prepare failed: '.$connect->error);
    $stmt->bind_param(
        'ssssisssddddddsssi',
        $poNumber, $poDate, $poType, $referenceNumber, $supplierId, $expectedDeliveryDate, $deliveryLocation,
        $subtotal, $discountPercent, $discountAmount, $gstPercent, $gstAmount,
        $otherCharges, $grandTotal, $poStatus, $paymentStatus, $notes, $userId
    );
    if (!$stmt->execute()) {
        throw new Exception('Insert error: '.$stmt->error);
    }
    $poId = $connect->insert_id;
    $stmt->close();

    // items
    $itemCount = intval($data['item_count'] ?? 0);
    for ($i=0;$i<$itemCount;$i++) {
        $productId = intval($data['product_id'][$i] ?? 0);
        $qty = intval($data['quantity_ordered'][$i] ?? 0);
        $unitPrice = floatval($data['unit_price'][$i] ?? 0);
        if ($productId <=0 || $qty <=0) continue;
        $totalPrice = $qty * $unitPrice;
        $itemStatus = 'Pending';
        $itemSql = "INSERT INTO po_items (po_id, product_id, quantity_ordered, quantity_received, unit_price, total_price, item_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $itemStmt = $connect->prepare($itemSql);
        $qty_received=0;
        $itemStmt->bind_param('iiiidds',$poId,$productId,$qty,$qty_received,$unitPrice,$totalPrice,$itemStatus);
        $itemStmt->execute();
        $itemStmt->close();
    }

    return $poId;
}

function updatePo($connect, $poId, $data) {
    // replicate updatePurchaseOrder logic
    $poNumber = $data['po_number'];
    $poDate = $data['po_date'];
    $poType = $data['po_type'];
    $referenceNumber = $data['reference_number'];
    $supplierId = intval($data['supplier_id']);
    $expectedDeliveryDate = $data['expected_delivery_date'] ?: null;
    $deliveryLocation = $data['delivery_location'];

    $subtotal = floatval($data['sub_total']);
    $discountPercent = floatval($data['discount_percent']);
    $discountAmount = floatval($data['total_discount']);
    $gstPercent = floatval($data['gst_percent']);
    $gstAmount = floatval($data['gst_amount']);
    $otherCharges = floatval($data['other_charges']);
    $grandTotal = floatval($data['grand_total']);
    $poStatus = $data['po_status'] ?? 'Draft';
    $paymentStatus = $data['payment_status'] ?? 'NotDue';
    $notes = $data['notes'] ?? '';

    $stmt = $connect->prepare("UPDATE purchase_orders SET 
        po_number = ?, po_date = ?, po_type = ?, reference_number = ?, supplier_id = ?, expected_delivery_date = ?, delivery_location = ?,
        subtotal = ?, discount_percentage = ?, discount_amount = ?, gst_percentage = ?, gst_amount = ?,
        other_charges = ?, grand_total = ?, po_status = ?, payment_status = ?, notes = ?, updated_at = NOW()
        WHERE po_id = ?");
    if (!$stmt) throw new Exception('Prepare failed2');
    // bind parameters dynamically to avoid mismatch issues
    $types = 'ssssisssdddddddsssi';
    $params = [
        & $poNumber, & $poDate, & $poType, & $referenceNumber, & $supplierId, & $expectedDeliveryDate, & $deliveryLocation,
        & $subtotal, & $discountPercent, & $discountAmount, & $gstPercent, & $gstAmount,
        & $otherCharges, & $grandTotal, & $poStatus, & $paymentStatus, & $notes, & $poId
    ];
    echo "Binding parameters: types=",$types," len=",strlen($types)," params=",count($params)," poStatus=",var_export($poStatus,true)," (len=".strlen($poStatus).")\n";
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception('Update failed: '.$stmt->error);
    }
    echo "Update executed, affected_rows=".$stmt->affected_rows." error='".$stmt->error."'\n";
    $stmt->close();
    // delete existing items
    $connect->query("DELETE FROM po_items WHERE po_id = $poId");
    $itemCount = intval($data['item_count'] ?? 0);
    for ($i=0;$i<$itemCount;$i++) {
        $productId = intval($data['product_id'][$i] ?? 0);
        $qty = intval($data['quantity_ordered'][$i] ?? 0);
        $unitPrice = floatval($data['unit_price'][$i] ?? 0);
        if ($productId <=0 || $qty <=0) continue;
        $totalPrice = $qty * $unitPrice;
        $itemStatus = 'Pending';
        $itemSql = "INSERT INTO po_items (po_id, product_id, quantity_ordered, quantity_received, unit_price, total_price, item_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $itemStmt = $connect->prepare($itemSql);
        $qty_received=0;
        $itemStmt->bind_param('iiiidds',$poId,$productId,$qty,$qty_received,$unitPrice,$totalPrice,$itemStatus);
        $itemStmt->execute();
        $itemStmt->close();
    }
}

function dumpPo($connect, $poId) {
    $res = $connect->query("SELECT * FROM purchase_orders WHERE po_id = $poId");
    $po = $res->fetch_assoc();
    echo "PO header:\n";
    print_r($po);
    $res2 = $connect->query("SELECT * FROM po_items WHERE po_id = $poId");
    echo "Items:\n";
    while($row=$res2->fetch_assoc()) print_r($row);
}

try {
    echo "Creating PO...\n";
    $data = [
        'po_number'=>'CLI-TEST-'.time(),
        'po_date'=>date('Y-m-d'),
        'po_type'=>'Express',
        'reference_number'=>'REFCLI',
        'supplier_id'=>1,
        'expected_delivery_date'=>'2026-03-01',
        'delivery_location'=>'Test Loc',
        'sub_total'=>500,
        'discount_percent'=>0,
        'total_discount'=>0,
        'gst_percent'=>0,
        'gst_amount'=>0,
        'other_charges'=>0,
        'grand_total'=>500,
        'item_count'=>2,
        'product_id'=>[1,2],
        'quantity_ordered'=>[5,5],
        'unit_price'=>[50,50]
    ];
    $poId = createPo($connect, $data);
    echo "Created PO ID: $poId\n";
    dumpPo($connect, $poId);
    echo "Updating PO...\n";
    $data['po_type']='Urgent';
    $data['reference_number']='UPDATED';
    $data['delivery_location']='NewLoc';
    $data['grand_total']=1000;
    $data['item_count']=1;
    $data['product_id']=[3];
    $data['quantity_ordered']=[10];
    $data['unit_price']=[100];
    $data['po_status']='Approved';
    updatePo($connect,$poId,$data);
    echo "After update:\n";
    dumpPo($connect,$poId);
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

