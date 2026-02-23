<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<?php
// Database connection
if (!isset($connect) || !$connect) {
    die("Database connection error");
}

// Get filter parameters
$filterSupplier = isset($_GET['supplier']) ? intval($_GET['supplier']) : 0;
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterGstType = isset($_GET['gst_type']) ? $_GET['gst_type'] : '';
$filterFromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filterToDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$searchInvoice = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$where = ['1=1'];
$params = [];

if ($filterSupplier > 0) {
    $where[] = "pi.supplier_id = ?";
    $params[] = $filterSupplier;
}

if ($filterStatus) {
    $where[] = "pi.status = ?";
    $params[] = $filterStatus;
}

if ($filterGstType) {
    $where[] = "pi.gst_determination_type = ?";
    $params[] = $filterGstType;
}

if ($filterFromDate) {
    $where[] = "pi.invoice_date >= ?";
    $params[] = $filterFromDate;
}

if ($filterToDate) {
    $where[] = "pi.invoice_date <= ?";
    $params[] = $filterToDate;
}

if ($searchInvoice) {
    $where[] = "(pi.invoice_no LIKE ? OR s.supplier_name LIKE ?)";
    $params[] = "%$searchInvoice%";
    $params[] = "%$searchInvoice%";
}

$whereClause = implode(' AND ', $where);

// Fetch invoices
$query = "
    SELECT 
        pi.id, pi.supplier_id, pi.invoice_no, pi.invoice_date, 
        pi.grand_total, pi.paid_amount, pi.outstanding_amount,
        pi.status, pi.gst_determination_type,
        s.supplier_name,
        COUNT(pii.id) as item_count
    FROM purchase_invoices pi
    LEFT JOIN suppliers s ON pi.supplier_id = s.supplier_id
    LEFT JOIN purchase_invoice_items pii ON pi.id = pii.invoice_id
    WHERE $whereClause
    GROUP BY pi.id
    ORDER BY pi.invoice_date DESC, pi.id DESC
    LIMIT 500
";

$stmt = $connect->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    $stmt->close();
} else {
    $invoices = [];
}

