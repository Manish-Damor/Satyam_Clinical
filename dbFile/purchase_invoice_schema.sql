-- Purchase Invoice and related tables for Satyam_Clinical
-- Run this migration on a fresh schema or after backing up existing DB

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `purchase_invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `invoice_no` VARCHAR(100) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `po_reference` VARCHAR(100) DEFAULT NULL,
  `grn_reference` VARCHAR(100) DEFAULT NULL,
  `payment_terms` VARCHAR(255) DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT 'INR',
  `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_tax` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `freight` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `round_off` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Draft','Received','Matched','Approved','Paid','Cancelled') NOT NULL DEFAULT 'Draft',
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `matched_by` INT DEFAULT NULL,
  `matched_at` DATETIME DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_supplier` (`supplier_id`),
  UNIQUE KEY `uq_supplier_invoice` (`supplier_id`,`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_invoice_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) DEFAULT NULL,
  `hsn_code` VARCHAR(50) DEFAULT NULL,
  `batch_no` VARCHAR(100) DEFAULT NULL,
  `manufacture_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `qty` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `free_qty` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `mrp` DECIMAL(14,2) DEFAULT NULL,
  `discount_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_invoice` (`invoice_id`),
  CONSTRAINT `fk_inv_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goods Received Note (GRN) and items. GRN is created at receive time and later matched with invoice.
CREATE TABLE IF NOT EXISTS `goods_received` (
  `grn_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `po_id` INT UNSIGNED DEFAULT NULL,
  `supplier_id` INT UNSIGNED DEFAULT NULL,
  `grn_no` VARCHAR(100) DEFAULT NULL,
  `received_by` INT DEFAULT NULL,
  `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`grn_id`),
  INDEX `idx_grn_po` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `grn_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `grn_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(100) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `qty_received` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  INDEX `idx_grn` (`grn_id`),
  CONSTRAINT `fk_grn_items_grn` FOREIGN KEY (`grn_id`) REFERENCES `goods_received`(`grn_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock batches table to track batch-level inventory
CREATE TABLE IF NOT EXISTS `stock_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(100) NOT NULL,
  `manufacture_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `qty` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `mrp` DECIMAL(14,2) DEFAULT NULL,
  `cost_price` DECIMAL(14,4) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_batch` (`product_id`,`batch_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplier payments / AP ledger
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `method` VARCHAR(50) DEFAULT NULL,
  `reference` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_payment_supplier` (`supplier_id`),
  INDEX `idx_payment_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Example: populate minimal configuration (uncomment to use)
-- INSERT INTO `purchase_invoices` (`supplier_id`,`invoice_no`,`invoice_date`,`subtotal`,`grand_total`) VALUES (1,'INV-0001',CURDATE(),0,0);
