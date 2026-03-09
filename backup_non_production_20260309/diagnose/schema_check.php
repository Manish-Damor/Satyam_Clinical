<?php
require './constant/connect.php';

echo "=== LIVE DATABASE SCHEMA CHECK ===\n\n";

// 1. Check product table structure
echo "1. PRODUCT TABLE STRUCTURE:\n";
$result = $connect->query("DESCRIBE product");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? "NOT NULL" : "NULL") . "\n";
}

// 2. Check product_batches table structure
echo "\n2. PRODUCT_BATCHES TABLE STRUCTURE:\n";
$result = $connect->query("DESCRIBE product_batches");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? "NOT NULL" : "NULL") . "\n";
}

// 3. Check sales_invoices table structure
echo "\n3. SALES_INVOICES TABLE STRUCTURE:\n";
$result = $connect->query("DESCRIBE sales_invoices");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? "NOT NULL" : "NULL") . "\n";
}

// 4. Check sales_invoice_items table structure
echo "\n4. SALES_INVOICE_ITEMS TABLE STRUCTURE:\n";
$result = $connect->query("DESCRIBE sales_invoice_items");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? "NOT NULL" : "NULL") . "\n";
}

// 5. Check clients table structure
echo "\n5. CLIENTS TABLE STRUCTURE:\n";
$result = $connect->query("DESCRIBE clients");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? "NOT NULL" : "NULL") . "\n";
}

// 6. Check sample data in product
echo "\n6. SAMPLE PRODUCT DATA (first 3 rows):\n";
$result = $connect->query("SELECT product_id, product_name, hsn_code, expected_mrp, purchase_rate, gst_rate FROM product LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "  ID: " . $row['product_id'] . ", Name: " . $row['product_name'] . ", HSN: " . $row['hsn_code'] . ", MRP: " . $row['expected_mrp'] . ", PTR: " . $row['purchase_rate'] . ", GST: " . $row['gst_rate'] . "\n";
}

// 7. Check sample data in product_batches
echo "\n7. SAMPLE PRODUCT_BATCHES DATA (first 3 rows):\n";
$result = $connect->query("SELECT batch_id, product_id, batch_number, expiry_date, available_quantity, purchase_rate, mrp FROM product_batches LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "  Batch ID: " . $row['batch_id'] . ", Product: " . $row['product_id'] . ", Number: " . $row['batch_number'] . ", Expiry: " . $row['expiry_date'] . ", Avail: " . $row['available_quantity'] . ", MRP: " . $row['mrp'] . ", PTR: " . $row['purchase_rate'] . "\n";
}

// 8. Check sample data in sales_invoices
echo "\n8. SAMPLE SALES_INVOICES DATA (first 2 rows):\n";
$result = $connect->query("SELECT invoice_id, invoice_number, client_id, invoice_date, subtotal, discount_amount, gst_amount, grand_total FROM sales_invoices LIMIT 2");
while ($row = $result->fetch_assoc()) {
    echo "  Invoice ID: " . $row['invoice_id'] . ", Number: " . $row['invoice_number'] . ", Client: " . $row['client_id'] . ", Date: " . $row['invoice_date'] . ", Grand Total: " . $row['grand_total'] . "\n";
}

// 9. Check sample data in sales_invoice_items
echo "\n9. SAMPLE SALES_INVOICE_ITEMS DATA (first 3 rows):\n";
$result = $connect->query("SELECT item_id, invoice_id, product_id, batch_id, quantity, unit_rate, purchase_rate, gst_rate, line_total FROM sales_invoice_items LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "  Item: " . $row['item_id'] . ", Invoice: " . $row['invoice_id'] . ", Product: " . $row['product_id'] . ", Batch: " . $row['batch_id'] . ", Qty: " . $row['quantity'] . ", Rate: " . $row['unit_rate'] . ", PTR: " . $row['purchase_rate'] . ", Total: " . $row['line_total'] . "\n";
}

echo "\n=== END SCHEMA CHECK ===\n";
?>
