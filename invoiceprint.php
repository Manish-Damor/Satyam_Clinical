<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Invoice Print</title>
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            /*border-bottom: 1px solid #eee;*/
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        /** RTL **/
        .invoice-box.rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .invoice-box.rtl table {
            text-align: right;
        }

        .invoice-box.rtl table tr td:nth-child(2) {
            text-align: left;
        }
    </style>
</head>

<?php
require_once('constant/connect.php');
$sql = "SELECT * FROM orders WHERE delete_status = 0 and id='" . $_GET['id'] . "'";
$result = $connect->query($sql);
foreach ($result as $row) { ?>
<body>
<div class="invoice-box">
    <table cellpadding="0" cellspacing="0">
        <tr class="top">
            <td colspan="8">
                <table>
                    <tr>
                        <td class="title">
                            <img src="./assets/uploadImage/Logo/bill.png" style="width: 100%; max-width: 300px" />
                        </td>
                        <td>
                            <h1 style="margin:0 0 11px;">Invoice</h1>
                            #:<?php echo $row['uno'] ?><br />
                            Created:<?php echo $row['orderDate'] ?><br />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="information">
            <td colspan="8">
                <table>
                    <tr>
                        <?php
                        require_once('constant/connect.php');
                        $sql1 = "SELECT email, user_id FROM users ";
                        $result1 = $connect->query($sql1);
                        foreach ($result1 as $row1) { ?>
							<td colspan>
                                <b>From:</b>
                                <?php echo $row1['email'] ?><br />
                                <?php echo $row1['address'] ?><br />
                            </td>
                            <?php } ?>
                            <td colspan="2">
                            <b>To:</b>
                            <?php echo $row['clientName'] ?>
                            <br />
                            <?php echo $row['clientContact'] ?>
                            
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="heading">
            <td colspan="3">Payment Method</td>
            <td colspan="4">Amount</td>
        </tr>

        <tr class="details">
            <td colspan="3"><?php 
                if($row['paymentType'] == 4){ echo "Phone Pe"; } 
                elseif($row['paymentType'] == 5){ echo "Google Pay"; } 
                elseif($row['paymentType'] == 3){ echo "Credit Card"; } 
                elseif($row['paymentType'] == 2){ echo "Cash"; } 
                elseif($row['paymentType'] == 1){ echo "Cheque"; }
            ?></td>
            <td colspan="4"><?php echo $row['grandTotalValue'] ?></td>
        </tr>

        <tr class="heading">
            <td>Medicine Name</td>
            <td>Batch</td>
            <td>Exp</td>
            <td>Qty</td>
            <td>MRP</td>
            <td>Rate</td>
            <td>Total</td>
        </tr>

        <?php
        require_once('constant/connect.php');
        $sql2 = "SELECT productName, total, rate, quantity FROM order_item WHERE lastid='" . $_GET['id'] . "'";
        $result2 = $connect->query($sql2);
        foreach ($result2 as $row2) { 
            $stmt1 = "SELECT * FROM product WHERE product_id='" . $row2['productName'] . "'";
            $result3 = $connect->query($stmt1);
            foreach ($result3 as $key1) { ?>
                <tr class="item">
                    <td><?php echo $key1['product_name']; ?></td>
                    <td><?php echo $key1['bno']; ?></td>
                    <td><?php echo $key1['expdate']; ?></td>
                    <td><?php echo $row2['quantity']; ?></td>
                    <td><?php echo $key1['mrp']; ?></td>
                    <td><?php echo $row2['rate']; ?></td>
                    <td><?php echo $row2['total']; ?></td>
                </tr>
            <?php } 
        } ?>

        <!-- Bank details and totals in separate rows -->
        <tr>
            <td colspan="3" rowspan="3" style="border: 1px solid #555">
                A/C NO: 88252565985645 <br> IFSC: MAHB7867
                <br> Bank Name: Bank Of Maharashtra
            </td>
            <td colspan="3" style="border: 1px solid; text-align: left;">TOTAL DISCOUNT</td>
            <?php
            $stmt2 = "SELECT * FROM orders WHERE clientName='" . $row['clientName'] . "'";
            $cola = $connect->query($stmt2);
            foreach ($cola as $pepsi) { ?>
                <td style="border: 1px solid"><?php echo $pepsi['discount'] ?></td>
            <?php } ?>
        </tr>

        <tr>
            <td colspan="3" style="border: 1px solid">TOTAL GST</td>
            <td style="border: 1px solid; text-align: left;"><?php echo $pepsi['gstn'] ?></td>
        </tr>

        <tr>
            <td colspan="3" style="border: 1px solid">GRAND TOTAL</td>
            <td style="border: 1px solid; text-align: left;"><?php echo $pepsi['grandTotalValue'] ?></td>
        </tr>

        <tr class="total">
            <td colspan="6" style="border-top: 2px solid #eee; text-align: center;">
                Amount in Words: <b><?php
                    $totalAmount = $pepsi['grandTotalValue'];
                    $number = $totalAmount;
                    $no = floor($number);
                    $point = round($number - $no, 2) * 100;
                    $hundred = null;
                    $digits_1 = strlen($no);
                    $i = 0;
                    $str = array();
                    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
                    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
                    while ($i < $digits_1) {
                        $divider = ($i == 2) ? 10 : 100;
                        $number = floor($no % $divider);
                        $no = floor($no / $divider);
                        $i += ($divider == 10) ? 1 : 2;
                        if ($number) {
                            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                            $str[] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
                        } else $str[] = null;
                    }
                    $str = array_reverse($str);
                    $result = implode('', $str);
                    echo $result . " Rupees";
                    ?></b>
            </td>
        </tr>
    </table>
</div>
</body>
<?php } ?>
</html>
