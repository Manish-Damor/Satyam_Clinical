<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>

<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

// Generate PO Number with error handling
$year = date('Y');
$month = date('m');
$poSql = "SELECT MAX(CAST(SUBSTRING(po_id, -4) AS UNSIGNED)) as maxPO FROM purchase_orders WHERE YEAR(po_date) = '$year'";
$poResult = $connect->query($poSql);

if(!$poResult) {
    die("Query Error: " . $connect->error);
}

$poRow = $poResult->fetch_assoc();
$nextPONum = ($poRow['maxPO'] ?? 0) + 1;
$poNumber = 'PO-' . $year . $month . '-' . str_pad($nextPONum, 4, '0', STR_PAD_LEFT);
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Add Purchase Order</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Add Purchase Order</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form id="purchaseOrderForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PO Number</label>
                                <input type="text" class="form-control" id="poNumber" value="<?php echo $poNumber; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PO Date</label>
                                <input type="date" class="form-control" name="poDate" id="poDate" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Name</label>
                                <input type="text" class="form-control" name="vendorName" id="vendorName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Contact</label>
                                <input type="text" class="form-control" name="vendorContact" id="vendorContact" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Email</label>
                                <input type="email" class="form-control" name="vendorEmail" id="vendorEmail">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Address</label>
                                <input type="text" class="form-control" name="vendorAddress" id="vendorAddress">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expected Delivery Date</label>
                                <input type="date" class="form-control" name="deliveryDate" id="deliveryDate" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Purchase Order Status</label>
                                <select class="form-control" name="poStatus" id="poStatus" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Received">Received</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Purchase Order Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th>Medicine/Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control product-select" name="productName[]" required>
                                                    <option value="">Select Product</option>
                                                    <?php 
                                                    $productSql = "SELECT product_id as id, product_name as productName FROM product WHERE status = 1";
                                                    $productResult = $connect->query($productSql);
                                                    while($prow = $productResult->fetch_assoc()) {
                                                        echo "<option value='".intval($prow['id'])."'>".htmlspecialchars($prow['productName'])."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
                                            <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
                                            <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-success" id="addRowBtn">Add Item</button>
                        </div>
                    </div>

                    <!-- Totals Section -->
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="form-group">
                                <label>Sub Total</label>
                                <input type="number" class="form-control" name="subTotal" id="subTotal" step="0.01" readonly>
                            </div>
                            <div class="form-group">
                                <label>Discount (%)</label>
                                <input type="number" class="form-control" name="discount" id="discount" step="0.01" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>Tax (GST %)</label>
                                <input type="number" class="form-control" name="gst" id="gst" step="0.01" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label><strong>Grand Total</strong></label>
                                <input type="number" class="form-control" name="grandTotal" id="grandTotal" step="0.01" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select class="form-control" name="paymentStatus" id="paymentStatus" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Save Purchase Order</button>
                            <a href="purchase_order.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script src="custom/js/purchase_order.js"></script>
<script>
$(document).ready(function() {
    const poNumber = '<?php echo $poNumber; ?>';
    
    // Add row button
    $('#addRowBtn').click(function() {
        addNewRow();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Calculate item total
    $(document).on('input', '.quantity, .unit-price', function() {
        const row = $(this).closest('tr');
        const quantity = row.find('.quantity').val() || 0;
        const unitPrice = row.find('.unit-price').val() || 0;
        const itemTotal = (quantity * unitPrice).toFixed(2);
        row.find('.item-total').val(itemTotal);
        calculateTotals();
    });

    // Calculate totals
    $('#discount, #gst').on('input', function() {
        calculateTotals();
    });

    // Submit form
    $('#purchaseOrderForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            poNumber: poNumber,
            poDate: $('#poDate').val(),
            vendorName: $('#vendorName').val(),
            vendorContact: $('#vendorContact').val(),
            vendorEmail: $('#vendorEmail').val(),
            vendorAddress: $('#vendorAddress').val(),
            deliveryDate: $('#deliveryDate').val(),
            poStatus: $('#poStatus').val(),
            subTotal: $('#subTotal').val(),
            discount: $('#discount').val(),
            gst: $('#gst').val(),
            grandTotal: $('#grandTotal').val(),
            paymentStatus: $('#paymentStatus').val(),
            notes: $('#notes').val(),
            items: []
        };

        $('#itemsTable tbody tr').each(function() {
            const productId = $(this).find('.product-select').val();
            const productName = $(this).find('.product-select option:selected').text();
            const quantity = $(this).find('.quantity').val();
            const unitPrice = $(this).find('.unit-price').val();
            const total = $(this).find('.item-total').val();

            if(productId && quantity && unitPrice) {
                formData.items.push({
                    productId: productId,
                    productName: productName,
                    quantity: quantity,
                    unitPrice: unitPrice,
                    total: total
                });
            }
        });

        if(formData.items.length === 0) {
            alert('Please add at least one item');
            return;
        }

        $.ajax({
            url: 'php_action/createPurchaseOrder.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(result) {
                // jQuery auto-parses JSON, so result is already an object
                if(result.success) {
                    alert('Purchase Order created successfully');
                    window.location.href = 'purchase_order.php';
                } else {
                    alert('Error: ' + (result.messages || result.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error creating Purchase Order. Check browser console for details.');
            }
        });
    });

    function addNewRow() {
        $.ajax({
            url: 'php_action/fetchProducts.php',
            type: 'GET',
            success: function(response) {
                let products = [];
                try {
                    if (typeof response === 'string') {
                        products = JSON.parse(response);
                    } else {
                        products = response;
                    }
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    alert('Error loading products. Please refresh the page.');
                    return;
                }

                let options = '<option value="">Select Product</option>';
                if(Array.isArray(products)) {
                    products.forEach(product => {
                        options += '<option value="' + product.id + '">' + product.productName + '</option>';
                    });
                }

                const newRow = `
                    <tr class="item-row">
                        <td>
                            <select class="form-control product-select" name="productName[]" required>
                                ` + options + `
                            </select>
                        </td>
                        <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
                        <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
                        <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                    </tr>
                `;
                $('#itemsTable tbody').append(newRow);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                alert('Error loading products. Check browser console.');
            }
        });
    }

    function calculateTotals() {
        let subTotal = 0;
        $('#itemsTable tbody tr').each(function() {
            const itemTotal = parseFloat($(this).find('.item-total').val()) || 0;
            subTotal += itemTotal;
        });

        const discount = parseFloat($('#discount').val()) || 0;
        const discountAmount = (subTotal * discount) / 100;
        const afterDiscount = subTotal - discountAmount;
        
        const gst = parseFloat($('#gst').val()) || 0;
        const gstAmount = (afterDiscount * gst) / 100;
        const grandTotal = afterDiscount + gstAmount;

        $('#subTotal').val(subTotal.toFixed(2));
        $('#grandTotal').val(grandTotal.toFixed(2));
    }
});
</script>
