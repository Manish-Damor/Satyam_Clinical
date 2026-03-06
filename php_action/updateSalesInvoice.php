<?php
/**
 * UPDATE SALES INVOICE
 * Handles draft/final updates with payment consistency.
 */

header('Content-Type: application/json');
require_once 'json_core.php';

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

    if (empty($_POST['client_id'])) {
        throw new Exception('Client is required');
    }

    if (empty($_POST['invoice_date'])) {
        throw new Exception('Invoice date is required');
    }

    $invoiceId = intval($_POST['invoice_id']);
    $clientId = intval($_POST['client_id']);
    $invoiceDate = trim((string)$_POST['invoice_date']);
    $dueDate = !empty($_POST['due_date']) ? trim((string)$_POST['due_date']) : null;
    $deliveryAddress = isset($_POST['delivery_address']) ? trim((string)$_POST['delivery_address']) : null;

    $paymentTypeInput = trim((string)($_POST['payment_type'] ?? 'Cash'));
    $paymentType = (strcasecmp($paymentTypeInput, 'Credit') === 0) ? 'Credit' : 'Cash';
    $paymentMethod = trim((string)($_POST['payment_method'] ?? ''));
    if ($paymentMethod === '' && $paymentType === 'Cash') {
        $paymentMethod = 'Cash';
    }
    $paymentNotes = isset($_POST['payment_notes']) ? trim((string)$_POST['payment_notes']) : null;

    $invoiceStatus = strtoupper(trim((string)($_POST['invoice_status'] ?? 'FINAL')));
    $isDraft = ($invoiceStatus === 'DRAFT');

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

    $connect->begin_transaction();

    try {
        $existingStmt = $connect->prepare(
            "SELECT invoice_number, client_id, payment_type, due_amount, submitted_at
             FROM sales_invoices
             WHERE invoice_id = ?
             FOR UPDATE"
        );
        $existingStmt->bind_param('i', $invoiceId);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        if ($existingResult->num_rows === 0) {
            throw new Exception('Invoice not found');
        }
        $existingInvoice = $existingResult->fetch_assoc();

        $updateInvoice = $connect->prepare(
            "UPDATE sales_invoices
             SET client_id = ?, invoice_date = ?, due_date = ?, delivery_address = ?,
                 subtotal = ?, discount_amount = ?, discount_percent = ?,
                 gst_amount = ?, grand_total = ?, paid_amount = ?, due_amount = ?,
                 payment_type = ?, payment_method = ?, payment_notes = ?,
                 payment_status = ?, updated_by = ?, updated_at = NOW()
             WHERE invoice_id = ?"
        );

        $updateInvoice->bind_param(
            'isssdddddddssssii',
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
            $paymentMethod,
            $paymentNotes,
            $paymentStatus,
            $userId,
            $invoiceId
        );

        if (!$updateInvoice->execute()) {
            throw new Exception('Failed to update invoice: ' . $updateInvoice->error);
        }

        if ($isDraft) {
            $markDraft = $connect->prepare(
                "UPDATE sales_invoices
                 SET submitted_by = NULL, submitted_at = NULL
                 WHERE invoice_id = ?"
            );
            $markDraft->bind_param('i', $invoiceId);
            if (!$markDraft->execute()) {
                throw new Exception('Failed to mark invoice as draft: ' . $markDraft->error);
            }
        } else {
            $markSubmitted = $connect->prepare(
                "UPDATE sales_invoices
                 SET submitted_by = COALESCE(submitted_by, ?),
                     submitted_at = COALESCE(submitted_at, NOW())
                 WHERE invoice_id = ?"
            );
            $markSubmitted->bind_param('ii', $userId, $invoiceId);
            if (!$markSubmitted->execute()) {
                throw new Exception('Failed to mark invoice as submitted: ' . $markSubmitted->error);
            }
        }

        $oldClientId = intval($existingInvoice['client_id']);
        $oldCreditImpact = (!empty($existingInvoice['submitted_at']) && strcasecmp((string)$existingInvoice['payment_type'], 'Credit') === 0)
            ? max(0, floatval($existingInvoice['due_amount']))
            : 0.0;
        $newCreditImpact = (!$isDraft && strcasecmp($paymentType, 'Credit') === 0)
            ? max(0, $dueAmount)
            : 0.0;

        $increaseBalance = $connect->prepare(
            "UPDATE clients SET outstanding_balance = outstanding_balance + ? WHERE client_id = ?"
        );
        $decreaseBalance = $connect->prepare(
            "UPDATE clients SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE client_id = ?"
        );

        if ($oldClientId === $clientId) {
            $delta = $newCreditImpact - $oldCreditImpact;
            if ($delta > 0.0001) {
                $increaseBalance->bind_param('di', $delta, $clientId);
                if (!$increaseBalance->execute()) {
                    throw new Exception('Failed to increase client outstanding: ' . $increaseBalance->error);
                }
            } elseif ($delta < -0.0001) {
                $decreaseAmount = abs($delta);
                $decreaseBalance->bind_param('di', $decreaseAmount, $clientId);
                if (!$decreaseBalance->execute()) {
                    throw new Exception('Failed to decrease client outstanding: ' . $decreaseBalance->error);
                }
            }
        } else {
            if ($oldCreditImpact > 0) {
                $decreaseBalance->bind_param('di', $oldCreditImpact, $oldClientId);
                if (!$decreaseBalance->execute()) {
                    throw new Exception('Failed to decrease previous client outstanding: ' . $decreaseBalance->error);
                }
            }

            if ($newCreditImpact > 0) {
                $increaseBalance->bind_param('di', $newCreditImpact, $clientId);
                if (!$increaseBalance->execute()) {
                    throw new Exception('Failed to increase new client outstanding: ' . $increaseBalance->error);
                }
            }
        }

        $deleteItems = $connect->prepare("DELETE FROM sales_invoice_items WHERE invoice_id = ?");
        $deleteItems->bind_param('i', $invoiceId);
        $deleteItems->execute();

        $productIds = $_POST['product_id'] ?? [];
        $batchIds = $_POST['batch_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $rates = $_POST['rate'] ?? [];
        $ptrs = $_POST['ptr'] ?? [];
        $gstRates = $_POST['gst_rate'] ?? [];
        $lineTotals = $_POST['line_total'] ?? [];
        $batchNumbers = $_POST['batch_number'] ?? [];
        $expiryDates = $_POST['expiry_date'] ?? [];

        if (!$isDraft && empty($productIds)) {
            throw new Exception('At least one item is required to update invoice');
        }

        $shouldPostStock = (!$isDraft && empty($existingInvoice['submitted_at']));
        $stockRows = [];

        if (!empty($productIds)) {
            $insertItem = $connect->prepare(
                "INSERT INTO sales_invoice_items
                (invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate,
                 line_subtotal, gst_rate, gst_amount, line_total, batch_number, expiry_date, added_date)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $fetchBatchMrp = $connect->prepare(
                "SELECT mrp FROM product_batches WHERE batch_id = ? AND product_id = ? LIMIT 1"
            );

            $insertedItemCount = 0;

            for ($i = 0; $i < count($productIds); $i++) {
                $productId = isset($productIds[$i]) ? intval($productIds[$i]) : 0;
                $batchIdRaw = isset($batchIds[$i]) ? intval($batchIds[$i]) : 0;
                $batchId = ($batchIdRaw > 0) ? $batchIdRaw : null;
                $quantity = isset($quantities[$i]) ? floatval($quantities[$i]) : 0;
                $rate = isset($rates[$i]) ? floatval($rates[$i]) : 0;
                $ptr = (isset($ptrs[$i]) && $ptrs[$i] !== '') ? floatval($ptrs[$i]) : null;
                $gstRate = isset($gstRates[$i]) ? floatval($gstRates[$i]) : 0;
                $lineTotalInput = isset($lineTotals[$i]) ? floatval($lineTotals[$i]) : 0;
                $batchNumber = trim((string)($batchNumbers[$i] ?? ''));
                $expiryDate = trim((string)($expiryDates[$i] ?? ''));
                $expiryDate = ($expiryDate === '') ? null : substr($expiryDate, 0, 10);

                if ($productId <= 0 && $quantity <= 0 && $rate <= 0) {
                    continue;
                }

                if ($productId <= 0 || $quantity <= 0 || $rate <= 0) {
                    if ($isDraft) {
                        continue;
                    }
                    throw new Exception('Product, quantity and rate are required for each invoice row');
                }

                if (!$isDraft) {
                    if ($batchId === null || $batchId <= 0) {
                        throw new Exception('Batch selection is required for product #' . $productId);
                    }

                    $fetchBatchMrp->bind_param('ii', $batchId, $productId);
                    $fetchBatchMrp->execute();
                    $batchMrpRow = $fetchBatchMrp->get_result()->fetch_assoc();

                    if (!$batchMrpRow) {
                        throw new Exception('Invalid batch selected for product #' . $productId);
                    }

                    $batchMrp = (float)$batchMrpRow['mrp'];
                    if ($batchMrp > 0 && $rate > ($batchMrp + 0.0001)) {
                        throw new Exception('Rate cannot exceed MRP for product #' . $productId);
                    }

                    $stockRows[] = [
                        'product_id' => $productId,
                        'batch_id' => $batchId,
                        'quantity' => $quantity
                    ];
                }

                $lineSubtotal = $quantity * $rate;
                $lineGstAmount = $lineSubtotal * ($gstRate / 100);
                $lineTotal = ($lineTotalInput > 0) ? $lineTotalInput : ($lineSubtotal + $lineGstAmount);

                $insertItem->bind_param(
                    'iiidddddddss',
                    $invoiceId,
                    $productId,
                    $batchId,
                    $quantity,
                    $rate,
                    $ptr,
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
            }

            if (!$isDraft && $insertedItemCount === 0) {
                throw new Exception('At least one complete item row is required to update invoice');
            }
        }

        if ($shouldPostStock && !empty($stockRows)) {
            $batchCheck = $connect->prepare(
                "SELECT batch_number, available_quantity
                 FROM product_batches
                 WHERE batch_id = ?
                   AND product_id = ?
                   AND status = 'Active'
                   AND expiry_date >= CURDATE()
                 FOR UPDATE"
            );
            $batchDeduct = $connect->prepare(
                "UPDATE product_batches
                 SET available_quantity = available_quantity - ?
                 WHERE batch_id = ? AND available_quantity >= ?"
            );
            $insertMovement = $connect->prepare(
                "INSERT INTO stock_movements
                (product_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, created_by, created_at)
                VALUES (?, ?, 'Sales', ?, 'Invoice', ?, ?, ?, NOW())"
            );

            foreach ($stockRows as $stockRow) {
                $productId = (int)$stockRow['product_id'];
                $batchId = (int)$stockRow['batch_id'];
                $quantity = (float)$stockRow['quantity'];

                $batchCheck->bind_param('ii', $batchId, $productId);
                $batchCheck->execute();
                $batch = $batchCheck->get_result()->fetch_assoc();

                if (!$batch) {
                    throw new Exception('Batch not available for product #' . $productId . ' while finalizing invoice.');
                }

                $availableQty = (float)$batch['available_quantity'];
                if ($quantity > ($availableQty + 0.0001)) {
                    throw new Exception('Insufficient stock for product #' . $productId . ' (batch ' . ($batch['batch_number'] ?: $batchId) . ').');
                }

                $batchDeduct->bind_param('did', $quantity, $batchId, $quantity);
                if (!$batchDeduct->execute() || $batchDeduct->affected_rows === 0) {
                    throw new Exception('Failed to deduct stock for batch #' . $batchId);
                }

                $movementNote = 'Sales Invoice #' . $existingInvoice['invoice_number'] . ' finalization';
                $insertMovement->bind_param('iidisi', $productId, $batchId, $quantity, $invoiceId, $movementNote, $userId);
                if (!$insertMovement->execute()) {
                    throw new Exception('Failed to create stock movement: ' . $insertMovement->error);
                }
            }
        }

        $connect->commit();

        $response['success'] = true;
        $response['message'] = $isDraft
            ? 'Draft invoice updated successfully'
            : 'Invoice updated successfully';
    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
