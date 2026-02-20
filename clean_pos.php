<?php
require_once 'php_action/core.php';

echo "Checking current PO count...\n";
$result = $connect->query("SELECT COUNT(*) as cnt FROM purchase_orders");
$row = $result->fetch_assoc();
echo "Current POs in database: " . $row['cnt'] . "\n";

if ($row['cnt'] > 0) {
    echo "\nDeleting all existing POs and items...\n";
    
    // Get all PO IDs first
    $poResult = $connect->query("SELECT po_id FROM purchase_orders");
    while ($po = $poResult->fetch_assoc()) {
        $poId = $po['po_id'];
        // Delete items first
        $connect->query("DELETE FROM po_items WHERE po_id = $poId");
    }
    
    // Delete all POs
    $connect->query("DELETE FROM purchase_orders");
    
    echo "✓ Deleted all existing POs and items\n";
}

echo "\nDatabase ready for fresh sample data.\n";
