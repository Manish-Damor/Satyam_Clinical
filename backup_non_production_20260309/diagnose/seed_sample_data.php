<?php
// Script to insert sample data across core tables for demo/testing.
// Run from CLI: php seed_sample_data.php

require_once __DIR__ . '/php_action/core.php';

function insertIfEmpty($connect, $table, $columns, $valuesList) {
    $countRes = $connect->query("SELECT COUNT(*) AS cnt FROM $table");
    $cnt = $countRes->fetch_assoc()['cnt'];
    if ($cnt > 0) {
        echo "[skip] $table already has data ($cnt rows)\n";
        return;
    }

    $cols = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO $table ($cols) VALUES ($placeholders)";
    $stmt = $connect->prepare($sql);
    foreach ($valuesList as $vals) {
        $types = str_repeat('s', count($vals));
        // fallback for numeric types
        foreach ($vals as $i => $val) {
            if (is_int($val)) $types[$i] = 'i';
            if (is_float($val)) $types[$i] = 'd';
        }
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
    }
    echo "Inserted " . count($valuesList) . " rows into $table\n";
    $stmt->close();
}

try {
    // brands
    insertIfEmpty($connect, 'brands', ['brand_name'], [
        ['Acme Pharma'], ['GoodHealth'], ['MediCorp']
    ]);

    // categories
    insertIfEmpty($connect, 'categories', ['categories_name'], [
        ['Analgesic'], ['Antibiotic'], ['Vitamin'], ['Supplement']
    ]);

    // suppliers
    insertIfEmpty($connect, 'suppliers', ['supplier_name','company_name','supplier_code','phone','email','address','city','state','gst_number','credit_days','supplier_status','is_verified'], [
        ['HealthSupplies','Health Supplies Ltd','SUP-001','1234567890','sales@healthsupplies.com','123 Main St','Ahmedabad','Gujarat','27AAAPL1234C1Z2',30,'Active',1],
        ['MedicPlus','MedicPlus Traders','SUP-002','0987654321','contact@medicplus.com','456 Market Rd','Surat','Gujarat','27BBBP1234D1Z5',45,'Active',1]
    ]);

    // products
    $brandRes = $connect->query("SELECT brand_id FROM brands ORDER BY brand_id");
    $brandIds = [];
    while($b=$brandRes->fetch_assoc()) $brandIds[] = $b['brand_id'];
    $catRes = $connect->query("SELECT categories_id FROM categories ORDER BY categories_id");
    $catIds = [];
    while($c=$catRes->fetch_assoc()) $catIds[] = $c['categories_id'];

    if (empty($brandIds) || empty($catIds)) {
        throw new Exception('Brands or categories missing');
    }

    insertIfEmpty($connect, 'product', ['product_name','content','pack_size','hsn_code','gst_rate','brand_id','categories_id','reorder_level','status'], [
        ['Paracetamol','500mg','10 tablets','3004',5,$brandIds[0],$catIds[0],50,1],
        ['Amoxicillin','250mg','20 capsules','3003',12,$brandIds[1],$catIds[1],30,1],
        ['Vitamin C','1000mg','15 tablets','3005',18,$brandIds[2] ?? $brandIds[0],$catIds[2] ?? $catIds[0],100,1]
    ]);

    // product_batches (stock)
    $prodRes = $connect->query("SELECT product_id FROM product WHERE status = 1");
    $prodIds = [];
    while($p=$prodRes->fetch_assoc()) $prodIds[] = $p['product_id'];
    $suppRes = $connect->query("SELECT supplier_id FROM suppliers LIMIT 1");
    $supplierId = $suppRes->fetch_assoc()['supplier_id'] ?? 0;

    if (!empty($prodIds) && $supplierId) {
        $batchValues = [];
        foreach ($prodIds as $pid) {
            $batchValues[] = [$pid, 'BATCH'.rand(100,999), date('Y-m-d', strtotime('+1 year')), 100, 0, 0, 50.00, 80.00, $supplierId, 'Active'];
        }
        insertIfEmpty($connect, 'product_batches', ['product_id','batch_number','expiry_date','available_quantity','reserved_quantity','damaged_quantity','purchase_rate','mrp','supplier_id','status'], $batchValues);
    }

    echo "\nSample data seeding complete.\n";
    echo "You can now visit management pages (brands, categories, suppliers, medicines, batches) to see records.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>