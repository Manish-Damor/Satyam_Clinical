<?php
require_once __DIR__ . '/php_action/purchase_invoice_action.php';

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    die('Invalid invoice ID');
}

$invoiceId = intval($_GET['id']);
$invoice = PurchaseInvoiceAction::getInvoice($invoiceId);
if (!$invoice) {
    die('Invoice not found');
}

$company = [
    'title' => 'Satyam Clinical',
    'short_title' => 'SC',
    'currency_symbol' => '₹',
    'invoice_logo' => '',
    'address' => 'Nashik, Maharashtra, India',
    'phone' => '-',
    'gstin' => '-',
    'footer' => ''
];

$manageWebsiteCheck = $connect->query("SHOW TABLES LIKE 'manage_website'");
if ($manageWebsiteCheck && $manageWebsiteCheck->num_rows > 0) {
    $mwRes = $connect->query("SELECT title, short_title, invoice_logo, currency_symbol, footer FROM manage_website LIMIT 1");
    if ($mwRes && $mwRes->num_rows > 0) {
        $mw = $mwRes->fetch_assoc();
        if (!empty($mw['title'])) {
            $company['title'] = $mw['title'];
        }
        if (!empty($mw['short_title'])) {
            $company['short_title'] = $mw['short_title'];
        }
        if (!empty($mw['currency_symbol'])) {
            $company['currency_symbol'] = $mw['currency_symbol'];
        }
        if (!empty($mw['invoice_logo'])) {
            $company['invoice_logo'] = $mw['invoice_logo'];
        }
        if (!empty($mw['footer'])) {
            $company['footer'] = trim(strip_tags((string)$mw['footer']));
        }
    }
}

$currency = (string)$company['currency_symbol'];
$isIntra = (($invoice['gst_determination_type'] ?? '') === 'intrastate');
$items = is_array($invoice['items'] ?? null) ? $invoice['items'] : [];

$subtotal = (float)($invoice['subtotal'] ?? 0);
$discountTotal = (float)($invoice['total_discount'] ?? 0);
$taxableAmount = max(0, $subtotal - $discountTotal);
$freight = (float)($invoice['freight'] ?? 0);
$roundOff = (float)($invoice['round_off'] ?? 0);
$invoiceTotal = (float)($invoice['grand_total'] ?? 0);
$paidAmount = (float)($invoice['paid_amount'] ?? ($invoice['amount_paid'] ?? 0));
$outstandingAmount = (float)($invoice['outstanding_amount'] ?? 0);
$netPayable = $outstandingAmount > 0 ? $outstandingAmount : $invoiceTotal;

function fmtNum($value, $decimals = 2)
{
    return number_format((float)$value, $decimals);
}

function safeText($value)
{
    return htmlspecialchars((string)$value);
}

function fmtDate($value, $format = 'd-m-Y')
{
    return !empty($value) ? date($format, strtotime((string)$value)) : '-';
}

function wordsBelowThousand($number)
{
    $number = (int)$number;
    $ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    ];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $parts = [];
    if ($number >= 100) {
        $parts[] = $ones[intdiv($number, 100)] . ' Hundred';
        $number %= 100;
    }
    if ($number >= 20) {
        $parts[] = $tens[intdiv($number, 10)];
        $number %= 10;
    }
    if ($number > 0) {
        $parts[] = $ones[$number];
    }

    return trim(implode(' ', array_filter($parts)));
}

function numberToWordsIndian($number)
{
    $number = (int)$number;
    if ($number === 0) {
        return 'Zero';
    }

    $parts = [];

    $crore = intdiv($number, 10000000);
    if ($crore > 0) {
        $parts[] = wordsBelowThousand($crore) . ' Crore';
        $number %= 10000000;
    }

    $lakh = intdiv($number, 100000);
    if ($lakh > 0) {
        $parts[] = wordsBelowThousand($lakh) . ' Lakh';
        $number %= 100000;
    }

    $thousand = intdiv($number, 1000);
    if ($thousand > 0) {
        $parts[] = wordsBelowThousand($thousand) . ' Thousand';
        $number %= 1000;
    }

    if ($number > 0) {
        $parts[] = wordsBelowThousand($number);
    }

    return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
}

