<?php
/**
 * SALES INVOICE PRINT (PHARMACY FORMAT)
 * Troikaa-style layout with manufacturer and pack columns.
 */

require './constant/connect.php';

$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($invoiceId <= 0) {
    die('Invalid invoice id');
}

function safeText($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmtDate($value, $format = 'd.m.Y')
{
    if (empty($value)) {
        return '-';
    }
    $ts = strtotime((string)$value);
    return $ts ? date($format, $ts) : '-';
}

function fmtMonthYear($value)
{
    if (empty($value)) {
        return '-';
    }
    $ts = strtotime((string)$value);
    return $ts ? date('M-y', $ts) : '-';
}

function fmtAmount($value, $decimals = 2)
{
    return number_format((float)$value, $decimals, '.', '');
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

    $text = numberToWordsIndian($rupees) . ' Only';
    if ($paise > 0) {
        $text = numberToWordsIndian($rupees) . ' and ' . numberToWordsIndian($paise) . ' Paise Only';
    }

    return $text;
}

function pickSetting($settings, $keys, $default = '')
{
    foreach ($keys as $key) {
        $k = strtolower((string)$key);
        if (isset($settings[$k]) && trim((string)$settings[$k]) !== '') {
            return trim((string)$settings[$k]);
        }
    }
    return $default;
}

$company = [
    'name' => 'Satyam Clinical Pharmacy',
    'address' => 'Nashik, Maharashtra, India',
    'phone' => '-',
    'email' => '-',
    'gstin' => '-',
    'pan' => '-',
    'lic_no' => '-',
    'dl_no' => '-',
    'msme_no' => '-',
    'bank_name' => 'State Bank Of India',
    'ifsc' => '-',
    'account_no' => '-',
    'branch' => '-',
    'tagline' => 'We have a Unique Solution to Life'
];

$settings = [];
$settingsCheck = $connect->query("SHOW TABLES LIKE 'company_settings'");
if ($settingsCheck && $settingsCheck->num_rows > 0) {
    $settingsRes = $connect->query("SELECT setting_key, setting_value FROM company_settings");
    if ($settingsRes) {
        while ($row = $settingsRes->fetch_assoc()) {
            $key = strtolower(trim((string)($row['setting_key'] ?? '')));
            if ($key === '') {
                continue;
            }
            $settings[$key] = (string)($row['setting_value'] ?? '');
        }
    }
}

$company['name'] = pickSetting($settings, ['company_name', 'title', 'store_name'], $company['name']);
$company['address'] = pickSetting($settings, ['company_address', 'address'], $company['address']);
$company['phone'] = pickSetting($settings, ['company_phone', 'phone', 'mobile'], $company['phone']);
$company['email'] = pickSetting($settings, ['company_email', 'email'], $company['email']);
$company['gstin'] = pickSetting($settings, ['company_gstin', 'gstin'], $company['gstin']);
$company['pan'] = pickSetting($settings, ['company_pan', 'pan'], $company['pan']);
$company['lic_no'] = pickSetting($settings, ['license_no', 'lic_no'], $company['lic_no']);
$company['dl_no'] = pickSetting($settings, ['dl_no', 'drug_license_no', 'drug_licence_no'], $company['dl_no']);
$company['msme_no'] = pickSetting($settings, ['msme_no', 'udyam_no'], $company['msme_no']);
$company['bank_name'] = pickSetting($settings, ['bank_name'], $company['bank_name']);
$company['ifsc'] = pickSetting($settings, ['ifsc_code', 'ifsc'], $company['ifsc']);
$company['account_no'] = pickSetting($settings, ['account_no', 'account_number'], $company['account_no']);
$company['branch'] = pickSetting($settings, ['branch_name', 'branch'], $company['branch']);
$company['tagline'] = pickSetting($settings, ['invoice_tagline', 'tagline'], $company['tagline']);

$invoiceStmt = $connect->prepare(
    "SELECT si.*, 
            c.name AS client_name,
            c.contact_phone,
            c.email AS client_email,
            c.gstin AS client_gstin,
            c.drug_licence_no AS client_dl_no,
            c.billing_address,
            c.shipping_address,
            c.city,
            c.state,
            c.postal_code,
            c.outstanding_balance
     FROM sales_invoices si
     LEFT JOIN clients c ON c.client_id = si.client_id
     WHERE si.invoice_id = ? AND si.deleted_at IS NULL"
);
$invoiceStmt->bind_param('i', $invoiceId);
$invoiceStmt->execute();
$invoiceResult = $invoiceStmt->get_result();
if ($invoiceResult->num_rows === 0) {
    die('Invoice not found');
}
$invoice = $invoiceResult->fetch_assoc();

$itemStmt = $connect->prepare(
    "SELECT sii.*, 
            p.product_name,
            p.content,
            p.pack_size,
            p.hsn_code,
            COALESCE(b.brand_name, '') AS manufacturer_name,
            COALESCE(sii.batch_number, pb.batch_number, '') AS batch_number_display,
            COALESCE(sii.expiry_date, pb.expiry_date) AS expiry_display,
            COALESCE(pb.mrp, p.expected_mrp, 0) AS mrp_display
     FROM sales_invoice_items sii
     LEFT JOIN product p ON p.product_id = sii.product_id
     LEFT JOIN brands b ON b.brand_id = p.brand_id
     LEFT JOIN product_batches pb ON pb.batch_id = sii.batch_id
     WHERE sii.invoice_id = ?
     ORDER BY sii.item_id ASC"
);
$itemStmt->bind_param('i', $invoiceId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
}

$isIntrastate = ((float)($invoice['igst_amount'] ?? 0) <= 0.0001);
$gstSummary = [];
foreach ($items as $line) {
    $gstRate = (float)($line['gst_rate'] ?? 0);
    $key = number_format($gstRate, 2, '.', '');

    $taxable = (float)($line['line_subtotal'] ?? 0);
    if ($taxable <= 0) {
        $lineTotal = (float)($line['line_total'] ?? 0);
        $taxable = ($gstRate > 0) ? ($lineTotal / (1 + ($gstRate / 100))) : $lineTotal;
    }

    $gstAmt = (float)($line['gst_amount'] ?? 0);
    if ($gstAmt <= 0 && $gstRate > 0) {
        $gstAmt = $taxable * ($gstRate / 100);
    }

    if (!isset($gstSummary[$key])) {
        $gstSummary[$key] = [
            'rate' => $gstRate,
            'taxable' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0
        ];
    }

    $gstSummary[$key]['taxable'] += $taxable;
    if ($isIntrastate) {
        $gstSummary[$key]['cgst'] += ($gstAmt / 2);
        $gstSummary[$key]['sgst'] += ($gstAmt / 2);
    } else {
        $gstSummary[$key]['igst'] += $gstAmt;
    }
}
ksort($gstSummary);

$totalTaxableFromSlabs = 0;
$totalCgstFromSlabs = 0;
$totalSgstFromSlabs = 0;
$totalIgstFromSlabs = 0;
foreach ($gstSummary as $taxRow) {
    $totalTaxableFromSlabs += (float)$taxRow['taxable'];
    $totalCgstFromSlabs += (float)$taxRow['cgst'];
    $totalSgstFromSlabs += (float)$taxRow['sgst'];
    $totalIgstFromSlabs += (float)$taxRow['igst'];
}

$amountBeforeTax = (float)($invoice['subtotal'] ?? 0) - (float)($invoice['discount_amount'] ?? 0);
if ($amountBeforeTax < 0) {
    $amountBeforeTax = max(0, (float)$totalTaxableFromSlabs);
}

$transportCharges = (float)($invoice['other_charges'] ?? 0);
$cgstAmount = (float)($invoice['cgst_amount'] ?? $totalCgstFromSlabs);
$sgstAmount = (float)($invoice['sgst_amount'] ?? $totalSgstFromSlabs);
$igstAmount = (float)($invoice['igst_amount'] ?? $totalIgstFromSlabs);
$gstAmount = (float)($invoice['gst_amount'] ?? ($cgstAmount + $sgstAmount + $igstAmount));
$grandTotal = (float)($invoice['grand_total'] ?? 0);

$currentOutstanding = (float)($invoice['outstanding_balance'] ?? 0);
$invoiceDue = (float)($invoice['due_amount'] ?? 0);
$previousBalance = max(0, $currentOutstanding - $invoiceDue);
$todayBillAmount = $grandTotal;
$totalUnpaidBalance = $currentOutstanding;

$buyerAddress = trim((string)($invoice['billing_address'] ?? ''));
if ($buyerAddress === '') {
    $buyerAddress = trim((string)($invoice['shipping_address'] ?? ''));
}
$placeOfSupply = trim((string)($invoice['state'] ?? ''));
if ($placeOfSupply === '') {
    $placeOfSupply = 'Gujarat';
}

$amountWords = amountInWordsIndian($grandTotal);
$minRows = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice - <?php echo safeText($invoice['invoice_number'] ?? ''); ?></title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            background: #fff;
        }

        .no-print {
            text-align: center;
            padding: 12px;
        }

        .no-print button {
            padding: 8px 14px;
            margin: 0 4px;
            border: 1px solid #bbb;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 3px;
            font-size: 13px;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 6mm;
            background: #fff;
            border: 1px solid #000;
        }

        .title-row {
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
            padding: 2px 0 3px;
            letter-spacing: 0.5px;
            font-size: 12px;
        }

        .company-box {
            border-bottom: 1px solid #000;
            text-align: center;
            padding: 6px 4px;
        }

        .company-name {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin: 2px 0;
        }

        .company-meta {
            margin-top: 2px;
            line-height: 1.4;
            font-size: 11px;
        }

        .company-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }

        .party-table,
        .items-table,
        .tax-table,
        .summary-table,
        .balance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .party-table td,
        .party-table th,
        .items-table td,
        .items-table th,
        .tax-table td,
        .tax-table th,
        .summary-table td,
        .summary-table th,
        .balance-table td,
        .balance-table th {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .party-table .label,
        .summary-table .label {
            font-weight: 700;
            white-space: nowrap;
        }

        .items-table th {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }

        .items-table td {
            font-size: 10px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-wrap {
            display: grid;
            grid-template-columns: 46% 54%;
            gap: 0;
            margin-top: -1px;
        }

        .summary-table td {
            font-size: 11px;
            padding: 4px 6px;
        }

        .summary-table .bold-row td {
            font-weight: 700;
            font-size: 13px;
        }

        .amount-words {
            border: 1px solid #000;
            border-top: none;
            padding: 6px;
            font-weight: 700;
            font-size: 11px;
        }

        .bank-sign-wrap {
            display: grid;
            grid-template-columns: 62% 38%;
            border: 1px solid #000;
            border-top: none;
        }

        .bank-block,
        .sign-block {
            min-height: 88px;
            padding: 6px;
        }

        .bank-block {
            border-right: 1px solid #000;
        }

        .bank-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sign-company {
            font-size: 30px;
            font-weight: 700;
            text-align: right;
            margin-bottom: 26px;
        }

        .sign-line {
            text-align: right;
            font-weight: 700;
        }

        .balance-table {
            border-top: none;
            margin-top: -1px;
        }

        .balance-table td {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
        }

        .box-label-prev { background: #ffd54f; }
        .box-label-today { background: #a5d6a7; }
        .box-label-unpaid { background: #fff59d; }

        .footer-note {
            text-align: center;
            margin-top: 8px;
            font-size: 11px;
        }

        .muted { color: #333; }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                margin: 0;
                border: 1px solid #000;
                box-shadow: none;
            }
        }

        @media screen {
            body { background: #f3f4f6; }
            .sheet {
                margin: 14px auto 22px;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button type="button" onclick="window.print()">Print Invoice</button>
    <button type="button" onclick="window.history.back()">Back</button>
</div>

<div class="sheet">
    <div class="title-row">TAX INVOICE</div>

    <div class="company-box">
        <div class="company-name"><?php echo safeText($company['name']); ?></div>
        <div class="company-meta muted">Lic No. <?php echo safeText($company['lic_no']); ?>
            <?php if (trim((string)$company['msme_no']) !== '' && trim((string)$company['msme_no']) !== '-'): ?>
                <span style="margin-left:12px;">MSME Certificate No. <?php echo safeText($company['msme_no']); ?></span>
            <?php endif; ?>
        </div>
        <div class="company-meta">Address : <?php echo safeText($company['address']); ?></div>
        <div class="company-meta">Mob. <?php echo safeText($company['phone']); ?> / Email: <?php echo safeText($company['email']); ?></div>
        <div class="company-meta-row">
            <div><strong>GSTIN :</strong> <?php echo safeText($company['gstin']); ?></div>
            <div><strong>PAN NO. :</strong> <?php echo safeText($company['pan']); ?></div>
        </div>
        <div class="company-meta" style="text-align:left;"><strong>DL No.</strong> <?php echo safeText($company['dl_no']); ?></div>
    </div>

    <table class="party-table">
        <tr>
            <td class="label" style="width:66%;">Name of Buyer : <?php echo safeText($invoice['client_name'] ?? '-'); ?></td>
            <td class="label" style="width:34%;">Tax Invoice No. : <?php echo safeText($invoice['invoice_number'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td><?php echo nl2br(safeText($buyerAddress)); ?></td>
            <td class="label">Date : <?php echo safeText(fmtDate($invoice['invoice_date'] ?? null)); ?></td>
        </tr>
        <tr>
            <td><strong>GSTIN:</strong> <?php echo safeText($invoice['client_gstin'] ?? '-'); ?></td>
            <td><strong>Party Order No.:</strong> <?php echo safeText($invoice['payment_notes'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td><strong>Place of Supply :</strong> <?php echo safeText($placeOfSupply); ?></td>
            <td><strong>Contact No.:</strong> <?php echo safeText($invoice['contact_phone'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td><strong>D.L.No.</strong> <?php echo safeText($invoice['client_dl_no'] ?? '-'); ?></td>
            <td><strong>Payment Type:</strong> <?php echo safeText($invoice['payment_type'] ?? '-'); ?></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%;">Sr. No.</th>
                <th style="width:27%;">Description of Goods</th>
                <th style="width:8%;">Pack</th>
                <th style="width:8%;">HSN Code</th>
                <th style="width:10%;">Batch No.</th>
                <th style="width:8%;">Exp.</th>
                <th style="width:6%;">GST %</th>
                <th style="width:6%;">QTY.</th>
                <th style="width:8%;">MRP</th>
                <th style="width:8%;">Rate</th>
                <th style="width:9%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php $idx = 1; foreach ($items as $item): ?>
                <?php
                    $desc = trim((string)($item['product_name'] ?? ''));
                    $content = trim((string)($item['content'] ?? ''));
                    if ($content !== '') {
                        $desc .= ($desc !== '' ? ' ' : '') . $content;
                    }
                    $pack = trim((string)($item['pack_size'] ?? ''));
                    $batchNo = trim((string)($item['batch_number_display'] ?? ''));
                    $exp = fmtMonthYear($item['expiry_display'] ?? null);
                    $gstRate = (float)($item['gst_rate'] ?? 0);
                    $qty = (float)($item['quantity'] ?? 0);
                    $mrp = (float)($item['mrp_display'] ?? 0);
                    $rate = (float)($item['unit_rate'] ?? 0);
                    $amount = (float)($item['line_subtotal'] ?? 0);
                    if ($amount <= 0) {
                        $amount = (float)($item['line_total'] ?? 0);
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $idx++; ?></td>
                    <td><?php echo safeText($desc !== '' ? $desc : '-'); ?></td>
                    <td class="text-center"><?php echo safeText($pack !== '' ? $pack : '-'); ?></td>
                    <td class="text-center"><?php echo safeText($item['hsn_code'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo safeText($batchNo !== '' ? $batchNo : '-'); ?></td>
                    <td class="text-center"><?php echo safeText($exp); ?></td>
                    <td class="text-center"><?php echo fmtAmount($gstRate, 0); ?>%</td>
                    <td class="text-center"><?php echo fmtAmount($qty, 2); ?></td>
                    <td class="text-right"><?php echo fmtAmount($mrp, 2); ?></td>
                    <td class="text-right"><?php echo fmtAmount($rate, 2); ?></td>
                    <td class="text-right"><?php echo fmtAmount($amount, 2); ?></td>
                </tr>
            <?php endforeach; ?>

            <?php for ($blank = count($items); $blank < $minRows; $blank++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="tax-table">
            <thead>
                <tr>
                    <th style="width:40%;">Taxable Amount</th>
                    <?php if ($isIntrastate): ?>
                        <th style="width:30%;">CGST</th>
                        <th style="width:30%;">SGST</th>
                    <?php else: ?>
                        <th style="width:60%;">IGST</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gstSummary)): ?>
                    <tr>
                        <td colspan="<?php echo $isIntrastate ? 3 : 2; ?>" class="text-center">No tax breakup</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gstSummary as $rateKey => $taxRow): ?>
                        <tr>
                            <td><?php echo safeText(rtrim(rtrim($rateKey, '0'), '.')); ?>% : <?php echo fmtAmount($taxRow['taxable'], 2); ?></td>
                            <?php if ($isIntrastate): ?>
                                <td class="text-right"><?php echo fmtAmount($taxRow['cgst'], 2); ?></td>
                                <td class="text-right"><?php echo fmtAmount($taxRow['sgst'], 2); ?></td>
                            <?php else: ?>
                                <td class="text-right"><?php echo fmtAmount($taxRow['igst'], 2); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td class="text-right"><strong>TOTAL</strong></td>
                    <?php if ($isIntrastate): ?>
                        <td class="text-right"><strong><?php echo fmtAmount($cgstAmount, 2); ?></strong></td>
                        <td class="text-right"><strong><?php echo fmtAmount($sgstAmount, 2); ?></strong></td>
                    <?php else: ?>
                        <td class="text-right"><strong><?php echo fmtAmount($igstAmount, 2); ?></strong></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="label">Total Amount Before Tax</td>
                <td class="text-right"><?php echo fmtAmount($amountBeforeTax, 2); ?></td>
            </tr>
            <?php if (abs($transportCharges) > 0.0001): ?>
                <tr>
                    <td class="label">Transportation Charges</td>
                    <td class="text-right"><?php echo fmtAmount($transportCharges, 2); ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($isIntrastate): ?>
                <tr>
                    <td class="label">CGST</td>
                    <td class="text-right"><?php echo fmtAmount($cgstAmount, 2); ?></td>
                </tr>
                <tr>
                    <td class="label">SGST</td>
                    <td class="text-right"><?php echo fmtAmount($sgstAmount, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="label">IGST</td>
                    <td class="text-right"><?php echo fmtAmount($igstAmount, 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="label">Total GST</td>
                <td class="text-right"><?php echo fmtAmount($gstAmount, 2); ?></td>
            </tr>
            <tr class="bold-row">
                <td class="label">Grand Total (Rs.)</td>
                <td class="text-right"><?php echo fmtAmount($grandTotal, 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="amount-words">
        Amount in Words: <?php echo safeText($amountWords); ?>
    </div>

    <div class="bank-sign-wrap">
        <div class="bank-block">
            <div class="bank-title">Bank Details</div>
            <div>Account Holder : <?php echo safeText($company['name']); ?></div>
            <div style="margin-top:4px;"><?php echo safeText($company['bank_name']); ?> &nbsp;&nbsp;&nbsp; IFSC Code : <?php echo safeText($company['ifsc']); ?></div>
            <div style="margin-top:4px;">A/c. No. : <?php echo safeText($company['account_no']); ?> &nbsp;&nbsp;&nbsp; Branch : <?php echo safeText($company['branch']); ?></div>
        </div>
        <div class="sign-block">
            <div class="sign-company">For <?php echo safeText($company['name']); ?>.</div>
            <div class="sign-line">Authorised Signatory</div>
        </div>
    </div>

    <table class="balance-table">
        <tr>
            <td class="box-label-prev" style="width:42%;">Previous Balance (Rs.)</td>
            <td style="width:12%;" class="text-right"><?php echo fmtAmount($previousBalance, 2); ?></td>
            <td style="width:32%;" class="box-label-unpaid">Total Unpaid Balance (Rs.)</td>
            <td style="width:14%;" class="text-right"><?php echo fmtAmount($totalUnpaidBalance, 2); ?></td>
        </tr>
        <tr>
            <td class="box-label-today">Today Bill Amount (Rs.)</td>
            <td class="text-right"><?php echo fmtAmount($todayBillAmount, 2); ?></td>
            <td colspan="2"></td>
        </tr>
    </table>

    <div class="footer-note">" <?php echo safeText($company['tagline']); ?> "</div>
</div>

</body>
</html>
