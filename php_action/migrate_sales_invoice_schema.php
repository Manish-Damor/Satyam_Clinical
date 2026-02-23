<?php
/**
 * PHASE 1: DATABASE SCHEMA MIGRATION
 * Sales Invoice Module - Complete Schema Upgrade
 * 
 * Actions:
 * 1. Rename customers → clients (with pharmacy-specific fields)
 * 2. Create sales_invoices table (replaces orders)
 * 3. Create sales_invoice_items table (replaces order_item)
 * 4. Add purchase_rate column to product table (for PTR storage)
 * 5. Create invoice_sequence for auto-number generation
 * 6. Add sample client data
 * 
 * Safety: All old tables renamed to _legacy
 */

require './constant/connect.php';

$errors = [];
$success = [];

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║         PHASE 1: DATABASE SCHEMA MIGRATION - SALES INVOICE MODULE      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

try {
    
    // ====================================================================
    // STEP 1: BACKUP OLD TABLES (rename to _legacy)
    // ====================================================================
    echo "[STEP 1] Backing up old tables...\n";
    
    $oldTables = ['customers', 'orders', 'order_item'];
    foreach ($oldTables as $table) {
        $res = $connect->query("SHOW TABLES LIKE '$table'");
        if ($res->num_rows > 0) {
            $legacyTable = $table . '_legacy_' . date('Y-m-d_Hi');
            $renameSQL = "RENAME TABLE `$table` TO `$legacyTable`";
            if ($connect->query($renameSQL)) {
                $success[] = "✓ Renamed $table → $legacyTable";
            } else {
                $errors[] = "✗ Failed to rename $table: " . $connect->error;
            }
        }
    }
    
    // ====================================================================
    // STEP 2: CREATE CLIENTS TABLE (renamed from customers)
    // ====================================================================
    echo "[STEP 2] Creating clients table...\n";
    
    $createClientsSQL = "
    CREATE TABLE IF NOT EXISTS clients (
        client_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_code VARCHAR(50) UNIQUE,
        
        -- Contact Information
        name VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(20),
        email VARCHAR(100),
        
        -- Address
        billing_address TEXT,
        shipping_address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(10),
        country VARCHAR(100) DEFAULT 'India',
        
        -- Tax Information
        gstin VARCHAR(15),
        pan VARCHAR(10),
        
        -- Credit Terms
        credit_limit DECIMAL(14,2),
        outstanding_balance DECIMAL(14,2) DEFAULT 0,
        payment_terms INT DEFAULT 30 COMMENT 'Payment days',
        
        -- Business Details
        business_type ENUM('Retail','Wholesale','Hospital','Clinic','Distributor','Other') DEFAULT 'Retail',
        
        -- Status & Audit
        status ENUM('ACTIVE','INACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
        notes TEXT,
        created_by INT(10) UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_by INT(10) UNSIGNED,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_client_code (client_code),
        INDEX idx_name (name),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($connect->query($createClientsSQL)) {
        $success[] = "✓ Created clients table with pharmacy-specific fields";
    } else {
        $errors[] = "✗ Failed to create clients table: " . $connect->error;
    }
    
    // ====================================================================
    // STEP 3: CREATE SALES_INVOICES TABLE
    // ====================================================================
    echo "[STEP 3] Creating sales_invoices table...\n";
    
    $createInvoicesSQL = "
    CREATE TABLE IF NOT EXISTS sales_invoices (
        invoice_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        
        -- Invoice Identification
        invoice_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Format: INV-YY-NNNNN',
        
        -- Client Reference
        client_id INT(10) UNSIGNED NOT NULL,
        
        -- Dates
        invoice_date DATE NOT NULL,
        due_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        -- Delivery Address (optional, separate from billing)
        delivery_address TEXT COMMENT 'If different from client address',
        
        -- Financial Summary
        subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(14,2) DEFAULT 0 COMMENT 'Fixed amount discount',
        discount_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Percentage discount',
        gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        gst_percent INT DEFAULT 18 COMMENT 'GST rate in percentage',
        grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
        
        -- Payment Tracking
        paid_amount DECIMAL(14,2) DEFAULT 0,
        due_amount DECIMAL(14,2) DEFAULT 0,
        payment_type VARCHAR(50) COMMENT 'Cash, Card, Cheque, Online',
        payment_place VARCHAR(50) COMMENT 'In India / Out Of India',
        payment_notes TEXT,
        
        -- Status & Workflow
        invoice_status ENUM('DRAFT','SUBMITTED','FULFILLED','CANCELLED') DEFAULT 'DRAFT',
        payment_status ENUM('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID',
        
        -- Audit Trail
        created_by INT(10) UNSIGNED,
        submitted_by INT(10) UNSIGNED,
        submitted_at DATETIME,
        fulfilled_by INT(10) UNSIGNED,
        fulfilled_at DATETIME,
        updated_by INT(10) UNSIGNED,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME COMMENT 'Soft delete',
        
        -- Indexes
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE RESTRICT,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (submitted_by) REFERENCES users(id),
        FOREIGN KEY (fulfilled_by) REFERENCES users(id),
        FOREIGN KEY (updated_by) REFERENCES users(id),
        
        UNIQUE INDEX uidx_invoice_number (invoice_number),
        INDEX idx_client_id (client_id),
        INDEX idx_invoice_date (invoice_date),
        INDEX idx_invoice_status (invoice_status),
        INDEX idx_payment_status (payment_status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($connect->query($createInvoicesSQL)) {
        $success[] = "✓ Created sales_invoices table with full workflow tracking";
    } else {
        $errors[] = "✗ Failed to create sales_invoices table: " . $connect->error;
    }
    
    // ====================================================================
    // STEP 4: CREATE SALES_INVOICE_ITEMS TABLE
    // ====================================================================
    echo "[STEP 4] Creating sales_invoice_items table...\n";
    
    $createItemsSQL = "
    CREATE TABLE IF NOT EXISTS sales_invoice_items (
        item_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        
        -- Reference
        invoice_id INT(10) UNSIGNED NOT NULL,
        product_id INT(10) UNSIGNED NOT NULL,
        batch_id INT(10) UNSIGNED,
        
        -- Quantity & Pricing
        quantity DECIMAL(14,3) NOT NULL,
        unit_rate DECIMAL(14,4) NOT NULL COMMENT 'Selling rate per unit (MRP)',
        purchase_rate DECIMAL(14,4) COMMENT 'Cost price (PTR - not shown on print)',
        
        -- Line Calculation
        line_subtotal DECIMAL(14,2) NOT NULL,
        gst_rate DECIMAL(5,2) DEFAULT 18,
        gst_amount DECIMAL(14,2),
        line_total DECIMAL(14,2) NOT NULL,
        
        -- Additional Info
        batch_number VARCHAR(100),
        expiry_date DATE,
        
        -- Audit
        added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (invoice_id) REFERENCES sales_invoices(invoice_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES product(product_id),
        
        INDEX idx_invoice_id (invoice_id),
        INDEX idx_product_id (product_id),
        INDEX idx_batch_id (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($connect->query($createItemsSQL)) {
        $success[] = "✓ Created sales_invoice_items table (PTR field for seller visibility only)";
    } else {
        $errors[] = "✗ Failed to create sales_invoice_items table: " . $connect->error;
    }
    
    // ====================================================================
    // STEP 5: ADD PURCHASE_RATE TO PRODUCT TABLE
    // ====================================================================
    echo "[STEP 5] Updating product table with purchase_rate...\n";
    
    // First check if column exists
    $checkColumn = $connect->query("SHOW COLUMNS FROM product LIKE 'purchase_rate'");
    if ($checkColumn->num_rows == 0) {
        $addColumnSQL = "ALTER TABLE product ADD COLUMN purchase_rate DECIMAL(14,4) COMMENT 'PTR - Cost price from supplier'";
        if ($connect->query($addColumnSQL)) {
            $success[] = "✓ Added purchase_rate column to product table";
        } else {
            $errors[] = "✗ Failed to add purchase_rate column: " . $connect->error;
        }
    } else {
        $success[] = "✓ purchase_rate column already exists in product table";
    }
    
    // ====================================================================
    // STEP 6: CREATE INVOICE SEQUENCE TABLE (for auto-number generation)
    // ====================================================================
    echo "[STEP 6] Creating invoice_sequence table...\n";
    
    $createSequenceSQL = "
    CREATE TABLE IF NOT EXISTS invoice_sequence (
        year INT(4) NOT NULL,
        next_number INT(5) UNSIGNED NOT NULL DEFAULT 1,
        last_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (year),
        UNIQUE KEY uidx_year (year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if ($connect->query($createSequenceSQL)) {
        // Initialize current year
        $year = date('Y');
        $insert = "INSERT IGNORE INTO invoice_sequence (year, next_number) VALUES ($year, 1)";
        if ($connect->query($insert)) {
            $success[] = "✓ Created invoice_sequence table (for INV-YY-NNNNN generation)";
        }
    } else {
        $errors[] = "✗ Failed to create invoice_sequence table: " . $connect->error;
    }
    
    // ====================================================================
    // STEP 7: ADD SAMPLE CLIENTS DATA
    // ====================================================================
    echo "[STEP 7] Adding sample client data...\n";
    
    $sampleClients = [
        [
            'code' => 'CL001',
            'name' => 'Sunrise Pharmacy',
            'phone' => '9876543210',
            'email' => 'sunrise@pharmacy.com',
            'billing' => 'Shop No. 5, Rajendra Nagar, Nashik',
            'shipping' => 'Shop No. 5, Rajendra Nagar, Nashik',
            'city' => 'Nashik',
            'state' => 'Maharashtra',
            'postal' => '422001',
            'gstin' => '27AABCT1234K1Z0',
            'business' => 'Retail',
            'terms' => 30,
            'limit' => 50000
        ],
        [
            'code' => 'CL002',
            'name' => 'Apollo Healthcare Distribution',
            'phone' => '9123456789',
            'email' => 'apollo@dist.com',
            'billing' => 'Plot 42, Industrial Area, Mumbai',
            'shipping' => 'Plot 42, Industrial Area, Mumbai',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal' => '400012',
            'gstin' => '27AABCT5678K1Z0',
            'business' => 'Distributor',
            'terms' => 45,
            'limit' => 200000
        ],
        [
            'code' => 'CL003',
            'name' => 'City Hospital Pharmacy',
            'phone' => '9988776655',
            'email' => 'pharmacy@cityhospital.com',
            'billing' => '123 Medical Complex, Pune',
            'shipping' => '123 Medical Complex, Pune',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal' => '411001',
            'gstin' => '27AABCT9012K1Z0',
            'business' => 'Hospital',
            'terms' => 60,
            'limit' => 100000
        ]
    ];
    
    foreach ($sampleClients as $client) {
        $stmt = $connect->prepare("
            INSERT INTO clients 
            (client_code, name, contact_phone, email, billing_address, shipping_address, 
             city, state, postal_code, gstin, business_type, payment_terms, credit_limit, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
        ");
        
        $stmt->bind_param(
            'sssssssssssd',
            $client['code'],
            $client['name'],
            $client['phone'],
            $client['email'],
            $client['billing'],
            $client['shipping'],
            $client['city'],
            $client['state'],
            $client['postal'],
            $client['gstin'],
            $client['business'],
            $client['terms'],
            $client['limit']
        );
        
        if ($stmt->execute()) {
            $success[] = "✓ Added sample client: {$client['name']}";
        } else {
            $errors[] = "✗ Failed to add client {$client['name']}: " . $stmt->error;
        }
    }
    
    // ====================================================================
    // STEP 8: VERIFY SCHEMA
    // ====================================================================
    echo "[STEP 8] Verifying schema...\n";
    
    $tables = ['clients', 'sales_invoices', 'sales_invoice_items', 'invoice_sequence'];
    foreach ($tables as $table) {
        $res = $connect->query("SHOW TABLES LIKE '$table'");
        if ($res->num_rows > 0) {
            $success[] = "✓ Verified table: $table";
        }
    }
    
} catch (Exception $e) {
    $errors[] = "✗ Error: " . $e->getMessage();
}

// ====================================================================
// DISPLAY RESULTS
// ====================================================================
echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           MIGRATION RESULTS                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "SUCCESS (" . count($success) . "):\n";
foreach ($success as $s) {
    echo "  $s\n";
}

if (!empty($errors)) {
    echo "\nERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  $e\n";
    }
} else {
    echo "\n  ✓ NO ERRORS - MIGRATION SUCCESSFUL\n";
}

echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                     PHASE 1 COMPLETE                                   ║\n";
echo "║  Ready for Phase 2: Clients CRUD Module                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n";
?>
