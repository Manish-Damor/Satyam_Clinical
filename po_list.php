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
$filterFromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filterToDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$searchPO = isset($_GET['search']) ? $_GET['search'] : '';

// Build query for PURCHASE ORDERS
$where = ['1=1'];
$params = [];

if ($filterSupplier > 0) {
    $where[] = "po.supplier_id = ?";
    $params[] = $filterSupplier;
}

if ($filterStatus) {
    $where[] = "po.po_status = ?";
    $params[] = $filterStatus;
}

if ($filterFromDate) {
    $where[] = "po.po_date >= ?";
    $params[] = $filterFromDate;
}

if ($filterToDate) {
    $where[] = "po.po_date <= ?";
    $params[] = $filterToDate;
}

if ($searchPO) {
    $where[] = "(po.po_number LIKE ? OR s.supplier_name LIKE ?)";
    $params[] = "%$searchPO%";
    $params[] = "%$searchPO%";
}

$whereClause = implode(' AND ', $where);

// Fetch Purchase Orders from purchase_orders table
$query = "
    SELECT 
        po.po_id, po.supplier_id, po.po_number, po.po_date, 
        po.expected_delivery_date, po.grand_total, po.po_status,
        po.payment_status, s.supplier_name,
        COUNT(poi.po_item_id) as item_count
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN po_items poi ON po.po_id = poi.po_id
    WHERE $whereClause
    GROUP BY po.po_id
    ORDER BY po.po_date DESC, po.po_id DESC
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
    $purchaseOrders = [];
    while ($row = $result->fetch_assoc()) {
        $purchaseOrders[] = $row;
    }
    $stmt->close();
} else {
    $purchaseOrders = [];
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
                <h3 class="text-primary"><i class="fa fa-shopping-cart"></i> Purchase Orders</h3>
            </div>
            <div class="col-md-4 align-self-center text-end">
                <a href="create_po.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Create New PO
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">Advanced Filters</div>
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-2">
                        <select name="supplier" class="form-control form-control-sm">
                            <option value="">-- All Suppliers --</option>
                            <?php foreach ($suppliers as $supp): ?>
                                <option value="<?=$supp['supplier_id']?>" <?=$filterSupplier==$supp['supplier_id']?'selected':''?>>
                                    <?=htmlspecialchars($supp['supplier_name'])?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">-- All Status --</option>
                            <option value="Draft" <?=$filterStatus=='Draft'?'selected':''?>>Draft</option>
                            <option value="Submitted" <?=$filterStatus=='Submitted'?'selected':''?>>Submitted</option>
                            <option value="Approved" <?=$filterStatus=='Approved'?'selected':''?>>Approved</option>
                            <option value="PartialReceived" <?=$filterStatus=='PartialReceived'?'selected':''?>>Partial Received</option>
                            <option value="Received" <?=$filterStatus=='Received'?'selected':''?>>Received</option>
                            <option value="Cancelled" <?=$filterStatus=='Cancelled'?'selected':''?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?=$filterFromDate?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?=$filterToDate?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control form-control-sm" id="searchBox" placeholder="Search PO # or Supplier" value="<?=$searchPO?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-sm w-100">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total POs</h5>
                        <h3 class="text-primary"><?=count($purchaseOrders)?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Total Amount</h5>
                        <h3 class="text-success">
                            ₹ <?=number_format(array_sum(array_column($purchaseOrders, 'grand_total')), 2)?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Pending Approval</h5>
                        <h3 class="text-warning">
                            <?=count(array_filter($purchaseOrders, fn($p) => $p['po_status'] == 'Draft' || $p['po_status'] == 'Submitted'))?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-muted">Received</h5>
                        <h3 class="text-info">
                            <?=count(array_filter($purchaseOrders, fn($p) => $p['po_status'] == 'Received'))?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Orders Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa fa-list"></i> Purchase Order List</h5>
            </div>
            <div class="card-body">
                <?php if (count($purchaseOrders) === 0): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No purchase orders found. 
                        <a href="create_po.php">Create a new PO</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:10%">PO Number</th>
                                    <th style="width:15%">Supplier</th>
                                    <th style="width:10%">PO Date</th>
                                    <th style="width:8%">Items</th>
                                    <th style="width:12%">Grand Total</th>
                                    <th style="width:12%">PO Status</th>
                                    <th style="width:12%">Payment Status</th>
                                    <th style="width:15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchaseOrders as $po): ?>
                                    <tr>
                                        <td><strong><a href="po_view.php?id=<?=$po['po_id']?>" class="text-primary"><?=htmlspecialchars($po['po_number'])?></a></strong></td>
                                        <td><?=htmlspecialchars($po['supplier_name'] ?? '-')?></td>
                                        <td><?=date('d M Y', strtotime($po['po_date']))?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?=$po['item_count']?></span></td>
                                        <td class="text-end fw-bold">₹ <?=number_format($po['grand_total'], 2)?></td>
                                        <td>
                                            <?php 
                                                $statusClass = match($po['po_status']) {
                                                    'Draft' => 'secondary',
                                                    'Submitted' => 'warning',
                                                    'Approved' => 'info',
                                                    'PartialReceived' => 'primary',
                                                    'Received' => 'success',
                                                    'Cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <span class="badge bg-<?=$statusClass?>"><?=$po['po_status']?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $payStatus = match($po['payment_status']) {
                                                    'NotDue' => 'info',
                                                    'Due' => 'warning',
                                                    'PartialPaid' => 'primary',
                                                    'Paid' => 'success',
                                                    'Overdue' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <span class="badge bg-<?=$payStatus?>"><?=$po['payment_status']?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="po_view.php?id=<?=$po['po_id']?>" class="btn btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="editorder.php?po_id=<?=$po['po_id']?>" class="btn btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button class="btn btn-success btn-approve-po" data-id="<?=$po['po_id']?>" data-status="<?=$po['po_status']?>" title="Approve">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-delete-po" data-id="<?=$po['po_id']?>" title="Cancel">
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
    // Real-time search in table
    $('#searchBox').on('keyup', function(){
        const val = $(this).val().toLowerCase();
        $('table tbody tr').each(function(){
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(val));
        });
    });

    // Approve PO
    $(document).on('click', '.btn-approve-po', function(){
        const poId = $(this).data('id');
        const currentStatus = $(this).data('status');
        
        if (currentStatus === 'Approved' || currentStatus === 'Received') {
            alert('This PO is already approved/received');
            return;
        }

        if (!confirm('Mark this PO as Approved?')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'approve_po',
                po_id: poId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ PO approved successfully');
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

    // Cancel/Delete PO
    $(document).on('click', '.btn-delete-po', function(){
        const poId = $(this).data('id');
        
        if (!confirm('Are you sure you want to cancel this PO?')) return;

        $.ajax({
            url: 'php_action/po_actions.php',
            method: 'POST',
            data: {
                action: 'cancel_po',
                po_id: poId
            },
            dataType: 'json',
            success: function(resp){
                if (resp.success) {
                    alert('✓ PO cancelled successfully');
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



