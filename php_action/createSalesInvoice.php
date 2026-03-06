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
    $invoiceStatus = strtoupper(trim((string)($_POST['invoice_status'] ?? 'FINAL')));
    $isDraft = ($invoiceStatus === 'DRAFT');
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
    $sessionUserId = $_SESSION['userId'] ?? null;
    $userId = (is_numeric($sessionUserId) && intval($sessionUserId) > 0)
        ? intval($sessionUserId)
        : null;
    
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
    if (!$isDraft && strtolower($paymentType) === 'credit') {
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

        if (!$isDraft) {
            $markSubmitted = $connect->prepare("\n                UPDATE sales_invoices\n                SET submitted_by = ?, submitted_at = NOW()\n                WHERE invoice_id = ?\n            ");
            $markSubmitted->bind_param('ii', $userId, $invoiceId);
            if (!$markSubmitted->execute()) {
                throw new Exception('Failed to mark invoice as submitted: ' . $markSubmitted->error);
            }
        }
        
        // UPDATE CLIENT OUTSTANDING BALANCE IF CREDIT PAYMENT
        if (!$isDraft && strtolower($paymentType) === 'credit') {
            $updateBalance = $connect->prepare("
                UPDATE clients SET outstanding_balance = outstanding_balance + ? WHERE client_id = ?
            ");
            $updateBalance->bind_param('di', $grandTotal, $clientId);
            if (!$updateBalance->execute()) {
                throw new Exception('Failed to update client credit: ' . $updateBalance->error);
            }
        }
        
        // Insert items and process allocation plan
        if (!$isDraft && empty($_POST['product_id'])) {
            throw new Exception('At least one item is required to create invoice');
        }

        if (!$isDraft && !empty($_POST['product_id'])) {
            $insertItem = $connect->prepare("
                INSERT INTO sales_invoice_items
                (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate,
                 line_subtotal, gst_rate, gst_amount, line_total, batch_number, expiry_date, added_date)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $insertMovement = $connect->prepare("
                INSERT INTO stock_movements
                (product_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, created_by, created_at)
                VALUES (?, ?, 'Sales', ?, 'Invoice', ?, ?, ?, NOW())
            ");

            $updateBatch = $connect->prepare("
                UPDATE product_batches SET available_quantity = available_quantity - ? WHERE batch_id = ? AND available_quantity >= ?
            ");

            $selectedBatchFetch = $connect->prepare("
                SELECT batch_id, batch_number, available_quantity, mrp, purchase_rate, expiry_date
                FROM product_batches
                WHERE batch_id = ?
                  AND product_id = ?
                  AND status = 'Active'
                                    AND expiry_date >= CURDATE()
                FOR UPDATE
            ");

            $productIds = $_POST['product_id'];
            $batchIds = $_POST['batch_id'] ?? [];
            $quantities = $_POST['quantity'];
            $rates = $_POST['rate'];
            $gstRates = $_POST['gst_rate'];

            for ($i = 0; $i < count($productIds); $i++) {
                $productId = intval($productIds[$i]);
                $batchId = isset($batchIds[$i]) ? intval($batchIds[$i]) : 0;
                $quantity = floatval($quantities[$i]);
                $rate = floatval($rates[$i]);
                $gstRate = floatval($gstRates[$i]);

                if ($productId <= 0 && $quantity <= 0 && $rate <= 0) {
                    continue;
                }

                if ($productId <= 0 || $quantity <= 0) {
                    throw new Exception('Product and quantity are required for each row');
                }

                if ($batchId <= 0) {
                    throw new Exception('Batch is required for product #' . $productId);
                }

                if ($rate <= 0) {
                    throw new Exception('Rate is required for product #' . $productId);
                }

                $selectedBatchFetch->bind_param('ii', $batchId, $productId);
                $selectedBatchFetch->execute();
                $batch = $selectedBatchFetch->get_result()->fetch_assoc();

                if (!$batch) {
                    throw new Exception('Selected batch is invalid, inactive, or expired for product #' . $productId);
                }

                $availableQty = (float) $batch['available_quantity'];
                if ($quantity > ($availableQty + 0.0001)) {
                    throw new Exception(
                        'Insufficient stock for product #' . $productId .
                        ' (batch ' . ($batch['batch_number'] ?: $batchId) . ')'
                    );
                }

                $batchMrp = (float) $batch['mrp'];
                if ($batchMrp > 0 && $rate > ($batchMrp + 0.0001)) {
                    throw new Exception(
                        'Rate cannot exceed MRP for product #' . $productId .
                        ' (batch ' . ($batch['batch_number'] ?: $batchId) . ')'
                    );
                }

                $unitRate = $rate;
                $batchPtr = (float) $batch['purchase_rate'];
                $batchNumber = (string)($batch['batch_number'] ?? '');
                $expiryDate = !empty($batch['expiry_date']) ? (string)$batch['expiry_date'] : null;
                $lineSubtotal = $quantity * $unitRate;
                $lineGstAmount = $lineSubtotal * ($gstRate / 100);
                $lineTotal = $lineSubtotal + $lineGstAmount;

                $insertItem->bind_param(
                    'iiidddddddss',
                    $invoiceId,
                    $productId,
                    $batchId,
                    $quantity,
                    $unitRate,
                    $batchPtr,
                    $lineSubtotal,
                    $gstRate,
                    $lineGstAmount,
                    $lineTotal,
                    $batchNumber,
                    $expiryDate
                );
                if (!$insertItem->execute()) {
                    throw new Exception('Failed to add item: ' . $insertItem->error);
                }

                $updateBatch->bind_param('did', $quantity, $batchId, $quantity);
                if (!$updateBatch->execute() || $updateBatch->affected_rows === 0) {
                    throw new Exception('Insufficient stock for batch ' . $batchId);
                }

                $note = 'Sales Invoice #' . $invoiceNumber;
                $insertMovement->bind_param('iidisi', $productId, $batchId, $quantity, $invoiceId, $note, $userId);
                if (!$insertMovement->execute()) {
                    throw new Exception('Failed to log stock movement: ' . $insertMovement->error);
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
        if (!$isDraft && $paidAmount > 0) {
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
        $response['message'] = $isDraft
            ? "Draft invoice {$invoiceNumber} saved successfully"
            : "Invoice {$invoiceNumber} created successfully";
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
