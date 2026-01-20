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
                            <div class="table-responsive" style="overflow: visible !important;">
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
                                                <div class="position-relative form-group" style="position: relative; overflow: visible;">
                                                    <input type="text" class="form-control product-input" name="productName[]" placeholder="Type to search medicines..." autocomplete="off" required style="position: relative; z-index: 1;">
                                                    <input type="hidden" class="product-id" name="productId[]">
                                                    <div class="product-dropdown" style="position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; display: none; z-index: 10000; box-shadow: 0 4px 8px rgba(0,0,0,0.15); min-width: 300px;"></div>
                                                </div>
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

    // Product autocomplete search
    $(document).on('input', '.product-input', function() {
        const $input = $(this);
        const $dropdown = $input.closest('.position-relative').find('.product-dropdown');
        const searchTerm = $input.val();

        if(searchTerm.length < 1) {
            $dropdown.hide();
            return;
        }

        // Position dropdown below input
        const offset = $input.offset();
        $dropdown.css({

            //top: (offset.top + $input.outerHeight() + 5) + 'px',
            //left: offset.left + 'px',
            //width: $input.outerWidth() + 'px'
        });

                $.ajax({
            url: 'php_action/searchProducts.php',
            type: 'GET',
            data: {q: searchTerm},
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
                    return;
                }

                if(products.length === 0) {
                    $dropdown.html('<div style="padding: 10px;">No medicines found</div>').show();
                    return;
                }

                let html = '';
                products.forEach(product => {
                    html += `<div class="product-item" style="padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.2s;" data-id="${product.id}" data-name="${product.productName}" data-price="${product.price}">
                        <strong>${product.productName}</strong><br>
                        <small style="color: #666;">Price: ₹${parseFloat(product.price).toFixed(2)}</small>
                    </div>`;
                });
                $dropdown.html(html).show();

                // Select the first item by default
                $dropdown.find('.product-item').removeClass('selected').css('background-color', 'white');
                const $firstItem = $dropdown.find('.product-item').first();
                if($firstItem.length) {
                    $firstItem.addClass('selected').css('background-color', '#f5f5f5');
                }

                // Hover effect (delegated handlers will also update selection via mouse)
                $dropdown.find('.product-item').on('mouseenter', function() {
                    $dropdown.find('.product-item').removeClass('selected').css('background-color', 'white');
                    $(this).addClass('selected').css('background-color', '#f5f5f5');
                });
                $dropdown.find('.product-item').on('mouseleave', function() {
                    // keep selection on mouseleave (no-op) so keyboard stays in sync
                });
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Product selection from dropdown
    $(document).on('click', '.product-item', function() {
        const $item = $(this);
        const $input = $item.closest('.position-relative').find('.product-input');
        const $idField = $item.closest('.position-relative').find('.product-id');
        const $dropdown = $item.closest('.product-dropdown');

        $input.val($item.data('name'));
        $idField.val($item.data('id'));
        $dropdown.hide();

        // Auto-fill unit price
        const $unitPriceInput = $input.closest('tr').find('.unit-price');
        $unitPriceInput.val(parseFloat($item.data('price')).toFixed(2)).trigger('input');
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if(!$(e.target).closest('.position-relative').length) {
            $('.product-dropdown').hide();
        }
    });

    // Keyboard navigation for product dropdowns (Arrow Up/Down, Enter, Escape)
    $(document).on('keydown', '.product-input', function(e) {
        const $input = $(this);
        const $dropdown = $input.closest('.position-relative').find('.product-dropdown');
        if(!$dropdown.is(':visible')) return;
        const $items = $dropdown.find('.product-item');
        if($items.length === 0) return;

        const key = e.key || e.which;

        if(key === 'ArrowDown' || key === 'Down' || e.which === 40) {
            e.preventDefault();
            let $selected = $items.filter('.selected');
            if($selected.length === 0) {
                $items.first().addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
            } else {
                const $next = $selected.nextAll('.product-item').first();
                if($next.length) {
                    $selected.removeClass('selected').css('background-color', 'white');
                    $next.addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
                } else {
                    $selected.removeClass('selected').css('background-color', 'white');
                    $items.first().addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
                }
            }
        } else if(key === 'ArrowUp' || key === 'Up' || e.which === 38) {
            e.preventDefault();
            let $selected = $items.filter('.selected');
            if($selected.length === 0) {
                $items.last().addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
            } else {
                const $prev = $selected.prevAll('.product-item').first();
                if($prev.length) {
                    $selected.removeClass('selected').css('background-color', 'white');
                    $prev.addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
                } else {
                    $selected.removeClass('selected').css('background-color', 'white');
                    $items.last().addClass('selected').css('background-color', '#f5f5f5')[0].scrollIntoView({block: 'nearest'});
                }
            }
        } else if(key === 'Enter' || e.which === 13) {
            e.preventDefault();
            const $sel = $items.filter('.selected');
            if($sel.length) {
                $sel.trigger('click');
            }
        } else if(key === 'Escape' || e.which === 27) {
            $dropdown.hide();
        }
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
            const productId = $(this).find('.product-id').val();
            const productName = $(this).find('.product-input').val();
            const quantity = $(this).find('.quantity').val();
            const unitPrice = $(this).find('.unit-price').val();
            const total = $(this).find('.item-total').val();

            if(productId && productName && quantity && unitPrice) {
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
        const newRow = `
            <tr class="item-row">
                <td>
                    <div class="position-relative" style="position: relative;">
                        <input type="text" class="form-control product-input" name="productName[]" placeholder="Type to search medicines..." autocomplete="off" required style="position: relative; z-index: 1;">
                        <input type="hidden" class="product-id" name="productId[]">
                        <div class="product-dropdown" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; display: none; z-index: 10000; box-shadow: 0 4px 8px rgba(0,0,0,0.15); min-width: 300px;"></div>
                    </div>
                </td>
                <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
                <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
                <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
            </tr>
        `;
        $('#itemsTable tbody').append(newRow);
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
