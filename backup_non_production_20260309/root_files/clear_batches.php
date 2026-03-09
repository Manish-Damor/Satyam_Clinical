<?php
$conn = new mysqli('localhost', 'root', '', 'satyam_clinical_new');
$conn->query('DELETE FROM product_batches WHERE batch_id > 0');
echo 'Cleared product_batches: ' . $conn->affected_rows . " rows deleted\n";
$conn->query('DELETE FROM stock_movements WHERE movement_id > 0');
echo 'Cleared stock_movements: ' . $conn->affected_rows . " rows deleted\n";
$conn->close();
?>
