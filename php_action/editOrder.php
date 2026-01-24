<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => array());

if ($_POST) {

    // 🔹 START TRANSACTION (everything inside is one unit)
    $connect->begin_transaction();

    try {

        /* =======================
           1. READ & VALIDATE INPUT
           ======================= */

        $orderId = $_POST['orderId'];

        if (empty($orderId) || !is_numeric($orderId)) {
            throw new Exception("Invalid Order ID");
        }

        $orderDate = date('Y-m-d', strtotime($_POST['orderDate']));
        $clientName = trim($_POST['clientName']);
        $clientContact = trim($_POST['clientContact']);
        $subTotalValue = $_POST['subTotalValue'];
        $totalAmountValue = $_POST['totalAmountValue'];
        $discount = $_POST['discount'];
        $grandTotalValue = $_POST['grandTotalValue'];
        $paid = $_POST['paid'];
        $dueValue = $_POST['dueValue'];
        $paymentType = $_POST['paymentType'];
        $paymentStatus = $_POST['paymentStatus'];
        $paymentPlace = $_POST['paymentPlace'];
        $gstn = $_POST['vatValue'];

        // Basic validation
        if (strlen($clientName) < 2) {
            throw new Exception("Client name is too short");
        }

        if (!preg_match('/^[0-9]{10}$/', $clientContact)) {
            throw new Exception("Invalid contact number");
        }

        if ($paid < 0 || $discount < 0) {
            throw new Exception("Invalid payment values");
        }


        /* =======================
           2. UPDATE ORDERS (PREPARED STATEMENT)
           ======================= */

        $stmt = $connect->prepare("
            UPDATE orders SET 
                orderDate = ?,
                clientName = ?,
                clientContact = ?,
                subTotal = ?,
                totalAmount = ?,
                discount = ?,
                grandTotalValue = ?,
                paid = ?,
                dueValue = ?,
                paymentType = ?,
                paymentStatus = ?,
                paymentPlace = ?,
                gstn = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssssiiisii",
            $orderDate,
            $clientName,
            $clientContact,
            $subTotalValue,
            $totalAmountValue,
            $discount,
            $grandTotalValue,
            $paid,
            $dueValue,
            $paymentType,
            $paymentStatus,
            $paymentPlace,
            $gstn,
            $orderId
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update order");
        }


        /* =======================
           3. RESTORE OLD STOCK
           ======================= */

        $oldItemsSql = "SELECT productName, quantity FROM order_item WHERE lastid = ?";
        $stmtOld = $connect->prepare($oldItemsSql);
        $stmtOld->bind_param("i", $orderId);
        $stmtOld->execute();
        $oldItemsResult = $stmtOld->get_result();

        while ($oldItem = $oldItemsResult->fetch_assoc()) {

            $pid = $oldItem['productName'];
            $qty = $oldItem['quantity'];

            $restoreSql = "UPDATE product SET quantity = quantity + ? WHERE product_id = ?";
            $stmtRestore = $connect->prepare($restoreSql);
            $stmtRestore->bind_param("ii", $qty, $pid);
            $stmtRestore->execute();
        }


        /* =======================
           4. DELETE OLD ORDER ITEMS
           ======================= */

        $deleteSql = "DELETE FROM order_item WHERE lastid = ?";
        $stmtDelete = $connect->prepare($deleteSql);
        $stmtDelete->bind_param("i", $orderId);
        $stmtDelete->execute();


        /* =======================
           5. INSERT NEW ITEMS + STOCK LOCK + REDUCE STOCK
           ======================= */

        for ($x = 0; $x < count($_POST['productId']); $x++) {

            $productId = $_POST['productId'][$x];
            $quantity  = $_POST['quantity'][$x];
            $rate      = $_POST['rateValue'][$x];
            $total     = $_POST['totalValue'][$x];

            // Skip empty rows safely
            if (empty($productId) || empty($quantity)) {
                continue;
            }

            if (!is_numeric($productId) || $quantity <= 0) {
                throw new Exception("Invalid product or quantity");
            }

            /* 🔹 LOCK PRODUCT ROW */
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
                (lastid, productName, quantity, rate, total) 
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmtInsert = $connect->prepare($insertSql);
            $stmtInsert->bind_param("iiidd", $orderId, $productId, $quantity, $rate, $total);
            $stmtInsert->execute();
        }


        /* =======================
           6. COMMIT TRANSACTION
           ======================= */

        $connect->commit();

        $valid['success'] = true;
        $valid['messages'] = "Successfully Updated";
		header('location:'.$_SERVER['HTTP_REFERER']);


    } catch (Exception $e) {

        // 🔹 ROLLBACK EVERYTHING ON ANY ERROR
        $connect->rollback();

        $valid['success'] = false;
        $valid['messages'] = "Update failed: " . $e->getMessage();
    }

    echo json_encode($valid);
}
?>





