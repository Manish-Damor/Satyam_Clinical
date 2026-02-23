<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';

$editMode = false;
$clientData = [
    'client_id' => '',
    'client_code' => '',
    'name' => '',
    'contact_phone' => '',
    'email' => '',
    'billing_address' => '',
    'shipping_address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => 'India',
    'gstin' => '',
    'pan' => '',
    'credit_limit' => '',
    'payment_terms' => 30,
    'business_type' => 'Retail',
    'status' => 'ACTIVE',
    'notes' => ''
];

// Load client data if editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $editMode = true;
    $clientId = intval($_GET['id']);
    
    $stmt = $connect->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $clientData = $result->fetch_assoc();
    } else {
        header('Location: clients_list.php');
        exit;
    }
}

$pageTitle = $editMode ? 'Edit Client' : 'Add New Client';
$submitButton = $editMode ? 'Update Client' : 'Create Client';
?>

<div class="page-wrapper">
    
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary"><?php echo $pageTitle; ?></h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="clients_list.php">Clients</a></li>
                <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
            </ol>
        </div>
    </div>
    
    <div class="container-fluid">
        
        <div class="card">
            <div class="card-body">
                <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-check-circle"></i> <span id="successText"></span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div id="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fa fa-exclamation-circle"></i> <span id="errorText"></span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <form id="clientForm" method="POST">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="client_id" id="clientId" value="<?php echo $clientData['client_id']; ?>" />
                    <?php endif; ?>
                    
                    <!-- Section 1: Basic Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary mb-3"><i class="fa fa-info-circle"></i> Basic Information</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Client Code</label>
                                <input type="text" class="form-control" name="client_code" value="<?php echo htmlspecialchars($clientData['client_code']); ?>" 
                                    placeholder="e.g., CL001" />
                            </div>
                            <div class="col-md-9">
                                <label>Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($clientData['name']); ?>" 
                                    placeholder="Full client name" required />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($clientData['contact_phone']); ?>" 
                                    placeholder="Mobile number" pattern="[0-9]{10}" />
                            </div>
                            <div class="col-md-6">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($clientData['email']); ?>" 
                                    placeholder="Email address" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 2: Address Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-map-marker"></i> Address Information</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Billing Address</label>
                                <textarea class="form-control" name="billing_address" rows="3" placeholder="Billing address"><?php echo htmlspecialchars($clientData['billing_address']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label>Shipping Address (if different)</label>
                                <textarea class="form-control" name="shipping_address" rows="3" placeholder="Shipping address"><?php echo htmlspecialchars($clientData['shipping_address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label>City</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($clientData['city']); ?>" 
                                    placeholder="City" />
                            </div>
                            <div class="col-md-3">
                                <label>State</label>
                                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($clientData['state']); ?>" 
                                    placeholder="State" />
                            </div>
                            <div class="col-md-2">
                                <label>Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($clientData['postal_code']); ?>" 
                                    placeholder="PIN code" pattern="[0-9]{6}" />
                            </div>
                            <div class="col-md-4">
                                <label>Country</label>
                                <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($clientData['country']); ?>" 
                                    placeholder="Country" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 3: Tax & Business Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-briefcase"></i> Tax & Business Information</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-4">
                                <label>GSTIN</label>
                                <input type="text" class="form-control" name="gstin" value="<?php echo htmlspecialchars($clientData['gstin']); ?>" 
                                    placeholder="GST Registration Number" pattern="[0-9A-Z]{15}" />
                            </div>
                            <div class="col-md-4">
                                <label>PAN</label>
                                <input type="text" class="form-control" name="pan" value="<?php echo htmlspecialchars($clientData['pan']); ?>" 
                                    placeholder="PAN" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" />
                            </div>
                            <div class="col-md-4">
                                <label>Business Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="business_type" required>
                                    <option value="Retail" <?php echo $clientData['business_type'] === 'Retail' ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Wholesale" <?php echo $clientData['business_type'] === 'Wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                                    <option value="Hospital" <?php echo $clientData['business_type'] === 'Hospital' ? 'selected' : ''; ?>>Hospital</option>
                                    <option value="Clinic" <?php echo $clientData['business_type'] === 'Clinic' ? 'selected' : ''; ?>>Clinic</option>
                                    <option value="Distributor" <?php echo $clientData['business_type'] === 'Distributor' ? 'selected' : ''; ?>>Distributor</option>
                                    <option value="Other" <?php echo $clientData['business_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 4: Credit Terms -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-credit-card"></i> Credit Terms</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Credit Limit (Rs.)</label>
                                <input type="number" class="form-control" name="credit_limit" value="<?php echo $clientData['credit_limit']; ?>" 
                                    placeholder="0" step="0.01" />
                            </div>
                            <div class="col-md-4">
                                <label>Payment Terms (Days)</label>
                                <input type="number" class="form-control" name="payment_terms" value="<?php echo $clientData['payment_terms']; ?>" 
                                    placeholder="30" min="0" />
                            </div>
                            <div class="col-md-4">
                                <label>Status <span class="text-danger">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="ACTIVE" <?php echo $clientData['status'] === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                    <option value="INACTIVE" <?php echo $clientData['status'] === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="SUSPENDED" <?php echo $clientData['status'] === 'SUSPENDED' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 5: Additional Notes -->
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <h5 class="text-primary mb-3"><i class="fa fa-sticky-note"></i> Additional Notes</h5>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <textarea class="form-control" name="notes" rows="4" placeholder="Any additional notes about this client"><?php echo htmlspecialchars($clientData['notes']); ?></textarea>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-flat" id="submitBtn">
                                    <i class="fa fa-check"></i> <?php echo $submitButton; ?>
                                </button>
                                <a href="clients_list.php" class="btn btn-secondary btn-flat">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script>
$(document).ready(function() {
    $('#clientForm').on('submit', function(e) {
        e.preventDefault();
        
        const isEditMode = <?php echo $editMode ? 'true' : 'false'; ?>;
        const actionUrl = isEditMode ? 'php_action/updateClient.php' : 'php_action/createClient.php';
        
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#successText').text(response.message);
                    $('#successMessage').show();
                    $('#errorMessage').hide();
                    
                    setTimeout(function() {
                        window.location.href = 'clients_list.php';
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
    });
});
</script>
