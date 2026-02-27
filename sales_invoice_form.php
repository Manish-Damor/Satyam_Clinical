<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';

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
    <!-- ============ STORE HEADER ============ -->
    <div class="row page-titles">
        <div class="col-md-12 text-center border-bottom pb-3 mb-3">
            <h2 class="font-weight-bold text-dark mb-1">TROIKAA LIFE CARE</h2>
            <p class="text-muted mb-1">HOUSE NO.3196/9, SHOP NO 12, HARIOM COMPLEX, SADAK PALIYA, DUNGRA, PIN 396195</p>
            <p class="text-muted mb-0"><strong>Phone:</strong> 9925455205 | <strong>D.L. No.:</strong> 20B-193927, 21B-193928</p>
        </div>
    </div>

    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary"><i class="fa fa-file-invoice-dollar"></i> <?php echo $pageTitle; ?></h3>
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
                <!-- Success/Error Messages -->
                <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-check-circle"></i> <span id="successText"></span>
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
                </div>

                <div id="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-exclamation-circle"></i> <span id="errorText"></span>
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
                </div>

                <form id="invoiceForm" method="POST" class="invoice-form">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="invoice_id" value="<?php echo $invoiceData['invoice_id']; ?>" />
                    <?php endif; ?>

                    <!-- ============ SECTION 1: INVOICE HEADER ============ -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa fa-document"></i> Invoice Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Invoice Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="invoiceNumber" name="invoice_number" 
                                        value="<?php echo $invoiceData['invoice_number'] ?? ''; ?>" readonly />
                                    <small class="text-muted">Auto-generated</small>
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-lg" name="invoice_date" 
                                        value="<?php echo $invoiceData['invoice_date'] ?? date('Y-m-d'); ?>" required />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Payment Terms (Days) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-lg" id="paymentTerms" name="payment_terms" 
                                        value="<?php echo $invoiceData['payment_terms'] ?? 0; ?>" min="0" required />
                                    <small class="text-muted">Auto-calculates due date</small>
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Due Date</label>
                                    <input type="date" class="form-control form-control-lg" id="dueDate" name="due_date" 
                                        value="<?php echo $invoiceData['due_date'] ?? ''; ?>" readonly />
                                    <small class="text-muted">Auto-calculated</small>
                                </div>
                            </div>

                            <!-- Invoice Status REMOVED: using payment_status only -->
                        </div>
                    </div>

                    <!-- ============ SECTION 2: CLIENT SELECTION ============ -->
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fa fa-user-tie"></i> Client Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label class="font-weight-bold">Select Client <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-lg" id="clientSelect" name="client_id" required style="width: 100%;">
                                        <option value="">-- Select Client --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="font-weight-bold">&nbsp;</label>
                                    <a href="clients_form.php" target="_blank" class="btn btn-sm btn-success btn-block">
                                        <i class="fa fa-plus"></i> Add New Client
                                    </a>
                                </div>
                            </div>

                            <!-- Client Details Panel -->
                            <div id="clientDetailsPanel" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body p-3">
                                                <h6 class="card-title font-weight-bold">Billing Address</h6>
                                                <div id="billingAddr" class="text-sm">
                                                    <em class="text-muted">Select a client above</em>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body p-3">
                                                <h6 class="card-title font-weight-bold">Business Type & Credit Info</h6>
                                                <table class="table table-sm mb-0">
                                                    <tr>
                                                        <td class="text-nowrap"><strong>Type:</strong></td>
                                                        <td><span id="clientType" class="badge badge-info">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>GSTIN:</strong></td>
                                                        <td><span id="clientGST" class="font-monospace">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>PAN:</strong></td>
                                                        <td><span id="clientPAN" class="font-monospace">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>D.L. No.:</strong></td>
                                                        <td><span id="clientDL" class="font-monospace">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Credit Limit:</strong></td>
                                                        <td><strong id="creditLimit">₹0</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Outstanding:</strong></td>
                                                        <td id="outstanding">₹0</td>
                                                    </tr>
                                                    <tr style="background-color: #e8f5e9;">
                                                        <td><strong>Available:</strong></td>
                                                        <td><strong id="availableCredit" class="text-success">₹0</strong></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label class="font-weight-bold">Delivery Address (if different)</label>
                                        <textarea class="form-control" name="delivery_address" rows="3" 
                                            placeholder="Leave blank to use billing address"><?php echo $invoiceData['delivery_address'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ SECTION 3: INVOICE ITEMS ============ -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fa fa-list"></i> Medicine Items</h5>
                            <button type="button" class="btn btn-sm btn-light" id="addRowBtn"><i class="fa fa-plus"></i> Add Item</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm invoice-items-table" id="invoiceItemsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="width: 18%;">Medicine Name</th>
                                            <th style="width: 7%;">HSN</th>
                                            <th style="width: 12%;">Batch (Expiry)</th>
                                            <th style="width: 8%;">Avail</th>
                                            <th style="width: 8%;">Qty</th>
                                            <th style="width: 8%;">MRP</th>
                                            <th style="width: 8%; background-color: #fff3e0; color: #000;"><strong>PTR</strong></th>
                                            <th style="width: 8%;">Rate</th>
                                            <th style="width: 7%;">Disc %</th>
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
                                                        placeholder="Type medicine name..." data-row="1" autocomplete="off" />
                                                    <input type="hidden" class="product-id" name="product_id[]" />
                                                    <div class="search-results" style="position: absolute; background: white; border: 1px solid #999; max-height: 300px; overflow-y: auto; display: none; width: 100%; z-index: 1000; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.15); top: 100%; left: 0;"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm hsn-code text-center" readonly data-row="1" />
                                                <input type="hidden" class="hsn-value" name="hsn_code[]" />
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="1">
                                                    <option value="">--Select--</option>
                                                </select>
                                            </td>
                                            <td>
                                                <span class="available-qty text-center text-info font-weight-bold" style="display:block;">-</span>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm quantity-input text-center" 
                                                    name="quantity[]" data-row="1" min="0.01" step="0.01" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm mrp-display text-center" readonly data-row="1" />
                                                <input type="hidden" class="mrp-value" name="mrp[]" />
                                            </td>
                                            <td style="background-color: #fff3e0;">
                                                <input type="text" class="form-control form-control-sm ptr-display text-center" readonly data-row="1" style="background-color: #ffe082; font-weight: bold; color: #000;" />
                                                <input type="hidden" class="ptr-value" name="ptr[]" />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm rate-input text-center font-weight-bold" 
                                                    name="rate[]" data-row="1" min="0" step="0.01" placeholder="0.00" />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm discount-input text-center" 
                                                    name="line_discount[]" data-row="1" value="0" min="0" max="100" step="0.01" />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm gst-input text-center" 
                                                    name="gst_rate[]" data-row="1" value="18" placeholder="0" step="0.01" min="0" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm total-display text-right font-weight-bold" readonly data-row="1" />
                                                <input type="hidden" class="total-value" name="line_total[]" />
                                                <input type="hidden" class="allocation-plan-input" name="allocation_plan[]" />
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-row" data-row="1" title="Remove Row">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ============ SECTION 4: FINANCIAL SUMMARY ============ -->
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fa fa-calculator"></i> Financial Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr>
                                                <td><strong>Subtotal:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right" id="subtotal" readonly style="border: none; padding: 0.25rem; font-weight: bold;" />
                                                    <input type="hidden" name="subtotal" id="subtotalValue" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Invoice Discount (%):</strong></td>
                                                <td class="text-right">
                                                    <input type="number" class="form-control text-right" name="discount_percent" 
                                                        id="discountPercent" value="0" step="0.01" min="0" max="100" style="padding: 0.25rem;" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Discount Amount:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right" id="discountAmount" readonly style="border: none; padding: 0.25rem; font-weight: bold;" />
                                                    <input type="hidden" name="discount_amount" id="discountAmountValue" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>GST Amount:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right" id="gstAmount" readonly style="border: none; padding: 0.25rem; font-weight: bold;" />
                                                    <input type="hidden" name="gst_amount" id="gstAmountValue" />
                                                </td>
                                            </tr>
                                            <tr style="border-top: 3px solid #333; background-color: #fff3e0;">
                                                <td><strong class="text-lg">Grand Total:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right font-weight-bold" 
                                                        id="grandTotal" readonly style="border: none; padding: 0.5rem; font-size: 18px;" />
                                                    <input type="hidden" name="grand_total" id="grandTotalValue" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ SECTION 5: PAYMENT DETAILS ============ -->
                    <div class="card border-danger mb-3">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fa fa-money-bill"></i> Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <!-- Credit Warning Alert -->
                            <div id="creditWarningAlert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display: none;">
                                <strong><i class="fa fa-exclamation-triangle"></i> Credit Limit Warning</strong><br>
                                <span id="creditWarningText"></span>
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <label class="font-weight-bold">Payment Type <span class="text-danger">*</span></label>
                                    <select class="form-control" id="paymentTypeSelect" name="payment_type" required>
                                        <option value="">-- Select --</option>
                                        <option value="Cash">💵 Cash</option>
                                        <option value="Credit">📋 Credit</option>
                                    </select>
                                </div>

                                <div class="col-md-3" id="paymentMethodColumn" style="display: none;">
                                    <label class="font-weight-bold">Payment Method</label>
                                    <select class="form-control" name="payment_method" id="paymentMethodSelect">
                                        <option value="">-- Select Method --</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Card">Card</option>
                                        <option value="NEFT">NEFT</option>
                                        <option value="UPI">UPI</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Paid Amount</label>
                                    <input type="number" class="form-control" name="paid_amount" id="paidAmount" value="0" 
                                        step="0.01" min="0" />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Due Amount</label>
                                    <input type="text" class="form-control" id="dueAmount" readonly style="font-weight: bold; background-color: #f5f5f5;" />
                                    <input type="hidden" name="due_amount" id="dueAmountValue" />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Payment Status</label>
                                    <input type="text" class="form-control" id="paymentStatus" readonly style="font-weight: bold; background-color: #e8f5e9;" />
                                    <input type="hidden" name="payment_status" id="paymentStatusValue" />
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="font-weight-bold">Payment Notes</label>
                                    <textarea class="form-control" name="payment_notes" rows="2" 
                                        placeholder="Cheque number, reference details, etc."><?php echo $invoiceData['payment_notes'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ ACTION BUTTONS ============ -->
                    <div class="card">
                        <div class="card-body">
                            <button type="button" class="btn btn-light btn-lg border mr-2" id="resetBtn" title="Clear all fields">
                                <i class="fa fa-redo"></i> Reset
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg mr-2" id="saveDraftBtn" title="Save as draft for later">
                                <i class="fa fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" class="btn btn-success btn-lg mr-2" id="submitBtn">
                                <i class="fa fa-check-circle"></i> <?php echo $editMode ? 'Update Invoice' : 'Create Invoice'; ?>
                            </button>
                            <button type="button" class="btn btn-info btn-lg mr-2" id="previewBtn" title="Preview this invoice">
                                <i class="fa fa-eye"></i> Preview
                            </button>
                            <a href="sales_invoice_list.php" class="btn btn-secondary btn-lg">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<!-- Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<style>
    .invoice-form { font-size: 0.95rem; }
    .invoice-items-table thead th { background-color: #2c3e50; color: white; font-weight: bold; padding: 10px 5px; }
    .form-control-lg { height: 38px; padding: 0.5rem 0.75rem; font-size: 0.95rem; }
    .card { margin-bottom: 20px; }
    .card-header { font-weight: bold; }
    #invoiceItemsTable tbody tr:hover { background-color: #f9f9f9; }
    .ptr-display { background-color: #ffe082 !important; font-weight: bold; }

    /* dropdown visibility fix */
    .table-responsive { overflow: visible !important; }
    .product-search { width: 100%; }
    .search-results {
        z-index: 9999 !important;
        min-width: 100%;
    }
    .fefo-explain {
        margin-top: 4px;
        font-size: 11px;
        color: #64748b;
        line-height: 1.3;
        display: block;
    }
    
    @media (max-width: 768px) {
        .table-responsive { font-size: 0.85rem; }
        .form-control-sm { height: 28px; }
    }

    @media print {
        .no-print, .btn, .form-control, .card-header, #addRowBtn, .remove-row { display: none !important; }
        .sidebar, .header, .page-titles, .navbar { display: none !important; }
        .container-fluid { max-width: 100%; padding: 0; }
        .card { border: none; page-break-inside: avoid; }
        
        /* Hide internal info on print */
        .ptr-display, [style*="background-color: #ffe082"], 
        #billingAddr, #clientDetailsPanel { display: none !important; }
        
        /* FORCE BLACK TEXT ONLY */
        * { color: #000 !important; }
        body { font-size: 12px; line-height: 1.4; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; color: #000; }
        th { background-color: #f0f0f0 !important; font-weight: bold; }
        
        /* Remove backgrounds */
        .form-control, input, textarea, select { background-color: white !important; color: #000 !important; border: none !important; }
        
        /* Print-specific text color for all elements */
        h1, h2, h3, h4, h5, h6, p, span, a, strong, b { color: #000 !important; }
        .badge { color: #000 !important; background-color: #f0f0f0 !important; }
        .alert { color: #000 !important; background-color: white !important; }
        
        /* Page break handling */
        .page-break { page-break-after: always; }
    }
</style>

<script>
let rowCount = 1;
let allClients = [];

$(document).ready(function() {
    loadClients();
    
    if ($('#invoiceNumber').val() === '') {
        getNextInvoiceNumber();
    }

    // Calculate due date when payment terms change
    $('#paymentTerms').on('change input', function() {
        const invoiceDate = $('input[name="invoice_date"]').val();
        const terms = parseInt($(this).val()) || 0;
        if (invoiceDate && terms > 0) {
            const dueDate = new Date(invoiceDate);
            dueDate.setDate(dueDate.getDate() + terms);
            $('#dueDate').val(dueDate.toISOString().split('T')[0]);
        }
    });

    // Calculate due date when invoice date changes
    $('input[name="invoice_date"]').on('change', function() {
        const terms = parseInt($('#paymentTerms').val()) || 0;
        if (terms > 0) {
            const dueDate = new Date($(this).val());
            dueDate.setDate(dueDate.getDate() + terms);
            $('#dueDate').val(dueDate.toISOString().split('T')[0]);
        }
    });

    // Discount change
    $('#discountPercent').on('change input', function() {
        calculateTotals();
    });

    // Payment terms and invoice date change - auto-calculate due date
    $('#paymentTerms, input[name="invoice_date"]').on('change input', function() {
        const invoiceDate = $('input[name="invoice_date"]').val();
        const terms = parseInt($('#paymentTerms').val()) || 0;
        if (invoiceDate && terms > 0) {
            const dueDate = new Date(invoiceDate);
            dueDate.setDate(dueDate.getDate() + terms);
            $('#dueDate').val(dueDate.toISOString().split('T')[0]);
        }
    });

    // Client selection
    $('#clientSelect').on('change', function() {
        const clientId = $(this).val();
        if (clientId) {
            const optSelected = $(this).find('option:selected');
            const clientData = {
                name: optSelected.data('name'),
                contact_phone: optSelected.data('contact'),
                email: optSelected.data('email'),
                billing_address: optSelected.data('billing'),
                shipping_address: optSelected.data('shipping'),
                city: optSelected.data('city'),
                state: optSelected.data('state'),
                postal_code: optSelected.data('postal'),
                gstin: optSelected.data('gstin'),
                pan: optSelected.data('pan'),
                drug_licence_no: optSelected.data('dl'),
                business_type: optSelected.data('business'),
                credit_limit: parseFloat(optSelected.data('credit')) || 0,
                outstanding_balance: parseFloat(optSelected.data('outstanding')) || 0
            };
            
            displayClientInfo(clientData);
            checkCreditLimit(); // Check credit after client selected
        } else {
            $('#clientDetailsPanel').hide();
        }
    });

    // Payment type change
    $('#paymentTypeSelect').on('change', function() {
        const paymentType = $(this).val();
        
        // Show/hide payment method column
        if (paymentType === 'Cash') {
            $('#paymentMethodColumn').show();
        } else {
            $('#paymentMethodColumn').hide();
        }
        
        // Check credit limit when credit is selected
        if (paymentType === 'Credit') {
            checkCreditLimit();
        } else {
            $('#creditWarningAlert').hide();
        }
        
        calculateTotals();
    });

    // Payment amount change - auto-calculate payment status
    $('#paidAmount').on('change input', function() {
        calculatePayment();
    });

    // Add row button
    $('#addRowBtn').on('click', function() {
        addInvoiceRow();
    });

    // Reset button
    $('#resetBtn').on('click', function() {
        if (confirm('Are you sure? This will clear all data.')) {
            $('#invoiceForm')[0].reset();
            $('#itemsBody').html(getBlankRowHTML(1));
            rowCount = 1;
            $('#clientDetailsPanel').hide();
            calculateTotals();
        }
    });

    // Save as draft
    $('#saveDraftBtn').on('click', function() {
        $('input[name="invoice_status"]').val('DRAFT');
        $('#invoiceForm').submit();
    });

    // Submit form
    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();
        submitInvoice();
    });

    // Preview button
    $('#previewBtn').on('click', function() {
        window.print();
    });

    // Product search with dropdown
    $(document).on('input', '.product-search', function() {
        const searchTerm = $(this).val().trim();
        const searchInput = $(this);
        const parentDiv = searchInput.closest('div');
        const resultsDiv = parentDiv.find('.search-results');

        console.log('Search triggered:', searchTerm);

        if (searchTerm.length < 2) {
            resultsDiv.empty().hide();
            return;
        }

        // Show loading state
        resultsDiv.html('<div style="padding:10px;color:#666;background:#fff;">Searching...</div>').show();

        $.ajax({
            url: 'php_action/searchProductsInvoice.php',
            type: 'GET',
            data: { q: searchTerm },
            dataType: 'json',
            timeout: 5000,
            success: function(products) {
                console.log('Products received:', products);
                resultsDiv.empty();
                
                if (!products || products.length === 0) {
                    resultsDiv.html('<div style="padding:10px;color:#999;background:#fff;">No products found</div>');
                } else {
                    const html = products.map(product => `
                        <div class="product-item" style="padding:10px;cursor:pointer;border-bottom:1px solid #eee;background:#fff;hover:background:#f0f0f0;" 
                            data-id="${product.id}" data-name="${product.product_name}" data-hsn="${product.hsn_code}" data-gst="${product.gst_rate}">
                            <strong>${product.product_name}</strong> ${product.content ? '(' + product.content + ')' : ''}<br>
                            <small class="text-muted">HSN: ${product.hsn_code || 'N/A'} | GST: ${product.gst_rate}% | MRP: ₹${product.expected_mrp || '0'}</small>
                        </div>
                    `).join('');
                    resultsDiv.html(html);
                    console.log('Results shown:', products.length, 'items');
                }
                resultsDiv.show();
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                resultsDiv.html('<div style="padding:10px;color:#d9534f;background:#fff;">Error: ' + error + '</div>').show();
            }
        });
    });

    // Select product from dropdown
    $(document).on('click', '.product-item', function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const hsn = $(this).data('hsn');
        const gst = $(this).data('gst');

        const row = $(this).closest('tr');
        const searchInput = row.find('.product-search');
        
        searchInput.val(productName);
        row.find('.product-id').val(productId);
        row.find('.hsn-code').val(hsn || '');
        row.find('.hsn-value').val(hsn || '');
        row.find('.gst-input').val(gst || 0);
        
        // Hide dropdown
        const parentDiv = searchInput.closest('div');
        parentDiv.find('.search-results').hide();

        console.log('Product selected:', productId, productName);
        
        // Fetch product details (batches, etc)
        fetchProductDetails(productId, row);
    });

    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.product-search, .search-results, .product-item').length) {
            $('.search-results').hide();
        }
    });

    // Quantity change - auto-allocate if exceeds batch quantity
    $(document).on('change input', '.quantity-input', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat($(this).val()) || 0;
        const productId = row.find('.product-id').val();
        const batchId = row.find('.batch-select').val();
        const availableQty = parseFloat(row.find('.available-qty').text()) || 0;

        console.log('Quantity entered:', qty, 'Available:', availableQty, 'Product:', productId);
    renderFefoExplain(row);

        // If quantity exceeds available batch quantity, trigger multi-batch allocation
        if (productId && batchId && qty > availableQty && availableQty > 0) {
            console.log('Triggering multi-batch allocation');
            
            $.ajax({
                url: 'php_action/getBatchAllocationPlan.php',
                type: 'POST',
                data: { product_id: productId, quantity: qty },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.allocation_plan) {
                        console.log('Allocation plan received:', response.data.allocation_plan);
                        
                        // Store allocation plan in hidden field
                        row.find('.allocation-plan-input').val(JSON.stringify(response.data.allocation_plan));
                        
                        // Fill batches from allocation plan starting from current row
                        const plan = response.data.allocation_plan;
                        let currentRow = row;
                        
                        plan.forEach((allocation, index) => {
                            if (index === 0) {
                                // Update first row with first batch from plan
                                currentRow.find('.batch-select').val(allocation.batch_id).change();
                                currentRow.find('.quantity-input').val(allocation.allocated_quantity);
                                currentRow.find('.rate-input').data('from-allocation', true);
                                // store plan only once (first row)
                                currentRow.find('.allocation-plan-input').val(JSON.stringify(plan));
                            } else {
                                // Add new rows for additional batches
                                addInvoiceRow();
                                const newRow = $('#itemsBody tr:last');
                                
                                // Copy product details from current row
                                newRow.find('.product-search').val(row.find('.product-search').val());
                                newRow.find('.product-id').val(productId);
                                newRow.find('.hsn-code').val(row.find('.hsn-code').val());
                                newRow.find('.gst-input').val(row.find('.gst-input').val());
                                
                                // Fetch batches for this product in the new row (populates dropdown)
                                fetchProductDetails(productId, newRow);
                                
                                // After batches are loaded, set the batch and quantity from allocation plan
                                setTimeout(function() {
                                    newRow.find('.batch-select').val(allocation.batch_id).change();
                                    newRow.find('.quantity-input').val(allocation.allocated_quantity);
                                    newRow.find('.rate-input').data('from-allocation', true);
                                    // do NOT copy allocation_plan into these extra rows
                                }, 100);
                            }
                        });
                        
                        // Show warning if insufficient
                        if (!response.canFulfill) {
                            let warningMsg = 'Stock Allocation Warning:\\n';
                            response.warnings.forEach(w => {
                                warningMsg += '• ' + w.message + '\\n';
                            });
                            alert(warningMsg);
                        }

                        renderFefoExplainFromPlan(row, response.data.allocation_plan, qty);
                    }
                    
                    calculateLineTotalRow(row);
                },
                error: function(e) {
                    console.log('Allocation plan error:', e);
                    calculateLineTotalRow(row);
                }
            });
        } else {
            calculateLineTotalRow(row);
        }
    });

    // Batch change
    $(document).on('change', '.batch-select', function() {
        const row = $(this).closest('tr');
        const sel = $(this).find('option:selected');
        const avail = parseFloat(sel.data('available')) || 0;
        const batchMrp = sel.data('mrp');
        const batchPtr = sel.data('ptr');
        const expiry = sel.data('expiry');
        const batchId = $(this).val();

        row.find('.available-qty').text(avail.toFixed(2));
        row.find('.mrp-display').val(batchMrp ? parseFloat(batchMrp).toFixed(2) : '0.00');
        row.find('.mrp-value').val(batchMrp || 0);
        row.find('.ptr-display').val(batchPtr ? parseFloat(batchPtr).toFixed(2) : '0.00');
        row.find('.ptr-value').val(batchPtr || 0);

        // Initialize line total with MRP as default rate if rate is empty
        if (!row.find('.rate-input').val()) {
            row.find('.rate-input').val(batchMrp || 0);
        }

        // Reset allocation plan when batch changes (unless auto-filled from allocation)
        if (!row.find('.rate-input').data('from-allocation')) {
            row.find('.allocation-plan-input').val('');
        }

        renderFefoExplain(row);

        calculateLineTotalRow(row);
    });

    // Rate change
    $(document).on('change input', '.rate-input', function() {
        const row = $(this).closest('tr');
        calculateLineTotalRow(row);
    });

    // Discount/GST change
    $(document).on('change input', '.discount-input, .gst-input', function() {
        const row = $(this).closest('tr');
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
                    select.append(`
                        <option value="${client.client_id}" 
                            data-name="${client.name}"
                            data-contact="${client.contact_phone || ''}"
                            data-email="${client.email || ''}"
                            data-billing="${client.billing_address || ''}"
                            data-shipping="${client.shipping_address || ''}"
                            data-city="${client.city || ''}"
                            data-state="${client.state || ''}"
                            data-postal="${client.postal_code || ''}"
                            data-gstin="${client.gstin || ''}"
                            data-pan="${client.pan || ''}"
                            data-dl="${client.drug_licence_no || ''}"
                            data-business="${client.business_type || ''}"
                            data-credit="${client.credit_limit || 0}"
                            data-outstanding="${client.outstanding_balance || 0}">
                            ${client.name} (${client.client_code})
                        </option>
                    `);
                });
                select.select2({ width: '100%', allowClear: false });
            }
        }
    });
}

