<?php
/**
 * ADD INVOICE PAYMENT TRANSACTION
 * Records payment/adjustment for an invoice
 * Tracks all payment transactions with details
 */

header('Content-Type: application/json');
require_once 'json_core.php';

$response = [
    'success' => false,
    'message' => '',
    'transaction_id' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    $transactionType = trim($_POST['transaction_type'] ?? 'PAYMENT');
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $referenceNo = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $sessionUserId = $_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null);
    $userId = (is_numeric($sessionUserId) && intval($sessionUserId) > 0)
        ? intval($sessionUserId)
        : 0;

    if ($userId <= 0 && PHP_SAPI === 'cli') {
        $userId = 1;
    }

    if ($userId <= 0) {
        throw new Exception('Unauthorized user session');
    }
    
    if ($invoiceId <= 0) {
        throw new Exception('Invoice ID required');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    // Validate transaction type
    $validTypes = ['PAYMENT', 'DISCOUNT', 'ADJUSTMENT'];
    if (!in_array($transactionType, $validTypes)) {
        throw new Exception('Invalid transaction type');
    }
    
    // Verify invoice exists
    $invoiceCheck = $connect->prepare("SELECT invoice_id, client_id, payment_type, paid_amount, due_amount, grand_total, submitted_at FROM sales_invoices WHERE invoice_id = ?");
    $invoiceCheck->bind_param('i', $invoiceId);
    $invoiceCheck->execute();
    $invoiceResult = $invoiceCheck->get_result();
    
    if ($invoiceResult->num_rows === 0) {
        throw new Exception('Invoice not found');
    }
    
    $invoiceData = $invoiceResult->fetch_assoc();
    if (empty($invoiceData['submitted_at'])) {
        throw new Exception('Cannot record payment for draft invoice');
    }
    $clientId = $invoiceData['client_id'];
    $invoicePaymentType = $invoiceData['payment_type'];
    $currentPaidAmount = $invoiceData['paid_amount'];
    $currentDueAmount = $invoiceData['due_amount'];
    $grandTotal = $invoiceData['grand_total'];
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Insert transaction record
        $insertTxn = $connect->prepare("
            INSERT INTO invoice_transactions
            (invoice_id, transaction_type, amount, payment_method, reference_number, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertTxn->bind_param(
            'isdsssi',
            $invoiceId,
            $transactionType,
            $amount,
            $paymentMethod,
            $referenceNo,
            $notes,
            $userId
        );
        
        if (!$insertTxn->execute()) {
            throw new Exception('Failed to record transaction: ' . $insertTxn->error);
        }
        
        $transactionId = $connect->insert_id;
        
        // Update invoice payment status
        if ($transactionType === 'PAYMENT') {
            $newPaidAmount = $currentPaidAmount + $amount;
            
            // Validate payment doesn't exceed invoice total
            if ($newPaidAmount > $grandTotal) {
                $newPaidAmount = $grandTotal;
            }
            
            $newDueAmount = $grandTotal - $newPaidAmount;
            
            // Determine payment status
            if ($newPaidAmount >= $grandTotal) {
                $paymentStatus = 'PAID';
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'PARTIAL';
            } else {
                $paymentStatus = 'UNPAID';
            }
            
            // Update invoice with payment_received_date
            $updateInvoice = $connect->prepare("
                UPDATE sales_invoices 
                SET paid_amount = ?, due_amount = ?, payment_status = ?, updated_at = NOW()
                WHERE invoice_id = ?
            ");
            
            $updateInvoice->bind_param(
                'ddsi',
                $newPaidAmount,
                $newDueAmount,
                $paymentStatus,
                $invoiceId
            );
            
            if (!$updateInvoice->execute()) {
                throw new Exception('Failed to update invoice: ' . $updateInvoice->error);
            }
            
            // If this is a CREDIT invoice, reduce client's outstanding balance
            if (strtolower($invoicePaymentType) === 'credit') {
                $updateBalance = $connect->prepare("
                    UPDATE clients SET outstanding_balance = outstanding_balance - ? WHERE client_id = ?
                ");
                $adjustBalanceAmount = max(0, min($amount, $currentDueAmount));
                $updateBalance->bind_param('di', $adjustBalanceAmount, $clientId);
                if (!$updateBalance->execute()) {
                    throw new Exception('Failed to update client balance: ' . $updateBalance->error);
                }
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['message'] = 'Payment transaction recorded successfully';
        $response['transaction_id'] = $transactionId;
        
    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
