<?php
$c=new mysqli('localhost','root','', 'satyam_clinical_new');
if($c->connect_error){die('conn');}
$r=$c->query('SHOW COLUMNS FROM product');
while($row=$r->fetch_assoc()){echo $row['Field'].'\n';}
?>