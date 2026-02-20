-- Migration: Implement Credit Control
-- Version: 004
-- Description: Add credit control tables and payment tracking
-- Date: February 2026

-- NOTE: Customers table columns not modified as it doesn't exist in this system
-- The payment and credit log tables are standalone and work with any system

-- Create customer credit history log (standalone)
CREATE TABLE IF NOT EXISTS `customer_credit_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `old_limit` DECIMAL(12, 2),
    `new_limit` DECIMAL(12, 2),
    `old_status` VARCHAR(20),
    `new_status` VARCHAR(20),
    `changed_by` INT UNSIGNED,
    `reason` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_credit_log_user` FOREIGN KEY (`changed_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for credit limit changes';


-- Create customer payment tracking
CREATE TABLE IF NOT EXISTS `customer_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED,
    `payment_amount` DECIMAL(12, 2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_reference` VARCHAR(100),
    `payment_date` DATE NOT NULL DEFAULT CURDATE(),
    `recorded_by` INT UNSIGNED,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_payment_date` (`payment_date`),
    CONSTRAINT `fk_payment_recorder` FOREIGN KEY (`recorded_by`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer payment receipts';

-- Create invoice payments table
CREATE TABLE IF NOT EXISTS `invoice_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED,
    `invoice_id` INT UNSIGNED,
    `amount_due` DECIMAL(12, 2) NOT NULL,
    `amount_paid` DECIMAL(12, 2) DEFAULT 0,
    `due_date` DATE NOT NULL,
    `invoice_date` DATE NOT NULL,
    `customer_id` INT UNSIGNED,
    `payment_status` ENUM('UNPAID', 'PARTIAL', 'PAID', 'OVERDUE', 'CANCELLED') DEFAULT 'UNPAID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_due_date` (`due_date`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Invoice payment tracking';

-- Placeholder VIEWs
CREATE OR REPLACE VIEW `v_customer_credit_exposure` AS
SELECT 'NO CUSTOMERS TABLE - VIEWS SKIPPED' as note;

CREATE OR REPLACE VIEW `v_overdue_invoices` AS
SELECT 'NO CUSTOMERS TABLE - VIEWS SKIPPED' as note;
