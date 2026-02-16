<?php
include('./constant/connect.php');

$poId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$poId) {
    die('Invalid PO ID');
}

$sql = "SELECT * FROM purchase_order WHERE po_id = $poId";
$result = $connect->query($sql);

if($result->num_rows == 0) {
    die('PO not found');
}

$po = $result->fetch_assoc();

// Get items
$itemsSql = "SELECT * FROM purchase_order_items WHERE po_id = $poId";
$itemsResult = $connect->query($itemsSql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?php echo $po['po_number']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            background-color: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-info h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .company-info p {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }
        .document-title {
            text-align: right;
        }
        .document-title h1 {
            font-size: 28px;
            color: #d32f2f;
            margin: 0;
        }
        .document-title .po-number {
            font-size: 14px;
            color: #666;
        }
        .po-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-box {
            background-color: #f9f9f9;
            padding: 12px;
            border-left: 3px solid #333;
        }
        .detail-box h5 {
            font-size: 11px;
            color: #666;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .detail-box p {
            font-size: 12px;
            margin: 3px 0;
            color: #333;
        }
        .detail-box strong {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        thead {
            background-color: #f0f0f0;
        }
        th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #ddd;
            color: #333;
        }
        td {
            padding: 10px;
            font-size: 11px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .amount-col {
            text-align: right;
        }
        .totals {
            margin-left: auto;
            width: 300px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        .total-row.grand {
            background-color: #fff59d;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid #ff9800;
            padding: 12px;
        }
        .notes {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 3px solid #333;
            font-size: 11px;
        }
        .notes h5 {
            margin-bottom: 10px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        .signature {
            text-align: center;
            font-size: 11px;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
            }
            .ptr-column {
                display: none !important;
            }
        }
        .status-cancelled {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(220, 53, 69, 0.3);
            font-weight: bold;
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php if($po['cancelled_status'] == 1): ?>
    <div class="status-cancelled">CANCELLED</div>
    <?php endif; ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h2>SATYAM CLINICAL SUPPLIES</h2>
                <p>Address: 123 Medical Lane, Mumbai - 400001</p>
                <p>GST: 27AABCU9603R1Z0 | Contact: +91-9876543210</p>
                <p>Email: info@satyamclinical.com</p>
            </div>
            <div class="document-title">
                <h1>PURCHASE ORDER</h1>
                <div class="po-number">
                    <p>PO #: <?php echo htmlspecialchars($po['po_number']); ?></p>
                    <p>Date: <?php echo date('d-m-Y', strtotime($po['po_date'])); ?></p>
                </div>
            </div>
        </div>

        <!-- PO Details -->
        <div class="po-details">
            <div class="detail-box">
                <h5>Bill To:</h5>
                <strong><?php echo htmlspecialchars($po['supplier_name']); ?></strong>
                <p><?php echo htmlspecialchars($po['supplier_address']); ?></p>
                <p><?php echo htmlspecialchars($po['supplier_city'] . ', ' . $po['supplier_state'] . ' - ' . $po['supplier_pincode']); ?></p>
                <p><strong>GST:</strong> <?php echo htmlspecialchars($po['supplier_gst']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($po['supplier_contact']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($po['supplier_email']); ?></p>
            </div>
            <div class="detail-box">
                <h5>Ship To:</h5>
                <strong><?php echo htmlspecialchars($po['supplier_name']); ?></strong>
                <p><?php echo htmlspecialchars($po['supplier_address']); ?></p>
                <p><?php echo htmlspecialchars($po['supplier_city'] . ', ' . $po['supplier_state'] . ' - ' . $po['supplier_pincode']); ?></p>
                <p><strong>GST:</strong> <?php echo htmlspecialchars($po['supplier_gst']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($po['supplier_contact']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($po['supplier_email']); ?></p>
            </div>

            <div class="detail-box">
                <h5>DELIVERY DETAILS</h5>
                <p><?php echo htmlspecialchars($po['delivery_address']); ?></p>
                <p><?php echo htmlspecialchars($po['delivery_city'] . ', ' . $po['delivery_state'] . ' - ' . $po['delivery_pincode']); ?></p>
                <p><strong>Expected Delivery:</strong> <?php echo date('d-m-Y', strtotime($po['expected_delivery_date'])); ?></p>
                <p><strong>PO Status:</strong> <span style="color: #d32f2f;"><?php echo htmlspecialchars($po['po_status']); ?></span></p>
                <p><strong>Payment Terms:</strong> <?php echo htmlspecialchars($po['payment_terms']); ?></p>
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">Sr.</th>
                    <th style="width: 25%;">Medicine Name</th>
                    <th style="width: 8%;">HSN Code</th>
                    <th style="width: 10%;">Pack Size</th>
                    <th style="width: 8%;">Batch No.</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 8%;">MRP</th>
                    <th style="width: 8%;">Rate</th>
                    <th style="width: 10%;">Amount</th>
                    <th style="width: 8%;">Tax %</th>
                    <th style="width: 12%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr = 1;
                while($item = $itemsResult->fetch_assoc()) {
                ?>
                <tr>
                    <td><?php echo $sr++; ?></td>
                    <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['hsn_code']); ?></td>
                    <td><?php echo htmlspecialchars($item['pack_size']); ?></td>
                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                    <td class="amount-col"><?php echo intval($item['quantity_ordered']); ?></td>
                    <td class="amount-col">₹<?php echo number_format($item['mrp'] ?? 0, 2); ?></td>
                    <td class="amount-col">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="amount-col">₹<?php echo number_format($item['line_amount'], 2); ?></td>
                    <td class="amount-col"><?php echo number_format($item['tax_percent'], 2); ?>%</td>
                    <td class="amount-col"><strong>₹<?php echo number_format($item['item_total'], 2); ?></strong></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div style="display: flex; justify-content: flex-end;">
            <div class="totals">
                <div class="total-row">
                    <span>Sub Total:</span>
                    <span>₹<?php echo number_format($po['sub_total'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Discount:</span>
                    <span>₹<?php echo number_format($po['total_discount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Taxable Amount:</span>
                    <span>₹<?php echo number_format($po['taxable_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>CGST (9%):</span>
                    <span>₹<?php echo number_format($po['cgst_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>SGST (9%):</span>
                    <span>₹<?php echo number_format($po['sgst_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>IGST (18%):</span>
                    <span>₹<?php echo number_format($po['igst_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Round Off:</span>
                    <span>₹<?php echo number_format($po['round_off'], 2); ?></span>
                </div>
                <div class="total-row grand">
                    <span>GRAND TOTAL:</span>
                    <span>₹<?php echo number_format($po['grand_total'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if(!empty($po['notes']) || !empty($po['terms_conditions'])): ?>
        <div class="notes">
            <?php if(!empty($po['notes'])): ?>
            <h5>Special Instructions:</h5>
            <p><?php echo nl2br(htmlspecialchars($po['notes'])); ?></p>
            <?php endif; ?>
            
            <?php if(!empty($po['terms_conditions'])): ?>
            <h5 style="margin-top: 10px;">Terms & Conditions:</h5>
            <p><?php echo nl2br(htmlspecialchars($po['terms_conditions'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Cancellation Info -->
        <?php if($po['cancelled_status'] == 1): ?>
        <div class="notes" style="border-left-color: #d32f2f; background-color: #ffebee;">
            <h5 style="color: #d32f2f;">CANCELLATION DETAILS</h5>
            <p><strong>Cancelled Date:</strong> <?php echo date('d-m-Y', strtotime($po['cancelled_date'])); ?></p>
            <p><strong>Reason:</strong> <?php echo htmlspecialchars($po['cancellation_reason']); ?></p>
            <p><strong>Details:</strong> <?php echo htmlspecialchars($po['cancellation_details']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div class="signature">
                <p>Prepared By</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature">
                <p>Authorized By</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature">
                <p>Supplier Acceptance</p>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>

<?php $connect->close(); ?>
