<?php
// Quick tester for createSalesInvoice.php via CLI.
$_SERVER['REQUEST_METHOD']='POST';
$_POST = [
    'client_id' => 1,
    'invoice_number' => 'TEST-CLI-' . time(),
    'invoice_date' => date('Y-m-d'),
    'subtotal' => 0,
    'discount_amount' => 0,
    'discount_percent' => 0,
    'gst_amount' => 0,
    'grand_total' => 0,
    'paid_amount' => 0,
    'due_amount' => 0
];

include 'createSalesInvoice.php';
