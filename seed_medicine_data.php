<?php
/**
 * SEED MEDICINE MODULE WITH SAMPLE DATA
 * Creates realistic product batches for testing multi-batch scenarios
 */

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection Failed: " . $conn->connect_error);
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SEEDING MEDICINE MODULE WITH SAMPLE DATA\n";
echo str_repeat("=", 80) . "\n\n";

// Get existing products or create sample products
echo "STEP 1: Preparing Products\n";
echo str_repeat("-", 80) . "\n";

$products_sql = "SELECT COUNT(*) as cnt FROM product";
$product_count = $conn->query($products_sql)->fetch_assoc()['cnt'];
echo "Current products: $product_count\n";

if ($product_count == 0) {
    echo "Creating sample products...\n";
    
    $sample_products = [
        ['Paracetamol 500mg', 'Tablet', 'Strip', 10, '5891234567890', 5],
        ['Ibuprofen 600mg', 'Tablet', 'Strip', 10, '5891234567891', 5],
        ['Amoxicillin 500mg', 'Capsule', 'Strip', 10, '5891234567892', 5],
        ['Azithromycin 250mg', 'Tablet', 'Strip', 10, '5891234567893', 5],
        ['Metformin 500mg', 'Tablet', 'Strip', 10, '5891234567894', 5],
        ['Aspirin 75mg', 'Tablet', 'Strip', 10, '5891234567895', 5],
        ['Cetirizine 10mg', 'Tablet', 'Strip', 10, '5891234567896', 5],
        ['Omeprazole 20mg', 'Capsule', 'Strip', 10, '5891234567897', 5]
    ];
    
    $insert_stmt = $conn->prepare("
        INSERT INTO product 
        (product_name, product_type, unit_type, pack_size, hsn_code, gst_rate, status)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($sample_products as $prod) {
        $insert_stmt->bind_param(
            'sssisii',
            $prod[0], $prod[1], $prod[2], $prod[3], $prod[4], $prod[5]
        );
        $insert_stmt->execute();
        echo "  ✅ Created: " . $prod[0] . "\n";
    }
    $insert_stmt->close();
} else {
    echo "✅ Using existing products\n";
}
echo "\n";

// Get supplier IDs
echo "STEP 2: Preparing Suppliers\n";
echo str_repeat("-", 80) . "\n";

$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers LIMIT 5")->fetch_all(MYSQLI_ASSOC);
if (empty($suppliers)) {
    echo "⚠️  No suppliers found. Creating sample suppliers...\n";
    $supplier_stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, city, state, pincode, gstin, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $suppliers_data = [
        ['PharmaCorp Ltd', 'Raj Kumar', '9876543210', 'raj@pharmacorp.com', '123 Main St', 'Delhi', 'Delhi', '110001', '07AACPT1234A1Z5'],
        ['MediSupply Inc', 'Priya Singh', '9876543211', 'priya@medisupply.com', '456 Park Ave', 'Mumbai', 'Maharashtra', '400001', '27AAACR5055K1Z0'],
        ['Generic Pharma', 'Vijay Patel', '9876543212', 'vijay@genericpharma.com', '789 Oak Rd', 'Bangalore', 'Karnataka', '560001', '29AABCU9603R1Z5']
    ];
    
    foreach ($suppliers_data as $supp) {
        $supplier_stmt->bind_param('sssssssss', ...$supp);
        $supplier_stmt->execute();
    }
    $supplier_stmt->close();
    $suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}

echo "Available suppliers: " . count($suppliers) . "\n";
foreach ($suppliers as $supp) {
    echo "  - " . $supp['supplier_name'] . " (ID: " . $supp['supplier_id'] . ")\n";
}
echo "\n";

// STEP 3: Create sample batches with varying quantities
echo "STEP 3: Creating Sample Product Batches\n";
echo str_repeat("-", 80) . "\n";

$products = $conn->query("SELECT product_id, product_name FROM product WHERE status = 1")->fetch_all(MYSQLI_ASSOC);

$today = new DateTime();
$batch_count = 0;

// For each product, create 3-5 batches with different quantities and expiry dates
foreach ($products as $product) {
    $product_id = $product['product_id'];
    $product_name = $product['product_name'];
    
    // Create 4 batches per product
    for ($i = 1; $i <= 4; $i++) {
        // Vary quantities: 100, 150, 50, 250 to create edge cases
        $quantities = [100, 150, 50, 250];
        $qty = $quantities[$i - 1];
        
        // Vary expiry dates from 3 months to 18 months in future
        $months_offset = [3, 6, 9, 12, 18];
        $expiry = clone $today;
        $expiry->add(new DateInterval('P' . $months_offset[$i - 1] . 'M'));
        
        // Manufacturing date: 12 months before expiry
        $mfg_date = clone $expiry;
        $mfg_date->sub(new DateInterval('P12M'));
        
        // Vary costs and MRP
        $base_cost = 10 + ($i * 2);
        $mrp = $base_cost * 1.5;
        
        // Select random supplier
        $supplier = $suppliers[array_rand($suppliers)];
        
        // Generate unique batch number with timestamp and random suffix
        $random_suffix = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        $batch_number = strtoupper(substr($product_name, 0, 3)) . "-" . $today->format('Ym') . "-" . $random_suffix;
        
        $batch_stmt = $conn->prepare("
            INSERT INTO product_batches 
            (product_id, supplier_id, batch_number, manufacturing_date, expiry_date,
             available_quantity, reserved_quantity, damaged_quantity, purchase_rate, mrp, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$batch_stmt) {
            echo "❌ Prepare failed: " . $conn->error . "\n";
            continue;
        }
        
        $supplier_id = $supplier['supplier_id'];
        $reserved = 0;
        $damaged = 0;
        $status = 'Active';
        $batch_stmt->bind_param(
            'iisssiiidds',
            $product_id,
            $supplier_id,
            $batch_number,
            $mfg_date->format('Y-m-d'),
            $expiry->format('Y-m-d'),
            $qty,
            $reserved,
            $damaged,
            $base_cost,
            $mrp,
            $status
        );
        
        if ($batch_stmt->execute()) {
            $batch_count++;
            $batch_id = $batch_stmt->insert_id;
            
            // Log initial stock movement using the actual columns in stock_movements
            $movement_stmt = $conn->prepare("
                INSERT INTO stock_movements 
                (product_id, batch_id, movement_type, quantity, reference_type, recorded_by, reason)
                VALUES (?, ?, 'Purchase', ?, 'seed_data', 1, 'Initial batch creation')
            ");
            
            if ($movement_stmt) {
                $movement_stmt->bind_param('iid', $product_id, $batch_id, $qty);
                $movement_stmt->execute();
                $movement_stmt->close();
            }
        } else {
            echo "❌ Batch insert failed: " . $batch_stmt->error . "\n";
        }
        $batch_stmt->close();
    }
    
    echo "✅ Created 4 batches for: $product_name\n";
}

echo "Total batches created: $batch_count\n\n";

// STEP 4: Create sample purchase invoices with stock
echo "STEP 4: Creating Sample Purchase Invoices\n";
echo str_repeat("-", 80) . "\n";

$purchase_invoices = $conn->query("SELECT COUNT(*) as cnt FROM purchase_invoices")->fetch_assoc()['cnt'];
echo "Existing purchase invoices: $purchase_invoices\n";

if ($purchase_invoices == 0) {
    $supplier = $suppliers[0];
    $supplier_id = $supplier['supplier_id'];
    
    $inv_stmt = $conn->prepare("
        INSERT INTO purchase_invoices 
        (supplier_id, invoice_no, supplier_invoice_no, invoice_date, supplier_invoice_date, gst_type, 
         subtotal, total_cgst, total_sgst, total_igst, grand_total, status, created_by)
        VALUES (?, ?, ?, NOW(), NOW(), 'intrastate', 5000, 450, 450, 0, 5900, 'Approved', 1)
    ");
    
    $invoice_no = 'PI-' . date('Ym') . '-001';
    $supplier_inv_no = 'SUPP-2026-001';
    
    $inv_stmt->bind_param('iss', $supplier_id, $invoice_no, $supplier_inv_no);
    $inv_stmt->execute();
    $invoice_id = $inv_stmt->insert_id;
    $inv_stmt->close();
    
    echo "✅ Created sample purchase invoice (ID: $invoice_id)\n";
} else {
    echo "✅ Using existing purchase invoices\n";
}
echo "\n";

// STEP 5: Display batch summary
echo "STEP 5: Batch Summary\n";
echo str_repeat("-", 80) . "\n";

$batch_summary = $conn->query("
    SELECT 
        p.product_name,
        COUNT(pb.batch_id) as batch_count,
        SUM(pb.available_quantity) as total_qty,
        MIN(pb.expiry_date) as earliest_expiry,
        MAX(pb.expiry_date) as latest_expiry
    FROM product p
    LEFT JOIN product_batches pb ON p.product_id = pb.product_id
    GROUP BY p.product_id, p.product_name
    ORDER BY p.product_name
")->fetch_all(MYSQLI_ASSOC);

foreach ($batch_summary as $summary) {
    echo sprintf(
        "%-30s | Batches: %2d | Total Qty: %5d | Expiry: %s to %s\n",
        substr($summary['product_name'], 0, 30),
        $summary['batch_count'],
        $summary['total_qty'],
        $summary['earliest_expiry'],
        $summary['latest_expiry']
    );
}
echo "\n";

// STEP 6: Display ready-to-test scenarios
echo "STEP 6: Ready-to-Test Scenarios\n";
echo str_repeat("-", 80) . "\n";

$test_batches = $conn->query("
    SELECT 
        p.product_id,
        p.product_name,
        pb.batch_id,
        pb.batch_number,
        pb.available_quantity,
        pb.expiry_date,
        s.supplier_name
    FROM product_batches pb
    JOIN product p ON pb.product_id = p.product_id
    LEFT JOIN suppliers s ON pb.supplier_id = s.supplier_id
    ORDER BY p.product_id, pb.batch_id
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

echo "Sample Batches Available for Testing:\n";
$scenario_num = 1;
foreach ($test_batches as $batch) {
    echo sprintf(
        "%2d. %-30s Batch: %-15s | Qty: %3d | Expiry: %s\n",
        $scenario_num++,
        substr($batch['product_name'], 0, 30),
        $batch['batch_number'],
        $batch['available_quantity'],
        $batch['expiry_date']
    );
}

echo "\n📋 Edge Cases Scenario:\n";
echo "   - Product with low qty batch (50) + medium qty batch (100) + high qty batch (250)\n";
echo "   - Paracetamol has batches expiring: 3mo, 6mo, 9mo, 12mo\n";
echo "   - Ibuprofen has varied quantities: 50, 100, 150, 250\n";
echo "   ✅ Use these to test multi-batch quantity allocation\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ SAMPLE DATA CREATED SUCCESSFULLY\n";
echo str_repeat("=", 80) . "\n\n";

$conn->close();
?>
