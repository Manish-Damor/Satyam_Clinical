<?php
chdir(__DIR__ . '/..');
require_once 'php_action/core.php';
$poNumber = trim(file_get_contents(__DIR__ . '/last_po_number.txt'));
if (!$poNumber) { echo "No PO number found\n"; exit(1); }
$stmt = $connect->prepare("SELECT * FROM purchase_orders WHERE po_number = ? LIMIT 1");
$stmt->bind_param('s', $poNumber);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "PO not found: $poNumber\n";
    exit(2);
}
$po = $res->fetch_assoc();
echo "Found PO: " . $po['po_id'] . " | " . $po['po_number'] . " | supplier_id=" . $po['supplier_id'] . " | grand_total=" . $po['grand_total'] . "\n";
// list items
$items = $connect->query("SELECT * FROM po_items WHERE po_id = " . intval($po['po_id']));
while($it = $items->fetch_assoc()){
    echo "Item: product_id=" . $it['product_id'] . " qty=" . $it['quantity_ordered'] . " unit_price=" . $it['unit_price'] . " gst_pct=" . ($it['gst_percentage'] ?? $it['tax_percent'] ?? 'N/A') . "\n";
}
$connect->close();
