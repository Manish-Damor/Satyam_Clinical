<?php
// CLI test for purchase invoice creation
$_SESSION['userId'] = 1; // ensure session user

// sample items
$items = [[
    'product_id'=>1,
    'product_name'=>'Test Product',
    'hsn_code'=>'1234',
    'batch_no'=>'BATCH-CLI',
    'manufacture_date'=>date('Y-m-d', strtotime('-30 days')),
    'expiry_date'=>date('Y-m-d', strtotime('+365 days')),
    'qty'=>10,
    'free_qty'=>0,
    'unit_cost'=>50,
    'mrp'=>60,
    'discount_percent'=>0,
    'tax_rate'=>18,
]];

$data = [
    'supplier_id'=>10012,
    'invoice_no'=>'CLI-PUR-'.time(),
    'supplier_invoice_no'=>'SUP-CLI-'.time(),
    'supplier_invoice_date'=>date('Y-m-d'),
    'invoice_date'=>date('Y-m-d'),
    'po_reference'=>'PO123',
    'place_of_supply'=>'Gujarat',
    'gst_type'=>'intrastate',
    'freight'=>0,
    'round_off'=>0,
    'paid_amount'=>0,
    'payment_mode'=>'Credit',
    'payment_terms'=>'Net 30',
    'due_date'=>date('Y-m-d', strtotime('+30 days')),
    'status'=>'Approved',
    'notes'=>'CLI test'
];

$data['items'] = $items;

// manually call action
require_once 'core.php';
require_once 'purchase_invoice_action.php';
$result = PurchaseInvoiceAction::createInvoice($data, $data['items']);
echo json_encode($result, JSON_PRETTY_PRINT);
