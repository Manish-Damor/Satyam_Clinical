-- Migration: Create Approval Logs Table
-- Version: 001
-- Description: Implements approval workflow tracking for purchase orders, GRNs, invoices, etc.
-- Date: February 2026

CREATE TABLE IF NOT EXISTS `approval_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique approval log ID',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'Type: PO, GRN, INVOICE, SALES_ORDER, SUPPLIER_PAYMENT',
    `entity_id` INT UNSIGNED NOT NULL COMMENT 'ID of the entity being approved (po_id, grn_id, etc)',
    `status_from` VARCHAR(20) NOT NULL DEFAULT 'DRAFT' COMMENT 'Previous status: DRAFT, SUBMITTED, etc',
    `status_to` VARCHAR(20) NOT NULL COMMENT 'New status: SUBMITTED, APPROVED, REJECTED, POSTED, CANCELLED',
    `action` VARCHAR(50) NOT NULL COMMENT 'Action taken: SUBMIT, APPROVE, REJECT, POST, CANCEL',
    `approved_by` INT UNSIGNED NOT NULL COMMENT 'User ID who performed the action',
    `approved_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of approval action',
    `remarks` TEXT COMMENT 'Approval remarks or rejection reasons',
    `ip_address` VARCHAR(45) COMMENT 'IP address of approver',
    `user_agent` VARCHAR(255) COMMENT 'Browser user agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_approved_by` (`approved_by`),
    INDEX `idx_approved_at` (`approved_at`),
    INDEX `idx_status_to` (`status_to`),
    CONSTRAINT `fk_approval_user` FOREIGN KEY (`approved_by`) 
        REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks all approval/rejection actions across the system';
