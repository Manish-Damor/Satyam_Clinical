<?php
include 'constant/connect.php';

echo "=== Checking purchase_invoice_items table ===\n\n";

// Check NEW database
echo "1. satyam_clinical_new items table:\n";
$columnsResult = $connect->query("DESCRIBE purchase_invoice_items");
$newCols = [];
while ($col = $columnsResult->fetch_assoc()) {
    $newCols[] = $col['Field'];
    if ($col['Field'] === 'effective_rate') {
        echo "  ✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}

if (!in_array('effective_rate', $newCols)) {
    echo "  ✗ effective_rate - MISSING\n";
}

echo "\n2. satyam_clinical (OLD) items table:\n";
$oldConnect = new mysqli("localhost", "root", "", "satyam_clinical");
$columnsResult = $oldConnect->query("DESCRIBE purchase_invoice_items");
while ($col = $columnsResult->fetch_assoc()) {
    if ($col['Field'] === 'effective_rate') {
        echo "  ✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
$oldConnect->close();
?>
