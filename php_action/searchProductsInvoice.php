<?php
/**
 * SEARCH PRODUCTS FOR INVOICE
 * Autocomplete handler for product search
 * Prepared statements for security
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [];

try {
    $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($searchTerm) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $searchPattern = '%' . $searchTerm . '%';
    
    // Search products by name, content, or HSN code - removed status filter to check all products
    $stmt = $connect->prepare("
        SELECT 
            product_id,
            product_name,
            content,
            pack_size,
            hsn_code,
            expected_mrp,
            gst_rate
        FROM product
        WHERE (product_name LIKE ? OR content LIKE ? OR hsn_code LIKE ?)
        ORDER BY product_name ASC
        LIMIT 20
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $stmt->bind_param('sss', $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'content' => $row['content'],
            'pack_size' => $row['pack_size'],
            'hsn_code' => $row['hsn_code'],
            'expected_mrp' => $row['expected_mrp'],
            'gst_rate' => $row['gst_rate']
        ];
    }
    
} catch (Exception $e) {
    // Return empty for errors
}

echo json_encode($response);
?>
