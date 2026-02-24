<?php
// CLI test to create invoice with one item
$_SERVER['REQUEST_METHOD']='POST';
$_POST = [
    'client_id' => 1,
    'invoice_number' => 'TEST-CLI-ITEM-' . time(),
    'invoice_date' => date('Y-m-d'),
    'subtotal' => 100,
    'discount_amount' => 0,
    'discount_percent' => 0,
    'gst_amount' => 18,
    'grand_total' => 118,
    'paid_amount' => 118,
    'due_amount' => 0,
    'product_id' => [1],
    'batch_id' => [23],
    'quantity' => [2],
    'rate' => [50],
    'ptr' => [40],
    'gst_rate' => [18],
    'line_total' => [118],
    'allocation_plan' => []
];

include 'createSalesInvoice.php';
