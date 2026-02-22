<?php
/**
 * Purchase Invoice Module - Database Migration
 * Adds new columns and constraints for improved functionality
 */

require_once 'constant/connect.php';

echo "\n================== PURCHASE INVOICE - DATABASE MIGRATION ==================\n\n";

$status = 'SUCCESS';
$errors = [];

try {
    // 1. Add supplier_invoice_no column
    echo "1) Adding supplier_invoice_no column...\n";
    $check = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'supplier_invoice_no'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN supplier_invoice_no VARCHAR(100) NOT NULL DEFAULT '' AFTER invoice_no";
        if (!$connect->query($sql)) {
            $errors[] = "Failed to add supplier_invoice_no: " . $connect->error;
            echo "   ✗ ERROR: " . $connect->error . "\n";
        } else {
            echo "   ✓ Added supplier_invoice_no column\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 2. Add supplier_invoice_date column
    echo "\n2) Adding supplier_invoice_date column...\n";
    $check = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'supplier_invoice_date'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN supplier_invoice_date DATE AFTER supplier_invoice_no";
        if (!$connect->query($sql)) {
            $errors[] = "Failed to add supplier_invoice_date: " . $connect->error;
            echo "   ✗ ERROR: " . $connect->error . "\n";
        } else {
            echo "   ✓ Added supplier_invoice_date column\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 3. Add effective_rate column to purchase_invoice_items
    echo "\n3) Adding effective_rate column to purchase_invoice_items...\n";
    $check = $connect->query("SHOW COLUMNS FROM purchase_invoice_items LIKE 'effective_rate'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoice_items ADD COLUMN effective_rate DECIMAL(14,4) AFTER unit_cost";
        if (!$connect->query($sql)) {
            $errors[] = "Failed to add effective_rate: " . $connect->error;
            echo "   ✗ ERROR: " . $connect->error . "\n";
        } else {
            echo "   ✓ Added effective_rate column\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 4. Add updated_at column
    echo "\n4) Adding updated_at column...\n";
    $check = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'updated_at'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        if (!$connect->query($sql)) {
            $errors[] = "Failed to add updated_at: " . $connect->error;
            echo "   ✗ ERROR: " . $connect->error . "\n";
        } else {
            echo "   ✓ Added updated_at column\n";
        }
    } else {
        echo "   ✓ Column already exists\n";
    }

    // 5. Add place_of_supply column (if not exists)
    echo "\n5) Checking place_of_supply column...\n";
    $check = $connect->query("SHOW COLUMNS FROM purchase_invoices LIKE 'place_of_supply'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD COLUMN place_of_supply VARCHAR(100) DEFAULT 'Gujarat' AFTER supplier_location_state";
        if (!$connect->query($sql)) {
            echo "   ⚠ INFO: place_of_supply might use supplier_location_state instead\n";
        } else {
            echo "   ✓ Added place_of_supply column\n";
        }
    } else {
        echo "   ✓ Column already exists (or using supplier_location_state)\n";
    }

    // 6. Update status ENUM (remove 'Matched')
    echo "\n6) Updating status ENUM (removing 'Matched')...\n";
    $result = $connect->query("SHOW COLUMNS FROM purchase_invoices WHERE Field='status'");
    $column = $result->fetch_assoc();
    $currentType = $column['Type'];
    
    if (strpos($currentType, 'Matched') !== false) {
        // Need to change enum
        $sql = "ALTER TABLE purchase_invoices MODIFY COLUMN status ENUM('Draft','Approved','Cancelled','Received','Paid') NOT NULL DEFAULT 'Draft'";
        if (!$connect->query($sql)) {
            $errors[] = "Note: Could not modify status ENUM immediately. Status: " . $connect->error;
            echo "   ⚠ WARNING: " . $connect->error . "\n";
            echo "   (May have data with 'Matched' status - migration needed)\n";
        } else {
            echo "   ✓ Updated status ENUM\n";
        }
    } else {
        echo "   ✓ Status ENUM already correct\n";
    }

    // 7. Add unique constraint (supplier_id, supplier_invoice_no)
    echo "\n7) Adding unique constraint on (supplier_id, supplier_invoice_no)...\n";
    $result = $connect->query("SHOW INDEX FROM purchase_invoices WHERE Key_name='unique_supplier_invoice'");
    if ($result && $result->num_rows === 0) {
        $sql = "ALTER TABLE purchase_invoices ADD UNIQUE KEY unique_supplier_invoice (supplier_id, supplier_invoice_no)";
        if (!$connect->query($sql)) {
            $errors[] = "Failed to add unique constraint: " . $connect->error;
            echo "   ✗ ERROR: " . $connect->error . "\n";
        } else {
            echo "   ✓ Added unique constraint\n";
        }
    } else {
        echo "   ✓ Unique constraint already exists\n";
    }

    // Migration summary
    echo "\n" . str_repeat("═", 80) . "\n";
    if (count($errors) === 0) {
        echo "✅ ALL DATABASE MIGRATIONS COMPLETED SUCCESSFULLY\n";
    } else {
        echo "⚠️  MIGRATIONS COMPLETED WITH NOTES:\n";
        foreach ($errors as $err) {
            echo "  - {$err}\n";
        }
    }
    echo str_repeat("═", 80) . "\n";

} catch (Exception $e) {
    echo "❌ MIGRATION ERROR: " . $e->getMessage() . "\n";
}

$connect->close();
?>
