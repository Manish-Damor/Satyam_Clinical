<?php
// Test file to diagnose issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNOSTIC TEST ===\n\n";

// Test 1: Database Connection
echo "TEST 1: Database Connection\n";
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical";

$connect = new mysqli($localhost, $username, $password, $dbname);

if($connect->connect_error) {
  echo "❌ Connection Failed: " . $connect->connect_error . "\n";
  exit();
} else {
  echo "✅ Database Connected Successfully\n";
}

// Test 2: Tables Exist
echo "\nTEST 2: Check Tables\n";
$sql = "SHOW TABLES LIKE 'purchase_orders'";
$result = $connect->query($sql);

if($result && $result->num_rows > 0) {
    echo "✅ purchase_orders table exists\n";
} else {
    echo "❌ purchase_orders table NOT found\n";
}

$sql = "SHOW TABLES LIKE 'po_items'";
$result = $connect->query($sql);

if($result && $result->num_rows > 0) {
    echo "✅ po_items table exists\n";
} else {
    echo "❌ po_items table NOT found\n";
}

// Test 3: Table Structure
echo "\nTEST 3: Table Structure\n";
$sql = "DESCRIBE purchase_orders";
$result = $connect->query($sql);

if($result && $result->num_rows > 0) {
    echo "✅ purchase_orders columns: ";
    $count = 0;
    while($row = $result->fetch_assoc()) {
        if($count > 0) echo ", ";
        echo $row['Field'];
        $count++;
    }
    echo "\n";
} else {
    echo "❌ Could not read table structure\n";
}

// Test 4: Query Test
echo "\nTEST 4: Sample Query\n";
$sql = "SELECT COUNT(*) as cnt FROM purchase_orders WHERE delete_status = 0";
$result = $connect->query($sql);

if($result) {
    $row = $result->fetch_assoc();
    echo "✅ Query executed - Records found: " . $row['cnt'] . "\n";
} else {
    echo "❌ Query failed: " . $connect->error . "\n";
}

// Test 5: Product Table
echo "\nTEST 5: Product Table\n";
$sql = "SELECT COUNT(*) as cnt FROM product";
$result = $connect->query($sql);

if($result) {
    $row = $result->fetch_assoc();
    echo "✅ Products table exists - Records: " . $row['cnt'] . "\n";
} else {
    echo "❌ Products table issue: " . $connect->error . "\n";
}

// Test 6: File Permissions
echo "\nTEST 6: File Permissions\n";
$files = array(
    './constant/connect.php' => 'constant/connect.php',
    './constant/layout/head.php' => 'constant/layout/head.php',
    './constant/layout/header.php' => 'constant/layout/header.php',
    './constant/layout/sidebar.php' => 'constant/layout/sidebar.php',
    './constant/layout/footer.php' => 'constant/layout/footer.php',
    './purchase_order.php' => 'purchase_order.php'
);

foreach($files as $path => $name) {
    if(file_exists($path)) {
        echo "✅ $name - exists\n";
    } else {
        echo "❌ $name - MISSING\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n";
$connect->close();
?>