function amountInWordsIndian($amount)
{
    $amount = round(max(0, (float)$amount), 2);
    $rupees = (int)floor($amount);
    $paise = (int)round(($amount - $rupees) * 100);

    $text = numberToWordsIndian($rupees) . ' Rupees';
    if ($paise > 0) {
        $text .= ' and ' . numberToWordsIndian($paise) . ' Paise';
    }

    return trim($text) . ' only';
}

$totalQty = 0.0;
$totalFreeQty = 0.0;
$totalAmountBeforeDiscount = 0.0;
$totalTaxableFromItems = 0.0;
$totalTaxFromItems = 0.0;
$totalLineFromItems = 0.0;
$productPackById = [];

foreach ($items as $line) {
    $rowQty = (float)($line['qty'] ?? 0);
    $rowRate = (float)($line['unit_cost'] ?? 0);

    $totalQty += $rowQty;
    $totalFreeQty += (float)($line['free_qty'] ?? 0);
    $totalAmountBeforeDiscount += ($rowQty * $rowRate);
    $totalTaxableFromItems += (float)($line['taxable_value'] ?? 0);
    $totalTaxFromItems += (float)($line['tax_amount'] ?? 0);
    $totalLineFromItems += (float)($line['line_total'] ?? 0);
}

$missingPackProductIds = [];
foreach ($items as $line) {
    $snapshotPack = trim((string)($line['pack_size_snapshot'] ?? ''));
    $productId = intval($line['product_id'] ?? 0);
    if ($snapshotPack === '' && $productId > 0) {
        $missingPackProductIds[$productId] = true;
    }
}

