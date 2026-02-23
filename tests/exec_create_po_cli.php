<?php
chdir(__DIR__ . '/..');
// Prepare POST-like environment and include createPurchaseOrder.php
$_SERVER['REQUEST_METHOD'] = 'POST';
if (session_status() == PHP_SESSION_NONE) session_start();
$_SESSION['userId'] = 1;

// pick a supplier and a product (use db_connect directly so core.php behavior doesn't redirect)
require_once 'php_action/db_connect.php';
$sup = $connect->query("SELECT supplier_id FROM suppliers WHERE supplier_status='Active' LIMIT 1")->fetch_assoc();
$prod = $connect->query("SELECT product_id, expected_mrp FROM product WHERE status=1 LIMIT 1")->fetch_assoc();
// DO NOT close $connect here - createPurchaseOrder.php will use the same connection

$poNumber = 'CLI-AUTO-' . time();
$_POST = [
    'po_number' => $poNumber,
    'po_date' => date('Y-m-d'),
    'po_type' => 'Regular',
    'supplier_id' => $sup['supplier_id'] ?? 0,
    'expected_delivery_date' => date('Y-m-d', strtotime('+7 days')),
    'delivery_location' => 'Main Warehouse',
    'sub_total' => 0,
    'discount_percent' => 0,
    'total_discount' => 0,
    'gst_percent' => 0,
    'gst_amount' => 0,
    'other_charges' => 0,
    'grand_total' => 0,
    'item_count' => 1,
    'medicine_id' => [ $prod['product_id'] ?? 0 ],
    'quantity' => [ 5 ],
    'unit_price' => [ isset($prod['expected_mrp']) ? floatval($prod['expected_mrp']) : 10.00 ],
    'gst_percentage' => [ 18 ]
];
// write the po number to a temp file so the checker can find it
file_put_contents(__DIR__ . '/last_po_number.txt', $poNumber);
// save session id so we can inspect session after handler runs
file_put_contents(__DIR__ . '/last_session_id.txt', session_id());

include 'php_action/createPurchaseOrder.php';

// Note: createPurchaseOrder.php will call header() and exit(); including it will terminate this script after processing.
