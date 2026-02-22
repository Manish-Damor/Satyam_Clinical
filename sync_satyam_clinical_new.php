<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'constant/connect.php';

echo "=== Syncing satyam_clinical_new with Phase 2 Changes ===\n\n";

try {
    // 1. Add supplier_invoice_no column
    echo "1. Adding supplier_invoice_no column...\n";
    $checkCol = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'supplier_invoice_no'");
    if ($checkCol->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN supplier_invoice_no VARCHAR(100) AFTER invoice_no";
        if ($connect->query($sql)) {
            echo "   ✓ Column added\n";
        } else {
            echo "   ✗ Error: " . $connect->error . "\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 2. Add supplier_invoice_date column
    echo "2. Adding supplier_invoice_date column...\n";
    $checkCol = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'supplier_invoice_date'");
    if ($checkCol->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN supplier_invoice_date DATE AFTER supplier_invoice_no";
        if ($connect->query($sql)) {
            echo "   ✓ Column added\n";
        } else {
            echo "   ✗ Error: " . $connect->error . "\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 3. Add place_of_supply column
    echo "3. Adding place_of_supply column...\n";
    $checkCol = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'place_of_supply'");
    if ($checkCol->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN place_of_supply VARCHAR(100) DEFAULT 'Gujarat' AFTER supplier_location_state";
        if ($connect->query($sql)) {
            echo "   ✓ Column added\n";
        } else {
            echo "   ✗ Error: " . $connect->error . "\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 4. Add effective_rate to items table
    echo "4. Adding effective_rate to purchase_invoice_items...\n";
    $checkCol = $connect->query("SHOW COLUMNS FROM purchase_invoice_items LIKE 'effective_rate'");
    if ($checkCol->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoice_items ADD COLUMN effective_rate DECIMAL(14,4) AFTER unit_cost";
        if ($connect->query($sql)) {
            echo "   ✓ Column added\n";
        } else {
            echo "   ✗ Error: " . $connect->error . "\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 5. Update status ENUM to remove 'Matched' and reorder
    echo "5. Updating status ENUM...\n";
    $sql = "ALTER TABLE purchase_invoices MODIFY COLUMN status ENUM('Draft','Approved','Cancelled','Received','Paid') DEFAULT 'Draft'";
    if ($connect->query($sql)) {
        echo "   ✓ ENUM updated\n";
    } else {
        // This might fail if there are 'Matched' values, so try to fix them first
        echo "   ⚠ Will attempt to clean up old values first...\n";
        $connect->query("UPDATE purchase_invoices SET status = 'Draft' WHERE status = 'Matched'");
        if ($connect->query($sql)) {
            echo "   ✓ ENUM updated after cleanup\n";
        } else {
            echo "   ✗ Error: " . $connect->error . "\n";
        }
    }

    // 6. Create unique constraint on supplier_invoice_no
    echo "6. Adding unique constraint on (supplier_id, supplier_invoice_no)...\n";
    $constraintCheck = $connect->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='purchase_invoices' AND COLUMN_NAME='supplier_invoice_no' AND CONSTRAINT_NAME LIKE 'unique%'");
    if ($constraintCheck && $constraintCheck->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD UNIQUE KEY unique_supplier_invoice (supplier_id, supplier_invoice_no)";
        if ($connect->query($sql)) {
            echo "   ✓ Constraint added\n";
        } else {
            echo "   ⚠ Constraint might already exist: " . $connect->error . "\n";
        }
    } else {
        echo "   ✓ Constraint already exists\n";
    }

    echo "\n✅ satyam_clinical_new is now synced with Phase 2 changes!\n";
    
    // Verify
    echo "\n=== Verification ===\n";
    $cols = $connect->query("DESCRIBE purchase_invoices");
    $verified = [];
    while ($col = $cols->fetch_assoc()) {
        $verified[] = $col['Field'];
    }
    
    $required = ['supplier_invoice_no', 'supplier_invoice_date', 'place_of_supply'];
    echo "Required Phase 2 columns in purchase_invoices:\n";
    foreach ($required as $col) {
        if (in_array($col, $verified)) {
            echo "  ✓ $col\n";
        } else {
            echo "  ✗ $col\n";
        }
    }

    echo "\nItems table:\n";
    $itemCols = $connect->query("DESCRIBE purchase_invoice_items");
    $itemVerified = [];
    while ($col = $itemCols->fetch_assoc()) {
        $itemVerified[] = $col['Field'];
    }
    
    if (in_array('effective_rate', $itemVerified)) {
        echo "  ✓ effective_rate\n";
    } else {
        echo "  ✗ effective_rate\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>
