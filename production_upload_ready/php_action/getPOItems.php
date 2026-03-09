<?php

require_once 'core.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'items' => [],
    'message' => 'Unknown error'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $poId = intval($_POST['po_id'] ?? 0);

        if (!$poId) {
            throw new Exception('PO ID is required');
        }

        // Fetch PO items
        $sql = "
            SELECT 
                poi.po_item_id,
                poi.medicine_id,
                poi.medicine_name,
                poi.batch_number,
                poi.expiry_date,
                poi.quantity_ordered
            FROM po_items poi
            WHERE poi.po_id = ?
            ORDER BY poi.po_item_id
        ";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $connect->error);
        }

        $stmt->bind_param("i", $poId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'po_item_id' => (int)$row['po_item_id'],
                'medicine_id' => (int)$row['medicine_id'],
                'medicine_name' => $row['medicine_name'],
                'batch_number' => $row['batch_number'],
                'expiry_date' => $row['expiry_date'],
                'quantity_ordered' => (int)$row['quantity_ordered']
            ];
        }

        $stmt->close();

        if (empty($items)) {
            throw new Exception('No items found for this PO');
        }

        $response['success'] = true;
        $response['items'] = $items;
        $response['message'] = 'Items loaded successfully';

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

} else {
    $response['message'] = 'Invalid request method (expected POST)';
}

echo json_encode($response);

?>
