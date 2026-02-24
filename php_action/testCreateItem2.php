<?php
// CLI test simulating a two‑batch allocation plan
$_SERVER['REQUEST_METHOD']='POST';

$plan = [[
    'batch_id'=>23,'batch_number'=>'PAR-202602-74882','allocated_quantity'=>96,'available_quantity'=>96,'expiry_date'=>'2026-05-23','days_to_expiry'=>88,'expiry_status'=>'ok','mrp'=>18,'purchase_rate'=>12
],[
    'batch_id'=>24,'batch_number'=>'PAR-202602-22242','allocated_quantity'=>54,'available_quantity'=>150,'expiry_date'=>'2026-08-23','days_to_expiry'=>180,'expiry_status'=>'ok','mrp'=>21,'purchase_rate'=>14
]];

$_POST = [
    'client_id' => 1,
    'invoice_number' => 'TEST-CLI-MULTI-' . time(),
    'invoice_date' => date('Y-m-d'),
    'subtotal' => 150,
    'discount_amount' => 0,
    'discount_percent' => 0,
    'gst_amount' => 27,
    'grand_total' => 177,
    'paid_amount' => 0,
    'due_amount' => 177,
    'product_id' => [1,1],
    'batch_id' => [23,24],
    'quantity' => [96,54],
    'rate' => [18,21],
    'ptr' => [12,14],
    'gst_rate' => [18,18],
    'line_total' => [113.28,63.72],
    'allocation_plan' => [json_encode($plan),'']
];

include 'createSalesInvoice.php';
