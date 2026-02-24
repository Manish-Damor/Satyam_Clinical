<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
require './constant/connect.php';
?>

<div class="page-wrapper">
    
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Sales Invoices</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Sales Invoices</li>
            </ol>
        </div>
    </div>
    
    <div class="container-fluid">
        
        <div class="card">
            <div class="card-body">
                
                <div class="mb-3">
                    <a href="sales_invoice_form.php" class="btn btn-primary btn-flat">
                        <i class="fa fa-plus"></i> Create New Invoice
                    </a>
                </div>
                
                <!-- Search & Filter -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by invoice number or client..." />
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="dateFrom" class="form-control" placeholder="From date" />
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="dateTo" class="form-control" placeholder="To date" />
                    </div>
                    <div class="col-md-2">
                        <select id="paymentTypeFilter" class="form-control">
                            <option value="">-- Payment Type --</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="paymentFilter" class="form-control">
                            <option value="">-- Payment Status --</option>
                            <option value="UNPAID">Unpaid</option>
                            <option value="PARTIAL">Partial</option>
                            <option value="PAID">Paid</option>
                        </select>
                    </div>
                </div>
                
                <!-- Invoices Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="invoicesTable">
                        <thead>
                            <tr>
                                <th style="width: 4%;">#</th>
                                <th style="width: 10%;">Invoice No.</th>
                                <th style="width: 15%;">Client</th>
                                <th style="width: 9%;">Date</th>
                                <th style="width: 9%;">Amount</th>
                                <th style="width: 8%;">Type</th>
                                <th style="width: 12%;">Payment Status</th>
                                <th style="width: 12%;">Paid / Due</th>
                                <th style="width: 12%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                    <div id="noData" class="alert alert-info text-center" style="display: none;">
                        <i class="fa fa-info-circle"></i> No invoices found
                    </div>
                </div>
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
    let allInvoices = [];
    
    // Load invoices on page load
    loadInvoices();
    
    function loadInvoices() {
        $.ajax({
            url: 'php_action/fetchSalesInvoices.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    allInvoices = response.data;
                    displayInvoices(allInvoices);
                } else {
                    console.error('Error loading invoices');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#invoicesBody').html('<tr><td colspan="8"><div class="alert alert-danger">Error loading invoices</div></td></tr>');
            }
        });
    }
    
    function displayInvoices(invoices) {
        const tbody = $('#invoicesBody');
        tbody.empty();
        
        if (invoices.length === 0) {
            $('#noData').show();
            $('#invoicesTable').hide();
            return;
        }
        
        $('#noData').hide();
        $('#invoicesTable').show();
        
        invoices.forEach((invoice, index) => {
            const paymentStatusBadge = getPaymentStatusBadge(invoice.payment_status);
            const paymentTypeBadge = getPaymentTypeBadge(invoice.payment_type);
            
            const row = `
                <tr>
                    <td align="center">${index + 1}</td>
                    <td><strong>${invoice.invoice_number}</strong></td>
                    <td>${invoice.client_name}</td>
                    <td>${formatDate(invoice.invoice_date)}</td>
                    <td align="right"><strong>₹${parseFloat(invoice.grand_total).toFixed(2)}</strong></td>
                    <td align="center">${paymentTypeBadge}</td>
                    <td align="center">${paymentStatusBadge}</td>
                    <td align="center">
                        <span class="text-success font-weight-bold">₹${parseFloat(invoice.paid_amount || 0).toFixed(2)}</span> / 
                        <span class="text-danger">₹${parseFloat(invoice.due_amount || 0).toFixed(2)}</span>
                    </td>
                    <td align="center">
                        <a href="sales_invoice_form.php?id=${invoice.invoice_id}" class="btn btn-xs btn-primary" title="Edit">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="print_invoice.php?id=${invoice.invoice_id}" target="_blank" class="btn btn-xs btn-success" title="Print">
                            <i class="fa fa-print"></i>
                        </a>
                        <button type="button" class="btn btn-xs btn-warning record-payment-btn" data-id="${invoice.invoice_id}" title="Record Payment">
                            <i class="fa fa-rupee"></i>
                        </button>
                        <button type="button" class="btn btn-xs btn-danger delete-btn" data-id="${invoice.invoice_id}" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function getInvoiceStatusBadge(status) {
        const badges = {
            'DRAFT': '<span class="badge badge-secondary">Draft</span>',
            'SUBMITTED': '<span class="badge badge-info">Submitted</span>',
            'FULFILLED': '<span class="badge badge-success">Fulfilled</span>',
            'CANCELLED': '<span class="badge badge-danger">Cancelled</span>'
        };
        return badges[status] || status;
    }
    
    function getPaymentTypeBadge(type) {
        const badges = {
            'Cash': '<span class="badge badge-info">💵 Cash</span>',
            'Credit': '<span class="badge badge-warning">📋 Credit</span>'
        };
        return badges[type] || type;
    }
    
    function getPaymentStatusBadge(status) {
        const badges = {
            'UNPAID': '<span class="badge badge-danger">Unpaid</span>',
            'PARTIAL': '<span class="badge badge-warning">Partial</span>',
            'PAID': '<span class="badge badge-success">Paid</span>'
        };
        return badges[status] || status;
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-IN');
    }
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        applyFilters();
    });
    
    $('#dateFrom').on('change', function() {
        applyFilters();
    });
    
    $('#dateTo').on('change', function() {
        applyFilters();
    });
    
    $('#paymentTypeFilter').on('change', function() {
        applyFilters();
    });
    
    $('#paymentFilter').on('change', function() {
        applyFilters();
    });
    
    function applyFilters() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        const paymentTypeFilter = $('#paymentTypeFilter').val();
        const paymentFilter = $('#paymentFilter').val();
        
        const filtered = allInvoices.filter(invoice => {
            const matchSearch = invoice.invoice_number.toLowerCase().includes(searchTerm) || 
                              invoice.client_name.toLowerCase().includes(searchTerm);
            const matchPaymentType = paymentTypeFilter === '' || invoice.payment_type === paymentTypeFilter;
            const matchPayment = paymentFilter === '' || invoice.payment_status === paymentFilter;
            
            let matchDate = true;
            if (dateFrom || dateTo) {
                const invoiceDate = new Date(invoice.invoice_date);
                if (dateFrom) {
                    matchDate = matchDate && invoiceDate >= new Date(dateFrom);
                }
                if (dateTo) {
                    matchDate = matchDate && invoiceDate <= new Date(dateTo);
                }
            }
            
            return matchSearch && matchPaymentType && matchPayment && matchDate;
        });
        
        displayInvoices(filtered);
    }
    
    // Record payment functionality
    $(document).on('click', '.record-payment-btn', function() {
        const invoiceId = $(this).data('id');
        const amount = prompt('Enter payment amount:');
        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            return;
        }
        const method = prompt('Enter payment method (Cash/Cheque/Card/etc):');
        const notes = prompt('Any notes/reference?');

        $.ajax({
            url: 'php_action/addInvoicePayment.php',
            type: 'POST',
            data: {
                invoice_id: invoiceId,
                transaction_type: 'PAYMENT',
                amount: parseFloat(amount),
                payment_method: method || '',
                notes: notes || ''
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert('Payment recorded');
                    loadInvoices();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error recording payment: ' + error);
            }
        });
    });

    // Delete functionality
    $(document).on('click', '.delete-btn', function() {
        const invoiceId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this invoice?')) {
            $.ajax({
                url: 'php_action/deleteSalesInvoice.php',
                type: 'POST',
                data: { invoice_id: invoiceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadInvoices();
                        alert('Invoice deleted successfully');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting invoice: ' + error);
                }
            });
        }
    });
});
</script>
