-- Migration: Create Audit Logs Table
-- Version: 002
-- Description: Implements comprehensive audit trail for all financial/sensitive operations
-- Date: February 2026

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique audit log ID',
    `table_name` VARCHAR(100) NOT NULL COMMENT 'Table being audited',
    `record_id` INT UNSIGNED NOT NULL COMMENT 'Primary key of affected record',
    `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL COMMENT 'Type of operation',
    `user_id` INT UNSIGNED COMMENT 'User who performed the action',
    `old_data` LONGTEXT COMMENT 'JSON serialized old values (for UPDATE/DELETE)',
    `new_data` LONGTEXT COMMENT 'JSON serialized new values (for INSERT/UPDATE)',
    `changes_summary` VARCHAR(255) COMMENT 'Summary of changes for quick review',
    `ip_address` VARCHAR(45) COMMENT 'IP address of user',
    `user_agent` VARCHAR(255) COMMENT 'Browser user agent',
    `action_timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When action occurred',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_table_record` (`table_name`, `record_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_action_timestamp` (`action_timestamp`),
    INDEX `idx_table_action` (`table_name`, `action`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Complete audit trail of all financial and sensitive operations';

-- Create VIEW for recent audit trail
CREATE OR REPLACE VIEW `v_audit_trail_recent` AS
SELECT 
    al.id,
    al.table_name,
    al.record_id,
    al.action,
    u.username as user_name,
    al.ip_address,
    al.action_timestamp,
    al.changes_summary
FROM `audit_logs` al
LEFT JOIN `users` u ON al.user_id = u.user_id
ORDER BY al.action_timestamp DESC
LIMIT 500;
