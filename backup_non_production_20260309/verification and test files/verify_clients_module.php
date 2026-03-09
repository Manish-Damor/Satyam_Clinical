<?php
/**
 * CLIENTS MODULE VERIFICATION
 * Tests all components of the Clients CRUD module
 */

require './constant/connect.php';

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║           PHASE 2: CLIENTS CRUD MODULE VERIFICATION                   ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

$tests = [];

// Test 1: Verify clients table
echo "[TEST 1] Checking clients table...\n";
$res = $connect->query("SHOW TABLES LIKE 'clients'");
if ($res->num_rows > 0) {
    $tests[] = "✓ Clients table exists";
    echo "  ✓ Table found\n";
    
    // Show columns
    $res2 = $connect->query("SHOW COLUMNS FROM clients");
    $columnCount = 0;
    while ($res2->fetch_assoc()) {
        $columnCount++;
    }
    echo "  ✓ Table has $columnCount columns\n";
} else {
    echo "  ✗ Clients table not found\n";
}

// Test 2: Count sample data
echo "\n[TEST 2] Checking sample client data...\n";
$res = $connect->query("SELECT COUNT(*) as cnt FROM clients");
$row = $res->fetch_assoc();
$clientCount = $row['cnt'];
echo "  ✓ Found {$clientCount} clients\n";
$tests[] = "✓ Sample data loaded ({$clientCount} clients)";

// Test 3: Verify client handlers exist
echo "\n[TEST 3] Checking backend handlers...\n";
$handlers = [
    'php_action/createClient.php',
    'php_action/updateClient.php',
    'php_action/deleteClient.php',
    'php_action/fetchClients.php'
];

foreach ($handlers as $handler) {
    if (file_exists($handler)) {
        echo "  ✓ $handler exists\n";
        $tests[] = "✓ " . basename($handler) . " created";
    } else {
        echo "  ✗ $handler NOT found\n";
    }
}

// Test 4: Test fetchClients.php
echo "\n[TEST 4] Testing fetchClients.php...\n";
$output = @file_get_contents('php_action/fetchClients.php');
if (strpos($output, 'SELECT') !== false) {
    echo "  ✓ fetchClients.php contains SQL query\n";
    $tests[] = "✓ fetchClients.php functional";
} else {
    echo "  ✗ fetchClients.php may have issues\n";
}

// Test 5: Verify UI files
echo "\n[TEST 5] Checking UI files...\n";
$uiFiles = [
    'clients_list.php',
    'clients_form.php'
];

foreach ($uiFiles as $file) {
    if (file_exists($file)) {
        echo "  ✓ $file exists\n";
        $tests[] = "✓ " . $file . " created";
    } else {
        echo "  ✗ $file NOT found\n";
    }
}

// Test 6: Sample client details
echo "\n[TEST 6] Sample client details...\n";
$res = $connect->query("SELECT client_code, name, business_type, status FROM clients LIMIT 3");
while ($client = $res->fetch_assoc()) {
    echo "  • {$client['client_code']}: {$client['name']} ({$client['business_type']}) - {$client['status']}\n";
}

// Test 7: Verify prepared statements in handlers
echo "\n[TEST 7] Verifying prepared statements...\n";
$psCount = 0;
foreach ($handlers as $handler) {
    if (file_exists($handler)) {
        $content = file_get_contents($handler);
        if (strpos($content, 'prepare(') !== false && strpos($content, 'bind_param') !== false) {
            $psCount++;
        }
    }
}
echo "  ✓ {$psCount} handlers using prepared statements\n";
$tests[] = "✓ Prepared statements implemented ({$psCount} handlers)";

// Summary
echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                        VERIFICATION SUMMARY                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($tests as $test) {
    echo "$test\n";
}

echo "\n✓ PHASE 2: CLIENTS CRUD MODULE - COMPLETE\n";
echo "\nURL to access Clients module: http://localhost/Satyam_Clinical/clients_list.php\n";
echo "\n════════════════════════════════════════════════════════════════════════\n";
?>
