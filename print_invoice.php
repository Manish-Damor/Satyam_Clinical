<?php
/**
 * PROFESSIONAL SALES INVOICE PRINT TEMPLATE
 * Print-optimized PDF-friendly format
 * 2-column layout with company branding
 * PTR hidden from print view
 */

require './constant/connect.php';

// Get invoice ID from URL
$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$invoiceId) {
    die('Invoice not found');
}

// Fetch invoice details
$stmt = $connect->prepare("
    SELECT si.*, c.name as client_name, c.contact_phone, c.email, c.gstin as client_gstin,
           c.billing_address, c.shipping_address, c.city, c.state, c.postal_code
    FROM sales_invoices si
    LEFT JOIN clients c ON si.client_id = c.client_id
    WHERE si.invoice_id = ? AND si.deleted_at IS NULL
");

$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$invoiceResult = $stmt->get_result();

if ($invoiceResult->num_rows === 0) {
    die('Invoice not found');
}

$invoice = $invoiceResult->fetch_assoc();

// Fetch invoice items
$itemStmt = $connect->prepare("
    SELECT sii.*, p.product_name, p.content, p.pack_size, p.hsn_code
    FROM sales_invoice_items sii
    LEFT JOIN product p ON sii.product_id = p.product_id
    WHERE sii.invoice_id = ?
    ORDER BY sii.item_id ASC
");

$itemStmt->bind_param('i', $invoiceId);
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Company details (hardcoded - customize as needed)
$company = [
    'name' => 'Satyam Clinical Pharmacy',
    'address' => 'Nashik, Maharashtra, India',
    'gstin' => '27XXXXXXX1234K1',
    'pan' => 'AAAXP1234A',
    'phone' => '+91-XXXXX-XXXXX',
    'email' => 'info@satyamclinical.com'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            color: #000;
            background: #fff;
            font-size: 12px;
            line-height: 1.4;
        }
        
        @media print {
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
            }
            .page {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 10mm;
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
            body, html {
                background: white;
                color: black;
            }
        }
        
        @media screen {
            .page {
                width: 8.5in;
                height: 11in;
                margin: 20px auto;
                padding: 10mm;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                background: white;
            }
        }
        
        .page {
            background: white;
            color: black;
            position: relative;
            overflow: hidden;
        }
        
        /* Header Section */
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .company-info {
            display: grid;
            grid-template-columns: 2fr 2fr;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .company-details h3 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .company-details p {
            font-size: 10px;
            margin: 2px 0;
        }
        
        .invoice-meta {
            text-align: right;
        }
        
        .invoice-meta table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .invoice-meta td {
            padding: 3px 5px;
            border: 1px solid #000;
        }
        
        .invoice-meta .label {
            font-weight: bold;
            background: #f0f0f0;
            width: 40%;
        }
        
        /* Addresses Section */
        .addresses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
            border: 1px solid #000;
            padding: 10px;
        }
        
        .address-box h4 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        
        .address-box p {
            font-size: 10px;
            margin: 2px 0;
            line-height: 1.3;
        }
        
        /* Items Table */
        .items-section {
            margin: 20px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 5px;
        }
        
        .items-table thead {
            background: #000;
            color: #fff;
            font-weight: bold;
        }
        
        .items-table th {
            padding: 6px;
            text-align: left;
            border: 1px solid #000;
            font-size: 10px;
        }
        
        .items-table td {
            padding: 6px;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Financial Summary */
        .summary-section {
            float: right;
            width: 45%;
            margin-top: 10px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            border: 1px solid #000;
        }
        
        .summary-table tr {
            border-bottom: 1px solid #000;
        }
        
        .summary-table td {
            padding: 5px;
        }
        
        .summary-table .label {
            font-weight: bold;
            width: 50%;
            text-align: left;
        }
        
        .summary-table .amount {
            text-align: right;
            width: 50%;
        }
        
        .summary-table .total-row {
            background: #000;
            color: #fff;
            font-weight: bold;
        }
        
        .summary-table .total-row td {
            padding: 8px;
            font-size: 12px;
        }
        
        /* Notes & Terms */
        .notes-section {
            clear: both;
            margin-top: 30px;
            font-size: 10px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }
        
        .notes-section h4 {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .notes-section p {
            margin: 3px 0;
            line-height: 1.3;
        }
        
        /* Signature Section */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
        }
        
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            min-height: 40px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        
        .sig-label {
            font-weight: bold;
            font-size: 9px;
        }
        
        /* Footer */
        .footer {
            border-top: 1px solid #000;
            margin-top: 15px;
            padding-top: 8px;
            font-size: 9px;
            text-align: center;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            border-top: 1px solid #000;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .footer-box {
            text-align: center;
            font-size: 9px;
        }
        
        .footer-box strong {
            display: block;
            margin-bottom: 3px;
        }
        
        /* Hidden from Print */
        .no-print {
            margin: 20px;
            text-align: center;
        }
        
        .no-print button {
            padding: 10px 20px;
            margin: 0 5px;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid #ccc;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .no-print button:hover {
            background: #e0e0e0;
        }
        
        /* Hide PTR column from print */
        .ptr-column {
            display: none;
        }
        
        @media screen {
            .ptr-column {
                display: table-cell;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="fa fa-print"></i> Print Invoice</button>
    <button onclick="window.history.back()">← Back</button>
</div>

<div class="page">
    <!-- Header Section -->
    <div class="header">
        <div class="company-info">
            <div class="company-details">
                <h3><?php echo htmlspecialchars($company['name']); ?></h3>
                <p><?php echo htmlspecialchars($company['address']); ?></p>
                <p>Phone: <?php echo htmlspecialchars($company['phone']); ?></p>
                <p>Email: <?php echo htmlspecialchars($company['email']); ?></p>
            </div>
            <div class="invoice-meta">
                <table>
                    <tr>
                        <td class="label">Invoice No.</td>
                        <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Date</td>
                        <td><?php echo date('d-M-Y', strtotime($invoice['invoice_date'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Due Date</td>
                        <td><?php echo $invoice['due_date'] ? date('d-M-Y', strtotime($invoice['due_date'])) : '-'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td><?php echo htmlspecialchars($invoice['invoice_status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Addresses Section -->
    <div class="addresses">
        <div class="address-box">
            <h4>BILL TO</h4>
            <p><strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong></p>
            <p><?php echo htmlspecialchars($invoice['billing_address']); ?></p>
            <p><?php 
                $cityState = [];
                if ($invoice['city']) $cityState[] = $invoice['city'];
                if ($invoice['state']) $cityState[] = $invoice['state'];
                if ($invoice['postal_code']) $cityState[] = $invoice['postal_code'];
                echo htmlspecialchars(implode(', ', $cityState));
            ?></p>
            <p>Phone: <?php echo htmlspecialchars($invoice['contact_phone']); ?></p>
            <?php if ($invoice['client_gstin']): ?>
                <p>GSTIN: <?php echo htmlspecialchars($invoice['client_gstin']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="address-box">
            <h4>SHIP TO</h4>
            <p><?php 
                if ($invoice['delivery_address']) {
                    echo nl2br(htmlspecialchars($invoice['delivery_address']));
                } else {
                    echo '<em>Same as Billing Address</em>';
                }
            ?></p>
        </div>
    </div>
    
    <!-- Items Section -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">SL</th>
                    <th style="width: 30%;">Medicine / Product</th>
                    <th style="width: 8%;">HSN</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 12%;">Rate</th>
                    <th class="ptr-column" style="width: 10%;">PTR</th>
                    <th style="width: 8%;">GST %</th>
                    <th style="width: 13%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $slNo = 1;
                foreach ($items as $item):
                    $lineTotal = $item['line_total'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $slNo++; ?></td>
                    <td class="text-left">
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                        <small><?php echo htmlspecialchars($item['content'] . ' ' . $item['pack_size']); ?></small>
                    </td>
                    <td class="text-center"><?php echo htmlspecialchars($item['hsn_code']); ?></td>
                    <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                    <td class="text-right">₹<?php echo number_format($item['unit_rate'], 2); ?></td>
                    <td class="ptr-column text-right">₹<?php echo $item['purchase_rate'] ? number_format($item['purchase_rate'], 2) : '-'; ?></td>
                    <td class="text-right"><?php echo number_format($item['gst_rate'], 1); ?></td>
                    <td class="text-right">₹<?php echo number_format($lineTotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Financial Summary -->
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">₹<?php echo number_format($invoice['subtotal'], 2); ?></td>
            </tr>
            <?php if ($invoice['discount_amount'] > 0): ?>
            <tr>
                <td class="label">Discount (<?php echo number_format($invoice['discount_percent'], 1); ?>%):</td>
                <td class="amount">-₹<?php echo number_format($invoice['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label">GST (18%):</td>
                <td class="amount">₹<?php echo number_format($invoice['gst_amount'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td class="label">GRAND TOTAL:</td>
                <td class="amount">₹<?php echo number_format($invoice['grand_total'], 2); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Notes & Payment Terms -->
    <div class="notes-section">
        <h4>Payment Terms & Conditions:</h4>
        <p>• Payment is due within <?php echo intval($invoice['due_date'] ? (strtotime($invoice['due_date']) - strtotime($invoice['invoice_date'])) / (60*60*24) : 30); ?> days of invoice date</p>
        <p>• Cheques should be drawn in favor of "<?php echo htmlspecialchars($company['name']); ?>"</p>
        <p>• All medicines are subject to expiry date validation at the time of delivery</p>
        <p>• GST Registration No. (GSTIN): <?php echo htmlspecialchars($company['gstin']); ?></p>
        <p>• PAN: <?php echo htmlspecialchars($company['pan']); ?></p>
    </div>
    
    <!-- Signature Section -->
    <div class="signatures">
        <div>
            <div class="sig-line"></div>
            <div class="sig-label">Prepared By</div>
        </div>
        <div>
            <div class="sig-line"></div>
            <div class="sig-label">Authorized By</div>
        </div>
        <div>
            <div class="sig-line"></div>
            <div class="sig-label">Received By</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Generated on <?php echo date('d-M-Y H:i'); ?></p>
        <p>This is a computer generated document and does not require signature.</p>
    </div>
</div>

</body>
</html>
