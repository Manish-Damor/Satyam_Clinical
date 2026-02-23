<?php
require_once 'core.php';

try {
    $db = $connect; // from core
    // Check if column exists
    $res = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_items' AND COLUMN_NAME = 'quantity_received'");
    if ($res && $res->num_rows > 0) {
        echo "quantity_received column already exists.\n";
        exit;
    }

    // Add column safely
    $sql = "ALTER TABLE po_items ADD COLUMN quantity_received DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantity_ordered";
    if ($db->query($sql) === TRUE) {
        echo "Added quantity_received column to po_items.\n";
    } else {
        echo "Failed to alter table: " . $db->error . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

?>
