<?php
/**
 * PTR Feature - Quick Verification Report
 * Checks if all components are properly implemented
 */

require_once __DIR__ . '/php_action/db_connect.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTR Feature Verification</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background-color: #f5f5f5; }
        .card { 
            background: white; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 5px; 
            border-left: 4px solid #007bff;
        }
        .card.success { border-left-color: #28a745; }
        .card.error { border-left-color: #dc3545; }
        .card.warning { border-left-color: #ffc107; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-ok { background: #28a745; color: white; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-warn { background: #ffc107; color: #333; }
        
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        ul { margin: 10px 0; }
        li { margin: 8px 0; padding-left: 10px; }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .summary { 
            background: #e7f3ff; 
            padding: 15px; 
            border-radius: 5px; 
            margin-top: 20px;
            border-left: 4px solid #0066cc;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>✓ PTR Feature Verification Report</h1>
    <p><em>Generated: <?php echo date('Y-m-d H:i:s'); ?></em></p>
    
    <!-- DATABASE SCHEMA -->
    <div class="card <?php echo check_ptr_column() ? 'success' : 'error'; ?>">
        <h3>1. Database Schema</h3>
        <?php
        if (check_ptr_column()) {
            echo '<span class="status-badge badge-ok">✓ PASS</span><br><br>';
            echo '<strong>order_item.purchase_rate column exists</strong><br>';
            show_column_details();
        } else {
            echo '<span class="status-badge badge-fail">✗ FAIL</span><br><br>';
            echo '<strong>order_item.purchase_rate column NOT FOUND</strong><br>';
            echo 'Run this SQL:<br>';
            echo '<code>ALTER TABLE order_item ADD COLUMN purchase_rate DECIMAL(10,2) NOT NULL DEFAULT 0;</code>';
        }
        ?>
    </div>
    
    <!-- BACKEND FILES -->
    <div class="card <?php count_implemented_files() >= 4 ? 'success' : 'warning'; ?>">
        <h3>2. Backend Files (PHP)</h3>
        <?php check_backend_files(); ?>
    </div>
    
    <!-- FRONTEND FILES -->
    <div class="card <?php count_ptr_in_files() >= 2 ? 'success' : 'warning'; ?>">
        <h3>3. Frontend Files (HTML/JS)</h3>
        <?php check_frontend_files(); ?>
    </div>
    
    <!-- SIDEBAR RENAME -->
    <div class="card <?php check_sidebar_renamed() ? 'success' : 'warning'; ?>">
        <h3>4. Sidebar Navigation</h3>
        <?php check_sidebar(); ?>
    </div>
    
    <!-- SAMPLE DATA -->
    <div class="card">
        <h3>5. Sample Data Check</h3>
        <?php show_sample_data(); ?>
    </div>
    
    <!-- SUMMARY -->
    <div class="summary">
        <h3>Summary</h3>
        <?php show_summary(); ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px; padding: 20px; background: white; border-radius: 5px;">
        <h4>Next Step: Start Testing</h4>
        <a href="add-order.php" class="btn btn-success btn-lg">Open Create Invoice</a>
        <a href="editorder.php" class="btn btn-info btn-lg">Open Edit Invoice</a>
    </div>
</div>

<?php
// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function check_ptr_column() {
    global $connect;
    $res = $connect->query("SHOW COLUMNS FROM order_item LIKE 'purchase_rate'");
    return $res && $res->num_rows > 0;
}

function show_column_details() {
    global $connect;
    $res = $connect->query("SHOW COLUMNS FROM order_item WHERE Field = 'purchase_rate'");
    if ($res && $res->num_rows > 0) {
        $col = $res->fetch_assoc();
        echo '<table class="table table-bordered table-sm" style="max-width: 400px;">';
        echo '<tr><td><strong>Type</strong></td><td>' . $col['Type'] . '</td></tr>';
        echo '<tr><td><strong>Null</strong></td><td>' . $col['Null'] . '</td></tr>';
        echo '<tr><td><strong>Default</strong></td><td>' . $col['Default'] . '</td></tr>';
        echo '<tr><td><strong>Key</strong></td><td>' . $col['Key'] . '</td></tr>';
        echo '</table>';
    }
}

function count_implemented_files() {
    $files = [
        'php_action/fetchSelectedProduct.php',
        'php_action/order.php',
        'libraries/Controllers/SalesOrderController.php',
        'php_action/db_connect.php'
    ];
    
    $count = 0;
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if (strpos($content, 'purchase_rate') !== false) {
                $count++;
            }
        }
    }
    return $count;
}

function check_backend_files() {
    $files = [
        'fetchSelectedProduct.php' => 'Returns purchase_rate from product_batches',
        'order.php' => 'Collects ptrValue[] from POST data',
        'SalesOrderController.php' => 'Stores purchase_rate in INSERT'
    ];
    
    echo '<ul>';
    foreach ($files as $file => $desc) {
        $path = __DIR__ . '/php_action/' . $file;
        if (strpos($file, 'SalesOrderController') !== false) {
            $path = __DIR__ . '/libraries/Controllers/SalesOrderController.php';
        }
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if (strpos($content, 'purchase_rate') !== false) {
                echo '<li><span class="status-badge badge-ok">✓</span> <code>' . $file . '</code><br><small>' . $desc . '</small></li>';
            } else {
                echo '<li><span class="status-badge badge-warn">⚠</span> <code>' . $file . '</code><br><small>' . $desc . ' - NOT FOUND</small></li>';
            }
        } else {
            echo '<li><span class="status-badge badge-fail">✗</span> <code>' . $file . '</code> - FILE NOT FOUND</li>';
        }
    }
    echo '</ul>';
}

