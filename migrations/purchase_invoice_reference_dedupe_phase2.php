<?php
/**
 * Purchase Invoice Reference Dedupe (Phase 2)
 * - Rewrites duplicate invoice_no values into PIE-yy-mm-xxxxx format
 * - Keeps earliest record per duplicate reference unchanged
 * - Adds global unique index on invoice_no when safe
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

function indexExists(mysqli $conn, string $table, string $indexName): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeIndex = $conn->real_escape_string($indexName);
    $res = $conn->query("SHOW INDEX FROM `{$safeTable}` WHERE Key_name = '{$safeIndex}'");
    return $res && $res->num_rows > 0;
}

function referenceExists(mysqli $conn, string $invoiceNo): bool
{
    $stmt = $conn->prepare("SELECT id FROM purchase_invoices WHERE invoice_no = ? LIMIT 1");
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param('s', $invoiceNo);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();
    return $exists;
}

function generateNextPieReference(mysqli $conn, ?string $invoiceDate): string
{
    $dateObj = DateTime::createFromFormat('Y-m-d', (string)$invoiceDate);
    if (!$dateObj) {
        $dateObj = new DateTime();
    }

    $prefix = 'PIE-' . $dateObj->format('y') . '-' . $dateObj->format('m') . '-';
    $like = $prefix . '%';
    $maxSeq = 0;

    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(invoice_no, 11, 5) AS UNSIGNED)) AS max_seq FROM purchase_invoices WHERE invoice_no LIKE ?");
    if ($stmt) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            $maxSeq = intval($row['max_seq'] ?? 0);
        }
        $stmt->close();
    }

    $seq = $maxSeq + 1;
    while (true) {
        $candidate = $prefix . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
        if (!referenceExists($conn, $candidate)) {
            return $candidate;
        }
        $seq++;
    }
}

function duplicateCount(mysqli $conn): int
{
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM (SELECT invoice_no FROM purchase_invoices GROUP BY invoice_no HAVING COUNT(*) > 1) d");
    if (!$res) return -1;
    $row = $res->fetch_assoc();
    return intval($row['cnt'] ?? 0);
}

echo "=== Purchase Invoice Reference Dedupe (Phase 2) ===" . PHP_EOL;

$before = duplicateCount($conn);
if ($before < 0) {
    logWarn('Could not count duplicates');
    $conn->close();
    exit;
}
logInfo("Duplicate reference groups before cleanup: {$before}");

if ($before > 0) {
    $conn->begin_transaction();
    try {
        $dupRes = $conn->query("SELECT invoice_no FROM purchase_invoices GROUP BY invoice_no HAVING COUNT(*) > 1");
        if (!$dupRes) {
            throw new Exception('Failed to fetch duplicate invoice_no groups: ' . $conn->error);
        }

        $updates = 0;
        while ($dup = $dupRes->fetch_assoc()) {
            $oldRef = $dup['invoice_no'];
            $rowsStmt = $conn->prepare("SELECT id, invoice_date FROM purchase_invoices WHERE invoice_no = ? ORDER BY id ASC");
            if (!$rowsStmt) {
                throw new Exception('Failed preparing duplicate row fetch');
            }
            $rowsStmt->bind_param('s', $oldRef);
            $rowsStmt->execute();
            $rows = $rowsStmt->get_result();
            $rowsStmt->close();

            $rowIndex = 0;
            while ($row = $rows->fetch_assoc()) {
                $rowIndex++;
                if ($rowIndex === 1) {
                    continue; // keep first occurrence unchanged
                }

                $newRef = generateNextPieReference($conn, $row['invoice_date'] ?? null);
                $updStmt = $conn->prepare("UPDATE purchase_invoices SET invoice_no = ? WHERE id = ?");
                if (!$updStmt) {
                    throw new Exception('Failed preparing update statement');
                }
                $rowId = intval($row['id']);
                $updStmt->bind_param('si', $newRef, $rowId);
                if (!$updStmt->execute()) {
                    $err = $updStmt->error;
                    $updStmt->close();
                    throw new Exception('Failed updating id ' . $rowId . ': ' . $err);
                }
                $updStmt->close();

                logInfo("Updated invoice id {$rowId}: {$oldRef} -> {$newRef}");
                $updates++;
            }
        }

        $conn->commit();
        logOk("Reference cleanup committed. Rows updated: {$updates}");
    } catch (Exception $e) {
        $conn->rollback();
        logWarn('Cleanup rolled back: ' . $e->getMessage());
        $conn->close();
        exit;
    }
} else {
    logOk('No duplicate references found; no rewrite needed');
}

$after = duplicateCount($conn);
logInfo("Duplicate reference groups after cleanup: {$after}");

if ($after === 0) {
    if (!indexExists($conn, 'purchase_invoices', 'uq_invoice_no_global')) {
        if ($conn->query("ALTER TABLE purchase_invoices ADD UNIQUE KEY uq_invoice_no_global (invoice_no)")) {
            logOk('Added global unique index uq_invoice_no_global on purchase_invoices.invoice_no');
        } else {
            logWarn('Could not add uq_invoice_no_global: ' . $conn->error);
        }
    } else {
        logOk('Global unique index uq_invoice_no_global already exists');
    }
} else {
    logWarn('Duplicates still remain, skipped unique index creation');
}

echo "=== Completed ===" . PHP_EOL;
$conn->close();
