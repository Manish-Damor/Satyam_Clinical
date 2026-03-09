<?php

header('Content-Type: application/json');
require_once 'json_core.php';

$response = [
    'success' => false,
    'message' => '',
];

try {
    $productId = isset($_POST['productId']) ? (int) $_POST['productId'] : 0;
    if ($productId <= 0) {
        throw new Exception('Product ID is required');
    }

    $productStmt = $connect->prepare(
        "SELECT product_id, product_name, brand_id, categories_id, hsn_code, gst_rate,
                COALESCE(expected_mrp, 0) AS selling_rate,
                COALESCE(purchase_rate, 0) AS purchase_rate
         FROM product
         WHERE product_id = ? AND status = 1
         LIMIT 1"
    );

    if (!$productStmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }

    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();

    if (!$productResult || $productResult->num_rows === 0) {
        throw new Exception('Product not found or inactive');
    }

    $product = $productResult->fetch_assoc();
    $productStmt->close();

    $batchStmt = $connect->prepare(
        "SELECT batch_id, batch_number, expiry_date, available_quantity, purchase_rate, mrp, status
         FROM product_batches
         WHERE product_id = ?
           AND LOWER(status) = 'active'
           AND COALESCE(available_quantity, 0) > 0
         ORDER BY expiry_date ASC, batch_id ASC"
    );

    if (!$batchStmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }

    $batchStmt->bind_param('i', $productId);
    $batchStmt->execute();
    $batchResult = $batchStmt->get_result();

    $batches = [];
    $totalQuantity = 0;
    $batchPurchaseRate = 0.0;

    while ($batchResult && ($batchRow = $batchResult->fetch_assoc())) {
        $batches[] = [
            'batch_id' => (int) $batchRow['batch_id'],
            'batch_number' => (string) $batchRow['batch_number'],
            'expiry_date' => (string) $batchRow['expiry_date'],
            'available_quantity' => (int) $batchRow['available_quantity'],
            'status' => (string) $batchRow['status'],
        ];

        $totalQuantity += (int) $batchRow['available_quantity'];
        if ($batchPurchaseRate <= 0 && isset($batchRow['purchase_rate'])) {
            $batchPurchaseRate = (float) $batchRow['purchase_rate'];
        }
    }

    $batchStmt->close();

    $purchaseRate = (float) $product['purchase_rate'];
    if ($purchaseRate <= 0 && $batchPurchaseRate > 0) {
        $purchaseRate = $batchPurchaseRate;
    }

    $response = [
        'success' => true,
        'product_id' => (int) $product['product_id'],
        'product_name' => (string) $product['product_name'],
        'brand_id' => (int) $product['brand_id'],
        'categories_id' => (int) $product['categories_id'],
        'hsn_code' => (string) $product['hsn_code'],
        'gst_rate' => (float) $product['gst_rate'],
        // Legacy consumers expect `rate` and `quantity` keys.
        'rate' => (float) $product['selling_rate'],
        'quantity' => $totalQuantity,
        'purchase_rate' => $purchaseRate,
        'batches' => $batches,
    ];
} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);