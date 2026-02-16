<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');

include('./constant/connect.php');


$sql = "SELECT po.po_id, po.po_number, po.po_date, po.supplier_name, po.supplier_contact, 
        po.grand_total, po.po_status, po.payment_status, po.cancelled_status,
        (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.po_id) as item_count
        FROM purchase_order po 
        WHERE po.cancelled_status = 0
        ORDER BY po.po_date DESC";

    // echo "<pre>"; // Makes the output human-readable in a browser
    // echo($sql);
    // echo "</pre>";

    // exit;


$result = $connect->query($sql);

if(!$result) {
    die("Query Error: " . $connect->error);
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Purchase Orders</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Purchase Orders</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <a href="create_po.php"><button class="btn btn-primary"><i class="fa fa-plus"></i> Create New PO</button></a>
                <a href="supplier.php"><button class="btn btn-info"><i class="fa fa-building"></i> Manage Suppliers</button></a>
                <a href="po_cancelled.php"><button class="btn btn-warning"><i class="fa fa-ban"></i> Cancelled POs</button></a>
                 
                <div class="table-responsive m-t-40">
                    <table class="table table-bordered table-striped" id="poTable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:5%;">#</th>
                                <th style="width:12%;">PO Number</th>
                                <th style="width:10%;">PO Date</th>
                                <th style="width:20%;">Supplier</th>
                                <th style="width:10%;">Contact</th>
                                <th style="width:10%;">Items</th>
                                <th style="width:12%;">Grand Total</th>
                                <th style="width:10%;">PO Status</th>
                                <th style="width:10%;">Payment Status</th>
                                <th style="width:15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($result->num_rows > 0) {
                                $count = 0;
                                while($row = $result->fetch_assoc()) {
                                    $count++;
                                    $poStatus = $row['po_status'];
                                    $paymentStatus = $row['payment_status'];
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $count; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['po_number']); ?></strong></td>
                                <td><?php echo date('d-m-Y', strtotime($row['po_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_contact']); ?></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo intval($row['item_count']); ?></span>
                                </td>
                                <td class="text-right"><strong>₹<?php echo number_format($row['grand_total'], 2); ?></strong></td>
                                <td>
                                    <span class="label <?php 
                                        if($poStatus == 'Cancelled') echo 'label-danger';
                                        elseif($poStatus == 'Confirmed' || $poStatus == 'Received') echo 'label-success';
                                        elseif($poStatus == 'Draft') echo 'label-default';
                                        elseif($poStatus == 'Pending') echo 'label-warning';
                                        else echo 'label-info';
                                    ?>">
                                        <?php echo htmlspecialchars($poStatus); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="label <?php echo ($paymentStatus == 'Paid') ? 'label-success' : 'label-warning'; ?>">
                                        <?php echo htmlspecialchars($paymentStatus); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_po.php?id=<?php echo intval($row['po_id']); ?>" class="btn btn-info btn-sm" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="edit_po.php?id=<?php echo intval($row['po_id']); ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="print_po.php?id=<?php echo intval($row['po_id']); ?>" class="btn btn-warning btn-sm" title="Print">
                                        <i class="fa fa-print"></i>
                                    </a>
                                    <?php if($poStatus !== 'Received' && $poStatus !== 'Cancelled'): ?>
                                    <a href="cancel_po.php?id=<?php echo intval($row['po_id']); ?>" class="btn btn-danger btn-sm" title="Cancel">
                                        <i class="fa fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>No Purchase Orders Found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script>
$(document).ready(function() {
    $('#poTable').DataTable({
        "order": [[2, "desc"]]
    });
});
</script>



