<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate session
session_start();
$_SESSION['userId'] = 1;

include 'constant/connect.php';
require_once 'php_action/purchase_invoice_action.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PURCHASE INVOICE COMPLETE WORKFLOW TEST                       ║\n";
echo "║  Testing: Database Tables → Validations → Calculations → Flow  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Verify database schema
echo "TEST 1: Database Schema Verification\n";
echo "─────────────────────────────────────\n";
$tables = ['purchase_invoices', 'purchase_invoice_items', 'product', 'suppliers'];
foreach ($tables as $table) {
    $check = $connect->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "✓ Table exists: $table\n";
    } else {
        echo "✗ Table missing: $table\n";
    }
}

// Verify Phase 2 columns
echo "\nPhase 2 Columns:\n";
$newCols = ['supplier_invoice_no', 'supplier_invoice_date', 'place_of_supply', 'effective_rate'];
// look at both header and item tables for new columns
$existingInvCols = [];
$colsResult = $connect->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='purchase_invoices'");
while ($row = $colsResult->fetch_assoc()) {
    $existingInvCols[] = $row['COLUMN_NAME'];
}
$existingItemCols = [];
$colsResult = $connect->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='purchase_invoice_items'");
while ($row = $colsResult->fetch_assoc()) {
    $existingItemCols[] = $row['COLUMN_NAME'];
}
foreach ($newCols as $col) {
    if ($col === 'effective_rate') {
        if (in_array($col, $existingItemCols)) {
            echo "  ✓ $col (items table)\n";
        } else {
            echo "  ✗ $col missing in items table\n";
        }
    } else {
        if (in_array($col, $existingInvCols)) {
            echo "  ✓ $col (invoices table)\n";
        } else {
            echo "  ✗ $col missing in invoices table\n";
        }
    }
}

// Test 2: Test data preparation
echo "\n\nTEST 2: Test Data Preparation\n";
echo "────────────────────────────────\n";

// Get first supplier
$suppResult = $connect->query("SELECT supplier_id, state FROM suppliers WHERE supplier_status='Active' LIMIT 1");
if ($suppResult && $suppResult->num_rows > 0) {
    $supplier = $suppResult->fetch_assoc();
    $supplier_id = $supplier['supplier_id'];
    echo "✓ Using supplier ID: $supplier_id (State: {$supplier['state']})\n";
} else {
    echo "✗ No active suppliers found!\n";
    exit;
}

// Get first product
$prodResult = $connect->query("SELECT product_id, product_name, gst_rate FROM product WHERE status=1 LIMIT 1");
if ($prodResult && $prodResult->num_rows > 0) {
    $product = $prodResult->fetch_assoc();
    $product_id = $product['product_id'];
    echo "✓ Using product ID: $product_id ({$product['product_name']}, GST: {$product['gst_rate']}%)\n";
} else {
    echo "✗ No active products found!\n";
    exit;
}

// Test 3: Invoice creation
echo "\n\nTEST 3: Invoice Creation Workflow\n";
echo "──────────────────────────────────\n";

$testInvoiceNo = 'WORKFLOW-TEST-' . date('YmdHis');
$testSupplierInvNo = 'SUP-TEST-' . date('YmdHis');

$testData = [
    'supplier_id' => $supplier_id,
    'invoice_no' => $testInvoiceNo,
    'supplier_invoice_no' => $testSupplierInvNo,
    'supplier_invoice_date' => date('Y-m-d'),
    'invoice_date' => date('Y-m-d'),
    'po_reference' => 'WF-TEST-001',
    'place_of_supply' => $supplier['state'],
    'gst_type' => strtolower($supplier['state']) === 'gujarat' ? 'intrastate' : 'interstate',
    'payment_terms' => 'Net 30',
    'status' => 'Draft',
    'notes' => 'Workflow test invoice',
    'freight' => 50,
    'round_off' => 0,
    'paid_amount' => 0,
    'payment_mode' => 'Credit'
];

$testItems = [
    [
        'product_id' => $product_id,
        'product_name' => $product['product_name'],
        'hsn_code' => '30041090',
        'batch_no' => 'WF-BATCH-' . substr(md5(time()), 0, 8),
        'manufacture_date' => date('Y-m-d', strtotime('-3 months')),
        'expiry_date' => date('Y-m-d', strtotime('+12 months')),
        'qty' => 100,
        'free_qty' => 10,
        'unit_cost' => 50.00,
        'mrp' => 65.00,
        'discount_percent' => 5,
        'tax_rate' => $product['gst_rate']
    ]
];