<?php 	

// require_once 'core.php';

// $valid['success'] = array('success' => false, 'messages' => array());

// if($_POST) {	
// 	$orderId = $_POST['orderId'];

// 	$orderDate 						= date('Y-m-d', strtotime($_POST['orderDate']));
//   $clientName 					= $_POST['clientName'];
//   $clientContact 				= $_POST['clientContact'];
//   $subTotalValue 				= $_POST['subTotalValue'];
//   //$vatValue 						=	$_POST['vatValue'];
//   $totalAmountValue     = $_POST['totalAmountValue'];
//   $discount 						= $_POST['discount'];
//   $grandTotalValue 			= $_POST['grandTotalValue'];
//   $paid 								= $_POST['paid'];
//   $dueValue 						= $_POST['dueValue'];
//   $paymentType 					= $_POST['paymentType'];
//   $paymentStatus 				= $_POST['paymentStatus'];
//   $paymentPlace 				= $_POST['paymentPlace'];
//   $gstn 				= $_POST['vatValue'];
// 	$userid 				= $_SESSION['userId'];
				
// 	$sql = "UPDATE orders SET orderDate = '$orderDate',clientName = '$clientName', 	clientContact = '$clientContact', subTotal = '$subTotalValue', totalAmount = '$totalAmountValue', discount = '$discount', grandTotalValue = '$grandTotalValue', paid = '$paid', dueValue = '$dueValue', paymentType = '$paymentType',paymentStatus = '$paymentStatus',paymentPlace = '$paymentPlace' , gstn = '$gstn' WHERE id = {$orderId}";
// 	//echo $sql;exit;	
// 	$connect->query($sql);
// 	$readyToUpdateOrderItem = false;
// 	// add the quantity from the order item to product table
// 	for($x = 0; $x < count($_POST['productName']); $x++) {		
// 		//  product table
// 		$updateProductQuantitySql = "SELECT product.quantity FROM product WHERE product.product_id = ".$_POST['productName'][$x]."";
// 	//echo $updateProductQuantitySql;exit;
// 		$updateProductQuantityData = $connect->query($updateProductQuantitySql);			//echo print_r($updateProductQuantityData);exit;
			
// 		while ($updateProductQuantityResult = $updateProductQuantityData->fetch_row()) {
// 			// order item table add product quantity
// 			$orderItemTableSql = "SELECT order_item.quantity FROM order_item WHERE order_item.lastid = {$orderId}";
// 			//echo $orderItemTableSql;exit;
// 			$orderItemResult = $connect->query($orderItemTableSql);
// 			$orderItemData = $orderItemResult->fetch_row();
// //echo print_r($orderItemData);exit;
// 			$editQuantity = $updateProductQuantityResult[0] + $orderItemData[0];						//echo print_r($editQuantity);exit;	

// 			$updateQuantitySql = "UPDATE product SET quantity = $editQuantity WHERE product_id = ".$_POST['productName'][$x]."";
// 			//echo $updateQuantitySql;exit;
// 			$connect->query($updateQuantitySql);		
// 		} // while	
		
// 		if(count($_POST['productName']) == count($_POST['productName'])) {
// 			$readyToUpdateOrderItem = true;			
// 		}
// 	} // /for quantity

// 	// remove the order item data from order item table
// 	for($x = 0; $x < count($_POST['productName']); $x++) {			
// 		$removeOrderSql = "DELETE FROM order_item WHERE lastid = {$orderId}";
// 		//echo $removeOrderSq;exit;
// 		$connect->query($removeOrderSql);	
// 	} // /for quantity

// 	if($readyToUpdateOrderItem) {
// 			// insert the order item data 
// 		for($x = 0; $x < count($_POST['productName']); $x++) {			
// 			$updateProductQuantitySql = "SELECT product.quantity FROM product WHERE product.product_id = ".$_POST['productName'][$x]."";
// 			$updateProductQuantityData = $connect->query($updateProductQuantitySql);
			
// 			while ($updateProductQuantityResult = $updateProductQuantityData->fetch_row()) {
// 				$updateQuantity[$x] = $updateProductQuantityResult[0] - $_POST['quantity'][$x];							
// 					// update product table
// 					$updateProductTable = "UPDATE product SET quantity = '".$updateQuantity[$x]."' WHERE product_id = ".$_POST['productName'][$x]."";
// 					$connect->query($updateProductTable);

// 					// add into order_item
// 				$orderItemSql = "INSERT INTO order_item (lastid,productName, quantity, rate, total) 
// 				VALUES ({$orderId},'".$_POST['productName'][$x]."', '".$_POST['quantity'][$x]."', '".$_POST['rateValue'][$x]."', '".$_POST['totalValue'][$x]."')";
// //echo $orderItemSql;exit;
// 				$connect->query($orderItemSql);		
// 			} // while	
// 		} // /for quantity
// 	}

	

