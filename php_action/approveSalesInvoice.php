<?php
/**
 * APPROVE SALES INVOICE
 * Finalizes a draft invoice from list action.
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

    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    if ($invoiceId <= 0) {
        throw new Exception('Invoice ID is required');
    }

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

    $connect->begin_transaction();

    try {
        $invoiceStmt = $connect->prepare("\n            SELECT\n                invoice_id, invoice_number, client_id, payment_type, payment_method,\n                grand_total, paid_amount, due_amount, payment_status, submitted_at, deleted_at\n            FROM sales_invoices\n            WHERE invoice_id = ?\n            FOR UPDATE\n        ");
        $invoiceStmt->bind_param('i', $invoiceId);
        $invoiceStmt->execute();
        $invoiceResult = $invoiceStmt->get_result();

        if ($invoiceResult->num_rows === 0) {
            throw new Exception('Invoice not found');
        }

        $invoice = $invoiceResult->fetch_assoc();

        if (!empty($invoice['deleted_at'])) {
            throw new Exception('Cannot approve a deleted invoice');
        }

        if (!empty($invoice['submitted_at'])) {
            $connect->commit();
            $response['success'] = true;
            $response['message'] = 'Invoice is already approved';
            echo json_encode($response);
            exit;
        }

        $itemCountStmt = $connect->prepare("SELECT COUNT(*) AS item_count FROM sales_invoice_items WHERE invoice_id = ?");
        $itemCountStmt->bind_param('i', $invoiceId);
        $itemCountStmt->execute();
        $itemCountRow = $itemCountStmt->get_result()->fetch_assoc();
        $itemCount = isset($itemCountRow['item_count']) ? intval($itemCountRow['item_count']) : 0;

        if ($itemCount <= 0) {
            throw new Exception('Cannot approve invoice without items. Please edit draft first.');
        }

        // Draft approval posts stock impact now (stock is not moved when draft is saved).
        $itemStmt = $connect->prepare("\n            SELECT item_id, product_id, batch_id, quantity, unit_rate\n            FROM sales_invoice_items\n            WHERE invoice_id = ?\n            FOR UPDATE\n        ");
        $itemStmt->bind_param('i', $invoiceId);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        $fetchBatch = $connect->prepare("\n            SELECT batch_number, expiry_date, available_quantity, mrp\n            FROM product_batches\n            WHERE batch_id = ?\n              AND product_id = ?\n              AND status = 'Active'\n              AND expiry_date >= CURDATE()\n            FOR UPDATE\n        ");

        $updateBatch = $connect->prepare("\n            UPDATE product_batches\n            SET available_quantity = available_quantity - ?\n            WHERE batch_id = ? AND available_quantity >= ?\n        ");

        $insertMovement = $connect->prepare("\n            INSERT INTO stock_movements\n            (product_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, created_by, created_at)\n            VALUES (?, ?, 'Sales', ?, 'Invoice', ?, ?, ?, NOW())\n        ");

        while ($item = $itemResult->fetch_assoc()) {
            $productId = intval($item['product_id']);
            $batchId = intval($item['batch_id'] ?? 0);
            $quantity = floatval($item['quantity']);
            $unitRate = floatval($item['unit_rate']);

            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception('Draft contains invalid item rows. Please edit and save again.');
            }

            if ($batchId <= 0) {
                throw new Exception('Draft item for product #' . $productId . ' is missing batch. Please edit draft before approval.');
            }

            $fetchBatch->bind_param('ii', $batchId, $productId);
            $fetchBatch->execute();
            $batch = $fetchBatch->get_result()->fetch_assoc();

            if (!$batch) {
                throw new Exception('Batch not available/active for product #' . $productId . ' during approval.');
            }

            $availableQty = floatval($batch['available_quantity']);
            if ($quantity > ($availableQty + 0.0001)) {
                throw new Exception('Insufficient stock for product #' . $productId . ' (batch ' . ($batch['batch_number'] ?: $batchId) . ').');
            }

            $batchMrp = floatval($batch['mrp']);
            if ($batchMrp > 0 && $unitRate > ($batchMrp + 0.0001)) {
                throw new Exception('Rate exceeds MRP for product #' . $productId . ' during approval.');
            }

            $updateBatch->bind_param('did', $quantity, $batchId, $quantity);
            if (!$updateBatch->execute() || $updateBatch->affected_rows === 0) {
                throw new Exception('Failed stock deduction for batch #' . $batchId);
            }

            $movementNote = 'Sales Invoice #' . $invoice['invoice_number'] . ' approval';
            $insertMovement->bind_param('iidisi', $productId, $batchId, $quantity, $invoiceId, $movementNote, $userId);
            if (!$insertMovement->execute()) {
                throw new Exception('Failed stock movement entry during approval: ' . $insertMovement->error);
            }
        }

        $paymentType = (strcasecmp((string)$invoice['payment_type'], 'Credit') === 0) ? 'Credit' : 'Cash';
        $paymentMethod = trim((string)($invoice['payment_method'] ?? ''));
        if ($paymentMethod === '' && $paymentType === 'Cash') {
            $paymentMethod = 'Cash';
        }

        $grandTotal = floatval($invoice['grand_total'] ?? 0);
        $paidAmount = floatval($invoice['paid_amount'] ?? 0);

        if ($paymentType === 'Cash' && $paidAmount <= 0 && $grandTotal > 0) {
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

        $updateInvoice = $connect->prepare("\n            UPDATE sales_invoices\n            SET paid_amount = ?, due_amount = ?, payment_status = ?,\n                submitted_by = ?, submitted_at = NOW(),\n                updated_by = ?, updated_at = NOW()\n            WHERE invoice_id = ?\n        ");
        $updateInvoice->bind_param('ddsiii', $paidAmount, $dueAmount, $paymentStatus, $userId, $userId, $invoiceId);

        if (!$updateInvoice->execute()) {
            throw new Exception('Failed to approve invoice: ' . $updateInvoice->error);
        }

        if ($paymentType === 'Credit' && $dueAmount > 0) {
            $updateBalance = $connect->prepare("\n                UPDATE clients\n                SET outstanding_balance = outstanding_balance + ?\n                WHERE client_id = ?\n            ");
            $clientId = intval($invoice['client_id']);
            $updateBalance->bind_param('di', $dueAmount, $clientId);
            if (!$updateBalance->execute()) {
                throw new Exception('Failed to update client outstanding: ' . $updateBalance->error);
            }
        }

        if ($paidAmount > 0) {
            $txnCheck = $connect->prepare("\n                SELECT transaction_id\n                FROM invoice_transactions\n                WHERE invoice_id = ? AND transaction_type = 'PAYMENT'\n                LIMIT 1\n            ");
            $txnCheck->bind_param('i', $invoiceId);
            $txnCheck->execute();
            $txnExists = $txnCheck->get_result()->num_rows > 0;

            if (!$txnExists) {
                $txnNotes = 'Auto payment entry on draft approval';
                $insertTxn = $connect->prepare("\n                    INSERT INTO invoice_transactions\n                    (invoice_id, transaction_type, amount, payment_method, notes, created_by, created_at)\n                    VALUES (?, 'PAYMENT', ?, ?, ?, ?, NOW())\n                ");
                $insertTxn->bind_param('idssi', $invoiceId, $paidAmount, $paymentMethod, $txnNotes, $userId);
                if (!$insertTxn->execute()) {
                    throw new Exception('Failed to create payment transaction: ' . $insertTxn->error);
                }
            }
        }

        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Invoice ' . $invoice['invoice_number'] . ' approved successfully';
    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
