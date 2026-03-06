<?php 

session_start();

require_once 'db_connect.php';

if (!isset($_SESSION['userId']) && isset($_SESSION['user_id'])) {
	$_SESSION['userId'] = $_SESSION['user_id'];
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['userId'])) {
	$_SESSION['user_id'] = $_SESSION['userId'];
}

if (empty($_SESSION['userId'])) {
	header('location:'.$store_url);	
} 



?>