<?php
// Simple CLI tester for invoice actions
require_once __DIR__ . '/php_action/core.php';

function runAction($action, $invoiceId) {
    $_POST['action'] = $action;
    $_POST['invoice_id'] = $invoiceId;
    ob_start();
    include __DIR__ . '/php_action/po_actions.php';
    $output = ob_get_clean();
    return $output;
}

// list a couple of invoices
$result = $connect->query("SELECT id, status FROM purchase_invoices LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "Invoice {$row['id']} status={$row['status']}\n";
}

// try approving first invoice if not approved
if ($result->num_rows > 0) {
    $result->data_seek(0);
    $first = $result->fetch_assoc();
    echo "Attempting approve action for invoice {$first['id']}...\n";
    echo runAction('approve_invoice', $first['id']) . "\n";
    echo "Attempting delete action for invoice {$first['id']}...\n";
    echo runAction('delete_invoice', $first['id']) . "\n";
}
