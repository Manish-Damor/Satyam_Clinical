<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';

// Get current user
$userId = $_SESSION['userId'] ?? null;

// Edit mode
$editMode = false;
$invoiceData = [];
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $editMode = true;
    $invoiceId = intval($_GET['id']);
    $stmt = $connect->prepare("SELECT * FROM sales_invoices WHERE invoice_id = ?");
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $invoiceData = $result->fetch_assoc();
    } else {
        header('Location: sales_invoice_list.php');
        exit;
    }
}

$pageTitle = $editMode ? 'Edit Sales Invoice' : 'Create Sales Invoice';
?>

<div class="page-wrapper">
    
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary"><?php echo $pageTitle; ?></h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="sales_invoice_list.php">Sales Invoices</a></li>
                <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
            </ol>
        </div>
    </div>
    
    <div class="container-fluid">
        
        <div class="card">
            <div class="card-body">
                
                <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-check-circle"></i> <span id="successText"></span>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div id="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-exclamation-circle"></i> <span id="errorText"></span>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <form id="invoiceForm" method="POST">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="invoice_id" value="<?php echo $invoiceData['invoice_id']; ?>" />
                    <?php endif; ?>
                    
                    <!-- Section 1: Invoice Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-file-text"></i> Invoice Details</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Invoice Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="invoiceNumber" name="invoice_number" 
                                    value="<?php echo $invoiceData['invoice_number'] ?? ''; ?>" readonly />
                                <small class="text-muted">Auto-generated</small>
                            </div>
                            <div class="col-md-3">
                                <label>Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="invoice_date" 
                                    value="<?php echo $invoiceData['invoice_date'] ?? date('Y-m-d'); ?>" required />
                            </div>
                            <div class="col-md-3">
                                <label>Due Date</label>
                                <input type="date" class="form-control" name="due_date" 
                                    value="<?php echo $invoiceData['due_date'] ?? ''; ?>" />
                            </div>
                            <div class="col-md-3">
                                <label>Status</label>
                                <select class="form-control" name="invoice_status">
                                    <option value="DRAFT" <?php echo isset($invoiceData['invoice_status']) && $invoiceData['invoice_status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="SUBMITTED" <?php echo isset($invoiceData['invoice_status']) && $invoiceData['invoice_status'] === 'SUBMITTED' ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="FULFILLED" <?php echo isset($invoiceData['invoice_status']) && $invoiceData['invoice_status'] === 'FULFILLED' ? 'selected' : ''; ?>>Fulfilled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 2: Client Selection -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-user"></i> Client Information</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Select Client <span class="text-danger">*</span></label>
                                <select class="form-control" id="clientSelect" name="client_id" required style="width: 100%;">
                                    <option value="">-- Select Client --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Or <a href="clients_form.php" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fa fa-plus"></i> Add New Client
                                </a></label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 3: Billing & Shipping Addresses (2 Column Layout) -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-map-marker"></i> Billing & Shipping Addresses</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Bill To</label>
                                                        <div class="search-results" style="display: none;"></div>
                                        <em class="text-muted">Select a client above</em>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Ship To</label>
                                <div class="card" style="background-color: #f9f9f9; padding: 15px; min-height: 120px;">
                                    <textarea class="form-control" name="delivery_address" rows="5" 
                                        placeholder="If different from billing address"><?php echo $invoiceData['delivery_address'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 4: Invoice Items -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-list"></i> Invoice Items</h5>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="invoiceItemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 18%;">Medicine Name</th>
                                    <th style="width: 8%;">HSN Code</th>
                                    <th style="width: 10%;">Batch No</th>
                                    <th style="width: 8%;">Avail Qty</th>
                                    <th style="width: 8%;">Qty</th>
                                    <th style="width: 8%;">MRP</th>
                                    <th style="width: 8%;">Rate</th>
                                    <th style="width: 8%;">PTR</th>
                                    <th style="width: 7%;">Discount %</th>
                                    <th style="width: 7%;">GST %</th>
                                    <th style="width: 12%;">Line Total</th>
                                    <th style="width: 4%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr id="row1" class="item-row">
                                    <td>
                                        <div style="position: relative;">
                                            <input type="text" class="form-control form-control-sm product-search" 
                                                placeholder="Search medicine..." data-row="1" autocomplete="off" />
                                            <input type="hidden" class="product-id" name="product_id[]" />
                                            <div class="search-results" style="position: absolute; background: white; border: 1px solid #ddd; 
                                                max-height: 250px; overflow-y: auto; display: none; z-index: 1000; width: 100%; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm hsn-code" readonly data-row="1" />
                                        <input type="hidden" class="hsn-value" name="hsn_code[]" />
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="1">
                                            <option value="">--Select--</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="available-qty" style="display:inline-block; min-width:40px; text-align:center; color:#007bff; font-weight:bold;">-</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm quantity-input text-center" 
                                            name="quantity[]" data-row="1" min="0.01" step="0.01" />
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm mrp-display text-center" readonly data-row="1" />
                                        <input type="hidden" class="mrp-value" name="mrp[]" />
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm rate-input text-center" name="rate[]" data-row="1" min="0" step="0.01" />
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm ptr-display text-center" readonly data-row="1" />
                                        <input type="hidden" class="ptr-value" name="ptr[]" />
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm discount-input text-center" 
                                            name="line_discount[]" data-row="1" value="0" min="0" max="100" step="0.01" />
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm gst-input text-center" 
                                            name="gst_rate[]" data-row="1" value="18" step="0.01" min="0" />
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm total-display text-right font-weight-bold" readonly data-row="1" />
                                        <input type="hidden" class="total-value" name="line_total[]" />
                                        <input type="hidden" class="allocation-plan-input" name="allocation_plan[]" />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-row" data-row="1" title="Remove">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-success btn-sm" id="addRowBtn">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <!-- Section 5: Financial Summary -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-calculator"></i> Financial Summary</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm" style="border: none;">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td align="right">
                                            <input type="text" class="form-control text-right" id="subtotal" readonly />
                                            <input type="hidden" name="subtotal" id="subtotalValue" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Discount (%):</strong></td>
                                        <td align="right">
                                            <input type="number" class="form-control text-right" name="discount_percent" 
                                                id="discountPercent" value="0" step="0.01" min="0" max="100" 
                                                onchange="calculateTotals()" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Discount Amount:</strong></td>
                                        <td align="right">
                                            <input type="text" class="form-control text-right" id="discountAmount" readonly />
                                            <input type="hidden" name="discount_amount" id="discountAmountValue" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>GST Amount:</strong></td>
                                        <td align="right">
                                            <input type="text" class="form-control text-right" id="gstAmount" readonly />
                                            <input type="hidden" name="gst_amount" id="gstAmountValue" />
                                        </td>
                                    </tr>
                                    <tr style="border-top: 2px solid #333;">
                                        <td><strong>Grand Total:</strong></td>
                                        <td align="right">
                                            <input type="text" class="form-control text-right font-weight-bold" 
                                                id="grandTotal" readonly style="font-size: 16px;" />
                                            <input type="hidden" name="grand_total" id="grandTotalValue" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 6: Payment Details -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-credit-card"></i> Payment Details</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Payment Type</label>
                                <select class="form-control" name="payment_type">
                                    <option value="">-- Select --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Card">Credit/Debit Card</option>
                                    <option value="Online">Online Transfer</option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Paid Amount</label>
                                <input type="number" class="form-control" name="paid_amount" value="0" 
                                    step="0.01" min="0" onchange="calculatePayment()" />
                            </div>
                            <div class="col-md-3">
                                <label>Due Amount</label>
                                <input type="text" class="form-control" id="dueAmount" readonly />
                                <input type="hidden" name="due_amount" id="dueAmountValue" />
                            </div>
                            <div class="col-md-3">
                                <label>Payment Status</label>
                                <select class="form-control" name="payment_status">
                                    <option value="UNPAID">Unpaid</option>
                                    <option value="PARTIAL">Partial</option>
                                    <option value="PAID">Paid</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <label>Payment Place</label>
                                <select class="form-control" name="payment_place">
                                    <option value="">-- Select --</option>
                                    <option value="In India">In India</option>
                                    <option value="Out Of India">Out Of India</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="form-group">
                        <hr>
                        <button type="submit" class="btn btn-success btn-flat" id="submitBtn">
                            <i class="fa fa-check"></i> <?php echo $editMode ? 'Update Invoice' : 'Create Invoice'; ?>
                        </button>
                        <button type="button" class="btn btn-info btn-flat" id="previewBtn" <?php echo !$editMode ? 'disabled' : ''; ?>>
                            <i class="fa fa-eye"></i> Preview Invoice
                        </button>
                        <a href="sales_invoice_list.php" class="btn btn-secondary btn-flat">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<!-- Select2 for Client Dropdown -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<script>
let rowCount = 1;
let allClients = [];

$(document).ready(function() {
    // Initialize Select2 for client dropdown
    loadClients();
    
    // Load invoice number on page load
    if ($('#invoiceNumber').val() === '') {
        getNextInvoiceNumber();
    }
    
    // Client selection change
    $('#clientSelect').on('change', function() {
        const clientId = $(this).val();
        if (clientId) {
            const clientData = $('#clientSelect option:selected').data('client');
            if (clientData) {
                const billToText = `${clientData.name}\n${clientData.contact_phone || ''}\n${clientData.email || ''}\n${clientData.billing_address || ''}\n${clientData.city || ''}, ${clientData.state || ''} ${clientData.postal_code || ''}`;
                $('#billToAddress').text(billToText);
                if ($('textarea[name="delivery_address"]').length) $('textarea[name="delivery_address"]').val(clientData.shipping_address || clientData.billing_address || '');
            }
        } else {
            $('#billToAddress').html('<em class="text-muted">Select a client above</em>');
            if ($('textarea[name="delivery_address"]').length) $('textarea[name="delivery_address"]').val('');
        }
    });
    
    // Add row button
    $('#addRowBtn').on('click', function() {
        addInvoiceRow();
    });
    
    // Submit form
    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();
        submitInvoice();
    });
    
    // Product search (live)
    $(document).on('keyup', '.product-search', function() {
        const searchTerm = $(this).val();
        const resultsDiv = $(this).siblings('.search-results');

        if (searchTerm.length < 2) {
            resultsDiv.hide();
            return;
        }

        $.ajax({
            url: 'php_action/searchProductsInvoice.php',
            type: 'GET',
            data: { q: searchTerm },
            dataType: 'json',
            success: function(products) {
                resultsDiv.empty();
                if (!products || products.length === 0) {
                    resultsDiv.html('<div style="padding:10px;color:#999">No products found</div>');
                } else {
                    products.forEach(product => {
                        const item = $(`<div class="product-item" style="padding:8px;cursor:pointer;border-bottom:1px solid #eee;" data-id="${product.id}" data-name="${product.product_name}" data-mrp="${product.expected_mrp}" data-ptr="${product.purchase_rate}" data-hsn="${product.hsn_code}" data-gst="${product.gst_rate}">${product.text}</div>`);
                        resultsDiv.append(item);
                    });
                }
                resultsDiv.show();
            }
        });
    });

    // Select product from search results
    $(document).on('click', '.product-item', function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const mrp = $(this).data('mrp');
        const ptr = $(this).data('ptr');
        const hsn = $(this).data('hsn');
        const gst = $(this).data('gst');

        const row = $(this).closest('tr');
        row.find('.product-search').val(productName);
        row.find('.product-id').val(productId);
        row.find('.hsn-code').val((typeof hsn !== 'undefined' && hsn !== null && hsn !== '') ? hsn : '');
        row.find('.hsn-value').val((typeof hsn !== 'undefined' && hsn !== null && hsn !== '') ? hsn : '');
        row.find('.mrp-display').val((typeof mrp !== 'undefined' && mrp !== null && mrp !== '') ? mrp : 0);
        row.find('.mrp-value').val((typeof mrp !== 'undefined' && mrp !== null && mrp !== '') ? mrp : 0);
        row.find('.ptr-display').val((typeof ptr !== 'undefined' && ptr !== null && ptr !== '') ? ptr : 0);
        row.find('.ptr-value').val((typeof ptr !== 'undefined' && ptr !== null && ptr !== '') ? ptr : 0);
        row.find('.gst-input').val((typeof gst !== 'undefined' && gst !== null && gst !== '') ? gst : 0);

        row.find('.search-results').hide();

        // Populate batch select and available qty
        fetchProductDetails(productId, row);
    });

    // Quantity change - recalculate and allocation
    $(document).on('change input', '.quantity-input', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat($(this).val()) || 0;
        const productId = row.find('.product-id').val();

        // If batch selected, ensure availability
        const selectedBatch = row.find('.batch-select').val();
        if (selectedBatch) {
            const avail = parseFloat(row.find('.batch-select option:selected').data('available')) || 0;
            row.find('.available-qty').text(avail);
            if (qty > avail) {
                // Fetch allocation plan when selected batch insufficient
                fetchBatchAllocation(productId, qty, row);
            } else {
                row.data('allocation-plan', null);
                row.find('.allocation-badge').remove();
                row.find('.allocation-plan-input').val('');
            }
        } else if (productId) {
            // No batch selected: attempt auto allocation
            fetchBatchAllocation(productId, qty, row);
        }

        calculateLineTotalRow(row);
    });

    // Batch change - update available qty and adjust rate/ptr if batch has specific rates
    $(document).on('change', '.batch-select', function() {
        const row = $(this).closest('tr');
        const sel = $(this).find('option:selected');
        const avail = parseFloat(sel.data('available')) || 0;
        const batchMrp = sel.data('mrp');
        const batchPtr = sel.data('ptr');

        row.find('.available-qty').text(avail);
        row.find('.mrp-display').val((typeof batchMrp !== 'undefined' && batchMrp !== null && batchMrp !== '') ? batchMrp : row.find('.mrp-value').val() || 0);
        row.find('.mrp-value').val((typeof batchMrp !== 'undefined' && batchMrp !== null && batchMrp !== '') ? batchMrp : row.find('.mrp-value').val() || 0);
        row.find('.ptr-display').val((typeof batchPtr !== 'undefined' && batchPtr !== null && batchPtr !== '') ? batchPtr : row.find('.ptr-value').val() || 0);
        row.find('.ptr-value').val((typeof batchPtr !== 'undefined' && batchPtr !== null && batchPtr !== '') ? batchPtr : row.find('.ptr-value').val() || 0);

        // Recalculate
        calculateLineTotalRow(row);
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });
});