// Fetch suppliers for filter dropdown
$suppliers = [];
$res = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_status='Active' ORDER BY supplier_name");
if ($res) while ($r = $res->fetch_assoc()) $suppliers[] = $r;
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row page-titles mb-3">
            <div class="col-md-8 align-self-center">
                <h3 class="text-primary"><i class="fa fa-list"></i> Purchase Invoices</h3>
                <small class="text-muted">Manage all purchase invoices and track payments</small>
            </div>
            <div class="col-md-4 align-self-center text-end">
                <a href="purchase_invoice.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Create New Invoice
                </a>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fa fa-filter"></i> Filters & Search</h5>
            </div>
            <div class="card-body">
                <form method="GET" id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" class="form-control form-control-sm">
                            <option value="">-- All Suppliers --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?=$s['supplier_id']?>" <?=$filterSupplier==$s['supplier_id']?'selected':''?>>
                                    <?=htmlspecialchars($s['supplier_name'])?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">-- All Status --</option>
                            <option value="Draft" <?=$filterStatus=='Draft'?'selected':''?>>Draft</option>
                            <option value="Received" <?=$filterStatus=='Received'?'selected':''?>>Received</option>
                            <option value="Matched" <?=$filterStatus=='Matched'?'selected':''?>>Matched</option>
                            <option value="Approved" <?=$filterStatus=='Approved'?'selected':''?>>Approved</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">GST Type</label>
                        <select name="gst_type" class="form-control form-control-sm">
                            <option value="">-- All Types --</option>
                            <option value="intrastate" <?=$filterGstType=='intrastate'?'selected':''?>>Intra-State</option>
                            <option value="interstate" <?=$filterGstType=='interstate'?'selected':''?>>Inter-State</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?=$filterFromDate?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?=$filterToDate?>">
                    </div>

                    <div class="col-md-1 pt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa fa-search"></i> Filter
                        </button>
                    </div>
                </form>

                <hr>

                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" id="searchBox" placeholder="Search by Invoice # or Supplier..." class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="location.href='invoice_list.php'">
                            <i class="fa fa-redo"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total Invoices</h5>
                        <h3 class="text-primary"><?=count($invoices)?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total Amount</h5>
                        <h3 class="text-success">
                            ₹ <?=number_format(array_sum(array_column($invoices, 'grand_total')), 2)?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Outstanding</h5>
                        <h3 class="text-warning">
                            ₹ <?=number_format(array_sum(array_column($invoices, 'outstanding_amount')), 2)?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Paid Amount</h5>
                        <h3 class="text-info">
                            ₹ <?=number_format(array_sum(array_column($invoices, 'paid_amount')), 2)?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa fa-table"></i> Invoice List</h5>
            </div>
            <div class="card-body">
                <?php if (count($invoices) === 0): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No invoices found. 
                        <a href="purchase_invoice.php">Create a new invoice</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:10%">Invoice #</th>
                                    <th style="width:15%">Supplier</th>
                                    <th style="width:10%">Date</th>
                                    <th style="width:8%">Items</th>
                                    <th style="width:10%">GST Type</th>
                                    <th style="width:12%">Grand Total</th>
                                    <th style="width:12%">Outstanding</th>
                                    <th style="width:10%">Status</th>
                                    <th style="width:12%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <a href="invoice_view.php?id=<?=$inv['id']?>" class="text-primary">
                                                    <?=htmlspecialchars($inv['invoice_no'])?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td><?=htmlspecialchars($inv['supplier_name'])?></td>
                                        <td><?=date('d M Y', strtotime($inv['invoice_date']))?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?=$inv['item_count']?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?=$inv['gst_determination_type']=='intrastate'?'bg-info':'bg-warning'?>">
                                                <?=$inv['gst_determination_type']=='intrastate'?'Intra-State':'Inter-State'?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ₹ <?=number_format($inv['grand_total'], 2)?>
                                        </td>
                                        <td class="text-end <?=$inv['outstanding_amount']>0?'text-warning':'text-success'?>">
                                            <?=number_format($inv['outstanding_amount'], 2)?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = match($inv['status']) {
                                                    'Draft' => 'secondary',
                                                    'Received' => 'info',
                                                    'Matched' => 'warning',
                                                    'Approved' => 'success',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <span class="badge bg-<?=$statusClass?>">
                                                <?=$inv['status']?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="invoice_view.php?id=<?=$inv['id']?>" class="btn btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="invoice_view.php?id=<?=$inv['id']?>&print=1" target="_blank" class="btn btn-secondary" title="Print">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                                <?php if ($inv['status'] === 'Draft'): ?>
                                                    <a href="invoice_edit.php?id=<?=$inv['id']?>" class="btn btn-warning" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button class="btn btn-success btn-approve" data-id="<?=$inv['id']?>" data-status="<?=$inv['status']?>" title="Approve">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-delete" data-id="<?=$inv['id']?>" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    // Real-time search
    $('#searchBox').on('keyup', function(){
        const val = $(this).val().toLowerCase();
        $('table tbody tr').each(function(){
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(val));
        });
    });

    // Approve button
    $(document).on('click', '.btn-approve', function(){
        const invoiceId = $(this).data('id');
        const currentStatus = $(this).data('status');
        
        if (currentStatus === 'Approved') {
            alert('This invoice is already approved');
            return;
        }

        if (!confirm('Mark this invoice as Approved?')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'approve_invoice',
                invoice_id: invoiceId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ Invoice approved successfully');
                    location.reload();
                } else {
                    alert('✗ Error: ' + resp.error);
                }
            },
            error: function(){
                alert('Server error occurred');
            }
        });
    });

    // Delete button
    $(document).on('click', '.btn-delete', function(){
        const invoiceId = $(this).data('id');
        
        if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'delete_invoice',
                invoice_id: invoiceId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ Invoice deleted successfully');
                    location.reload();
                } else {
                    alert('✗ Error: ' + resp.error);
                }
            },
            error: function(){
                alert('Server error occurred');
            }
        });
    });
</script>

</body>
</html>



