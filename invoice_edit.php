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

// Only Draft invoices can be edited
if ($invoice['status'] !== 'Draft') {
    die('<div class="alert alert-danger">Only Draft invoices can be edited</div>');
}

// Get suppliers list for dropdown
$suppliers = [];
$res = $connect->query("SELECT supplier_id, supplier_name, state, gst_number FROM suppliers WHERE supplier_status='Active' ORDER BY supplier_name");
if ($res) while ($r = $res->fetch_assoc()) $suppliers[] = $r;
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row page-titles mb-3">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-pen-square"></i> Edit Purchase Invoice</h3>
                <small class="text-muted">Invoice #<?=htmlspecialchars($invoice['invoice_no'])?></small>
            </div>
            <div class="col-md-4 align-self-center text-end">
                <a href="invoice_list.php" class="btn btn-secondary btn-sm">
                    <i class="fa fa-chevron-left"></i> Cancel
                </a>
            </div>
        </div>

        <form id="editInvoiceForm" method="POST" action="php_action/po_edit_action.php">
            <input type="hidden" name="invoice_id" value="<?=$invoiceId?>">

            <div class="row">
                <!-- Left Column: Header Info -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">Invoice Details</div>
                        <div class="card-body">
                            <!-- Invoice Number -->
                            <div class="form-group mb-2">
                                <label>Invoice Number *</label>
                                <input type="text" name="invoice_no" class="form-control" value="<?=htmlspecialchars($invoice['invoice_no'])?>" required>
                            </div>

                            <!-- Supplier -->
                            <div class="form-group mb-2">
                                <label>Supplier *</label>
                                <select name="supplier_id" id="supplier" class="form-control" required>
                                    <option value="">-- Select Supplier --</option>
                                    <?php foreach ($suppliers as $supp): ?>
                                        <option value="<?=$supp['supplier_id']?>" 
                                                data-state="<?=$supp['state']?>"
                                                data-gstin="<?=$supp['gst_number']?>"
                                                <?=$invoice['supplier_id']==$supp['supplier_id']?'selected':''?>>
                                            <?=htmlspecialchars($supp['supplier_name'])?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Supplier State (Read-only) -->
                            <div class="form-group mb-2">
                                <label>Supplier State</label>
                                <input type="text" id="supplierState" class="form-control" readonly value="<?=$invoice['supplier_location_state'] ?? ''?>">
                            </div>

                            <!-- GST Type (Auto-determined) -->
                            <div class="form-group mb-2">
                                <label>GST Type (Auto) *</label>
                                <select name="gst_determination_type" id="gstType" class="form-control" required>
                                    <option value="intrastate" <?=$invoice['gst_determination_type']=='intrastate'?'selected':''?>>Intra-State</option>
                                    <option value="interstate" <?=$invoice['gst_determination_type']=='interstate'?'selected':''?>>Inter-State</option>
                                </select>
                                <small class="text-muted">Automatically set based on supplier state (Gujarat = Intra-State)</small>
                            </div>

                            <!-- Invoice Date -->
                            <div class="form-group mb-2">
                                <label>Invoice Date *</label>
                                <input type="date" name="invoice_date" class="form-control" value="<?=$invoice['invoice_date']?>" required>
                            </div>

                            <!-- Due Date -->
                            <div class="form-group mb-2">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="<?=$invoice['due_date'] ?? ''?>">
                            </div>

                            <!-- PO Reference -->
                            <div class="form-group mb-2">
                                <label>PO Reference</label>
                                <input type="text" name="po_reference" class="form-control" value="<?=htmlspecialchars($invoice['po_reference'] ?? '')?>">
                            </div>

                            <!-- GRN Reference -->
                            <div class="form-group mb-2">
                                <label>GRN Reference</label>
                                <input type="text" name="grn_reference" class="form-control" value="<?=htmlspecialchars($invoice['grn_reference'] ?? '')?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Additional Info -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">Additional Information</div>
                        <div class="card-body">
                            <!-- Payment Mode -->
                            <div class="form-group mb-2">
                                <label>Payment Mode</label>
                                <select name="payment_mode" class="form-control">
                                    <option value="">-- Select --</option>
                                    <option value="Cash" <?=$invoice['payment_mode']=='Cash'?'selected':''?>>Cash</option>
                                    <option value="Check" <?=$invoice['payment_mode']=='Check'?'selected':''?>>Check</option>
                                    <option value="Bank Transfer" <?=$invoice['payment_mode']=='Bank Transfer'?'selected':''?>>Bank Transfer</option>
                                    <option value="Credit" <?=$invoice['payment_mode']=='Credit'?'selected':''?>>Credit</option>
                                </select>
                            </div>

                            <!-- Freight -->
                            <div class="form-group mb-2">
                                <label>Freight (₹)</label>
                                <input type="number" name="freight" class="form-control" value="<?=$invoice['freight'] ?? 0?>" step="0.01" onchange="recalcTotals()">
                            </div>

                            <!-- Round Off -->
                            <div class="form-group mb-2">
                                <label>Round Off (₹)</label>
                                <input type="number" name="round_off" class="form-control" value="<?=$invoice['round_off'] ?? 0?>" step="0.01" onchange="recalcTotals()">
                            </div>

                            <!-- Total Discount -->
                            <div class="form-group mb-2">
                                <label>Total Discount (₹)</label>
                                <input type="number" name="total_discount" class="form-control" value="<?=$invoice['total_discount'] ?? 0?>" step="0.01" onchange="recalcTotals()">
                            </div>

                            <!-- Paid Amount -->
                            <div class="form-group mb-2">
                                <label>Paid Amount (₹)</label>
                                <input type="number" name="paid_amount" class="form-control" value="<?=$invoice['paid_amount']?>" step="0.01" onchange="recalcTotals()">
                            </div>

                            <!-- Notes -->
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?=htmlspecialchars($invoice['notes'] ?? '')?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Invoice Items</h5>
                        <button type="button" class="btn btn-light btn-sm" onclick="addItemRow()">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:20%">Product Name</th>
                                    <th style="width:8%">Batch</th>
                                    <th style="width:8%">Expiry</th>
                                    <th style="width:7%">Qty</th>
                                    <th style="width:10%">Unit Cost</th>
                                    <th style="width:10%">MRP</th>
                                    <th style="width:7%">Margin%</th>
                                    <th style="width:8%">Tax%</th>
                                    <th style="width:10%">Line Total</th>
                                    <th style="width:6%">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <?php foreach ($invoice['items'] as $item): ?>
                                    <tr class="item-row">
                                        <td>
                                            <input type="hidden" class="product-id" value="<?=$item['product_id']?>">
                                            <input type="hidden" class="hsn-code" value="<?=htmlspecialchars($item['hsn_code'] ?? '')?>">
                                            <input type="text" class="form-control form-control-sm product-name" placeholder="Search product..." value="<?=htmlspecialchars($item['product_name'])?>" data-gst="0">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm batch-no" placeholder="Batch" value="<?=htmlspecialchars($item['batch_no'])?>">
                                        </td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm expiry-date" value="<?=$item['expiry_date']?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm qty" placeholder="Qty" value="<?=$item['qty']?>" step="0.001" onchange="calcItemTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm unit-cost" placeholder="Cost" value="<?=$item['unit_cost']?>" step="0.01" onchange="calcItemTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm mrp" placeholder="MRP" value="<?=$item['mrp']?>" step="0.01" onchange="calcItemTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm margin-pct" placeholder="Margin%" value="<?=$item['margin_percent'] ?? 0?>" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm tax-rate" placeholder="Tax%" value="<?=$item['tax_rate'] ?? 0?>" step="0.01" onchange="calcItemTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm line-total" placeholder="Total" value="<?=$item['line_total']?>" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); recalcTotals();">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-end"><input type="hidden" name="subtotal" value="0"> ₹ <span id="subtotalDisplay">0.00</span></td>
                                </tr>
                                <tr>
                                    <td><strong>CGST:</strong></td>
                                    <td class="text-end"><input type="hidden" name="total_cgst" value="0"> ₹ <span id="cgstDisplay">0.00</span></td>
                                </tr>
                                <tr>
                                    <td><strong>SGST:</strong></td>
                                    <td class="text-end"><input type="hidden" name="total_sgst" value="0"> ₹ <span id="sgstDisplay">0.00</span></td>
                                </tr>
                                <tr>
                                    <td><strong>IGST:</strong></td>
                                    <td class="text-end"><input type="hidden" name="total_igst" value="0"> ₹ <span id="igstDisplay">0.00</span></td>
                                </tr>
                                <tr class="fw-bold border-top border-bottom">
                                    <td>Grand Total:</td>
                                    <td class="text-end text-success"><input type="hidden" name="grand_total" value="0"> ₹ <span id="grandTotalDisplay">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Outstanding:</td>
                                    <td class="text-end"><input type="hidden" name="outstanding_amount" value="0"> ₹ <span id="outstandingDisplay">0.00</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="row mt-3 mb-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                    <a href="po_view.php?id=<?=$invoiceId?>" class="btn btn-secondary">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery-ui.min.js"></script>

