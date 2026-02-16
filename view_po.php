<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>

<?php 
include('./constant/connect.php');

$poId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$poId) {
    die('<div class="alert alert-danger">Invalid PO ID</div>');
}

$sql = "SELECT po.*, 
        (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.po_id) as item_count
        FROM purchase_order po WHERE po.po_id = $poId";
$result = $connect->query($sql);

if($result->num_rows == 0) {
    die('<div class="alert alert-danger">PO not found</div>');
}

$po = $result->fetch_assoc();
$itemsSql = "SELECT * FROM purchase_order_items WHERE po_id = $poId";


    // echo "<pre>"; // Makes the output human-readable in a browser
    // print_r($itemsSql);
    // echo "</pre>";

    // exit;

$itemsResult = $connect->query($itemsSql);
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-8 align-self-center">
            <h3 class="text-primary">View Purchase Order</h3>
        </div>
        <div class="col-md-4 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="po_list.php">POs</a></li>
                <li class="breadcrumb-item active">View</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <!-- PO Header -->
        <div class="card">
            <div class="card-header bg-primary">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-white m-0">PO #<?php echo htmlspecialchars($po['po_number']); ?></h5>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="label <?php echo ($po['po_status'] == 'Cancelled') ? 'label-danger' : 'label-success'; ?>">
                            <?php echo htmlspecialchars($po['po_status']); ?>
                        </span>
                        <span class="label <?php echo ($po['payment_status'] == 'Paid') ? 'label-success' : 'label-warning'; ?>">
                            <?php echo htmlspecialchars($po['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>PO Date</h6>
                        <p><?php echo date('d-m-Y', strtotime($po['po_date'])); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>PO Type</h6>
                        <p><?php echo htmlspecialchars($po['po_type']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Expected Delivery</h6>
                        <p><?php echo date('d-m-Y', strtotime($po['expected_delivery_date'])); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Items</h6>
                        <p><strong><?php echo intval($po['item_count']); ?> items</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Info -->
        <div class="card mt-3">
            <div class="card-header bg-info">
                <h5 class="text-white m-0">Supplier Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><?php echo htmlspecialchars($po['supplier_name']); ?></h6>
                        <p><?php echo htmlspecialchars($po['supplier_address']); ?></p>
                        <p><?php echo htmlspecialchars($po['supplier_city'] . ', ' . $po['supplier_state'] . ' - ' . $po['supplier_pincode']); ?></p>
                        <p><strong>GST:</strong> <?php echo htmlspecialchars($po['supplier_gst']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($po['supplier_contact']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($po['supplier_email']); ?></p>
                        <p><strong>Payment Terms:</strong> <?php echo htmlspecialchars($po['payment_terms']); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($po['payment_method']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card mt-3">
            <div class="card-header bg-success">
                <h5 class="text-white m-0">Line Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>HSN</th>
                                <th>Pack</th>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Amount</th>
                                <th>Tax %</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while($item = $itemsResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($item['medicine_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['hsn_code']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['pack_size']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['batch_number']) . "</td>";
                                echo "<td>" . date('d-m-Y', strtotime($item['expiry_date'])) . "</td>";
                                echo "<td class='text-right'>" . intval($item['quantity_ordered']) . "</td>";
                                echo "<td class='text-right'>₹" . number_format($item['unit_price'], 2) . "</td>";
                                echo "<td class='text-right'>₹" . number_format($item['line_amount'], 2) . "</td>";
                                echo "<td class='text-right'>" . number_format($item['tax_percent'], 2) . "%</td>";
                                echo "<td class='text-right'><strong>₹" . number_format($item['item_total'], 2) . "</strong></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            h6{
                color:black;
            }
        </style>

        <!-- Totals -->
        <div class="card mt-3">
            <div class="card-header bg-warning">
                <h5 class="text-dark m-0">Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-md-3">
                        <h6>Sub Total</h6>
                        <p style="font-size: 16px; font-weight: bold;">₹<?php echo number_format($po['sub_total'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Discount</h6>
                        <p style="font-size: 16px; font-weight: bold;">₹<?php echo number_format($po['total_discount'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Taxable Amount</h6>
                        <p style="font-size: 16px; font-weight: bold;">₹<?php echo number_format($po['taxable_amount'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Grand Total</h6>
                        <p style="font-size: 18px; font-weight: bold; color: #d32f2f;">₹<?php echo number_format($po['grand_total'], 2); ?></p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <h6>CGST (<?php echo number_format($po['cgst_percent'], 2); ?>%)</h6>
                        <p>₹<?php echo number_format($po['cgst_amount'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>SGST (<?php echo number_format($po['sgst_percent'], 2); ?>%)</h6>
                        <p>₹<?php echo number_format($po['sgst_amount'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>IGST (<?php echo number_format($po['igst_percent'], 2); ?>%)</h6>
                        <p>₹<?php echo number_format($po['igst_amount'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Round Off</h6>
                        <p>₹<?php echo number_format($po['round_off'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if(!empty($po['notes']) || !empty($po['terms_conditions'])): ?>
        <div class="card mt-3">
            <div class="card-header bg-secondary">
                <h5 class="text-white m-0">Additional Information</h5>
            </div>
            <div class="card-body">
                <?php if(!empty($po['notes'])): ?>
                <h6>Notes</h6>
                <p><?php echo nl2br(htmlspecialchars($po['notes'])); ?></p>
                <?php endif; ?>

                <?php if(!empty($po['terms_conditions'])): ?>
                <h6 style="margin-top: 15px;">Terms & Conditions</h6>
                <p><?php echo nl2br(htmlspecialchars($po['terms_conditions'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cancellation Info -->
        <?php if($po['cancelled_status'] == 1): ?>
        <div class="card mt-3">
            <div class="card-header bg-danger">
                <h5 class="text-white m-0">Cancellation Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Cancelled Date</h6>
                        <p><?php echo date('d-m-Y', strtotime($po['cancelled_date'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Reason</h6>
                        <p><?php echo htmlspecialchars($po['cancellation_reason']); ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h6>Details</h6>
                        <p><?php echo nl2br(htmlspecialchars($po['cancellation_details'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="mt-4 text-center">
            <a href="print_po.php?id=<?php echo intval($poId); ?>" class="btn btn-info" target="_blank">
                <i class="fa fa-print"></i> Print PO
            </a>
            <?php if($po['po_status'] !== 'Received' && $po['po_status'] !== 'Cancelled'): ?>
            <a href="edit_po.php?id=<?php echo intval($poId); ?>" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit PO
            </a>
            <a href="cancel_po.php?id=<?php echo intval($poId); ?>" class="btn btn-danger">
                <i class="fa fa-times"></i> Cancel PO
            </a>
            <?php endif; ?>
            <a href="po_list.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php');?>
