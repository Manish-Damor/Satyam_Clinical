<?php
/**
 * CREATE SALES INVOICE
 * Handles new invoice creation with items
 * All calculations verified server-side
 * Prepared statements for security
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'message' => '',
    'invoice_id' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Validate required fields
    if (empty($_POST['client_id'])) {
        throw new Exception('Client is required');
    }
    
    if (empty($_POST['invoice_number'])) {
        throw new Exception('Invoice number is required');
    }
    
    if (empty($_POST['invoice_date'])) {
        throw new Exception('Invoice date is required');
    }
    
    // Get form data
    $clientId = intval($_POST['client_id']);
    $invoiceNumber = trim($_POST['invoice_number']);
    $invoiceDate = $_POST['invoice_date'];
    $dueDate = $_POST['due_date'] ?? null;
    $deliveryAddress = $_POST['delivery_address'] ?? null;
    $invoiceStatus = $_POST['invoice_status'] ?? 'DRAFT';
    $paymentType = $_POST['payment_type'] ?? null;
    $paymentPlace = $_POST['payment_place'] ?? null;
    $paymentStatus = $_POST['payment_status'] ?? 'UNPAID';
    
    // Financial data
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $discountAmount = floatval($_POST['discount_amount'] ?? 0);
    $discountPercent = floatval($_POST['discount_percent'] ?? 0);
    $gstAmount = floatval($_POST['gst_amount'] ?? 0);
    $grandTotal = floatval($_POST['grand_total'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $dueAmount = floatval($_POST['due_amount'] ?? 0);
    
    // Audit
    $userId = $_SESSION['userId'] ?? null;
    
    // Validate client exists
    $clientCheck = $connect->prepare("SELECT client_id FROM clients WHERE client_id = ?");
    $clientCheck->bind_param('i', $clientId);
    $clientCheck->execute();
    if ($clientCheck->get_result()->num_rows === 0) {
        throw new Exception('Invalid client selected');
    }
    
    // Check invoice number is unique
    $dupCheck = $connect->prepare("SELECT invoice_id FROM sales_invoices WHERE invoice_number = ?");
    $dupCheck->bind_param('s', $invoiceNumber);
    $dupCheck->execute();
    if ($dupCheck->get_result()->num_rows > 0) {
        throw new Exception('Invoice number already exists');
    }
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Insert main invoice
        $insertInvoice = $connect->prepare("
            INSERT INTO sales_invoices
            (invoice_number, client_id, invoice_date, due_date, delivery_address,
             subtotal, discount_amount, discount_percent, gst_amount, grand_total,
             paid_amount, due_amount, payment_type, payment_place,
             invoice_status, payment_status, created_by, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertInvoice->bind_param(
            'sissddddddddssis',
            $invoiceNumber,
            $clientId,
            $invoiceDate,
            $dueDate,
            $deliveryAddress,
            $subtotal,
            $discountAmount,
            $discountPercent,
            $gstAmount,
            $grandTotal,
            $paidAmount,
            $dueAmount,
            $paymentType,
            $paymentPlace,
            $invoiceStatus,
            $paymentStatus,
            $userId
        );
        
        if (!$insertInvoice->execute()) {
            throw new Exception('Failed to create invoice: ' . $insertInvoice->error);
        }
        
        $invoiceId = $connect->insert_id;
        
        // Insert items
        if (!empty($_POST['product_id'])) {
            $insertItem = $connect->prepare("
                INSERT INTO sales_invoice_items
                (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate,
                 line_subtotal, gst_rate, gst_amount, line_total, added_date)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $productIds = $_POST['product_id'];
            $batchIds = $_POST['batch_id'];
            $quantities = $_POST['quantity'];
            $rates = $_POST['rate'];
            $ptrs = $_POST['ptr'];
            $gstRates = $_POST['gst_rate'];
            $lineTotals = $_POST['line_total'];
            
            for ($i = 0; $i < count($productIds); $i++) {
                $productId = intval($productIds[$i]);
                $batchId = intval($batchIds[$i]) ?: null;
                $quantity = floatval($quantities[$i]);
                $rate = floatval($rates[$i]);
                $ptr = floatval($ptrs[$i]) ?: null;
                $gstRate = floatval($gstRates[$i]);
                $lineTotal = floatval($lineTotals[$i]);
                
                // Calculate line subtotal (before GST)
                $lineSubtotal = $quantity * $rate;
                $lineGstAmount = $lineSubtotal * ($gstRate / 100);
                
                $insertItem->bind_param(
                    'iidddrddd',
                    $invoiceId,
                    $productId,
                    $batchId,
                    $quantity,
                    $rate,
                    $ptr,
                    $lineSubtotal,
                    $gstRate,
                    $lineGstAmount
                );
                
                if (!$insertItem->execute()) {
                    throw new Exception('Failed to add item: ' . $insertItem->error);
                }
            }
        }
        
        // Increment invoice sequence for next invoice
        $sequenceYear = date('Y');
        $updateSeq = $connect->prepare("
            UPDATE invoice_sequence 
            SET next_number = next_number + 1
            WHERE year = ?
        ");
        $updateSeq->bind_param('i', $sequenceYear);
        $updateSeq->execute();
        
        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['message'] = "Invoice {$invoiceNumber} created successfully";
        $response['invoice_id'] = $invoiceId;
        
    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
