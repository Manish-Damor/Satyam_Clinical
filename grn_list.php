<?php 
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';

// Fetch all GRNs with status
$grnSql = "
    SELECT 
        g.grn_id,
        g.grn_number,
        g.grn_date,
        g.po_number,
        g.supplier_name,
        g.status,
        g.quality_check_status,
        COUNT(gi.grn_item_id) as item_count,
        g.created_by,
        u.username as created_by_name
    FROM goods_received g
    LEFT JOIN grn_items gi ON g.grn_id = gi.grn_id
    LEFT JOIN users u ON g.created_by = u.user_id
    GROUP BY g.grn_id, g.grn_number, g.grn_date, g.po_number, g.supplier_name, g.status, g.quality_check_status, g.created_by, u.username
    ORDER BY g.grn_date DESC, g.grn_id DESC
";

$grnResult = $connect->query($grnSql);

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-primary">Goods Received Notes (GRN)</h3>
        </div>
        <div class="col-md-4 align-self-center text-right">
            <a href="create_grn.php" class="btn btn-primary btn-md">
                <i class="fa fa-plus"></i> New GRN
            </a>
        </div>
    </div>

    <div class="container-fluid">

        <?php
        if (isset($_SESSION['grn_success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
                . htmlspecialchars($_SESSION['grn_success'])
                . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
            unset($_SESSION['grn_success']);
        }
        if (isset($_SESSION['grn_error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
                . htmlspecialchars($_SESSION['grn_error'])
                . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
            unset($_SESSION['grn_error']);
        }
        ?>

        <!-- GRNs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">GRN List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>GRN Number</th>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>GRN Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Quality</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($grnResult && $grnResult->num_rows > 0) {
                                while($grn = $grnResult->fetch_assoc()) {
                                    $statusBadge = '';
                                    switch($grn['status']) {
                                        case 'approved':
                                            $statusBadge = '<span class="badge badge-success">Approved</span>';
                                            break;
                                        case 'rejected':
                                            $statusBadge = '<span class="badge badge-danger">Rejected</span>';
                                            break;
                                        case 'pending_approval':
                                            $statusBadge = '<span class="badge badge-warning">Pending Approval</span>';
                                            break;
                                        default:
                                            $statusBadge = '<span class="badge badge-secondary">' . htmlspecialchars($grn['status']) . '</span>';
                                    }

                                    $qualityBadge = '';
                                    switch($grn['quality_check_status']) {
                                        case 'approved':
                                            $qualityBadge = '<span class="badge badge-success">Passed</span>';
                                            break;
                                        case 'failed':
                                            $qualityBadge = '<span class="badge badge-danger">Failed</span>';
                                            break;
                                        case 'pending':
                                            $qualityBadge = '<span class="badge badge-info">Pending</span>';
                                            break;
                                        default:
                                            $qualityBadge = '<span class="badge badge-secondary">-</span>';
                                    }

                                    echo '<tr>';
                                    echo '<td><strong>' . htmlspecialchars($grn['grn_number']) . '</strong></td>';
                                    echo '<td>' . htmlspecialchars($grn['po_number']) . '</td>';
                                    echo '<td>' . htmlspecialchars($grn['supplier_name']) . '</td>';
                                    echo '<td>' . date('d M Y', strtotime($grn['grn_date'])) . '</td>';
                                    echo '<td class="text-center"><span class="badge badge-info">' . $grn['item_count'] . '</span></td>';
                                    echo '<td>' . $statusBadge . '</td>';
                                    echo '<td>' . $qualityBadge . '</td>';
                                    echo '<td>' . htmlspecialchars($grn['created_by_name'] ?? '-') . '</td>';
                                    echo '<td>';
                                    echo '<a href="view_grn.php?id=' . intval($grn['grn_id']) . '" class="btn btn-sm btn-info" title="View">';
                                    echo '<i class="fa fa-eye"></i></a> ';

                                    // Approval button for manager/admin
                                    if ($grn['status'] === 'pending_approval' && in_array($userRole, ['manager', 'admin'])) {
                                        echo '<button class="btn btn-sm btn-success" onclick="approveGRN(' . intval($grn['grn_id']) . ')" title="Approve">';
                                        echo '<i class="fa fa-check"></i></button> ';
                                    }

                                    // Edit button for own GRNs if pending
                                    if ($grn['created_by'] == $userId && $grn['status'] === 'pending_approval') {
                                        echo '<a href="edit_grn.php?id=' . intval($grn['grn_id']) . '" class="btn btn-sm btn-warning" title="Edit">';
                                        echo '<i class="fa fa-edit"></i></a> ';
                                    }

                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="9" class="text-center text-muted">No GRNs found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve GRN</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" id="grnIdToApprove" name="grn_id" value="">
                    <div class="form-group">
                        <label>Approval Notes</label>
                        <textarea class="form-control" name="approval_notes" rows="3" placeholder="Add notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitApproval()">Approve GRN</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveGRN(grnId) {
    document.getElementById('grnIdToApprove').value = grnId;
    $('#approveModal').modal('show');
}

function submitApproval() {
    const grnId = document.getElementById('grnIdToApprove').value;
    const notes = document.querySelector('#approveForm textarea[name="approval_notes"]').value;

    fetch('php_action/approveGRN.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `grn_id=${grnId}&approval_notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting approval');
    });
}
</script>

<?php
include('./constant/layout/footer.php');
?>
