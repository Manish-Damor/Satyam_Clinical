<?php
include 'constant/connect.php';

echo "=== Comparing Databases ===\n\n";

// Check if purchase_invoices table exists in current (new) db
echo "1. Checking satyam_clinical_new for purchase_invoices table:\n";
$tableCheck = $connect->query("SHOW TABLES LIKE 'purchase_invoices'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "✓ Table exists\n";
    
    $columnsResult = $connect->query("DESCRIBE purchase_invoices");
    $currentCols = [];
    echo "\nCurrent columns in satyam_clinical_new:\n";
    while ($col = $columnsResult->fetch_assoc()) {
        $currentCols[] = $col['Field'];
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check for missing Phase 2 columns
    echo "\n2. Checking for Phase 2 changes:\n";
    $required_new_cols = [
        'supplier_invoice_no' => 'NEW (added in Phase 2)',
        'supplier_invoice_date' => 'NEW (added in Phase 2)',
        'place_of_supply' => 'NEW (added in Phase 2)',
        'updated_at' => 'NEW (added in Phase 2)',
        'effective_rate' => 'NEW but in items table (Phase 2)'
    ];
    
    foreach ($required_new_cols as $col => $desc) {
        if (in_array($col, $currentCols)) {
            echo "  ✓ $col - EXISTS\n";
        } else {
            echo "  ✗ $col - MISSING ($desc)\n";
        }
    }
} else {
    echo "✗ Table does not exist\n";
}

// Now check old database
echo "\n\n3. Checking satyam_clinical (OLD) database:\n";
$oldConnect = new mysqli("localhost", "root", "", "satyam_clinical");
if ($oldConnect->connect_error) {
    echo "✗ Cannot connect to old db\n";
} else {
    echo "✓ Connected to old satyam_clinical\n";
    $tableCheck = $oldConnect->query("SHOW TABLES LIKE 'purchase_invoices'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "✓ Table exists in old db\n";
        
        $columnsResult = $oldConnect->query("DESCRIBE purchase_invoices");
        $oldCols = [];
        echo "\nColumns in satyam_clinical (OLD):\n";
        while ($col = $columnsResult->fetch_assoc()) {
            $oldCols[] = $col['Field'];
            // Highlight new Phase 2 changes
            if (in_array($col['Field'], ['supplier_invoice_no', 'supplier_invoice_date', 'place_of_supply'])) {
                echo "  - [PHASE 2] " . $col['Field'] . " (" . $col['Type'] . ")\n";
            } else {
                echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        }
    }
    $oldConnect->close();
}

echo "\n\n4. Summary of Missing Changes:\n";
$missingChanges = array_diff($oldCols ?? [], $currentCols);
if (!empty($missingChanges)) {
    echo "Missing " . count($missingChanges) . " columns in NEW database:\n";
    foreach ($missingChanges as $col) {
        echo "  ✗ $col\n";
    }
} else {
    echo "All columns are synchronized!\n";
}
?>