function loadClients() {
    $.ajax({
        url: 'php_action/fetchClients.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                allClients = response.data;
                const select = $('#clientSelect');
                select.empty();
                select.append('<option value="">-- Select Client --</option>');
                response.data.forEach(client => {
                    select.append(`<option value="${client.client_id}">${client.name} (${client.client_code})</option>`);
                });
                select.select2({
                    placeholder: 'Search and select client...',
                    allowClear: true
                });
            }
        }
    });
}

function getNextInvoiceNumber() {
    $.ajax({
        url: 'php_action/getInvoiceNumber.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#invoiceNumber').val(response.invoice_number);
            }
        }
    });
}

function addInvoiceRow() {
    rowCount++;
    const row = `<tr id="row${rowCount}" class="item-row">
        <td>
            <div style="position: relative;">
                <input type="text" class="form-control form-control-sm product-search" 
                    placeholder="Search medicine..." data-row="${rowCount}" autocomplete="off" />
                <input type="hidden" class="product-id" name="product_id[]" />
                <div class="search-results" style="position: absolute; background: white; border: 1px solid #ddd; 
                    max-height: 250px; overflow-y: auto; display: none; z-index: 1000; width: 100%; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm hsn-code" readonly data-row="${rowCount}" />
            <input type="hidden" class="hsn-value" name="hsn_code[]" />
        </td>
        <td>
            <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="${rowCount}">
                <option value="">--Select--</option>
            </select>
        </td>
        <td>
            <span class="available-qty" style="display:inline-block; min-width:40px; text-align:center; color:#007bff; font-weight:bold;">-</span>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm quantity-input text-center" 
                name="quantity[]" data-row="${rowCount}" min="0.01" step="0.01" />
        </td>
        <td>
            <input type="text" class="form-control form-control-sm mrp-display text-center" readonly data-row="${rowCount}" />
            <input type="hidden" class="mrp-value" name="mrp[]" />
        </td>
        <td>
            <input type="number" class="form-control form-control-sm rate-input text-center" name="rate[]" data-row="${rowCount}" min="0" step="0.01" />
        </td>
        <td>
            <input type="text" class="form-control form-control-sm ptr-display text-center" readonly data-row="${rowCount}" />
            <input type="hidden" class="ptr-value" name="ptr[]" />
        </td>
        <td>
            <input type="number" class="form-control form-control-sm discount-input text-center" 
                name="line_discount[]" data-row="${rowCount}" value="0" min="0" max="100" step="0.01" />
        </td>
        <td>
            <input type="number" class="form-control form-control-sm gst-input text-center" 
                name="gst_rate[]" data-row="${rowCount}" value="18" step="0.01" min="0" />
        </td>
        <td>
            <input type="text" class="form-control form-control-sm total-display text-right font-weight-bold" readonly data-row="${rowCount}" />
            <input type="hidden" class="total-value" name="line_total[]" />
            <input type="hidden" class="allocation-plan-input" name="allocation_plan[]" />
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger remove-row" data-row="${rowCount}" title="Remove">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>`;
    
    $('#itemsBody').append(row);
}

