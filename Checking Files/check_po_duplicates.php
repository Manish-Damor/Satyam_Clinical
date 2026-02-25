<?php
require './constant/connect.php';

echo "=== PO NUMBERS - LAST 20 ===\n";
$res = $connect->query("SELECT po_id, po_number, po_date FROM purchase_orders ORDER BY po_id DESC LIMIT 20");
while ($row = $res->fetch_assoc()) {
    echo $row['po_id'] . " | " . $row['po_number'] . " | " . $row['po_date'] . "\n";
}

echo "\n=== CHECKING FOR DUPLICATES ===\n";
$dupRes = $connect->query("SELECT po_number, COUNT(*) as cnt FROM purchase_orders GROUP BY po_number HAVING cnt > 1");
if ($dupRes->num_rows > 0) {
    echo "DUPLICATE PO NUMBERS FOUND:\n";
    while ($row = $dupRes->fetch_assoc()) {
        echo " - " . $row['po_number'] . " (appears " . $row['cnt'] . " times)\n";
    }
} else {
    echo "No duplicates found.\n";
}

echo "\n=== YEAR 2026 PO SEQUENCE ===\n";
$seqRes = $connect->query("SELECT MAX(CAST(SUBSTRING(po_number, -4) AS UNSIGNED)) as maxNum FROM purchase_orders WHERE YEAR(po_date) = 2026");
$seqRow = $seqRes->fetch_assoc();
echo "Current max sequence for 2026: " . ($seqRow['maxNum'] ? $seqRow['maxNum'] : 0) . "\n";

// Show all 2026 POs
$poRes = $connect->query("SELECT po_number FROM purchase_orders WHERE YEAR(po_date) = 2026 ORDER BY po_number");
echo "\nAll 2026 POs:\n";
while ($row = $poRes->fetch_assoc()) {
    echo " - " . $row['po_number'] . "\n";
}
?>