try {
    echo "1. Creating DRAFT invoice...\n";
    $result = PurchaseInvoiceAction::createInvoice($testData, $testItems);
    
    if ($result['success']) {
        $draft_invoice_id = $result['invoice_id'];
        echo "   ✓ Draft invoice created: ID=$draft_invoice_id\n";
        
        // Verify header
        echo "2. Verifying invoice header...\n";
        $headerCheck = $connect->query("SELECT * FROM purchase_invoices WHERE id=$draft_invoice_id");
        if ($headerCheck && $headerCheck->num_rows > 0) {
            $headerData = $headerCheck->fetch_assoc();
            echo "   ✓ Invoice# {$headerData['invoice_no']}\n";
            echo "   ✓ Supplier Invoice# {$headerData['supplier_invoice_no']}\n";
            echo "   ✓ Status: {$headerData['status']}\n";
            echo "   ✓ Subtotal: ₹{$headerData['subtotal']}\n";
            echo "   ✓ Grand Total: ₹{$headerData['grand_total']}\n";
        }
        
        // Verify items
        echo "3. Verifying invoice items...\n";
        $itemsCheck = $connect->query("SELECT * FROM purchase_invoice_items WHERE invoice_id=$draft_invoice_id");
        if ($itemsCheck && $itemsCheck->num_rows > 0) {
            $itemCount = $itemsCheck->num_rows;
            echo "   ✓ Found $itemCount line item(s)\n";
            while ($item = $itemsCheck->fetch_assoc()) {
                echo "   ✓ Product: {$item['product_name']} (Qty: {$item['qty']}, Free: {$item['free_qty']})\n";
                echo "     - Unit Cost: ₹{$item['unit_cost']}\n";
                echo "     - Line Total: ₹{$item['line_total']}\n";
                echo "     - Effective Rate: ₹{$item['effective_rate']}\n";
                
                // Verify effective rate calculation
                $expected_effective = ($item['qty'] * $item['unit_cost']) / ($item['qty'] + $item['free_qty']);
                $actual_effective = floatval($item['effective_rate']);
                if (abs($expected_effective - $actual_effective) < 0.01) {
                    echo "   ✓ Effective rate correct!\n";
                } else {
                    echo "   ✗ Effective rate mismatch!\n";
                }
            }
        } else {
            echo "   ✗ No items found!\n";
        }
        
        // Test Draft - should NOT create stock
        echo "4. Verifying DRAFT does NOT create stock batches...\n";
        $stockCheck = $connect->query("SELECT * FROM stock_batches WHERE invoice_id=$draft_invoice_id");
        if ($stockCheck && $stockCheck->num_rows === 0) {
            echo "   ✓ No stock batches created for DRAFT (correct!)\n";
        } else {
            echo "   ✗ Stock batches found for DRAFT (should be empty!)\n";
        }

        // extra: approve the draft via helper and check stock
        echo "\n4a. Approving DRAFT invoice using helper...\n";
        if (PurchaseInvoiceAction::approveInvoice($draft_invoice_id, $_SESSION['userId'])) {
            echo "   ✓ Helper approved invoice\n";
            $st = $connect->query("SELECT status FROM purchase_invoices WHERE id=$draft_invoice_id")->fetch_assoc()['status'];
            echo "   ✓ New status: $st\n";
            $stockCheck2 = $connect->query("SELECT * FROM stock_batches WHERE invoice_id=$draft_invoice_id");
            if ($stockCheck2 && $stockCheck2->num_rows > 0) {
                echo "   ✓ Stock batch created by helper approval\n";
            } else {
                echo "   ✗ No stock batch after helper approval\n";
            }
        } else {
            echo "   ✗ Helper failed to approve draft\n";
        }
        
        // Test 4: Create APPROVED invoice
        echo "\n5. Creating APPROVED invoice...\n";
        $testData['status'] = 'Approved';
        $testData['invoice_no'] = 'WORKFLOW-APPR-' . date('YmdHis');
        $testData['supplier_invoice_no'] = 'SUP-APPR-' . date('YmdHis');
        
        $result2 = PurchaseInvoiceAction::createInvoice($testData, $testItems);
        if ($result2['success']) {
            $approved_invoice_id = $result2['invoice_id'];
            echo "   ✓ Approved invoice created: ID=$approved_invoice_id\n";
            
            // Verify APPROVED creates/updates stock
            echo "6. Verifying APPROVED affects stock batches...\n";
            $batchNo = $testItems[0]['batch_no'];
            $stockCheck = $connect->query("SELECT * FROM stock_batches WHERE batch_no='$batchNo'");
            if ($stockCheck && $stockCheck->num_rows > 0) {
                echo "   ✓ Stock batch exists for batch_no $batchNo\n";
                while ($batch = $stockCheck->fetch_assoc()) {
                    $total_qty_expected = ($testItems[0]['qty'] + $testItems[0]['free_qty']) * 2; // two invoices
                    echo "     - Batch: {$batch['batch_no']}\n";
                    echo "     - Quantity: {$batch['qty']} (expected $total_qty_expected)\n";
                    if (abs($batch['qty'] - $total_qty_expected) < 0.001) {
                        echo "     ✓ Quantity correctly accumulated across invoices\n";
                    } else {
                        echo "     ✗ Quantity mismatch\n";
                    }
                }
            } else {
                echo "   ✗ No stock batch found for batch_no $batchNo\n";
            }
        } else {
            echo "   ✗ Approved invoice failed: {$result2['error']}\n";
        }
        
        // Test 5: Duplicate prevention
        echo "\n7. Testing duplicate supplier invoice prevention...\n";
        $testData['invoice_no'] = 'WORKFLOW-DUP-' . date('YmdHis');
        // Use same supplier_invoice_no - should fail
        $dupResult = PurchaseInvoiceAction::createInvoice($testData, $testItems);
        if (!$dupResult['success'] && strpos($dupResult['error'], 'already exists') !== false) {
            echo "   ✓ Duplicate prevention working!\n";
            echo "     Error: {$dupResult['error']}\n";
        } else {
            echo "   ✗ Duplicate prevention not working!\n";
        }
        
    } else {
        echo "   ✗ Invoice creation failed: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Exception: {$e->getMessage()}\n";
}

echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  WORKFLOW TEST COMPLETE                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
?>
