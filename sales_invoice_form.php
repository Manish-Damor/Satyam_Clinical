<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';

$userId = $_SESSION['userId'] ?? null;

// Edit mode
$editMode = false;
$invoiceData = [];
$initialInvoiceItems = [];
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

if ($editMode) {
    $itemStmt = $connect->prepare("\n        SELECT\n            sii.item_id,\n            sii.product_id,\n            sii.batch_id,\n            sii.quantity,\n            sii.unit_rate,\n            sii.purchase_rate,\n            sii.gst_rate,\n            sii.line_total,\n            sii.batch_number,\n            sii.expiry_date,\n            COALESCE(p.product_name, '') AS product_name,\n            COALESCE(p.hsn_code, '') AS hsn_code,\n            COALESCE(p.pack_size, '') AS pack_size,\n            COALESCE(b.brand_name, '') AS brand_name,\n            COALESCE(pb.mrp, 0) AS mrp\n        FROM sales_invoice_items sii\n        LEFT JOIN product p ON p.product_id = sii.product_id\n        LEFT JOIN brands b ON b.brand_id = p.brand_id\n        LEFT JOIN product_batches pb ON pb.batch_id = sii.batch_id\n        WHERE sii.invoice_id = ?\n        ORDER BY sii.item_id ASC\n    ");
    $itemStmt->bind_param('i', $invoiceId);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();

    while ($itemRow = $itemResult->fetch_assoc()) {
        $initialInvoiceItems[] = [
            'product_id' => (int)$itemRow['product_id'],
            'product_name' => (string)($itemRow['product_name'] ?? ''),
            'brand_name' => (string)($itemRow['brand_name'] ?? ''),
            'pack_size' => (string)($itemRow['pack_size'] ?? ''),
            'hsn_code' => (string)($itemRow['hsn_code'] ?? ''),
            'batch_id' => isset($itemRow['batch_id']) ? (int)$itemRow['batch_id'] : null,
            'batch_number' => (string)($itemRow['batch_number'] ?? ''),
            'expiry_date' => !empty($itemRow['expiry_date']) ? (string)$itemRow['expiry_date'] : '',
            'quantity' => (float)$itemRow['quantity'],
            'rate' => (float)$itemRow['unit_rate'],
            'ptr' => isset($itemRow['purchase_rate']) ? (float)$itemRow['purchase_rate'] : 0.0,
            'mrp' => isset($itemRow['mrp']) ? (float)$itemRow['mrp'] : 0.0,
            'gst_rate' => isset($itemRow['gst_rate']) ? (float)$itemRow['gst_rate'] : 0.0,
            'line_total' => isset($itemRow['line_total']) ? (float)$itemRow['line_total'] : 0.0,
            'line_discount' => 0.0
        ];
    }
}

$pageTitle = $editMode ? 'Edit Sales Invoice' : 'Create Sales Invoice';
$initialInvoiceMode = 'FINAL';
if ($editMode) {
    $initialInvoiceMode = empty($invoiceData['submitted_at']) ? 'DRAFT' : 'FINAL';
}

$initialPaymentType = $invoiceData['payment_type'] ?? '';
$initialPaymentMethod = $invoiceData['payment_method'] ?? '';
$initialClientId = isset($invoiceData['client_id']) ? (int)$invoiceData['client_id'] : 0;
$initialPaidAmount = isset($invoiceData['paid_amount']) ? (float)$invoiceData['paid_amount'] : 0.0;
$initialPaymentStatus = $invoiceData['payment_status'] ?? 'UNPAID';
$initialSubtotal = isset($invoiceData['subtotal']) ? (float)$invoiceData['subtotal'] : 0.0;
$initialDiscountAmount = isset($invoiceData['discount_amount']) ? (float)$invoiceData['discount_amount'] : 0.0;
$initialDiscountPercent = isset($invoiceData['discount_percent']) ? (float)$invoiceData['discount_percent'] : 0.0;
$initialGstAmount = isset($invoiceData['gst_amount']) ? (float)$invoiceData['gst_amount'] : 0.0;
$initialGrandTotal = isset($invoiceData['grand_total']) ? (float)$invoiceData['grand_total'] : 0.0;
$initialDueAmount = isset($invoiceData['due_amount'])
    ? (float)$invoiceData['due_amount']
    : max(0, $initialGrandTotal - $initialPaidAmount);
$initialPaymentTerms = 0;