function fetchProductDetails(productId, row) {
    $.ajax({
        url: 'php_action/fetchProductInvoice.php',
        type: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const product = response.data.product;
                const batches = response.data.batches;

                // Set HSN, MRP and PTR and GST
                row.find('.hsn-code').val(product.hsn_code ?? '');
                row.find('.hsn-value').val(product.hsn_code ?? '');
                const mrp = (product.expected_mrp ?? product.mrp ?? 0) || 0;
                row.find('.mrp-display').val(mrp);
                row.find('.mrp-value').val(mrp);
                row.find('.rate-input').val(mrp); // Default selling rate to MRP, user can edit
                row.find('.ptr-display').val((product.purchase_rate ?? product.ptr ?? 0) || 0);
                row.find('.ptr-value').val((product.purchase_rate ?? product.ptr ?? 0) || 0);
                row.find('.gst-input').val(product.gst_rate ?? 0);

                // Populate batches
                const batchSelect = row.find('.batch-select');
                batchSelect.empty();
                batchSelect.append('<option value="">--Select--</option>');

                batches.forEach(batch => {
                    const expiryDate = new Date(batch.expiry_date).toLocaleDateString();
                    // include batch-specific mrp/ptr if present
                    batchSelect.append(`<option value="${batch.batch_id}" data-available="${batch.available_quantity}" data-mrp="${batch.mrp}" data-ptr="${batch.purchase_rate}">
                        ${batch.batch_number} (Exp: ${expiryDate}, Qty: ${batch.available_quantity})
                    </option>`);
                });

                // Update available qty display
                if (batches.length > 0) {
                    row.find('.available-qty').text(batches[0].available_quantity);
                } else {
                    row.find('.available-qty').text('-');
                }
            }
        }
    });
}

