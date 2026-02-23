<?php
// Professional PO Print Page - Print Optimized
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Invalid Purchase Order ID');
}

require_once 'constant/connect.php';

$poId = intval($_GET['id']);

// Fetch PO
$poRes = $connect->query("SELECT * FROM purchase_orders WHERE po_id = $poId");
if (!$poRes || $poRes->num_rows === 0) {
    die('Purchase Order not found');
}
$po = $poRes->fetch_assoc();

// Fetch supplier
$suppRes = $connect->query("SELECT * FROM suppliers WHERE supplier_id = {$po['supplier_id']} LIMIT 1");
$supplier = $suppRes ? $suppRes->fetch_assoc() : null;

// Fetch items
$itemsRes = $connect->query("SELECT * FROM po_items WHERE po_id = $poId ORDER BY po_item_id ASC");
$items = [];
while ($item = $itemsRes->fetch_assoc()) {
    $items[] = $item;
}

// Calculate totals
$totalQty = array_sum(array_column($items, 'quantity_ordered'));
$totalReceived = array_sum(array_column($items, 'quantity_received'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO - <?= htmlspecialchars($po['po_number']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 0;
            margin: 0;
        }
        
        .print-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
            background: white;
            line-height: 1.3;
        }
        
        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 8mm;
            border-bottom: 2px solid #000;
            padding-bottom: 5mm;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header p {
            font-size: 9pt;
            margin: 1mm 0;
        }
        
        /* TOP INFO - 2 COLUMNS */
        .top-info {
            display: flex;
            gap: 15mm;
            margin-bottom: 6mm;
        }
        
        .top-left, .top-right {
            flex: 1;
            font-size: 9pt;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 2mm 0;
            padding: 0 2mm;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 45mm;
        }
        
        .info-value {
            text-align: right;
            word-break: break-word;
        }
        
        /* SUPPLIER & DELIVERY - 2 COLUMNS */
        .mid-section {
            display: flex;
            gap: 15mm;
            margin-bottom: 6mm;
        }
        
        .info-box {
            flex: 1;
            font-size: 9pt;
            border: 1px solid #000;
            padding: 4mm;
        }
        
        .info-box h3 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 3mm;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
        }
        
        .box-row {
            margin: 1.5mm 0;
            display: flex;
            flex-direction: column;
        }
        
        .box-label {
            font-weight: bold;
            font-size: 8.5pt;
        }
        
        .box-value {
            font-size: 9pt;
            word-break: break-word;
            margin-left: 2mm;
        }
        
        /* ITEMS TABLE */
        .items-section {
            margin-bottom: 6mm;
        }
        
        .items-section h3 {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3mm;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 4mm;
        }
        
        table thead {
            background: #333;
            color: white;
        }
        
        table th {
            border: 1px solid #000;
            padding: 3mm 2mm;
            text-align: left;
            font-weight: bold;
            font-size: 8.5pt;
        }
        
        table td {
            border: 1px solid #ccc;
            padding: 2.5mm 2mm;
            text-align: left;
        }
        
        table td.num {
            text-align: right;
        }
        
        table tbody tr:nth-child(even) {
            background: #f5f5f5;
        }
        
        /* TOTALS - RIGHT ALIGNED BOX */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 6mm;
        }
        
        .totals-box {
            width: 75mm;
            font-size: 9pt;
            border: 2px solid #000;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2mm 4mm;
            border-bottom: 1px solid #ccc;
        }
        
        .total-row.grand {
            background: #333;
            color: white;
            font-weight: bold;
            border: none;
            padding: 3mm 4mm;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-value {
            text-align: right;
            min-width: 30mm;
        }
        
        /* TERMS SECTION */
        .terms-section {
            font-size: 8.5pt;
            margin-bottom: 6mm;
            border: 1px solid #000;
            padding: 3mm;
            background: #f9f9f9;
        }
        
        .terms-section h4 {
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }
        
        .terms-section ul {
            margin-left: 4mm;
            line-height: 1.3;
        }
        
        .terms-section li {
            margin: 1mm 0;
        }
        
        /* SIGNATURES */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 8mm;
            font-size: 9pt;
        }
        
        .sig-block {
            flex: 1;
            text-align: center;
        }
        
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 20mm;
            padding-top: 1mm;
            font-weight: bold;
        }
        
        .sig-label {
            font-size: 8.5pt;
            height: 4mm;
        }
        
        /* FOOTER */
        .footer {
            font-size: 8pt;
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 1px solid #000;
            display: flex;
            justify-content: space-between;
        }
        
        .footer-left {
            flex: 1;
        }
        
        .footer-center {
            text-align: center;
            flex: 1;
        }
        
        .footer-right {
            text-align: right;
            flex: 1;
        }
        
        /* PRINT STYLES */
        @page {
            size: A4;
            margin: 8mm;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 8mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="print-container">
    <!-- HEADER -->
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <p>SATYAM CLINICAL SUPPLIES - PHARMACY DIVISION</p>
    </div>
    
    <!-- TOP INFO: 2 COLUMNS -->
    <div class="top-info">
        <div class="top-left">
            <div class="info-row">
                <span class="info-label">PO Number:</span>
                <span class="info-value"><strong><?= htmlspecialchars($po['po_number']) ?></strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">PO Date:</span>
                <span class="info-value"><?= date('d-M-Y', strtotime($po['po_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= htmlspecialchars($po['po_status']) ?></span>
            </div>
        </div>
        <div class="top-right">
            <div class="info-row">
                <span class="info-label">Expected Delivery:</span>
                <span class="info-value"><?= $po['expected_delivery_date'] ? date('d-M-Y', strtotime($po['expected_delivery_date'])) : 'N/A' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Delivery Location:</span>
                <span class="info-value"><?= htmlspecialchars($po['delivery_location'] ?? 'Main Warehouse') ?></span>
            </div>
        </div>
    </div>
    
    <!-- SUPPLIER & DELIVERY INFO: 2 COLUMNS -->
    <div class="mid-section">
        <div class="info-box">
            <h3>Supplier Information</h3>
            <?php if ($supplier): ?>
                <div class="box-row">
                    <span class="box-label">Name:</span>
                    <span class="box-value"><?= htmlspecialchars($supplier['supplier_name']) ?></span>
                </div>
                <div class="box-row">
                    <span class="box-label">Contact:</span>
                    <span class="box-value"><?= htmlspecialchars($supplier['contact_person'] ?? '-') ?></span>
                </div>
                <div class="box-row">
                    <span class="box-label">Phone:</span>
                    <span class="box-value"><?= htmlspecialchars($supplier['phone'] ?? '-') ?></span>
                </div>
                <div class="box-row">
                    <span class="box-label">Email:</span>
                    <span class="box-value"><?= htmlspecialchars($supplier['email'] ?? '-') ?></span>
                </div>
                <div class="box-row">
                    <span class="box-label">Address:</span>
                    <span class="box-value"><?= htmlspecialchars($supplier['address'] ?? '-') ?></span>
                </div>
            <?php else: ?>
                <div class="box-row">
                    <span class="box-value">Supplier not found</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h3>Delivery Details</h3>
            <div class="box-row">
                <span class="box-label">Location:</span>
                <span class="box-value"><?= htmlspecialchars($po['delivery_location'] ?? 'Main Warehouse') ?></span>
            </div>
            <div class="box-row">
                <span class="box-label">Expected By:</span>
                <span class="box-value"><?= $po['expected_delivery_date'] ? date('d-M-Y', strtotime($po['expected_delivery_date'])) : 'N/A' ?></span>
            </div>
            <div class="box-row">
                <span class="box-label">Total Items:</span>
                <span class="box-value"><?= count($items) ?></span>
            </div>
            <div class="box-row">
                <span class="box-label">Qty Ordered:</span>
                <span class="box-value"><?= number_format($totalQty, 2) ?></span>
            </div>
            <div class="box-row">
                <span class="box-label">Qty Received:</span>
                <span class="box-value"><?= number_format($totalReceived, 2) ?></span>
            </div>
        </div>
    </div>
    
    <!-- ITEMS TABLE -->
    <div class="items-section">
        <h3>Ordered Items</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Product</th>
                    <th style="width: 10%;" class="num">Qty Ord</th>
                    <th style="width: 10%;" class="num">Qty Rcv</th>
                    <th style="width: 12%;" class="num">Unit Price</th>
                    <th style="width: 8%;" class="num">GST%</th>
                    <th style="width: 15%;" class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($item['product_name'] ?? $item['item_description'] ?? 'N/A') ?></td>
                        <td class="num"><?= number_format($item['quantity_ordered'] ?? 0, 2) ?></td>
                        <td class="num"><?= number_format($item['quantity_received'] ?? 0, 2) ?></td>
                        <td class="num">₹ <?= number_format($item['unit_price'] ?? 0, 2) ?></td>
                        <td class="num"><?= number_format($item['gst_percentage'] ?? 0, 0) ?>%</td>
                        <td class="num"><strong>₹ <?= number_format($item['total_price'] ?? 0, 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- TOTALS -->
    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-value">₹ <?= number_format($po['subtotal'], 2) ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Discount (<?= number_format($po['discount_percentage'] ?? 0, 1) ?>%):</span>
                <span class="total-value">- ₹ <?= number_format($po['discount_amount'] ?? 0, 2) ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">GST (<?= number_format($po['gst_percentage'] ?? 0, 1) ?>%):</span>
                <span class="total-value">₹ <?= number_format($po['gst_amount'], 2) ?></span>
            </div>
            <?php if ($po['other_charges'] > 0): ?>
                <div class="total-row">
                    <span class="total-label">Other Charges:</span>
                    <span class="total-value">₹ <?= number_format($po['other_charges'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span class="total-label">GRAND TOTAL:</span>
                <span class="total-value">₹ <?= number_format($po['grand_total'], 2) ?></span>
            </div>
        </div>
    </div>
    
    <!-- TERMS & CONDITIONS -->
    <div class="terms-section">
        <h4>Terms & Conditions</h4>
        <ul>
            <li>Payment: As per agreement</li>
            <li>Delivery: As per agreed schedule</li>
            <li>Products must meet quality standards</li>
            <li>GRN to be prepared on receipt</li>
            <li>Deviations must be reported immediately</li>
            <li>Rates inclusive of applicable taxes</li>
        </ul>
    </div>
    
    <!-- SIGNATURES -->
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-label">Prepared By</div>
            <div class="sig-line"></div>
            <p style="font-size: 8pt; margin-top: 1mm;">Warehouse / Procurement</p>
        </div>
        <div class="sig-block">
            <div class="sig-label">Approved By</div>
            <div class="sig-line"></div>
            <p style="font-size: 8pt; margin-top: 1mm;">Manager / Director</p>
        </div>
        <div class="sig-block">
            <div class="sig-label">Supplier Accepted</div>
            <div class="sig-line"></div>
            <p style="font-size: 8pt; margin-top: 1mm;">Authorized Rep</p>
        </div>
    </div>
    
    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-left">
            <strong>Bank Details:</strong><br/>
            A/C: XXXXXXXXXXXX<br/>
            IFSC: BANK0001
        </div>
        <div class="footer-center">
            <strong>GST: 27AABCT1234A1Z0</strong><br/>
            <strong>PAN: AABCT1234A</strong>
        </div>
        <div class="footer-right">
            Generated: <?= date('d-M-Y H:i') ?><br/>
            PO ID: <?= $poId ?><br/>
            Page 1 of 1
        </div>
    </div>
</div>

</body>
</html>


</body>
</html>
