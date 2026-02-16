<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

$sql = "SELECT po.po_id, po.po_number, po.po_date, po.supplier_name, po.grand_total, 
        po.cancelled_date, po.cancellation_reason,
        (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.po_id) as item_count
        FROM purchase_order po 
        WHERE po.cancelled_status = 1
        ORDER BY po.cancelled_date DESC";

$result = $connect->query($sql);

if(!$result) {
    die("Query Error: " . $connect->error);
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-danger"><i class="fa fa-ban"></i> Cancelled Purchase Orders</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="po_list.php">Purchase Orders</a></li>
                <li class="breadcrumb-item active">Cancelled</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <a href="po_list.php" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Active POs</a>
                 
                <div class="table-responsive m-t-40">
                    <table class="table table-bordered table-striped" id="cancelledTable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:5%;">#</th>
                                <th style="width:12%;">PO Number</th>
                                <th style="width:10%;">PO Date</th>
                                <th style="width:18%;">Supplier</th>
                                <th style="width:10%;">Items</th>
                                <th style="width:12%;">Amount</th>
                                <th style="width:12%;">Cancelled Date</th>
                                <th style="width:20%;">Reason</th>
                                <th style="width:10%;">Actions</th>
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
                                <td>
                                    <span class="text-danger"><strong><?php echo htmlspecialchars($row['po_number']); ?></strong></span>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($row['po_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td class="text-center">
                                    <span class="badge badge-warning"><?php echo intval($row['item_count']); ?></span>
                                </td>
                                <td class="text-right">₹<?php echo number_format($row['grand_total'], 2); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['cancelled_date'])); ?></td>
                                <td>
                                    <span class="label label-danger"><?php echo htmlspecialchars($row['cancellation_reason']); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewCancellationDetails(<?php echo intval($row['po_id']); ?>)" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <a href="print_po.php?id=<?php echo intval($row['po_id']); ?>&cancelled=1" class="btn btn-warning btn-sm" title="Print Cancelled">
                                        <i class="fa fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No Cancelled Purchase Orders Found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Cancellation Details -->
<div id="detailsModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Cancellation Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div id="detailsContent" class="modal-body">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script>
$(document).ready(function() {
    $('#cancelledTable').DataTable({
        "order": [[6, "desc"]]
    });
});

function viewCancellationDetails(poId) {
    fetch(`php_action/getCancellationDetails.php?po_id=${poId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>PO Number</h6>
                        <p><strong>${data.po_number}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Supplier</h6>
                        <p><strong>${data.supplier_name}</strong></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>PO Date</h6>
                        <p><strong>${data.po_date}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Cancelled Date</h6>
                        <p><strong>${data.cancelled_date}</strong></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Cancellation Reason</h6>
                        <p><span class="label label-danger">${data.cancellation_reason}</span></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Cancellation Details</h6>
                        <p>${data.cancellation_details}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>PO Amount</h6>
                        <p><strong>₹${data.grand_total}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Refund Status</h6>
                        <p><span class="label label-warning">${data.refund_status}</span></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Items in this PO</h6>
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Rate</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.items.map(item => `
                                    <tr>
                                        <td>${item.medicine_name}</td>
                                        <td class="text-center">${item.quantity_ordered}</td>
                                        <td class="text-right">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td class="text-right">₹${parseFloat(item.item_total).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('detailsContent').innerHTML = html;
            $('#detailsModal').modal('show');
        })
        .catch(error => {
            alert('Error loading details');
            console.error(error);
        });
}
</script>
