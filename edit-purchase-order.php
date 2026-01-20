<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>

<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$id) {
    die('Invalid Purchase Order ID');
}

$sql = "SELECT * FROM purchase_orders WHERE id = $id AND delete_status = 0";
$result = $connect->query($sql);

if(!$result) {
    die("Query Error: " . $connect->error);
}

if($result->num_rows == 0) {
    die('Purchase Order not found');
}

$po = $result->fetch_assoc();

$itemsSql = "SELECT * FROM po_items WHERE po_master_id = $id";
$itemsResult = $connect->query($itemsSql);

if(!$itemsResult) {
    die("Query Error: " . $connect->error);
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Edit Purchase Order</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Edit Purchase Order</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form id="purchaseOrderForm">
                    <input type="hidden" name="poId" value="<?php echo intval($po['id']); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PO Number</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['po_id']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PO Date</label>
                                <input type="date" class="form-control" name="poDate" id="poDate" required value="<?php echo $po['po_date']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Name</label>
                                <input type="text" class="form-control" name="vendorName" id="vendorName" required value="<?php echo htmlspecialchars($po['vendor_name']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Contact</label>
                                <input type="text" class="form-control" name="vendorContact" id="vendorContact" required value="<?php echo htmlspecialchars($po['vendor_contact']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Email</label>
                                <input type="email" class="form-control" name="vendorEmail" id="vendorEmail" value="<?php echo htmlspecialchars($po['vendor_email']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vendor Address</label>
                                <input type="text" class="form-control" name="vendorAddress" id="vendorAddress" value="<?php echo htmlspecialchars($po['vendor_address']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expected Delivery Date</label>
                                <input type="date" class="form-control" name="deliveryDate" id="deliveryDate" required value="<?php echo $po['expected_delivery_date']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Purchase Order Status</label>
                                <select class="form-control" name="poStatus" id="poStatus" required>
                                    <option value="Pending" <?php echo ($po['po_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo ($po['po_status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Received" <?php echo ($po['po_status'] == 'Received') ? 'selected' : ''; ?>>Received</option>
                                    <option value="Cancelled" <?php echo ($po['po_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
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
                                        <?php 
                                        if($itemsResult->num_rows > 0) {
                                            while($item = $itemsResult->fetch_assoc()) {
                                        ?>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control product-select" name="productName[]" required>
                                                    <option value="">Select Product</option>
                                                    <?php 
                                                    $productSql = "SELECT product_id as id, product_name as productName FROM product WHERE status = 1";
                                                    $productResult = $connect->query($productSql);
                                                    while($prow = $productResult->fetch_assoc()) {
                                                        $selected = ($prow['id'] == $item['product_id']) ? 'selected' : '';
                                                        echo "<option value='".intval($prow['id'])."' $selected>".htmlspecialchars($prow['productName'])."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control quantity" name="quantity[]" value="<?php echo intval($item['quantity']); ?>" min="1" required></td>
                                            <td><input type="number" class="form-control unit-price" name="unitPrice[]" value="<?php echo floatval($item['unit_price']); ?>" step="0.01" min="0" required></td>
                                            <td><input type="number" class="form-control item-total" name="itemTotal[]" value="<?php echo floatval($item['total']); ?>" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                                        </tr>
                                        <?php 
                                            }
                                        } else {
                                        ?>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control product-select" name="productName[]" required>
                                                    <option value="">Select Product</option>
                                                    <?php 
                                                    $productSql = "SELECT product_id as id, product_name as productName FROM product WHERE status = 1";
                                                    $productResult = $connect->query($productSql);
                                                    while($prow = $productResult->fetch_assoc()) {
                                                        echo "<option value='".$prow['id']."'>".$prow['productName']."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
                                            <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
                                            <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                                        </tr>
                                        <?php 
                                        }
                                        ?>
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
                                <input type="number" class="form-control" name="subTotal" id="subTotal" step="0.01" readonly value="<?php echo floatval($po['sub_total']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Discount (%)</label>
                                <input type="number" class="form-control" name="discount" id="discount" step="0.01" min="0" value="<?php echo floatval($po['discount']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Tax (GST %)</label>
                                <input type="number" class="form-control" name="gst" id="gst" step="0.01" min="0" value="<?php echo floatval($po['gst']); ?>">
                            </div>
                            <div class="form-group">
                                <label><strong>Grand Total</strong></label>
                                <input type="number" class="form-control" name="grandTotal" id="grandTotal" step="0.01" readonly value="<?php echo floatval($po['grand_total']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select class="form-control" name="paymentStatus" id="paymentStatus" required>
                                    <option value="Pending" <?php echo ($po['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Partial" <?php echo ($po['payment_status'] == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                                    <option value="Paid" <?php echo ($po['payment_status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"><?php echo htmlspecialchars($po['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update Purchase Order</button>
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
    const poId = $('input[name="poId"]').val();
    
    // Add row button
    $('#addRowBtn').click(function() {
        // alert('Button clicked');
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
            poId: poId,
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
            url: 'php_action/editPurchaseOrder.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(result) {
                // jQuery auto-parses JSON, so result is already an object
                if(result.success) {
                    alert('Purchase Order updated successfully');
                    window.location.href = 'purchase_order.php';
                } else {
                    alert('Error: ' + (result.messages || result.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error updating Purchase Order. Check browser console for details.');
            }
        });
    });

    // below is original version
    // function addNewRow() {
    //     $.ajax({
    //         url: 'php_action/fetchProducts.php',
    //         type: 'GET',
    //         success: function(response) {
    //             const products = JSON.parse(response);
    //             let options = '<option value="">Select Product</option>';
    //             products.forEach(product => {
    //                 options += '<option value="' + product.id + '">' + product.productName + '</option>';
    //             });

    //             const newRow = `
    //                 <tr class="item-row">
    //                     <td>
    //                         <select class="form-control product-select" name="productName[]" required>
    //                             ` + options + `
    //                         </select>
    //                     </td>
    //                     <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
    //                     <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
    //                     <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
    //                     <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
    //                 </tr>
    //             `;
    //             $('#itemsTable tbody').append(newRow);
    //         }
    //     });
    // }

    function addNewRow() {
    console.log('Add Row clicked');

    $.ajax({
        url: 'php_action/fetchProducts.php',
        type: 'GET',
        dataType: 'json',   // VERY IMPORTANT

        success: function(products) {
            console.log('Products received:', products);

            let options = '<option value="">Select Product</option>';

            products.forEach(function(product) {
                options += '<option value="' + product.id + '">' + product.productName + '</option>';
            });

            const newRow = `
                <tr class="item-row">
                    <td>
                        <select class="form-control product-select" name="productName[]" required>
                            ${options}
                        </select>
                    </td>
                    <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
                    <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
                    <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
                </tr>
            `;

            $('#itemsTable tbody').append(newRow);

            console.log('Row added successfully');
        },

        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            console.error('Response:', xhr.responseText);
            alert('Failed to load products');
        }
    });
}


//     function addNewRow() {
//     console.log('Add Row clicked');

//     $.ajax({
//         url: 'php_action/fetchProducts.php',
//         type: 'GET',

//         beforeSend: function () {
//             console.log('Sending request to fetchProducts.php...');
//         },

//         success: function(response) {
//             console.log('Raw response from server:', response);

//             let products;
//             try {
//                 products = JSON.parse(response);
//                 console.log('Parsed products:', products);
//             } catch (e) {
//                 console.error('JSON parse error:', e);
//                 alert('Response is not valid JSON. Check console.');
//                 return;
//             }

//             if (!Array.isArray(products)) {
//                 console.error('Response is not an array:', products);
//                 alert('Response format is wrong. Check console.');
//                 return;
//             }

//             let options = '<option value="">Select Product</option>';

//             products.forEach(function(product, index) {
//                 console.log('Product', index, product);

//                 options += '<option value="' + product.id + '">' + product.productName + '</option>';
//             });

//             const newRow = `
//                 <tr class="item-row">
//                     <td>
//                         <select class="form-control product-select" name="productName[]" required>
//                             ${options}
//                         </select>
//                     </td>
//                     <td><input type="number" class="form-control quantity" name="quantity[]" min="1" required></td>
//                     <td><input type="number" class="form-control unit-price" name="unitPrice[]" step="0.01" min="0" required></td>
//                     <td><input type="number" class="form-control item-total" name="itemTotal[]" readonly></td>
//                     <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
//                 </tr>
//             `;

//             console.log('Appending new row to table...');
//             $('#itemsTable tbody').append(newRow);

//             console.log('Row added successfully');
//         },

//         error: function(xhr, status, error) {
//             console.error('AJAX error:');
//             console.error('Status:', status);
//             console.error('Error:', error);
//             console.error('Response:', xhr.responseText);

//             alert('AJAX failed. Check console.');
//         }
//     });
// }


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
