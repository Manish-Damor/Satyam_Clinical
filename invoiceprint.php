<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Invoice Print</title>

<style>
body {
    font-family: Arial, sans-serif;
    font-size: 14px;
}

.invoice-box {
    max-width: 900px;
    margin: auto;
    padding: 20px;
    border: 1px solid #333;
}

.header {
    display: flex;
    justify-content: space-between;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.logo img {
    max-height: 80px;
}

.title {
    text-align: right;
}

.section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.box {
    border: 1px solid #ccc;
    padding: 8px;
}

.box h4 {
    margin: 0 0 6px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 4px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
}

th {
    background: #f2f2f2;
}

.right {
    text-align: right;
}

.totals {
    width: 40%;
    float: right;
    margin-top: 10px;
}

.totals div {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.grand {
    font-weight: bold;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
}

.footer {
    margin-top: 40px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    text-align: center;
}
.print-btn {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }


</style>
</head>

<?php
require_once('constant/connect.php');

/* FETCH ORDER */
$id = intval($_GET['id']);
$sql = "SELECT * FROM orders WHERE delete_status = 0 AND id = '$id'";
$result = $connect->query($sql);

if($result->num_rows == 0) {
    die("Invoice not found");
}

$row = $result->fetch_assoc();

/* FETCH COMPANY / USER */
$userSql = "SELECT * FROM users LIMIT 1";
$userRes = $connect->query($userSql);
$user = $userRes->fetch_assoc();

/* FETCH ITEMS */
$itemSql = "SELECT * FROM order_item WHERE lastid = '$id'";
$itemRes = $connect->query($itemSql);

/* AMOUNT IN WORDS FUNCTION */
function amountInWords($number) {
    $no = floor($number);
    $words = array(
        '0'=>'','1'=>'One','2'=>'Two','3'=>'Three','4'=>'Four','5'=>'Five','6'=>'Six','7'=>'Seven',
        '8'=>'Eight','9'=>'Nine','10'=>'Ten','11'=>'Eleven','12'=>'Twelve','13'=>'Thirteen','14'=>'Fourteen',
        '15'=>'Fifteen','16'=>'Sixteen','17'=>'Seventeen','18'=>'Eighteen','19'=>'Nineteen','20'=>'Twenty',
        '30'=>'Thirty','40'=>'Forty','50'=>'Fifty','60'=>'Sixty','70'=>'Seventy','80'=>'Eighty','90'=>'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    $i = 0; $str = array();

    while ($no > 0) {
        $divider = ($i == 2) ? 10 : 100;
        $number = $no % $divider;
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;

        if ($number) {
            $str[] = ($number < 21) ?
                $words[$number] . " " . $digits[count($str)] . " " :
                $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[count($str)] . " ";
        }
    }
    return trim(implode('', array_reverse($str))) . " Rupees Only";
}

/* GST SPLIT */
$gstAmount = $row['gstn'];
$gstPercent = $row['gstPercentage'];

if($row['paymentPlace'] == 1) { // In India
    $cgst = $gstAmount / 2;
    $sgst = $gstAmount / 2;
    $igst = 0;
} else {
    $cgst = 0;
    $sgst = 0;
    $igst = $gstAmount;
}
?>

<body>

<div class="invoice-box">

<!-- HEADER -->
<div class="header">
    <div class="logo">
        <img src="./assets/uploadImage/Logo/bill.png">
    </div>
    <div class="title">
        <h2>SALES INVOICE</h2>
        Invoice No: <?php echo $row['uno']; ?><br>
        Date: <?php echo $row['orderDate']; ?>
    </div>
</div>

<!-- INFO -->
<div class="section">

    <div class="box">
        <h4>From</h4>
        <?php echo $user['email']; ?><br>
        <?php echo $user['address']; ?>
    </div>

    <div class="box">
        <h4>To (Customer)</h4>
        Name: <?php echo $row['clientName']; ?><br>
        Contact: <?php echo $row['clientContact']; ?>
    </div>

</div>

<!-- PAYMENT INFO -->
<div class="section">
    <div class="box">
        <h4>Payment Info</h4>
        Mode:
        <?php 
            if($row['paymentType']==1) echo "Cheque";
            elseif($row['paymentType']==2) echo "Cash";
            elseif($row['paymentType']==3) echo "Credit Card";
            elseif($row['paymentType']==4) echo "PhonePe";
            elseif($row['paymentType']==5) echo "Google Pay";
        ?><br>
        Status:
        <?php 
            if($row['paymentStatus']==1) echo "Full Payment";
            elseif($row['paymentStatus']==2) echo "Advance Payment";
            else echo "No Payment";
        ?>
    </div>

    <div class="box">
        <h4>Amounts</h4>
        Paid: ₹<?php echo number_format($row['paid'],2); ?><br>
        Due: ₹<?php echo number_format($row['due'],2); ?>
    </div>
</div>

<!-- ITEMS -->
<table>
<tr>
    <th>#</th>
    <th>Medicine</th>
    <th>Batch</th>
    <th>Exp</th>
    <th>Qty</th>
    <th>MRP</th>
    <th>Rate</th>
    <th>Total</th>
</tr>

<?php 
$i=1;
while($item = $itemRes->fetch_assoc()) {

$prodSql = "SELECT * FROM product WHERE product_id = '".$item['productName']."'";
$prodRes = $connect->query($prodSql);
$prod = $prodRes->fetch_assoc();
?>
<tr>
    <td><?php echo $i++; ?></td>
    <td><?php echo $prod['product_name']; ?></td>
    <td><?php echo $prod['bno']; ?></td>
    <td><?php echo $prod['expdate']; ?></td>
    <td><?php echo $item['quantity']; ?></td>
    <td><?php echo number_format($prod['mrp'],2); ?></td>
    <td><?php echo number_format($item['rate'],2); ?></td>
    <td><?php echo number_format($item['total'],2); ?></td>
</tr>
<?php } ?>
</table>

<!-- BANK + TOTALS ROW -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">

    <!-- BANK DETAILS (LEFT SIDE) -->
    <div class="box">
        <h4>Bank Details</h4>
        A/C No: 88252565985645<br>
        IFSC: MAHB7867<br>
        Bank: Bank Of Maharashtra
    </div>

    <!-- TOTALS (RIGHT SIDE) -->
    <div class="totals" style="width:100%; float:none;">
        <div><span>Sub Total</span><span>₹<?php echo number_format($row['subTotal'],2); ?></span></div>
        <div><span>Discount</span><span>- ₹<?php echo number_format($row['discount'],2); ?></span></div>

        <?php if($row['paymentPlace']==1){ ?>
            <div><span>CGST</span><span>₹<?php echo number_format($cgst,2); ?></span></div>
            <div><span>SGST</span><span>₹<?php echo number_format($sgst,2); ?></span></div>
        <?php } else { ?>
            <div><span>IGST</span><span>₹<?php echo number_format($igst,2); ?></span></div>
        <?php } ?>

        <div class="grand"><span>Grand Total</span><span>₹<?php echo number_format($row['grandTotalValue'],2); ?></span></div>
    </div>

</div>


<!-- TOTALS -->
<!-- <div class="totals">
    <div><span>Sub Total</span><span>₹<?php echo number_format($row['subTotalValue'],2); ?></span></div>
    <div><span>Discount</span><span>- ₹<?php echo number_format($row['discount'],2); ?></span></div>

    <?php if($row['paymentPlace']==1){ ?>
        <div><span>CGST</span><span>₹<?php echo number_format($cgst,2); ?></span></div>
        <div><span>SGST</span><span>₹<?php echo number_format($sgst,2); ?></span></div>
    <?php } else { ?>
        <div><span>IGST</span><span>₹<?php echo number_format($igst,2); ?></span></div>
    <?php } ?>

    <div class="grand"><span>Grand Total</span><span>₹<?php echo number_format($row['grandTotalValue'],2); ?></span></div>
</div> -->

<!-- <div style="clear:both;"></div> -->

<!-- AMOUNT WORDS -->
<!-- <p><b>Amount in Words:</b> <?php echo amountInWords($row['grandTotalValue']); ?></p> -->

<!-- BANK -->
<!-- <div class="box">
    <h4>Bank Details</h4>
    A/C No: 88252565985645<br>
    IFSC: MAHB7867<br>
    Bank: Bank Of Maharashtra
</div> -->

<!-- FOOTER -->
<div class="footer">
    <div>
        ___________________<br>
        Authorized By
    </div>
    <div>
        ___________________<br>
        Customer
    </div>
</div>

</div>

<div class="print-btn">
    <button onclick="window.print()">Print Invoice</button>
</div>

</body>
</html>
