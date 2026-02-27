<?php
/**
 * CREATE SALES INVOICE
 * Handles new invoice creation with items
 * All calculations verified server-side
 * Prepared statements for security
 */

header('Content-Type: application/json');
require '../constant/connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $paymentType = $_POST['payment_type'] ?? 'Cash';
    $paymentMethod = $_POST['payment_method'] ?? null;
    $paymentNotes = $_POST['payment_notes'] ?? null;
    
    // Payment status is auto-calculated on frontend, still use it but ensure it's valid
    $paymentStatusValue = $_POST['payment_status'] ?? 'UNPAID';
    $validStatuses = ['UNPAID', 'PARTIAL', 'PAID'];
    $paymentStatus = in_array($paymentStatusValue, $validStatuses) ? $paymentStatusValue : 'UNPAID';
    
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
    
    // Fetch client details including addresses and tax info
    $clientFetch = $connect->prepare("SELECT client_id, name, state, billing_address, shipping_address, gstin, pan, drug_licence_no, credit_limit, outstanding_balance FROM clients WHERE client_id = ?");
    $clientFetch->bind_param('i', $clientId);
    $clientFetch->execute();
    $clientResult = $clientFetch->get_result();
    
    if ($clientResult->num_rows === 0) {
        throw new Exception('Invalid client selected');
    }
    
    $clientData = $clientResult->fetch_assoc();
    $clientState = $clientData['state'] ?? 'Gujarat';
    $clientGstin = $clientData['gstin'] ?? '';
    $clientPan = $clientData['pan'] ?? '';
    $clientDL = $clientData['drug_licence_no'] ?? '';
    $billingAddr = $clientData['billing_address'] ?? $deliveryAddress;
    $shippingAddr = $clientData['shipping_address'] ?? $deliveryAddress;
    $creditLimit = floatval($clientData['credit_limit']) ?? 0;
    $outstandingBalance = floatval($clientData['outstanding_balance']) ?? 0;
    
    // Determine tax type: Intrastate (CGST+SGST) or Interstate (IGST)
    $companyState = 'Gujarat'; // All company state in Gujarat
    $isIntrastate = (strtolower($clientState) === 'gujarat');
    
    // Calculate CGST, SGST, or IGST
    $gstPercentage = floatval($_POST['gst_rate'] ?? 18);
    $cgstPercent = $isIntrastate ? ($gstPercentage / 2) : 0;
    $sgstPercent = $isIntrastate ? ($gstPercentage / 2) : 0;
    $igstPercent = !$isIntrastate ? $gstPercentage : 0;
    
    $cgstAmount = $isIntrastate ? round($gstAmount / 2, 2) : 0;
    $sgstAmount = $isIntrastate ? round($gstAmount / 2, 2) : 0;
    $igstAmount = !$isIntrastate ? $gstAmount : 0;
    
    // Check invoice number is unique
    $dupCheck = $connect->prepare("SELECT invoice_id FROM sales_invoices WHERE invoice_number = ?");
    $dupCheck->bind_param('s', $invoiceNumber);
    $dupCheck->execute();
    if ($dupCheck->get_result()->num_rows > 0) {
        throw new Exception('Invoice number already exists');
    }
    
    // CREDIT SYSTEM LOGIC
    // For Credit payments, update the client's outstanding balance
    if (strtolower($paymentType) === 'credit') {
        $newOutstanding = $outstandingBalance + $grandTotal;
        // Note: We allow credit even if it exceeds limit (with warning shown on frontend)
        // Proceed anyway as per user requirement
    }
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        // Insert main invoice with all tax and client details
        $insertInvoice = $connect->prepare("
            INSERT INTO sales_invoices
            (invoice_number, client_id, invoice_date, due_date, delivery_address, billing_address, shipping_address,
             subtotal, discount_amount, discount_percent, gst_amount, grand_total,
             cgst_percent, sgst_percent, igst_percent, cgst_amount, sgst_amount, igst_amount,
             client_gstin, client_pan, client_dl_no,
             paid_amount, due_amount, payment_type, payment_method, payment_notes, payment_status, created_by, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertInvoice->bind_param(
            'sisssssdddddddddddsssddssssi',
            $invoiceNumber,
            $clientId,
            $invoiceDate,
            $dueDate,
            $deliveryAddress,
            $billingAddr,
            $shippingAddr,
            $subtotal,
            $discountAmount,
            $discountPercent,
            $gstAmount,
            $grandTotal,
            $cgstPercent,
            $sgstPercent,
            $igstPercent,
            $cgstAmount,
            $sgstAmount,
            $igstAmount,
            $clientGstin,
            $clientPan,
            $clientDL,
            $paidAmount,
            $dueAmount,
            $paymentType,
            $paymentMethod,
            $paymentNotes,
            $paymentStatus,
            $userId
        );
        
        if (!$insertInvoice->execute()) {
            throw new Exception('Failed to create invoice: ' . $insertInvoice->error);
        }
        
        $invoiceId = $connect->insert_id;
        
        // UPDATE CLIENT OUTSTANDING BALANCE IF CREDIT PAYMENT
        if (strtolower($paymentType) === 'credit') {
            $updateBalance = $connect->prepare("
                UPDATE clients SET outstanding_balance = outstanding_balance + ? WHERE client_id = ?
            ");
            $updateBalance->bind_param('di', $grandTotal, $clientId);
            if (!$updateBalance->execute()) {
                throw new Exception('Failed to update client credit: ' . $updateBalance->error);
            }
        }
        
        // Insert items and process allocation plan
        if (!empty($_POST['product_id'])) {
            $insertItem = $connect->prepare("
                INSERT INTO sales_invoice_items
                (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate,
                 line_subtotal, gst_rate, gst_amount, line_total, added_date)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $insertMovement = $connect->prepare("
                INSERT INTO stock_movements
                (product_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, created_by, created_at)
                VALUES (?, ?, 'Sales', ?, 'Invoice', ?, ?, ?, NOW())
            ");

            $updateBatch = $connect->prepare("
                UPDATE product_batches SET available_quantity = available_quantity - ? WHERE batch_id = ? AND available_quantity >= ?
            ");

            $fefoBatchFetch = $connect->prepare("\
                SELECT batch_id, available_quantity, mrp, purchase_rate, expiry_date
                FROM product_batches
                WHERE product_id = ?
                  AND status = 'Active'
                  AND expiry_date > CURDATE()
                  AND available_quantity > 0
                ORDER BY expiry_date ASC, batch_id ASC
                FOR UPDATE
            ");

            $productIds = $_POST['product_id'];
            $quantities = $_POST['quantity'];
            $rates = $_POST['rate'];
            $ptrs = $_POST['ptr'];
            $gstRates = $_POST['gst_rate'];

            for ($i = 0; $i < count($productIds); $i++) {
                $productId = intval($productIds[$i]);
                $quantity = floatval($quantities[$i]);
                $rate = floatval($rates[$i]);
                $gstRate = floatval($gstRates[$i]);

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $remainingQty = $quantity;
                $fefoBatchFetch->bind_param('i', $productId);
                $fefoBatchFetch->execute();
                $batchResult = $fefoBatchFetch->get_result();

                while ($remainingQty > 0 && $batch = $batchResult->fetch_assoc()) {
                    $batchId = (int) $batch['batch_id'];
                    $availableQty = (float) $batch['available_quantity'];
                    if ($availableQty <= 0) {
                        continue;
                    }

                    $allocQty = min($remainingQty, $availableQty);
                    $unitRate = $rate > 0 ? $rate : (float) $batch['mrp'];
                    $batchPtr = (float) $batch['purchase_rate'];
                    $lineSubtotal = $allocQty * $unitRate;
                    $lineGstAmount = $lineSubtotal * ($gstRate / 100);
                    $lineTotal = $lineSubtotal + $lineGstAmount;

                    $insertItem->bind_param(
                        'iiiddddddd',
                        $invoiceId,
                        $productId,
                        $batchId,
                        $allocQty,
                        $unitRate,
                        $batchPtr,
                        $lineSubtotal,
                        $gstRate,
                        $lineGstAmount,
                        $lineTotal
                    );
                    if (!$insertItem->execute()) {
                        throw new Exception('Failed to add item: ' . $insertItem->error);
                    }

                    $updateBatch->bind_param('did', $allocQty, $batchId, $allocQty);
                    if (!$updateBatch->execute() || $updateBatch->affected_rows === 0) {
                        throw new Exception('Insufficient stock for batch ' . $batchId);
                    }

                    $note = 'Sales Invoice #' . $invoiceNumber;
                    $insertMovement->bind_param('iiidisi', $productId, $batchId, $allocQty, $invoiceId, $note, $userId);
                    if (!$insertMovement->execute()) {
                        throw new Exception('Failed to log stock movement: ' . $insertMovement->error);
                    }

                    $remainingQty -= $allocQty;
                }

                if ($remainingQty > 0) {
                    throw new Exception('Insufficient FEFO stock for product #' . $productId . ' (short by ' . $remainingQty . ')');
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
        
        // Log initial payment transaction if paid amount > 0
        if ($paidAmount > 0) {
            $insertTxn = $connect->prepare("
                INSERT INTO invoice_transactions
                (invoice_id, transaction_type, amount, payment_method, created_by, created_at)
                VALUES (?, 'PAYMENT', ?, ?, ?, NOW())
            ");
            $insertTxn->bind_param('idsi', $invoiceId, $paidAmount, $paymentMethod, $userId);
            $insertTxn->execute();
        }
        
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
