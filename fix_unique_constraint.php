<?php
require_once 'constant/connect.php';

echo "\n Fixing unique constraint issue...\n";

// First, populate supplier_invoice_no with invoice_no for existing records that are empty
$sql = "UPDATE purchase_invoices SET supplier_invoice_no = invoice_no WHERE supplier_invoice_no = '' OR supplier_invoice_no IS NULL";
if ($connect->query($sql)) {
    echo "✓ Populated empty supplier_invoice_no values\n";
} else {
    echo "✗ Error: " . $connect->error . "\n";
}

// Now try to add the unique constraint again
$sql = "ALTER TABLE purchase_invoices ADD UNIQUE KEY unique_supplier_invoice (supplier_id, supplier_invoice_no)";
if ($connect->query($sql)) {
    echo "✓ Added unique constraint successfully\n";
} else {
    echo "Note: " . $connect->error . "\n";
}

// Verify
$result = $connect->query("SHOW INDEX FROM purchase_invoices WHERE Key_name='unique_supplier_invoice'");
if ($result && $result->num_rows > 0) {
    echo "\n✅ Unique constraint is now in place\n";
} else {
    echo "\n⚠️  Constraint not yet applied (may have duplicate combinations)\n";
}

$connect->close();
?>
