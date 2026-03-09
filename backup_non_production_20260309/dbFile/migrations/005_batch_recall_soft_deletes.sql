-- Migration: Batch Recall & Soft Deletes
-- Version: 005
-- Description: Enable batch recall tracking and soft delete support for financial tables
-- Date: February 2026

-- Create batch recall table for product safety/tracking
CREATE TABLE IF NOT EXISTS `batch_recalls` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique recall ID',
    `batch_id` INT UNSIGNED NOT NULL COMMENT 'Batch being recalled',
    `product_id` INT UNSIGNED NOT NULL COMMENT 'Product reference',
    `recall_reason` VARCHAR(255) NOT NULL COMMENT 'Reason for recall: DEFECT, EXPIRY_ALERT, CONTAMINANT, QUALITY, REGULATORY',
    `recall_severity` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM' COMMENT 'Recall severity',
    `recall_date` DATE NOT NULL DEFAULT (CURDATE()) COMMENT 'Date recall was initiated',
    `recall_initiated_by` INT UNSIGNED COMMENT 'User who initiated recall',
    `status` ENUM('ACTIVE', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE' COMMENT 'Recall status',
    `description` TEXT COMMENT 'Detailed description of recall',
    `internal_notes` TEXT COMMENT 'Internal notes',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_batch_id` (`batch_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_recall_date` (`recall_date`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_recall_batch` FOREIGN KEY (`batch_id`) 
        REFERENCES `product_batches` (`batch_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_recall_product` FOREIGN KEY (`product_id`) 
        REFERENCES `product` (`product_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_recall_user` FOREIGN KEY (`recall_initiated_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track product batch recalls for safety and compliance';

-- Create batch sales mapping for recall queries
CREATE TABLE IF NOT EXISTS `batch_sales_map` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique mapping ID',
    `batch_id` INT UNSIGNED NOT NULL COMMENT 'Batch sold',
    `order_id` INT UNSIGNED NOT NULL COMMENT 'Sales order',
    `order_item_id` INT UNSIGNED COMMENT 'Line item in order',
    `quantity_sold` DECIMAL(10, 2) NOT NULL COMMENT 'Quantity of this batch in order',
    `sale_date` DATE NOT NULL COMMENT 'Sale date',
    `customer_id` INT UNSIGNED COMMENT 'Customer who purchased',
    `customer_name` VARCHAR(255) COMMENT 'Customer name (denormalized)',
    `customer_contact` VARCHAR(20) COMMENT 'Customer phone (denormalized)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_batch_id` (`batch_id`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_sale_date` (`sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Maps batches to sales orders for quick recall query';

-- Add soft delete to purchase_orders if not exists
ALTER TABLE `purchase_orders` 
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME COMMENT 'Soft delete timestamp',
ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);

-- Add soft delete to goods_received if not exists  
ALTER TABLE `goods_received` 
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME COMMENT 'Soft delete timestamp',
ADD INDEX IF NOT EXISTS `idx_deleted_at_gr` (`deleted_at`);

-- Add soft delete to purchase_invoices if not exists
ALTER TABLE `purchase_invoices` 
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME COMMENT 'Soft delete timestamp',
ADD INDEX IF NOT EXISTS `idx_deleted_at_pi` (`deleted_at`);

-- Add soft delete to supplier_payments if not exists
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique payment ID',
    `supplier_id` INT UNSIGNED NOT NULL COMMENT 'Supplier paid',
    `invoice_id` INT UNSIGNED COMMENT 'Purchase invoice being paid',
    `payment_amount` DECIMAL(12, 2) NOT NULL COMMENT 'Amount paid',
    `payment_method` VARCHAR(50) NOT NULL COMMENT 'CASH, CHEQUE, NEFT, BANK_TRANSFER, RTGS',
    `payment_reference` VARCHAR(100) COMMENT 'Cheque number, transaction ID, etc',
    `payment_date` DATE NOT NULL DEFAULT (CURDATE()) COMMENT 'Payment date',
    `recorded_by` INT UNSIGNED COMMENT 'User who recorded payment',
    `remarks` TEXT COMMENT 'Payment remarks',
    `reconciled` BOOLEAN DEFAULT FALSE COMMENT 'Reconciliation status',
    `reconciled_by` INT UNSIGNED COMMENT 'User who reconciled',
    `reconciled_at` DATETIME COMMENT 'Reconciliation timestamp',
    `payment_status` ENUM('PENDING', 'PROCESSED', 'RECONCILED', 'REVERSED') DEFAULT 'PENDING' 
        COMMENT 'Status of payment',
    `deleted_at` DATETIME COMMENT 'Soft delete timestamp',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_supplier_id` (`supplier_id`),
    INDEX `idx_invoice_id` (`invoice_id`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_payment_supplier` FOREIGN KEY (`supplier_id`) 
        REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) 
        REFERENCES `purchase_invoices` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_recorder` FOREIGN KEY (`recorded_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_reconciler` FOREIGN KEY (`reconciled_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Supplier payment tracking with full audit';

-- VIEWs removed - they would need parameters or specific batch lookup
-- Use these queries manually as needed during implementation
