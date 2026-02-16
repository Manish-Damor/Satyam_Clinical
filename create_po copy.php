<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

// Show messages if any
$successMsg = isset($_SESSION['po_success']) ? $_SESSION['po_success'] : '';
$errorMsg = isset($_SESSION['po_error']) ? $_SESSION['po_error'] : '';
unset($_SESSION['po_success'], $_SESSION['po_error']);

// Generate PO Number
$year = date('y');
$month = date('m');
$poSql = "SELECT MAX(CAST(SUBSTRING(po_number, -4) AS UNSIGNED)) as maxPO FROM purchase_order WHERE YEAR(po_date) = '$year'";
$poResult = $connect->query($poSql);
$poRow = $poResult->fetch_assoc();
$nextPONum = (isset($poRow['maxPO']) && $poRow['maxPO']) ? $poRow['maxPO'] + 1 : 1;
$poNumber = 'PO-' . $year .'-'.$year+1 .  '/' . str_pad($nextPONum, 4, '0', STR_PAD_LEFT);
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-primary">Create Purchase Order</h3>
        </div>
        <div class="col-md-4 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="po_list.php">Purchase Orders</a></li>
                <li class="breadcrumb-item active">Create PO</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($successMsg); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?php echo htmlspecialchars($errorMsg); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>

        <form id="poForm" method="POST" action="php_action/createPurchaseOrder.php">
            <!-- Header Section -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="text-white m-0">PO Header Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>PO Number</label>
                                <input type="text" class="form-control" id="poNumber" readonly value="<?php echo $poNumber; ?>">
                                <input type="hidden" id="poNumberHidden" name="po_number" value="<?php echo $poNumber; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>PO Date *</label>
                                <input type="date" class="form-control" id="poDate" name="po_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>PO Type</label>
                                <select class="form-control" name="po_type">
                                    <option value="Regular">Regular</option>
                                    <option value="Express">Express</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Expected Delivery Date</label>
                                <input type="date" class="form-control" name="expected_delivery_date">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier Section -->
            <div class="card mt-3">
                <div class="card-header bg-info">
                    <h5 class="text-white m-0">Supplier Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Select Supplier *</label>
                                <select class="form-control" id="supplierId" name="supplier_id" required onchange="loadSupplierDetails()">
                                    <option value="">-- Choose Supplier --</option>
                                    <?php
                                    $supSql = "SELECT supplier_id, supplier_name, supplier_code FROM suppliers WHERE is_active = 1 ORDER BY supplier_name";
                                    $supResult = $connect->query($supSql);
                                    while($supRow = $supResult->fetch_assoc()) {
                                        echo "<option value='" . intval($supRow['supplier_id']) . "'>" . htmlspecialchars($supRow['supplier_name']) . " (" . htmlspecialchars($supRow['supplier_code']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Number (Invoice/Bill)</label>
                                <input type="text" class="form-control" name="reference_number" placeholder="Supplier's invoice number">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier Contact</label>
                                <input type="tel" class="form-control" id="supplierContact" name="supplier_contact" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier Email</label>
                                <input type="email" class="form-control" id="supplierEmail" name="supplier_email" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Supplier Address</label>
                        <textarea class="form-control" id="supplierAddress" name="supplier_address" rows="2" readonly></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="supplierCity" name="supplier_city" placeholder="City" readonly>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="supplierState" name="supplier_state" placeholder="State" readonly>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="supplierPincode" name="supplier_pincode" placeholder="Pincode" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier GST</label>
                                <input type="text" class="form-control" id="supplierGst" name="supplier_gst" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Terms</label>
                                <input type="text" class="form-control" id="paymentTerms" name="payment_terms" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="card mt-3">
                <div class="card-header bg-success">
                    <h5 class="text-white m-0">
                        Medicines/Products
                        <button type="button" class="btn btn-light btn-sm float-right" id="addRowBtn">
                            <i class="fa fa-plus"></i> Add Medicine
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- <table class="table table-bordered table-sm" id="itemsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:18%;">Medicine Name</th>
                                    <th style="width:8%;">HSN Code</th>
                                    <th style="width:10%;">Pack Size</th>
                                    <th style="width:8%;">Batch No.</th>
                                    <th style="width:8%;">Expiry Date</th>
                                    <th style="width:7%;">MRP</th>
                                    <th style="width:7%;">PTR</th>
                                    <th style="width:8%;">Rate</th>
                                    <th style="width:7%;">Qty</th>
                                    <th style="width:8%;">Discount %</th>
                                    <th style="width:8%;">Amount</th>
                                    <th style="width:8%;">Tax %</th>
                                    <th style="width:8%;">Total</th>
                                    <th style="width:5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <div style="position: relative;">
                                            <input type="text" class="form-control form-control-sm medicine-search" placeholder="Search..." autocomplete="off">
                                            <input type="hidden" class="medicine-id" name="medicine_id[]">
                                            <div class="medicine-dropdown" style="position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ddd; max-height: 250px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></div>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm hsn-code" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm pack-size" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm batch-number" readonly></td>
                                    <td><input type="date" class="form-control form-control-sm expiry-date" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm mrp-value" step="0.01" readonly></td>
                                    <td><input type="number" class="form-control form-control-sm ptr-value" step="0.01" readonly style="background-color: #fff3cd;"></td>
                                    <td><input type="number" class="form-control form-control-sm unit-price" name="unit_price[]" step="0.01" min="0"></td>
                                    <td><input type="number" class="form-control form-control-sm quantity" name="quantity[]" min="1" value="0"></td>
                                    <td><input type="number" class="form-control form-control-sm discount-percent" name="discount_percent[]" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" class="form-control form-control-sm line-amount" readonly style="background-color: #f0f0f0;"></td>
                                    <td><input type="number" class="form-control form-control-sm tax-percent" name="tax_percent[]" step="0.01" value="18"></td>
                                    <td><input type="number" class="form-control form-control-sm item-total" readonly style="background-color: #f0f0f0;"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                                </tr>
                            </tbody>
                        </table> -->
                        <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:18%;">Medicine Name</th>
                                    <th style="width:8%;">HSN Code</th>
                                    <th style="width:12%;">Pack Size</th>
                                    <th style="width:8%;">Batch No.</th>
                                    <th style="width:8%;">Expiry</th>
                                    <th style="width:auto%;">MRP</th>
                                    <th style="width:auto%;">PTR</th>
                                    <th style="width:auto%;">Rate</th>
                                    <th style="width:7%;">Qty</th>
                                    <th style="width:auto%;">Disc %</th>
                                    <th style="width:8%;">Amt</th>
                                    <th style="width:auto;">Tax %</th>
                                    <th style="width:8%;">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <div style="position: relative;">
                                            <input type="text" class="form-control form-control-sm medicine-search"  name="medicine_name[]" placeholder="Search..." autocomplete="off">
                                            <input type="hidden" class="medicine-id" name="medicine_id[]" value="">
                                            <div class="medicine-dropdown" style="position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ddd; max-height: 250px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></div>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm hsn-code" name="hsn_code[]" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm pack-size" name="pack_size[]" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm batch-number" name="batch_number[]"  readonly></td>
                                    <td style=""><input type="date" class="form-control form-control-sm  expiry-date" style="" name="expiry_date[]"  readonly></td>
                                    <td><input type="text" class="form-control form-control-sm mrp-value" name="mrp[]"  step="0.01" readonly value="0.00"></td>
                                    <td><input type="text" class="form-control form-control-sm ptr-value" name="ptr[]"  step="0.01" readonly value="0.00" style="background-color: #fff3cd;"></td>
                                    <td><input type="text" class="form-control form-control-sm unit-price" name="unit_price[]" step="0.01" min="0" value="0.00"></td>
                                    <td><input type="number" class="form-control form-control-sm quantity" name="quantity[]" min="1" value="1"></td>
                                    <td><input type="text" class="form-control form-control-sm discount-percent" name="discount_percent[]" step="0.01" min="0" value="0.00"></td>
                                    <td><input type="text" class="form-control form-control-sm line-amount" readonly value="0.00" style="background-color: #f0f0f0;"></td>
                                    <td><input type="text" class="form-control form-control-sm tax-percent" name="tax_percent[]" step="0.01" value="18"></td>
                                    <td><input type="text" class="form-control form-control-sm item-total"   readonly value="0.00" style="background-color: #f0f0f0;"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-row" onclick="removeRow(event)"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h5 class="text-dark m-0">Calculations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sub Total</label>
                                <input type="number" class="form-control" id="subTotal" name="sub_total" readonly value="0.00" style="font-size: 16px; font-weight: bold;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Discount %</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discountPercent" name="discount_percent" placeholder="%" step="0.01" min="0" value="0.00">
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Total Discount</label>
                                <input type="number" class="form-control" id="totalDiscount" name="total_discount" readonly value="0.00" style="font-size: 16px; font-weight: bold;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Taxable Amount</label>
                                <input type="number" class="form-control" id="taxableAmount" name="taxable_amount" readonly value="0.00" style="font-size: 16px; font-weight: bold;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>CGST (9%)</label>
                                <input type="number" class="form-control" id="cgstAmount" name="cgst_amount" readonly value="0.00" style="font-size: 14px; background-color: #e8f5e9;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>SGST (9%)</label>
                                <input type="number" class="form-control" id="sgstAmount" name="sgst_amount" readonly value="0.00" style="font-size: 14px; background-color: #e8f5e9;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>IGST (18%)</label>
                                <input type="number" class="form-control" id="igstAmount" name="igst_amount" readonly value="0.00" style="font-size: 14px; background-color: #e8f5e9;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Round Off</label>
                                <input type="number" class="form-control" id="roundOff" name="round_off" step="0.01" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label style="font-size: 16px; font-weight: bold;">GRAND TOTAL</label>
                                <input type="number" class="form-control" id="grandTotal" name="grand_total" readonly value="0.00" style="font-size: 18px; font-weight: bold; background-color: #fff59d; border: 2px solid #ff9800;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="card mt-3">
                <div class="card-header bg-secondary">
                    <h5 class="text-white m-0">Additional Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select class="form-control" name="payment_method">
                                    <option value="Online Transfer">Online Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit">Credit</option>
                                    <option value="NEFT">NEFT</option>
                                    <option value="RTGS">RTGS</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PO Status</label>
                                <select class="form-control" name="po_status">
                                    <option value="Draft">Draft</option>
                                    <option value="Sent">Sent to Supplier</option>
                                    <option value="Pending">Pending Confirmation</option>
                                    <option value="Confirmed">Confirmed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes & Special Instructions</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any special instructions..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Terms & Conditions</label>
                        <textarea class="form-control" name="terms_conditions" rows="3">1. Goods to be delivered as per agreed schedule
                            2. Payment terms: As per agreement
                            3. Quality check required before acceptance
                            4. Damaged/defective goods to be replaced</textarea>
                    </div>
                </div>
            </div>

            <!-- HIDDEN FIELD FOR ITEM COUNT -->
            <input type="hidden" id="itemCount" name="item_count" value="1">

            <!-- Action Buttons -->
            <div class="mt-4 text-right">
                <button type="button" class="btn btn-secondary mr-2">Save as Draft</button>
                <button type="button" class="btn btn-info mr-2">Preview</button>
                <button type="submit" class="btn btn-success mr-2">Create PO</button>
                <a href="po_list.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<style>
.medicine-dropdown {
    border-radius: 4px;
}
.medicine-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    font-size: 12px;
}
.medicine-dropdown-item:hover {
    background-color: #f5f5f5;
}
.medicine-dropdown-item strong {
    display: block;
    color: #333;
}
.medicine-dropdown-item small {
    color: #666;
}
.input-group-addon {
    padding: 6px 12px;
    background-color: #f5f5f5;
    border: 1px solid #ccc;
}
.table-sm td {
    padding: 8px 1px;
    vertical-align: middle;
}
.table-sm input[type="text"],
.table-sm input[type="date"],
.table-sm input[type="number"] {
    height: 30px;
    padding: 4px 6px;
    font-size: 12px;
}
.table-sm input[type="date"] {
    
}
</style>

<script>
let itemCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    attachEventListeners();
    initializeMedicineSearch();
});

