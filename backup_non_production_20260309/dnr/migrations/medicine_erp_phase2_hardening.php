<?php
/**
 * Medicine ERP Phase-2 Hardening
 * - Configurable master tables (product type, unit type, GST slabs)
 * - Daily expiry housekeeping event
 * - DB guards for non-negative stock and valid movement quantities
 */

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

function runQuery(mysqli $conn, string $sql, string $label)
{
    if ($conn->query($sql)) {
        echo "[OK] {$label}" . PHP_EOL;
    } else {
        echo "[WARN] {$label}: " . $conn->error . PHP_EOL;
    }
}

echo "=== Medicine ERP Phase-2 Hardening ===" . PHP_EOL;

runQuery(
    $conn,
    "CREATE TABLE IF NOT EXISTS master_product_types (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        type_code VARCHAR(30) NOT NULL,
        display_name VARCHAR(60) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_type_code (type_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "Create master_product_types"
);

runQuery(
    $conn,
    "CREATE TABLE IF NOT EXISTS master_unit_types (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        unit_code VARCHAR(30) NOT NULL,
        display_name VARCHAR(60) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_unit_code (unit_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "Create master_unit_types"
);

runQuery(
    $conn,
    "CREATE TABLE IF NOT EXISTS master_gst_slabs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        gst_rate DECIMAL(5,2) NOT NULL,
        display_name VARCHAR(30) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_gst_rate (gst_rate)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "Create master_gst_slabs"
);

$productTypes = [
    ['Tablet', 'Tablet', 10],
    ['Capsule', 'Capsule', 20],
    ['Syrup', 'Syrup', 30],
    ['Injection', 'Injection', 40],
    ['Ointment', 'Ointment', 50],
    ['Drops', 'Drops', 60],
    ['Others', 'Others', 99],
];

$ptStmt = $conn->prepare(
    "INSERT INTO master_product_types (type_code, display_name, sort_order)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), sort_order = VALUES(sort_order)"
);
foreach ($productTypes as $row) {
    $ptStmt->bind_param('ssi', $row[0], $row[1], $row[2]);
    $ptStmt->execute();
}
$ptStmt->close();
echo "[OK] Seed master_product_types" . PHP_EOL;

$unitTypes = [
    ['Strip', 'Strip', 10],
    ['Box', 'Box', 20],
    ['Bottle', 'Bottle', 30],
    ['Vial', 'Vial', 40],
    ['Tube', 'Tube', 50],
    ['Piece', 'Piece', 60],
    ['Sachet', 'Sachet', 70],
];

$utStmt = $conn->prepare(
    "INSERT INTO master_unit_types (unit_code, display_name, sort_order)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), sort_order = VALUES(sort_order)"
);
foreach ($unitTypes as $row) {
    $utStmt->bind_param('ssi', $row[0], $row[1], $row[2]);
    $utStmt->execute();
}
$utStmt->close();
echo "[OK] Seed master_unit_types" . PHP_EOL;

$gstSlabs = [
    [0.00, '0%', 10],
    [5.00, '5%', 20],
    [12.00, '12%', 30],
    [18.00, '18%', 40],
    [28.00, '28%', 50],
];

$gstStmt = $conn->prepare(
    "INSERT INTO master_gst_slabs (gst_rate, display_name, sort_order)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), sort_order = VALUES(sort_order)"
);
foreach ($gstSlabs as $row) {
    $gstStmt->bind_param('dsi', $row[0], $row[1], $row[2]);
    $gstStmt->execute();
}
$gstStmt->close();
echo "[OK] Seed master_gst_slabs" . PHP_EOL;

runQuery($conn, "DROP TRIGGER IF EXISTS trg_product_batches_non_negative_ins", "Drop trigger trg_product_batches_non_negative_ins");
runQuery($conn, "DROP TRIGGER IF EXISTS trg_product_batches_non_negative_upd", "Drop trigger trg_product_batches_non_negative_upd");
runQuery($conn, "DROP TRIGGER IF EXISTS trg_stock_movements_quantity_chk_ins", "Drop trigger trg_stock_movements_quantity_chk_ins");
runQuery($conn, "DROP TRIGGER IF EXISTS trg_stock_movements_quantity_chk_upd", "Drop trigger trg_stock_movements_quantity_chk_upd");

runQuery(
    $conn,
    "CREATE TRIGGER trg_product_batches_non_negative_ins
     BEFORE INSERT ON product_batches
     FOR EACH ROW
     BEGIN
       IF NEW.available_quantity < 0 OR NEW.reserved_quantity < 0 OR NEW.damaged_quantity < 0 THEN
         SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch quantities cannot be negative';
       END IF;
     END",
    "Create trigger trg_product_batches_non_negative_ins"
);

runQuery(
    $conn,
    "CREATE TRIGGER trg_product_batches_non_negative_upd
     BEFORE UPDATE ON product_batches
     FOR EACH ROW
     BEGIN
       IF NEW.available_quantity < 0 OR NEW.reserved_quantity < 0 OR NEW.damaged_quantity < 0 THEN
         SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch quantities cannot be negative';
       END IF;
     END",
    "Create trigger trg_product_batches_non_negative_upd"
);

runQuery(
    $conn,
    "CREATE TRIGGER trg_stock_movements_quantity_chk_ins
     BEFORE INSERT ON stock_movements
     FOR EACH ROW
     BEGIN
       IF NEW.quantity <= 0 THEN
         SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock movement quantity must be greater than 0';
       END IF;
     END",
    "Create trigger trg_stock_movements_quantity_chk_ins"
);

runQuery(
    $conn,
    "CREATE TRIGGER trg_stock_movements_quantity_chk_upd
     BEFORE UPDATE ON stock_movements
     FOR EACH ROW
     BEGIN
       IF NEW.quantity <= 0 THEN
         SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock movement quantity must be greater than 0';
       END IF;
     END",
    "Create trigger trg_stock_movements_quantity_chk_upd"
);

runQuery($conn, "SET GLOBAL event_scheduler = ON", "Enable event scheduler");
runQuery($conn, "DROP EVENT IF EXISTS ev_mark_expired_batches_daily", "Drop existing expiry event");

runQuery(
    $conn,
    "CREATE EVENT ev_mark_expired_batches_daily
     ON SCHEDULE EVERY 1 DAY
     STARTS CURRENT_TIMESTAMP + INTERVAL 1 MINUTE
     DO
       UPDATE product_batches
       SET status = 'Expired', updated_at = CURRENT_TIMESTAMP
       WHERE status = 'Active' AND expiry_date < CURDATE()",
    "Create daily expiry housekeeping event"
);

runQuery(
    $conn,
    "UPDATE product_batches
     SET status = 'Expired', updated_at = CURRENT_TIMESTAMP
     WHERE status = 'Active' AND expiry_date < CURDATE()",
    "Run one-time expiry synchronization"
);

echo "=== Completed ===" . PHP_EOL;
$conn->close();
