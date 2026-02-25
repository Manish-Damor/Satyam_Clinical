<?php
$connect = new mysqli('localhost','root','', 'satyam_clinical_new');
if($connect->connect_error) { echo 'conn_err'; exit; }
$res = $connect->query("SHOW COLUMNS FROM po_items LIKE 'gst_percentage'");
if($res && $res->num_rows>0) echo 'exists'; else echo 'missing';
?>