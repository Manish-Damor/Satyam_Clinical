<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => array());

if ($_POST) {

    // 🔹 START TRANSACTION (important!)
    $connect->begin_transaction();

    try {

        /* =======================
           1. READ & VALIDATE INPUT
           ======================= */

        $uno = trim($_POST['uno']);
        $orderDate = $_POST['orderDate'];
        $clientName = trim($_POST['clientName']);
        $clientContact = trim($_POST['clientContact']);
        $subTotal = $_POST['subTotalValue'];
        $totalAmount = $_POST['totalAmountValue'];
        $discount = $_POST['discount'];
        $grandTotalValue = $_POST['grandTotalValue'];
        $gstn = $_POST['gstn'];
        $paid = $_POST['paid'];
        $dueValue = $_POST['dueValue'];
        $paymentType = $_POST['paymentType'];
        $paymentStatus = $_POST['paymentStatus'];
        $paymentPlace = $_POST['paymentPlace'];
        $gstPercentage = $_POST['gstPercentage'];

        // Basic validation (backend must protect)
        if (strlen($clientName) < 2) {
            throw new Exception("Client name is too short");
        }

        if (!preg_match('/^[0-9]{10}$/', $clientContact)) {
            throw new Exception("Invalid contact number");
        }

        


        /* =======================
           2. INSERT INTO ORDERS (PREPARED STATEMENT)
           ======================= */

        $stmt = $connect->prepare("
            INSERT INTO orders 
            (uno, orderDate, clientName, gstPercents, gstn, clientContact, subTotal, totalAmount, discount, grandTotalValue, paid, dueValue, paymentType, paymentStatus, paymentPlace) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssddddddiii",
            $uno,
            $orderDate,
            $clientName,
            $gstPercentage,
            $gstn,
            $clientContact,
            $subTotal,
            $totalAmount,
            $discount,
            $grandTotalValue,
            $paid,
            $dueValue,
            $paymentType,
            $paymentStatus,
            $paymentPlace
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create order");
        }

        // Get newly created order id
        $lastid = $connect->insert_id;


        /* =======================
           3. INSERT ORDER ITEMS + LOCK STOCK + REDUCE STOCK
           ======================= */

        for ($i = 0; $i < count($_POST['productId']); $i++) {

            $productId = $_POST['productId'][$i];
            $quantity  = $_POST['quantity'][$i];
            $rate      = $_POST['rateValue'][$i];
            $total     = $_POST['totalValue'][$i];
            $added_date = date('Y-m-d');

            // Skip empty rows
            if (empty($productId) || empty($quantity)) {
                continue;
            }

            if (!is_numeric($productId) || $quantity <= 0) {
                throw new Exception("Invalid product or quantity");
            }

            /* 🔹 LOCK PRODUCT ROW (multi-user safety) */
            $lockSql = "SELECT quantity FROM product WHERE product_id = ? FOR UPDATE";
            $stmtLock = $connect->prepare($lockSql);
            $stmtLock->bind_param("i", $productId);
            $stmtLock->execute();
            $result = $stmtLock->get_result();
            $stockRow = $result->fetch_assoc();

            if (!$stockRow) {
                throw new Exception("Product not found");
            }

            if ($stockRow['quantity'] < $quantity) {
                throw new Exception("Not enough stock for product ID $productId");
            }

            /* 🔹 REDUCE STOCK */
            $updateStockSql = "UPDATE product SET quantity = quantity - ? WHERE product_id = ?";
            $stmtUpdate = $connect->prepare($updateStockSql);
            $stmtUpdate->bind_param("ii", $quantity, $productId);
            $stmtUpdate->execute();

            /* 🔹 INSERT ORDER ITEM */
            $insertSql = "
                INSERT INTO order_item 
                (productName, quantity, rate, total, lastid, added_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmtInsert = $connect->prepare($insertSql);
            $stmtInsert->bind_param("iiiddi", $productId, $quantity, $rate, $total, $lastid, $added_date);
            $stmtInsert->execute();
        }


        /* =======================
           4. COMMIT TRANSACTION
           ======================= */

        $connect->commit();

        $valid['success'] = true;
        $valid['messages'] = "Order Successfully Added";
		header('location:../Order.php');


    } catch (Exception $e) {

        // 🔹 ROLLBACK EVERYTHING ON ANY ERROR
        $connect->rollback();

        $valid['success'] = false;
        $valid['messages'] = "Order Failed: " . $e->getMessage();
    }

    echo json_encode($valid);
}
?>



<?php 	

// require_once 'core.php';

// $valid['success'] = array('success' => false, 'messages' => array());

// if($_POST) {	

//   $uno= $_POST['uno'];
//   //echo $productName ;exit;
//   $orderDate 	= $_POST['orderDate'];
//   $clientName 		= $_POST['clientName'];
//   //$projectName 		= $_POST['projectName'];
//   $clientContact 			= $_POST['clientContact'];
//   //$address 			= $_POST['address'];
//   $subTotal 		= $_POST['subTotalValue'];
//   $totalAmount 	= $_POST['totalAmountValue'];
//   //$productStatus 	= $_POST['productStatus'];
//   $discount 	= $_POST['discount'];
//   $grandTotalValue 	= $_POST['grandTotalValue'];
//   $gstn 	= $_POST['gstn'];
//   $paid 	= $_POST['paid'];
//   $dueValue 	= $_POST['dueValue'];

//   $paymentType 	= $_POST['paymentType'];
//   $paymentStatus 	= $_POST['paymentStatus'];
//   $paymentPlace 	= $_POST['paymentPlace'];
//   $gstPercentage    = $_POST['gstPercentage'];
// 	//$type = explode('.', $_FILES['productImage']['name']);
	
	
// 	$sql = "INSERT INTO orders (uno, orderDate, clientName, gstPercents, gstn, clientContact, subTotal, totalAmount, discount, grandTotalValue, paid, dueValue, paymentType, paymentStatus, paymentPlace) 
// 			VALUES ('$uno', '$orderDate', '$clientName', '$gstPercentage', '$gstn', '$clientContact', '$subTotal', '$totalAmount', '$discount', '$grandTotalValue', '$paid', '$dueValue', '$paymentType', '$paymentStatus', '$paymentPlace')";
// 	//echo $sql;exit;
// 	if($connect->query($sql) === TRUE) 
// 	{
// 		//echo "gfghh";exit;
// 		$lastid = mysqli_insert_id($connect);
// 		$checkbox1 =count($_POST['productId']);
// 		// print_r ($checkbox1);exit;
// 		for($i=0; $i<($checkbox1);$i++)
// 			{
// 				extract($_POST);
// 				$added_date=date('Y-m-d');
// 				$sql1 = "INSERT INTO order_item (productName, quantity,rate,total,lastid,added_date) 
// 				VALUES ('$productId[$i]', '$quantity[$i]', '$rateValue[$i]', '$totalValue[$i]','$lastid','$added_date')";
// 				// echo $sql1;exit;
// 				if($connect->query($sql1) === TRUE)
// 					{
// 					// echo $lastid;exit;

// 						$valid['success'] = true;
// 						$valid['messages'] = "Successfully Added";
// 						header('location:../Order.php');	
// 					} 
// 			}
// 	}
// 	else {
// 		$valid['success'] = false;
// 		$valid['messages'] = "Error while adding the members";
// 		header('location:../add-order.php');
// 	}

// 	// /else	
// 	// if
// 	// if in_array 		

// 	$connect->close();

// 	echo json_encode($valid);

// 	} // /if $_POST

?>