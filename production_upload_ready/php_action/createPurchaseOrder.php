<?php
// Create Purchase Order - Direct Database Handler
require_once 'core.php';

/* ============================================
   HANDLE FORM SUBMISSION (POST REQUEST)
   ============================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../create_po.php');
    exit;
}

try {
    // Validate session
    if (!isset($_SESSION['userId']) || $_SESSION['userId'] <= 0) {
        throw new Exception('Session expired. Please login again.');
    }

    $userId = intval($_SESSION['userId']);
    global $connect;

    // ========================================
    // COLLECT PO HEADER DATA
    // ========================================
    $poNumber = isset($_POST['po_number']) ? trim($_POST['po_number']) : '';
    $poDate = isset($_POST['po_date']) ? trim($_POST['po_date']) : date('Y-m-d');
    // these fields exist on the old form but are not stored in the simplified pharmacy PO
    // keep retrieval for compatibility but they are not written to the database
    $poType = isset($_POST['po_type']) ? trim($_POST['po_type']) : 'Regular';
    $referenceNumber = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
    $supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $expectedDeliveryDate = isset($_POST['expected_delivery_date']) ? trim($_POST['expected_delivery_date']) : null;
    $deliveryLocation = isset($_POST['delivery_location']) ? trim($_POST['delivery_location']) : 'Main Warehouse';
    
    // Financial data
    $subtotal = isset($_POST['sub_total']) ? floatval($_POST['sub_total']) : 0;
    $discountPercent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;
    $discountAmount = isset($_POST['total_discount']) ? floatval($_POST['total_discount']) : 0;
    // header gst values are optional - they are primarily stored per-item now
    $gstPercent = isset($_POST['gst_percent']) ? floatval($_POST['gst_percent']) : 0;
    $gstAmount = isset($_POST['gst_amount']) ? floatval($_POST['gst_amount']) : 0;
    $otherCharges = isset($_POST['other_charges']) ? floatval($_POST['other_charges']) : 0;
    $grandTotal = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0;

    // Subtotal and grand total may be recomputed on server side as needed

    
    // Validation
    if (empty($poNumber)) throw new Exception('PO Number is required');
    if ($supplierId <= 0) throw new Exception('Please select a supplier');

    // Check if PO number already exists (should not happen with new generation logic)
    $dupCheckSql = "SELECT COUNT(*) as cnt FROM purchase_orders WHERE po_number = ?";
    $dupStmt = $connect->prepare($dupCheckSql);
    if (!$dupStmt) {
        throw new Exception('Database prepare error: ' . $connect->error);
    }
    $dupStmt->bind_param('s', $poNumber);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    $dupRow = $dupResult->fetch_assoc();
    $dupStmt->close();
    
    if ($dupRow['cnt'] > 0) {
        // PO number collision detected - generate new one
        $year = date('y');
        for ($i = 1; $i <= 1000; $i++) {
            $testNum = str_pad($i, 4, '0', STR_PAD_LEFT);
            $testPO = 'PO-' . $year . '-' . $testNum;
            $testStmt = $connect->prepare("SELECT COUNT(*) as cnt FROM purchase_orders WHERE po_number = ?");
            $testStmt->bind_param('s', $testPO);
            $testStmt->execute();
            $testRes = $testStmt->get_result();
            $testRow = $testRes->fetch_assoc();
            $testStmt->close();
            if ($testRow['cnt'] == 0) {
                $poNumber = $testPO;
                break;
            }
        }
        if ($dupRow['cnt'] > 0) {
            throw new Exception('Unable to generate unique PO number. Please contact administrator.');
        }
    }

    // Check supplier exists
    $supRes = $connect->query("SELECT supplier_id FROM suppliers WHERE supplier_id = $supplierId");
    if (!$supRes || $supRes->num_rows === 0) {
        throw new Exception('Selected supplier not found');
    }

    // ========================================
    // INSERT INTO PURCHASE_ORDERS
    // ========================================
    // Header insert - now using only the currently supported columns
    $sql = "INSERT INTO purchase_orders (
                po_number, po_date, supplier_id, expected_delivery_date, delivery_location,
                subtotal, discount_percentage, discount_amount, gst_percentage, gst_amount,
                other_charges, grand_total, po_status, payment_status, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $connect->error);
    }

    $poStatus = 'Draft';
    $paymentStatus = 'NotDue';
    $notes = 'Created from form';

    // binding parameters for the reduced column set
    $stmt->bind_param(
        'ssissdddddddsssi',
        $poNumber, $poDate, $supplierId, $expectedDeliveryDate, $deliveryLocation,
        $subtotal, $discountPercent, $discountAmount, $gstPercent, $gstAmount,
        $otherCharges, $grandTotal, $poStatus, $paymentStatus, $notes, $userId
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create PO: ' . $stmt->error);
    }

    $poId = $connect->insert_id;
    $stmt->close();

    // ========================================
    // INSERT PO ITEMS
    // ========================================
    $itemCount = isset($_POST['item_count']) ? intval($_POST['item_count']) : 0;
    $itemsAdded = 0;

    for ($i = 0; $i < $itemCount; $i++) {
        // Form posts simple arrays: medicine_id[] and quantity[] (pharmacy PO simplified)
        $productId = isset($_POST['medicine_id'][$i]) ? intval($_POST['medicine_id'][$i]) : 0;
        $quantity = isset($_POST['quantity'][$i]) ? intval($_POST['quantity'][$i]) : 0;
        $unitPrice = isset($_POST['unit_price'][$i]) ? floatval($_POST['unit_price'][$i]) : 0;

        // Skip empty rows
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $totalPrice = $quantity * $unitPrice;
        $itemStatus = 'Pending';

        // include gst_percentage column for each line
        $itemSql = "INSERT INTO po_items (
                        po_id, product_id, quantity_ordered, quantity_received,
                        unit_price, total_price, gst_percentage, item_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $itemStmt = $connect->prepare($itemSql);
        if (!$itemStmt) {
            throw new Exception('Failed to prepare item statement');
        }

        $qty_received = 0;
        $gstPct = isset($_POST['gst_percentage'][$i]) ? floatval($_POST['gst_percentage'][$i]) : 0;

        $itemStmt->bind_param(
            'iiiiddds',
            $poId, $productId, $quantity, $qty_received,
            $unitPrice, $totalPrice, $gstPct, $itemStatus
        );

        if ($itemStmt->execute()) {
            $itemsAdded++;
        }
        $itemStmt->close();
    }

    if ($itemsAdded === 0) {
        // Delete the PO if no items were added
        $connect->query("DELETE FROM purchase_orders WHERE po_id = $poId");
        throw new Exception('No valid items provided. PO creation cancelled.');
    }

    // ========================================
    // SUCCESS - REDIRECT TO PO LIST
    // ========================================
    $_SESSION['success'] = "Purchase Order $poNumber created successfully with $itemsAdded items!";
    header('Location: ../po_list.php');
    exit;

} catch (Exception $e) {
    // preserve user input for redisplay
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    $_SESSION['old_post'] = $_POST;
    header('Location: ../create_po.php');
    exit;
}

?>