<script>
    const COMPANY_STATE = 'Gujarat';

    // Auto-complete for products
    $('.product-name').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'php_action/get_product_list.php',
                data: { q: request.term },
                dataType: 'json',
                success: function(data) {
                    response(data.map(p => ({
                        label: p.product_name + ' (GST: ' + p.gst_rate + '%)',
                        value: p.product_name,
                        product_id: p.product_id,
                        gst_rate: p.gst_rate,
                        hsn_code: p.hsn_code
                    })));
                }
            });
        },
        select: function(event, ui) {
            const $row = $(this).closest('tr');
            $row.find('.product-id').val(ui.item.product_id);
            $row.find('.hsn-code').val(ui.item.hsn_code);
            $(this).data('gst', ui.item.gst_rate);
            $(this).data('tax', ui.item.gst_rate);
            $row.find('.tax-rate').val(ui.item.gst_rate).trigger('change');
            return false;
        },
        minLength: 2
    });

    // Supplier change - update GST type
    $('#supplier').change(function() {
        const state = $(this).find(':selected').data('state');
        $('#supplierState').val(state || '');
        
        // Auto-set GST type based on state
        const gstType = (state === COMPANY_STATE) ? 'intrastate' : 'interstate';
        $('#gstType').val(gstType).trigger('change');
    });

    // Calculate item totals
    function calcItemTotal(element) {
        const $row = $(element).closest('tr');
        const qty = parseFloat($row.find('.qty').val()) || 0;
        const unitCost = parseFloat($row.find('.unit-cost').val()) || 0;
        const mrp = parseFloat($row.find('.mrp').val()) || 0;
        const taxRate = parseFloat($row.find('.tax-rate').val()) || 0;

        // Calculate margin %
        const margin = qty > 0 && unitCost > 0 ? ((mrp - unitCost) / unitCost) * 100 : 0;
        $row.find('.margin-pct').val(margin.toFixed(2));

        // Calculate line total: (qty * unitCost) * (1 + tax%/100)
        const subtotal = qty * unitCost;
        const taxAmount = subtotal * (taxRate / 100);
        const lineTotal = subtotal + taxAmount;
        $row.find('.line-total').val(lineTotal.toFixed(2));

        recalcTotals();
    }

    // Recalculate all totals
    function recalcTotals() {
        let subtotal = 0;
        let totalTax = 0;
        let totalCgst = 0;
        let totalSgst = 0;
        let totalIgst = 0;

        $('#itemsBody tr').each(function() {
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const unitCost = parseFloat($(this).find('.unit-cost').val()) || 0;
            const taxRate = parseFloat($(this).find('.tax-rate').val()) || 0;

            const lineSub = qty * unitCost;
            const lineTax = lineSub * (taxRate / 100);

            subtotal += lineSub;
            totalTax += lineTax;

            // Determine if intrastate or interstate
            const gstType = $('#gstType').val();
            if (gstType === 'intrastate') {
                totalCgst += lineTax / 2;
                totalSgst += lineTax / 2;
            } else {
                totalIgst += lineTax;
            }
        });

        const discount = parseFloat($('input[name="total_discount"]').val()) || 0;
        const freight = parseFloat($('input[name="freight"]').val()) || 0;
        const roundOff = parseFloat($('input[name="round_off"]').val()) || 0;

        const grandTotal = subtotal + totalTax + freight + roundOff - discount;
        const paidAmount = parseFloat($('input[name="paid_amount"]').val()) || 0;
        const outstanding = grandTotal - paidAmount;

        // Update display
        $('input[name="subtotal"]').val(subtotal.toFixed(2));
        $('input[name="total_cgst"]').val(totalCgst.toFixed(2));
        $('input[name="total_sgst"]').val(totalSgst.toFixed(2));
        $('input[name="total_igst"]').val(totalIgst.toFixed(2));
        $('input[name="grand_total"]').val(grandTotal.toFixed(2));
        $('input[name="outstanding_amount"]').val(outstanding.toFixed(2));

        $('#subtotalDisplay').text(subtotal.toFixed(2));
        $('#cgstDisplay').text(totalCgst.toFixed(2));
        $('#sgstDisplay').text(totalSgst.toFixed(2));
        $('#igstDisplay').text(totalIgst.toFixed(2));
        $('#grandTotalDisplay').text(grandTotal.toFixed(2));
        $('#outstandingDisplay').text(outstanding.toFixed(2));
    }

    // Add blank item row
    function addItemRow() {
        const html = `
            <tr class="item-row">
                <td>
                    <input type="hidden" class="product-id" value="">
                    <input type="hidden" class="hsn-code" value="">
                    <input type="text" class="form-control form-control-sm product-name" placeholder="Search product..." data-gst="0">
                </td>
                <td><input type="text" class="form-control form-control-sm batch-no" placeholder="Batch"></td>
                <td><input type="date" class="form-control form-control-sm expiry-date"></td>
                <td><input type="number" class="form-control form-control-sm qty" placeholder="Qty" step="0.001" onchange="calcItemTotal(this)"></td>
                <td><input type="number" class="form-control form-control-sm unit-cost" placeholder="Cost" step="0.01" onchange="calcItemTotal(this)"></td>
                <td><input type="number" class="form-control form-control-sm mrp" placeholder="MRP" step="0.01" onchange="calcItemTotal(this)"></td>
                <td><input type="number" class="form-control form-control-sm margin-pct" placeholder="Margin%" step="0.01" readonly></td>
                <td><input type="number" class="form-control form-control-sm tax-rate" placeholder="Tax%" step="0.01" onchange="calcItemTotal(this)"></td>
                <td><input type="number" class="form-control form-control-sm line-total" placeholder="Total" step="0.01" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); recalcTotals();"><i class="fa fa-trash"></i></button></td>
            </tr>
        `;
        $('#itemsBody').append(html);

        // Attach autocomplete to new row
        $('#itemsBody tr:last .product-name').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'php_action/get_product_list.php',
                    data: { q: request.term },
                    dataType: 'json',
                    success: function(data) {
                        response(data.map(p => ({
                            label: p.product_name + ' (GST: ' + p.gst_rate + '%)',
                            value: p.product_name,
                            product_id: p.product_id,
                            gst_rate: p.gst_rate,
                            hsn_code: p.hsn_code
                        })));
                    }
                });
            },
            select: function(event, ui) {
                const $row = $(this).closest('tr');
                $row.find('.product-id').val(ui.item.product_id);
                $row.find('.hsn-code').val(ui.item.hsn_code);
                $(this).data('gst', ui.item.gst_rate);
                $row.find('.tax-rate').val(ui.item.gst_rate).trigger('change');
                return false;
            },
            minLength: 2
        });
    }

    // Form submission
    $('#editInvoiceForm').submit(function(e) {
        e.preventDefault();

        // Validate at least one item
        if ($('#itemsBody tr').length === 0) {
            alert('Please add at least one item');
            return;
        }

        // Collect items data
        const items = [];
        $('#itemsBody tr').each(function() {
            const item = {
                product_id: $(this).find('.product-id').val(),
                batch_no: $(this).find('.batch-no').val(),
                expiry_date: $(this).find('.expiry-date').val(),
                qty: $(this).find('.qty').val(),
                unit_cost: $(this).find('.unit-cost').val(),
                mrp: $(this).find('.mrp').val(),
                tax_rate: $(this).find('.tax-rate').val()
            };
            items.push(item);
        });

        // Send to backend
        $.ajax({
            url: 'php_action/po_edit_action.php',
            method: 'POST',
            dataType: 'json',
            data: {
                invoice_id: $('input[name="invoice_id"]').val(),
                invoice_no: $('input[name="invoice_no"]').val(),
                supplier_id: $('select[name="supplier_id"]').val(),
                gst_determination_type: $('select[name="gst_determination_type"]').val(),
                invoice_date: $('input[name="invoice_date"]').val(),
                due_date: $('input[name="due_date"]').val(),
                po_reference: $('input[name="po_reference"]').val(),
                grn_reference: $('input[name="grn_reference"]').val(),
                payment_mode: $('select[name="payment_mode"]').val(),
                freight: $('input[name="freight"]').val(),
                round_off: $('input[name="round_off"]').val(),
                total_discount: $('input[name="total_discount"]').val(),
                paid_amount: $('input[name="paid_amount"]').val(),
                notes: $('textarea[name="notes"]').val(),
                items: items
            },
            success: function(resp) {
                if (resp.success) {
                    alert('✓ Invoice updated successfully');
                    window.location.href = 'invoice_view.php?id=' + $('input[name="invoice_id"]').val();
                } else {
                    alert('✗ Error: ' + resp.error);
                }
            },
            error: function() {
                alert('Server error occurred');
            }
        });
    });

    // Initialize calculations on page load
    $(document).ready(function() {
        recalcTotals();
    });
</script>

</body>
</html>