if (isset($invoiceData['payment_terms']) && $invoiceData['payment_terms'] !== null && $invoiceData['payment_terms'] !== '') {
    $initialPaymentTerms = max(0, (int)$invoiceData['payment_terms']);
} elseif ($editMode && !empty($invoiceData['invoice_date']) && !empty($invoiceData['due_date'])) {
    try {
        $invoiceDateObj = new DateTime((string)$invoiceData['invoice_date']);
        $dueDateObj = new DateTime((string)$invoiceData['due_date']);
        $diffDays = (int)$invoiceDateObj->diff($dueDateObj)->format('%r%a');
        $initialPaymentTerms = max(0, $diffDays);
    } catch (Exception $e) {
        $initialPaymentTerms = 0;
    }
}
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
                    <input type="hidden" name="invoice_status" id="invoiceStatus" value="<?php echo htmlspecialchars($initialInvoiceMode); ?>" />

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
                                        value="<?php echo htmlspecialchars((string)$initialPaymentTerms); ?>" min="0" required />
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
                            <div class="table-responsive invoice-items-scroll">
                                <table class="table table-bordered table-sm invoice-items-table" id="invoiceItemsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="width: 16%;">Medicine Name</th>
                                            <th style="width: 9%;">Mfr.</th>
                                            <th style="width: 7%;">Pack</th>
                                            <th style="width: 6%;">HSN</th>
                                            <th style="width: 11%;">B.No.</th>
                                            <th style="width: 7%;">Exp.</th>
                                            <th style="width: 6%;">Avail</th>
                                            <th style="width: 6%;">Qty</th>
                                            <th style="width: 7%;">MRP</th>
                                            <th style="width: 7%; background-color: #fff3e0; color: #000;"><strong>PTR</strong></th>
                                            <th style="width: 7%;">Rate</th>
                                            <th style="width: 6%;">Disc %</th>
                                            <th style="width: 6%;">GST %</th>
                                            <th style="width: 10%;">Line Total</th>
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
                                                    <div class="search-results"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm manufacturer-display" readonly />
                                                <input type="hidden" class="manufacturer-value" name="manufacturer_snapshot[]" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm pack-display text-center" readonly />
                                                <input type="hidden" class="pack-value" name="pack_size_snapshot[]" />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm hsn-code text-center" readonly data-row="1" />
                                                <input type="hidden" class="hsn-value" name="hsn_code[]" />
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="1">
                                                    <option value="">--Select--</option>
                                                </select>
                                                <input type="hidden" class="batch-number-value" name="batch_number[]" />
                                                <small class="fefo-explain">FEFO preview will appear after qty entry.</small>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm expiry-display text-center" readonly />
                                                <input type="hidden" class="expiry-value" name="expiry_date[]" />
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
                                            <td class="ptr-column" style="background-color: #fff3e0;">
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
                                                    <input type="hidden" name="subtotal" id="subtotalValue" value="<?php echo htmlspecialchars(number_format($initialSubtotal, 2, '.', '')); ?>" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Invoice Discount (%):</strong></td>
                                                <td class="text-right">
                                                    <input type="number" class="form-control text-right" name="discount_percent" 
                                                        id="discountPercent" value="<?php echo htmlspecialchars(number_format($initialDiscountPercent, 2, '.', '')); ?>" step="0.01" min="0" max="100" style="padding: 0.25rem;" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Discount Amount:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right" id="discountAmount" readonly style="border: none; padding: 0.25rem; font-weight: bold;" />
                                                    <input type="hidden" name="discount_amount" id="discountAmountValue" value="<?php echo htmlspecialchars(number_format($initialDiscountAmount, 2, '.', '')); ?>" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>GST Amount:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right" id="gstAmount" readonly style="border: none; padding: 0.25rem; font-weight: bold;" />
                                                    <input type="hidden" name="gst_amount" id="gstAmountValue" value="<?php echo htmlspecialchars(number_format($initialGstAmount, 2, '.', '')); ?>" />
                                                </td>
                                            </tr>
                                            <tr style="border-top: 3px solid #333; background-color: #fff3e0;">
                                                <td><strong class="text-lg">Grand Total:</strong></td>
                                                <td class="text-right">
                                                    <input type="text" class="form-control text-right font-weight-bold" 
                                                        id="grandTotal" readonly style="border: none; padding: 0.5rem; font-size: 18px;" />
                                                    <input type="hidden" name="grand_total" id="grandTotalValue" value="<?php echo htmlspecialchars(number_format($initialGrandTotal, 2, '.', '')); ?>" />
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
                                        <option value="Cash" <?php echo $initialPaymentType === 'Cash' ? 'selected' : ''; ?>>Cash Sale</option>
                                        <option value="Credit" <?php echo $initialPaymentType === 'Credit' ? 'selected' : ''; ?>>Credit Sale</option>
                                    </select>
                                </div>

                                <div class="col-md-3" id="paymentMethodColumn">
                                    <label class="font-weight-bold">Payment Method</label>
                                    <select class="form-control" name="payment_method" id="paymentMethodSelect">
                                        <option value="">-- Select Method --</option>
                                        <option value="Cash" <?php echo $initialPaymentMethod === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="UPI" <?php echo $initialPaymentMethod === 'UPI' ? 'selected' : ''; ?>>UPI</option>
                                        <option value="Card" <?php echo $initialPaymentMethod === 'Card' ? 'selected' : ''; ?>>Card</option>
                                        <option value="Bank Transfer" <?php echo $initialPaymentMethod === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="Cheque" <?php echo $initialPaymentMethod === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="Other" <?php echo $initialPaymentMethod === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Grand Total (Quick Ref)</label>
                                    <input type="text" class="form-control" id="paymentGrandTotal" readonly style="font-weight: bold; background-color: #f5f5f5;" value="<?php echo htmlspecialchars(number_format($initialGrandTotal, 2, '.', '')); ?>" />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Paid Amount</label>
                                    <input type="number" class="form-control" name="paid_amount" id="paidAmount"
                                        value="<?php echo htmlspecialchars(number_format($initialPaidAmount, 2, '.', '')); ?>"
                                        step="0.01" min="0" />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Due Amount</label>
                                    <input type="text" class="form-control" id="dueAmount" readonly style="font-weight: bold; background-color: #f5f5f5;" />
                                    <input type="hidden" name="due_amount" id="dueAmountValue" value="<?php echo htmlspecialchars(number_format($initialDueAmount, 2, '.', '')); ?>" />
                                </div>

                                <div class="col-md-3">
                                    <label class="font-weight-bold">Payment Status</label>
                                    <input type="text" class="form-control" id="paymentStatus" readonly style="font-weight: bold; background-color: #e8f5e9;" />
                                    <input type="hidden" name="payment_status" id="paymentStatusValue" value="<?php echo htmlspecialchars($initialPaymentStatus); ?>" />
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

    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" role="dialog" aria-labelledby="invoicePreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="invoicePreviewLabel"><i class="fa fa-eye"></i> Invoice Preview</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="invoicePreviewContent">
                    <div class="text-center text-muted py-3">Preview will appear here.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceAlertModal" tabindex="-1" role="dialog" aria-labelledby="invoiceAlertTitleText" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white" id="invoiceAlertHeader">
                    <h5 class="modal-title" id="invoiceAlertTitle">
                        <i id="invoiceAlertIcon" class="fa fa-info-circle mr-2"></i>
                        <span id="invoiceAlertTitleText">Notification</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="invoiceAlertText" class="invoice-alert-text">-</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="invoiceAlertOkBtn" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<!-- Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<style>
    :root {
        --si-bg: #f2f6fb;
        --si-surface: #ffffff;
        --si-border: #c7d4e5;
        --si-text: #172b4d;
        --si-muted: #4f6280;
        --si-primary: #1f4e8c;
        --si-success: #0f6b52;
        --si-info: #145f86;
        --si-warning: #b87407;
        --si-danger: #8c1f39;
        --si-focus: #2563eb;
    }

    .page-wrapper {
        background: linear-gradient(180deg, #edf3fb 0%, #f7fbff 45%, #f0f6ff 100%);
        color: var(--si-text);
    }

    .page-wrapper .page-titles h2,
    .page-wrapper .page-titles h3,
    .page-wrapper .page-titles p {
        color: var(--si-text) !important;
    }

    .page-wrapper .text-muted {
        color: var(--si-muted) !important;
    }

    .breadcrumb-item a {
        color: #275f9d;
        font-weight: 600;
    }

    .breadcrumb-item.active {
        color: #314a71;
        font-weight: 600;
    }

    .invoice-form {
        font-size: 0.95rem;
        color: var(--si-text);
    }

    .form-control-lg {
        height: 38px;
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
    }

    label.font-weight-bold {
        color: #1f365a;
        letter-spacing: 0.1px;
    }

    .card {
        margin-bottom: 20px;
        border: 1px solid var(--si-border);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 8px 18px rgba(19, 38, 68, 0.06);
        background: var(--si-surface);
    }

    .card-header {
        font-weight: 700;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .card.border-primary { border-color: #2f66a8 !important; }
    .card.border-success { border-color: #208164 !important; }
    .card.border-info { border-color: #2b7ba5 !important; }
    .card.border-warning { border-color: #d1902e !important; }
    .card.border-danger { border-color: #b73f5a !important; }

    .card-header.bg-primary {
        background: linear-gradient(135deg, #2c6bae, #1b4f88) !important;
        color: #ffffff !important;
    }

    .card-header.bg-success {
        background: linear-gradient(135deg, #198065, #0f694f) !important;
        color: #ffffff !important;
    }

    .card-header.bg-info {
        background: linear-gradient(135deg, #2f7ea8, #185f87) !important;
        color: #ffffff !important;
    }

    .card-header.bg-warning {
        background: linear-gradient(135deg, #efbb67, #de9a33) !important;
        color: #3f2b00 !important;
    }

    .card-header.bg-danger {
        background: linear-gradient(135deg, #be4a63, #972d47) !important;
        color: #ffffff !important;
    }

    .invoice-items-table thead th {
        background-color: #1e426f;
        color: #ffffff;
        font-weight: 700;
        padding: 10px 5px;
        border-color: #2b547f !important;
    }

    .table td,
    .table th {
        border-color: #d4deec;
    }

    .table-sm td,
    .table-sm th {
        color: #243e60;
    }

    .form-control,
    .form-control-sm,
    .form-control-lg,
    .batch-select {
        border: 1px solid #b8c8da;
        color: #122946;
        background-color: #ffffff;
    }

    .form-control::placeholder,
    .form-control-sm::placeholder,
    .form-control-lg::placeholder {
        color: #7d90ad;
    }

    .form-control:focus,
    .form-control-sm:focus,
    .form-control-lg:focus,
    .batch-select:focus {
        border-color: var(--si-focus);
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
    }

    input[readonly],
    textarea[readonly],
    .form-control[readonly] {
        background-color: #f3f7fc !important;
        color: #203a5d;
    }

    #clientDetailsPanel .card.bg-light {
        background: #f2f7fd !important;
        border: 1px solid #d7e3f2;
    }

    #clientDetailsPanel .card.bg-light .card-title {
        color: #0f345f;
        font-size: 1rem;
        letter-spacing: 0.2px;
    }

    #clientDetailsPanel #billingAddr {
        color: #15365e;
        font-size: 0.95rem;
        line-height: 1.45;
        font-weight: 500;
    }

    #clientDetailsPanel #billingAddr em,
    #clientDetailsPanel .text-sm,
    #clientDetailsPanel .table td,
    #clientDetailsPanel .table th {
        color: #1c3e67 !important;
    }

    #clientDetailsPanel .table td strong,
    #clientDetailsPanel .table td span,
    #clientDetailsPanel #outstanding,
    #clientDetailsPanel #availableCredit {
        color: #102f54 !important;
        font-weight: 600;
    }

    #clientDetailsPanel .badge-info {
        background-color: #1e5f99;
        color: #ffffff;
    }

    #clientDetailsPanel .badge-success {
        background-color: #157a5c;
        color: #ffffff;
    }

    #clientDetailsPanel tr[style*="background-color: #e8f5e9"] {
        background-color: #dff4e9 !important;
    }

    #clientDetailsPanel tr[style*="background-color: #e8f5e9"] td {
        color: #0e4b35 !important;
        font-weight: 700;
    }

    #successMessage {
        border-left: 5px solid #1f8a6a;
    }

    #errorMessage {
        border-left: 5px solid #c03553;
    }

    #creditWarningAlert {
        border-left: 5px solid #d28a16;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #b8c8da;
        border-radius: 4px;
        background: #ffffff;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        color: #15304f;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--si-focus);
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
    }

    .select2-container--default .select2-results__option {
        color: #1a3355;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #2f6daa;
        color: #ffffff;
    }

    #invoiceItemsTable tbody tr:hover { background-color: #f9f9f9; }

    /* Keep all invoice item fields visible with horizontal scroll */
    .invoice-items-scroll {
        overflow-x: auto !important;
        overflow-y: visible !important;
        max-width: 100%;
        padding-bottom: 6px;
        border: 1px solid #d7dde5;
        border-radius: 6px;
        background: #fff;
    }

    .invoice-items-scroll .table {
        margin-bottom: 0;
    }

    #invoiceItemsTable {
        min-width: 1580px;
        width: max-content;
    }

    #invoiceItemsTable th,
    #invoiceItemsTable td {
        white-space: nowrap;
        vertical-align: top;
    }

    #invoiceItemsTable .form-control-sm,
    #invoiceItemsTable .form-control,
    #invoiceItemsTable .batch-select {
        min-width: 90px;
    }

    #invoiceItemsTable .product-search {
        min-width: 250px;
    }

    #invoiceItemsTable th:nth-child(1), #invoiceItemsTable td:nth-child(1) { min-width: 270px; }
    #invoiceItemsTable th:nth-child(2), #invoiceItemsTable td:nth-child(2) { min-width: 145px; }
    #invoiceItemsTable th:nth-child(3), #invoiceItemsTable td:nth-child(3) { min-width: 90px; }
    #invoiceItemsTable th:nth-child(4), #invoiceItemsTable td:nth-child(4) { min-width: 95px; }
    #invoiceItemsTable th:nth-child(5), #invoiceItemsTable td:nth-child(5) { min-width: 185px; }
    #invoiceItemsTable th:nth-child(6), #invoiceItemsTable td:nth-child(6) { min-width: 95px; }
    #invoiceItemsTable th:nth-child(7), #invoiceItemsTable td:nth-child(7) { min-width: 80px; }
    #invoiceItemsTable th:nth-child(8), #invoiceItemsTable td:nth-child(8) { min-width: 90px; }
    #invoiceItemsTable th:nth-child(9), #invoiceItemsTable td:nth-child(9) { min-width: 100px; }
    #invoiceItemsTable th:nth-child(10), #invoiceItemsTable td:nth-child(10) { min-width: 100px; }
    #invoiceItemsTable th:nth-child(11), #invoiceItemsTable td:nth-child(11) { min-width: 100px; }
    #invoiceItemsTable th:nth-child(12), #invoiceItemsTable td:nth-child(12) { min-width: 90px; }
    #invoiceItemsTable th:nth-child(13), #invoiceItemsTable td:nth-child(13) { min-width: 90px; }
    #invoiceItemsTable th:nth-child(14), #invoiceItemsTable td:nth-child(14) { min-width: 125px; }
    #invoiceItemsTable th:nth-child(15), #invoiceItemsTable td:nth-child(15) { min-width: 72px; }

    .manufacturer-display,
    .pack-display,
    .expiry-display { background-color: #f8f9fa !important; }
    .ptr-display { background-color: #ffe082 !important; font-weight: bold; color: #000 !important; }

    /* dropdown visibility fix */
    .product-search { width: 100%; }
    .search-results {
        position: fixed !important;
        z-index: 12000 !important;
        min-width: 220px;
        max-height: 260px;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid #9aa4b2;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
        font-size: 12px;
        line-height: 1.2;
        display: none;
    }

    .search-results .product-item {
        font-size: 12px;
        line-height: 1.25;
        padding: 8px 10px !important;
        color: #133152;
    }

    .search-results .product-item:hover,
    .search-results .product-item.active-product-option {
        background: #eaf3ff !important;
    }

    .search-results .product-item small {
        font-size: 11px;
    }

    .invoice-preview-header {
        border-bottom: 2px solid #d7dde5;
        margin-bottom: 12px;
        padding-bottom: 10px;
    }

    .invoice-preview-meta {
        font-size: 13px;
        line-height: 1.5;
    }

    #invoicePreviewContent .table th,
    #invoicePreviewContent .table td {
        font-size: 12px;
        vertical-align: middle;
    }

    #invoicePreviewContent .preview-summary td {
        padding: 6px 8px;
    }

    #invoiceAlertModal .modal-content {
        border-radius: 10px;
        overflow: hidden;
    }

    .invoice-alert-text {
        white-space: pre-line;
        font-size: 14px;
        line-height: 1.5;
        color: #1f2937;
    }

    .fefo-explain {
        margin-top: 4px;
        font-size: 11px;
        color: #64748b;
        line-height: 1.3;
        display: block;
    }
    
    @media (max-width: 768px) {
        .invoice-items-scroll {
            font-size: 0.85rem;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .form-control-sm { height: 28px; }
        #invoiceItemsTable { min-width: 1180px; }
        #invoiceItemsTable .product-search { min-width: 190px; }
        #invoiceItemsTable th,
        #invoiceItemsTable td {
            font-size: 11px;
            padding: 0.35rem;
        }
    }

    @media print {
        .no-print, .btn, .form-control, .card-header, #addRowBtn, .remove-row { display: none !important; }
        .sidebar, .header, .page-titles, .navbar { display: none !important; }
        .container-fluid { max-width: 100%; padding: 0; }
        .card { border: none; page-break-inside: avoid; }
        
        /* Hide internal info on print */
        .ptr-column, .ptr-display,
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

        .invoice-items-scroll { overflow: visible !important; border: none !important; }
        #invoiceItemsTable { min-width: 100% !important; width: 100% !important; }
        
        /* Page break handling */
        .page-break { page-break-after: always; }
    }
