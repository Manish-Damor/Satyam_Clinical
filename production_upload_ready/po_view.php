<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('<div class="alert alert-danger">Invalid Purchase Order ID</div>');
}

$poId = intval($_GET['id']);

// Fetch PO from purchase_orders table
$poRes = $connect->query("SELECT * FROM purchase_orders WHERE po_id = $poId");
if (!$poRes || $poRes->num_rows === 0) {
    die('<div class="alert alert-danger">Purchase Order not found</div>');
}

$po = $poRes->fetch_assoc();

// Fetch PO items
$itemsRes = $connect->query("SELECT * FROM po_items WHERE po_id = $poId");
$items = [];
while ($item = $itemsRes->fetch_assoc()) {
    $items[] = $item;
}

// Fetch supplier info
$suppRes = $connect->query("SELECT * FROM suppliers WHERE supplier_id = {$po['supplier_id']} LIMIT 1");
$supplier = $suppRes ? $suppRes->fetch_assoc() : null;
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row page-titles mb-3">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-file"></i> Purchase Order Details</h3>
                <small class="text-muted">PO #<?=htmlspecialchars($po['po_number'])?></small>
            </div>
            <div class="col-md-4 align-self-center text-end">
                <a href="po_list.php" class="btn btn-secondary btn-sm">
                    <i class="fa fa-chevron-left"></i> Back to List
                </a>
                <a href="print_po.php?id=<?=$poId?>" class="btn btn-primary btn-sm" target="_blank">
                    <i class="fa fa-print"></i> Print PO
                </a>
            </div>
        </div>

        <!-- PO Header Section -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">PO Information</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>PO Number:</strong></td>
                                <td><?=htmlspecialchars($po['po_number'])?></td>
                            </tr>
                            <tr>
                                <td><strong>PO Date:</strong></td>
                                <td><?=date('d M Y', strtotime($po['po_date']))?></td>
                            </tr>
                            <tr>
                                <td><strong>Expected Delivery:</strong></td>
                                <td><?=!empty($po['expected_delivery_date']) ? date('d M Y', strtotime($po['expected_delivery_date'])) : 'N/A'?></td>
                            </tr>
                            <tr>
                                <td><strong>Delivery Location:</strong></td>
                                <td><?=htmlspecialchars($po['delivery_location'] ?? '-')?></td>
                            </tr>
                            <tr>
                                <td><strong>PO Status:</strong></td>
                                <td>
                                    <?php 
                                        $statusClass = match($po['po_status']) {
                                            'Draft' => 'secondary',
                                            'Submitted' => 'warning',
                                            'Approved' => 'info',
                                            'PartialReceived' => 'primary',
                                            'Received' => 'success',
                                            'Cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                    <span class="badge bg-<?=$statusClass?>"><?=$po['po_status']?></span>
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
                        <?php if ($supplier): ?>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Supplier Name:</strong></td>
                                    <td><?=htmlspecialchars($supplier['supplier_name'])?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact Person:</strong></td>
                                    <td><?=htmlspecialchars($supplier['contact_person'] ?? '-')?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?=htmlspecialchars($supplier['email'] ?? '-')?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?=htmlspecialchars($supplier['phone'] ?? '-')?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?=htmlspecialchars($supplier['address'] ?? '-')?></td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">Supplier information not found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PO Items -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Purchase Order Items (<?=count($items)?> items)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width:25%">Product</th>
                                <th style="width:10%">Unit</th>
                                <th style="width:10%">Quantity</th>
                                <th style="width:12%">Unit Price</th>
                                <th style="width:10%">Discount %</th>
                                <th style="width:12%">Line Total</th>
                                <th style="width:10%">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?=htmlspecialchars($item['product_name'] ?? $item['item_description'])?></td>
                                    <td><?=htmlspecialchars($item['unit_type'] ?? '-')?></td>
                                    <td class="text-end"><?=number_format($item['quantity_ordered'] ?? 0, 2)?></td>
                                    <td class="text-end">₹ <?=number_format($item['unit_price'] ?? 0, 2)?></td>
                                    <td class="text-end"><?=number_format($item['discount_percent'] ?? 0, 2)?>%</td>
                                    <td class="text-end fw-bold">₹ <?=number_format($item['total_price'] ?? 0, 2)?></td>
                                    <td class="text-center"><?=!empty($item['quantity_received']) ? number_format($item['quantity_received'], 2) : '-'?></td>
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
                        <p><?=nl2br(htmlspecialchars($po['notes'] ?? 'No notes'))?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-header bg-success text-white">PO Summary</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-end">₹ <?=number_format($po['subtotal'], 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>Discount <?=number_format($po['discount_percentage'] ?? 0, 2)?>%:</strong></td>
                                <td class="text-end">-₹ <?=number_format($po['discount_amount'] ?? 0, 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>GST <?=number_format($po['gst_percentage'] ?? 0, 2)?>%:</strong></td>
                                <td class="text-end">₹ <?=number_format($po['gst_amount'], 2)?></td>
                            </tr>
                            <tr>
                                <td><strong>Other Charges:</strong></td>
                                <td class="text-end">₹ <?=number_format($po['other_charges'], 2)?></td>
                            </tr>
                            <tr class="fw-bold border-top border-bottom">
                                <td>Grand Total:</td>
                                <td class="text-end text-success">₹ <?=number_format($po['grand_total'], 2)?></td>
                            </tr>
                            <!-- Payment status removed from PO module (handled elsewhere) -->
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-md-12">
                <?php if ($po['po_status'] === 'Draft'): ?>
                    <a href="create_po.php?id=<?=$poId?>" class="btn btn-warning">
                        <i class="fa fa-edit"></i> Edit PO
                    </a>
                    <button class="btn btn-primary btn-submit-po" data-id="<?=$poId?>"><i class="fa fa-upload"></i> Submit</button>
                    <button class="btn btn-danger btn-cancel-po" data-id="<?=$poId?>"><i class="fa fa-trash"></i> Cancel</button>
                <?php elseif ($po['po_status'] === 'Submitted'): ?>
                    <button class="btn btn-success btn-approve-po" data-id="<?=$poId?>"><i class="fa fa-check"></i> Approve</button>
                    <button class="btn btn-secondary btn-reject-po" data-id="<?=$poId?>"><i class="fa fa-undo"></i> Reject</button>
                    <button class="btn btn-danger btn-cancel-po" data-id="<?=$poId?>"><i class="fa fa-trash"></i> Cancel</button>
                <?php elseif ($po['po_status'] === 'Approved'): ?>
                    <button class="btn btn-primary btn-mark-partial" data-id="<?=$poId?>"><i class="fa fa-box"></i> Partial Receive</button>
                    <button class="btn btn-success btn-mark-received" data-id="<?=$poId?>"><i class="fa fa-check-circle"></i> Receive All</button>
                <?php elseif ($po['po_status'] === 'PartialReceived'): ?>
                    <button class="btn btn-primary btn-update-received" data-id="<?=$poId?>"><i class="fa fa-edit"></i> Update Received Qty</button>
                <?php elseif ($po['po_status'] === 'Received'): ?>
                    <button class="btn btn-dark btn-close-po" data-id="<?=$poId?>"><i class="fa fa-lock"></i> Close PO</button>
                <?php endif; ?>

                <a href="print_po.php?id=<?=$poId?>" target="_blank" class="btn btn-info">
                    <i class="fa fa-print"></i> Print PO
                </a>

                <a href="po_list.php" class="btn btn-secondary">
                    <i class="fa fa-chevron-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!-- Update Received Modal -->
<div class="modal fade" id="updateReceivedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Received Quantities - PO #<?=htmlspecialchars($po['po_number'])?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateReceivedForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_received">
                    <input type="hidden" name="po_id" value="<?=$poId?>">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Ordered</th>
                                    <th class="text-end">Already Received</th>
                                    <th class="text-end">Update Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?=htmlspecialchars($it['product_name'] ?? $it['item_description'])?></td>
                                        <td class="text-end"><?=number_format($it['quantity_ordered'] ?? 0, 2)?></td>
                                        <td class="text-end"><?=number_format($it['quantity_received'] ?? 0, 2)?></td>
                                        <td class="text-end">
                                            <input type="hidden" name="item_id[]" value="<?=$it['po_item_id']?>">
                                            <input name="quantity_received[]" type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="<?=number_format($it['quantity_received'] ?? 0, 2, '.', '')?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Received Quantities</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Reuse handlers from list page
    $(document).on('click', '.btn-submit-po', function(){
        const poId = $(this).data('id'); if (!confirm('Submit this PO for approval?')) return;
        $.post('php_action/po_actions.php', { action: 'submit_po', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); location.reload(); } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error'));
    });

    $(document).on('click', '.btn-approve-po', function(){ const poId = $(this).data('id'); if (!confirm('Approve this PO?')) return; $.post('php_action/po_actions.php', { action: 'approve_po', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); location.reload(); } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error')); });

    $(document).on('click', '.btn-reject-po', function(){ const poId = $(this).data('id'); if (!confirm('Reject to Draft?')) return; $.post('php_action/po_actions.php', { action: 'reject_po', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); location.reload(); } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error')); });

    $(document).on('click', '.btn-cancel-po', function(){ const poId = $(this).data('id'); if (!confirm('Cancel this PO? This cannot be undone.')) return; $.post('php_action/po_actions.php', { action: 'cancel_po', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); window.location.href='po_list.php'; } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error')); });

    $(document).on('click', '.btn-mark-received', function(){ const poId = $(this).data('id'); if (!confirm('Mark all items as received?')) return; $.post('php_action/po_actions.php', { action: 'mark_received', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); location.reload(); } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error')); });

    $(document).on('click', '.btn-mark-partial, .btn-update-received', function(){
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const m = new bootstrap.Modal(document.getElementById('updateReceivedModal'));
            m.show();
        } else if ($.fn && $.fn.modal) {
            $('#updateReceivedModal').modal('show');
        } else {
            alert('Modal not available');
        }
    });

    // Submit updated received quantities
    $('#updateReceivedForm').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const data = form.serialize();
        $.post('php_action/po_actions.php', data, function(resp){
            if (resp.success) { alert(resp.message); location.reload(); }
            else alert('Error: '+(resp.error||'Unknown'));
        }, 'json').fail(()=>alert('Server error'));
    });

    $(document).on('click', '.btn-close-po', function(){ const poId = $(this).data('id'); if (!confirm('Close this PO (read-only)?')) return; $.post('php_action/po_actions.php', { action: 'close_po', po_id: poId }, function(resp){ if (resp.success) { alert(resp.message); location.reload(); } else alert('Error: '+resp.error); }, 'json').fail(()=>alert('Server error')); });
</script>

</body>
</html>
