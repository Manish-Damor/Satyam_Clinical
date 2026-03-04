<?php
header('Content-Type: application/json');
require_once 'core.php';

if (!isset($connect) || !$connect) {
    echo json_encode(['success' => false, 'error' => 'Database connection missing']);
    exit;
}

$dateInput = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
$dateObj = DateTime::createFromFormat('Y-m-d', $dateInput);
if (!$dateObj) {
    $dateObj = new DateTime();
}

$prefix = 'PIE-' . $dateObj->format('y') . '-' . $dateObj->format('m') . '-';
$like = $prefix . '%';
$maxSeq = 0;

$stmt = $connect->prepare("SELECT MAX(CAST(SUBSTRING(invoice_no, 11, 5) AS UNSIGNED)) AS max_seq FROM purchase_invoices WHERE invoice_no LIKE ?");
if ($stmt) {
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $maxSeq = intval($row['max_seq'] ?? 0);
    }
    $stmt->close();
}

$reference = $prefix . str_pad((string)($maxSeq + 1), 5, '0', STR_PAD_LEFT);
echo json_encode(['success' => true, 'reference' => $reference]);
exit;
?>
