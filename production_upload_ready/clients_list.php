<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';

// Check if delete was successful
$deleteMessage = '';
if (isset($_GET['deleted'])) {
    $deleteMessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check-circle"></i> Client deleted successfully
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}
?>

<div class="page-wrapper">
    
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Clients Management</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Clients</li>
            </ol>
        </div>
    </div>
    
    <div class="container-fluid">
        
        <div class="card">
            <div class="card-body">
                <?php echo $deleteMessage; ?>
                
                <div class="mb-3">
                    <a href="clients_form.php" class="btn btn-primary btn-flat">
                        <i class="fa fa-plus"></i> Add New Client
                    </a>
                </div>
                
                <!-- Search & Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or code..." />
                    </div>
                    <div class="col-md-3">
                        <select id="statusFilter" class="form-control">
                            <option value="">-- All Status --</option>
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                            <option value="SUSPENDED">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="businessFilter" class="form-control">
                            <option value="">-- All Business Types --</option>
                            <option value="Retail">Retail</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Clinic">Clinic</option>
                            <option value="Distributor">Distributor</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- Clients Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="clientsTable">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 8%;">Code</th>
                                <th style="width: 20%;">Name</th>
                                <th style="width: 12%;">Contact</th>
                                <th style="width: 15%;">GSTIN</th>
                                <th style="width: 15%;">Business Type</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 20%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="clientsBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                    <div id="noData" class="alert alert-info text-center" style="display: none;">
                        <i class="fa fa-info-circle"></i> No clients found
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Client View Modal -->
<div class="modal fade" id="clientViewModal" tabindex="-1" role="dialog" aria-labelledby="clientViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="clientViewModalLabel"><i class="fa fa-user"></i> Client Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Client Code:</strong> <span id="viewClientCode">-</span></div>
                    <div class="col-md-4"><strong>Status:</strong> <span id="viewClientStatus">-</span></div>
                    <div class="col-md-4"><strong>Business Type:</strong> <span id="viewBusinessType">-</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Name:</strong> <span id="viewClientName">-</span></div>
                    <div class="col-md-6"><strong>Contact Phone:</strong> <span id="viewContactPhone">-</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Email:</strong> <span id="viewEmail">-</span></div>
                    <div class="col-md-6"><strong>Created Date:</strong> <span id="viewCreatedDate">-</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><strong>GSTIN:</strong> <span id="viewGstin">-</span></div>
                    <div class="col-md-6"><strong>PAN:</strong> <span id="viewPan">-</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Credit Limit:</strong> Rs. <span id="viewCreditLimit">0.00</span></div>
                    <div class="col-md-4"><strong>Outstanding:</strong> Rs. <span id="viewOutstanding">0.00</span></div>
                    <div class="col-md-4"><strong>Payment Terms:</strong> <span id="viewPaymentTerms">-</span> days</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>City:</strong> <span id="viewCity">-</span></div>
                    <div class="col-md-4"><strong>State:</strong> <span id="viewState">-</span></div>
                    <div class="col-md-4"><strong>Country:</strong> <span id="viewCountry">-</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Billing Address:</strong>
                        <div id="viewBillingAddress" class="border rounded p-2 bg-light">-</div>
                    </div>
                    <div class="col-md-6">
                        <strong>Shipping Address:</strong>
                        <div id="viewShippingAddress" class="border rounded p-2 bg-light">-</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Notes:</strong>
                        <div id="viewNotes" class="border rounded p-2 bg-light">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="viewEditClientBtn"><i class="fa fa-pencil"></i> Edit Client</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    let allClients = [];

    function normalizeStatus(status) {
        const value = (status || '').toString().trim().toUpperCase();
        const allowed = ['ACTIVE', 'INACTIVE', 'SUSPENDED'];
        return allowed.includes(value) ? value : 'ACTIVE';
    }

    function normalizeText(value) {
        const text = (value || '').toString().trim();
        return text === '' ? '-' : text;
    }

    function normalizeMoney(value) {
        const amount = parseFloat(value);
        if (Number.isNaN(amount)) {
            return '0.00';
        }
        return amount.toFixed(2);
    }
    
    // Load clients on page load
    loadClients();
    
    function loadClients() {
        $.ajax({
            url: 'php_action/fetchClients.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    allClients = response.data;
                    displayClients(allClients);
                } else {
                    console.error('Error loading clients');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#clientsBody').html('<tr><td colspan="8"><div class="alert alert-danger">Error loading clients</div></td></tr>');
            }
        });
    }
    
    function displayClients(clients) {
        const tbody = $('#clientsBody');
        tbody.empty();
        
        if (clients.length === 0) {
            $('#noData').show();
            $('#clientsTable').hide();
            return;
        }
        
        $('#noData').hide();
        $('#clientsTable').show();
        
        clients.forEach((client, index) => {
            const normalizedStatus = normalizeStatus(client.status);
            const statusBadge = getStatusBadge(normalizedStatus);
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${client.client_code || '-'}</strong></td>
                    <td>${client.name}</td>
                    <td>${client.contact_phone || '-'}</td>
                    <td>${client.gstin || '-'}</td>
                    <td><span class="badge badge-info">${client.business_type}</span></td>
                    <td>${statusBadge}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info mb-1 view-btn" data-id="${client.client_id}" title="View">
                            <i class="fa fa-eye"></i> View
                        </button>
                        <a href="clients_form.php?id=${client.client_id}" class="btn btn-sm btn-primary mb-1" title="Edit">
                            <i class="fa fa-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger mb-1 delete-btn" data-id="${client.client_id}" title="Delete">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function getStatusBadge(status) {
        const badges = {
            'ACTIVE': '<span class="badge badge-success">Active</span>',
            'INACTIVE': '<span class="badge badge-secondary">Inactive</span>',
            'SUSPENDED': '<span class="badge badge-danger">Suspended</span>'
        };
        return badges[status] || status;
    }

    // View details modal
    $(document).on('click', '.view-btn', function() {
        const clientId = parseInt($(this).data('id'), 10);
        const client = allClients.find(c => parseInt(c.client_id, 10) === clientId);

        if (!client) {
            alert('Client details not found. Please refresh the page.');
            return;
        }

        const normalizedStatus = normalizeStatus(client.status);

        $('#viewClientCode').text(normalizeText(client.client_code));
        $('#viewClientStatus').html(getStatusBadge(normalizedStatus));
        $('#viewBusinessType').text(normalizeText(client.business_type));
        $('#viewClientName').text(normalizeText(client.name));
        $('#viewContactPhone').text(normalizeText(client.contact_phone));
        $('#viewEmail').text(normalizeText(client.email));
        $('#viewCreatedDate').text(normalizeText(client.created_date));
        $('#viewGstin').text(normalizeText(client.gstin));
        $('#viewPan').text(normalizeText(client.pan));
        $('#viewCreditLimit').text(normalizeMoney(client.credit_limit));
        $('#viewOutstanding').text(normalizeMoney(client.outstanding_balance));
        $('#viewPaymentTerms').text(normalizeText(client.payment_terms));
        $('#viewCity').text(normalizeText(client.city));
        $('#viewState').text(normalizeText(client.state));
        $('#viewCountry').text(normalizeText(client.country));
        $('#viewBillingAddress').text(normalizeText(client.billing_address));
        $('#viewShippingAddress').text(normalizeText(client.shipping_address));
        $('#viewNotes').text(normalizeText(client.notes));
        $('#viewEditClientBtn').attr('href', `clients_form.php?id=${client.client_id}`);

        $('#clientViewModal').modal('show');
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        applyFilters();
    });
    
    $('#statusFilter').on('change', function() {
        applyFilters();
    });
    
    $('#businessFilter').on('change', function() {
        applyFilters();
    });
    
    function applyFilters() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const statusFilter = $('#statusFilter').val();
        const businessFilter = $('#businessFilter').val();
        
        const filtered = allClients.filter(client => {
            const normalizedStatus = normalizeStatus(client.status);
            const matchSearch = client.name.toLowerCase().includes(searchTerm) || 
                              (client.client_code && client.client_code.toLowerCase().includes(searchTerm));
            const matchStatus = statusFilter === '' || normalizedStatus === statusFilter;
            const matchBusiness = businessFilter === '' || client.business_type === businessFilter;
            
            return matchSearch && matchStatus && matchBusiness;
        });
        
        displayClients(filtered);
    }
    
    // Delete functionality
    $(document).on('click', '.delete-btn', function() {
        const clientId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
            $.ajax({
                url: 'php_action/deleteClient.php',
                type: 'POST',
                data: { client_id: clientId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Reload clients without page refresh
                        loadClients();
                        alert('Client deleted successfully');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting client: ' + error);
                }
            });
        }
    });
});
</script>