function calculateLineTotalRow(row) {
    const qty = parseFloat(row.find('.quantity-input').val()) || 0;
    const rate = parseFloat(row.find('.rate-input').val()) || 0;
    const discount = parseFloat(row.find('.discount-input').val()) || 0;
    const gst = parseFloat(row.find('.gst-input').val()) || 0;

    const amount = rate * qty;
    const discountAmount = amount * (discount / 100);
    const taxable = amount - discountAmount;
    const tax = taxable * (gst / 100);
    const lineTotal = taxable + tax;

    row.find('.total-display').val(lineTotal.toFixed(2));
    row.find('.total-value').val(lineTotal.toFixed(2));

    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0, totalDiscount = 0, totalTax = 0;

    $('#itemsBody tr.item-row').each(function() {
        const row = $(this);
        const rate = parseFloat(row.find('.rate-input').val()) || 0;
        const qty = parseFloat(row.find('.quantity-input').val()) || 0;
        const discount = parseFloat(row.find('.discount-input').val()) || 0;
        const gst = parseFloat(row.find('.gst-input').val()) || 0;

        const lineAmount = rate * qty;
        const discountAmount = lineAmount * (discount / 100);
        const taxable = lineAmount - discountAmount;
        const taxAmount = taxable * (gst / 100);

        subtotal += lineAmount;
        totalDiscount += discountAmount;
        totalTax += taxAmount;
    });

    const grandTotal = subtotal - totalDiscount + totalTax;

    // Update legacy fields if present
    if ($('#subtotal').length) $('#subtotal').val(subtotal.toFixed(2));
    if ($('#subtotalValue').length) $('#subtotalValue').val(subtotal.toFixed(2));
    if ($('#discountAmount').length) $('#discountAmount').val(totalDiscount.toFixed(2));
    if ($('#discountAmountValue').length) $('#discountAmountValue').val(totalDiscount.toFixed(2));
    if ($('#gstAmount').length) $('#gstAmount').val(totalTax.toFixed(2));
    if ($('#gstAmountValue').length) $('#gstAmountValue').val(totalTax.toFixed(2));
    if ($('#grandTotal').length) $('#grandTotal').val(grandTotal.toFixed(2));
    if ($('#grandTotalValue').length) $('#grandTotalValue').val(grandTotal.toFixed(2));

    calculatePayment();
}

