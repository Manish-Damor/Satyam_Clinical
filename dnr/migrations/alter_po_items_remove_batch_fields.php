<?php
/**
 * Migration: Remove batch_number, expiry_date, manufacturing_date from po_items
 * These fields should NOT be in PO items — batches exist only in stock_batches after invoice approval.
 * Date: 2026-02-22
 */
require_once __DIR__ . '/../constant/connect.php';

echo "=== Migration: Remove Batch Fields from PO Items ===\n\n";

try {
    // Start transaction
    $connect->begin_transaction();

    // Check if columns exist before dropping
    $result = $connect->query("DESCRIBE po_items batch_number");
    if ($result && $result->num_rows > 0) {
        echo "Dropping batch_number column...\n";
        $connect->query("ALTER TABLE po_items DROP COLUMN batch_number");
        echo "✓ batch_number dropped\n\n";
    } else {
        echo "✓ batch_number already removed or doesn't exist\n\n";
    }

    $result = $connect->query("DESCRIBE po_items expiry_date");
    if ($result && $result->num_rows > 0) {
        echo "Dropping expiry_date column...\n";
        $connect->query("ALTER TABLE po_items DROP COLUMN expiry_date");
        echo "✓ expiry_date dropped\n\n";
    } else {
        echo "✓ expiry_date already removed or doesn't exist\n\n";
    }

    $result = $connect->query("DESCRIBE po_items manufacturing_date");
    if ($result && $result->num_rows > 0) {
        echo "Dropping manufacturing_date column...\n";
        $connect->query("ALTER TABLE po_items DROP COLUMN manufacturing_date");
        echo "✓ manufacturing_date dropped\n\n";
    } else {
        echo "✓ manufacturing_date already removed or doesn't exist\n\n";
    }

    // Add pending_qty if not exists
    $result = $connect->query("DESCRIBE po_items pending_qty");
    if (!($result && $result->num_rows > 0)) {
        echo "Adding pending_qty column...\n";
        $connect->query("ALTER TABLE po_items ADD COLUMN pending_qty INT(10) UNSIGNED DEFAULT 0 AFTER quantity_ordered");
        echo "✓ pending_qty added\n\n";
    } else {
        echo "✓ pending_qty already exists\n\n";
    }

    // Add index on (po_id, product_id)
    $indexCheck = $connect->query("SHOW INDEX FROM po_items WHERE Column_name='product_id' AND Column_name='po_id'");
    if (!($indexCheck && $indexCheck->num_rows > 0)) {
        echo "Adding composite index (po_id, product_id)...\n";
        $connect->query("ALTER TABLE po_items ADD INDEX idx_po_product (po_id, product_id)");
        echo "✓ Index added\n\n";
    } else {
        echo "✓ Index already exists\n\n";
    }

    $connect->commit();
    echo "✅ Migration completed successfully\n";

} catch (Exception $e) {
    $connect->rollback();
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
