<?php
/**
 * FETCH PRODUCT FOR INVOICE
 * Returns product details including PTR (purchase rate) for display
 * Prepared statements for security
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    if (empty($_POST['product_id'])) {
        throw new Exception('Product ID required');
    }
    
    $productId = intval($_POST['product_id']);
    
    // Fetch product with all pricing info
    $stmt = $connect->prepare("
        SELECT 
            product_id,
            product_name,
            content,
            pack_size,
            hsn_code,
            expected_mrp as selling_price,
            purchase_rate as ptr,
            gst_rate,
            reorder_level,
            status
        FROM product
        WHERE product_id = ? AND status = 1
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Product not found or inactive');
    }
    
    $product = $result->fetch_assoc();
    
    // Fetch available batches with quantities
    $batchStmt = $connect->prepare("
        SELECT 
            b.batch_id,
            b.batch_number,
            b.expiry_date,
            COALESCE(SUM(sm.quantity_in - sm.quantity_out), 0) as available_quantity
        FROM medicine_batch b
        LEFT JOIN stock_movements sm ON b.batch_id = sm.batch_id
        WHERE b.product_id = ? 
        AND b.status = 'active'
        GROUP BY b.batch_id
        HAVING available_quantity > 0
        ORDER BY b.expiry_date ASC
    ");
    
    $batchStmt->bind_param('i', $productId);
    $batchStmt->execute();
    $batchResult = $batchStmt->get_result();
    
    $batches = [];
    while ($batch = $batchResult->fetch_assoc()) {
        $batches[] = $batch;
    }
    
    $response['success'] = true;
    $response['data'] = [
        'product' => $product,
        'batches' => $batches
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
