<?php
/**
 * Phase 1 Migration Executor
 * Executes all 6 SQL migrations in order with verification
 */

// Database config
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "satyam_clinical_new";

// Connect to database
$conn = new mysqli($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection Failed: " . $conn->connect_error);
}

echo "✅ Connected to database: $dbname\n\n";

// Step 1: Backup database
echo "=" . str_repeat("=", 68) . "\n";
echo "PHASE 1: DATABASE MIGRATION\n";
echo "=" . str_repeat("=", 68) . "\n\n";

echo "Step 1: Creating Database Backup...\n";
$backup_file = __DIR__ . "\dbFile\backup_2026_02_17.sql";
$backup_cmd = "\"c:\\xampp\\mysql\\bin\\mysqldump.exe\" -u root $dbname > \"$backup_file\" 2>&1";
exec($backup_cmd, $output, $return_code);

if ($return_code === 0) {
    $backup_size = filesize($backup_file) / 1024 / 1024; // MB
    echo "✅ Backup created: $backup_size MB\n";
    echo "   Location: $backup_file\n\n";
} else {
    echo "⚠️  Backup skipped (MySQL may need to be started)\n";
    echo "   You can backup manually using: mysqldump -u root $dbname > backup.sql\n\n";
}

// Step 2: Execute migrations
$migrations = [
    '001_create_approval_logs.sql',
    '002_create_audit_logs.sql',
    '003_enhance_stock_movements.sql',
    '004_implement_credit_control.sql',
    '005_batch_recall_soft_deletes.sql',
    '006_status_workflow.sql'
];

echo "Step 2: Executing SQL Migrations\n";
echo str_repeat("-", 70) . "\n";

$migration_dir = __DIR__ . "\dbFile\migrations";
$errors = [];

foreach ($migrations as $index => $filename) {
    $migration_file = $migration_dir . "\\" . $filename;
    
    if (!file_exists($migration_file)) {
        echo "❌ Migration $index: $filename - FILE NOT FOUND\n";
        $errors[] = "Missing: $filename";
        continue;
    }

    echo "⏳ Migration $index: $filename... ";
    
    // Read and execute SQL file
    $sql_content = file_get_contents($migration_file);
    
    // Remove comments
    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
    
    // Split by ; and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($s) { return !empty($s) && strlen($s) > 3; }
    );
    $success = true;
    $statement_count = 0;

    foreach ($statements as $statement) {
        if (!$conn->query($statement)) {
            $errors[] = "Migration $filename | Statement: " . substr($statement, 0, 60) . "... | Error: " . $conn->error;
            $success = false;
            // Continue to next statement instead of breaking
            // break;
        }
        $statement_count++;
    }

    if ($success) {
        echo "✅ ($statement_count SQL statements)\n";
    } else {
        echo "❌ ERROR\n";
    }
}

echo str_repeat("-", 70) . "\n\n";

// Step 3: Verify tables created
echo "Step 3: Verifying Tables Created\n";
echo str_repeat("-", 70) . "\n";

$required_tables = [
    'approval_logs',
    'audit_logs',
    'customer_credit_log',
    'customer_payments',
    'supplier_payments',
    'batch_recalls',
    'batch_sales_map',
    'inventory_adjustments',
    'invoice_payments'
];

$tables_status = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ $table\n";
        $tables_status[$table] = true;
    } else {
        echo "❌ $table - NOT FOUND\n";
        $tables_status[$table] = false;
    }
}

echo str_repeat("-", 70) . "\n\n";

// Step 4: Verify enhanced columns in existing tables
echo "Step 4: Verifying Enhanced Columns in Existing Tables\n";
echo str_repeat("-", 70) . "\n";

$enhanced_checks = [
    'customers' => ['credit_limit', 'outstanding_balance', 'credit_status'],
    'stock_movements' => ['warehouse_id', 'balance_after', 'reference_type'],
    'purchase_orders' => ['status', 'submitted_at', 'approved_by'],
    'goods_received' => ['status', 'quality_check_status'],
];

foreach ($enhanced_checks as $table => $columns) {
    $table_exists = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$table_exists || $table_exists->num_rows === 0) {
        echo "⚠️  $table - Does not exist (may be from existing system)\n";
        continue;
    }

    foreach ($columns as $column) {
        $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($result && $result->num_rows > 0) {
            echo "✅ $table.$column\n";
        } else {
            echo "❌ $table.$column - NOT FOUND\n";
        }
    }
}

echo str_repeat("-", 70) . "\n\n";

// Summary
echo "=" . str_repeat("=", 68) . "\n";
echo "PHASE 1 SUMMARY\n";
echo "=" . str_repeat("=", 68) . "\n\n";

$all_tables_created = array_reduce($tables_status, function($carry, $item) {
    return $carry && $item;
}, true);

if ($all_tables_created) {
    echo "✅ DATABASE MIGRATION COMPLETE\n";
    echo "✅ All required tables created successfully\n\n";
    echo "Next Steps:\n";
    echo "1. Review PRODUCTION_IMPLEMENTATION.md Phase 2\n";
    echo "2. Create directories: libraries/Services, libraries/Middleware\n";
    echo "3. Copy service classes to libraries/Services/\n";
    echo "4. Run Phase 2 implementation\n";
} else {
    echo "⚠️  SOME TABLES MISSING:\n";
    foreach ($tables_status as $table => $status) {
        if (!$status) {
            echo "   - $table\n";
        }
    }
    echo "\nPlease check migrations for errors above.\n";
}

if (!empty($errors)) {
    echo "\n⚠️  ERRORS ENCOUNTERED:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
$conn->close();
?>
