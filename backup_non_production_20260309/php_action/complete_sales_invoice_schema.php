<?php
require './constant/connect.php';

echo "Checking users table structure...\n";

// Check users table
$res = $connect->query("SHOW COLUMNS FROM users");
while($col = $res->fetch_assoc()) {
    echo "  • {$col['Field']}: {$col['Type']}\n";
}

echo "\n\nCreating sales_invoices table with adjusted foreign keys...\n";

// Recreate sales_invoices with working foreign keys
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
    
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE RESTRICT,
    
    UNIQUE INDEX uidx_invoice_number (invoice_number),
    INDEX idx_client_id (client_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_invoice_status (invoice_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($connect->query($createInvoicesSQL)) {
    echo "✓ Created sales_invoices table successfully\n";
} else {
    echo "✗ Error: " . $connect->error . "\n";
}

// Create sales_invoice_items
echo "\nCreating sales_invoice_items table...\n";

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
    echo "✓ Created sales_invoice_items table successfully\n";
} else {
    echo "✗ Error: " . $connect->error . "\n";
}

// Create invoice_sequence
echo "\nCreating invoice_sequence table...\n";

$createSequenceSQL = "
CREATE TABLE IF NOT EXISTS invoice_sequence (
    year INT(4) NOT NULL,
    next_number INT(5) UNSIGNED NOT NULL DEFAULT 1,
    last_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($connect->query($createSequenceSQL)) {
    $year = date('Y');
    $insert = "INSERT IGNORE INTO invoice_sequence (year, next_number) VALUES ($year, 1)";
    if ($connect->query($insert)) {
        echo "✓ Created invoice_sequence table successfully\n";
    }
} else {
    echo "✗ Error: " . $connect->error . "\n";
}

// Verify purchase_rate in product
echo "\nVerifying purchase_rate in product table...\n";
$checkColumn = $connect->query("SHOW COLUMNS FROM product LIKE 'purchase_rate'");
if ($checkColumn->num_rows == 0) {
    $addColumnSQL = "ALTER TABLE product ADD COLUMN purchase_rate DECIMAL(14,4) COMMENT 'PTR - Cost price from supplier'";
    if ($connect->query($addColumnSQL)) {
        echo "✓ Added purchase_rate column to product table\n";
    } else {
        echo "✗ Error: " . $connect->error . "\n";
    }
} else {
    echo "✓ purchase_rate column already exists\n";
}

// Verify tables exist
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║             SCHEMA VERIFICATION                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

$tables = ['clients', 'sales_invoices', 'sales_invoice_items', 'invoice_sequence'];
foreach ($tables as $table) {
    $res = $connect->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        echo "✓ $table\n";
    }
}

echo "\n✓ PHASE 1: DATABASE MIGRATION COMPLETE\n";
?>
