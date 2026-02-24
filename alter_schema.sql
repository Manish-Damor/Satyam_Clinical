-- Drop invoice_status column (remove unnecessary field)
ALTER TABLE sales_invoices DROP COLUMN invoice_status;

-- Add new tracking columns
ALTER TABLE sales_invoices ADD COLUMN payment_method VARCHAR(50) AFTER payment_type;
ALTER TABLE sales_invoices ADD COLUMN payment_notes TEXT AFTER payment_method;
ALTER TABLE sales_invoices ADD COLUMN payment_received_date DATETIME AFTER paid_amount;
ALTER TABLE sales_invoices ADD COLUMN is_cancelled TINYINT DEFAULT 0 AFTER updated_at;

-- Confirm changes
DESCRIBE sales_invoices;
