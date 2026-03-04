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
$res = $connect->query("SELECT p.product_id, p.product_name, p.hsn_code, p.gst_rate, p.content, p.pack_size, COALESCE(b.brand_name, '') AS brand_name FROM product p LEFT JOIN brands b ON b.brand_id = p.brand_id WHERE p.status=1 ORDER BY p.product_name");
if ($res) while ($r = $res->fetch_assoc()) $products[] = $r;

function getNextEntryReference($connect, $baseDate = null)
{
    $dateObj = DateTime::createFromFormat('Y-m-d', (string) $baseDate);
    if (!$dateObj) {
        $dateObj = new DateTime();
    }

    $yy = $dateObj->format('y');
    $mm = $dateObj->format('m');
    $prefix = 'PIE-' . $yy . '-' . $mm . '-';
    $like = $prefix . '%';
    $maxSeq = 0;

    $stmt = $connect->prepare("SELECT MAX(CAST(SUBSTRING(invoice_no, 11, 5) AS UNSIGNED)) AS max_seq FROM purchase_invoices WHERE invoice_no LIKE ?");
    if ($stmt) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            $maxSeq = intval($row['max_seq'] ?? 0);
        }
        $stmt->close();
    }

    return $prefix . str_pad((string) ($maxSeq + 1), 5, '0', STR_PAD_LEFT);
}

