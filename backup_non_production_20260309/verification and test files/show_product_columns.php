<?php
$connect = new mysqli('localhost','root','','satyam_clinical');
if($connect->connect_error) die('connect error');
$res=$connect->query('SHOW COLUMNS FROM product');
while($r=$res->fetch_assoc()){echo $r['Field'].' '.$r['Type']."\n";}