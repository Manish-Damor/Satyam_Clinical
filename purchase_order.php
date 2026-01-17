<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>

<?php include('./constant/layout/sidebar.php');?>

<?php 
// Database connection and query
include('./constant/connect.php');
$user = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;

// Execute query with error handling
$sql = "SELECT po_id, po_date, vendor_name, vendor_contact, payment_status, id, grand_total FROM purchase_orders WHERE delete_status = 0 ORDER BY po_date DESC";
$result = $connect->query($sql);

if(!$result) {
    die("Query Error: " . $connect->error);
}
?>
       <div class="page-wrapper">
            
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary"> View Purchase Orders</h3> 
                </div>
                <div class="col-md-7 align-self-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                        <li class="breadcrumb-item active">View Purchase Orders</li>
                    </ol>
                </div>
            </div>
            
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <a href="add-purchase-order.php"><button class="btn btn-primary"><i class="fa fa-plus"></i> Add Purchase Order</button></a>
                         
                        <div class="table-responsive m-t-40">
                            <table id="myTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>PO Number</th>
                                        <th>PO Date</th>
                                        <th>Vendor Name</th>
                                        <th>Contact</th>
                                        <th>Total Amount</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if($result->num_rows > 0) {
                                        $count = 0;
                                        while($row = $result->fetch_assoc()) {
                                            $count++;
                                            $id = $row['id'];
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $count; ?></td>
                                        <td><?php echo htmlspecialchars($row['po_id']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['po_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['vendor_contact']); ?></td>
                                        <td>₹<?php echo number_format($row['grand_total'], 2); ?></td>
                                        <td>
                                            <span class="label <?php echo ($row['payment_status'] == 'Paid') ? 'label-success' : 'label-warning'; ?>">
                                                <?php echo htmlspecialchars($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-purchase-order.php?id=<?php echo intval($id); ?>" class="btn btn-info btn-sm" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="deletePO(<?php echo intval($id); ?>)" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                            <a href="print-purchase-order.php?id=<?php echo intval($id); ?>" class="btn btn-warning btn-sm" title="Print">
                                                <i class="fa fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No Purchase Orders Found</td></tr>";
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

<script src="custom/js/purchase_order.js"></script>
<script>
function deletePO(id) {
    if(confirm('Are you sure you want to delete this Purchase Order?')) {
        $.ajax({
            url: 'php_action/removePurchaseOrder.php',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                alert('Purchase Order deleted successfully');
                location.reload();
            }
        });
    }
}
</script>
