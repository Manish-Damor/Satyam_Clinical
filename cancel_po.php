<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

$poId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$poId) {
    die('<div class="alert alert-danger">Invalid PO ID</div>');
}

$sql = "SELECT * FROM purchase_order WHERE po_id = $poId";
$result = $connect->query($sql);

if($result->num_rows == 0) {
    die('<div class="alert alert-danger">PO not found</div>');
}

$po = $result->fetch_assoc();
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-danger">Cancel Purchase Order</h3>
        </div>
        <div class="col-md-4 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="po_list.php">POs</a></li>
                <li class="breadcrumb-item active">Cancel PO</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <!-- PO Details -->
        <div class="card">
            <div class="card-header bg-info">
                <h5 class="text-white m-0">Purchase Order Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>PO Number</h6>
                        <p><strong><?php echo htmlspecialchars($po['po_number']); ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6>PO Date</h6>
                        <p><strong><?php echo date('d-m-Y', strtotime($po['po_date'])); ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Supplier</h6>
                        <p><strong><?php echo htmlspecialchars($po['supplier_name']); ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Grand Total</h6>
                        <p><strong style="font-size: 18px; color: #d32f2f;">₹<?php echo number_format($po['grand_total'], 2); ?></strong></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Status</h6>
                        <p>
                            <span class="label label-info"><?php echo htmlspecialchars($po['po_status']); ?></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Items Count</h6>
                        <p>
                            <?php
                            $itemSql = "SELECT COUNT(*) as cnt FROM purchase_order_items WHERE po_id = $poId";
                            $itemResult = $connect->query($itemSql);
                            $itemRow = $itemResult->fetch_assoc();
                            echo '<strong>' . intval($itemRow['cnt']) . ' items</strong>';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Form -->
        <div class="card mt-3">
            <div class="card-header bg-danger">
                <h5 class="text-white m-0">Cancellation Details</h5>
            </div>
            <form id="cancelForm" method="POST" action="php_action/cancelPO.php">
                <input type="hidden" name="po_id" value="<?php echo intval($poId); ?>">
                
                <div class="card-body">
                    <div id="cancelMessages"></div>
                    
                    <div class="alert alert-warning">
                        <strong><i class="fa fa-exclamation-triangle"></i> Warning!</strong><br>
                        Cancelling this PO will not delete it but mark it as cancelled. You can track the cancellation history.
                    </div>

                    <div class="form-group">
                        <label>Cancellation Reason *</label>
                        <select class="form-control" id="cancellationReason" name="cancellation_reason" required onchange="toggleOtherReason()">
                            <option value="">-- Select Reason --</option>
                            <option value="Supplier Request">Supplier Request</option>
                            <option value="Incorrect Order">Incorrect Order</option>
                            <option value="Product Discontinued">Product Discontinued</option>
                            <option value="Duplicate Order">Duplicate Order</option>
                            <option value="Budget Issue">Budget Issue</option>
                            <option value="Quality Issue">Quality Issue</option>
                            <option value="Delivery Issue">Delivery Issue</option>
                            <option value="Other">Other - Please Specify</option>
                        </select>
                    </div>

                    <div class="form-group" id="otherReasonDiv" style="display: none;">
                        <label>Please Specify Reason</label>
                        <input type="text" class="form-control" id="otherReason" name="other_reason" placeholder="Enter the cancellation reason">
                    </div>

                    <div class="form-group">
                        <label>Detailed Cancellation Notes *</label>
                        <textarea class="form-control" name="cancellation_details" rows="5" required placeholder="Provide detailed explanation for cancellation...
For example: 
- Item not needed anymore
- Supplier failed to confirm delivery date
- Quality concerns with previous orders
- Budget constraints"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Expected Refund Amount</label>
                        <div class="input-group">
                            <span class="input-group-addon">₹</span>
                            <input type="number" class="form-control" name="refund_amount" step="0.01" value="<?php echo $po['grand_total']; ?>" readonly>
                        </div>
                        <small class="form-text text-muted">Default is the full PO amount. Modify if partial refund is expected.</small>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmCancellation" required>
                        <label class="form-check-label" for="confirmCancellation">
                            I confirm the cancellation of this PO and understand it cannot be undone.
                        </label>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fa fa-times"></i> Confirm Cancellation
                    </button>
                    <a href="po_list.php" class="btn btn-secondary btn-lg">Go Back</a>
                </div>
            </form>
        </div>

        <!-- Items That Will Be Cancelled -->
        <div class="card mt-3">
            <div class="card-header bg-secondary">
                <h5 class="text-white m-0">Items in this PO</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Amount</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $itemsSql = "SELECT medicine_name, quantity_ordered, unit_price, line_amount, tax_amount, item_total 
                                        FROM purchase_order_items WHERE po_id = $poId";
                            $itemsResult = $connect->query($itemsSql);
                            
                            while($item = $itemsResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($item['medicine_name']) . "</td>";
                                echo "<td class='text-center'>" . intval($item['quantity_ordered']) . "</td>";
                                echo "<td class='text-right'>₹" . number_format($item['unit_price'], 2) . "</td>";
                                echo "<td class='text-right'>₹" . number_format($item['line_amount'], 2) . "</td>";
                                echo "<td class='text-right'>₹" . number_format($item['tax_amount'], 2) . "</td>";
                                echo "<td class='text-right'><strong>₹" . number_format($item['item_total'], 2) . "</strong></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold;">
                                <td colspan="5" class="text-right">Grand Total:</td>
                                <td class="text-right" style="background-color: #ffebee;">₹<?php echo number_format($po['grand_total'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>

<script>
function toggleOtherReason() {
    const reason = document.getElementById('cancellationReason').value;
    const otherDiv = document.getElementById('otherReasonDiv');
    const otherInput = document.getElementById('otherReason');
    
    if(reason === 'Other') {
        otherDiv.style.display = 'block';
        otherInput.required = true;
    } else {
        otherDiv.style.display = 'none';
        otherInput.required = false;
    }
}

document.getElementById('cancelForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if(!document.getElementById('confirmCancellation').checked) {
        alert('Please confirm the cancellation');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('php_action/cancelPO.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('PO cancelled successfully!');
            window.location.href = 'po_list.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error cancelling PO');
    });
});
</script>
