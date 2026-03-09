<?php
// DB credentials.
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

// DB connection.
$connect = new mysqli($localhost, $username, $password, $dbname);

// Check connection.
if ($connect->connect_error) {
    die("Connection Failed : " . $connect->connect_error);
}
