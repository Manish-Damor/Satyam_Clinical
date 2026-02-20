<?php
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);

echo "Purchase Orders Table structure:\n";
$result = $conn->query("DESCRIBE purchase_orders");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - Key: " . $row['Key'] . "\n";
}

$conn->close();
?>
