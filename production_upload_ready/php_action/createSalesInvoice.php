<?php
/**
 * CREATE SALES INVOICE
 * Handles new invoice creation with draft/final workflows.
 */

header('Content-Type: application/json');
require_once 'json_core.php';

$response = [
    'success' => false,
    'message' => '',
    'invoice_id' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['client_id'])) {
        throw new Exception('Client is required');
    }

    if (empty($_POST['invoice_number'])) {
        throw new Exception('Invoice number is required');
    }

    if (empty($_POST['invoice_date'])) {
        throw new Exception('Invoice date is required');
    }

    $clientId = intval($_POST['client_id']);
    $invoiceNumber = trim((string)$_POST['invoice_number']);
    $invoiceDate = trim((string)$_POST['invoice_date']);
    $dueDate = !empty($_POST['due_date']) ? trim((string)$_POST['due_date']) : null;
    $deliveryAddress = isset($_POST['delivery_address']) ? trim((string)$_POST['delivery_address']) : null;
    $invoiceStatus = strtoupper(trim((string)($_POST['invoice_status'] ?? 'FINAL')));
    $isDraft = ($invoiceStatus === 'DRAFT');

    $paymentTypeInput = trim((string)($_POST['payment_type'] ?? 'Cash'));
    $paymentType = (strcasecmp($paymentTypeInput, 'Credit') === 0) ? 'Credit' : 'Cash';
    $paymentMethod = trim((string)($_POST['payment_method'] ?? ''));
    if ($paymentMethod === '' && $paymentType === 'Cash') {
        $paymentMethod = 'Cash';
    }
    $paymentNotes = isset($_POST['payment_notes']) ? trim((string)$_POST['payment_notes']) : null;

    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $discountAmount = floatval($_POST['discount_amount'] ?? 0);
    $discountPercent = floatval($_POST['discount_percent'] ?? 0);
    $gstAmount = floatval($_POST['gst_amount'] ?? 0);
    $grandTotal = floatval($_POST['grand_total'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $dueAmount = floatval($_POST['due_amount'] ?? 0);

    if (!$isDraft && $paymentType === 'Cash' && $paidAmount <= 0 && $grandTotal > 0) {
        $paidAmount = $grandTotal;
    }

    if ($paidAmount < 0) {
        $paidAmount = 0;
    }

    if ($paidAmount > $grandTotal && $grandTotal > 0) {
        $paidAmount = $grandTotal;
    }

    $dueAmount = max(0, $grandTotal - $paidAmount);

    if ($paidAmount >= $grandTotal && $grandTotal > 0) {
        $paymentStatus = 'PAID';
    } elseif ($paidAmount > 0) {
        $paymentStatus = 'PARTIAL';
    } else {
        $paymentStatus = 'UNPAID';
    }

    $sessionUserId = $_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null);
    $userId = (is_numeric($sessionUserId) && intval($sessionUserId) > 0)
        ? intval($sessionUserId)
        : null;

    $clientFetch = $connect->prepare(
        "SELECT client_id, state, billing_address, shipping_address, gstin, pan, drug_licence_no
         FROM clients WHERE client_id = ?"
    );
    $clientFetch->bind_param('i', $clientId);
    $clientFetch->execute();
    $clientResult = $clientFetch->get_result();

    if ($clientResult->num_rows === 0) {
        throw new Exception('Invalid client selected');
    }

    $clientData = $clientResult->fetch_assoc();
    $clientState = $clientData['state'] ?? 'Gujarat';
    $billingAddr = $clientData['billing_address'] ?? $deliveryAddress;
    $shippingAddr = $clientData['shipping_address'] ?? $deliveryAddress;

    $isIntrastate = (strtolower((string)$clientState) === 'gujarat');
    $gstPercentage = floatval($_POST['gst_rate'] ?? 18);
    $cgstPercent = $isIntrastate ? ($gstPercentage / 2) : 0;
    $sgstPercent = $isIntrastate ? ($gstPercentage / 2) : 0;
    $igstPercent = !$isIntrastate ? $gstPercentage : 0;

    $cgstAmount = $isIntrastate ? round($gstAmount / 2, 2) : 0;
    $sgstAmount = $isIntrastate ? round($gstAmount / 2, 2) : 0;
    $igstAmount = !$isIntrastate ? $gstAmount : 0;

    $dupCheck = $connect->prepare("SELECT invoice_id FROM sales_invoices WHERE invoice_number = ?");
    $dupCheck->bind_param('s', $invoiceNumber);
    $dupCheck->execute();
    if ($dupCheck->get_result()->num_rows > 0) {
        throw new Exception('Invoice number already exists');
    }

    $connect->begin_transaction();

    try {
        $insertInvoice = $connect->prepare(
            "INSERT INTO sales_invoices
            (invoice_number, client_id, invoice_date, due_date, delivery_address, billing_address, shipping_address,
             subtotal, discount_amount, discount_percent, gst_amount, grand_total,
             cgst_percent, sgst_percent, igst_percent, cgst_amount, sgst_amount, igst_amount,
             client_gstin, client_pan, client_dl_no,
             paid_amount, due_amount, payment_type, payment_method, payment_notes, payment_status, created_by, created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $clientGstin = $clientData['gstin'] ?? '';
        $clientPan = $clientData['pan'] ?? '';
        $clientDL = $clientData['drug_licence_no'] ?? '';

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
            $markSubmitted = $connect->prepare(
                "UPDATE sales_invoices
                 SET submitted_by = ?, submitted_at = NOW()
                 WHERE invoice_id = ?"
            );
            $markSubmitted->bind_param('ii', $userId, $invoiceId);
            if (!$markSubmitted->execute()) {
                throw new Exception('Failed to mark invoice as submitted: ' . $markSubmitted->error);
            }
        }

        if (!$isDraft && $paymentType === 'Credit' && $dueAmount > 0) {
            $updateBalance = $connect->prepare(
                "UPDATE clients SET outstanding_balance = outstanding_balance + ? WHERE client_id = ?"
            );
            $updateBalance->bind_param('di', $dueAmount, $clientId);
            if (!$updateBalance->execute()) {
                throw new Exception('Failed to update client credit: ' . $updateBalance->error);
            }
        }

        $productIds = $_POST['product_id'] ?? [];
        $batchIds = $_POST['batch_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $gstRates = $_POST['gst_rate'] ?? [];
        $ptrs = $_POST['ptr'] ?? [];
        $lineTotals = $_POST['line_total'] ?? [];
        $batchNumbers = $_POST['batch_number'] ?? [];
        $expiryDates = $_POST['expiry_date'] ?? [];

        if (!$isDraft && empty($productIds)) {
            throw new Exception('At least one item is required to create invoice');
        }

        if (!empty($productIds)) {
            $insertItem = $connect->prepare(
                "INSERT INTO sales_invoice_items
                (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate,
                 line_subtotal, gst_rate, gst_amount, line_total, batch_number, expiry_date, added_date)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $insertMovement = null;
            $updateBatch = null;
            $selectedBatchFetch = null;

            if (!$isDraft) {
                $insertMovement = $connect->prepare(
                    "INSERT INTO stock_movements
                    (product_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, created_by, created_at)
                    VALUES (?, ?, 'Sales', ?, 'Invoice', ?, ?, ?, NOW())"
                );

                $updateBatch = $connect->prepare(
                    "UPDATE product_batches
                     SET available_quantity = available_quantity - ?
                     WHERE batch_id = ? AND available_quantity >= ?"
                );

                $selectedBatchFetch = $connect->prepare(
                    "SELECT batch_id, batch_number, available_quantity, mrp, purchase_rate, expiry_date
                     FROM product_batches
                     WHERE batch_id = ?
                       AND product_id = ?
                       AND status = 'Active'
                       AND expiry_date >= CURDATE()
                     FOR UPDATE"
                );
            }

            $insertedItemCount = 0;

            for ($i = 0; $i < count($productIds); $i++) {
                $productId = isset($productIds[$i]) ? intval($productIds[$i]) : 0;
                $quantity = isset($quantities[$i]) ? floatval($quantities[$i]) : 0;
                $rate = isset($rates[$i]) ? floatval($rates[$i]) : 0;
                $gstRate = isset($gstRates[$i]) ? floatval($gstRates[$i]) : 0;
                $batchIdRaw = isset($batchIds[$i]) ? intval($batchIds[$i]) : 0;
                $batchId = ($batchIdRaw > 0) ? $batchIdRaw : null;
                $batchNumber = trim((string)($batchNumbers[$i] ?? ''));
                $expiryDate = trim((string)($expiryDates[$i] ?? ''));
                $expiryDate = ($expiryDate === '') ? null : substr($expiryDate, 0, 10);
                $batchPtr = (isset($ptrs[$i]) && $ptrs[$i] !== '') ? floatval($ptrs[$i]) : null;

                if ($productId <= 0 && $quantity <= 0 && $rate <= 0) {
                    continue;
                }

                if ($productId <= 0 || $quantity <= 0 || $rate <= 0) {
                    if ($isDraft) {
                        continue;
                    }
                    throw new Exception('Product, quantity and rate are required for each row');
                }

                if (!$isDraft) {
                    if ($batchId === null || $batchId <= 0) {
                        throw new Exception('Batch is required for product #' . $productId);
                    }

                    $selectedBatchFetch->bind_param('ii', $batchId, $productId);
                    $selectedBatchFetch->execute();
                    $batch = $selectedBatchFetch->get_result()->fetch_assoc();

                    if (!$batch) {
                        throw new Exception('Selected batch is invalid, inactive, or expired for product #' . $productId);
                    }

                    $availableQty = (float)$batch['available_quantity'];
                    if ($quantity > ($availableQty + 0.0001)) {
                        throw new Exception(
                            'Insufficient stock for product #' . $productId .
                            ' (batch ' . ($batch['batch_number'] ?: $batchId) . ')'
                        );
                    }

                    $batchMrp = (float)$batch['mrp'];
                    if ($batchMrp > 0 && $rate > ($batchMrp + 0.0001)) {
                        throw new Exception(
                            'Rate cannot exceed MRP for product #' . $productId .
                            ' (batch ' . ($batch['batch_number'] ?: $batchId) . ')'
                        );
                    }

                    $batchPtr = (float)$batch['purchase_rate'];
                    $batchNumber = (string)($batch['batch_number'] ?? $batchNumber);
                    $expiryDate = !empty($batch['expiry_date']) ? (string)$batch['expiry_date'] : $expiryDate;
                }

                $lineSubtotal = $quantity * $rate;
                $lineGstAmount = $lineSubtotal * ($gstRate / 100);
                $lineTotalInput = isset($lineTotals[$i]) ? floatval($lineTotals[$i]) : 0;
                $lineTotal = ($lineTotalInput > 0) ? $lineTotalInput : ($lineSubtotal + $lineGstAmount);

                $insertItem->bind_param(
                    'iiidddddddss',
                    $invoiceId,
                    $productId,
                    $batchId,
                    $quantity,
                    $rate,
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

                $insertedItemCount++;

                if (!$isDraft) {
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

            if (!$isDraft && $insertedItemCount === 0) {
                throw new Exception('At least one complete item row is required to create invoice');
            }
        }

        $sequenceYear = date('Y');
        $updateSeq = $connect->prepare(
            "UPDATE invoice_sequence SET next_number = next_number + 1 WHERE year = ?"
        );
        $updateSeq->bind_param('i', $sequenceYear);
        $updateSeq->execute();

        if (!$isDraft && $paidAmount > 0) {
            $insertTxn = $connect->prepare(
                "INSERT INTO invoice_transactions
                (invoice_id, transaction_type, amount, payment_method, created_by, created_at)
                VALUES (?, 'PAYMENT', ?, ?, ?, NOW())"
            );
            $insertTxn->bind_param('idsi', $invoiceId, $paidAmount, $paymentMethod, $userId);
            $insertTxn->execute();
        }

        $connect->commit();

        $response['success'] = true;
        $response['message'] = $isDraft
            ? 'Draft invoice ' . $invoiceNumber . ' saved successfully'
            : 'Invoice ' . $invoiceNumber . ' created successfully';
        $response['invoice_id'] = $invoiceId;
    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