// Fetch Batch Allocation Plan (uses BatchQuantityHandler via php_action)
function fetchBatchAllocation(productId, qty, row) {
    if (!productId || qty <= 0) return;

    $.ajax({
        url: 'php_action/getBatchAllocationPlan.php',
        method: 'POST',
        data: { product_id: productId, quantity: qty },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const summary = response.data.summary;
                // remove existing badge
                row.find('.allocation-badge').remove();

                let badgeHtml = '';
                if (summary.is_complete) {
                    badgeHtml = `<span class="badge badge-success allocation-badge" style="margin-left:8px">✓ ${summary.batch_count} batch(es) allocated</span>`;
                } else {
                    const shortfall = summary.required_quantity - summary.total_allocated;
                    badgeHtml = `<span class="badge badge-warning allocation-badge" style="margin-left:8px">⚠ ${shortfall} units short</span>`;
                }

                row.find('.available-qty').after(badgeHtml);

                // store allocation plan for backend use
                row.data('allocation-plan', response.data.allocation_plan || null);
                try {
                    row.find('.allocation-plan-input').val(JSON.stringify(response.data.allocation_plan || null));
                } catch (e) {
                    row.find('.allocation-plan-input').val('');
                }

                // prepend warnings if any
                if (response.warnings && response.warnings.length) {
                    let warningsHtml = '';
                    response.warnings.forEach(w => { warningsHtml += `<div class="text-warning small">${w.message}</div>`; });
                    row.find('.available-qty').before(warningsHtml);
                }
            } else {
                row.find('.allocation-badge').remove();
            }
        }
    });
}

