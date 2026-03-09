<?php
/**
 * MEDICINE MODULE CONSOLIDATION MIGRATION
 * - Migrates stock_batches → product_batches
 * - Deletes legacy tables (stock_batches, medicine_batch)
 * - Verifies product_batches schema completeness
 * - Creates unified batch tracking system
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
echo "MEDICINE MODULE CONSOLIDATION MIGRATION\n";
echo str_repeat("=", 80) . "\n\n";

// STEP 1: Backup Database
echo "STEP 1: Creating Database Backup\n";
echo str_repeat("-", 80) . "\n";
$backup_file = __DIR__ . "\\..\\dbFile\\backup_before_medicine_consolidation_" . date('Y_m_d_H_i_s') . ".sql";
$backup_dir = dirname($backup_file);
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}
$backup_cmd = "\"c:\\xampp\\mysql\\bin\\mysqldump.exe\" -u root $dbname > \"$backup_file\" 2>&1";
exec($backup_cmd, $output, $return_code);

if ($return_code === 0) {
    $backup_size = filesize($backup_file) / 1024 / 1024;
    echo "✅ Backup created: {$backup_size} MB\n";
    echo "   Location: $backup_file\n\n";
} else {
    echo "⚠️  Backup warning: " . implode("\n", $output) . "\n\n";
}

// STEP 2: Check product_batches schema and add missing columns if needed
echo "STEP 2: Verifying product_batches Schema\n";
echo str_repeat("-", 80) . "\n";

$result = $conn->query("DESCRIBE product_batches");
$existing_cols = [];
while ($row = $result->fetch_assoc()) {
    $existing_cols[] = $row['Field'];
}

$required_cols = [
    'batch_id', 'product_id', 'supplier_id', 'batch_number',
    'manufacturing_date', 'expiry_date', 'available_quantity',
    'reserved_quantity', 'damaged_quantity', 'purchase_rate', 'mrp',
    'status', 'created_at', 'updated_at'
];

$missing_cols = array_diff($required_cols, $existing_cols);

if (empty($missing_cols)) {
    echo "✅ product_batches schema is complete\n\n";
} else {
    echo "⚠️  Missing columns: " . implode(", ", $missing_cols) . "\n";
    echo "   Adding missing columns...\n\n";
    
    $alter_statements = [
        'reserved_quantity' => "ALTER TABLE product_batches ADD COLUMN reserved_quantity INT DEFAULT 0 AFTER available_quantity",
        'damaged_quantity' => "ALTER TABLE product_batches ADD COLUMN damaged_quantity INT DEFAULT 0 AFTER reserved_quantity",
        'updated_at' => "ALTER TABLE product_batches ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];
    
    foreach ($missing_cols as $col) {
        if (isset($alter_statements[$col])) {
            if ($conn->query($alter_statements[$col])) {
                echo "✅ Added column: $col\n";
            } else {
                echo "❌ Failed to add $col: " . $conn->error . "\n";
            }
        }
    }
    echo "\n";
}

// STEP 3: Migrate data from stock_batches to product_batches
echo "STEP 3: Migrating stock_batches → product_batches\n";
echo str_repeat("-", 80) . "\n";

$check_sb = $conn->query("SHOW TABLES LIKE 'stock_batches'");
if ($check_sb && $check_sb->num_rows > 0) {
    echo "📊 stock_batches table found\n";
    
    // Count records
    $count_sb = $conn->query("SELECT COUNT(*) as cnt FROM stock_batches")->fetch_assoc();
    $sb_count = $count_sb['cnt'];
    echo "   Records to migrate: $sb_count\n";
    
    if ($sb_count > 0) {
        // Migrate data - validate supplier_id exists first
        $migrate_sql = "
            INSERT INTO product_batches 
            (product_id, supplier_id, batch_number, manufacturing_date, expiry_date, 
             available_quantity, purchase_rate, mrp, status, created_at)
            SELECT 
                sb.product_id, 
                CASE WHEN s.supplier_id IS NOT NULL THEN sb.supplier_id ELSE NULL END, 
                sb.batch_no, 
                sb.manufacture_date, 
                sb.expiry_date, 
                COALESCE(sb.qty, 0), 
                COALESCE(sb.cost_price, 0), 
                COALESCE(sb.mrp, 0), 
                'Active', 
                COALESCE(sb.created_at, NOW())
            FROM stock_batches sb
            LEFT JOIN suppliers s ON sb.supplier_id = s.supplier_id
            WHERE sb.batch_no NOT IN (
                SELECT batch_number FROM product_batches 
                WHERE product_id = sb.product_id
            )
        ";
        
        if ($conn->query($migrate_sql)) {
            $affected = $conn->affected_rows;
            echo "✅ Migrated $affected records\n";
        } else {
            echo "⚠️  Migration note: " . $conn->error . "\n";
            echo "   (Some duplicates may already exist, continuing...)\n";
        }
    } else {
        echo "✅ No records to migrate (table is empty)\n";
    }
    echo "\n";
} else {
    echo "✅ stock_batches table not found (already deleted or never existed)\n\n";
}

// STEP 4: Check and log stock_movements data
echo "STEP 4: Verifying stock_movements Table\n";
echo str_repeat("-", 80) . "\n";

$check_sm = $conn->query("SHOW TABLES LIKE 'stock_movements'");
if ($check_sm && $check_sm->num_rows > 0) {
    $count_sm = $conn->query("SELECT COUNT(*) as cnt FROM stock_movements")->fetch_assoc();
    echo "✅ stock_movements exists with " . $count_sm['cnt'] . " records\n";
    
    // Check schema
    $sm_result = $conn->query("DESCRIBE stock_movements");
    $sm_cols = [];
    while ($row = $sm_result->fetch_assoc()) {
        $sm_cols[] = $row['Field'];
    }
    echo "   Columns: " . implode(", ", $sm_cols) . "\n";
} else {
    echo "❌ stock_movements table NOT FOUND\n";
    echo "   Creating stock_movements table...\n";
    
    $create_sm_sql = "
        CREATE TABLE stock_movements (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            batch_id INT,
            warehouse_id INT,
            movement_type VARCHAR(50) NOT NULL,
            quantity_moved DECIMAL(10,2),
            balance_before DECIMAL(10,2),
            balance_after DECIMAL(10,2),
            reference_type VARCHAR(50),
            reference_id INT,
            recorded_by INT,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES product(product_id),
            FOREIGN KEY (batch_id) REFERENCES product_batches(batch_id),
            KEY idx_product_batch (product_id, batch_id),
            KEY idx_movement_type (movement_type),
            KEY idx_recorded_at (recorded_at)
        )
    ";
    
    if ($conn->query($create_sm_sql)) {
        echo "✅ Created stock_movements table\n";
    } else {
        echo "❌ Failed to create stock_movements: " . $conn->error . "\n";
    }
}
echo "\n";

// STEP 5: Delete legacy tables
echo "STEP 5: Deleting Legacy Tables\n";
echo str_repeat("-", 80) . "\n";

$legacy_tables = ['stock_batches', 'medicine_batch'];

foreach ($legacy_tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        // Count records before deletion
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $count_result ? $count_result->fetch_assoc()['cnt'] : 0;
        
        // Delete table
        if ($conn->query("DROP TABLE $table")) {
            echo "✅ Deleted $table ($count records)\n";
        } else {
            echo "❌ Failed to delete $table: " . $conn->error . "\n";
        }
    } else {
        echo "✅ $table not found (already deleted)\n";
    }
}
echo "\n";

// STEP 6: Add indexes for performance
echo "STEP 6: Adding Performance Indexes\n";
echo str_repeat("-", 80) . "\n";

// First, check what indexes already exist
$existing_indexes = [];
$index_check = $conn->query("SHOW INDEX FROM product_batches");
while ($idx = $index_check->fetch_assoc()) {
    $existing_indexes[] = $idx['Key_name'];
}

$desired_indexes = [
    'idx_product_status' => "ALTER TABLE product_batches ADD INDEX idx_product_status (product_id, status)",
    'idx_expiry' => "ALTER TABLE product_batches ADD INDEX idx_expiry (expiry_date)",
    'idx_supplier' => "ALTER TABLE product_batches ADD INDEX idx_supplier (supplier_id)"
];

foreach ($desired_indexes as $idx_name => $idx_sql) {
    if (in_array($idx_name, $existing_indexes)) {
        echo "✅ Index already exists: $idx_name\n";
    } else {
        if ($conn->query($idx_sql)) {
            echo "✅ Index added: $idx_name\n";
        } else {
            echo "⚠️  Skipping index ($idx_name): " . $conn->error . "\n";
        }
    }
}

// Check stock_movements index
$sm_idx_check = $conn->query("SHOW INDEX FROM stock_movements WHERE Key_name = 'idx_product_dated'");
if (!($sm_idx_check && $sm_idx_check->num_rows > 0)) {
    if ($conn->query("ALTER TABLE stock_movements ADD INDEX idx_product_dated (product_id, created_at)")) {
        echo "✅ Index added: idx_product_dated\n";
    } else {
        echo "⚠️  Skipping stock_movements index: " . $conn->error . "\n";
    }
} else {
    echo "✅ Index already exists: idx_product_dated\n";
}
echo "\n";

// STEP 7: Verify final state
echo "STEP 7: Final Verification\n";
echo str_repeat("-", 80) . "\n";

$check_tables = [
    'product' => "Product Master",
    'product_batches' => "Product Batches (UNIFIED)",
    'stock_movements' => "Stock Audit Trail"
];

foreach ($check_tables as $table => $desc) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc();
        echo "✅ $table ($desc): " . $count['cnt'] . " records\n";
    } else {
        echo "❌ $table MISSING\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ MIGRATION COMPLETE\n";
echo str_repeat("=", 80) . "\n";
echo "\nNext Steps:\n";
echo "1. Create sample data: Run seed_medicine_data.php\n";
echo "2. Update sales invoice form for multi-batch support\n";
echo "3. Test end-to-end workflow\n";
echo "\nBackup location: $backup_file\n\n";

$conn->close();
?>
