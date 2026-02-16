<?php 	

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical";
$store_url = "http://localhost/Satyam_Clinical/";
// db connection
$connect = new mysqli($localhost, $username, $password, $dbname);
// check connection
if($connect->connect_error) {
  die("Connection Failed : " . $connect->connect_error);
} else {
  // echo "Successfully connected";
}

?>