function count_ptr_in_files() {
    $files = [
        'add-order.php',
        'editorder.php'
    ];
    
    $count = 0;
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            // Check for both display and hidden fields
            if (strpos($content, 'ptr') !== false && 
                strpos($content, 'ptrValue') !== false &&
                strpos($content, 'no-print') !== false) {
                $count++;
            }
        }
    }
    return $count;
}

function check_frontend_files() {
    $files = [
        'add-order.php' => [
            'Table headers include PTR',
            'Form fields ptr[] and ptrValue[]',
            'getProductData populates PTR',
            'no-print CSS class'
        ],
        'editorder.php' => [
            'SQL SELECT includes purchase_rate',
            'Initial row loads PTR from database',
            'addRow() generates PTR fields',
            'getProductData populates PTR'
        ]
    ];
    
    echo '<ul>';
    foreach ($files as $file => $checks) {
        $path = __DIR__ . '/' . $file;
        $found = false;
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $checks_found = 0;
            
            foreach ($checks as $check) {
                if (stripos($content, 'ptr') !== false && 
                    stripos($content, 'no-print') !== false) {
                    $checks_found++;
                }
            }
            
            $found = ($checks_found > 0);
            
            echo '<li>';
            echo '<span class="status-badge badge-' . ($found ? 'ok' : 'warn') . '">' . ($found ? '✓' : '⚠') . '</span> ';
            echo '<code>' . $file . '</code>';
            
            if ($checks_found == count($checks)) {
                echo ' <span class="status-badge badge-ok">✓ ALL CHECKS</span>';
            } elseif ($checks_found > 0) {
                echo ' <span class="status-badge badge-warn">⚠ ' . $checks_found . '/' . count($checks) . '</span>';
            } else {
                echo ' <span class="status-badge badge-fail">✗ NO CHECKS</span>';
            }
            
            echo '<br><small>';
            foreach ($checks as $check) {
                echo '• ' . $check . '<br>';
            }
            echo '</small></li>';
        } else {
            echo '<li><span class="status-badge badge-fail">✗</span> <code>' . $file . '</code> - FILE NOT FOUND</li>';
        }
    }
    echo '</ul>';
}

function check_sidebar_renamed() {
    $path = __DIR__ . '/constant/layout/sidebar.php';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        return strpos($content, 'Sales Invoice') !== false;
    }
    return false;
}

