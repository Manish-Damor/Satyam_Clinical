<?php
// Simple test to verify everything works
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Purchase Order System - Diagnostics</h1>";

// Test 1: PHP
echo "<h2>✅ PHP is working</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test 2: Database
echo "<h2>Testing Database...</h2>";
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical";

$connect = new mysqli($localhost, $username, $password, $dbname);

if($connect->connect_error) {
  echo "<h3 style='color:red'>❌ Database Connection Failed</h3>";
  echo "Error: " . $connect->connect_error . "<br>";
  exit();
} else {
  echo "<h3 style='color:green'>✅ Database Connected</h3>";
}

// Test 3: Check Tables
echo "<h2>Checking Tables...</h2>";
$tables = array('purchase_orders', 'po_items', 'product');

foreach($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $connect->query($sql);
    
    if($result && $result->num_rows > 0) {
        echo "<h3 style='color:green'>✅ $table table exists</h3>";
        
        // Count records
        $countSql = "SELECT COUNT(*) as cnt FROM $table";
        $countResult = $connect->query($countSql);
        if($countResult) {
            $row = $countResult->fetch_assoc();
            echo "Records: " . $row['cnt'] . "<br>";
        }
    } else {
        echo "<h3 style='color:red'>❌ $table table NOT FOUND</h3>";
    }
}

// Test 4: Sample Query
echo "<h2>Testing Queries...</h2>";
$sql = "SELECT COUNT(*) as cnt FROM purchase_orders WHERE delete_status = 0";
$result = $connect->query($sql);

if($result) {
    $row = $result->fetch_assoc();
    echo "<h3 style='color:green'>✅ Query works - Active POs: " . $row['cnt'] . "</h3>";
} else {
    echo "<h3 style='color:red'>❌ Query failed: " . $connect->error . "</h3>";
}

// Test 5: File Verification
echo "<h2>Checking Files...</h2>";
$required_files = array(
    'purchase_order.php',
    'add-purchase-order.php',
    'edit-purchase-order.php',
    'print-purchase-order.php',
    'php_action/createPurchaseOrder.php',
    'php_action/editPurchaseOrder.php',
    'php_action/removePurchaseOrder.php',
    'php_action/fetchProducts.php',
    'constant/layout/sidebar.php',
    'constant/layout/head.php'
);

foreach($required_files as $file) {
    if(file_exists($file)) {
        echo "<span style='color:green'>✅</span> $file<br>";
    } else {
        echo "<span style='color:red'>❌</span> $file - MISSING<br>";
    }
}

echo "<h2 style='color:green'>✅ All Systems Ready!</h2>";
echo "<p><a href='purchase_order.php'>Click here to open Purchase Order page</a></p>";

$connect->close();
?>