if (!empty($missingPackProductIds)) {
    $idList = implode(',', array_map('intval', array_keys($missingPackProductIds)));
    $productPackRes = $connect->query("SELECT product_id, pack_size, content FROM product WHERE product_id IN ($idList)");
    if ($productPackRes) {
        while ($prow = $productPackRes->fetch_assoc()) {
            $pid = intval($prow['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $pack = trim((string)($prow['pack_size'] ?? ''));
            if ($pack === '') {
                $pack = trim((string)($prow['content'] ?? ''));
            }
            if ($pack !== '') {
                $productPackById[$pid] = $pack;
            }
        }
    }
}

$amountWords = amountInWordsIndian($invoiceTotal);
$netAmountWords = amountInWordsIndian($netPayable);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice Print - <?php echo safeText($invoice['invoice_no'] ?? ''); ?></title>
    <style>
        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

        .print-actions {
            max-width: 210mm;
            margin: 8px auto 0;
            padding: 0 4mm;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            border: 1px solid #333;
            background: #f8f8f8;
            color: #111;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 2px;
            cursor: pointer;
            text-decoration: none;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 6px auto;
            padding: 3mm;
            background: #fff;
        }

        .invoice {
            border: 1px solid #111;
        }

        .header {
            display: grid;
            grid-template-columns: 23mm 1fr 86mm;
            min-height: 30mm;
        }

        .logo-col {
            border-right: 1px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5mm;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
        }

        .logo-col img {
            max-width: 100%;
            max-height: 26mm;
            object-fit: contain;
        }

        .company-col {
            padding: 1.6mm 2mm;
            border-right: 1px solid #111;
        }

        .company-col h1 {
            margin: 0;
            font-size: 20px;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .company-meta {
            margin-top: 1.6mm;
            font-size: 9px;
            line-height: 1.25;
        }

        .head-right {
            display: grid;
            grid-template-columns: 1fr 22mm;
        }

        .head-right table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8px;
        }

        .head-right td {
            border-bottom: 1px solid #111;
            padding: 1mm 1.3mm;
            vertical-align: middle;
            line-height: 1.2;
        }

        .head-right tr:last-child td {
            border-bottom: none;
        }

        .head-right td:first-child {
            width: 38%;
            font-weight: 700;
            border-right: 1px solid #111;
        }

        .qr-box {
            border-left: 1px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 8px;
            padding: 1mm;
        }

        .qr-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .title-strip {
            display: grid;
            grid-template-columns: 1fr auto;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            align-items: center;
        }

        .tax-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            padding: 1.6mm 0;
        }

        .copy-type {
            border-left: 1px solid #111;
            padding: 0 2mm;
            font-size: 8.8px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .bill-party-row {
            display: grid;
            grid-template-columns: 1fr 76mm;
            border-bottom: 1px solid #111;
        }

        .party-left {
            border-right: 1px solid #111;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 34mm;
        }

        .party-card {
            border-right: 1px solid #111;
            padding: 1.6mm 1.8mm;
        }

        .party-card:last-child {
            border-right: none;
        }

        .party-title {
            margin: 0 0 1.1mm;
            border-bottom: 1px solid #111;
            padding-bottom: .8mm;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .party-line {
            font-size: 8.7px;
            line-height: 1.22;
            margin: .35mm 0;
            word-break: break-word;
        }

        .bill-right {
            display: flex;
            flex-direction: column;
        }

        .bill-right-title {
            border-bottom: 1px solid #111;
            font-size: 11px;
            font-weight: 700;
            padding: 1.4mm 1.8mm;
        }

        .bill-right table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.7px;
            height: 100%;
        }

        .bill-right td {
            border-bottom: 1px solid #111;
            border-left: 1px solid #111;
            padding: .95mm 1.4mm;
            line-height: 1.15;
            vertical-align: middle;
        }

        .bill-right tr:last-child td {
            border-bottom: none;
        }

        .bill-right td:first-child {
            border-left: none;
            width: 33%;
            font-weight: 700;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
        }

        .items th,
        .items td {
            border: 1px solid #111;
            padding: 1mm .8mm;
            line-height: 1.2;
            vertical-align: top;
            word-break: break-word;
        }

        .items th {
            text-align: center;
            font-size: 7.8px;
            font-weight: 700;
            background: #fff;
        }

        .items .total-row td {
            font-weight: 700;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .no-wrap { white-space: nowrap; }

        .bottom-wrap {
            display: grid;
            grid-template-columns: 1fr 74mm;
            align-items: stretch;
        }

        .terms-box {
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
            min-height: 42mm;
            padding: 1.8mm 2mm;
            font-size: 8.6px;
            line-height: 1.35;
        }

        .terms-title {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 1.1mm;
            text-transform: uppercase;
        }

        .totals-box {
            border-bottom: 1px solid #111;
            display: grid;
            grid-template-rows: auto auto;
        }

        .totals {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .totals td {
            border-bottom: 1px solid #111;
            padding: 1.3mm 1.6mm;
            line-height: 1.2;
        }

        .totals tr:last-child td {
            border-bottom: none;
        }

        .totals .label {
            width: 56%;
            font-weight: 700;
        }

        .totals .value {
            text-align: right;
            white-space: nowrap;
        }

        .totals .grand td {
            font-weight: 700;
            font-size: 10.4px;
        }

        .totals-extra {
            border-top: 1px solid #111;
            padding: 1.2mm 1.6mm;
            font-size: 8.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-lines {
            display: grid;
            grid-template-columns: 1fr 74mm;
        }

        .amount-words {
            border-right: 1px solid #111;
            padding: 1.6mm 2mm;
            font-size: 8.8px;
            line-height: 1.35;
        }

        .sign-box {
            padding: 1.6mm 1.6mm;
            font-size: 9px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sign-space {
            min-height: 16mm;
        }

        .sign-label {
            border-top: 1px solid #111;
            padding-top: 1.2mm;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 4mm;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }

            .items thead {
                display: table-header-group;
            }

            .items tr,
            .totals tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <a href="invoice_view.php?id=<?php echo (int)$invoiceId; ?>" class="btn">Back</a>
        <button onclick="window.print()" class="btn">Print</button>
    </div>

    <div class="sheet">
        <div class="invoice">
            <div class="header">
                <div class="logo-col">
                    <?php
                        $logoFile = trim((string)$company['invoice_logo']);
                        $logoPath = __DIR__ . '/assets/uploadImage/Logo/' . $logoFile;
                        if ($logoFile !== '' && file_exists($logoPath)):
                    ?>
                        <img src="assets/uploadImage/Logo/<?php echo safeText($logoFile); ?>" alt="Logo">
                    <?php else: ?>
                        <?php echo safeText($company['short_title']); ?>
                    <?php endif; ?>
                </div>

                <div class="company-col">
                    <h1><?php echo safeText($company['title']); ?></h1>
                    <div class="company-meta">
                        <div><?php echo safeText($company['address']); ?></div>
                        <div>Phone: <?php echo safeText($company['phone']); ?></div>
                        <div>GST No: <?php echo safeText($company['gstin']); ?></div>
                        <?php if (!empty($company['footer'])): ?>
                            <div><?php echo safeText($company['footer']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="head-right">
                    <table>
                        <tr><td>GST No.</td><td><?php echo safeText($company['gstin']); ?></td></tr>
                        <tr><td>PAN No.</td><td>-</td></tr>
                        <tr><td>D.L No.</td><td>-</td></tr>
                        <tr><td>FSSAI No.</td><td>-</td></tr>
                    </table>
                    <div class="qr-box">
                        <?php if (file_exists(__DIR__ . '/assets/uploadImage/Logo/bill.png')): ?>
                            <img src="assets/uploadImage/Logo/bill.png" alt="QR">
                        <?php else: ?>
                            QR
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="title-strip">
                <div class="tax-title">Tax Invoice</div>
                <div class="copy-type">Original / Duplicate / Triplicate</div>
            </div>

            <div class="bill-party-row">
                <div class="party-left">
                    <div class="party-card">
                        <div class="party-title">Bill To:</div>
                        <div class="party-line"><strong><?php echo safeText($invoice['supplier_name'] ?? '-'); ?></strong></div>
                        <div class="party-line"><?php echo safeText($invoice['company_name'] ?? '-'); ?></div>
                        <div class="party-line"><?php echo safeText($invoice['address'] ?? '-'); ?></div>
                        <div class="party-line">Phone: <?php echo safeText($invoice['phone'] ?? '-'); ?></div>
                        <div class="party-line">GST No: <?php echo safeText($invoice['supplier_gstin'] ?? '-'); ?></div>
                        <div class="party-line">Place of Supply: <?php echo safeText($invoice['place_of_supply'] ?? '-'); ?></div>
                    </div>
                    <div class="party-card">
                        <div class="party-title">Ship To:</div>
                        <div class="party-line"><strong><?php echo safeText($invoice['supplier_name'] ?? '-'); ?></strong></div>
                        <div class="party-line"><?php echo safeText($invoice['company_name'] ?? '-'); ?></div>
                        <div class="party-line"><?php echo safeText($invoice['address'] ?? '-'); ?></div>
                        <div class="party-line">Phone: <?php echo safeText($invoice['phone'] ?? '-'); ?></div>
                        <div class="party-line">GST No: <?php echo safeText($invoice['supplier_gstin'] ?? '-'); ?></div>
                        <div class="party-line">Email: <?php echo safeText($invoice['email'] ?? '-'); ?></div>
                    </div>
                </div>

                <div class="bill-right">
                    <div class="bill-right-title">Bill No.: <?php echo safeText($invoice['supplier_invoice_no'] ?? '-'); ?></div>
                    <table>
                        <tr><td>Date</td><td><?php echo fmtDate($invoice['supplier_invoice_date'] ?? null); ?></td></tr>
                        <tr><td>Due Dt</td><td><?php echo fmtDate($invoice['due_date'] ?? null); ?></td></tr>
                        <tr><td>L.R. No</td><td><?php echo safeText($invoice['lr_no'] ?? '-'); ?></td></tr>
                        <tr><td>L.R. Dt</td><td><?php echo fmtDate($invoice['lr_date'] ?? null); ?></td></tr>
                        <tr><td>Carrier</td><td><?php echo safeText($invoice['carrier_name'] ?? '-'); ?></td></tr>
                        <tr><td>Vehicle No</td><td><?php echo safeText($invoice['vehicle_no'] ?? '-'); ?></td></tr>
                        <tr><td>F. Slip No</td><td><?php echo safeText($invoice['f_slip_no'] ?? '-'); ?></td></tr>
                        <tr><td>E-Way Bill</td><td><?php echo safeText($invoice['eway_bill_no'] ?? '-'); ?></td></tr>
                    </table>
                </div>
            </div>

            <table class="items">
                <colgroup>
                    <col style="width:3%;">
                    <col style="width:6%;">
                    <col style="width:14%;">
                    <col style="width:5%;">
                    <col style="width:6%;">
                    <col style="width:6%;">
                    <col style="width:5%;">
                    <col style="width:5%;">
                    <col style="width:4%;">
                    <col style="width:6%;">
                    <col style="width:5%;">
                    <col style="width:4%;">
                    <col style="width:5%;">
                    <col style="width:4%;">
                    <col style="width:7%;">
                    <col style="width:4%;">
                    <col style="width:4%;">
                    <col style="width:3%;">
                    <col style="width:4%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.<br>No</th>
                        <th>HSN<br>Code</th>
                        <th>Product Name</th>
                        <th>Pack</th>
                        <th>Mfg.<br>Co</th>
                        <th>Batch No.</th>
                        <th>Exp.<br>Date</th>
                        <th>MRP</th>
                        <th>PTR</th>
                        <th>Sale Rate<br>(PTS)</th>
                        <th>Quantity<br>Billed</th>
                        <th>Qty<br>Disc</th>
                        <th>Amount</th>
                        <th>Disc<br>%</th>
                        <th>Taxable<br>Amount</th>
                        <th>GST<br>%</th>
                        <th>Tax<br>Amount</th>
                        <th>Cess</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                                $gstRate = (float)($item['tax_rate'] ?? ($item['product_gst_rate'] ?? 0));
                                $lineAmount = ((float)($item['qty'] ?? 0) * (float)($item['unit_cost'] ?? 0));
                                $ptr = (float)($item['supplier_quoted_mrp'] ?? 0);
                                $rowProductId = intval($item['product_id'] ?? 0);
                                $packValue = trim((string)($item['pack_size_snapshot'] ?? ''));
                                if ($packValue === '' && $rowProductId > 0 && isset($productPackById[$rowProductId])) {
                                    $packValue = $productPackById[$rowProductId];
                                }
                                if ($packValue === '') {
                                    $packValue = '-';
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td class="text-center"><?php echo safeText($item['hsn_code'] ?? '-'); ?></td>
                                <td><?php echo safeText($item['product_name'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo safeText($packValue); ?></td>
                                <td class="text-center"><?php echo safeText($item['manufacturer_snapshot'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo safeText($item['batch_no'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo fmtDate($item['expiry_date'] ?? null, 'm-y'); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['mrp'] ?? 0, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo $ptr > 0 ? fmtNum($ptr, 2) : '-'; ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['unit_cost'] ?? 0, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['qty'] ?? 0, 3); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['free_qty'] ?? 0, 3); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($lineAmount, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['discount_percent'] ?? 0, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['taxable_value'] ?? 0, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($gstRate, 2); ?></td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['tax_amount'] ?? 0, 2); ?></td>
                                <td class="text-end no-wrap">0.00</td>
                                <td class="text-end no-wrap"><?php echo fmtNum($item['line_total'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="19" class="text-center">No items found</td>
                        </tr>
                    <?php endif; ?>

                    <tr class="total-row">
                        <td colspan="10" class="text-end">TOTAL</td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalQty, 3); ?></td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalFreeQty, 3); ?></td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalAmountBeforeDiscount, 2); ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalTaxableFromItems, 2); ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalTaxFromItems, 2); ?></td>
                        <td class="text-end">0.00</td>
                        <td class="text-end no-wrap"><?php echo fmtNum($totalLineFromItems, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="bottom-wrap">
                <div class="terms-box">
                    <div class="terms-title">Terms and Conditions :</div>
                    <div>1. We certify that products sold as per this bill are paid by us and comply with applicable tax rules.</div>
                    <div>2. Goods once sold are not returnable unless approved by policy.</div>
                    <div>3. Interest may be charged on delayed payment.</div>
                    <div>4. Product sold on non-returnable basis; no expiry/breakage replacement without approval.</div>
                    <?php if (!empty($invoice['notes'])): ?>
                        <div style="margin-top: 1.2mm;"><strong>Notes:</strong> <?php echo nl2br(safeText($invoice['notes'])); ?></div>
                    <?php endif; ?>
                </div>

                <div class="totals-box">
                    <table class="totals">
                        <tr><td class="label">Sub Total</td><td class="value"><?php echo fmtNum($subtotal, 2); ?></td></tr>
                        <tr><td class="label">Taxable Amt</td><td class="value"><?php echo fmtNum($taxableAmount, 2); ?></td></tr>
                        <?php if ($isIntra): ?>
                            <tr><td class="label">CGST</td><td class="value"><?php echo fmtNum($invoice['total_cgst'] ?? 0, 2); ?></td></tr>
                            <tr><td class="label">SGST</td><td class="value"><?php echo fmtNum($invoice['total_sgst'] ?? 0, 2); ?></td></tr>
                        <?php else: ?>
                            <tr><td class="label">IGST</td><td class="value"><?php echo fmtNum($invoice['total_igst'] ?? 0, 2); ?></td></tr>
                        <?php endif; ?>
                        <tr><td class="label">Freight</td><td class="value"><?php echo fmtNum($freight, 2); ?></td></tr>
                        <tr><td class="label">Round Off</td><td class="value"><?php echo fmtNum($roundOff, 2); ?></td></tr>
                        <tr class="grand"><td class="label">Invoice Total</td><td class="value"><?php echo fmtNum($invoiceTotal, 2); ?></td></tr>
                        <tr class="grand"><td class="label">Net Payable</td><td class="value"><?php echo fmtNum($netPayable, 2); ?></td></tr>
                    </table>
                    <div class="totals-extra">
                        <span>Total -&gt; Qty : <?php echo fmtNum($totalQty, 0); ?></span>
                        <span>Wt : 0.000</span>
                    </div>
                </div>
            </div>

            <div class="footer-lines">
                <div class="amount-words">
                    <div><strong>Invoice Amount in Words :</strong> <?php echo safeText($amountWords); ?></div>
                    <div style="margin-top: 1.2mm;"><strong>Net Payable Amount in Words :</strong> <?php echo safeText($netAmountWords); ?></div>
                </div>
                <div class="sign-box">
                    <div>For <?php echo safeText($company['title']); ?></div>
                    <div class="sign-space"></div>
                    <div class="sign-label">Authorised Signatory</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['print'])): ?>
    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                try { window.print(); } catch (e) {}
            }, 150);
        });
    </script>
    <?php endif; ?>
</body>
</html>
