<?php
// legacy entry point kept for backward compatibility.
// Redirect users to the new supplier management module which uses
// the current schema (supplier_status, phone, address etc.).
header('Location: manage_suppliers.php');
exit;
?>


<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Suppliers/Vendors Management</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Suppliers</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addSupplierModal">
                    <i class="fa fa-plus"></i> Add New Supplier
                </button>
                
                <div class="table-responsive m-t-40">
                    <table class="table table-bordered table-striped" id="suppliersTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Code</th>
                                <th>Supplier Name</th>
                                <!-- <th>Type</th> -->
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <!-- <th>Total Amount</th> -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($result->num_rows > 0) {
                                $count = 0;
                                while($row = $result->fetch_assoc()) {
                                    $count++;
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $count; ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['supplier_name']); ?></strong></td>
                                <!-- <td>
                                    <span class="label label-info"><?php //echo htmlspecialchars($row['supplier_type']); ?></span>
                                </td> -->
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo intval($row['total_orders']); ?></td>
                                <!-- <td class="text-right">₹<?php //echo number_format($row['total_amount_ordered'], 2); ?></td> -->
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="editSupplier(<?php echo intval($row['supplier_id']); ?>)" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <a href="php_action/deleteSupplier.php?id=<?php echo intval($row['supplier_id']); ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure?')" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No Suppliers Found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div id="addSupplierModal" class="modal hide" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Add New Supplier</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="supplierForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="supplierId" name="supplier_id">
                    
                    <div class="row">
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="supplierName">Supplier Name *</label>
                                <input type="text" class="form-control" id="supplierName" name="supplier_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phoneNumber">Phone Number *</label>
                                <input type="tel" class="form-control" id="phoneNumber" name="phone_number" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gstNumber">GST Number</label>
                                <input type="text" class="form-control" id="gstNumber" name="gst_number" placeholder="eg. 27AABCU9603R1Z0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="panNumber">PAN Number</label>
                                <input type="text" class="form-control" id="panNumber" name="pan_number" placeholder="eg. ABCPK1234L">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        
                        
                        <div class="col-md-6">                            
                            <div class="form-group">
                                <label for="dlNumber">DL Number *</label>
                                <input type="tel" class="form-control" id="dlNumber" name="dl_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">                            
                            <div class="form-group">
                                <label for="fssaiNumber">FSSAI Number *</label>
                                <input type="tel" class="form-control" id="fssaiNumber" name="fsssai_number" required>
                            </div>
                        </div>
                    </div>
                    

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="form-group">
                        <label for="billingAddress">Billing Address *</label>
                        <textarea style="height:60px" class="form-control" id="billingAddress" name="billing_address" rows="5" placeholder="Address Here" required></textarea>
                    </div>

                    <div class="row">
                        <!-- <div class="col-md-3">
                            <div class="form-group">
                                <label for="distCode">District Code</label>
                                <input type="number" class="form-control" id="distCode" name="dist_code">
                            </div>
                        </div> -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billingCity">City</label>
                                <input type="text" class="form-control" id="billingCity" name="billing_city">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billingState">State</label>
                                <input type="text" class="form-control" id="billingState" name="billing_state">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billingPincode">Pincode</label>
                                <input type="text" class="form-control" id="billingPincode" name="billing_pincode">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Terms</label>
                                <input type="text" class="form-control" id="paymentTerms" name="payment_terms" placeholder="30 days net">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Days</label>
                                <input type="number" class="form-control" id="paymentDays" name="payment_days" value="30">
                            </div>
                        </div>
                    </div>

                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script>
$(document).ready(function() {
    $('#supplierForm').on('submit', function(e) {
        // e.preventDefault();
        
        var formData = {
            supplier_id: $('#supplierId').val(),
            //supplier_code: $('#supplierCode').val(),
            supplier_name: $('#supplierName').val(),
            //supplier_type: $('#supplierType').val(),
            phone_number: $('#phoneNumber').val(),
            gst_number: $('#gstNumber').val(),
            //contact_person: $('#contactPerson').val(),
            pan_number: $('#panNumber').val(),
            dl_number: $('#dlNumber').val(),
            fsssai_number: $('#fssaiNumber').val(),
            email: $('#email').val(),
            billing_address: $('#billingAddress').val(),
            // dist_code: $('#distCode').val(),
            billing_city: $('#billingCity').val(),
            billing_state: $('#billingState').val(),
            billing_pincode: $('#billingPincode').val(),
            payment_terms: $('#paymentTerms').val(),
            payment_days: $('#paymentDays').val()
        };
        
        $.ajax({
            url: 'php_action/saveSupplier.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if(response.success) {
                    alert('Supplier saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error saving supplier');
            }
        });
    });
});

// function editSupplier(id) {
//     $.ajax({
//         url: 'php_action/getSupplier.php?id=' + id,
//         type: 'GET',
//         dataType: 'json',
//         success: function(data) {
//             $('#supplierId').val(data.supplier_id);
//             $('#supplierCode').val(data.supplier_code);
//             $('#supplierName').val(data.supplier_name);
//             $('#supplierType').val(data.supplier_type);
//             $('#gstNumber').val(data.gst_number);
//             $('#contactPerson').val(data.contact_person);
//             $('#primaryContact').val(data.primary_contact);
//             $('#email').val(data.email);
//             $('#billingAddress').val(data.billing_address);
//             $('#billingCity').val(data.billing_city);
//             $('#billingState').val(data.billing_state);
//             $('#billingPincode').val(data.billing_pincode);
//             $('#paymentTerms').val(data.payment_terms);
//             $('#paymentDays').val(data.payment_days);
            
//             $('#modalTitle').text('Edit Supplier');
//             $('#addSupplierModal').modal('show');
//         }
//     });
// }

function editSupplier(id) {
    $.ajax({
        url: 'php_action/getSupplier.php?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {

            // Handle API error
            if (!response.success) {
                alert(response.error || "Failed to load supplier data");
                return;
            }

            const data = response.data;

            $('#supplierId').val(data.supplier_id);
            //$('#supplierCode').val(data.supplier_code);
            $('#supplierName').val(data.supplier_name);
            $('#supplierType').val(data.supplier_type);
            $('#gstNumber').val(data.gst_number);
            $('#contactPerson').val(data.contact_person);
            $('#primaryContact').val(data.primary_contact);
            $('#email').val(data.email);
            $('#billingAddress').val(data.billing_address);
            $('#billingCity').val(data.billing_city);
            $('#billingState').val(data.billing_state);
            $('#billingPincode').val(data.billing_pincode);
            $('#paymentTerms').val(data.payment_terms);
            $('#paymentDays').val(data.payment_days);

            $('#modalTitle').text('Edit Supplier');
            $('#addSupplierModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
            alert("Server error while loading supplier data.");
        }
    });
}

</script>