function displayClientInfo(client) {
    const billingHtml = `
        <strong>${client.name}</strong><br>
        ${client.contact_phone || ''}<br>
        ${client.email || ''}<br>
        ${client.billing_address || ''}<br>
        ${client.city || ''}, ${client.state || ''} ${client.postal_code || ''}
    `;
    $('#billingAddr').html(billingHtml);

    const creditLimit = parseFloat(client.credit_limit) || 0;
    const outstanding = parseFloat(client.outstanding_balance) || 0;
    const available = creditLimit - outstanding;

    $('#clientType').text(client.business_type || 'N/A').removeClass().addClass(`badge badge-${client.business_type === 'Wholesale' ? 'success' : 'info'}`);
    $('#clientGST').text(client.gstin || 'N/A');
    $('#clientPAN').text(client.pan || 'N/A');
    $('#clientDL').text(client.drug_licence_no || 'N/A');
    $('#creditLimit').text('₹' + creditLimit.toFixed(2));
    $('#outstanding').text('₹' + outstanding.toFixed(2));
    $('#availableCredit').text('₹' + available.toFixed(2)).removeClass().addClass(available >= 0 ? 'text-success' : 'text-danger').addClass('font-weight-bold');

    $('#clientDetailsPanel').show();
}

function checkCreditLimit() {
    const paymentType = $('#paymentTypeSelect').val();
    const grandTotal = parseFloat($('#grandTotal').val()) || 0;
    
    // Only check if payment_type is Credit
    if (paymentType !== 'Credit') {
        $('#creditWarningAlert').hide();
        return;
    }
    
    // Get client data from currently selected option
    const clientId = $('#clientSelect').val();
    if (!clientId) {
        $('#creditWarningAlert').hide();
        return;
    }
    
    const optSelected = $('#clientSelect').find('option:selected');
    const creditLimit = parseFloat(optSelected.data('credit')) || 0;
    const outstanding = parseFloat(optSelected.data('outstanding')) || 0;
    const available = creditLimit - outstanding;
    
    // Check if invoice amount exceeds available credit
    if (grandTotal > available) {
        const exceedAmount = grandTotal - available;
        $('#creditWarningText').html(
            `This invoice (₹${grandTotal.toFixed(2)}) will exceed available credit by ₹${exceedAmount.toFixed(2)}<br>
            <small>Credit Limit: ₹${creditLimit.toFixed(2)} | Outstanding: ₹${outstanding.toFixed(2)} | Available: ₹${available.toFixed(2)}</small>`
        );
        $('#creditWarningAlert').show();
    } else if (grandTotal > 0) {
        // Show info if credit is sufficient
        const newOutstanding = outstanding + grandTotal;
        $('#creditWarningText').html(
            `Credit Usage OK<br>
            <small>After this invoice: Outstanding will be ₹${newOutstanding.toFixed(2)} (Limit: ₹${creditLimit.toFixed(2)})</small>`
        );
        $('#creditWarningAlert').removeClass('alert-warning').addClass('alert-info').show();
    } else {
        $('#creditWarningAlert').hide();
    }
}

