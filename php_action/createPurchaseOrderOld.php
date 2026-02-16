<?php 
/**
 * Create Purchase Order Processing
 * Matches project structure - uses POST variables
 * Similar to createProduct.php, createBrand.php pattern
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'core.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    throw new Exception("Invalid JSON payload");
}


// Initialize response (matching project pattern)
$valid['success'] = array('success' => false, 'messages' => array());
$debug = array();

// Debug mode (optional - can be disabled in production)
$DEBUG_ON = true;

if($DEBUG_ON) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/po_creation_errors.log');
}

// START PROCESSING POST DATA
if($_POST) {
    try {
        $debug[] = "=== PO Creation Started ===";
        $debug[] = "User ID: " . isset($_SESSION['userId']) ? $_SESSION['userId'] : 'NOT SET';
        
        // Extract PO Header Information from POST
        $po_number = isset($_POST['po_number']) ? $_POST['po_number'] : '';
        $po_date = isset($_POST['po_date']) ? $_POST['po_date'] : date('Y-m-d');
        $po_type = isset($_POST['po_type']) ? $_POST['po_type'] : 'Regular';
        $expected_delivery_date = isset($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : '';
        
        $debug[] = "PO Header: po_number=$po_number, po_date=$po_date, po_type=$po_type";
        
        // Extract Supplier Information from POST
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        $supplier_name = isset($_POST['supplier_name']) ? $_POST['supplier_name'] : '';
        $supplier_contact = isset($_POST['supplier_contact']) ? $_POST['supplier_contact'] : '';
        $supplier_email = isset($_POST['supplier_email']) ? $_POST['supplier_email'] : '';
        $supplier_gst = isset($_POST['supplier_gst']) ? $_POST['supplier_gst'] : '';
        $supplier_address = isset($_POST['supplier_address']) ? $_POST['supplier_address'] : '';
        
        $debug[] = "Supplier: ID=$supplier_id, Name=$supplier_name, Email=$supplier_email";
        
        // Validate required fields
        if(empty($po_number)) {
            throw new Exception("PO Number is required");
        }
        if($supplier_id <= 0) {
            throw new Exception("Please select a valid Supplier");
        }
        
        $debug[] = "✓ Required fields validated";
        
        // Extract Delivery Information
        $delivery_address = isset($_POST['delivery_address']) ? $_POST['delivery_address'] : $supplier_address;
        $delivery_date = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : $expected_delivery_date;
        $shipping_terms = isset($_POST['shipping_terms']) ? $_POST['shipping_terms'] : '';
        
        // Extract Payment Information
        $payment_terms = isset($_POST['payment_terms']) ? $_POST['payment_terms'] : '';
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
        
        // Extract Financial Data
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
        $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
        $tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
        $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
        $grand_total = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0;
        
        $debug[] = "Financial: Subtotal=$subtotal, Discount=$discount, Tax=$tax_amount, Total=$grand_total";
        
        // Extract Notes and Terms
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $terms = isset($_POST['terms']) ? $_POST['terms'] : '';
        
        // Get User ID
        $userId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
        if($userId <= 0) {
            throw new Exception("User session expired. Please login again");
        }
        
        $debug[] = "User ID: $userId";
        
        // START TRANSACTION (like in project pattern)
        $connect->begin_transaction();
        $debug[] = "✓ Transaction started";
        
        // INSERT PO MASTER RECORD
        $insertPoSql = "INSERT INTO purchase_order (
                        po_number,
                        po_date,
                        po_type,
                        expected_delivery_date,
                        supplier_id,
                        supplier_name,
                        supplier_contact,
                        supplier_email,
                        supplier_gst,
                        supplier_address,
                        delivery_address,

                        sub_total,
                        total_discount,
                        discount_percent,
                        taxable_amount,

                        cgst_percent,
                        cgst_amount,
                        sgst_percent,
                        sgst_amount,
                        igst_percent,
                        igst_amount,

                        round_off,
                        grand_total,

                        payment_terms,
                        payment_method,
                        po_status,
                        payment_status,
                        created_by
                        ) VALUES (
                            ?,?,?,?,?,?,?,?,?,?,
                            ?,?,?,?,?,?,?,?,?,?,
                            ?,?,?,?,?,?,?,?
                        )";


        // $insertPoSql = "INSERT INTO purchase_order (
        //     po_number,
        //     po_date,
        //     po_type,
        //     expected_delivery_date,
        //     supplier_id,
        //     supplier_name,
        //     supplier_contact,
        //     supplier_email,
        //     supplier_gst,
        //     supplier_address,
        //     delivery_address,
        //     delivery_date,
        //     shipping_terms,
        //     payment_terms,
        //     payment_method,
        //     subtotal,
        //     discount,
        //     tax_rate,
        //     tax_amount,
        //     grand_total,
        //     notes,
        //     terms,
        //     po_status,
        //     payment_status,
        //     created_by,
        //     created_at,
        //     updated_at
        // ) VALUES (
        //     ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        // )";
        
        $stmt = $connect->prepare($insertPoSql);
        if(!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $po_status = 'Draft';
        $payment_status = 'Pending';
        
        // Bind parameters with types (25 parameters total)
        // s=string, i=integer, d=double


        $stmt->bind_param(
                            'ssssissssssddddddddddddssssi',
                            $po_number,
                            $po_date,
                            $po_type,
                            $expected_delivery_date,
                            $supplier_id,
                            $supplier_name,
                            $supplier_contact,
                            $supplier_email,
                            $supplier_gst,
                            $supplier_address,
                            $delivery_address,

                            $subtotal,              // sub_total
                            $discount,              // total_discount
                            $discount_percent,
                            $taxable_amount,

                            $cgst_percent,
                            $cgst_amount,
                            $sgst_percent,
                            $sgst_amount,
                            $igst_percent,
                            $igst_amount,

                            $round_off,
                            $grand_total,

                            $payment_terms,
                            $payment_method,
                            $po_status,
                            $payment_status,
                            $userId
                        );

        // $stmt->bind_param(
        //     'ssssissssssssssdddddsssssi',
        //     $po_number,              // s 1
        //     $po_date,                // s 2
        //     $po_type,                // s 3
        //     $expected_delivery_date, // s 4
        //     $supplier_id,            // i 5
        //     $supplier_name,          // s 6
        //     $supplier_contact,       // s 7
        //     $supplier_email,         // s 8
        //     $supplier_gst,           // s 9
        //     $supplier_address,       // s 10
        //     $delivery_address,       // s 11
        //     $delivery_date,          // s 12
        //     $shipping_terms,         // s 13
        //     $payment_terms,          // s 14
        //     $payment_method,         // s 15
        //     $subtotal,               // d 16
        //     $discount,               // d 17
        //     $tax_rate,               // d 18
        //     $tax_amount,             // d 19
        //     $grand_total,            // d 20
        //     $notes,                  // s 21
        //     $terms,                  // s 22
        //     $po_status,              // s 23
        //     $payment_status,         // s 24
        //     $userId                  // i 25
        // );
        
        if(!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $po_id = $connect->insert_id;
        $debug[] = "✓ PO Master inserted with ID: $po_id";
        $stmt->close();
        
        // PROCESS LINE ITEMS IF THEY EXIST
        $itemsProcessed = 0;
        if(isset($_POST['item_product_id']) && is_array($_POST['item_product_id'])) {
            $debug[] = "Processing line items...";
            $productIds = $_POST['item_product_id'];
            $quantities = isset($_POST['item_quantity']) ? $_POST['item_quantity'] : array();
            $rates = isset($_POST['item_rate']) ? $_POST['item_rate'] : array();
            $discounts = isset($_POST['item_discount']) ? $_POST['item_discount'] : array();
            $taxes = isset($_POST['item_tax']) ? $_POST['item_tax'] : array();

            $insertItemSql = "INSERT INTO purchase_order_items (
                                po_id,
                                po_number,
                                medicine_id,
                                medicine_name,
                                quantity_ordered,
                                unit_price,
                                line_amount,
                                tax_percent,
                                tax_amount,
                                item_total,
                                item_status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            
            // $insertItemSql = "INSERT INTO purchase_order_items (
            //     po_id,
            //     product_id,
            //     quantity,
            //     rate,
            //     discount,
            //     tax_percent,
            //     total,
            //     item_status,
            //     created_at
            // ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $itemStmt = $connect->prepare($insertItemSql);

foreach($_POST['item_product_id'] as $key => $medicine_id) {

    $medicine_id = intval($medicine_id);
    $medicine_name = $_POST['item_name'][$key];
    $qty = floatval($_POST['item_quantity'][$key]);
    $rate = floatval($_POST['item_rate'][$key]);
    $tax = floatval($_POST['item_tax'][$key]);

    if($medicine_id <= 0 || $qty <= 0) continue;

    $line_amount = $qty * $rate;
    $tax_amount = ($line_amount * $tax) / 100;
    $item_total = $line_amount + $tax_amount;
    $itemStatus = 'Pending';

    $itemStmt->bind_param(
        'isisiidddds',
        $po_id,
        $po_number,
        $medicine_id,
        $medicine_name,
        $qty,
        $rate,
        $line_amount,
        $tax,
        $tax_amount,
        $item_total,
        $itemStatus
    );

    $itemStmt->execute();
}

            
            // $itemStmt = $connect->prepare($insertItemSql);
            // if(!$itemStmt) {
            //     throw new Exception("Item Prepare failed: " . $connect->error);
            // }
            
            // foreach($productIds as $key => $productId) {
            //     $productId = intval($productId);
            //     $quantity = isset($quantities[$key]) ? floatval($quantities[$key]) : 0;
            //     $rate = isset($rates[$key]) ? floatval($rates[$key]) : 0;
            //     $discount = isset($discounts[$key]) ? floatval($discounts[$key]) : 0;
            //     $tax = isset($taxes[$key]) ? floatval($taxes[$key]) : 0;
                
            //     if($productId > 0 && $quantity > 0) {
            //         $itemAmount = ($quantity * $rate) - $discount;
            //         $itemTotal = $itemAmount + ($itemAmount * $tax / 100);
                    
            //         $itemStatus = 'Pending';
                    
            //         $itemStmt->bind_param(
            //             'iidddds',
            //             $po_id,
            //             $productId,
            //             $quantity,
            //             $rate,
            //             $discount,
            //             $tax,
            //             $itemTotal,
            //             $itemStatus
            //         );
                    
            //         if(!$itemStmt->execute()) {
            //             throw new Exception("Item Execute failed at index $key: " . $itemStmt->error);
            //         }
                    
            //         $itemsProcessed++;
            //     }
            // }
            
            $itemStmt->close();
            $debug[] = "✓ Inserted $itemsProcessed line items";
        }
        
        // COMMIT TRANSACTION
        $connect->commit();
        $debug[] = "✓ Transaction committed";
        
        // SUCCESS RESPONSE
        $valid['success'] = true;
        $valid['messages'] = "Purchase Order #$po_number created successfully with $itemsProcessed items";
        $valid['po_id'] = $po_id;
        $valid['po_number'] = $po_number;
        $valid['items'] = $itemsProcessed;
        
        // Redirect after success (matching project pattern like createProduct.php)
        header('location:../po_list.php');
        
    } catch(Exception $e) {
        // ROLLBACK ON ERROR
        $connect->rollback();
        $debug[] = "✗ Error: " . $e->getMessage();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }
    
    // Add debug info to response
    $valid['debug'] = $debug;
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode($valid);
    
} else {
    // No POST data
    $valid['success'] = false;
    $valid['messages'] = "Invalid request - POST data required";
    $valid['debug'] = array("No POST data received - Request method: " . $_SERVER['REQUEST_METHOD']);
    
    header('Content-Type: application/json');
    echo json_encode($valid);
}

// CLOSE CONNECTION
if(isset($connect)) {
    $connect->close();
}

exit;