</style>

<script>
let rowCount = 1;
let allClients = [];
let clientSearchTerm = '';
let paymentAutoSync = false;
const isEditMode = <?php echo $editMode ? 'true' : 'false'; ?>;
const initialInvoiceMode = '<?php echo addslashes($initialInvoiceMode); ?>';
const initialClientId = <?php echo (int)$initialClientId; ?>;
const initialInvoiceItems = <?php echo json_encode($initialInvoiceItems, JSON_UNESCAPED_SLASHES); ?>;

function escapeHtml(value) {
    return (value || '').toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatMoney(value) {
    const num = parseFloat(value);
    if (!Number.isFinite(num)) return '0.00';
    return num.toFixed(2);
}

function safePreviewText(value, fallback) {
    const text = (value || '').toString().trim();
    return text !== '' ? escapeHtml(text) : (fallback || '-');
}

function showInvoicePopup(type, message, title) {
    const configMap = {
        success: {
            title: 'Success',
            icon: 'fa fa-check-circle',
            headerClass: 'bg-success text-white',
            buttonClass: 'btn btn-success'
        },
        error: {
            title: 'Error',
            icon: 'fa fa-exclamation-circle',
            headerClass: 'bg-danger text-white',
            buttonClass: 'btn btn-danger'
        },
        warning: {
            title: 'Warning',
            icon: 'fa fa-exclamation-triangle',
            headerClass: 'bg-warning text-dark',
            buttonClass: 'btn btn-warning'
        },
        info: {
            title: 'Notification',
            icon: 'fa fa-info-circle',
            headerClass: 'bg-info text-white',
            buttonClass: 'btn btn-info'
        }
    };

    const safeType = configMap[type] ? type : 'info';
    const cfg = configMap[safeType];
    const modal = $('#invoiceAlertModal');

    if (!modal.length || typeof modal.modal !== 'function') {
        alert((title || cfg.title) + ': ' + (message || ''));
        return;
    }

    const header = $('#invoiceAlertHeader');
    const icon = $('#invoiceAlertIcon');
    const titleText = $('#invoiceAlertTitleText');
    const messageText = $('#invoiceAlertText');
    const okBtn = $('#invoiceAlertOkBtn');

    header.removeClass('bg-success bg-danger bg-warning bg-info text-white text-dark');
    header.addClass(cfg.headerClass);

    icon.attr('class', cfg.icon + ' mr-2');
    titleText.text(title || cfg.title);
    messageText.text(message || '');

    okBtn.removeClass('btn-success btn-danger btn-warning btn-info');
    okBtn.addClass(cfg.buttonClass);

    modal.modal('show');
}

function normalizeText(value) {
    return (value || '').toString().toLowerCase().replace(/[^a-z0-9]/g, '');
}

function acronymFromText(value) {
    return (value || '')
        .toString()
        .toLowerCase()
        .split(/\s+/)
        .filter(Boolean)
        .map(word => word.charAt(0))
        .join('');
}

function isSubsequence(query, target) {
    if (!query) return true;
    let pointer = 0;
    for (let index = 0; index < target.length && pointer < query.length; index++) {
        if (target[index] === query[pointer]) pointer++;
    }
    return pointer === query.length;
}

function fuzzyScoreMedicine(term, product) {
    const query = normalizeText(term);
    if (!query) return 0;

    const name = normalizeText(product.product_name || '');
    const brand = normalizeText(product.brand_name || '');
    const content = normalizeText(product.content || '');
    const hsn = normalizeText(product.hsn_code || '');
    const idText = (product.id || product.product_id || '').toString();
    const acronym = normalizeText(acronymFromText((product.product_name || '') + ' ' + (product.brand_name || '')));

    if (name === query) return 1200;
    if (idText === term) return 1100;
    if (name.startsWith(query)) return 950;
    if (brand.startsWith(query)) return 870;
    if (name.includes(query)) return 760;
    if (brand.includes(query)) return 700;
    if (content.includes(query)) return 620;
    if (acronym.startsWith(query)) return 580;
    if (isSubsequence(query, name)) return 520;
    if (isSubsequence(query, brand)) return 500;
    if (hsn.includes(query)) return 460;
    return 0;
}

function fuzzyScoreClient(term, haystack) {
    const query = normalizeText(term);
    if (!query) return 0;

    const text = normalizeText(haystack || '');
    const acronym = normalizeText(acronymFromText(haystack || ''));

    if (text === query) return 1000;
    if (text.startsWith(query)) return 900;
    if (text.includes(query)) return 760;
    if (acronym.startsWith(query)) return 640;
    if (isSubsequence(query, text)) return 540;
    return 0;
}

function focusFirstClientResultOption() {
    const openContainer = $('.select2-container--open');
    if (!openContainer.length) return;

    const options = openContainer
        .find('.select2-results__option[aria-selected]')
        .not('.select2-results__option--loading')
        .not('.select2-results__option--disabled');

    if (!options.length) return;

    const first = options.first();
    options.removeClass('select2-results__option--highlighted');
    first.addClass('select2-results__option--highlighted').trigger('mouseenter');
}

function setActiveProductResult(resultsDiv, index) {
    const options = resultsDiv.find('.product-item');
    if (!options.length) {
        resultsDiv.data('active-index', -1);
        return;
    }

    const safeIndex = Math.max(0, Math.min(index, options.length - 1));
    options.removeClass('active-product-option').css({ backgroundColor: '#fff' });

    const active = options.eq(safeIndex);
    active.addClass('active-product-option').css({ backgroundColor: '#eef6ff' });
    resultsDiv.data('active-index', safeIndex);

    const activeTop = active.position().top;
    const activeBottom = activeTop + active.outerHeight();
    const currentScroll = resultsDiv.scrollTop();
    const visibleHeight = resultsDiv.innerHeight();

    if (activeBottom > visibleHeight) {
        resultsDiv.scrollTop(currentScroll + (activeBottom - visibleHeight));
    } else if (activeTop < 0) {
        resultsDiv.scrollTop(currentScroll + activeTop);
    }
}

function positionSearchDropdown(searchInput, resultsDiv) {
    if (!searchInput || !searchInput.length || !resultsDiv || !resultsDiv.length) {
        return;
    }

    const inputEl = searchInput[0];
    const rect = inputEl.getBoundingClientRect();
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    const desiredMaxHeight = 260;
    const minDropdownHeight = 100;
    const spaceBelow = viewportHeight - rect.bottom - 8;
    const top = rect.bottom + 2;
    const width = Math.max(220, rect.width);

    // Always render below the input and let the results list scroll when space is limited.
    let maxHeight = Math.min(desiredMaxHeight, Math.max(minDropdownHeight, spaceBelow));
    if (spaceBelow < 70) {
        maxHeight = 70;
    }

    let left = Math.max(8, rect.left);
    if ((left + width) > (viewportWidth - 8)) {
        left = Math.max(8, viewportWidth - width - 8);
    }

    resultsDiv.css({
        left: left + 'px',
        top: Math.max(8, top) + 'px',
        width: width + 'px',
        maxHeight: maxHeight + 'px'
    });

    resultsDiv.data('anchor-input', inputEl);
}

function repositionVisibleProductDropdowns() {
    $('.search-results:visible').each(function() {
        const resultsDiv = $(this);
        const anchorInput = resultsDiv.data('anchor-input');

        if (!anchorInput || !document.body.contains(anchorInput)) {
            resultsDiv.hide().data('active-index', -1).removeData('anchor-input');
            return;
        }

        positionSearchDropdown($(anchorInput), resultsDiv);
    });
}

function renderProductResults(resultsDiv, products) {
    if (!Array.isArray(products) || products.length === 0) {
        resultsDiv.html('<div style="padding:10px;color:#999;background:#fff;">No products found</div>').show();
        const anchorInput = resultsDiv.data('anchor-input');
        if (anchorInput && document.body.contains(anchorInput)) {
            positionSearchDropdown($(anchorInput), resultsDiv);
        }
        return;
    }

    const html = products.map(product => `
        <div class="product-item" style="padding:10px;cursor:pointer;border-bottom:1px solid #eee;background:#fff;" 
            data-id="${product.id || product.product_id}"
            data-name="${escapeHtml(product.product_name || '')}"
            data-hsn="${escapeHtml(product.hsn_code || '')}"
            data-gst="${product.gst_rate || 0}"
            data-mfr="${escapeHtml(product.brand_name || '')}"
            data-pack="${escapeHtml(product.pack_size || '')}">
            <strong>${escapeHtml(product.product_name || '')}</strong> ${product.content ? '(' + escapeHtml(product.content) + ')' : ''}<br>
            <small class="text-muted">Mfr: ${escapeHtml(product.brand_name || '-')} | Pack: ${escapeHtml(product.pack_size || '-')} | HSN: ${escapeHtml(product.hsn_code || 'N/A')} | GST: ${product.gst_rate || 0}% | MRP: ₹${product.expected_mrp || '0'}</small>
        </div>
    `).join('');

    resultsDiv.html(html).show();
    const anchorInput = resultsDiv.data('anchor-input');
    if (anchorInput && document.body.contains(anchorInput)) {
        positionSearchDropdown($(anchorInput), resultsDiv);
    }
    setActiveProductResult(resultsDiv, 0);
}

function fetchFallbackMedicineResults(searchTerm, onDone) {
    $.ajax({
        url: 'php_action/searchMedicines.php',
        type: 'GET',
        data: { q: searchTerm },
        dataType: 'json',
        timeout: 5000,
        success: function(results) {
            const rows = Array.isArray(results) ? results.map(function(item) {
                return {
                    id: item.product_id || item.id,
                    product_id: item.product_id || item.id,
                    product_name: item.product_name,
                    content: item.content,
                    pack_size: item.pack_size,
                    hsn_code: item.hsn_code,
                    expected_mrp: item.expected_mrp || 0,
                    gst_rate: item.gst_rate || 0,
                    brand_name: item.brand_name || ''
                };
            }) : [];
            onDone(rows);
        },
        error: function() {
            onDone([]);
        }
    });
}

function selectProductOption(option) {
    const productId = option.data('id');
    const productName = option.data('name');
    const hsn = option.data('hsn');
    const gst = option.data('gst');
    const manufacturer = option.data('mfr') || '';
    const packSize = option.data('pack') || '';

    const row = option.closest('tr');
    const searchInput = row.find('.product-search');

    searchInput.val(productName);
    row.find('.product-id').val(productId);
    row.find('.manufacturer-display').val(manufacturer);
    row.find('.manufacturer-value').val(manufacturer);
    row.find('.pack-display').val(packSize);
    row.find('.pack-value').val(packSize);
    row.find('.hsn-code').val(hsn || '');
    row.find('.hsn-value').val(hsn || '');
    row.find('.gst-input').val(gst || 0);

    // Reset dependent values before fresh batch fetch
    row.find('.batch-select').empty().append('<option value="">--Select Batch--</option>');
    row.find('.batch-number-value').val('');
    row.find('.expiry-display').val('');
    row.find('.expiry-value').val('');
    row.find('.available-qty').text('-');
    row.find('.mrp-display').val('');
    row.find('.mrp-value').val('');
    row.find('.ptr-display').val('');
    row.find('.ptr-value').val('');
    row.find('.rate-input').val('').attr('max', '');
    row.find('.allocation-plan-input').val('');

    searchInput.closest('div').find('.search-results').hide().data('active-index', -1).removeData('anchor-input');

    fetchProductDetails(productId, row);
}

function buildPreviewItemsRowsHtml() {
    let rowsHtml = '';

    $('#itemsBody tr.item-row').each(function(index) {
        const row = $(this);
        const productId = row.find('.product-id').val();
        const productName = row.find('.product-search').val();
        const qty = parseFloat(row.find('.quantity-input').val()) || 0;
        const rate = parseFloat(row.find('.rate-input').val()) || 0;

        if (!productId || qty <= 0 || rate <= 0) {
            return;
        }

        const manufacturer = row.find('.manufacturer-display').val();
        const pack = row.find('.pack-display').val();
        const batchNumber = row.find('.batch-number-value').val();
        const expiry = row.find('.expiry-display').val();
        const mrp = row.find('.mrp-value').val();
        const discount = row.find('.discount-input').val();
        const gst = row.find('.gst-input').val();
        const lineTotal = row.find('.total-value').val() || row.find('.total-display').val();

        rowsHtml += `
            <tr>
                <td>${index + 1}</td>
                <td>${safePreviewText(productName, '-')}</td>
                <td>${safePreviewText(manufacturer, '-')}</td>
                <td class="text-center">${safePreviewText(pack, '-')}</td>
                <td>${safePreviewText(batchNumber, '-')}</td>
                <td class="text-center">${safePreviewText(expiry, '-')}</td>
                <td class="text-right">${formatMoney(qty)}</td>
                <td class="text-right">${formatMoney(mrp)}</td>
                <td class="text-right">${formatMoney(rate)}</td>
                <td class="text-right">${formatMoney(discount)}</td>
                <td class="text-right">${formatMoney(gst)}</td>
                <td class="text-right"><strong>${formatMoney(lineTotal)}</strong></td>
            </tr>
        `;
    });

    if (rowsHtml === '') {
        rowsHtml = '<tr><td colspan="12" class="text-center text-muted">No valid invoice items to preview.</td></tr>';
    }

    return rowsHtml;
}

function openInvoicePreviewModal() {
    calculateTotals();

    const selectedClient = $('#clientSelect').find('option:selected');
    const clientName = selectedClient.data('name') || selectedClient.text();
    const clientPhone = selectedClient.data('contact');
    const clientEmail = selectedClient.data('email');
    const clientGST = selectedClient.data('gstin');
    const clientPAN = selectedClient.data('pan');
    const clientDL = selectedClient.data('dl');
    const billingAddress = selectedClient.data('billing') || $('#billingAddr').text();
    const deliveryAddress = $('textarea[name="delivery_address"]').val();

    const invoiceNumber = $('#invoiceNumber').val();
    const invoiceDate = $('input[name="invoice_date"]').val();
    const dueDate = $('#dueDate').val();
    const paymentTerms = $('#paymentTerms').val();
    const paymentType = $('#paymentTypeSelect').val();
    const paymentMethod = $('#paymentMethodSelect').val();
    const paymentStatus = $('#paymentStatusValue').val() || $('#paymentStatus').val();
    const paymentNotes = $('textarea[name="payment_notes"]').val();

    const subtotal = $('#subtotalValue').val();
    const discountAmount = $('#discountAmountValue').val();
    const gstAmount = $('#gstAmountValue').val();
    const grandTotal = $('#grandTotalValue').val();
    const paidAmount = $('#paidAmount').val();
    const dueAmount = $('#dueAmountValue').val();

    const previewHtml = `
        <div class="invoice-preview-header">
            <h4 class="mb-1 text-dark">TROIKAA LIFE CARE</h4>
            <div class="text-muted">HOUSE NO.3196/9, SHOP NO 12, HARIOM COMPLEX, SADAK PALIYA, DUNGRA, PIN 396195</div>
            <div class="text-muted">Phone: 9925455205 | D.L. No.: 20B-193927, 21B-193928</div>
        </div>

        <div class="row invoice-preview-meta mb-3">
            <div class="col-md-6">
                <div><strong>Invoice No:</strong> ${safePreviewText(invoiceNumber, '-')}</div>
                <div><strong>Invoice Date:</strong> ${safePreviewText(invoiceDate, '-')}</div>
                <div><strong>Due Date:</strong> ${safePreviewText(dueDate, '-')}</div>
                <div><strong>Payment Terms:</strong> ${safePreviewText(paymentTerms, '0')} day(s)</div>
            </div>
            <div class="col-md-6">
                <div><strong>Client:</strong> ${safePreviewText(clientName, '-')}</div>
                <div><strong>Phone:</strong> ${safePreviewText(clientPhone, '-')}</div>
                <div><strong>Email:</strong> ${safePreviewText(clientEmail, '-')}</div>
                <div><strong>GSTIN:</strong> ${safePreviewText(clientGST, '-')} | <strong>PAN:</strong> ${safePreviewText(clientPAN, '-')}</div>
                <div><strong>D.L. No:</strong> ${safePreviewText(clientDL, '-')}</div>
            </div>
        </div>

        <div class="mb-2"><strong>Billing Address:</strong> ${safePreviewText(billingAddress, '-')}</div>
        <div class="mb-3"><strong>Delivery Address:</strong> ${safePreviewText(deliveryAddress, safePreviewText(billingAddress, '-'))}</div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Mfr</th>
                        <th>Pack</th>
                        <th>Batch</th>
                        <th>Exp</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Disc %</th>
                        <th class="text-right">GST %</th>
                        <th class="text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${buildPreviewItemsRowsHtml()}
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6 invoice-preview-meta">
                <div><strong>Payment Type:</strong> ${safePreviewText(paymentType, '-')}</div>
                <div><strong>Payment Method:</strong> ${safePreviewText(paymentMethod, '-')}</div>
                <div><strong>Payment Status:</strong> ${safePreviewText(paymentStatus, '-')}</div>
                <div><strong>Notes:</strong> ${safePreviewText(paymentNotes, '-')}</div>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm preview-summary mb-0">
                    <tr><td><strong>Subtotal</strong></td><td class="text-right">${formatMoney(subtotal)}</td></tr>
                    <tr><td><strong>Discount</strong></td><td class="text-right">${formatMoney(discountAmount)}</td></tr>
                    <tr><td><strong>GST</strong></td><td class="text-right">${formatMoney(gstAmount)}</td></tr>
                    <tr><td><strong>Grand Total</strong></td><td class="text-right"><strong>${formatMoney(grandTotal)}</strong></td></tr>
                    <tr><td><strong>Paid</strong></td><td class="text-right">${formatMoney(paidAmount)}</td></tr>
                    <tr><td><strong>Due</strong></td><td class="text-right"><strong>${formatMoney(dueAmount)}</strong></td></tr>
                </table>
            </div>
        </div>
    `;

    $('#invoicePreviewContent').html(previewHtml);

    if ($.fn.modal) {
        $('#invoicePreviewModal').modal('show');
    } else {
        showInvoicePopup('warning', 'Preview is currently unavailable.', 'Preview Unavailable');
    }
}

$(document).ready(function() {
    loadClients();
    
    if ($('#invoiceNumber').val() === '') {
        getNextInvoiceNumber();
    }

    if (isEditMode) {
        const initialPaymentType = '<?php echo addslashes($initialPaymentType); ?>';
        if (initialPaymentType) {
            $('#paymentTypeSelect').val(initialPaymentType);
        }
    }

    if (String($('#paymentTypeSelect').val() || '') === 'Cash' && initialInvoiceMode === 'FINAL') {
        const seededPaid = parseFloat($('#paidAmount').val()) || 0;
        const seededGrand = parseFloat($('#grandTotalValue').val()) || 0;
        paymentAutoSync = Math.abs(seededPaid - seededGrand) < 0.01;
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
        const isDraftMode = isDraftInvoice();

        // Keep method visible for all types; choose sensible default.
        if (paymentType === 'Cash' && !$('#paymentMethodSelect').val()) {
            $('#paymentMethodSelect').val('Cash');
        }

        if (!isDraftMode && paymentType === 'Cash') {
            paymentAutoSync = true;
            syncPaidAmountToGrandTotal(true);
        } else if (paymentType !== 'Cash') {
            paymentAutoSync = false;
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
        paymentAutoSync = false;
        calculatePayment();
    });

    // Select full number on focus so replacing amount is quick.
    $('#paidAmount').on('focus', function() {
        this.select();
    });

    // Add row button
    $('#addRowBtn').on('click', function() {
        addInvoiceRow();
    });

    $('#submitBtn').on('click', function() {
        $('#invoiceStatus').val('FINAL');
        $('#invoiceForm').data('submit-intent', 'FINAL');
    });

    // Reset button
    $('#resetBtn').on('click', function() {
        if (confirm('Are you sure? This will clear all data.')) {
            $('#invoiceForm')[0].reset();
            $('#itemsBody').html(getBlankRowHTML(1));
            rowCount = 1;
            $('#invoiceStatus').val('FINAL');
            $('#invoiceForm').removeData('submit-intent');
            $('#successMessage, #errorMessage, #creditWarningAlert').hide();
            $('#clientDetailsPanel').hide();

            if ($('#clientSelect').hasClass('select2-hidden-accessible')) {
                $('#clientSelect').val('').trigger('change');
            }

            if (!isEditMode) {
                getNextInvoiceNumber();
            }

            $('#paymentTypeSelect').val('');
            $('#paymentMethodSelect').val('');
            paymentAutoSync = false;
            calculateTotals();
        }
    });

    // Save as draft
    $('#saveDraftBtn').on('click', function() {
        $('#invoiceStatus').val('DRAFT');
        $('#invoiceForm').data('submit-intent', 'DRAFT');
        $('#invoiceForm').trigger('submit');
    });

    // Submit form
    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();
        const submitIntent = $(this).data('submit-intent');
        if (submitIntent === 'DRAFT') {
            $('#invoiceStatus').val('DRAFT');
        } else {
            $('#invoiceStatus').val('FINAL');
        }
        $(this).removeData('submit-intent');
        submitInvoice();
    });

    // Preview button
    $('#previewBtn').on('click', function() {
        openInvoicePreviewModal();
    });

    // Initialize visible totals and payment state from hidden values before row edits.
    if (isEditMode) {
        const seededSubtotal = parseFloat($('#subtotalValue').val()) || 0;
        const seededDiscount = parseFloat($('#discountAmountValue').val()) || 0;
        const seededGst = parseFloat($('#gstAmountValue').val()) || 0;
        const seededGrand = parseFloat($('#grandTotalValue').val()) || 0;
        const seededDue = parseFloat($('#dueAmountValue').val()) || 0;

        $('#subtotal').val(seededSubtotal.toFixed(2));
        $('#discountAmount').val(seededDiscount.toFixed(2));
        $('#gstAmount').val(seededGst.toFixed(2));
        $('#grandTotal').val(seededGrand.toFixed(2));
        $('#paymentGrandTotal').val(seededGrand.toFixed(2));
        $('#dueAmount').val(seededDue.toFixed(2));
    }

    if (isEditMode && Array.isArray(initialInvoiceItems) && initialInvoiceItems.length > 0) {
        populateEditInvoiceRows(initialInvoiceItems);
    } else {
        calculatePayment();
    }

    // Product search with dropdown
    $(document).on('input', '.product-search', function() {
        const searchTerm = $(this).val().trim();
        const searchInput = $(this);
        const parentDiv = searchInput.closest('div');
        const resultsDiv = parentDiv.find('.search-results');
        const timerRef = searchInput.data('searchTimer');

        if (timerRef) {
            clearTimeout(timerRef);
        }

        if (searchTerm.length < 2) {
            resultsDiv.empty().hide().data('active-index', -1).removeData('anchor-input');
            return;
        }

        resultsDiv.data('anchor-input', searchInput.get(0));
        resultsDiv.html('<div style="padding:10px;color:#666;background:#fff;">Searching...</div>').show();
        positionSearchDropdown(searchInput, resultsDiv);

        const nextTimer = setTimeout(function() {
            $.ajax({
                url: 'php_action/searchProductsInvoice.php',
                type: 'GET',
                data: { q: searchTerm },
                dataType: 'json',
                timeout: 5000,
                success: function(products) {
                    const rows = Array.isArray(products) ? products : [];
                    const ranked = rows
                        .map(product => ({ product, score: fuzzyScoreMedicine(searchTerm, product) }))
                        .filter(entry => entry.score > 0)
                        .sort((left, right) => {
                            if (right.score !== left.score) return right.score - left.score;
                            return (left.product.product_name || '').localeCompare(right.product.product_name || '');
                        })
                        .map(entry => entry.product)
                        .slice(0, 15);

                    if (ranked.length > 0) {
                        renderProductResults(resultsDiv, ranked);
                    } else {
                        fetchFallbackMedicineResults(searchTerm, function(fallbackRows) {
                            const fallbackRanked = fallbackRows
                                .map(product => ({ product, score: fuzzyScoreMedicine(searchTerm, product) }))
                                .filter(entry => entry.score > 0)
                                .sort((left, right) => {
                                    if (right.score !== left.score) return right.score - left.score;
                                    return (left.product.product_name || '').localeCompare(right.product.product_name || '');
                                })
                                .map(entry => entry.product)
                                .slice(0, 15);
                            renderProductResults(resultsDiv, fallbackRanked);
                        });
                    }
                },
                error: function() {
                    fetchFallbackMedicineResults(searchTerm, function(fallbackRows) {
                        const fallbackRanked = fallbackRows
                            .map(product => ({ product, score: fuzzyScoreMedicine(searchTerm, product) }))
                            .filter(entry => entry.score > 0)
                            .sort((left, right) => {
                                if (right.score !== left.score) return right.score - left.score;
                                return (left.product.product_name || '').localeCompare(right.product.product_name || '');
                            })
                            .map(entry => entry.product)
                            .slice(0, 15);

                        if (fallbackRanked.length > 0) {
                            renderProductResults(resultsDiv, fallbackRanked);
                        } else {
                            resultsDiv.html('<div style="padding:10px;color:#d9534f;background:#fff;">Search error</div>').show();
                        }
                    });
                }
            });
        }, 180);

        searchInput.data('searchTimer', nextTimer);
    });

    // Select product from dropdown
    $(document).on('mousedown', '.product-item', function(e) {
        e.preventDefault();
        selectProductOption($(this));
    });

    // Hover highlight
    $(document).on('mouseenter', '.product-item', function() {
        const resultsDiv = $(this).closest('.search-results');
        const index = resultsDiv.find('.product-item').index(this);
        setActiveProductResult(resultsDiv, index);
    });

    // Keyboard navigation for product dropdown
    $(document).on('keydown', '.product-search', function(e) {
        const input = $(this);
        const resultsDiv = input.closest('div').find('.search-results');
        const options = resultsDiv.find('.product-item');

        if (!resultsDiv.is(':visible') || !options.length) {
            return;
        }

        let currentIndex = parseInt(resultsDiv.data('active-index'), 10);
        if (isNaN(currentIndex) || currentIndex < 0) {
            currentIndex = 0;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveProductResult(resultsDiv, currentIndex + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveProductResult(resultsDiv, currentIndex - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const active = options.eq(currentIndex);
            if (active.length) {
                selectProductOption(active);
            }
        } else if (e.key === 'Escape') {
            resultsDiv.hide().data('active-index', -1);
        }
    });

    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.product-search, .search-results, .product-item').length) {
            $('.search-results').hide().data('active-index', -1).removeData('anchor-input');
        }
    });

    $(window).on('scroll resize', function() {
        repositionVisibleProductDropdowns();
    });

    $('.invoice-items-scroll').on('scroll', function() {
        repositionVisibleProductDropdowns();
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
                        const chosenRate = parseFloat(row.find('.rate-input').val()) || 0;
                        
                        plan.forEach((allocation, index) => {
                            if (index === 0) {
                                // Update first row with first batch from plan
                                currentRow.find('.batch-select').val(allocation.batch_id).change();
                                currentRow.find('.quantity-input').val(allocation.allocated_quantity);
                                if (chosenRate > 0) {
                                    currentRow.find('.rate-input').val(chosenRate.toFixed(2));
                                }
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
                                newRow.find('.manufacturer-display').val(row.find('.manufacturer-display').val());
                                newRow.find('.manufacturer-value').val(row.find('.manufacturer-value').val());
                                newRow.find('.pack-display').val(row.find('.pack-display').val());
                                newRow.find('.pack-value').val(row.find('.pack-value').val());
                                newRow.find('.hsn-code').val(row.find('.hsn-code').val());
                                newRow.find('.hsn-value').val(row.find('.hsn-value').val());
                                newRow.find('.gst-input').val(row.find('.gst-input').val());
                                if (chosenRate > 0) {
                                    newRow.find('.rate-input').val(chosenRate.toFixed(2));
                                }
                                
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
                            showInvoicePopup('warning', warningMsg, 'Stock Allocation Warning');
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
        const batchNumber = sel.data('batch-number') || '';
        const expiry = sel.data('expiry');
        const expiryValue = expiry ? String(expiry).substring(0, 10) : '';

        row.find('.available-qty').text(avail.toFixed(2));
        row.find('.mrp-display').val(batchMrp ? parseFloat(batchMrp).toFixed(2) : '0.00');
        row.find('.mrp-value').val(batchMrp || 0);
        row.find('.batch-number-value').val(batchNumber);
        row.find('.expiry-display').val(formatExpiryShort(expiryValue));
        row.find('.expiry-value').val(expiryValue);
        row.find('.ptr-display').val(batchPtr ? parseFloat(batchPtr).toFixed(2) : '0.00');
        row.find('.ptr-value').val(batchPtr || 0);
        enforceRateLimit(row, true);

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
        enforceRateLimit(row, false);
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
                    const clientSearchText = [
                        client.name || '',
                        client.client_code || '',
                        client.contact_phone || '',
                        client.email || '',
                        client.city || '',
                        client.state || '',
                        client.gstin || '',
                        client.pan || ''
                    ].join(' ');

                    const name = escapeHtml(client.name || '');
                    const code = escapeHtml(client.client_code || '');
                    const contact = escapeHtml(client.contact_phone || '');
                    const email = escapeHtml(client.email || '');
                    const billing = escapeHtml(client.billing_address || '');
                    const shipping = escapeHtml(client.shipping_address || '');
                    const city = escapeHtml(client.city || '');
                    const state = escapeHtml(client.state || '');
                    const postal = escapeHtml(client.postal_code || '');
                    const gstin = escapeHtml(client.gstin || '');
                    const pan = escapeHtml(client.pan || '');
                    const dl = escapeHtml(client.drug_licence_no || '');
                    const business = escapeHtml(client.business_type || '');

                    select.append(`
                        <option value="${client.client_id}" 
                            data-name="${name}"
                            data-code="${code}"
                            data-contact="${contact}"
                            data-email="${email}"
                            data-billing="${billing}"
                            data-shipping="${shipping}"
                            data-city="${city}"
                            data-state="${state}"
                            data-postal="${postal}"
                            data-gstin="${gstin}"
                            data-pan="${pan}"
                            data-dl="${dl}"
                            data-business="${business}"
                            data-credit="${client.credit_limit || 0}"
                            data-outstanding="${client.outstanding_balance || 0}"
                            data-search="${escapeHtml(clientSearchText)}">
                            ${name} (${code})
                        </option>
                    `);
                });

                select.select2({
                    width: '100%',
                    allowClear: false,
                    matcher: function(params, data) {
                        const term = $.trim(params.term || '');
                        if (term === '') return data;
                        if (!data.element) return data;

                        const elem = $(data.element);
                        const haystack = [
                            elem.data('name') || '',
                            elem.data('code') || '',
                            elem.data('contact') || '',
                            elem.data('email') || '',
                            elem.data('city') || '',
                            elem.data('state') || '',
                            elem.data('gstin') || '',
                            elem.data('pan') || ''
                        ].join(' ');

                        return fuzzyScoreClient(term, haystack) > 0 ? data : null;
                    },
                    sorter: function(data) {
                        if (!clientSearchTerm) return data;
                        return data.slice().sort(function(a, b) {
                            const aElem = a.element ? $(a.element) : null;
                            const bElem = b.element ? $(b.element) : null;

                            const aHaystack = aElem ? [
                                aElem.data('name') || '',
                                aElem.data('code') || '',
                                aElem.data('contact') || '',
                                aElem.data('email') || '',
                                aElem.data('city') || '',
                                aElem.data('state') || '',
                                aElem.data('gstin') || '',
                                aElem.data('pan') || ''
                            ].join(' ') : (a.text || '');

                            const bHaystack = bElem ? [
                                bElem.data('name') || '',
                                bElem.data('code') || '',
                                bElem.data('contact') || '',
                                bElem.data('email') || '',
                                bElem.data('city') || '',
                                bElem.data('state') || '',
                                bElem.data('gstin') || '',
                                bElem.data('pan') || ''
                            ].join(' ') : (b.text || '');

                            const bScore = fuzzyScoreClient(clientSearchTerm, bHaystack);
                            const aScore = fuzzyScoreClient(clientSearchTerm, aHaystack);
                            if (bScore !== aScore) return bScore - aScore;
                            return (a.text || '').localeCompare(b.text || '');
                        });
                    }
                });

                select.on('select2:open', function() {
                    setTimeout(function() {
                        const searchField = document.querySelector('.select2-container--open .select2-search__field');
                        if (searchField) searchField.focus();
                        focusFirstClientResultOption();
                    }, 0);
                });

                select.on('select2:close', function() {
                    clientSearchTerm = '';
                });

                $(document).off('input.salesClientSearch').on('input.salesClientSearch', '.select2-container--open .select2-search__field', function() {
                    clientSearchTerm = $(this).val() || '';
                    setTimeout(function() {
                        focusFirstClientResultOption();
                    }, 0);
                });

                $(document).off('keydown.salesClientSearchEnter').on('keydown.salesClientSearchEnter', '.select2-container--open .select2-search__field', function(e) {
                    if (e.key !== 'Enter') return;

                    const openContainer = $('.select2-container--open');
                    if (!openContainer.length) return;

                    let target = openContainer.find('.select2-results__option--highlighted').first();
                    if (!target.length) {
                        target = openContainer
                            .find('.select2-results__option[aria-selected]')
                            .not('.select2-results__option--loading')
                            .not('.select2-results__option--disabled')
                            .first();
                    }

                    if (target.length) {
                        e.preventDefault();
                        target.trigger('mouseup');
                    }
                });

                if (isEditMode && initialClientId > 0) {
                    select.val(String(initialClientId)).trigger('change');
                }
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
    const creditExposure = parseFloat($('#dueAmountValue').val()) || 0;
    
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
    if (creditExposure > available) {
        const exceedAmount = creditExposure - available;
        $('#creditWarningText').html(
            `This invoice due (₹${creditExposure.toFixed(2)}) will exceed available credit by ₹${exceedAmount.toFixed(2)}<br>
            <small>Credit Limit: ₹${creditLimit.toFixed(2)} | Outstanding: ₹${outstanding.toFixed(2)} | Available: ₹${available.toFixed(2)}</small>`
        );
        $('#creditWarningAlert').removeClass('alert-info').addClass('alert-warning');
        $('#creditWarningAlert').show();
    } else if (creditExposure > 0) {
        // Show info if credit is sufficient
        const newOutstanding = outstanding + creditExposure;
        $('#creditWarningText').html(
            `Credit Usage OK<br>
            <small>After this invoice: Outstanding will be ₹${newOutstanding.toFixed(2)} (Limit: ₹${creditLimit.toFixed(2)})</small>`
        );
        $('#creditWarningAlert').removeClass('alert-warning').addClass('alert-info').show();
    } else {
        $('#creditWarningAlert').hide();
    }
}

function formatExpiryShort(dateValue) {
    if (!dateValue) return '';
    const d = new Date(dateValue);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-IN', { month: 'short', year: '2-digit' });
}

function enforceRateLimit(row, shouldReport) {
    const rateInput = row.find('.rate-input');
    if (!rateInput.length) return true;

    const mrpValue = parseFloat(row.find('.mrp-value').val()) || 0;
    const rateValue = parseFloat(rateInput.val()) || 0;

    if (mrpValue > 0) {
        rateInput.attr('max', mrpValue.toFixed(2));
    } else {
        rateInput.attr('max', '');
    }

    if (mrpValue > 0 && rateValue > mrpValue) {
        rateInput.val(mrpValue.toFixed(2));
        rateInput[0].setCustomValidity('Rate cannot exceed MRP');
        if (shouldReport) {
            rateInput[0].reportValidity();
        }
        setTimeout(function() {
            rateInput[0].setCustomValidity('');
        }, 1200);
        return false;
    }

    rateInput[0].setCustomValidity('');
    return true;
}

function fetchProductDetails(productId, row, onDone, options) {
    console.log('fetchProductDetails called with productId:', productId);
    const config = options || {};
    const autoSelectFirstBatch = config.autoSelectFirstBatch !== false;

    $.ajax({
        url: 'php_action/fetchProductInvoice.php',
        type: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            console.log('fetchProductInvoice response:', response);

            if (response.success) {
                const product = response.data.product || {};
                const batches = response.data.batches || [];
                const batchSelect = row.find('.batch-select');
                row.find('.manufacturer-display').val(product.brand_name || row.find('.manufacturer-display').val() || '');
                row.find('.manufacturer-value').val(product.brand_name || row.find('.manufacturer-value').val() || '');
                row.find('.pack-display').val(product.pack_size || row.find('.pack-display').val() || '');
                row.find('.pack-value').val(product.pack_size || row.find('.pack-value').val() || '');
                row.data('fefoBatches', batches);
                batchSelect.empty();
                batchSelect.append('<option value="">--Select Batch--</option>');

                if (batches.length === 0) {
                    console.warn('No batches found for product:', productId);
                    batchSelect.append('<option disabled>No stock available</option>');
                } else {
                    batches.forEach(batch => {
                        const expiry = formatExpiryShort(batch.expiry_date);
                        batchSelect.append(`
                            <option value="${batch.batch_id}" 
                                data-available="${batch.available_quantity}" 
                                data-mrp="${batch.mrp}" 
                                data-ptr="${batch.purchase_rate}"
                                data-batch-number="${batch.batch_number || ''}"
                                data-expiry="${batch.expiry_date}">
                                ${batch.batch_number} (Exp: ${expiry}, Qty: ${batch.available_quantity})
                            </option>
                        `);
                    });

                    // Auto-select first batch (FIFO - earliest expiry)
                    if (batches.length > 0 && autoSelectFirstBatch) {
                        batchSelect.val(batches[0].batch_id).change();
                        console.log('Auto-selected batch:', batches[0].batch_id);
                    }
                }
                renderFefoExplain(row);
                if (typeof onDone === 'function') {
                    onDone(response);
                }
            } else {
                console.error('fetchProductInvoice failed:', response.message);
                row.find('.batch-select').empty().append('<option value="">Error loading batches</option>');
                row.find('.fefo-explain').text('FEFO preview unavailable.');
                if (typeof onDone === 'function') {
                    onDone(null);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching product details:', {status: status, error: error, xhr: xhr});
            row.find('.batch-select').empty().append('<option value="">Error loading batches</option>');
            row.find('.fefo-explain').text('FEFO preview unavailable.');
            if (typeof onDone === 'function') {
                onDone(null);
            }
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
    enforceRateLimit(row, false);

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

function isDraftInvoice() {
    return String($('#invoiceStatus').val() || 'FINAL').toUpperCase() === 'DRAFT';
}

function syncPaidAmountToGrandTotal(forceApply) {
    const paymentType = $('#paymentTypeSelect').val();
    const grandTotal = parseFloat($('#grandTotalValue').val()) || 0;
    const paidRaw = $('#paidAmount').val();
    const paidAmount = parseFloat(paidRaw);

    if (isDraftInvoice() || paymentType !== 'Cash') {
        return;
    }

    if (forceApply || paymentAutoSync || paidRaw === '' || !Number.isFinite(paidAmount)) {
        $('#paidAmount').val(grandTotal.toFixed(2));
        paymentAutoSync = true;
    }
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
    $('#paymentGrandTotal').val(grandTotal.toFixed(2));

    if (paymentAutoSync) {
        syncPaidAmountToGrandTotal(false);
    }

    calculatePayment();
}

function calculatePayment() {
    const grandTotal = parseFloat($('#grandTotalValue').val()) || 0;
    let paidAmount = parseFloat($('#paidAmount').val()) || 0;

    if (paidAmount < 0) {
        paidAmount = 0;
        $('#paidAmount').val('0.00');
    }

    if (paidAmount > grandTotal && grandTotal > 0) {
        paidAmount = grandTotal;
        $('#paidAmount').val(grandTotal.toFixed(2));
    }

    const dueAmount = Math.max(0, grandTotal - paidAmount);

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
                    <div class="search-results"></div>
                </div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm manufacturer-display" readonly />
                <input type="hidden" class="manufacturer-value" name="manufacturer_snapshot[]" />
            </td>
            <td>
                <input type="text" class="form-control form-control-sm pack-display text-center" readonly />
                <input type="hidden" class="pack-value" name="pack_size_snapshot[]" />
            </td>
            <td>
                <input type="text" class="form-control form-control-sm hsn-code text-center" readonly data-row="${rowNum}" />
                <input type="hidden" class="hsn-value" name="hsn_code[]" />
            </td>
            <td>
                <select class="form-control form-control-sm batch-select" name="batch_id[]" data-row="${rowNum}">
                    <option value="">--Select--</option>
                </select>
                <input type="hidden" class="batch-number-value" name="batch_number[]" />
                <small class="fefo-explain">FEFO preview will appear after qty entry.</small>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm expiry-display text-center" readonly />
                <input type="hidden" class="expiry-value" name="expiry_date[]" />
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
            <td class="ptr-column" style="background-color: #fff3e0;">
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

function hydrateInvoiceRowForEdit(row, itemData, onDone) {
    const productId = parseInt(itemData.product_id || 0, 10);
    const batchId = parseInt(itemData.batch_id || 0, 10);
    const quantity = parseFloat(itemData.quantity) || 0;
    const rate = parseFloat(itemData.rate) || 0;
    const gstRate = Number.isFinite(parseFloat(itemData.gst_rate)) ? parseFloat(itemData.gst_rate) : 0;
    const lineDiscount = Number.isFinite(parseFloat(itemData.line_discount)) ? parseFloat(itemData.line_discount) : 0;
    const lineTotal = Number.isFinite(parseFloat(itemData.line_total)) ? parseFloat(itemData.line_total) : 0;
    const batchNumber = (itemData.batch_number || '').toString();
    const expiryDate = (itemData.expiry_date || '').toString().substring(0, 10);
    const ptrValue = Number.isFinite(parseFloat(itemData.ptr)) ? parseFloat(itemData.ptr) : 0;
    const mrpValue = Number.isFinite(parseFloat(itemData.mrp)) ? parseFloat(itemData.mrp) : 0;

    row.find('.product-search').val(itemData.product_name || '');
    row.find('.product-id').val(productId > 0 ? productId : '');
    row.find('.manufacturer-display').val(itemData.brand_name || '');
    row.find('.manufacturer-value').val(itemData.brand_name || '');
    row.find('.pack-display').val(itemData.pack_size || '');
    row.find('.pack-value').val(itemData.pack_size || '');
    row.find('.hsn-code').val(itemData.hsn_code || '');
    row.find('.hsn-value').val(itemData.hsn_code || '');
    row.find('.quantity-input').val(quantity > 0 ? quantity : '');
    row.find('.rate-input').val(rate > 0 ? rate.toFixed(2) : '');
    row.find('.gst-input').val(gstRate);
    row.find('.discount-input').val(lineDiscount);
    row.find('.batch-number-value').val(batchNumber);
    row.find('.expiry-display').val(formatExpiryShort(expiryDate));
    row.find('.expiry-value').val(expiryDate);
    row.find('.mrp-display').val(mrpValue > 0 ? mrpValue.toFixed(2) : '0.00');
    row.find('.mrp-value').val(mrpValue > 0 ? mrpValue.toFixed(2) : '0.00');
    row.find('.ptr-display').val(ptrValue > 0 ? ptrValue.toFixed(2) : '0.00');
    row.find('.ptr-value').val(ptrValue > 0 ? ptrValue.toFixed(2) : '0.00');
    row.find('.total-display').val(lineTotal > 0 ? lineTotal.toFixed(2) : '0.00');
    row.find('.total-value').val(lineTotal > 0 ? lineTotal.toFixed(2) : '0.00');

    if (productId <= 0) {
        if (typeof onDone === 'function') {
            onDone();
        }
        return;
    }

    fetchProductDetails(productId, row, function() {
        const batchSelect = row.find('.batch-select');

        if (batchId > 0) {
            if (!batchSelect.find(`option[value="${batchId}"]`).length) {
                const savedLabel = batchNumber !== '' ? batchNumber : ('Batch #' + batchId);
                batchSelect.append(
                    `<option value="${batchId}" data-available="0" data-mrp="${mrpValue.toFixed(2)}" data-ptr="${ptrValue.toFixed(2)}" data-batch-number="${escapeHtml(batchNumber)}" data-expiry="${escapeHtml(expiryDate)}">${escapeHtml(savedLabel)} (Saved)</option>`
                );
            }
            batchSelect.val(String(batchId)).trigger('change');
        } else {
            batchSelect.val('');
            row.find('.available-qty').text('-');
            row.find('.batch-number-value').val(batchNumber);
            row.find('.expiry-display').val(formatExpiryShort(expiryDate));
            row.find('.expiry-value').val(expiryDate);
            row.find('.mrp-display').val(mrpValue > 0 ? mrpValue.toFixed(2) : '0.00');
            row.find('.mrp-value').val(mrpValue > 0 ? mrpValue.toFixed(2) : '0.00');
            row.find('.ptr-display').val(ptrValue > 0 ? ptrValue.toFixed(2) : '0.00');
            row.find('.ptr-value').val(ptrValue > 0 ? ptrValue.toFixed(2) : '0.00');
        }

        row.find('.quantity-input').val(quantity > 0 ? quantity : '');
        row.find('.rate-input').val(rate > 0 ? rate.toFixed(2) : '');
        row.find('.gst-input').val(gstRate);
        row.find('.discount-input').val(lineDiscount);

        calculateLineTotalRow(row);

        if (typeof onDone === 'function') {
            onDone();
        }
    }, { autoSelectFirstBatch: batchId > 0 });
}

function populateEditInvoiceRows(items) {
    if (!Array.isArray(items) || items.length === 0) {
        calculatePayment();
        return;
    }

    $('#itemsBody').empty();
    rowCount = 0;

    let pending = 0;
    let inserted = 0;

    items.forEach(function(item) {
        const productId = parseInt(item.product_id || 0, 10);
        const quantity = parseFloat(item.quantity) || 0;
        const rate = parseFloat(item.rate) || 0;

        if (productId <= 0 || quantity <= 0 || rate <= 0) {
            return;
        }

        addInvoiceRow();
        inserted++;
        pending++;

        const row = $('#itemsBody tr:last');
        hydrateInvoiceRowForEdit(row, item, function() {
            pending--;
            if (pending <= 0) {
                calculateTotals();
            }
        });
    });

    if (inserted === 0) {
        addInvoiceRow();
        calculatePayment();
    }
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
    const submitMode = (String($('#invoiceStatus').val() || 'FINAL')).toUpperCase();
    const isDraftSubmit = (submitMode === 'DRAFT');

    const clientId = $('#clientSelect').val();
    if (!clientId) {
        showInvoicePopup('warning', 'Please select a client.', 'Validation');
        return;
    }

    const itemCount = $('.item-row').length;
    if (!isDraftSubmit && itemCount === 0) {
        showInvoicePopup('warning', 'Please add at least one item.', 'Validation');
        return;
    }

    let hasValidItems = false;
    let hasInvalidRate = false;
    let hasMissingBatch = false;
    $('.item-row').each(function() {
        const row = $(this);
        const productId = row.find('.product-id').val();
        const batchId = parseInt(row.find('.batch-select').val() || '0', 10);
        const qty = parseFloat(row.find('.quantity-input').val()) || 0;
        const rate = parseFloat(row.find('.rate-input').val()) || 0;
        const mrp = parseFloat(row.find('.mrp-value').val()) || 0;

        if (productId && qty > 0 && rate > 0) {
            hasValidItems = true;
        }

        if (!isDraftSubmit && productId && qty > 0 && batchId <= 0) {
            hasMissingBatch = true;
            row.find('.batch-select').focus();
            return false;
        }

        if (!isDraftSubmit && productId && qty > 0 && mrp > 0 && rate > mrp) {
            hasInvalidRate = true;
            enforceRateLimit(row, true);
            row.find('.rate-input').focus();
            return false;
        }
    });

    if (!isDraftSubmit && !hasValidItems) {
        showInvoicePopup('warning', 'Please add at least one item with product, quantity, and rate.', 'Validation');
        return;
    }

    if (!isDraftSubmit && hasMissingBatch) {
        showInvoicePopup('warning', 'Please select a batch for each item before creating the invoice.', 'Validation');
        return;
    }

    if (!isDraftSubmit && hasInvalidRate) {
        showInvoicePopup('warning', 'Rate cannot exceed MRP. Please correct the highlighted row.', 'Validation');
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
                $('#successMessage, #errorMessage').hide();
                showInvoicePopup('success', response.message || 'Invoice saved successfully.', 'Invoice Saved');

                setTimeout(function() {
                    window.location.href = 'sales_invoice_list.php';
                }, 1500);
            } else {
                $('#successMessage, #errorMessage').hide();
                showInvoicePopup('error', response.message || 'Unable to save invoice.', 'Invoice Not Saved');
            }
        },
        error: function(xhr, status, error) {
            let msg = 'Error: ' + error;
            if (xhr.responseText) {
                msg += '\n' + xhr.responseText;
            }
            $('#successMessage, #errorMessage').hide();
            showInvoicePopup('error', msg, 'Request Failed');
        }
    });
}
</script>

