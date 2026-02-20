-- Migration: Enhance Stock Movements Table  
-- Version: 003
-- Description: Upgrade stock_movements with warehouse, balance, and reference tracking
-- Date: February 2026

-- Note: Table already exists in the system, we ADD missing columns for balance and movement tracking

-- Note: stock_movements table already exists, we only ADD new columns
-- Existing columns: movement_id, product_id, batch_id, warehouse_id, movement_type, quantity, movement_date, reference_number, reference_type, etc.

-- Add balance tracking if not exists
ALTER TABLE `stock_movements` 
ADD COLUMN IF NOT EXISTS `balance_before` DECIMAL(10, 2) AFTER `quantity`,
ADD COLUMN IF NOT EXISTS `balance_after` DECIMAL(10, 2) AFTER `balance_before`;

-- Add reference tracking if not exists
ALTER TABLE `stock_movements` 
ADD COLUMN IF NOT EXISTS `reference_id` INT UNSIGNED AFTER `reference_type`;

-- Add recorded_by/user tracking if not exists (created_by already exists in table)
ALTER TABLE `stock_movements` 
ADD COLUMN IF NOT EXISTS `recorded_by` INT UNSIGNED AFTER `reference_id`;

-- Update existing null movements to have movement_type if they don't
-- This prevents violations of the NOT NULL constraint
-- SKIPPED: The existing table already has movement_type populated
/*
UPDATE `stock_movements` 
SET `movement_type` = 'INBOUND' 
WHERE `movement_type` IS NULL AND EXISTS (
    SELECT 1 FROM `goods_received` WHERE `goods_received`.`id` = `stock_movements`.`reference_id`
);

UPDATE `stock_movements` 
SET `movement_type` = 'OUTBOUND' 
WHERE `movement_type` IS NULL AND EXISTS (
    SELECT 1 FROM `orders` WHERE `orders`.`id` = `stock_movements`.`reference_id`
);

UPDATE `stock_movements` 
SET `movement_type` = 'ADJUSTMENT' 
WHERE `movement_type` IS NULL;
*/

-- Create VIEW for stock summary by batch
CREATE OR REPLACE VIEW `v_batch_stock_summary` AS
SELECT 
    pb.batch_id,
    pb.product_id,
    p.product_name,
    pb.batch_number,
    pb.manufacturing_date as mfg_date,
    pb.expiry_date as exp_date,
    pb.available_quantity as quantity_available,
    DATEDIFF(pb.expiry_date, CURDATE()) as days_to_expiry,
    CASE 
        WHEN pb.expiry_date < CURDATE() THEN 'EXPIRED'
        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 30 THEN 'CRITICAL'
        WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 90 THEN 'WARNING'
        ELSE 'OK'
    END as expiry_status,
    pb.purchase_rate,
    (pb.available_quantity * pb.purchase_rate) as stock_value
FROM `product_batches` pb
JOIN `product` p ON pb.product_id = p.product_id
WHERE pb.status != 'Damaged'
ORDER BY pb.expiry_date ASC;

-- Create VIEW for recent movements
CREATE OR REPLACE VIEW `v_stock_movement_recent` AS
SELECT 
    sm.movement_id as id,
    sm.product_id,
    p.product_name,
    sm.batch_id,
    pb.batch_number,
    sm.movement_type,
    sm.quantity,
    sm.balance_before,
    sm.balance_after,
    sm.reference_type,
    sm.reference_id,
    u.username as recorded_by_name,
    COALESCE(sm.movement_date, sm.created_at) as recorded_at
FROM `stock_movements` sm
JOIN `product` p ON sm.product_id = p.product_id
JOIN `product_batches` pb ON sm.batch_id = pb.batch_id
LEFT JOIN `users` u ON sm.recorded_by = u.user_id
ORDER BY COALESCE(sm.movement_date, sm.created_at) DESC
LIMIT 1000;
