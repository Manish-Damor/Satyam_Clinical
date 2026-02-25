<?php
$connect = new mysqli('localhost','root','', 'satyam_clinical_new');
if($connect->connect_error) { die('conn fail'); }
if($connect->query("ALTER TABLE po_items ADD COLUMN gst_percentage FLOAT DEFAULT 18;") === TRUE) {
    echo "added";
} else {
    echo "error: " . $connect->error;
}
?>