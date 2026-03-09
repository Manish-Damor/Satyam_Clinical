<?php
/**
 * Phase 2 Verification Script
 * Tests that all service classes load and initialize correctly
 */

// Database connection
require_once 'constant/connect.php';

echo "=" . str_repeat("=", 68) . "\n";
echo "PHASE 2: SERVICE LAYER VERIFICATION\n";
echo "=" . str_repeat("=", 68) . "\n\n";

// Test 1: Check directories
echo "Step 1: Checking Directory Structure\n";
echo str_repeat("-", 70) . "\n";

$required_dirs = [
    'libraries/Services' => 'Service classes',
    'libraries/Middleware' => 'Middleware classes',
    'libraries/Helpers' => 'Helper utilities',
    'config' => 'Configuration',
];

foreach ($required_dirs as $dir => $purpose) {
    if (is_dir($dir)) {
        echo "✅ $dir - $purpose\n";
    } else {
        echo "❌ $dir - MISSING\n";
    }
}

echo "\n";

// Test 2: Check service files
echo "Step 2: Checking Service Class Files\n";
echo str_repeat("-", 70) . "\n";

$service_files = [
    'libraries/Services/StockService.php' => 'Stock management engine',
    'libraries/Services/ApprovalEngine.php' => 'Approval workflow state machine',
    'libraries/Services/AuditLogger.php' => 'Audit trail logging',
    'libraries/Services/CreditControl.php' => 'Customer credit management',
    'libraries/Middleware/PermissionMiddleware.php' => 'Role-based access control',
    'libraries/Helpers/DatabaseHelper.php' => 'Database transaction wrapper',
];

$all_exist = true;
foreach ($service_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file) / 1024;
        echo "✅ $file (" . number_format($size, 1) . " KB) - $description\n";
    } else {
        echo "❌ $file - NOT FOUND\n";
        $all_exist = false;
    }
}

echo "\n";

// Test 3: Check config files
echo "Step 3: Checking Configuration Files\n";
echo str_repeat("-", 70) . "\n";

$config_files = [
    'config/services.php' => 'Service factory container',
    'config/bootstrap.php' => 'Service initialization bootstrap',
];

foreach ($config_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file) / 1024;
        echo "✅ $file (" . number_format($size, 1) . " KB) - $description\n";
    } else {
        echo "❌ $file - NOT FOUND\n";
        $all_exist = false;
    }
}

echo "\n";

// Test 4: Try to load services
echo "Step 4: Testing Service Loading\n";
echo str_repeat("-", 70) . "\n";

try {
    // Set up mock session if needed
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'ADMIN';
    }

    // Include bootstrap
    require_once 'config/bootstrap.php';
    
    echo "✅ Bootstrap loaded successfully\n";

    // Get services
    $services = getServices($connect);
    echo "✅ ServiceContainer initialized\n";

    // Test each service
    $service_tests = [
        'getAuditLogger' => 'AuditLogger',
        'getStockService' => 'StockService',
        'getApprovalEngine' => 'ApprovalEngine',
        'getCreditControl' => 'CreditControl',
        'getPermissionMiddleware' => 'PermissionMiddleware',
        'getDatabaseHelper' => 'DatabaseHelper',
    ];

    foreach ($service_tests as $method => $class_name) {
        $service = $services->$method();
        if ($service !== null && is_object($service)) {
            $class_parts = explode('\\', get_class($service));
            $actual_class = end($class_parts);
            echo "✅ $class_name loaded\n";
        } else {
            echo "❌ $class_name failed to load\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error loading services: " . $e->getMessage() . "\n";
    $all_exist = false;
}

echo "\n";

// Summary
echo "=" . str_repeat("=", 68) . "\n";
echo "PHASE 2 SUMMARY\n";
echo "=" . str_repeat("=", 68) . "\n\n";

if ($all_exist) {
    echo "✅ PHASE 2 COMPLETE - ALL SERVICES READY\n\n";
    echo "Service classes initialized and ready for integration.\n\n";
    echo "Next Steps:\n";
    echo "1. Review PRODUCTION_IMPLEMENTATION.md Phase 3\n";
    echo "2. Update existing controllers to use services\n";
    echo "3. Implement transaction wrappers\n";
    echo "4. Test workflows end-to-end\n";
} else {
    echo "⚠️  PHASE 2 INCOMPLETE - FIX ERRORS ABOVE\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

$connect->close();
?>