// Add Row Button
document.getElementById('addRowBtn').addEventListener('click', function(e) {
    e.preventDefault();
    addItemRow();
});

function addItemRow() {
    itemCount++;
    document.getElementById('itemCount').value = itemCount;
    
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    
    row.innerHTML = `
        <td>
            <div style="position: relative;">
                <input type="text" class="form-control form-control-sm medicine-search"  name="medicine_name[]" placeholder="Search..." autocomplete="off">
                <input type="hidden" class="medicine-id" name="medicine_id[]" value="">
                <div class="medicine-dropdown" style="position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ddd; max-height: 250px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></div>
            </div>
        </td>
        <td><input type="text" class="form-control form-control-sm hsn-code" name="hsn_code[]" readonly></td>
        <td><input type="text" class="form-control form-control-sm pack-size" name="pack_size[]" readonly></td>
        <td><input type="text" class="form-control form-control-sm batch-number" name="batch_number[]"  readonly></td>
        <td style=""><input type="date" class="form-control form-control-sm  expiry-date" style="" name="expiry_date[]"  readonly></td>
        <td><input type="text" class="form-control form-control-sm mrp-value" name="mrp[]"  step="0.01" readonly value="0.00"></td>
        <td><input type="text" class="form-control form-control-sm ptr-value" name="ptr[]"  step="0.01" readonly value="0.00" style="background-color: #fff3cd;"></td>
        <td><input type="text" class="form-control form-control-sm unit-price" name="unit_price[]" step="0.01" min="0" value="0.00"></td>
        <td><input type="number" class="form-control form-control-sm quantity" name="quantity[]" min="1" value="1"></td>
        <td><input type="text" class="form-control form-control-sm discount-percent" name="discount_percent[]" step="0.01" min="0" value="0.00"></td>
        <td><input type="text" class="form-control form-control-sm line-amount" readonly value="0.00" style="background-color: #f0f0f0;"></td>
        <td><input type="text" class="form-control form-control-sm tax-percent" name="tax_percent[]" step="0.01" value="18"></td>
        <td><input type="text" class="form-control form-control-sm item-total"   readonly value="0.00" style="background-color: #f0f0f0;"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row" onclick="removeRow(event)"><i class="fa fa-trash"></i></button></td>
    `;
    
    tbody.appendChild(row);
    initializeMedicineSearchForRow(row);
    attachRowEventListeners(row);
}