function calculatePayment() {
    const grandTotal = parseFloat($('#grandTotalValue').val()) || 0;
    // Get paid amount from form - need to query it
    const paidAmount = parseFloat($('input[name="paid_amount"]').val()) || 0;
    const dueAmount = grandTotal - paidAmount;
    
    $('#dueAmount').val(dueAmount.toFixed(2));
    $('#dueAmountValue').val(dueAmount.toFixed(2));
}

function submitInvoice() {
    const clientId = $('#clientSelect').val();
    if (!clientId) {
        alert('Please select a client');
        return;
    }
    
    const itemCount = $('.item-row').length;
    if (itemCount === 0) {
        alert('Please add at least one item');
        return;
    }
    
    const formData = $('#invoiceForm').serialize();
    const actionUrl = <?php echo $editMode ? "'php_action/updateSalesInvoice.php'" : "'php_action/createSalesInvoice.php'"; ?>;
    
    $.ajax({
        url: actionUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#successText').text(response.message);
                $('#successMessage').show();
                $('#errorMessage').hide();
                
                setTimeout(function() {
                    window.location.href = 'sales_invoice_list.php';
                }, 1500);
            } else {
                $('#errorText').text(response.message);
                $('#errorMessage').show();
                $('#successMessage').hide();
            }
        },
        error: function(xhr, status, error) {
            $('#errorText').text('Error: ' + error);
            $('#errorMessage').show();
            $('#successMessage').hide();
        }
    });
}
</script>

<style>
.no-print {
    display: table-cell;
}

@media print {
    .no-print {
        display: none !important;
    }
    .sidebar, .header, .submitButtonFooter {
        display: none !important;
    }
}
</style>
