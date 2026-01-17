<?php 
include('./constant/connect.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$id) {
    die('Invalid Purchase Order ID');
}

$sql = "SELECT * FROM purchase_orders WHERE id = $id AND delete_status = 0";
$result = $connect->query($sql);

if(!$result) {
    die("Query Error: " . $connect->error);
}

if($result->num_rows == 0) {
    die('Purchase Order not found');
}

$po = $result->fetch_assoc();

$itemsSql = "SELECT poi.*, p.product_name as productName FROM po_items poi LEFT JOIN product p ON poi.product_id = p.product_id WHERE poi.po_master_id = $id";
$itemsResult = $connect->query($itemsSql);

if(!$itemsResult) {
    die("Query Error: " . $connect->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?php echo htmlspecialchars($po['po_id']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .po-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .section {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .info-row {
            margin: 5px 0;
            padding: 3px 0;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            width: 50%;
            margin-left: 50%;
            margin-top: 20px;
        }
        .total-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .total-label {
            font-weight: bold;
        }
        .grand-total {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 16px;
            padding: 10px 0;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button onclick="window.print()">Print Purchase Order</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="company-name">SATYAM CLINICAL SUPPLIES</div>
            <div class="po-title">PURCHASE ORDER</div>
        </div>

        <div class="content">
            <div class="section">
                <div class="section-title">PURCHASE ORDER DETAILS</div>
                <div class="info-row">
                    <span class="label">PO Number:</span>
                    <span><?php echo htmlspecialchars($po['po_id']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">PO Date:</span>
                    <span><?php echo date('d-m-Y', strtotime($po['po_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">PO Status:</span>
                    <span><?php echo htmlspecialchars($po['po_status']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Expected Delivery:</span>
                    <span><?php echo date('d-m-Y', strtotime($po['expected_delivery_date'])); ?></span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">VENDOR DETAILS</div>
                <div class="info-row">
                    <span class="label">Vendor Name:</span>
                    <span><?php echo htmlspecialchars($po['vendor_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Contact:</span>
                    <span><?php echo htmlspecialchars($po['vendor_contact']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span><?php echo htmlspecialchars($po['vendor_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Address:</span>
                    <span><?php echo htmlspecialchars($po['vendor_address']); ?></span>
                </div>
            </div>
        </div>

        <h3>ITEMS ORDERED</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product/Medicine</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 0;
                while($item = $itemsResult->fetch_assoc()) {
                    $count++;
                ?>
                <tr>
                    <td><?php echo $count; ?></td>
                    <td><?php echo htmlspecialchars($item['productName']); ?></td>
                    <td class="text-right"><?php echo intval($item['quantity']); ?></td>
                    <td class="text-right">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right">₹<?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php 
                }
                ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span class="total-label">Sub Total:</span>
                <span class="text-right">₹<?php echo number_format($po['sub_total'], 2); ?></span>
            </div>
            <?php if($po['discount'] > 0): ?>
            <div class="total-row">
                <span class="total-label">Discount (<?php echo floatval($po['discount']); ?>%):</span>
                <span class="text-right">₹<?php echo number_format(($po['sub_total'] * $po['discount']) / 100, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if($po['gst'] > 0): ?>
            <div class="total-row">
                <span class="total-label">GST (<?php echo floatval($po['gst']); ?>%):</span>
                <span class="text-right">₹<?php echo number_format((($po['sub_total'] - (($po['sub_total'] * $po['discount']) / 100)) * $po['gst']) / 100, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span class="text-right">₹<?php echo number_format($po['grand_total'], 2); ?></span>
            </div>
        </div>

        <?php if(!empty($po['notes'])): ?>
        <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd;">
            <strong>Notes:</strong>
            <p><?php echo nl2br(htmlspecialchars($po['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr;">
            <div style="text-align: center;">
                <p>___________________</p>
                <p>Authorized By</p>
            </div>
            <div style="text-align: center;">
                <p>___________________</p>
                <p>Vendor Signature</p>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer generated document. No signature required.</p>
            <p>Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
