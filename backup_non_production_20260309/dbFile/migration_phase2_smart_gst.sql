-- ================================================================
-- PHASE 2 MIGRATION: Smart Invoice with Auto-GST & Multi-Rate Support
-- Safe modifications - all use IF NOT EXISTS/IF COLUMN NOT EXISTS
-- ================================================================

-- 1. PRODUCT TABLE: Add MRP column
ALTER TABLE product ADD COLUMN IF NOT EXISTS expected_mrp DECIMAL(14,2) NULL COMMENT 'Expected MRP for products (can be overridden per batch)';

-- 2. PURCHASE_INVOICES TABLE: Add smart detection columns
ALTER TABLE purchase_invoices 
ADD COLUMN IF NOT EXISTS company_location_state VARCHAR(100) DEFAULT 'Gujarat' COMMENT 'Our company state for GST determination',
ADD COLUMN IF NOT EXISTS supplier_location_state VARCHAR(100) NULL COMMENT 'Supplier state (denormalized for convenience)',
ADD COLUMN IF NOT EXISTS gst_determination_type ENUM('intrastate','interstate') NULL COMMENT 'Auto-detected GST type',
ADD COLUMN IF NOT EXISTS is_gst_registered TINYINT(1) DEFAULT 1 COMMENT 'Is supplier GST registered',
ADD COLUMN IF NOT EXISTS supplier_gstin VARCHAR(15) NULL COMMENT 'Denormalized from suppliers table',
ADD COLUMN IF NOT EXISTS total_cgst DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Central GST total for intra-state',
ADD COLUMN IF NOT EXISTS total_sgst DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'State GST total for intra-state',
ADD COLUMN IF NOT EXISTS total_igst DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Integrated GST total for inter-state',
ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Amount already paid',
ADD COLUMN IF NOT EXISTS payment_mode ENUM('Cash', 'Credit', 'Bank', 'Cheque') DEFAULT 'Credit' COMMENT 'Payment method',
ADD COLUMN IF NOT EXISTS outstanding_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Grand total - paid amount';

-- 3. PURCHASE_INVOICE_ITEMS TABLE: Add per-item tracking
ALTER TABLE purchase_invoice_items 
ADD COLUMN IF NOT EXISTS product_gst_rate DECIMAL(5,2) DEFAULT NULL COMMENT 'Product gst_rate from product table (audit trail)',
ADD COLUMN IF NOT EXISTS cgst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'CGST percentage (intra-state)',
ADD COLUMN IF NOT EXISTS sgst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'SGST percentage (intra-state)',
ADD COLUMN IF NOT EXISTS igst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'IGST percentage (inter-state)',
ADD COLUMN IF NOT EXISTS cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'CGST amount',
ADD COLUMN IF NOT EXISTS sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'SGST amount',
ADD COLUMN IF NOT EXISTS igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'IGST amount',
ADD COLUMN IF NOT EXISTS supplier_quoted_mrp DECIMAL(14,2) NULL COMMENT 'MRP as quoted by supplier for this batch',
ADD COLUMN IF NOT EXISTS our_selling_price DECIMAL(14,2) NULL COMMENT 'Our calculated selling price',
ADD COLUMN IF NOT EXISTS margin_amount DECIMAL(14,2) NULL COMMENT 'MRP - Cost',
ADD COLUMN IF NOT EXISTS margin_percent DECIMAL(6,2) NULL COMMENT 'Margin percentage: (MRP - Cost) / Cost * 100';

-- 4. STOCK_BATCHES TABLE: Add supplier and invoice tracking
ALTER TABLE stock_batches 
ADD COLUMN IF NOT EXISTS supplier_id INT UNSIGNED DEFAULT NULL COMMENT 'Which supplier provided this batch',
ADD COLUMN IF NOT EXISTS invoice_id INT UNSIGNED DEFAULT NULL COMMENT 'Which purchase invoice received this batch',
ADD COLUMN IF NOT EXISTS gst_rate_applied DECIMAL(5,2) DEFAULT NULL COMMENT 'GST rate applied on purchase',
ADD COLUMN IF NOT EXISTS unit_cost_with_tax DECIMAL(14,4) DEFAULT NULL COMMENT 'Cost price after tax (for valuation)',
ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL COMMENT 'User who created batch record';

-- Add foreign keys for stock_batches (safe - check constraint first)
SET @fk_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
WHERE CONSTRAINT_NAME = 'fk_stock_batches_supplier' AND TABLE_NAME = 'stock_batches');

-- Only add if doesn't exist
-- Manually check if needed and add carefully

-- 5. Create COMPANY_SETTINGS table for centralized config
CREATE TABLE IF NOT EXISTS company_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  setting_type VARCHAR(50) DEFAULT 'string',
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default company settings
INSERT IGNORE INTO company_settings (setting_key, setting_value, setting_type, description) VALUES
('company_state', 'Gujarat', 'string', 'Company location state for GST determination'),
('company_gstin', '', 'string', 'Company GSTIN number'),
('company_name', 'Satyam Clinical', 'string', 'Company name'),
('gst_registration_type', '1', 'string', '1=Regular, 2=Composition, 3=Not Registered'),
('default_payment_term_days', '30', 'integer', 'Default credit days for invoices');

-- 6. Update indexes for performance
ALTER TABLE purchase_invoices ADD INDEX IF NOT EXISTS idx_gst_type (gst_determination_type);
ALTER TABLE purchase_invoices ADD INDEX IF NOT EXISTS idx_state (supplier_location_state);
ALTER TABLE purchase_invoice_items ADD INDEX IF NOT EXISTS idx_gst_rate (product_gst_rate);
ALTER TABLE purchase_invoice_items ADD INDEX IF NOT EXISTS idx_margin (margin_percent);
ALTER TABLE stock_batches ADD INDEX IF NOT EXISTS idx_supplier (supplier_id);
ALTER TABLE stock_batches ADD INDEX IF NOT EXISTS idx_invoice (invoice_id);

-- ================================================================
-- VERIFICATION QUERIES (Run these to verify migration)
-- ================================================================
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'product' AND COLUMN_NAME = 'expected_mrp';
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'purchase_invoices' WHERE COLUMN_NAME IN ('company_location_state', 'gst_determination_type');
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'purchase_invoice_items' WHERE COLUMN_NAME IN ('cgst_percent', 'margin_percent');
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'stock_batches' WHERE COLUMN_NAME IN ('supplier_id', 'invoice_id');
