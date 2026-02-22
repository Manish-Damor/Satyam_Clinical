<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
require_once __DIR__ . '/php_action/purchase_invoice_action.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('<div class="alert alert-danger">Invalid invoice ID</div>');
}

$invoiceId = intval($_GET['id']);
$invoice = PurchaseInvoiceAction::getInvoice($invoiceId);

if (!$invoice) {
    die('<div class="alert alert-danger">Invoice not found</div>');
}
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row page-titles mb-3">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-file-invoice"></i> Invoice Details</h3>
                <small class="text-muted">Invoice #<?=htmlspecialchars($invoice['invoice_no'])?></small>
            </div>
            <div class="col-md-4 align-self-center text-end">
                <a href="invoice_list.php" class="btn btn-secondary btn-sm">
                    <i class="fa fa-chevron-left"></i> Back to List
                </a>
                <button class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>

        <!-- Invoice Header Section -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">Invoice Information</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Invoice #:</strong></td>
                                <td><?=htmlspecialchars($invoice['invoice_no'])?></td>
                            </tr>
                            <tr>
                                <td><strong>Invoice Date:</strong></td>
                                <td><?=date('d M Y', strtotime($invoice['invoice_date']))?></td>
                            </tr>
                            <tr>
                                <td><strong>Due Date:</strong></td>
                                <td><?=!empty($invoice['due_date']) ? date('d M Y', strtotime($invoice['due_date'])) : 'N/A'?></td>
                            </tr>
                            <tr>
                                <td><strong>PO Reference:</strong></td>
                                <td><?=htmlspecialchars($invoice['po_reference'] ?? '-')?></td>
                            </tr>
                            <tr>
                                <td><strong>GRN Reference:</strong></td>
                                <td><?=htmlspecialchars($invoice['grn_reference'] ?? '-')?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php 
                                        $statusClass = match($invoice['status']) {
                                            'Draft' => 'secondary',
                                            'Received' => 'info',
                                            'Matched' => 'warning',
                                            'Approved' => 'success',
                                            'Deleted' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                    <span class="badge bg-<?=$statusClass?>"><?=$invoice['status']?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">Supplier Information</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Supplier Name:</strong></td>
                                <td><?=htmlspecialchars($invoice['supplier_name'] ?? 'N/A')?></td>
                            </tr>
                            <tr>
                                <td><strong>Supplier State:</strong></td>
                                <td><?=htmlspecialchars($invoice['supplier_location_state'] ?? '-')?></td>
                            </tr>
                            <tr>
                                <td><strong>Supplier GSTIN:</strong></td>
                                <td><?=htmlspecialchars($invoice['supplier_gstin'] ?? '-')?></td>
                            </tr>
                            <tr>
                                <td><strong>GST Type:</strong></td>
                                <td>
                                    <span class="badge <?=$invoice['gst_determination_type']=='intrastate'?'bg-info':'bg-warning'?>">
                                        <?=$invoice['gst_determination_type']=='intrastate'?'Intra-State':'Inter-State'?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payment Terms:</strong></td>
                                <td><?=htmlspecialchars($invoice['payment_terms'] ?? '-')?> days</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Mode:</strong></td>
                                <td><?=htmlspecialchars($invoice['payment_mode'] ?? '-')?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Invoice Items (<?=count($invoice['items'])?> items)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width:20%">Product Name</th>
                                <th style="width:8%">HSN</th>
                                <th style="width:8%">Batch</th>
                                <th style="width:8%">Qty</th>
                                <th style="width:10%">Unit Cost</th>
                                <th style="width:10%">MRP</th>
                                <th style="width:8%">Margin%</th>
                                <th style="width:10%">Tax</th>
                                <th style="width:12%">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice['items'] as $item): ?>
                                <tr>
                                    <td><strong><?=htmlspecialchars($item['product_name'])?></strong></td>
                                    <td><?=htmlspecialchars($item['hsn_code'] ?? '-')?></td>
                                    <td><?=htmlspecialchars($item['batch_no'])?></td>
                                    <td class="text-end"><?=number_format($item['qty'], 3)?></td>
                                    <td class="text-end">₹ <?=number_format($item['unit_cost'], 2)?></td>
                                    <td class="text-end">₹ <?=number_format($item['mrp'], 2)?></td>
                                    <td class="text-end"><?=number_format($item['margin_percent'] ?? 0, 2)?>%</td>
                                    <td class="text-end">
                                        <?php
                                            $tax = $item['tax_amount'] ?? 0;
                                            $rate = $item['tax_rate'] ?? 0;
                                            echo "₹ " . number_format($tax, 2) . " (" . $rate . "%)";
                                        ?>
                                    </td>
                                    <td class="text-end fw-bold">₹ <?=number_format($item['line_total'], 2)?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">Notes & Remarks</div>
                    <div class="card-body">
                        <p><?=nl2br(htmlspecialchars($invoice['notes'] ?? 'No notes'))?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-header bg-success text-white">Invoice Summary</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-end">₹ <?=number_format($invoice['subtotal'] ?? 0, 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Discount:</strong></td>
                                <td class="text-end">-₹ <?=number_format($invoice['total_discount'] ?? 0, 2)?></td>
                            </tr>
                            <?php if ($invoice['gst_determination_type'] === 'intrastate'): ?>
                                <tr>
                                    <td><strong>CGST:</strong></td>
                                    <td class="text-end">₹ <?=number_format($invoice['total_cgst'] ?? 0, 2)?></td>
                                </tr>
                                <tr>
                                    <td><strong>SGST:</strong></td>
                                    <td class="text-end">₹ <?=number_format($invoice['total_sgst'] ?? 0, 2)?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><strong>IGST:</strong></td>
                                    <td class="text-end">₹ <?=number_format($invoice['total_igst'] ?? 0, 2)?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Freight:</strong></td>
                                <td class="text-end">₹ <?=number_format($invoice['freight'] ?? 0, 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>Round Off:</strong></td>
                                <td class="text-end">₹ <?=number_format($invoice['round_off'] ?? 0, 2)?></td>
                            </tr>
                            <tr class="fw-bold border-top border-bottom">
                                <td>Grand Total:</td>
                                <td class="text-end text-success">₹ <?=number_format($invoice['grand_total'], 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>Paid Amount:</strong></td>
                                <td class="text-end">₹ <?=number_format($invoice['paid_amount'], 2)?></td>
                            </tr>
                            <tr class="fw-bold <?=$invoice['outstanding_amount']>0?'text-warning':'text-success'?>">
                                <td>Outstanding:</td>
                                <td class="text-end">₹ <?=number_format($invoice['outstanding_amount'], 2)?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-md-12">
                <?php if ($invoice['status'] !== 'Approved'): ?>
                    <button class="btn btn-success btn-approve" data-id="<?=$invoiceId?>">
                        <i class="fa fa-check"></i> Approve Invoice
                    </button>
                <?php endif; ?>
                
                <?php if ($invoice['status'] === 'Draft'): ?>
                    <a href="invoice_edit.php?id=<?=$invoiceId?>" class="btn btn-warning">
                        <i class="fa fa-edit"></i> Edit Invoice
                    </a>
                <?php endif; ?>
                
                <button class="btn btn-info" onclick="window.print()">
                    <i class="fa fa-print"></i> Print Invoice
                </button>
                
                <?php if ($invoice['status'] !== 'Deleted'): ?>
                    <button class="btn btn-danger btn-delete" data-id="<?=$invoiceId?>">
                        <i class="fa fa-trash"></i> Delete Invoice
                    </button>
                <?php endif; ?>
                
                <a href="invoice_list.php" class="btn btn-secondary">
                    <i class="fa fa-chevron-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    // Approve button
    $(document).on('click', '.btn-approve', function(){
        const invoiceId = $(this).data('id');
        
        if (!confirm('Mark this invoice as Approved?')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'approve',
                invoice_id: invoiceId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ Invoice approved successfully');
                    location.reload();
                } else {
                    alert('✗ Error: ' + resp.error);
                }
            },
            error: function(){
                alert('Server error occurred');
            }
        });
    });

    // Delete button
    $(document).on('click', '.btn-delete', function(){
        const invoiceId = $(this).data('id');
        
        if (!confirm('Are you sure you want to delete this invoice?')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'delete',
                invoice_id: invoiceId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ Invoice deleted successfully');
                    window.location.href = 'po_list.php';
                } else {
                    alert('✗ Error: ' + resp.error);
                }
            },
            error: function(){
                alert('Server error occurred');
            }
        });
    });
</script>

</body>
</html>
