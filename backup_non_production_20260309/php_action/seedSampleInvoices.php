<?php
/**
 * Simple installer script to add sample sales invoices if none exist.
 * Run once via browser or CLI: php php_action/seedSampleInvoices.php
 */

require '../constant/connect.php';

header('Content-Type: text/plain');

try {
    $row = $connect->query("SELECT COUNT(*) as cnt FROM sales_invoices")->fetch_assoc();
    if (!$row) {
        throw new Exception('Unable to query sales_invoices');
    }
    if ((int)$row['cnt'] > 0) {
        echo "Table already has invoices, nothing to do.\n";
        exit;
    }

    // pick a valid client_id
    $client = $connect->query("SELECT client_id FROM clients ORDER BY client_id LIMIT 1")->fetch_assoc();
    if (!$client) {
        throw new Exception('No clients found - please add a client first');
    }
    $clientId = (int)$client['client_id'];

    // prepare two sample invoices
    $invoices = [
        [
            'number' => 'SLS-2025-00001',
            'date' => date('Y-m-d'),
            'due' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 1000,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'gst_amount' => 180,
            'grand_total' => 1180,
            'paid_amount' => 1180,
            'due_amount' => 0,
            'payment_type' => 'Cash',
            'payment_method' => 'Cash',
            'payment_notes' => 'Sample paid cash',
            'payment_status' => 'PAID'
        ],
        [
            'number' => 'SLS-2025-00002',
            'date' => date('Y-m-d'),
            'due' => date('Y-m-d', strtotime('+15 days')),
            'subtotal' => 500,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'gst_amount' => 90,
            'grand_total' => 590,
            'paid_amount' => 0,
            'due_amount' => 590,
            'payment_type' => 'Credit',
            'payment_method' => null,
            'payment_notes' => 'Sample credit invoice',
            'payment_status' => 'UNPAID'
        ],
    ];

    $stmt = $connect->prepare("INSERT INTO sales_invoices
        (invoice_number, client_id, invoice_date, due_date, subtotal, discount_amount, discount_percent, gst_amount, grand_total,
         paid_amount, due_amount, payment_type, payment_method, payment_notes, payment_status, created_at, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)");
    foreach ($invoices as $inv) {
        $stmt->bind_param('sissddddddsssss',
            $inv['number'],
            $clientId,
            $inv['date'],
            $inv['due'],
            $inv['subtotal'],
            $inv['discount_amount'],
            $inv['discount_percent'],
            $inv['gst_amount'],
            $inv['grand_total'],
            $inv['paid_amount'],
            $inv['due_amount'],
            $inv['payment_type'],
            $inv['payment_method'],
            $inv['payment_notes'],
            $inv['payment_status']
        );
        if (!$stmt->execute()) {
            throw new Exception('Insert failed: ' . $stmt->error);
        }
    }

    echo "Inserted " . count($invoices) . " sample invoices.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
