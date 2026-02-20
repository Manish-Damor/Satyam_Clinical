<?php
$connect = new mysqli('localhost','root','','satyam_clinical');
if($connect->connect_error) die('connect error');
$res=$connect->query('SHOW COLUMNS FROM product_batches');
if(!$res){echo 'error '.$connect->error;exit;}
while($r=$res->fetch_assoc()){echo $r['Field'].' '.$r['Type']."\n";}