function fetchProductDetails(productId, row) {
    console.log('fetchProductDetails called with productId:', productId);

    $.ajax({
        url: 'php_action/fetchProductInvoice.php',
        type: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            console.log('fetchProductInvoice response:', response);

            if (response.success) {
                const batches = response.data.batches || [];
                const batchSelect = row.find('.batch-select');
                row.data('fefoBatches', batches);
                batchSelect.empty();
                batchSelect.append('<option value="">--Select Batch--</option>');

                if (batches.length === 0) {
                    console.warn('No batches found for product:', productId);
                    batchSelect.append('<option disabled>No stock available</option>');
                } else {
                    batches.forEach(batch => {
                        const expiry = new Date(batch.expiry_date).toLocaleDateString('en-IN');
                        batchSelect.append(`
                            <option value="${batch.batch_id}" 
                                data-available="${batch.available_quantity}" 
                                data-mrp="${batch.mrp}" 
                                data-ptr="${batch.purchase_rate}"
                                data-expiry="${batch.expiry_date}">
                                ${batch.batch_number} (Exp: ${expiry}, Qty: ${batch.available_quantity})
                            </option>
                        `);
                    });

                    // Auto-select first batch (FIFO - earliest expiry)
                    if (batches.length > 0) {
                        batchSelect.val(batches[0].batch_id).change();
                        console.log('Auto-selected batch:', batches[0].batch_id);
                    }
                }
                renderFefoExplain(row);
            } else {
                console.error('fetchProductInvoice failed:', response.message);
                row.find('.batch-select').empty().append('<option value="">Error loading batches</option>');
                row.find('.fefo-explain').text('FEFO preview unavailable.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching product details:', {status: status, error: error, xhr: xhr});
            row.find('.batch-select').empty().append('<option value="">Error loading batches</option>');
            row.find('.fefo-explain').text('FEFO preview unavailable.');
        }
    });
    }

