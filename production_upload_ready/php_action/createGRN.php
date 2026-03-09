<?php

require_once 'core.php';
require_once '../config/bootstrap.php';

use Controllers\GRNController;

header('Content-Type: application/json');

$response = [
    'success' => false,
    'grn_id' => null,
    'grn_number' => null,
    'message' => 'Unknown error',
    'errors' => [],
    'quality_summary' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* SESSION VALIDATION */
        if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
            throw new Exception('Session expired. Please login again.');
        }

        $userId = intval($_SESSION['userId']);
        $userRole = $_SESSION['user_role'] ?? 'user';

        // ========================================
        // COLLECT GRN DATA
        // ========================================

        $grnDate = $_POST['grn_date'] ?? date('Y-m-d');
        $poId = intval($_POST['po_id'] ?? 0);
        $warehouseId = intval($_POST['warehouse_id'] ?? 1);
        $notes = $_POST['notes'] ?? '';

        if (!$poId) {
            throw new Exception('Purchase Order ID is required');
        }

        // ========================================
        // COLLECT ITEMS & QUALITY CHECKS
        // ========================================

        $items = [];
        $qualityChecks = [];

        $poItemIds = $_POST['po_item_id'] ?? [];
        $quantitiesReceived = $_POST['quantity_received'] ?? [];
        $batchNumbers = $_POST['batch_number'] ?? [];
        $expiryDates = $_POST['expiry_date'] ?? [];
        $checkResults = $_POST['check_result'] ?? [];
        $qualityNotes = $_POST['quality_notes'] ?? [];

        $itemCount = count($poItemIds);

        if ($itemCount === 0) {
            throw new Exception('No items to process');
        }

        for ($i = 0; $i < $itemCount; $i++) {

            $poItemId = intval($poItemIds[$i] ?? 0);
            $qtyReceived = intval($quantitiesReceived[$i] ?? 0);

            // Skip empty rows
            if ($poItemId <= 0) {
                continue;
            }

            // Only add item if quantity > 0
            if ($qtyReceived > 0) {
                $items[] = [
                    'po_item_id' => $poItemId,
                    'quantity_received' => $qtyReceived,
                    'batch_number' => $batchNumbers[$i] ?? '',
                    'expiry_date' => $expiryDates[$i] ?? null,
                    'product_id' => 0  // Will be fetched from PO item
                ];
            }

            // Quality check for this item
            $qualityChecks[$poItemId] = [
                'check_result' => $checkResults[$i] ?? 'passed',
                'notes' => $qualityNotes[$i] ?? ''
            ];
        }

        if (empty($items)) {
            throw new Exception('No items with quantity > 0');
        }

        // ========================================
        // PREPARE GRN DATA
        // ========================================

        $grnData = [
            'po_id' => $poId,
            'grn_date' => $grnDate,
            'warehouse_id' => $warehouseId,
            'received_by' => $userId,
            'notes' => $notes
        ];

        // ========================================
        // CREATE GRN USING CONTROLLER
        // ========================================

        $controller = new GRNController($connect, $userId, $userRole);
        $result = $controller->createGRN($grnData, $items, $qualityChecks);

        if ($result['success']) {
            $response['success'] = true;
            $response['grn_id'] = $result['grn_id'];
            $response['grn_number'] = $result['grn_number'];
            $response['message'] = $result['message'];
            $response['quality_summary'] = $result['quality_summary'];
        } else {
            $response['errors'] = $result['errors'];
            $response['message'] = $result['message'];
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        $response['errors'][] = $e->getMessage();
    }

} else {
    $response['message'] = 'Invalid request method (expected POST)';
}

echo json_encode($response);

?>
