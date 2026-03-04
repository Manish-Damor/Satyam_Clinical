<?php
/**
 * Purchase Invoice FK Repair (Phase 2)
 * Creates placeholder suppliers for missing supplier_id values referenced by purchase_invoices.
 * This unblocks updates on legacy rows while preserving existing invoice data.
 */

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

function logOk($msg) { echo "[OK] {$msg}" . PHP_EOL; }
function logWarn($msg) { echo "[WARN] {$msg}" . PHP_EOL; }
function logInfo($msg) { echo "[INFO] {$msg}" . PHP_EOL; }

echo "=== Purchase Invoice FK Repair (Phase 2) ===" . PHP_EOL;

$sql = "SELECT DISTINCT pi.supplier_id
        FROM purchase_invoices pi
        LEFT JOIN suppliers s ON s.supplier_id = pi.supplier_id
        WHERE s.supplier_id IS NULL
        ORDER BY pi.supplier_id";
$res = $conn->query($sql);
if (!$res) {
    logWarn('Failed to identify missing supplier IDs: ' . $conn->error);
    $conn->close();
    exit;
}

$missingIds = [];
while ($row = $res->fetch_assoc()) {
    $sid = intval($row['supplier_id'] ?? 0);
    if ($sid > 0) {
        $missingIds[] = $sid;
    }
}

if (empty($missingIds)) {
    logOk('No missing supplier references found');
    echo "=== Completed ===" . PHP_EOL;
    $conn->close();
    exit;
}

$conn->begin_transaction();
try {
    $insert = $conn->prepare("INSERT INTO suppliers (supplier_id, supplier_code, supplier_name, company_name, phone, address, city, state, country, credit_days, supplier_status, is_verified, payment_terms)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'India', 30, 'Inactive', 0, 'Legacy Imported')");
    if (!$insert) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    foreach ($missingIds as $sid) {
        $code = 'LEGACY-' . str_pad((string)$sid, 5, '0', STR_PAD_LEFT);
        $name = 'Legacy Supplier ' . $sid;
        $company = 'Legacy Supplier ' . $sid;
        $phone = '0000000000';
        $address = 'Legacy placeholder (auto-created for FK repair)';
        $city = 'Unknown';
        $state = 'Unknown';

        $insert->bind_param('isssssss', $sid, $code, $name, $company, $phone, $address, $city, $state);
        if (!$insert->execute()) {
            throw new Exception('Insert failed for supplier_id ' . $sid . ': ' . $insert->error);
        }

        logInfo('Inserted placeholder supplier_id=' . $sid);
    }

    $insert->close();
    $conn->commit();
    logOk('FK repair committed successfully');
} catch (Exception $e) {
    $conn->rollback();
    logWarn('FK repair rolled back: ' . $e->getMessage());
}

echo "=== Completed ===" . PHP_EOL;
$conn->close();
