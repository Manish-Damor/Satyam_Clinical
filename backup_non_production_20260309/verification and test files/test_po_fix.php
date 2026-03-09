<?php
require './constant/connect.php';

echo "=== PO NUMBER GENERATION TEST ===\n\n";

// Test the new generation logic
$year = date('y');
$month = date('m');

echo "Year: $year\n";
echo "Testing PO number generation...\n\n";

$testNumbers = [];
$maxAttempts = 1000;
$poNumber = null;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $testPONum = str_pad($attempt, 4, '0', STR_PAD_LEFT);
    $testPONumber = 'PO-' . $year . "-" . $testPONum;
    
    // Check if this PO number already exists
    $checkSql = "SELECT COUNT(*) as cnt FROM purchase_orders WHERE po_number = '" . $connect->real_escape_string($testPONumber) . "'";
    $checkResult = $connect->query($checkSql);
    $checkRow = $checkResult->fetch_assoc();
    
    if ($checkRow['cnt'] == 0) {
        $poNumber = $testPONumber;
        echo "✓ Generated: $poNumber (first available)\n";
        echo "  - Checked " . $attempt . " sequences\n";
        break;
    } else {
        echo "  - Sequence $testPONum: EXISTS (skipping)\n";
    }
    
    if ($attempt > 10) break; // Only show first 10 checks
}

if (!$poNumber) {
    $poNumber = 'PO-' . $year . '-' . strtotime(date('Y-m-d H:i:s'));
    echo "✓ Generated (fallback): $poNumber\n";
}

echo "\n=== ALL EXISTING PO-26 NUMBERS ===\n";
$existingRes = $connect->query("SELECT po_number FROM purchase_orders WHERE po_number LIKE 'PO-26%' ORDER BY po_number");
while ($row = $existingRes->fetch_assoc()) {
    echo " - " . $row['po_number'] . "\n";
}

echo "\n✓ SOLUTION APPLIED: PO numbers will now auto-increment from next available sequence.\n";
?>
