<?php
require_once __DIR__ . '/../php_action/core.php';
// add missing columns
$alter = "ALTER TABLE purchase_orders ";
// using separate queries because MySQL may not support IF NOT EXISTS for ADD COLUMN in older versions
// We'll check columns first
$result = $connect->query("SHOW COLUMNS FROM purchase_orders LIKE 'po_type'");
if (!$result || $result->num_rows == 0) {
    $connect->query("ALTER TABLE purchase_orders ADD COLUMN po_type varchar(20) NOT NULL DEFAULT 'Regular'");
    echo "Added column po_type\n";
} else {
    echo "Column po_type already exists\n";
}
$result = $connect->query("SHOW COLUMNS FROM purchase_orders LIKE 'reference_number'");
if (!$result || $result->num_rows == 0) {
    $connect->query("ALTER TABLE purchase_orders ADD COLUMN reference_number varchar(100) DEFAULT NULL");
    echo "Added column reference_number\n";
} else {
    echo "Column reference_number already exists\n";
}
