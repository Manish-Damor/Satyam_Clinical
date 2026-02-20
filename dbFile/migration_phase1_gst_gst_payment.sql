-- ================================================================
-- PHASE 1 MIGRATION: GST Split, MRP, Payment Tracking
-- ================================================================
-- Changes to purchase_invoices table
ALTER TABLE purchase_invoices 
ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER grand_total,
ADD COLUMN IF NOT EXISTS payment_mode ENUM('Cash', 'Credit', 'Bank', 'Cheque') DEFAULT 'Credit' AFTER paid_amount,
ADD COLUMN IF NOT EXISTS outstanding_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER payment_mode;

-- Changes to purchase_invoice_items table
ALTER TABLE purchase_invoice_items 
ADD COLUMN IF NOT EXISTS cgst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER tax_rate,
ADD COLUMN IF NOT EXISTS sgst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER cgst_percent,
ADD COLUMN IF NOT EXISTS igst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER sgst_percent,
ADD COLUMN IF NOT EXISTS cgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER igst_percent,
ADD COLUMN IF NOT EXISTS sgst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER cgst_amount,
ADD COLUMN IF NOT EXISTS igst_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER sgst_amount;

-- Create index for better performance on invoice lookups
ALTER TABLE purchase_invoices ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE purchase_invoices ADD INDEX IF NOT EXISTS idx_invoice_date (invoice_date);

-- Ensure invoice_no is unique per supplier (already configured, just confirming)
-- UNIQUE KEY already exists: uq_supplier_invoice (supplier_id,invoice_no)