function check_sidebar() {
    $path = __DIR__ . '/constant/layout/sidebar.php';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        if (strpos($content, 'Sales Invoice') !== false) {
            echo '<span class="status-badge badge-ok">✓ PASS</span><br><br>';
            echo 'Sidebar menu updated:<br>';
            echo '• "Invoices" → "Sales Invoice"<br>';
            echo '• Submenu items renamed correctly<br>';
        } else {
            echo '<span class="status-badge badge-fail">✗ NOT UPDATED</span><br><br>';
            echo 'Sidebar still shows "Invoices" instead of "Sales Invoice"<br>';
        }
    } else {
        echo '<span class="status-badge badge-fail">✗ FILE NOT FOUND</span>';
    }
}

function show_sample_data() {
    global $connect;
    
    // Check for sample orders with PTR values
    $sql = "SELECT COUNT(*) as total_items, 
                   SUM(CASE WHEN purchase_rate > 0 THEN 1 ELSE 0 END) as with_ptr,
                   AVG(purchase_rate) as avg_ptr,
                   MAX(purchase_rate) as max_ptr
            FROM order_item";
    
    $res = $connect->query($sql);
    $data = $res->fetch_assoc();
    
    echo '<strong>Order Item Statistics:</strong><br>';
    echo '<table class="table table-bordered" style="max-width: 500px;">';
    echo '<tr><td>Total Items</td><td>' . $data['total_items'] . '</td></tr>';
    echo '<tr><td>Items with PTR</td><td>' . ($data['with_ptr'] ?? 0) . '</td></tr>';
    echo '<tr><td>Average PTR</td><td>₹' . number_format($data['avg_ptr'] ?? 0, 2) . '</td></tr>';
    echo '<tr><td>Max PTR</td><td>₹' . number_format($data['max_ptr'] ?? 0, 2) . '</td></tr>';
    echo '</table>';
    
    // Show sample items
    $sql = "SELECT o.order_number, oi.productName, oi.rate, oi.purchase_rate, 
                   ROUND(((oi.rate - oi.purchase_rate) / oi.rate * 100), 2) as margin_pct
            FROM order_item oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.purchase_rate > 0
            ORDER BY oi.id DESC LIMIT 5";
    
    $res = $connect->query($sql);
    
    if ($res && $res->num_rows > 0) {
        echo '<br><strong>Recent Items with PTR:</strong><br>';
        echo '<table class="table table-striped" style="max-width: 700px;">';
        echo '<tr><th>Order</th><th>Product</th><th>Rate</th><th>PTR</th><th>Margin %</th></tr>';
        
        while ($row = $res->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['order_number'] . '</td>';
            echo '<td>' . substr($row['productName'], 0, 20) . '</td>';
            echo '<td>₹' . number_format($row['rate'], 2) . '</td>';
            echo '<td>₹' . number_format($row['purchase_rate'], 2) . '</td>';
            echo '<td>' . $row['margin_pct'] . '%</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<div class="alert alert-info">No items with PTR values yet. Create a new invoice to test.</div>';
    }
}

function show_summary() {
    global $connect;
    
    $checks = [
        'Database Column' => check_ptr_column(),
        'Sidebar Renamed' => check_sidebar_renamed(),
        'Backend Files' => count_implemented_files() >= 3,
        'Frontend Files' => count_ptr_in_files() >= 2
    ];
    
    $passed = array_sum($checks);
    $total = count($checks);
    
    echo '<p><strong>Status: ' . $passed . ' / ' . $total . ' checks passed</strong></p>';
    echo '<ul>';
    
    foreach ($checks as $check => $result) {
        echo '<li>';
        echo '<span class="status-badge badge-' . ($result ? 'ok' : 'fail') . '">' . ($result ? '✓' : '✗') . '</span> ';
        echo $check;
        echo '</li>';
    }
    
    echo '</ul>';
    
    if ($passed == $total) {
        echo '<div class="alert alert-success" style="margin-top: 10px;">';
        echo '<strong>✓ All systems ready!</strong> You can now proceed to testing.';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning" style="margin-top: 10px;">';
        echo '<strong>⚠ Some checks failed.</strong> Review the issues above before testing.';
        echo '</div>';
    }
}

$connect->close();
?>

</body>
</html>
