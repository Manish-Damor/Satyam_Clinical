<?php
/**
 * GET NEXT INVOICE NUMBER
 * Generates the next sales invoice number
 * Format: SLS-YYYY-00001
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'invoice_number' => '',
    'message' => ''
];

try {
    $currentYear = date('Y');
    
    // Query format: SLS-YYYY-XXXXX
    $stmt = $connect->prepare("
        SELECT 
            MAX(CAST(SUBSTRING(invoice_number, -5) AS UNSIGNED)) as max_num
        FROM sales_invoices 
        WHERE invoice_number LIKE ?
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $connect->error);
    }
    
    $pattern = 'SLS-' . $currentYear . '-%';
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $nextNum = ($row['max_num'] ?? 0) + 1;
    
    // Format: SLS-2026-00001
    $invoiceNumber = 'SLS-' . $currentYear . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    
    $response['success'] = true;
    $response['invoice_number'] = $invoiceNumber;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
