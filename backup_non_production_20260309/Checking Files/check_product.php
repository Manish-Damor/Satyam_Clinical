<?php
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);

echo "Product Table Structure:\n";
$result = $conn->query("DESCRIBE product");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ") - Key: " . $row['Key'] . "\n";
}
$conn->close();
?>
