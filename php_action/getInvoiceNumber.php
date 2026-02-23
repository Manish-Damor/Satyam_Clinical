<?php
/**
 * GET NEXT INVOICE NUMBER
 * Generates INV-YY-NNNNN format with annual reset
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
    $shortYear = date('y');
    
    // Get or create invoice sequence for current year
    $stmt = $connect->prepare("
        SELECT next_number FROM invoice_sequence WHERE year = ?
    ");
    $stmt->bind_param('i', $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nextNum = $row['next_number'];
    } else {
        // Create sequence if it doesn't exist (first invoice of year)
        $insertStmt = $connect->prepare("
            INSERT INTO invoice_sequence (year, next_number, last_reset) 
            VALUES (?, 1, NOW())
        ");
        $insertStmt->bind_param('i', $currentYear);
        $insertStmt->execute();
        $nextNum = 1;
    }
    
    // Generate invoice number
    $invoiceNumber = 'INV-' . $shortYear . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    
    $response['success'] = true;
    $response['invoice_number'] = $invoiceNumber;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
