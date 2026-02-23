<?php
/**
 * UPDATE SALES INVOICE
 * Prepared statements for security
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (empty($_POST['invoice_id'])) {
        throw new Exception('Invoice ID is required');
    }
    
    // Get form data
    $invoiceId = intval($_POST['invoice_id']);
    $clientId = intval($_POST['client_id']);
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
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Update main invoice
        $updateInvoice = $connect->prepare("
            UPDATE sales_invoices
            SET client_id = ?, invoice_date = ?, due_date = ?, delivery_address = ?,
                subtotal = ?, discount_amount = ?, discount_percent = ?, 
                gst_amount = ?, grand_total = ?, paid_amount = ?, due_amount = ?,
                payment_type = ?, payment_place = ?,
                invoice_status = ?, payment_status = ?, updated_by = ?, updated_at = NOW()
            WHERE invoice_id = ?
        ");
        
        $updateInvoice->bind_param(
            'issddddddddsssii',
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
            $userId,
            $invoiceId
        );
        
        if (!$updateInvoice->execute()) {
            throw new Exception('Failed to update invoice: ' . $updateInvoice->error);
        }
        
        // Delete existing items
        $deleteItems = $connect->prepare("DELETE FROM sales_invoice_items WHERE invoice_id = ?");
        $deleteItems->bind_param('i', $invoiceId);
        $deleteItems->execute();
        
        // Insert new items
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
        
        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['message'] = 'Invoice updated successfully';
        
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