function renderFefoExplainFromPlan(row, plan, requestedQty) {
    const explainEl = row.find('.fefo-explain');
    if (!explainEl.length) return;

    if (!Array.isArray(plan) || plan.length === 0) {
        explainEl.text('FEFO preview unavailable.');
        return;
    }

    const parts = plan.map(p => `${p.batch_number || ('#' + p.batch_id)}: ${parseFloat(p.allocated_quantity || 0).toFixed(2)}`);
    const allocated = plan.reduce((sum, p) => sum + (parseFloat(p.allocated_quantity || 0)), 0);
    explainEl.text(`FEFO plan (${allocated.toFixed(2)} / ${(parseFloat(requestedQty || 0)).toFixed(2)}): ${parts.join(' + ')}`);
}

function renderFefoExplain(row) {
    const explainEl = row.find('.fefo-explain');
    if (!explainEl.length) return;

    const qty = parseFloat(row.find('.quantity-input').val()) || 0;
    const batches = row.data('fefoBatches') || [];

    if (qty <= 0) {
        explainEl.text('FEFO preview will appear after qty entry.');
        return;
    }

    if (!Array.isArray(batches) || batches.length === 0) {
        explainEl.text('FEFO: no active batch available.');
        return;
    }

    let remaining = qty;
    const parts = [];
    for (let i = 0; i < batches.length; i++) {
        const batch = batches[i];
        const available = parseFloat(batch.available_quantity || 0);
        if (available <= 0) continue;

        const take = Math.min(remaining, available);
        if (take > 0) {
            parts.push(`${batch.batch_number}: ${take.toFixed(2)}`);
            remaining -= take;
        }

        if (remaining <= 0) break;
    }

    if (remaining > 0) {
        explainEl.text(`FEFO shortfall: ${remaining.toFixed(2)} unit(s) not available.`);
        return;
    }

    explainEl.text(`FEFO plan: ${parts.join(' + ')}`);
}


