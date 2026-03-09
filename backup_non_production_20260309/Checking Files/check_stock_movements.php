<?php
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection Failed: " . $conn->connect_error);
}

echo "Stock Movements Table Structure:\n";
echo str_repeat("-", 70) . "\n";
$result = $conn->query("DESCRIBE stock_movements");
while ($row = $result->fetch_assoc()) {
    printf("%-25s | %-30s | %-5s\n", $row['Field'], $row['Type'], $row['Null']);
}

$conn->close();
?>
