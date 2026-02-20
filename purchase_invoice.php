<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
// Database connection check - use satyam_clinical_new
if (!isset($connect) || !$connect) {
    die("Database connection error");
}

// Fetch suppliers for select box
$suppliers = [];
$res = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' ORDER BY supplier_name");
if ($res) while ($r = $res->fetch_assoc()) $suppliers[] = $r;

// Fetch all products for autocomplete - INCLUDING GST RATE
$products = [];
$res = $connect->query("SELECT product_id, product_name, hsn_code, gst_rate FROM product WHERE status=1 ORDER BY product_name");
if ($res) while ($r = $res->fetch_assoc()) $products[] = $r;
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row page-titles mb-2">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-file-invoice"></i> Create Purchase Invoice</h3>
                <small class="text-muted">Professional Invoice Management System</small>
            </div>
            <div class="col-md-4 align-self-center">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Purchase Invoice</li>
                </ol>
            </div>
        </div>

        <form id="invoiceForm">
            <!-- Invoice Header Section -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-file-alt"></i> Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Row 1: Supplier Selection -->
                        <div class="col-md-6">
                            <label class="form-label"><strong>Supplier <span class="text-danger">*</span></strong></label>
                            <select name="supplier_id" id="supplier_id" class="form-control form-control-md" required>
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?=htmlspecialchars($s['supplier_id'])?>"><?=htmlspecialchars($s['supplier_name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Invoice Number <span class="text-danger">*</span></strong></label>
                            <input type="text" name="invoice_no" id="invoice_no" class="form-control form-control-md" placeholder="e.g., INV-2026-001" required>
                        </div>

                        <!-- Row 2: Dates -->
                        <div class="col-md-3">
                            <label class="form-label"><strong>Invoice Date <span class="text-danger">*</span></strong></label>
                            <input type="date" name="invoice_date" id="invoice_date" class="form-control form-control-md" value="<?=date('Y-m-d')?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>Due Date</strong></label>
                            <input type="date" name="due_date" id="due_date" class="form-control form-control-md">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>PO Reference</strong></label>
                            <input type="text" name="po_reference" id="po_reference" class="form-control form-control-md" placeholder="PO-001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>GRN Reference</strong></label>
                            <input type="text" name="grn_reference" id="grn_reference" class="form-control form-control-md" placeholder="GRN-001">
                        </div>

                        <!-- Row 3: Additional Fields -->
                        <div class="col-md-3">
                            <label class="form-label"><strong>Currency</strong></label>
                            <input type="text" name="currency" id="currency" class="form-control form-control-md" value="INR" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><strong>GST Type <span class="text-danger">*</span></strong></label>
                            <select name="gst_type" id="gst_type" class="form-control form-control-md" required>
                                <option value="">-- Select GST Type --</option>
                                <option value="intrastate">Intra-State (CGST + SGST)</option>
                                <option value="interstate">Inter-State (IGST)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><strong>Payment Terms</strong></label>
                            <input type="text" name="payment_terms" id="payment_terms" class="form-control form-control-md" placeholder="Net 30">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><strong>Invoice Status</strong></label>
                            <select name="status" id="status" class="form-control form-control-md">
                                <option value="Draft">Draft</option>
                                <option value="Received">Received</option>
                                <option value="Matched">Matched</option>
                                <option value="Approved">Approved</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier Information Card -->
            <div class="card mb-3" id="supplierDetailsCard" style="display: none;">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fa fa-building"></i> Supplier Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Company:</strong> <span id="supplier_company">-</span></p>
                            <p><strong>Contact Person:</strong> <span id="supplier_contact">-</span></p>
                            <p><strong>Email:</strong> <span id="supplier_email">-</span></p>
                            <p><strong>Phone:</strong> <span id="supplier_phone">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <span id="supplier_address">-</span></p>
                            <p><strong>City:</strong> <span id="supplier_city">-</span></p>
                            <p><strong>GST Number:</strong> <span id="supplier_gst">-</span></p>
                            <p><strong>Credit Days:</strong> <span id="supplier_credit">-</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items Section -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-list"></i> Invoice Items</h5>
                        <button type="button" id="addRow" class="btn btn-sm btn-light">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:14%">Product Name</th>
                                    <th style="width:5%">HSN</th>
                                    <th style="width:6%">Batch</th>
                                    <th style="width:6%">MFG</th>
                                    <th style="width:6%">Expiry</th>
                                    <th style="width:5%">Qty</th>
                                    <th style="width:5%">Free</th>
                                    <th style="width:7%">Cost</th>
                                    <th style="width:7%">MRP</th>
                                    <th style="width:6%">Margin %</th>
                                    <th style="width:5%">Disc%</th>
                                    <th style="width:5%">GST%</th>
                                    <th style="width:8%">Total</th>
                                    <th style="width:3%">×</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa fa-sticky-note"></i> Notes</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Invoice notes, terms & conditions, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fa fa-calculator"></i> Invoice Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Subtotal:</strong>
                                <span id="subtotal" class="h6">₹ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Total Discount:</strong>
                                <span id="total_discount" class="h6">₹ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Taxable Value:</strong>
                                <span id="taxable_value" class="h6">₹ 0.00</span>
                            </div>
                            <div id="gst_details" style="display:none;">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>CGST:</strong>
                                    <span id="total_cgst" class="h6">₹ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>SGST:</strong>
                                    <span id="total_sgst" class="h6">₹ 0.00</span>
                                </div>
                            </div>
                            <div id="igst_details" style="display:none;">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>IGST:</strong>
                                    <span id="total_igst" class="h6">₹ 0.00</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Freight Charges:</strong>
                                <div>
                                    <input type="number" step="0.01" id="freight" name="freight" class="form-control form-control-sm d-inline-block" style="width: 120px;" value="0">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Round Off:</strong>
                                <div>
                                    <input type="number" step="0.01" id="round_off" name="round_off" class="form-control form-control-sm d-inline-block" style="width: 120px;" value="0">
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <h5>Grand Total:</h5>
                                <h3 class="text-success" id="grand_total">₹ 0.00</h3>
                            </div>
                            <hr>
                            <h6 class="text-info mb-3"><i class="fa fa-credit-card"></i> Payment Information</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Payment Mode:</strong>
                                <select id="payment_mode" name="payment_mode" class="form-control form-control-sm d-inline-block" style="width: 150px;">
                                    <option value="Credit">Credit</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Amount Paid:</strong>
                                <div>
                                    <input type="number" step="0.01" id="paid_amount" name="paid_amount" class="form-control form-control-sm d-inline-block" style="width: 120px;" value="0">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h5 class="text-warning">Outstanding:</h5>
                                <h5 class="text-warning" id="outstanding_amount">₹ 0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4 mb-4">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-success btn-lg me-2">
                        <i class="fa fa-save"></i> Save as Draft
                    </button>
                    <button type="button" id="approveBtn" class="btn btn-primary btn-lg me-2">
                        <i class="fa fa-check"></i> Save & Approve
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary btn-lg">
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
<script>
    const products = <?php echo json_encode($products); ?>;
    const COMPANY_STATE = 'Gujarat'; // Auto-detect from DB in production

    // Fetch and display supplier details with auto GST detection
    $('#supplier_id').on('change', function(){
        const supplierId = $(this).val();
        if (supplierId) {
            $.ajax({
                url: 'php_action/get_supplier_details.php',
                method: 'POST',
                data: { supplier_id: supplierId },
                dataType: 'json',
                success: function(resp){
                    if (resp.success) {
                        const s = resp.data;
                        $('#supplier_company').text(s.company_name || '-');
                        $('#supplier_contact').text(s.contact_person || '-');
                        $('#supplier_email').text(s.email || '-');
                        $('#supplier_phone').text(s.phone || '-');
                        $('#supplier_address').text(s.address || '-');
                        $('#supplier_city').text((s.city || '') + ', ' + (s.state || ''));
                        $('#supplier_gst').text(s.gst_number || '-');
                        $('#supplier_credit').text((s.credit_days || 30) + ' days');
                        
                        // Store supplier state for GST determination
                        const supplierState = s.state || '';
                        
                        // AUTO-DETECT GST TYPE
                        if (supplierState && supplierState.toLowerCase() === COMPANY_STATE.toLowerCase()) {
                            $('#gst_type').val('intrastate').trigger('change');
                        } else if (supplierState) {
                            $('#gst_type').val('interstate').trigger('change');
                        }
                        
                        // Auto-fill payment terms based on supplier
                        if (s.payment_terms) $('#payment_terms').val(s.payment_terms);
                        if (s.credit_days) {
                            const dueDate = new Date();
                            dueDate.setDate(dueDate.getDate() + s.credit_days);
                            $('#due_date').val(dueDate.toISOString().split('T')[0]);
                        }
                        
                        $('#supplierDetailsCard').slideDown();
                        recalcTotals(); // Recalculate if GST type changed
                    }
                },
                error: function(){ $('#supplierDetailsCard').slideUp(); }
            });
        } else {
            $('#supplierDetailsCard').slideUp();
            $('#gst_type').val(''); // Clear GST type if supplier deselected
        }
    });

    // Add empty row to table
    function addEmptyRow(){
        const row = `
        <tr>
            <td>
                <input type="hidden" class="product_id" value="0">
                <input class="form-control form-control-sm product_name" placeholder="Search..." autocomplete="off">
                <div class="product_suggest" style="display:none; position:absolute; background:#fff; border:1px solid #ddd; max-height:150px; overflow-y:auto; width:250px; z-index:1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
            </td>
            <td><input class="form-control form-control-sm hsn_code" readonly></td>
            <td><input class="form-control form-control-sm batch_no" required></td>
            <td><input type="date" class="form-control form-control-sm manufacture_date"></td>
            <td><input type="date" class="form-control form-control-sm expiry_date" required></td>
            <td><input type="number" step="0.001" class="form-control form-control-sm qty" value="0" required></td>
            <td><input type="number" step="0.001" class="form-control form-control-sm free_qty" value="0"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm unit_cost" value="0" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm mrp" value="0" required title="Supplier quoted MRP"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm margin_percent" value="0" readonly title="Calculated margin %"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm disc" value="0" title="Discount %"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm gst_percent" value="0" required title="Tax rate from product master"></td>
            <td class="line_total text-end fw-bold">₹ 0.00</td>
            <td><button type="button" class="btn btn-sm btn-danger remove" title="Remove item"><i class="fa fa-trash"></i></button></td>
        </tr>
        `;
        $('#itemsTable tbody').append(row);
    }

    // Recalculate totals with proper multi-rate GST support
    function recalcTotals(){
        const gstType = $('#gst_type').val();
        let subtotal = 0, total_discount = 0, total_cgst = 0, total_sgst = 0, total_igst = 0, total_tax = 0, taxable = 0;
        
        $('#itemsTable tbody tr').each(function(){
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const unit = parseFloat($(this).find('.unit_cost').val()) || 0;
            const mrp = parseFloat($(this).find('.mrp').val()) || 0;
            const discP = parseFloat($(this).find('.disc').val()) || 0;
            const gstP = parseFloat($(this).find('.gst_percent').val()) || 0; // Per-item tax rate
            
            // Calculate margin
            let marginPercent = 0;
            if (mrp > 0 && unit > 0) {
                marginPercent = ((mrp - unit) / unit) * 100;
            }
            $(this).find('.margin_percent').val(marginPercent.toFixed(2));
            
            const lineAmount = qty * unit;
            const discAmt = lineAmount * discP / 100;
            const taxableAmt = lineAmount - discAmt;
            let taxAmt = 0, cgstAmt = 0, sgstAmt = 0, igstAmt = 0;
            
            // Per-item tax calculation based on GST type and product's tax rate
            if (gstType === 'intrastate') {
                const half = gstP / 2;
                cgstAmt = taxableAmt * half / 100;
                sgstAmt = taxableAmt * half / 100;
                taxAmt = cgstAmt + sgstAmt;
                total_cgst += cgstAmt;
                total_sgst += sgstAmt;
            } else if (gstType === 'interstate') {
                igstAmt = taxableAmt * gstP / 100;
                taxAmt = igstAmt;
                total_igst += igstAmt;
            }
            
            subtotal += lineAmount;
            total_discount += discAmt;
            taxable += taxableAmt;
            total_tax += taxAmt;
            
            const total = taxableAmt + taxAmt;
            $(this).find('.line_total').text('₹ ' + total.toFixed(2));
        });
        
        $('#subtotal').text('₹ ' + subtotal.toFixed(2));
        $('#total_discount').text('₹ ' + total_discount.toFixed(2));
        $('#taxable_value').text('₹ ' + taxable.toFixed(2));
        
        // Show appropriate GST breakdown based on type
        if (gstType === 'intrastate') {
            $('#gst_details').show();
            $('#igst_details').hide();
            $('#total_cgst').text('₹ ' + total_cgst.toFixed(2));
            $('#total_sgst').text('₹ ' + total_sgst.toFixed(2));
        } else if (gstType === 'interstate') {
            $('#gst_details').hide();
            $('#igst_details').show();
            $('#total_igst').text('₹ ' + total_igst.toFixed(2));
        } else {
            $('#gst_details').hide();
            $('#igst_details').hide();
        }
        
        const freight = parseFloat($('#freight').val()) || 0;
        const round_off = parseFloat($('#round_off').val()) || 0;
        const paid = parseFloat($('#paid_amount').val()) || 0;
        const grand = subtotal - total_discount + total_tax + freight + round_off;
        const outstanding = grand - paid;
        
        $('#grand_total').text('₹ ' + grand.toFixed(2));
        $('#outstanding_amount').text('₹ ' + outstanding.toFixed(2));
        
        // Change color based on payment status
        if (outstanding <= 0) {
            $('#outstanding_amount').removeClass('text-warning').addClass('text-success');
        } else {
            $('#outstanding_amount').removeClass('text-success').addClass('text-warning');
        }
    }

    // Document ready
    $(document).ready(function(){
        addEmptyRow();

        // Add row button
        $('#addRow').on('click', function(){ addEmptyRow(); });

        // Remove row
        $('#itemsTable').on('click', '.remove', function(){
            $(this).closest('tr').remove();
            recalcTotals();
        });

        // Recalculate on any input change
        $('#itemsTable').on('input', 'input', recalcTotals);
        $('#freight, #round_off, #paid_amount, #gst_type').on('input change', recalcTotals);
        
        // GST type change should also recalculate
        $('#gst_type').on('change', recalcTotals);

        // Product autocomplete
        $('#itemsTable').on('input', '.product_name', function(){
            const val = $(this).val().toLowerCase();
            const row = $(this).closest('tr');
            const suggest = row.find('.product_suggest');
            
            if (val.length < 1) {
                suggest.hide();
                return;
            }
            
            let matches = products.filter(p => 
                p.product_name.toLowerCase().includes(val) || 
                p.product_id.toString().includes(val)
            ).slice(0, 10);
            
            if (matches.length === 0) {
                suggest.html('<div style="padding:5px; color:#999;">No products found</div>').show();
                return;
            }
            
            let html = matches.map(p => 
                `<div class="product-option" data-product-id="${p.product_id}" data-product-name="${p.product_name}" data-hsn-code="${p.hsn_code || ''}" data-gst-rate="${p.gst_rate || 0}" style="padding:8px 10px; cursor:pointer; border-bottom:1px solid #eee;">${p.product_name} <span style="color:#999; font-size:0.85em;">(${p.product_id}) GST:${p.gst_rate || 0}%</span></div>`
            ).join('');
            
            suggest.html(html).show();
        });

        // Product selection - with auto-fetch of tax rate
        $('#itemsTable').on('click', '.product-option', function(){
            const row = $(this).closest('tr');
            row.find('.product_id').val($(this).data('product-id'));
            row.find('.product_name').val($(this).data('product-name'));
            row.find('.hsn_code').val($(this).data('hsn-code'));
            
            // AUTO-FETCH GST RATE from product master
            const productId = $(this).data('product-id');
            const gstRate = $(this).data('gst-rate');
            row.find('.gst_percent').val(gstRate || 0);
            
            row.find('.product_suggest').hide();
            
            // Mark this as auto-filled
            row.find('.auto_filled').remove();
            row.find('.gst_percent').after('<small class="auto_filled text-success ms-1">(auto)</small>');
            
            recalcTotals();
        });

        // Hide suggest on outside click
        $(document).on('click', function(e){
            if (!$(e.target).closest('.product_name').length) {
                $('.product_suggest').hide();
            }
        });

        // Form submission
        $('#invoiceForm').on('submit', function(e){
            e.preventDefault();
            submitInvoice('Draft');
        });

        $('#approveBtn').on('click', function(){
            submitInvoice('Approved');
        });
    });

    // Submit invoice with proper per-item tax handling
    function submitInvoice(status){
        if (!$('#supplier_id').val()) {
            alert('Please select a supplier');
            return;
        }
        
        if (!$('#gst_type').val()) {
            alert('Please ensure GST type is auto-detected from supplier location');
            return;
        }

        const gstType = $('#gst_type').val();
        const items = [];
        let formValid = true;
        
        $('#itemsTable tbody tr').each(function(){
            const p = $(this).find('.product_name').val();
            if (!p) return; // Skip empty rows
            
            const batch = $(this).find('.batch_no').val();
            const expiry = $(this).find('.expiry_date').val();
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const mrp = parseFloat($(this).find('.mrp').val()) || 0;
            const gstP = parseFloat($(this).find('.gst_percent').val()) || 0;
            
            // Validations
            if (!batch) {
                alert('Batch number is required for all items');
                formValid = false;
                return false;
            }
            if (!expiry) {
                alert('Expiry date is required for all items');
                formValid = false;
                return false;
            }
            if (qty <= 0) {
                alert('Quantity must be greater than 0');
                formValid = false;
                return false;
            }
            if (mrp <= 0) {
                alert('MRP must be greater than 0');
                formValid = false;
                return false;
            }
            if (gstP < 0 || gstP > 100) {
                alert('GST rate must be between 0-100%');
                formValid = false;
                return false;
            }
            if (new Date(expiry) <= new Date($('#invoice_date').val())) {
                alert('Expiry date must be after invoice date');
                formValid = false;
                return false;
            }
            
            const unit = parseFloat($(this).find('.unit_cost').val()) || 0;
            const discP = parseFloat($(this).find('.disc').val()) || 0;
            
            // Per-item calculations (frontend display only, backend will recalculate)
            const lineAmount = qty * unit;
            const discAmt = lineAmount * discP / 100;
            const taxableAmt = lineAmount - discAmt;
            let cgstAmt = 0, sgstAmt = 0, igstAmt = 0;
            
            if (gstType === 'intrastate') {
                const half = gstP / 2;
                cgstAmt = taxableAmt * half / 100;
                sgstAmt = taxableAmt * half / 100;
            } else if (gstType === 'interstate') {
                igstAmt = taxableAmt * gstP / 100;
            }
            
            const taxAmt = cgstAmt + sgstAmt + igstAmt;
            const total = taxableAmt + taxAmt;
            
            items.push({
                product_id: parseInt($(this).find('.product_id').val()) || 0,
                product_name: p,
                hsn_code: $(this).find('.hsn_code').val(),
                batch_no: batch,
                manufacture_date: $(this).find('.manufacture_date').val() || null,
                expiry_date: expiry,
                qty: qty,
                free_qty: parseFloat($(this).find('.free_qty').val()) || 0,
                unit_cost: unit,
                mrp: mrp,
                discount_percent: discP,
                discount_amount: discAmt,
                taxable_value: taxableAmt,
                cgst_percent: gstType === 'intrastate' ? gstP / 2 : 0,
                sgst_percent: gstType === 'intrastate' ? gstP / 2 : 0,
                igst_percent: gstType === 'interstate' ? gstP : 0,
                cgst_amount: cgstAmt,
                sgst_amount: sgstAmt,
                igst_amount: igstAmt,
                tax_rate: gstP,
                tax_amount: taxAmt,
                line_total: total,
                margin_percent: parseFloat($(this).find('.margin_percent').val()) || 0
            });
        });

        if (!formValid || items.length === 0) {
            if (items.length === 0) alert('Please add at least one invoice item');
            return;
        }

        const payload = {
            supplier_id: $('#supplier_id').val(),
            invoice_no: $('#invoice_no').val(),
            invoice_date: $('#invoice_date').val(),
            due_date: $('#due_date').val() || null,
            po_reference: $('#po_reference').val(),
            grn_reference: $('#grn_reference').val(),
            gst_type: gstType,
            currency: $('#currency').val(),
            subtotal: parseFloat($('#subtotal').text().replace('₹ ', '')) || 0,
            total_discount: parseFloat($('#total_discount').text().replace('₹ ', '')) || 0,
            taxable_value: parseFloat($('#taxable_value').text().replace('₹ ', '')) || 0,
            total_cgst: parseFloat($('#total_cgst').text().replace('₹ ', '')) || 0,
            total_sgst: parseFloat($('#total_sgst').text().replace('₹ ', '')) || 0,
            total_igst: parseFloat($('#total_igst').text().replace('₹ ', '')) || 0,
            total_tax: parseFloat($('#total_cgst').text().replace('₹ ', '')) + parseFloat($('#total_sgst').text().replace('₹ ', '')) + parseFloat($('#total_igst').text().replace('₹ ', '')) || 0,
            freight: parseFloat($('#freight').val()) || 0,
            round_off: parseFloat($('#round_off').val()) || 0,
            grand_total: parseFloat($('#grand_total').text().replace('₹ ', '')) || 0,
            paid_amount: parseFloat($('#paid_amount').val()) || 0,
            outstanding_amount: parseFloat($('#outstanding_amount').text().replace('₹ ', '')) || 0,
            payment_mode: $('#payment_mode').val(),
            payment_terms: $('#payment_terms').val(),
            status: status,
            notes: $('#notes').val(),
            items: items
        };

        $.ajax({
            url: 'php_action/create_purchase_invoice.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(resp){
                try {
                    var j = (typeof resp === 'string')? JSON.parse(resp): resp;
                } catch(e) {
                    alert('Unexpected response from server');
                    console.log(resp);
                    return;
                }
                if (j.success) {
                    alert('✓ Invoice created successfully!\nInvoice ID: ' + j.invoice_id);
                    window.location.href = 'invoice_list.php';
                } else {
                    alert('✗ Error: ' + (j.error||'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error){
                alert('✗ Server error: ' + error);
                console.log(xhr.responseText);
            }
        });
    }
</script>
</body>
</html>
