<?php
/**
 * GET BATCH ALLOCATION PLAN & AUTOFILL
 * Called via AJAX when user selects product and enters quantity
 * Returns multi-batch allocation plan with warnings and autofill suggestions
 */

header('Content-Type: application/json');
require '../constant/connect.php';
require 'BatchQuantityHandler.php';

$response = [
    'success' => false,
    'data' => [],
    'warnings' => [],
    'canFulfill' => true,
    'message' => ''
];

try {
    if (empty($_POST['product_id']) || empty($_POST['quantity'])) {
        throw new Exception('Product ID and Quantity required');
    }

    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);

    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0');
    }

    // Initialize batch handler
    $handler = new BatchQuantityHandler($connect, $product_id, $quantity);

    // Check if quantity can be fulfilled
    if (!$handler->canFulfill()) {
        $response['canFulfill'] = false;
        $warnings = $handler->getWarnings();
        foreach ($warnings as $w) {
            $response['warnings'][] = $w;
        }
    }

    // Generate automatic allocation plan
    $allocationPlan = $handler->generateAllocationPlan();

    // If no warnings after generation, check again
    if (empty($response['warnings'])) {
        $warnings = $handler->getWarnings();
        foreach ($warnings as $w) {
            $response['warnings'][] = $w;
        }
    }

    $summary = $handler->getAllocationSummary();

    $response['success'] = true;
    $response['data'] = [
        'product_id' => $product_id,
        'required_quantity' => $quantity,
        'allocation_plan' => $allocationPlan,
        'summary' => $summary,
        'total_allocated' => $summary['total_allocated'],
        'batch_count' => $summary['batch_count'],
        'is_complete' => $summary['is_complete']
    ];

    if ($handler->isInsufficient()) {
        $response['canFulfill'] = false;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
