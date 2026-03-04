<?php
/**
 * Purchase Invoice Schema Alignment (Phase 1)
 * - Adds transport/logistics fields seen on supplier invoice
 * - Adds snapshot columns for pack/manufacturer at line level
 * - Expands payment_mode enum with UPI
 * - Adds query-performance index on invoice_no
 *
 * Safe to re-run (idempotent checks included)
 */

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

function logOk($label)
{
    echo "[OK] {$label}" . PHP_EOL;
}

function logWarn($label)
{
    echo "[WARN] {$label}" . PHP_EOL;
}

function runQuery(mysqli $conn, string $sql, string $label)
{
    if ($conn->query($sql)) {
        logOk($label);
    } else {
        logWarn("{$label}: " . $conn->error);
    }
}

function tableExists(mysqli $conn, string $table): bool
{
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $res && $res->num_rows > 0;
}

function indexExists(mysqli $conn, string $table, string $indexName): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeIndex = $conn->real_escape_string($indexName);
    $res = $conn->query("SHOW INDEX FROM `{$safeTable}` WHERE Key_name = '{$safeIndex}'");
    return $res && $res->num_rows > 0;
}

function addColumnIfMissing(mysqli $conn, string $table, string $column, string $definition, string $after = '')
{
    if (columnExists($conn, $table, $column)) {
        logOk("Column {$table}.{$column} already exists");
        return;
    }

    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
    if ($after !== '') {
        $sql .= " AFTER `{$after}`";
    }
    runQuery($conn, $sql, "Add column {$table}.{$column}");
}

function getColumnType(mysqli $conn, string $table, string $column): ?string
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    $row = $res->fetch_assoc();
    return strtolower((string)($row['Type'] ?? ''));
}

echo "=== Purchase Invoice Schema Alignment (Phase 1) ===" . PHP_EOL;

if (!tableExists($conn, 'purchase_invoices') || !tableExists($conn, 'purchase_invoice_items')) {
    logWarn('Required purchase tables not found; aborting migration');
    $conn->close();
    exit;
}

// Header-level transport/logistics fields (from supplier tax invoice)
addColumnIfMissing($conn, 'purchase_invoices', 'lr_no', 'VARCHAR(80) NULL', 'supplier_invoice_date');
addColumnIfMissing($conn, 'purchase_invoices', 'lr_date', 'DATE NULL', 'lr_no');
addColumnIfMissing($conn, 'purchase_invoices', 'carrier_name', 'VARCHAR(150) NULL', 'lr_date');
addColumnIfMissing($conn, 'purchase_invoices', 'vehicle_no', 'VARCHAR(50) NULL', 'carrier_name');
addColumnIfMissing($conn, 'purchase_invoices', 'f_slip_no', 'VARCHAR(80) NULL', 'vehicle_no');
addColumnIfMissing($conn, 'purchase_invoices', 'eway_bill_no', 'VARCHAR(50) NULL', 'f_slip_no');
addColumnIfMissing($conn, 'purchase_invoices', 'eway_bill_date', 'DATE NULL', 'eway_bill_no');

// Line-level snapshot fields for historical stability
addColumnIfMissing($conn, 'purchase_invoice_items', 'pack_size_snapshot', 'VARCHAR(60) NULL', 'product_name');
addColumnIfMissing($conn, 'purchase_invoice_items', 'manufacturer_snapshot', 'VARCHAR(120) NULL', 'pack_size_snapshot');

// Expand payment mode enum to include UPI (if not already present)
$paymentModeType = getColumnType($conn, 'purchase_invoices', 'payment_mode');
if ($paymentModeType !== null && strpos($paymentModeType, "'upi'") === false) {
    runQuery(
        $conn,
        "ALTER TABLE `purchase_invoices` MODIFY `payment_mode` ENUM('Cash','Credit','Bank','Cheque','UPI') NULL",
        "Expand purchase_invoices.payment_mode enum"
    );
} else {
    logOk('purchase_invoices.payment_mode already includes UPI');
}

// Add direct invoice_no index for faster entry-ref search
if (!indexExists($conn, 'purchase_invoices', 'idx_invoice_no')) {
    runQuery($conn, "ALTER TABLE `purchase_invoices` ADD INDEX `idx_invoice_no` (`invoice_no`)", "Add idx_invoice_no index");
} else {
    logOk('Index idx_invoice_no already exists');
}

// Optional advisory: detect duplicates before any future global unique(invoice_no)
$dupSql = "SELECT invoice_no, COUNT(*) AS c FROM purchase_invoices GROUP BY invoice_no HAVING c > 1 LIMIT 10";
$dupRes = $conn->query($dupSql);
if ($dupRes && $dupRes->num_rows > 0) {
    logWarn('Duplicate invoice_no values detected; global unique(invoice_no) should not be added yet');
    while ($row = $dupRes->fetch_assoc()) {
        echo "   - " . $row['invoice_no'] . " (" . $row['c'] . ")" . PHP_EOL;
    }
} else {
    logOk('No duplicate invoice_no values found (safe for future global unique constraint)');
}

echo "=== Completed ===" . PHP_EOL;
$conn->close();
