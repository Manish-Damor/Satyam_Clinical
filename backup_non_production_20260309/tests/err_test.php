<?php
chdir('c:/xampp/htdocs/Satyam_Clinical');
require 'php_action/db_connect.php';

global $connect;

// simulate POST with no items

$_SERVER['REQUEST_METHOD']='POST';
session_start();
$_SESSION['userId']=1;

// pick a valid supplier
$sup = $connect->query('SELECT supplier_id FROM suppliers WHERE supplier_status="Active" LIMIT 1')->fetch_assoc();
$_POST = [
    'po_number'=>'TESTERR'.time(),
    'po_date'=>date('Y-m-d'),
    'supplier_id' => $sup['supplier_id'],
    'item_count'=>0
];

include 'php_action/createPurchaseOrder.php';
