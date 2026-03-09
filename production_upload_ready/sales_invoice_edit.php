<?php 
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');

require './constant/connect.php';

// Check invoice ID
$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$invoiceId) {
    die('Invoice ID is required.');
}

// Fetch invoice header
$invoiceStmt = $connect->prepare("
    SELECT si.*, c.name as client_name, c.contact_phone, c.email, c.billing_address, c.city, c.state, c.postal_code
    FROM sales_invoices si
    LEFT JOIN clients c ON si.client_id = c.client_id
    WHERE si.invoice_id = ?
");
$invoiceStmt->bind_param('i', $invoiceId);
$invoiceStmt->execute();
$invoice = $invoiceStmt->get_result()->fetch_assoc();

if (!$invoice) {
    die('Invoice not found.');
}

// Fetch invoice items with product and batch information
$itemsStmt = $connect->prepare("
    SELECT sii.*, p.product_name, p.hsn_code, p.gst_rate as product_gst
    FROM sales_invoice_items sii
    LEFT JOIN product p ON sii.product_id = p.product_id
    WHERE sii.invoice_id = ?
    ORDER BY sii.item_id ASC
");
$itemsStmt->bind_param('i', $invoiceId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}

$pageTitle = 'Edit Sales Invoice #' . htmlspecialchars($invoice['invoice_number']);
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-primary"><?php echo $pageTitle; ?></h3>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <form id="editInvoiceForm" method="POST" action="php_action/updateSalesInvoiceEdit.php">
            <input type="hidden" name="invoice_id" value="<?php echo $invoiceId; ?>">

            <!-- Invoice Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label><strong>Invoice Number</strong></label>
                            <p><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <label><strong>Invoice Date</strong></label>
                            <input type="date" class="form-control" name="invoice_date" value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label><strong>Due Date</strong></label>
                            <input type="date" class="form-control" name="due_date" value="<?php echo htmlspecialchars($invoice['due_date']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label><strong>Status</strong></label>
                            <p><span class="badge badge-info"><?php echo htmlspecialchars($invoice['invoice_status']); ?></span></p>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label><strong>Billing Address</strong></label>
                            <p><?php echo nl2br(htmlspecialchars($invoice['billing_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label><strong>Delivery Address</strong></label>
                            <textarea class="form-control" name="delivery_address" rows="4"><?php echo htmlspecialchars($invoice['delivery_address']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Invoice Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 20%;">Medicine</th>
                                    <th style="width: 10%;">HSN</th>
                                    <th style="width: 10%;">Batch</th>
                                    <th style="width: 8%;">Expiry</th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 8%;">MRP</th>
                                    <th style="width: 8%;">Rate</th>
                                    <th style="width: 8%;">GST %</th>
                                    <th style="width: 12%;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $idx => $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <input type="hidden" name="item_id[]" value="<?php echo $item['item_id']; ?>">
                                        <input type="hidden" name="product_id[]" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="batch_id[]" value="<?php echo $item['batch_id']; ?>">
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($item['hsn_code']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($item['batch_number']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($item['expiry_date']); ?></small>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm quantity-input text-center" 
                                               name="quantity[]" value="<?php echo $item['quantity']; ?>" 
                                               data-row="<?php echo $idx; ?>" min="0.01" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-center mrp-display" 
                                               value="<?php echo number_format($item['unit_rate'], 2); ?>" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm rate-input text-center" 
                                               name="rate[]" value="<?php echo $item['unit_rate']; ?>" 
                                               data-row="<?php echo $idx; ?>" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm gst-input text-center" 
                                               name="gst_rate[]" value="<?php echo $item['gst_rate']; ?>" 
                                               data-row="<?php echo $idx; ?>" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm line-total-display text-right" 
                                               value="<?php echo number_format($item['line_total'], 2); ?>" readonly>
                                        <input type="hidden" class="line-total-value" name="line_total[]" value="<?php echo $item['line_total']; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Financial Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right">
                                        <input type="text" class="form-control text-right" id="subtotal" value="<?php echo number_format($invoice['subtotal'], 2); ?>" readonly>
                                        <input type="hidden" name="subtotal" id="subtotalValue" value="<?php echo $invoice['subtotal']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>GST Amount:</strong></td>
                                    <td class="text-right">
                                        <input type="text" class="form-control text-right" id="gstAmount" value="<?php echo number_format($invoice['gst_amount'], 2); ?>" readonly>
                                        <input type="hidden" name="gst_amount" id="gstAmountValue" value="<?php echo $invoice['gst_amount']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Discount Amount:</strong></td>
                                    <td class="text-right">
                                        <input type="number" class="form-control text-right" id="discountAmount" name="discount_amount" 
                                               value="<?php echo $invoice['discount_amount']; ?>" step="0.01" min="0">
                                    </td>
                                </tr>
                                <tr style="border-top: 2px solid #333;">
                                    <td><strong>Grand Total:</strong></td>
                                    <td class="text-right">
                                        <input type="text" class="form-control text-right font-weight-bold" id="grandTotal" 
                                               value="<?php echo number_format($invoice['grand_total'], 2); ?>" readonly style="font-size: 16px;">
                                        <input type="hidden" name="grand_total" id="grandTotalValue" value="<?php echo $invoice['grand_total']; ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                    <a href="sales_invoice_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for calculations -->
<script src="assets/js/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Recalculate on any change
    $(document).on('change input', '.quantity-input, .rate-input, .gst-input', function() {
        calculateAllTotals();
    });

    function calculateAllTotals() {
        let subtotal = 0, totalGst = 0;

        // Calculate each line
        $('#editInvoiceForm table tbody tr').each(function() {
            const qty = parseFloat($(this).find('.quantity-input').val()) || 0;
            const rate = parseFloat($(this).find('.rate-input').val()) || 0;
            const gstRate = parseFloat($(this).find('.gst-input').val()) || 0;

            const lineAmount = qty * rate;
            const lineGst = lineAmount * (gstRate / 100);
            const lineTotal = lineAmount + lineGst;

            $(this).find('.line-total-display').val(lineTotal.toFixed(2));
            $(this).find('.line-total-value').val(lineTotal.toFixed(2));

            subtotal += lineAmount;
            totalGst += lineGst;
        });

        const discount = parseFloat($('#discountAmount').val()) || 0;
        const grandTotal = subtotal + totalGst - discount;

        // Update summary
        $('#subtotal').val(subtotal.toFixed(2));
        $('#subtotalValue').val(subtotal.toFixed(2));
        $('#gstAmount').val(totalGst.toFixed(2));
        $('#gstAmountValue').val(totalGst.toFixed(2));
        $('#grandTotal').val(grandTotal.toFixed(2));
        $('#grandTotalValue').val(grandTotal.toFixed(2));
    }

    // Recalculate on discount change
    $('#discountAmount').on('change input', function() {
        calculateAllTotals();
    });

    // Initial calculation
    calculateAllTotals();
});
</script>

<?php include('./constant/layout/footer.php'); ?>
