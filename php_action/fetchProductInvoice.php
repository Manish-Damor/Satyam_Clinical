<?php
/**
 * FETCH PRODUCT FOR INVOICE
 * Returns product details including PTR (purchase rate) for display
 * Prepared statements for security
 */

header('Content-Type: application/json');
require_once 'json_core.php';

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
            p.product_id,
            p.product_name,
            p.content,
            p.pack_size,
            p.hsn_code,
            p.expected_mrp as selling_price,
            p.purchase_rate as ptr,
            p.gst_rate,
            p.reorder_level,
            p.status,
            COALESCE(b.brand_name, '') AS brand_name
        FROM product p
        LEFT JOIN brands b ON b.brand_id = p.brand_id
        WHERE p.product_id = ? AND p.status = 1
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
    
    // Fetch available batches with quantities and batch-specific pricing
    $batchStmt = $connect->prepare("
        SELECT 
            b.batch_id,
            b.batch_number,
            b.expiry_date,
            COALESCE(b.available_quantity, 0) as available_quantity,
            b.mrp,
            b.purchase_rate
        FROM product_batches b
        WHERE b.product_id = ? 
        AND LOWER(b.status) = 'active'
        AND COALESCE(b.available_quantity, 0) > 0
        AND b.expiry_date >= CURDATE()
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
