<?php
/**
 * EDIT SALES INVOICE BACKEND
 * Handles editing of invoice dates, delivery address, item quantities, rates, and GST
 * Does NOT change batch allocations (stock has already been allocated on creation)
 */

header('Content-Type: application/json');
require '../constant/connect.php';

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    if (!$invoiceId) {
        throw new Exception('Invoice ID is required.');
    }

    // Get form data
    $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
    $dueDate = $_POST['due_date'] ?? null;
    $deliveryAddress = $_POST['delivery_address'] ?? null;
    $discountAmount = floatval($_POST['discount_amount'] ?? 0);
    
    // Audit
    $userId = $_SESSION['userId'] ?? null;

    // Start transaction
    $connect->begin_transaction();

    try {
        // Update invoice header (dates, delivery address, discount, totals)
        $updateInvoice = $connect->prepare("
            UPDATE sales_invoices
            SET invoice_date = ?, due_date = ?, delivery_address = ?,
                discount_amount = ?,
                updated_by = ?, updated_at = NOW()
            WHERE invoice_id = ?
        ");

        $updateInvoice->bind_param('sssdii', $invoiceDate, $dueDate, $deliveryAddress, $discountAmount, $userId, $invoiceId);
        if (!$updateInvoice->execute()) {
            throw new Exception('Failed to update invoice header: ' . $updateInvoice->error);
        }

        // Update individual items (quantity, rate, GST, line totals)
        if (!empty($_POST['item_id'])) {
            $updateItem = $connect->prepare("
                UPDATE sales_invoice_items
                SET quantity = ?, unit_rate = ?, gst_rate = ?,
                    line_subtotal = ?, gst_amount = ?, line_total = ?
                WHERE item_id = ?
            ");

            $itemIds = $_POST['item_id'];
            $quantities = $_POST['quantity'];
            $rates = $_POST['rate'];
            $gstRates = $_POST['gst_rate'];
            $lineTotals = $_POST['line_total'];

            for ($i = 0; $i < count($itemIds); $i++) {
                $itemId = intval($itemIds[$i]);
                $qty = floatval($quantities[$i]);
                $rate = floatval($rates[$i]);
                $gstRate = floatval($gstRates[$i]);
                $lineTotal = floatval($lineTotals[$i]);

                // Recalculate line subtotal and GST
                $lineSubtotal = $qty * $rate;
                $gstAmount = $lineSubtotal * ($gstRate / 100);

                $updateItem->bind_param('ddddddi', $qty, $rate, $gstRate, $lineSubtotal, $gstAmount, $lineTotal, $itemId);
                if (!$updateItem->execute()) {
                    throw new Exception('Failed to update item ' . ($i + 1) . ': ' . $updateItem->error);
                }
            }
        }

        // Recalculate and update invoice totals
        $totalsStmt = $connect->prepare("
            SELECT 
                SUM(line_subtotal) as subtotal,
                SUM(gst_amount) as gst_total,
                SUM(line_total) as grand_before_discount
            FROM sales_invoice_items
            WHERE invoice_id = ?
        ");
        $totalsStmt->bind_param('i', $invoiceId);
        $totalsStmt->execute();
        $totals = $totalsStmt->get_result()->fetch_assoc();

        $subtotal = floatval($totals['subtotal'] ?? 0);
        $gstAmount = floatval($totals['gst_total'] ?? 0);
        $grandBeforeDiscount = floatval($totals['grand_before_discount'] ?? 0);
        $grandTotal = $grandBeforeDiscount - $discountAmount;
        $dueAmount = $grandTotal; // Assuming not paid yet during edit

        // Update invoice totals
        $updateTotals = $connect->prepare("
            UPDATE sales_invoices
            SET subtotal = ?, gst_amount = ?, grand_total = ?, due_amount = ?
            WHERE invoice_id = ?
        ");
        $updateTotals->bind_param('ddddi', $subtotal, $gstAmount, $grandTotal, $dueAmount, $invoiceId);
        if (!$updateTotals->execute()) {
            throw new Exception('Failed to update totals: ' . $updateTotals->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Invoice updated successfully.';
        header('Location: ../sales_invoice_list.php?msg=Invoice updated successfully');

    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