$entryReference = getNextEntryReference($connect, date('Y-m-d'));
$quickCreatedName = isset($_GET['created_name']) ? trim((string) $_GET['created_name']) : '';
$quickSupplierId = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row page-titles mb-2">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-file-invoice"></i> Purchase Invoice Entry</h3>
                <small class="text-muted">Supplier Bill Entry & Stock Posting Workflow</small>
            </div>
            <div class="col-md-4 align-self-center">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Purchase Invoice</li>
                </ol>
            </div>
        </div>

        <form id="invoiceForm">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-hashtag"></i> Entry Reference</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8 col-md-7">
                            <label class="form-label"><strong>Entry Reference No. <span class="text-danger">*</span></strong></label>
                            <input type="text" name="invoice_no" id="invoice_no" class="form-control form-control-md" value="<?=htmlspecialchars($entryReference)?>" readonly required>
                            <small class="text-muted">System-generated reference for this purchase entry.</small>
                        </div>
                        <div class="col-lg-4 col-md-5">
                            <label class="form-label"><strong>Entry Date <span class="text-danger">*</span></strong></label>
                            <input type="date" name="invoice_date" id="invoice_date" class="form-control form-control-md" value="<?=date('Y-m-d')?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-lg-9 col-md-10">
                            <label class="form-label"><strong>Supplier Selection <span class="text-danger">*</span></strong></label>
                            <select name="supplier_id" id="supplier_id" class="form-control form-control-md" required style="width: 100%;">
                                <option value="">-- Search Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?=htmlspecialchars($s['supplier_id'])?>"><?=htmlspecialchars($s['supplier_name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

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

            <div id="entrySections">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fa fa-file-alt"></i> Supplier Bill Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><strong>Supplier Invoice No. <span class="text-danger">*</span></strong></label>
                                <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" class="form-control form-control-md" placeholder="As printed on supplier bill" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>Supplier Invoice Date <span class="text-danger">*</span></strong></label>
                                <input type="date" name="supplier_invoice_date" id="supplier_invoice_date" class="form-control form-control-md" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>Due Date</strong></label>
                                <input type="date" name="due_date" id="due_date" class="form-control form-control-md">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Place of Supply</strong></label>
                                <input type="text" name="place_of_supply" id="place_of_supply" class="form-control form-control-md" value="Gujarat" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>GST Type <span class="text-danger">*</span></strong></label>
                                <select name="gst_type" id="gst_type" class="form-control form-control-md" required>
                                    <option value="">-- Select GST Type --</option>
                                    <option value="intrastate">Intra-State (CGST + SGST)</option>
                                    <option value="interstate">Inter-State (IGST)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Payment Terms</strong></label>
                                <input type="text" name="payment_terms" id="payment_terms" class="form-control form-control-md" placeholder="Net 30">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>PO Reference</strong></label>
                                <input type="text" name="po_reference" id="po_reference" class="form-control form-control-md" placeholder="Optional">
                            </div>
                            <div class="col-12 mt-2">
                                <hr class="my-1">
                                <h6 class="text-primary mb-2"><i class="fa fa-truck"></i> Transport Details (Optional)</h6>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>L.R. No.</strong></label>
                                <input type="text" name="lr_no" id="lr_no" class="form-control form-control-md" placeholder="Transport LR No.">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>L.R. Date</strong></label>
                                <input type="date" name="lr_date" id="lr_date" class="form-control form-control-md">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Carrier Name</strong></label>
                                <input type="text" name="carrier_name" id="carrier_name" class="form-control form-control-md" placeholder="Transport company">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Vehicle No.</strong></label>
                                <input type="text" name="vehicle_no" id="vehicle_no" class="form-control form-control-md" placeholder="Vehicle number">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>F. Slip No.</strong></label>
                                <input type="text" name="f_slip_no" id="f_slip_no" class="form-control form-control-md" placeholder="F. Slip reference">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>E-Way Bill No.</strong></label>
                                <input type="text" name="eway_bill_no" id="eway_bill_no" class="form-control form-control-md" placeholder="E-way bill number">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>E-Way Bill Date</strong></label>
                                <input type="date" name="eway_bill_date" id="eway_bill_date" class="form-control form-control-md">
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
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0" id="itemsTable" style="font-size: 0.9rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:17%; min-width:220px;">Medicine</th>
                                    <th style="width:6%; min-width:110px;">HSN</th>
                                    <th style="width:8%; min-width:100px;">Batch</th>
                                    <th style="width:7%; min-width:90px;">Expiry</th>
                                    <th style="width:6%; min-width:80px;">Qty</th>
                                    <th style="width:6%; min-width:70px;">Free</th>
                                    <th style="width:8%; min-width:100px;">Purchase Rate</th>
                                    <th style="width:7%; min-width:75px;">MRP</th>
                                    <th style="width:6%; min-width:70px;">Disc%</th>
                                    <th style="width:6%; min-width:70px;">GST%</th>
                                    <th style="width:8%; min-width:100px;" class="text-end">Taxable</th>
                                    <th style="width:8%; min-width:90px;" class="text-end">GST Amt</th>
                                    <th style="width:8%; min-width:90px;" class="text-end">Line Total</th>
                                    <th style="width:4%; min-width:45px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>

                <!-- Summary Section - Horizontal Layout -->
                <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fa fa-calculator"></i> Invoice Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3" style="font-size: 0.95rem;">
                        <!-- Subtotal -->
                        <div class="col-lg-2 col-md-3 col-sm-4 border-end pe-3">
                            <div class="text-muted small">Subtotal</div>
                            <div class="h5 text-dark mb-0" id="subtotal">₹ 0.00</div>
                        </div>
                        <!-- Discount -->
                        <div class="col-lg-2 col-md-3 col-sm-4 border-end pe-3">
                            <div class="text-muted small">Discount</div>
                            <div class="h5 text-danger mb-0" id="total_discount">₹ 0.00</div>
                        </div>
                        <!-- Taxable -->
                        <div class="col-lg-2 col-md-3 col-sm-4 border-end pe-3">
                            <div class="text-muted small">Taxable</div>
                            <div class="h5 text-dark mb-0" id="taxable_value">₹ 0.00</div>
                        </div>
                        <!-- GST/CGST/SGST/IGST -->
                        <div class="col-lg-2 col-md-3 col-sm-4 border-end pe-3">
                            <div class="text-muted small">Tax</div>
                            <div id="gst_details" style="display:none;">
                                <div class="small">CGST: <span id="total_cgst">0.00</span></div>
                                <div class="small">SGST: <span id="total_sgst">0.00</span></div>
                            </div>
                            <div id="igst_details" style="display:none;">
                                <div class="small">IGST: <span id="total_igst">0.00</span></div>
                            </div>
                        </div>
                        <!-- Grand Total -->
                        <div class="col-lg-2 col-md-3 col-sm-4">
                            <div class="text-muted small">Grand Total</div>
                            <div class="h5 text-success font-weight-bold mb-0" id="grand_total">₹ 0.00</div>
                        </div>
                    </div>
                    
                    <!-- Charges and Payment Section -->
                    <hr class="my-3">
                    <div class="row g-3">
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Freight</strong></label>
                            <input type="number" step="0.01" id="freight" name="freight" class="form-control form-control-sm" placeholder="0.00" value="0">
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Round Off</strong></label>
                            <input type="number" step="0.01" id="round_off" name="round_off" class="form-control form-control-sm" placeholder="0.00" value="0">
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Payment Status</strong></label>
                            <select id="payment_status" name="payment_status" class="form-control form-control-sm">
                                <option value="UNPAID">Unpaid / Credit</option>
                                <option value="PAID">Fully Paid</option>
                                <option value="PARTIAL">Partially Paid</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Payment Mode</strong></label>
                            <select id="payment_mode" name="payment_mode" class="form-control form-control-sm" disabled>
                                <option value="">-- Select --</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Amount Paid</strong></label>
                            <input type="number" step="0.01" id="paid_amount" name="paid_amount" class="form-control form-control-sm" placeholder="0.00" value="0" readonly>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small mb-1"><strong>Outstanding</strong></label>
                            <div class="h5 text-warning font-weight-bold" id="outstanding_amount">₹ 0.00</div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Notes Section -->
                <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-sticky-note"></i> Notes & Terms</h5>
                </div>
                <div class="card-body">
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Invoice notes, terms & conditions, etc."></textarea>
                </div>
                </div>

                <div class="row mt-4 mb-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success btn-lg me-2">
                            <i class="fa fa-save"></i> Save as Draft
                        </button>
                        <button type="button" id="finalSubmitBtn" class="btn btn-primary btn-lg me-2">
                            <i class="fa fa-check"></i> Final Submission
                        </button>
                        <a href="invoice_list.php" class="btn btn-secondary btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script>
    const products = <?php echo json_encode($products); ?>;
    const COMPANY_STATE = 'Gujarat'; // Auto-detect from DB in production
    const QUICK_CREATED_NAME = <?php echo json_encode($quickCreatedName); ?>;
    const QUICK_SUPPLIER_ID = <?php echo json_encode($quickSupplierId); ?>;
    let pendingQuickCreatedName = QUICK_CREATED_NAME;

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

    function fuzzyScoreProduct(term, product) {
        const query = normalizeText(term);
        if (!query) return 0;

        const name = normalizeText(product.product_name);
        const brand = normalizeText(product.brand_name || '');
        const content = normalizeText(product.content || '');
        const hsn = normalizeText(product.hsn_code || '');
        const idText = (product.product_id || '').toString();
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

    function fuzzyScoreSupplier(term, label) {
        const query = normalizeText(term);
        if (!query) return 0;

        const text = normalizeText(label || '');
        const acronym = normalizeText(acronymFromText(label || ''));

        if (text === query) return 1000;
        if (text.startsWith(query)) return 900;
        if (text.includes(query)) return 760;
        if (acronym.startsWith(query)) return 640;
        if (isSubsequence(query, text)) return 540;
        return 0;
    }

    function toggleEntrySections(isEnabled) {
        const section = $('#entrySections');
        section.find(':input').prop('disabled', !isEnabled);
        section.css({
            opacity: isEnabled ? 1 : 0.55,
            pointerEvents: isEnabled ? 'auto' : 'none'
        });
    }

    function escapeHtml(value) {
        return (value || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderProductSuggestions(suggest, results, queryTerm) {
        if (!results || results.length === 0) {
            const supplierId = encodeURIComponent($('#supplier_id').val() || '');
            const quickUrl = 'add_medicine.php?prefill_name=' + encodeURIComponent(queryTerm) + '&return_to=' + encodeURIComponent('purchase_invoice.php') + '&supplier_id=' + supplierId;
            suggest.html(
                '<div style="padding:8px 10px; color:#666; border-bottom:1px solid #eee;">No products found</div>' +
                '<div style="padding:8px 10px;">' +
                    '<a href="' + quickUrl + '" class="btn btn-sm btn-outline-primary quick-create-variant" data-term="' + escapeHtml(queryTerm) + '">Quick Add Missing Variant</a>' +
                '</div>'
            ).show().data('active-index', -1);
            return;
        }

        const html = results.slice(0, 12).map(p => {
            const brand = p.brand_name ? (' | Mfg: ' + escapeHtml(p.brand_name)) : '';
            const pack = p.pack_size ? (' | Pack: ' + escapeHtml(p.pack_size)) : '';
            const content = p.content ? (' | ' + escapeHtml(p.content)) : '';
            return `<div class="product-option" data-product-id="${p.product_id}" data-product-name="${escapeHtml(p.product_name)}" data-hsn-code="${escapeHtml(p.hsn_code || '')}" data-gst-rate="${p.gst_rate || 0}" data-pack-size="${escapeHtml(p.pack_size || '')}" data-manufacturer="${escapeHtml(p.brand_name || '')}" style="padding:8px 10px; cursor:pointer; border-bottom:1px solid #eee;">
                <strong>${escapeHtml(p.product_name)}</strong>
                <span style="color:#6b7280; font-size:0.85em;">${brand}${pack}${content}</span><br>
                <small style="color:#999;">ID:${p.product_id} | GST:${p.gst_rate || 0}% | HSN:${escapeHtml(p.hsn_code || '-')}</small>
            </div>`;
        }).join('');

        suggest.html(html).show();
        setActiveProductSuggestion(suggest, 0);
    }

    function setActiveProductSuggestion(suggest, index) {
        const options = suggest.find('.product-option');
        if (!options.length) {
            suggest.data('active-index', -1);
            return;
        }

        const safeIndex = Math.max(0, Math.min(index, options.length - 1));
        options.removeClass('active-product-option').css({ backgroundColor: '#fff' });

        const active = options.eq(safeIndex);
        active.addClass('active-product-option').css({ backgroundColor: '#eef6ff' });
        suggest.data('active-index', safeIndex);

        const activeTop = active.position().top;
        const activeBottom = activeTop + active.outerHeight();
        const currentScroll = suggest.scrollTop();
        const visibleHeight = suggest.innerHeight();

        if (activeBottom > visibleHeight) {
            suggest.scrollTop(currentScroll + (activeBottom - visibleHeight));
        } else if (activeTop < 0) {
            suggest.scrollTop(currentScroll + activeTop);
        }
    }

    function selectProductSuggestion(option) {
        const row = option.closest('tr');
        row.find('.product_id').val(option.data('product-id'));
        row.find('.product_name').val(option.data('product-name'));
        row.find('.hsn_code').val(option.data('hsn-code'));
        row.find('.gst_percent').val(option.data('gst-rate') || 0);
        row.find('.pack_size_snapshot').val(option.data('pack-size') || '');
        row.find('.manufacturer_snapshot').val(option.data('manufacturer') || '');

        row.find('.product_suggest').hide().data('active-index', -1);
        row.find('.auto_filled').remove();
        row.find('.gst_percent').after('<small class="auto_filled text-success ms-1">(auto)</small>');
        recalcTotals();
    }

    function applyQuickCreatedProductIfAny() {
        if (!pendingQuickCreatedName) return;
        if (!$('#supplier_id').val()) return;

        let targetRow = null;
        $('#itemsTable tbody tr').each(function() {
            const value = ($(this).find('.product_name').val() || '').trim();
            if (!value && !targetRow) {
                targetRow = $(this);
            }
        });

        if (!targetRow) {
            addEmptyRow();
            targetRow = $('#itemsTable tbody tr:last');
        }

        const input = targetRow.find('.product_name');
        input.val(pendingQuickCreatedName).focus();
        input.trigger('input');

        setTimeout(function() {
            const suggest = targetRow.find('.product_suggest');
            const first = suggest.find('.product-option').first();
            if (first.length) {
                setActiveProductSuggestion(suggest, 0);
                selectProductSuggestion(first);
                pendingQuickCreatedName = '';
            }
        }, 260);
    }

    function refreshEntryReference() {
        const dateVal = $('#invoice_date').val();
        $.ajax({
            url: 'php_action/get_next_entry_reference.php',
            method: 'GET',
            data: { date: dateVal },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success && resp.reference) {
                    $('#invoice_no').val(resp.reference);
                }
            }
        });
    }

    // Fetch and display supplier details with auto GST detection
    $('#supplier_id').on('change', function(){
        const supplierId = $(this).val();
        if (supplierId) {
            toggleEntrySections(true);
            $.ajax({
                url: 'php_action/get_supplier_details.php',
                method: 'POST',
                data: { supplier_id: supplierId },
                dataType: 'json',
                success: function(resp){
                    if (resp.success) {
                        const s = resp.data;
                        $('#supplier_company').text(s.company_name || s.supplier_name || '-');
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
                        
                        // Set place of supply
                        $('#place_of_supply').val(supplierState || 'Gujarat');
                        
                        // Auto-fill payment terms based on supplier
                        if (s.payment_terms) $('#payment_terms').val(s.payment_terms);
                        if (s.credit_days) {
                            const dueDate = new Date();
                            dueDate.setDate(dueDate.getDate() + s.credit_days);
                            $('#due_date').val(dueDate.toISOString().split('T')[0]);
                        }
                        
                        $('#supplierDetailsCard').slideDown();
                        applyQuickCreatedProductIfAny();
                        recalcTotals(); // Recalculate if GST type changed
                    }
                },
                error: function(){ $('#supplierDetailsCard').slideUp(); }
            });
        } else {
            toggleEntrySections(false);
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
                <input type="hidden" class="pack_size_snapshot" value="">
                <input type="hidden" class="manufacturer_snapshot" value="">
                <input class="form-control form-control-sm product_name" placeholder="Search..." autocomplete="off">
                <div class="product_suggest" style="display:none; position:absolute; background:#fff; border:1px solid #ddd; max-height:150px; overflow-y:auto; width:250px; z-index:1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
            </td>
            <td><input class="form-control form-control-sm hsn_code" readonly></td>
            <td><input class="form-control form-control-sm batch_no" required></td>
            <td><input type="date" class="form-control form-control-sm expiry_date" required></td>
            <td><input type="number" step="0.001" class="form-control form-control-sm qty" value="0" required></td>
            <td><input type="number" step="0.001" class="form-control form-control-sm free_qty" value="0"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm unit_cost" value="0" required title="Purchase rate from supplier invoice"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm mrp" value="0" required title="Supplier quoted MRP"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm disc" value="0" title="Discount %"></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm gst_percent" value="0" readonly title="Tax rate from product master (auto-populated)"></td>
            <td class="taxable_amount text-end fw-bold">₹ 0.00</td>
            <td class="gst_amount text-end fw-bold">₹ 0.00</td>
            <td class="line_total text-end fw-bold">₹ 0.00</td>
            <td><button type="button" class="btn btn-sm btn-danger remove" title="Remove item"><i class="fa fa-trash"></i></button></td>
        </tr>
        `;
        $('#itemsTable tbody').append(row);
    }

    // Recalculate totals with proper multi-rate GST support
    function recalcTotals(){
        const gstType = $('#gst_type').val();
        const paymentStatus = $('#payment_status').val() || 'UNPAID';
        let subtotal = 0, total_discount = 0, total_cgst = 0, total_sgst = 0, total_igst = 0, total_tax = 0, taxable = 0;
        
        $('#itemsTable tbody tr').each(function(){
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const unit = parseFloat($(this).find('.unit_cost').val()) || 0;
            const discP = parseFloat($(this).find('.disc').val()) || 0;
            const gstP = parseFloat($(this).find('.gst_percent').val()) || 0; // Per-item tax rate
            
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
            $(this).find('.taxable_amount').text('₹ ' + taxableAmt.toFixed(2));
            $(this).find('.gst_amount').text('₹ ' + taxAmt.toFixed(2));
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
        const grand = subtotal - total_discount + total_tax + freight + round_off;

        let paid = parseFloat($('#paid_amount').val()) || 0;
        if (paymentStatus === 'PAID') {
            paid = grand;
            $('#paid_amount').val(grand.toFixed(2));
            $('#paid_amount').prop('readonly', true);
            $('#payment_mode').prop('disabled', false);
        } else if (paymentStatus === 'UNPAID') {
            paid = 0;
            $('#paid_amount').val('0.00');
            $('#paid_amount').prop('readonly', true);
            $('#payment_mode').val('').prop('disabled', true);
        } else {
            if (paid < 0) paid = 0;
            if (paid > grand) paid = grand;
            $('#paid_amount').val(paid.toFixed(2));
            $('#paid_amount').prop('readonly', false);
            $('#payment_mode').prop('disabled', false);
        }

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
        let supplierSearchTerm = '';

        if ($.fn.select2) {
            $('#supplier_id').select2({
                width: '100%',
                placeholder: '-- Search Supplier --',
                allowClear: true,
                matcher: function(params, data) {
                    const term = $.trim(params.term || '');
                    if (term === '') return data;
                    if (!data.text) return null;
                    return fuzzyScoreSupplier(term, data.text) > 0 ? data : null;
                },
                sorter: function(data) {
                    if (!supplierSearchTerm) return data;
                    return data.slice().sort(function(a, b) {
                        const bScore = fuzzyScoreSupplier(supplierSearchTerm, b.text || '');
                        const aScore = fuzzyScoreSupplier(supplierSearchTerm, a.text || '');
                        if (bScore !== aScore) return bScore - aScore;
                        return (a.text || '').localeCompare(b.text || '');
                    });
                }
            });

            $('#supplier_id').on('select2:open', function() {
                setTimeout(function() {
                    const searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                }, 0);
            });

            $(document).on('input', '.select2-container--open .select2-search__field', function() {
                supplierSearchTerm = $(this).val() || '';
            });
        }

        addEmptyRow();
        toggleEntrySections(false);

        if (QUICK_SUPPLIER_ID > 0) {
            $('#supplier_id').val(String(QUICK_SUPPLIER_ID)).trigger('change');
        }

        // Add row button
        $('#addRow').on('click', function(){ addEmptyRow(); });

        // Remove row
        $('#itemsTable').on('click', '.remove', function(){
            $(this).closest('tr').remove();
            recalcTotals();
        });

        // Debounced recalc to improve performance when typing
        let recalcTimer;
        function scheduleRecalc() {
            clearTimeout(recalcTimer);
            recalcTimer = setTimeout(recalcTotals, 100);
        }

        $('#invoice_date').on('change', function() {
            refreshEntryReference();
            scheduleRecalc();
        });
        
        // Recalculate on any input change (debounced)
        $('#itemsTable').on('input', 'input', scheduleRecalc);
        $('#freight, #round_off, #paid_amount, #gst_type, #payment_status').on('input change', scheduleRecalc);
        
        // GST type change should also recalculate (debounced)
        $('#gst_type').on('change', scheduleRecalc);

        $('#payment_status').on('change', function() {
            const status = $(this).val() || 'UNPAID';
            if (status === 'UNPAID') {
                $('#payment_mode').val('').prop('disabled', true);
                $('#paid_amount').val('0.00').prop('readonly', true);
            } else if (status === 'PAID') {
                $('#paid_amount').prop('readonly', true);
                $('#payment_mode').prop('disabled', false);
            } else {
                $('#paid_amount').prop('readonly', false);
                $('#payment_mode').prop('disabled', false);
            }
            scheduleRecalc();
        });

        // Product autocomplete with fuzzy local + backend fallback
        $('#itemsTable').on('input', '.product_name', function(){
            const val = $(this).val().trim();
            const row = $(this).closest('tr');
            const suggest = row.find('.product_suggest');
            
            if (val.length < 2) {
                suggest.hide();
                return;
            }

            const localMatches = products
                .map(product => ({ product, score: fuzzyScoreProduct(val, product) }))
                .filter(entry => entry.score > 0)
                .sort((left, right) => {
                    if (right.score !== left.score) return right.score - left.score;
                    return (left.product.product_name || '').localeCompare(right.product.product_name || '');
                })
                .map(entry => entry.product)
                .slice(0, 12);

            if (localMatches.length > 0) {
                renderProductSuggestions(suggest, localMatches, val);
                return;
            }

            suggest.html('<div style="padding:8px 10px; color:#666;">Searching...</div>').show();
            if (val.length >= 2) {
                $.ajax({
                    url: 'php_action/searchMedicines.php',
                    method: 'GET',
                    data: { q: val },
                    dataType: 'json',
                    success: function(results) {
                        renderProductSuggestions(suggest, Array.isArray(results) ? results : [], val);
                    },
                    error: function() {
                        suggest.html('<div style="padding:8px 10px; color:#999;">Search error</div>').show();
                    }
                });
            }
        });

        // Product selection - with auto-fetch of tax rate
        $('#itemsTable').on('click', '.product-option', function(){
            selectProductSuggestion($(this));
        });

        $('#itemsTable').on('mouseenter', '.product-option', function(){
            const suggest = $(this).closest('.product_suggest');
            const index = suggest.find('.product-option').index(this);
            setActiveProductSuggestion(suggest, index);
        });

        $('#itemsTable').on('keydown', '.product_name', function(e){
            const row = $(this).closest('tr');
            const suggest = row.find('.product_suggest');
            const options = suggest.find('.product-option');

            if (!suggest.is(':visible') || !options.length) {
                return;
            }

            let currentIndex = parseInt(suggest.data('active-index'), 10);
            if (isNaN(currentIndex) || currentIndex < 0) {
                currentIndex = 0;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActiveProductSuggestion(suggest, currentIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActiveProductSuggestion(suggest, currentIndex - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const active = options.eq(currentIndex);
                if (active.length) {
                    selectProductSuggestion(active);
                }
            } else if (e.key === 'Escape') {
                suggest.hide().data('active-index', -1);
            }
        });

        // Hide suggest on outside click
        $(document).on('click', function(e){
            if (!$(e.target).closest('.product_name, .product_suggest, .product-option, .quick-create-variant').length) {
                $('.product_suggest').hide();
            }
        });

        $(document).on('click', '.quick-create-variant', function(){
            $('.product_suggest').hide();
        });

        // Form submission
        $('#invoiceForm').on('submit', function(e){
            e.preventDefault();
            submitInvoice('Draft');
        });

        $('#finalSubmitBtn').on('click', function(){
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

        const invoiceDateVal = $('#invoice_date').val();
        const supplierInvoiceDateVal = $('#supplier_invoice_date').val();
        const dueDateVal = $('#due_date').val();

        if (supplierInvoiceDateVal && invoiceDateVal && new Date(supplierInvoiceDateVal) > new Date(invoiceDateVal)) {
            alert('Supplier invoice date cannot be after entry date');
            return;
        }
        if (dueDateVal && invoiceDateVal && new Date(dueDateVal) < new Date(invoiceDateVal)) {
            alert('Due date cannot be before entry date');
            return;
        }
        const ewayDateVal = $('#eway_bill_date').val();
        const ewayNoVal = ($('#eway_bill_no').val() || '').trim();
        if (ewayDateVal && invoiceDateVal && new Date(ewayDateVal) < new Date(invoiceDateVal)) {
            alert('E-Way bill date cannot be before entry date');
            return;
        }
        if (ewayNoVal && !ewayDateVal) {
            alert('Please enter E-Way bill date when E-Way bill number is provided');
            return;
        }

        const paymentStatus = $('#payment_status').val() || 'UNPAID';
        const paymentMode = $('#payment_mode').val();
        const grandTotal = parseFloat($('#grand_total').text().replace('₹ ', '')) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;

        if ((paymentStatus === 'PAID' || paymentStatus === 'PARTIAL') && !paymentMode) {
            alert('Please select payment mode for paid or partial payment');
            return;
        }
        if (paymentStatus === 'PAID' && Math.abs(paidAmount - grandTotal) > 0.01) {
            alert('For Fully Paid status, paid amount must match grand total');
            return;
        }
        if (paymentStatus === 'PARTIAL' && (paidAmount <= 0 || paidAmount >= grandTotal)) {
            alert('For Partially Paid status, paid amount must be greater than 0 and less than grand total');
            return;
        }
        if (paymentStatus === 'UNPAID' && paidAmount !== 0) {
            alert('For Unpaid/Credit status, paid amount must be 0');
            return;
        }

        const gstType = $('#gst_type').val();
        const items = [];
        let formValid = true;
        const duplicateTracker = {};
        
        $('#itemsTable tbody tr').each(function(){
            const p = ($(this).find('.product_name').val() || '').trim();
            if (!p) return; // Skip empty rows

            const productId = parseInt($(this).find('.product_id').val(), 10) || 0;
            
            const batch = (($(this).find('.batch_no').val() || '') + '').trim();
            const expiry = $(this).find('.expiry_date').val();
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const freeQty = parseFloat($(this).find('.free_qty').val()) || 0;
            const unit = parseFloat($(this).find('.unit_cost').val()) || 0;
            const mrp = parseFloat($(this).find('.mrp').val()) || 0;
            const discP = parseFloat($(this).find('.disc').val()) || 0;
            const gstP = parseFloat($(this).find('.gst_percent').val()) || 0;
            
            // Validations
            if (productId <= 0) {
                alert('Please select medicine from dropdown suggestions for each entered item');
                formValid = false;
                return false;
            }
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
            if (freeQty < 0) {
                alert('Free quantity cannot be negative');
                formValid = false;
                return false;
            }
            if (unit <= 0) {
                alert('Purchase rate must be greater than 0');
                formValid = false;
                return false;
            }
            if (mrp <= 0) {
                alert('MRP must be greater than 0');
                formValid = false;
                return false;
            }
            if (unit > mrp) {
                alert('Purchase rate cannot be greater than MRP');
                formValid = false;
                return false;
            }
            if (discP < 0 || discP > 100) {
                alert('Discount must be between 0-100%');
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

            const duplicateKey = productId + '|' + batch.toUpperCase();
            if (duplicateTracker[duplicateKey]) {
                alert('Duplicate item found: same medicine and batch number cannot be entered multiple times');
                formValid = false;
                return false;
            }
            duplicateTracker[duplicateKey] = true;
            
            
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
                product_id: productId,
                product_name: p,
                pack_size_snapshot: ($(this).find('.pack_size_snapshot').val() || '').trim(),
                manufacturer_snapshot: ($(this).find('.manufacturer_snapshot').val() || '').trim(),
                hsn_code: $(this).find('.hsn_code').val(),
                batch_no: batch,
                manufacture_date: null,
                expiry_date: expiry,
                qty: qty,
                free_qty: freeQty,
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
                line_total: total
            });
        });

        if (!formValid || items.length === 0) {
            if (items.length === 0) alert('Please add at least one invoice item');
            return;
        }

        // Validate new fields
        if (!$('#supplier_invoice_no').val()) {
            alert('Please enter supplier invoice number');
            return;
        }
        if (!$('#supplier_invoice_date').val()) {
            alert('Please enter supplier invoice date');
            return;
        }
        
        const payload = {
            supplier_id: $('#supplier_id').val(),
            invoice_no: $('#invoice_no').val(),
            supplier_invoice_no: $('#supplier_invoice_no').val(),
            supplier_invoice_date: $('#supplier_invoice_date').val(),
            invoice_date: $('#invoice_date').val(),
            due_date: $('#due_date').val() || null,
            po_reference: $('#po_reference').val(),
            lr_no: ($('#lr_no').val() || '').trim(),
            lr_date: $('#lr_date').val() || null,
            carrier_name: ($('#carrier_name').val() || '').trim(),
            vehicle_no: ($('#vehicle_no').val() || '').trim(),
            f_slip_no: ($('#f_slip_no').val() || '').trim(),
            eway_bill_no: ewayNoVal,
            eway_bill_date: ewayDateVal || null,
            place_of_supply: $('#place_of_supply').val(),
            gst_type: gstType,
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
            payment_mode: paymentStatus === 'UNPAID' ? 'Credit' : $('#payment_mode').val(),
            payment_status: paymentStatus,
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
