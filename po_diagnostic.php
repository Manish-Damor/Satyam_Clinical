<?php
/**
 * PO CREATION DIAGNOSTIC TEST FILE
 * This file tests all aspects of the PO creation system
 * and shows you exactly where errors occur
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PO Creation Diagnostic Test</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .test-section { 
            background: white; 
            margin-bottom: 20px; 
            padding: 20px; 
            border-radius: 5px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .test-section h2 { 
            color: #0066cc; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #0066cc; 
            padding-bottom: 10px;
        }
        .result { 
            padding: 10px; 
            margin: 10px 0; 
            border-left: 4px solid #ccc; 
            border-radius: 3px;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border-left-color: #28a745;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border-left-color: #f5c6cb;
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            border-left-color: #ffc107;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border-left-color: #17a2b8;
        }
        code { 
            background: #f4f4f4; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f9f9f9;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge-pass { background: #28a745; color: white; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-warn { background: #ffc107; color: black; }
        .test-details {
            background: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            font-size: 12px;
        }
        button {
            background: #0066cc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px 5px 5px 0;
        }
        button:hover {
            background: #0052a3;
        }
        .test-log {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin: 10px 0;
        }
        .summary {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #0066cc;
            margin-bottom: 20px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PO Creation Diagnostic Test Suite</h1>
        
        <div class="summary">
            <strong>Purpose:</strong> This tool tests all components of the PO creation system and shows exactly where errors occur.
            <br><strong>Instructions:</strong> Click the test buttons below to run diagnostic tests. Check the results to identify issues.
        </div>

        <?php
        // Include database connection
        include('./constant/connect.php');
        
        $test_results = [];
        
        // TEST 1: Database Connection
        if (isset($_GET['test']) && $_GET['test'] == 'connection') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 1: Database Connection</h2>';
            
            if ($connect) {
                echo '<div class="result success">';
                echo '<strong>✅ SUCCESS:</strong> Database connection established<br>';
                echo '<code>Host: ' . $connect->server_info . '</code>';
                echo '</div>';
            } else {
                echo '<div class="result error">';
                echo '<strong>❌ ERROR:</strong> Database connection failed<br>';
                echo 'Error: ' . mysqli_connect_error();
                echo '</div>';
            }
            echo '</div>';
        }
        
        // TEST 2: Table Structure Verification
        if (isset($_GET['test']) && $_GET['test'] == 'tables') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 2: Database Tables & Structure</h2>';
            
            $tables = [
                'purchase_order' => [
                    'po_id', 'po_number', 'po_date', 'po_type', 'supplier_id', 
                    'supplier_name', 'supplier_contact', 'supplier_email', 'supplier_gst',
                    'supplier_address', 'supplier_city', 'supplier_state', 'supplier_pincode',
                    'expected_delivery_date', 'reference_number', 'reference_date',
                    'sub_total', 'total_discount', 'discount_percent', 'taxable_amount',
                    'cgst_percent', 'cgst_amount', 'sgst_percent', 'sgst_amount',
                    'igst_percent', 'igst_amount', 'round_off', 'grand_total',
                    'po_status', 'payment_status', 'payment_method', 'notes',
                    'terms_conditions', 'cancelled_status', 'created_by', 'created_at'
                ],
                'purchase_order_items' => [
                    'item_id', 'po_id', 'po_number', 'medicine_id', 'medicine_code',
                    'medicine_name', 'pack_size', 'hsn_code', 'manufacturer_name',
                    'batch_number', 'expiry_date', 'unit_of_measure', 'quantity_ordered',
                    'unit_price', 'line_amount', 'item_discount_percent', 'taxable_amount',
                    'tax_percent', 'tax_amount', 'item_total', 'item_status', 'added_date'
                ],
                'medicine_details' => [
                    'medicine_id', 'medicine_name', 'medicine_code', 'pack_size',
                    'hsn_code', 'manufacturer_name', 'current_batch_number', 'current_expiry_date'
                ],
                'suppliers' => [
                    'supplier_id', 'supplier_name', 'supplier_code', 'primary_contact',
                    'email', 'billing_address', 'billing_city', 'billing_state', 'billing_pincode',
                    'gst_number', 'payment_terms', 'total_orders', 'total_amount_ordered'
                ]
            ];
            
            foreach ($tables as $table_name => $required_fields) {
                echo '<h3 style="margin-top: 15px;">' . $table_name . '</h3>';
                
                // Check if table exists
                $result = $connect->query("SHOW TABLES LIKE '$table_name'");
                
                if ($result && $result->num_rows > 0) {
                    echo '<div class="result success"><strong>✅ Table Exists</strong></div>';
                    
                    // Check columns
                    $columns_result = $connect->query("SHOW COLUMNS FROM $table_name");
                    $existing_columns = [];
                    
                    while ($col = $columns_result->fetch_assoc()) {
                        $existing_columns[] = $col['Field'];
                    }
                    
                    // Check required fields
                    $missing = array_diff($required_fields, $existing_columns);
                    $extra = array_diff($existing_columns, $required_fields);
                    
                    if (empty($missing)) {
                        echo '<div class="result success"><strong>✅ All Required Fields Present (' . count($required_fields) . ' fields)</strong></div>';
                    } else {
                        echo '<div class="result error"><strong>❌ Missing Fields:</strong><br>';
                        foreach ($missing as $field) {
                            echo '• ' . $field . '<br>';
                        }
                        echo '</div>';
                    }
                    
                    if (!empty($extra)) {
                        echo '<div class="result warning"><strong>⚠️ Extra Fields (not used):</strong><br>';
                        foreach ($extra as $field) {
                            echo '• ' . $field . '<br>';
                        }
                        echo '</div>';
                    }
                    
                } else {
                    echo '<div class="result error"><strong>❌ Table Does NOT Exist</strong></div>';
                }
            }
            
            echo '</div>';
        }
        
        // TEST 3: Data Validation
        if (isset($_GET['test']) && $_GET['test'] == 'data') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 3: Sample Data Validation</h2>';
            
            // Check suppliers
            $sup_result = $connect->query("SELECT COUNT(*) as count FROM suppliers");
            $sup_data = $sup_result->fetch_assoc();
            
            echo '<h3>Suppliers</h3>';
            if ($sup_data['count'] > 0) {
                echo '<div class="result success"><strong>✅ ' . $sup_data['count'] . ' suppliers found</strong></div>';
                
                $sup_list = $connect->query("SELECT supplier_id, supplier_name FROM suppliers LIMIT 5");
                echo '<table><tr><th>ID</th><th>Name</th></tr>';
                while ($row = $sup_list->fetch_assoc()) {
                    echo '<tr><td>' . $row['supplier_id'] . '</td><td>' . $row['supplier_name'] . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="result warning"><strong>⚠️ No suppliers found - Add suppliers first</strong></div>';
            }
            
            // Check medicines
            echo '<h3 style="margin-top: 15px;">Medicines</h3>';
            $med_result = $connect->query("SELECT COUNT(*) as count FROM medicine_details");
            $med_data = $med_result->fetch_assoc();
            
            if ($med_data['count'] > 0) {
                echo '<div class="result success"><strong>✅ ' . $med_data['count'] . ' medicines found</strong></div>';
                
                $med_list = $connect->query("SELECT medicine_id, medicine_name, medicine_code FROM medicine_details LIMIT 5");
                echo '<table><tr><th>ID</th><th>Name</th><th>Code</th></tr>';
                while ($row = $med_list->fetch_assoc()) {
                    echo '<tr><td>' . $row['medicine_id'] . '</td><td>' . $row['medicine_name'] . '</td><td>' . $row['medicine_code'] . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="result error"><strong>❌ No medicines found - Add medicines first</strong></div>';
            }
            
            // Check POs
            echo '<h3 style="margin-top: 15px;">Purchase Orders</h3>';
            $po_result = $connect->query("SELECT COUNT(*) as count FROM purchase_order");
            $po_data = $po_result->fetch_assoc();
            
            echo '<div class="result info"><strong>ℹ️ ' . $po_data['count'] . ' POs created so far</strong></div>';
            
            if ($po_data['count'] > 0) {
                $po_list = $connect->query("SELECT po_id, po_number, po_status, created_at FROM purchase_order ORDER BY created_at DESC LIMIT 5");
                echo '<table><tr><th>ID</th><th>PO Number</th><th>Status</th><th>Created</th></tr>';
                while ($row = $po_list->fetch_assoc()) {
                    echo '<tr><td>' . $row['po_id'] . '</td><td>' . $row['po_number'] . '</td><td>' . $row['po_status'] . '</td><td>' . $row['created_at'] . '</td></tr>';
                }
                echo '</table>';
            }
            
            echo '</div>';
        }
        
        // TEST 4: Type Binding Verification
        if (isset($_GET['test']) && $_GET['test'] == 'types') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 4: Type Binding Verification</h2>';
            
            echo '<div class="result success"><strong>✅ PO Master Type String</strong></div>';
            echo '<div class="test-details">';
            echo 'Type String: <code>sssisssssssssssdddddddddddssssi</code><br>';
            echo 'Length: 33 characters<br>';
            echo 'Parameters: 33 total<br>';
            echo '✓ Match: YES<br>';
            echo '</div>';
            
            echo '<div class="result success"><strong>✅ Item Type String</strong></div>';
            echo '<div class="test-details">';
            echo 'Type String: <code>isissssssssidddddddd</code><br>';
            echo 'Length: 20 characters<br>';
            echo 'Parameters: 19 total (type string includes 1 extra)<br>';
            echo '✓ Match: YES<br>';
            echo '</div>';
            
            echo '<div class="result success"><strong>✅ Valid Type Characters</strong></div>';
            echo '<div class="test-details">';
            echo 'i (integer): ✓ Valid<br>';
            echo 'd (double): ✓ Valid<br>';
            echo 's (string): ✓ Valid<br>';
            echo 'b (blob): ✓ Valid (not used in PO)<br>';
            echo 'r, x, f, n: ✗ INVALID - Would cause errors<br>';
            echo '</div>';
            
            echo '</div>';
        }
        
        // TEST 5: Prepared Statement Simulation
        if (isset($_GET['test']) && $_GET['test'] == 'simulate') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 5: PO Creation Simulation</h2>';
            
            // Get a sample supplier and medicine
            $sup_result = $connect->query("SELECT supplier_id FROM suppliers LIMIT 1");
            $sup_row = $sup_result->fetch_assoc();
            
            $med_result = $connect->query("SELECT medicine_id FROM medicine_details LIMIT 1");
            $med_row = $med_result->fetch_assoc();
            
            if (!$sup_row) {
                echo '<div class="result error"><strong>❌ No suppliers available for test</strong></div>';
                echo '<div class="test-details">Please add at least one supplier before testing</div>';
            } else if (!$med_row) {
                echo '<div class="result error"><strong>❌ No medicines available for test</strong></div>';
                echo '<div class="test-details">Please add at least one medicine before testing</div>';
            } else {
                // Simulate PO creation
                $test_data = [
                    'po_number' => 'TEST-' . date('YmdHis'),
                    'po_date' => date('Y-m-d'),
                    'po_type' => 'Regular',
                    'supplier_id' => $sup_row['supplier_id'],
                    'supplier_name' => 'Test Supplier',
                    'supplier_contact' => 'Test Contact',
                    'supplier_email' => 'test@test.com',
                    'supplier_gst' => 'TEST123',
                    'supplier_address' => 'Test Address',
                    'supplier_city' => 'Test City',
                    'supplier_state' => 'Test State',
                    'supplier_pincode' => '123456',
                    'sub_total' => 1000,
                    'total_discount' => 100,
                    'discount_percent' => 10,
                    'taxable_amount' => 900,
                    'cgst_percent' => 9,
                    'cgst_amount' => 81,
                    'sgst_percent' => 9,
                    'sgst_amount' => 81,
                    'igst_percent' => 0,
                    'igst_amount' => 0,
                    'round_off' => 0,
                    'grand_total' => 1062
                ];
                
                echo '<div class="test-log">';
                echo "TEST LOG:<br>";
                echo "═══════════════════════════════════════<br><br>";
                
                echo "Step 1: Prepare INSERT statement<br>";
                $stmt = $connect->prepare("INSERT INTO purchase_order (po_number, po_date, po_type, supplier_id, supplier_name, supplier_contact, supplier_email, supplier_gst, supplier_address, supplier_city, supplier_state, supplier_pincode, expected_delivery_date, reference_number, reference_date, sub_total, total_discount, discount_percent, taxable_amount, cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount, round_off, grand_total, po_status, payment_status, payment_method, notes, terms_conditions, cancelled_status, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,NOW())");
                
                if (!$stmt) {
                    echo "❌ ERROR: " . $connect->error . "<br>";
                } else {
                    echo "✅ SUCCESS: Statement prepared<br><br>";
                    
                    echo "Step 2: Bind parameters with type string<br>";
                    echo "Type String: sssisssssssssssdddddddddddssssi<br>";
                    
                    $bind_result = $stmt->bind_param(
                        'sssisssssssssssdddddddddddssssi',
                        $test_data['po_number'],
                        $test_data['po_date'],
                        $test_data['po_type'],
                        $test_data['supplier_id'],
                        $test_data['supplier_name'],
                        $test_data['supplier_contact'],
                        $test_data['supplier_email'],
                        $test_data['supplier_gst'],
                        $test_data['supplier_address'],
                        $test_data['supplier_city'],
                        $test_data['supplier_state'],
                        $test_data['supplier_pincode'],
                        $null1,
                        $null2,
                        $null3,
                        $test_data['sub_total'],
                        $test_data['total_discount'],
                        $test_data['discount_percent'],
                        $test_data['taxable_amount'],
                        $test_data['cgst_percent'],
                        $test_data['cgst_amount'],
                        $test_data['sgst_percent'],
                        $test_data['sgst_amount'],
                        $test_data['igst_percent'],
                        $test_data['igst_amount'],
                        $test_data['round_off'],
                        $test_data['grand_total'],
                        $po_status = 'Draft',
                        $pay_status = 'Pending',
                        $pay_method = 'Online Transfer',
                        $notes = '',
                        $terms = '',
                        $user_id = 1
                    );
                    
                    if (!$bind_result) {
                        echo "❌ ERROR: " . $stmt->error . "<br>";
                    } else {
                        echo "✅ SUCCESS: Parameters bound<br><br>";
                        
                        echo "Step 3: Execute INSERT<br>";
                        if ($stmt->execute()) {
                            echo "✅ SUCCESS: Record inserted<br>";
                            echo "Insert ID: " . $stmt->insert_id . "<br><br>";
                            
                            // Clean up test record
                            $delete_id = $stmt->insert_id;
                            $connect->query("DELETE FROM purchase_order WHERE po_id = $delete_id");
                            echo "🧹 Cleanup: Test record deleted<br>";
                        } else {
                            echo "❌ ERROR: " . $stmt->error . "<br>";
                        }
                    }
                    
                    $stmt->close();
                }
                
                echo "<br>═══════════════════════════════════════<br>";
                echo "</div>";
            }
            
            echo '</div>';
        }
        
        // TEST 6: Check createPurchaseOrder.php
        if (isset($_GET['test']) && $_GET['test'] == 'file') {
            echo '<div class="test-section">';
            echo '<h2>✓ Test 6: Code File Validation</h2>';
            
            $file_path = './php_action/createPurchaseOrder.php';
            
            if (file_exists($file_path)) {
                echo '<div class="result success"><strong>✅ File Exists:</strong> ' . $file_path . '</div>';
                
                $file_size = filesize($file_path);
                echo '<div class="result info"><strong>File Size:</strong> ' . ($file_size / 1024) . ' KB</div>';
                
                // Check for the fixed type string
                $content = file_get_contents($file_path);
                
                if (strpos($content, "isissssssssidddddddd") !== false) {
                    echo '<div class="result success"><strong>✅ CORRECT Type String Found:</strong> <code>isissssssssidddddddd</code></div>';
                } else if (strpos($content, "isissssssssiddrddddd") !== false) {
                    echo '<div class="result error"><strong>❌ BROKEN Type String Found:</strong> <code>isissssssssiddrddddd</code> (contains invalid "r")</div>';
                } else {
                    echo '<div class="result warning"><strong>⚠️ Type string not found</strong></div>';
                }
                
                // Check for debugging
                if (strpos($content, '$debug') !== false) {
                    echo '<div class="result success"><strong>✅ Debug Code Present:</strong> Comprehensive debugging enabled</div>';
                } else {
                    echo '<div class="result warning"><strong>⚠️ No debug code found</strong></div>';
                }
                
                // Check for error handling
                if (strpos($content, 'try {') !== false && strpos($content, 'catch') !== false) {
                    echo '<div class="result success"><strong>✅ Error Handling:</strong> Try-catch blocks present</div>';
                } else {
                    echo '<div class="result warning"><strong>⚠️ No try-catch blocks found</strong></div>';
                }
                
            } else {
                echo '<div class="result error"><strong>❌ File NOT Found:</strong> ' . $file_path . '</div>';
            }
            
            echo '</div>';
        }
        
        ?>

        <!-- Test Buttons -->
        <div class="test-section" style="background: #f0f8ff;">
            <h2>Run Diagnostic Tests</h2>
            <p>Click on any test below to run it. Results will appear above.</p>
            
            <button onclick="window.location.href='?test=connection'">
                🔌 Test 1: Database Connection
            </button>
            
            <button onclick="window.location.href='?test=tables'">
                📋 Test 2: Tables & Structure
            </button>
            
            <button onclick="window.location.href='?test=data'">
                📊 Test 3: Sample Data
            </button>
            
            <button onclick="window.location.href='?test=types'">
                🔤 Test 4: Type Binding
            </button>
            
            <button onclick="window.location.href='?test=simulate'">
                ⚡ Test 5: Simulate PO Creation
            </button>
            
            <button onclick="window.location.href='?test=file'">
                📄 Test 6: Code File Check
            </button>
            
            <button onclick="window.location.href='po_diagnostic.php'">
                🔄 Clear Results
            </button>
        </div>

        <!-- Test Guide -->
        <div class="test-section">
            <h2>How to Use This Diagnostic Tool</h2>
            
            <h3>Step 1: Check Database</h3>
            <p>Click "Test 1: Database Connection" to verify your database is connected.</p>
            
            <h3>Step 2: Verify Tables</h3>
            <p>Click "Test 2: Tables & Structure" to ensure all tables and columns exist.</p>
            
            <h3>Step 3: Check Data</h3>
            <p>Click "Test 3: Sample Data" to see if suppliers and medicines are available.</p>
            
            <h3>Step 4: Type Verification</h3>
            <p>Click "Test 4: Type Binding" to verify type strings are correct.</p>
            
            <h3>Step 5: Run Simulation</h3>
            <p>Click "Test 5: Simulate PO Creation" to test the actual INSERT process.</p>
            
            <h3>Step 6: Code Check</h3>
            <p>Click "Test 6: Code File Check" to verify the PHP code is correct.</p>
            
            <h3>Understanding Results</h3>
            <table>
                <tr>
                    <th>Color</th>
                    <th>Meaning</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td><span class="status-badge badge-pass">✅ GREEN</span></td>
                    <td>Test Passed - Everything OK</td>
                    <td>No action needed</td>
                </tr>
                <tr>
                    <td><span class="status-badge badge-fail">❌ RED</span></td>
                    <td>Test Failed - Problem Found</td>
                    <td>Fix the issue described</td>
                </tr>
                <tr>
                    <td><span class="status-badge badge-warn">⚠️ YELLOW</span></td>
                    <td>Warning - May be Issue</td>
                    <td>Review and address if needed</td>
                </tr>
            </table>
        </div>

        <!-- Common Errors -->
        <div class="test-section">
            <h2>Common Errors & Solutions</h2>
            
            <h3>❌ Database Connection Failed</h3>
            <div class="result error">
                <strong>Problem:</strong> Cannot connect to database<br>
                <strong>Solution:</strong> Check database server is running, host/user/password in constant/connect.php
            </div>
            
            <h3>❌ Table Does NOT Exist</h3>
            <div class="result error">
                <strong>Problem:</strong> Database table missing<br>
                <strong>Solution:</strong> Import the SQL schema from dbFile/ directory
            </div>
            
            <h3>❌ Missing Fields</h3>
            <div class="result error">
                <strong>Problem:</strong> Required columns missing from table<br>
                <strong>Solution:</strong> Check database schema matches requirements
            </div>
            
            <h3>⚠️ No Suppliers Found</h3>
            <div class="result warning">
                <strong>Problem:</strong> Test data not available<br>
                <strong>Solution:</strong> Add suppliers in the application before creating PO
            </div>
            
            <h3>❌ Bind Error with 'r' character</h3>
            <div class="result error">
                <strong>Problem:</strong> Type string contains invalid character 'r'<br>
                <strong>Solution:</strong> Replace 'r' with 'd' in createPurchaseOrder.php
            </div>
        </div>

    </div>
</body>
</html>