// Initialize medicine search for all rows
function initializeMedicineSearch() {
    document.querySelectorAll('.item-row').forEach(row => {
        initializeMedicineSearchForRow(row);
    });
}

function initializeMedicineSearchForRow(row) {
    const input = row.querySelector('.medicine-search');
    const dropdown = row.querySelector('.medicine-dropdown');
    
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        if(query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        fetch(`php_action/searchMedicines.php?search=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                dropdown.innerHTML = '';
                
                if(data.length > 0) {
                    data.forEach(medicine => {
                        const item = document.createElement('div');
                        item.className = 'medicine-dropdown-item';
                        item.innerHTML = `
                            <strong>${medicine.medicine_name}</strong>
                            <small>HSN: ${medicine.hsn_code} | Pack: ${medicine.pack_size} | MRP: ₹${medicine.mrp}</small>
                        `;
                        item.addEventListener('mousedown', function() {
                            selectMedicine(row, medicine);
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                } else {
                    dropdown.innerHTML = '<div class="medicine-dropdown-item">No medicines found</div>';
                    dropdown.style.display = 'block';
                }
            })
            .catch(error => console.error('Error:', error));
    });
    
    input.addEventListener('blur', function() {
        setTimeout(() => {
            dropdown.style.display = 'none';
        }, 200);
    });
}

function selectMedicine(row, medicine) {
    row.querySelector('.medicine-id').value = medicine.medicine_id;
    row.querySelector('.medicine-search').value = medicine.medicine_name;
    row.querySelector('.hsn-code').value = medicine.hsn_code || '';
    row.querySelector('.pack-size').value = medicine.pack_size || '';
    row.querySelector('.batch-number').value = medicine.current_batch_number || '';
    row.querySelector('.expiry-date').value = medicine.current_expiry_date || '';
    row.querySelector('.mrp-value').value = parseFloat(medicine.mrp || 0).toFixed(2);
    row.querySelector('.ptr-value').value = parseFloat(medicine.ptr || 0).toFixed(2);
    row.querySelector('.unit-price').value = parseFloat(medicine.ptr || 0).toFixed(2);
    
    row.querySelector('.medicine-dropdown').style.display = 'none';
    calculateRow(row);
}

// Attach Event Listeners
function attachEventListeners() {
    document.querySelectorAll('.item-row').forEach(row => {
        attachRowEventListeners(row);
    });
    
    // Discount and RoundOff listeners
    const discountPercent = document.getElementById('discountPercent');
    const roundOff = document.getElementById('roundOff');
    
    if(discountPercent) {
        discountPercent.addEventListener('change', calculateTotals);
        discountPercent.addEventListener('input', calculateTotals);
    }
    
    if(roundOff) {
        roundOff.addEventListener('change', calculateTotals);
        roundOff.addEventListener('input', calculateTotals);
    }
}

function attachRowEventListeners(row) {
    const quantity = row.querySelector('.quantity');
    const unitPrice = row.querySelector('.unit-price');
    const discountPercent = row.querySelector('.discount-percent');
    const taxPercent = row.querySelector('.tax-percent');
    
    [quantity, unitPrice, discountPercent, taxPercent].forEach(input => {
        if(input) {
            input.addEventListener('change', () => calculateRow(row));
            input.addEventListener('input', () => calculateRow(row));
        }
    });
}

function calculateRow(row) {
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
    const discountPercent = parseFloat(row.querySelector('.discount-percent').value) || 0;
    const taxPercent = parseFloat(row.querySelector('.tax-percent').value) || 18;
    
    const lineAmount = quantity * unitPrice;
    const discountAmount = (lineAmount * discountPercent) / 100;
    const taxableAmount = lineAmount - discountAmount;
    const taxAmount = (taxableAmount * taxPercent) / 100;
    const itemTotal = taxableAmount + taxAmount;
    
    row.querySelector('.line-amount').value = lineAmount.toFixed(2);
    row.querySelector('.item-total').value = itemTotal.toFixed(2);
    
    calculateTotals();
}

function calculateTotals() {
    let subTotal = 0;
    let totalDiscount = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const discountPercent = parseFloat(row.querySelector('.discount-percent').value) || 0;
        
        const lineAmount = quantity * unitPrice;
        const lineDiscount = (lineAmount * discountPercent) / 100;
        
        subTotal += lineAmount;
        totalDiscount += lineDiscount;
    });
    
    const discountPercent = parseFloat(document.getElementById('discountPercent').value) || 0;
    const additionalDiscount = (subTotal * discountPercent) / 100;
    totalDiscount += additionalDiscount;
    
    const taxableAmount = subTotal - totalDiscount;
    
    const cgstAmount = (taxableAmount * 9) / 100;
    const sgstAmount = (taxableAmount * 9) / 100;
    const igstAmount = 0;
    const roundOff = parseFloat(document.getElementById('roundOff').value) || 0;
    const grandTotal = taxableAmount + cgstAmount + sgstAmount + roundOff;
    
    document.getElementById('subTotal').value = subTotal.toFixed(2);
    document.getElementById('totalDiscount').value = totalDiscount.toFixed(2);
    document.getElementById('taxableAmount').value = taxableAmount.toFixed(2);
    document.getElementById('cgstAmount').value = cgstAmount.toFixed(2);
    document.getElementById('sgstAmount').value = sgstAmount.toFixed(2);
    document.getElementById('igstAmount').value = igstAmount.toFixed(2);
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
}

// Form Submission
document.getElementById('poForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const supplierId = document.getElementById('supplierId').value;
    if(!supplierId) {
        alert('Please select a supplier');
        return;
    }
    
    let hasItems = false;
    document.querySelectorAll('.item-row').forEach(row => {
        const medicineId = row.querySelector('.medicine-id').value;
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        
        if(medicineId && quantity > 0) {
            hasItems = true;
        }
    });
    
    if(!hasItems) {
        alert('Please add at least one medicine with quantity');
        return;
    }
    
    // Submit the form normally
    this.submit();
});

function removeRow(event) {
    event.preventDefault();
    event.target.closest('tr').remove();
    calculateTotals();
}

function loadSupplierDetails() {
    const supplierId = document.getElementById('supplierId').value;
    if (!supplierId) return;

    fetch(`php_action/getBuyer.php?id=${supplierId}`)
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.error(result.error);
                return;
            }

            const data = result.data;
            
            document.getElementById('supplierContact').value = data.primary_contact || '';
            document.getElementById('supplierEmail').value = data.email || '';
            document.getElementById('supplierAddress').value = data.billing_address || '';
            document.getElementById('supplierCity').value = data.billing_city || '';
            document.getElementById('supplierState').value = data.billing_state || '';
            document.getElementById('supplierPincode').value = data.billing_pincode || '';
            document.getElementById('supplierGst').value = data.gst_number || '';
            document.getElementById('paymentTerms').value = data.payment_terms || '';
        })
        .catch(err => {
            console.error('Supplier fetch error:', err);
        });
}
</script>