// 	$valid['success'] = true;
// 	$valid['messages'] = "Successfully Updated";
// 	//echo"gfg";exit;		
// 	$connect->close();
// 	header('location:'.$_SERVER['HTTP_REFERER']);

// 	echo json_encode($valid);
 
// } // /if $_POST
// echo json_encode($valid);

// if($_POST) {	
// 	$connect->begin_transaction();


// 	$orderId = $_POST['orderId'];

// 	$orderDate = date('Y-m-d', strtotime($_POST['orderDate']));
// 	$clientName = $_POST['clientName'];
// 	$clientContact = $_POST['clientContact'];
// 	$subTotalValue = $_POST['subTotalValue'];
// 	$totalAmountValue = $_POST['totalAmountValue'];
// 	$discount = $_POST['discount'];
// 	$grandTotalValue = $_POST['grandTotalValue'];
// 	$paid = $_POST['paid'];
// 	$dueValue = $_POST['dueValue'];
// 	$paymentType = $_POST['paymentType'];
// 	$paymentStatus = $_POST['paymentStatus'];
// 	$paymentPlace = $_POST['paymentPlace'];
// 	$gstn = $_POST['vatValue'];

// 	/* ---------------- UPDATE ORDER TABLE ---------------- */

// 	$sql = "UPDATE orders SET 
// 			orderDate = '$orderDate',
// 			clientName = '$clientName',
// 			clientContact = '$clientContact',
// 			subTotal = '$subTotalValue',
// 			totalAmount = '$totalAmountValue',
// 			discount = '$discount',
// 			grandTotalValue = '$grandTotalValue',
// 			paid = '$paid',
// 			dueValue = '$dueValue',
// 			paymentType = '$paymentType',
// 			paymentStatus = '$paymentStatus',
// 			paymentPlace = '$paymentPlace',
// 			gstn = '$gstn'
// 			WHERE id = {$orderId}";
// 	$connect->query($sql);


// 	/* ----------- STEP 1: RESTORE OLD STOCK FIRST ----------- */

// 	$oldItemsSql = "SELECT productName, quantity FROM order_item WHERE lastid = {$orderId}";
// 	$oldItemsResult = $connect->query($oldItemsSql);

// 	while($oldItem = $oldItemsResult->fetch_assoc()) {

// 		$pid = $oldItem['productName'];   // product_id
// 		$qty = $oldItem['quantity'];

// 		// Add back old quantity to product stock
// 		$restoreSql = "UPDATE product 
// 					   SET quantity = quantity + $qty 
// 					   WHERE product_id = '$pid'";
// 		$connect->query($restoreSql);
// 	}


// 	/* ----------- STEP 2: DELETE OLD ORDER ITEMS ----------- */

// 	$connect->query("DELETE FROM order_item WHERE lastid = {$orderId}");


// 	/* ----------- STEP 3: INSERT NEW ORDER ITEMS & REDUCE STOCK ----------- */

// 	for($x = 0; $x < count($_POST['productId']); $x++) {

		
		
// 		$productId = $_POST['productId'][$x];
// 		$quantity  = $_POST['quantity'][$x];
// 		$rate      = $_POST['rateValue'][$x];
// 		$total     = $_POST['totalValue'][$x];
		
// 		if(empty($productId) || empty($quantity)) {
// 			continue; // skip invalid row
// 		}
// 		// Check available stock first
// 		$checkStockSql = "SELECT quantity FROM product WHERE product_id = '$productId'";
// 		$checkResult = $connect->query($checkStockSql);
// 		$stockRow = $checkResult->fetch_assoc();

// 		if($stockRow['quantity'] < $quantity) {
// 			// Not enough stock → STOP EVERYTHING
// 			$connect->rollback();

// 			$valid['success'] = false;
// 			$valid['messages'] = "Not enough stock for one of the medicines!";
// 			echo json_encode($valid);
// 			exit;
// 		}


// 		// Reduce stock
// 		$updateStockSql = "UPDATE product 
// 						   SET quantity = quantity - $quantity 
// 						   WHERE product_id = '$productId'";
// 		$connect->query($updateStockSql);

// 		// Insert new order item
// 		$orderItemSql = "INSERT INTO order_item 
// 			(lastid, productName, quantity, rate, total) 
// 			VALUES 
// 			('{$orderId}', '$productId', '$quantity', '$rate', '$total')";
// 		$connect->query($orderItemSql);
// 	}


// 	$valid['success'] = true;
// 	$valid['messages'] = "Successfully Updated";
// 	header('location:'.$_SERVER['HTTP_REFERER']);

// 	echo json_encode($valid);
// }

?>