function calculateLineTotalRow(row) {
    const qty = parseFloat(row.find('.quantity-input').val()) || 0;
    const rate = parseFloat(row.find('.rate-input').val()) || 0;
    const discount = parseFloat(row.find('.discount-input').val()) || 0;
    const gst = parseFloat(row.find('.gst-input').val()) || 0;

    // Line Total = (Qty × Rate - Discount%) × (1 + GST%)
    const lineAmount = rate * qty;
    const discountAmount = lineAmount * (discount / 100);
    const taxable = lineAmount - discountAmount;
    const taxAmount = taxable * (gst / 100);
    const lineTotal = taxable + taxAmount;

    const displayValue = lineTotal.toFixed(2);
    row.find('.total-display').val(displayValue);
    row.find('.total-value').val(lineTotal);

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

    let invoiceDiscount = 0;
    const discountPercent = parseFloat($('#discountPercent').val()) || 0;
    if (discountPercent > 0) {
        invoiceDiscount = subtotal * (discountPercent / 100);
    }

    const grandTotal = subtotal - totalDiscount - invoiceDiscount + totalTax;

    $('#subtotal').val(subtotal.toFixed(2));
    $('#subtotalValue').val(subtotal.toFixed(2));
    $('#discountAmount').val((totalDiscount + invoiceDiscount).toFixed(2));
    $('#discountAmountValue').val((totalDiscount + invoiceDiscount).toFixed(2));
    $('#gstAmount').val(totalTax.toFixed(2));
    $('#gstAmountValue').val(totalTax.toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
    $('#grandTotalValue').val(grandTotal.toFixed(2));

    calculatePayment();
}

function calculatePayment() {
    const grandTotal = parseFloat($('#grandTotalValue').val()) || 0;
    const paidAmount = parseFloat($('#paidAmount').val()) || 0;
    const dueAmount = grandTotal - paidAmount;

    $('#dueAmount').val(dueAmount.toFixed(2));
    $('#dueAmountValue').val(dueAmount.toFixed(2));

    // Auto-calculate payment status
    let status = 'UNPAID';
    if (paidAmount > 0 && paidAmount < grandTotal) {
        status = 'PARTIAL';
    } else if (paidAmount >= grandTotal) {
        status = 'PAID';
    }

    $('#paymentStatus').val(status);
    $('#paymentStatusValue').val(status);

    // Color code the status
    const statusInput = $('#paymentStatus');
    statusInput.removeClass('bg-success bg-warning bg-danger').css('background-color', 
        status === 'PAID' ? '#d4edda' : status === 'PARTIAL' ? '#fff3cd' : '#f8d7da'
    );
    
    // Check credit limit if applicable
    checkCreditLimit();
}

function getBlankRowHTML(rowNum) {
    return `
        <tr id="row${rowNum}" class="item-row">
            <td>
                <div style="position: relative;">
                    <input type="text" class="form-control form-control-sm product-search" 
                        placeholder="Type medicine name..." data-row="${rowNum}" autocomplete="off" />
                    <input type="hidden" class="product-id" name="product_id[]" />
                    <div class="search-results" style="position: absolute; background: white; border: 1px solid #999; max-height: 300px; overflow-y: auto; display: none; width: 100%; z-index: 1000; border-radius: 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.15); top: 100%; left: 0;"></div>
                </div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm hsn-code text-center" readonly data-row="${rowNum}" />
                <input type="hidden" class="hsn-value" name="hsn_code[]" />
                                                <small class="fefo-explain">FEFO preview will appear after qty entry.</small>
            </td>
            <td>
                <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="${rowNum}">
                    <option value="">--Select--</option>
                </select>
                <small class="fefo-explain">FEFO preview will appear after qty entry.</small>
            </td>
            <td>
                <span class="available-qty text-center text-info font-weight-bold" style="display:block;">-</span>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm quantity-input text-center" 
                    name="quantity[]" data-row="${rowNum}" min="0.01" step="0.01" />
            </td>
            <td>
                <input type="text" class="form-control form-control-sm mrp-display text-center" readonly data-row="${rowNum}" />
                <input type="hidden" class="mrp-value" name="mrp[]" />
            </td>
            <td style="background-color: #fff3e0;">
                <input type="text" class="form-control form-control-sm ptr-display text-center" readonly data-row="${rowNum}" style="background-color: #ffe082; font-weight: bold; color: #000;" />
                <input type="hidden" class="ptr-value" name="ptr[]" />
            </td>
            <td>
                <input type="number" class="form-control form-control-sm rate-input text-center font-weight-bold" 
                    name="rate[]" data-row="${rowNum}" min="0" step="0.01" placeholder="0.00" />
            </td>
            <td>
                <input type="number" class="form-control form-control-sm discount-input text-center" 
                    name="line_discount[]" data-row="${rowNum}" value="0" min="0" max="100" step="0.01" />
            </td>
            <td>
                <input type="number" class="form-control form-control-sm gst-input text-center" 
                    name="gst_rate[]" data-row="${rowNum}" value="18" placeholder="0" step="0.01" min="0" />
            </td>
            <td>
                <input type="text" class="form-control form-control-sm total-display text-right font-weight-bold" readonly data-row="${rowNum}" />
                <input type="hidden" class="total-value" name="line_total[]" />
                <input type="hidden" class="allocation-plan-input" name="allocation_plan[]" />
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row" data-row="${rowNum}" title="Remove Row">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

function addInvoiceRow() {
    rowCount++;
    const newRow = getBlankRowHTML(rowCount);
    $('#itemsBody').append(newRow);
}

function getNextInvoiceNumber() {
    $.ajax({
        url: 'php_action/getNextInvoiceNumber.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#invoiceNumber').val(response.invoice_number);
            }
        }
    });
}

// Debug helper: call with ?debug=1 to log endpoint responses
function runDebugChecks() {
    try {
        const params = new URLSearchParams(window.location.search);
        if (!params.has('debug')) return;

        console.group('DEBUG: sales_invoice_form endpoints');
        console.log('Requesting getNextInvoiceNumber.php');
        $.ajax({ url: 'php_action/getNextInvoiceNumber.php', dataType: 'json', timeout: 5000 })
            .done(function(res){ console.log('getNextInvoiceNumber response:', res); })
            .fail(function(xhr, status, err){ console.error('getNextInvoiceNumber failed:', status, err, xhr.responseText); });

        console.log('Requesting fetchClients.php');
        $.ajax({ url: 'php_action/fetchClients.php', dataType: 'json', timeout: 5000 })
            .done(function(res){ console.log('fetchClients response:', res); })
            .fail(function(xhr, status, err){ console.error('fetchClients failed:', status, err, xhr.responseText); });

        console.groupEnd();
    } catch (e) {
        console.error('Debug checks error:', e);
    }
}

// Run debug checks on load when requested
$(document).ready(function(){ runDebugChecks(); });

function submitInvoice() {
    // Validation
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

    let hasValidItems = false;
    $('.item-row').each(function() {
        const productId = $(this).find('.product-id').val();
        const qty = parseFloat($(this).find('.quantity-input').val()) || 0;
        const rate = parseFloat($(this).find('.rate-input').val()) || 0;

        if (productId && qty > 0 && rate > 0) {
            hasValidItems = true;
        }
    });

    if (!hasValidItems) {
        alert('Please add at least one item with product, quantity, and rate');
        return;
    }

    const formData = $('#invoiceForm').serialize();
    const actionUrl = '<?php echo $editMode ? "php_action/updateSalesInvoice.php" : "php_action/createSalesInvoice.php"; ?>';

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
            let msg = 'Error: ' + error;
            if (xhr.responseText) {
                msg += '\n' + xhr.responseText;
            }
            $('#errorText').text(msg);
            $('#errorMessage').show();
        }
    });
}
</script>

