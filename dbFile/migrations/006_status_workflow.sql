-- Migration: Add Status & Workflow Columns
-- Version: 006
-- Description: Add workflow status columns and inventory_adjustments table
-- Date: February 2026

-- Enhance purchase_orders table with status columns
ALTER TABLE `purchase_orders` 
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'DRAFT' COMMENT 'DRAFT, SUBMITTED, APPROVED, POSTED, CANCELLED',
ADD COLUMN IF NOT EXISTS `submitted_by` INT UNSIGNED COMMENT 'User who submitted PO',
ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME COMMENT 'When PO was submitted',
ADD COLUMN IF NOT EXISTS `approved_by` INT UNSIGNED COMMENT 'Manager who approved',
ADD COLUMN IF NOT EXISTS `approval_remarks` TEXT,
ADD INDEX IF NOT EXISTS `idx_po_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_po_submitted` (`submitted_at`);

-- Enhance goods_received table with status columns
ALTER TABLE `goods_received` 
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'DRAFT' COMMENT 'DRAFT, SUBMITTED, APPROVED, POSTED, DELIVERED',
ADD COLUMN IF NOT EXISTS `quality_check_status` ENUM('PENDING', 'PASSED', 'FAILED', 'CONDITIONAL') DEFAULT 'PENDING',
ADD COLUMN IF NOT EXISTS `submitted_by` INT UNSIGNED,
ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME,
ADD COLUMN IF NOT EXISTS `approved_by` INT UNSIGNED,
ADD COLUMN IF NOT EXISTS `approved_at` DATETIME,
ADD INDEX IF NOT EXISTS `idx_grn_status` (`status`);

-- Enhance purchase_invoices table with status columns
ALTER TABLE `purchase_invoices` 
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'DRAFT' COMMENT 'DRAFT, SUBMITTED, APPROVED, POSTED, PAID, CANCELLED',
ADD COLUMN IF NOT EXISTS `payment_status` ENUM('UNPAID', 'PARTIAL', 'PAID') DEFAULT 'UNPAID',
ADD COLUMN IF NOT EXISTS `submitted_by` INT UNSIGNED,
ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME,
ADD COLUMN IF NOT EXISTS `approved_by` INT UNSIGNED,
ADD COLUMN IF NOT EXISTS `approved_at` DATETIME,
ADD INDEX IF NOT EXISTS `idx_invoice_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_payment_status` (`payment_status`),
ADD INDEX IF NOT EXISTS `idx_supplier_invoice` (`supplier_id`, `status`);

-- Enhance orders table with workflow columns (only add columns, skip index for order_status if not exists)
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `order_status` ENUM('DRAFT', 'CONFIRMED', 'FULFILLED', 'CANCELLED') DEFAULT 'DRAFT',
ADD COLUMN IF NOT EXISTS `payment_status` ENUM('UNPAID', 'PARTIAL', 'PAID') DEFAULT 'UNPAID',
ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME COMMENT 'When order was submitted',
ADD COLUMN IF NOT EXISTS `fulfilled_at` DATETIME COMMENT 'When order was fulfilled',
ADD COLUMN IF NOT EXISTS `created_by` INT UNSIGNED COMMENT 'User who created order',
ADD COLUMN IF NOT EXISTS `updated_by` INT UNSIGNED COMMENT 'Last user to update order';

-- Create inventory_adjustments table for stock corrections
CREATE TABLE IF NOT EXISTS `inventory_adjustments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `batch_id` INT UNSIGNED NOT NULL,
    `adjustment_type` ENUM('IN', 'OUT', 'CORRECTION') NOT NULL,
    `quantity_adjusted` DECIMAL(10, 2) NOT NULL,
    `adjustment_reason` VARCHAR(255) NOT NULL,
    `adjustment_notes` TEXT,
    `previous_qty` DECIMAL(10, 2),
    `new_qty` DECIMAL(10, 2),
    `status` ENUM('DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'REJECTED', 'CANCELLED') DEFAULT 'DRAFT',
    `submitted_at` DATETIME,
    `submitted_by` INT UNSIGNED,
    `approved_at` DATETIME,
    `approved_by` INT UNSIGNED,
    `posted_at` DATETIME,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_batch_id` (`batch_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_by` (`created_by`),
    CONSTRAINT `fk_adj_product` FOREIGN KEY (`product_id`) 
        REFERENCES `product` (`product_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adj_batch` FOREIGN KEY (`batch_id`) 
        REFERENCES `product_batches` (`batch_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adj_creator` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_adj_approver` FOREIGN KEY (`approved_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stock adjustments with full approval workflow';

-- Create simple VIEW for pending approvals (actual logic in service layer)
CREATE OR REPLACE VIEW `v_pending_approvals` AS
SELECT 
    'IMPLEMENTATION_NOTE' as note,
    'Pending approvals require service layer querying across multiple tables' as description;
