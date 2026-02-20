<?php
require_once 'core.php';
require_once '../config/bootstrap.php';

use Controllers\SalesOrderController;

header('Content-Type: application/json');

$valid = ['success' => false, 'messages' => '', 'order_id' => null, 'warnings' => [], 'credit_analysis' => []];

if ($_POST) {

    try {

        // ========================================
        // 1. READ & VALIDATE INPUT
        // ========================================

        $uno = trim($_POST['uno'] ?? '');
        $orderDate = $_POST['orderDate'] ?? date('Y-m-d');
        $clientName = trim($_POST['clientName'] ?? '');
        $clientContact = trim($_POST['clientContact'] ?? '');
        $subTotal = (float)($_POST['subTotalValue'] ?? 0);
        $totalAmount = (float)($_POST['totalAmountValue'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $grandTotalValue = (float)($_POST['grandTotalValue'] ?? 0);
        $gstn = $_POST['gstn'] ?? '';
        $paid = (float)($_POST['paid'] ?? 0);
        $dueValue = (float)($_POST['dueValue'] ?? 0);
        $paymentType = $_POST['paymentType'] ?? 'cash';
        $paymentStatus = $_POST['paymentStatus'] ?? 'pending';
        $paymentPlace = $_POST['paymentPlace'] ?? 'counter';
        $gstPercentage = (float)($_POST['gstPercentage'] ?? 0);

        // Basic validation
        if (strlen($clientName) < 2) {
            throw new Exception("Client name is too short");
        }

        if (!preg_match('/^[0-9]{10}$/', $clientContact)) {
            throw new Exception("Invalid contact number (must be 10 digits)");
        }

        if ($grandTotalValue <= 0) {
            throw new Exception("Order total must be greater than zero");
        }

        // ========================================
        // 2. COLLECT ORDER ITEMS
        // ========================================

        $items = [];
        $itemCount = count($_POST['productId'] ?? []);

        if ($itemCount === 0) {
            throw new Exception("Order must contain at least one item");
        }

        for ($i = 0; $i < $itemCount; $i++) {

            $productId = $_POST['productId'][$i] ?? 0;
            $quantity = $_POST['quantity'][$i] ?? 0;
            $rate = $_POST['rateValue'][$i] ?? 0;
            $purchaseRate = $_POST['ptrValue'][$i] ?? 0;  // Purchase rate (PTR)

            // Skip empty rows
            if (empty($productId) || empty($quantity)) {
                continue;
            }

            if (!is_numeric($productId) || $quantity <= 0) {
                throw new Exception("Invalid product or quantity");
            }

            $items[] = [
                'product_id' => (int)$productId,
                'productName' => $_POST['productName'][$i] ?? '',
                'quantity' => (int)$quantity,
                'rate' => (float)$rate,
                'purchase_rate' => (float)$purchaseRate  // Include PTR in item
            ];
        }

        if (empty($items)) {
            throw new Exception("No valid items in order");
        }

        // ========================================
        // 3. PREPARE ORDER DATA
        // ========================================

        $orderData = [
            'uno' => $uno,
            'orderDate' => $orderDate,
            'clientName' => $clientName,
            'clientContact' => $clientContact,
            'subTotalValue' => $subTotal,
            'totalAmountValue' => $totalAmount,
            'discount' => $discount,
            'grandTotalValue' => $grandTotalValue,
            'gstn' => $gstn,
            'paid' => $paid,
            'dueValue' => $dueValue,
            'paymentType' => $paymentType,
            'paymentStatus' => $paymentStatus,
            'paymentPlace' => $paymentPlace,
            'gstPercentage' => $gstPercentage
        ];

        // ========================================
        // 4. CREATE SALES ORDER USING CONTROLLER
        // ========================================

        $userId = $_SESSION['userId'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? 'user';

        $controller = new SalesOrderController($connect, $userId, $userRole);
        $result = $controller->createSalesOrder($orderData, $items);

        if ($result['success']) {
            $valid['success'] = true;
            $valid['messages'] = $result['message'];
            $valid['order_id'] = $result['order_id'];
            $valid['warnings'] = $result['warnings'];
            $valid['credit_analysis'] = $result['credit_analysis'];
        } else {
            $valid['success'] = false;
            $valid['messages'] = $result['message'];
            $valid['errors'] = $result['errors'];
        }

    } catch (Exception $e) {

        $valid['success'] = false;
        $valid['messages'] = "Order Failed: " . $e->getMessage();
    }

    echo json_encode($valid);
} else {
    $valid['messages'] = 'No POST data received';
    echo json_encode($valid);
}

?>
