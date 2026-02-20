-- ============================================================
-- PROFESSIONAL PHARMACY INVENTORY ERP SYSTEM SCHEMA
-- ============================================================
-- Complete database schema for a professional pharmaceutical 
-- inventory management system with batch tracking, stock 
-- movement, supplier management, and compliance features.
-- ============================================================

USE satyam_clinical;

-- ============================================================
-- 1. SUPPLIERS TABLE (for purchase order management)
-- ============================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplier_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  -- Company Information
  `supplier_name` VARCHAR(150) NOT NULL,
  `supplier_code` VARCHAR(50) UNIQUE,
  `company_name` VARCHAR(150),
  
  -- Contact Information
  `contact_person` VARCHAR(100),
  `email` VARCHAR(100),
  `phone` VARCHAR(20) NOT NULL,
  `alternate_phone` VARCHAR(20),
  
  -- Address
  `address` TEXT NOT NULL,
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `pincode` VARCHAR(10),
  `country` VARCHAR(100) DEFAULT 'India',
  
  -- GST & Tax
  `gst_number` VARCHAR(15),
  `pan_number` VARCHAR(10),
  
  -- Payment Terms
  `credit_days` INT DEFAULT 30,
  `payment_terms` VARCHAR(255),
  
  -- Status
  `supplier_status` ENUM('Active','Inactive','Blocked') DEFAULT 'Active',
  `is_verified` TINYINT(1) DEFAULT 0,
  
  -- Audit Columns
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `idx_supplier_code` (`supplier_code`),
  KEY `idx_supplier_name` (`supplier_name`),
  KEY `idx_status` (`supplier_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `supplier_code`, `phone`, `address`, `city`, `state`, `supplier_status`) VALUES
(1, 'Cipla Limited', 'SUP001', '9876543210', '123 Pharma Street', 'Mumbai', 'Maharashtra', 'Active'),
(2, 'Mankind Pharma', 'SUP002', '9876543211', '456 Medical Road', 'Delhi', 'Delhi', 'Active'),
(3, 'Sun Pharmaceuticals', 'SUP003', '9876543212', '789 Science Park', 'Ahmedabad', 'Gujarat', 'Active');

-- ============================================================
-- 2. ENHANCED PRODUCT TABLE (with all necessary fields)
-- ============================================================
-- Note: The product table already exists with the new structure.
-- Verify it has these columns:
-- product_id, product_name, content, brand_id, categories_id,
-- product_type, unit_type, pack_size, hsn_code, gst_rate,
-- reorder_level, status, created_at, updated_at

-- ============================================================
-- 3. PRODUCT BATCHES TABLE (for batch-level tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS `product_batches` (
  `batch_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `product_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED,
  
  -- Batch Information
  `batch_number` VARCHAR(50) NOT NULL UNIQUE,
  `expiry_date` DATE NOT NULL,
  `manufacturing_date` DATE,
  
  -- Stock Management
  `available_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_quantity` INT UNSIGNED DEFAULT 0,
  `damaged_quantity` INT UNSIGNED DEFAULT 0,
  
  -- Pricing
  `purchase_rate` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) NOT NULL,
  
  -- Status
  `status` ENUM('Active','Expired','Blocked','Damaged') DEFAULT 'Active',
  
  -- Audit
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`batch_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_batch_number` (`batch_number`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_status` (`status`),
  
  CONSTRAINT `fk_product_batches_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT,
    
  CONSTRAINT `fk_product_batches_supplier`
    FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`supplier_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. STOCK MOVEMENTS TABLE (for audit trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `product_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED,
  
  -- Movement Details
  `movement_type` ENUM('Purchase','Sales','Adjustment','Return','Damage','Sample','Expiry') NOT NULL,
  `quantity` INT NOT NULL,
  `movement_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  -- Reference Information
  `reference_number` VARCHAR(100),  -- PO#, Invoice#, etc.
  `reference_type` VARCHAR(50),  -- PurchaseOrder, Invoice, AdjustmentNote
  
  -- Notes & Reason
  `reason` VARCHAR(255),
  `notes` TEXT,
  
  -- User Information
  `created_by` INT UNSIGNED,
  `verified_by` INT UNSIGNED,
  
  -- Audit
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`movement_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_reference_number` (`reference_number`),
  
  CONSTRAINT `fk_movements_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT,
    
  CONSTRAINT `fk_movements_batch`
    FOREIGN KEY (`batch_id`)
    REFERENCES `product_batches` (`batch_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PURCHASE ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `po_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `po_number` VARCHAR(50) NOT NULL UNIQUE,
  `po_date` DATE NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  
  -- Expected Details
  `expected_delivery_date` DATE,
  `delivery_location` VARCHAR(255),
  
  -- Costs
  `subtotal` DECIMAL(12,2) DEFAULT 0,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0,
  `discount_amount` DECIMAL(10,2) DEFAULT 0,
  `gst_percentage` DECIMAL(5,2) DEFAULT 0,
  `gst_amount` DECIMAL(10,2) DEFAULT 0,
  `other_charges` DECIMAL(10,2) DEFAULT 0,
  `grand_total` DECIMAL(12,2) DEFAULT 0,
  
  -- Status
  `po_status` ENUM('Draft','Submitted','Approved','Partial','Received','Cancelled') DEFAULT 'Draft',
  `payment_status` ENUM('Not Due','Due','Partial','Paid','Overdue') DEFAULT 'Not Due',
  
  -- Notes
  `notes` TEXT,
  `delete_status` TINYINT(1) DEFAULT 0,
  
  -- Audit
  `created_by` INT UNSIGNED,
  `approved_by` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `uk_po_number` (`po_number`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_po_date` (`po_date`),
  KEY `idx_po_status` (`po_status`),
  KEY `idx_delete_status` (`delete_status`),
  
  CONSTRAINT `fk_po_supplier`
    FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`supplier_id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. PURCHASE ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `po_items` (
  `po_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `po_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  
  -- Item Details
  `quantity_ordered` INT UNSIGNED NOT NULL,
  `quantity_received` INT UNSIGNED DEFAULT 0,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL,
  
  -- Batch Details (if known at PO time)
  `batch_number` VARCHAR(50),
  `expiry_date` DATE,
  `manufacturing_date` DATE,
  
  -- Item Status
  `item_status` ENUM('Pending','Partial','Received','Cancelled') DEFAULT 'Pending',
  
  -- Notes
  `notes` VARCHAR(255),
  
  -- Audit
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`po_item_id`),
  KEY `idx_po_id` (`po_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_item_status` (`item_status`),
  
  CONSTRAINT `fk_po_items_po`
    FOREIGN KEY (`po_id`)
    REFERENCES `purchase_orders` (`po_id`)
    ON DELETE CASCADE,
    
  CONSTRAINT `fk_po_items_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. INVENTORY ADJUSTMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `inventory_adjustments` (
  `adjustment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `adjustment_number` VARCHAR(50) NOT NULL UNIQUE,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED,
  
  -- Adjustment Details
  `adjustment_type` ENUM('Physical Count','Damage','Loss','Excess','Return','Other') NOT NULL,
  `quantity_variance` INT NOT NULL,  -- Positive: added, Negative: removed
  `old_quantity` INT,
  `new_quantity` INT,
  
  -- Reason & Notes
  `reason` VARCHAR(255) NOT NULL,
  `notes` TEXT,
  
  -- Approval
  `requested_by` INT UNSIGNED,
  `approved_by` INT UNSIGNED,
  `approval_status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  
  -- Date
  `adjustment_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`adjustment_id`),
  UNIQUE KEY `uk_adjustment_number` (`adjustment_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_adjustment_type` (`adjustment_type`),
  KEY `idx_approval_status` (`approval_status`),
  
  CONSTRAINT `fk_adjustment_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT,
    
  CONSTRAINT `fk_adjustment_batch`
    FOREIGN KEY (`batch_id`)
    REFERENCES `product_batches` (`batch_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. REORDER MANAGEMENT TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `reorder_management` (
  `reorder_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `product_id` INT UNSIGNED NOT NULL,
  
  -- Reorder Details
  `reorder_level` INT UNSIGNED NOT NULL,
  `reorder_quantity` INT UNSIGNED NOT NULL,
  
  -- Current Status
  `current_stock` INT UNSIGNED DEFAULT 0,
  `is_low_stock` TINYINT(1) DEFAULT 0,
  `alert_date` DATETIME,
  
  -- Preferred Supplier
  `preferred_supplier_id` INT UNSIGNED,
  
  -- Status
  `is_active` TINYINT(1) DEFAULT 1,
  
  -- Audit
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`reorder_id`),
  UNIQUE KEY `uk_product_id` (`product_id`),
  KEY `idx_is_low_stock` (`is_low_stock`),
  KEY `idx_preferred_supplier_id` (`preferred_supplier_id`),
  
  CONSTRAINT `fk_reorder_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT,
    
  CONSTRAINT `fk_reorder_supplier`
    FOREIGN KEY (`preferred_supplier_id`)
    REFERENCES `suppliers` (`supplier_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. EXPIRY MANAGEMENT TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `expiry_tracking` (
  `expiry_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  `batch_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  
  -- Expiry Details
  `batch_number` VARCHAR(50),
  `expiry_date` DATE NOT NULL,
  `days_remaining` INT,
  
  -- Alert Status
  `alert_level` ENUM('Green','Yellow','Red','Expired') DEFAULT 'Green',
  `alert_date` DATETIME,
  
  -- Quantity
  `stock_quantity` INT UNSIGNED,
  
  -- Action
  `action_taken` VARCHAR(255),
  `action_date` DATETIME,
  
  -- Audit
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`expiry_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_alert_level` (`alert_level`),
  
  CONSTRAINT `fk_expiry_batch`
    FOREIGN KEY (`batch_id`)
    REFERENCES `product_batches` (`batch_id`)
    ON DELETE CASCADE,
    
  CONSTRAINT `fk_expiry_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. INVENTORY SUMMARY VIEW
-- ============================================================
-- This view provides a comprehensive inventory summary
CREATE OR REPLACE VIEW `v_inventory_summary` AS
SELECT 
  p.product_id,
  p.product_name,
  b.brand_name,
  c.categories_name,
  p.pack_size,
  p.hsn_code,
  p.gst_rate,
  
  COALESCE(SUM(pb.available_quantity), 0) AS total_stock,
  COALESCE(SUM(pb.reserved_quantity), 0) AS reserved_stock,
  COALESCE(SUM(pb.damaged_quantity), 0) AS damaged_stock,
  
  COUNT(DISTINCT CASE WHEN pb.status = 'Active' THEN pb.batch_id END) AS active_batches,
  COUNT(DISTINCT CASE WHEN pb.status = 'Expired' THEN pb.batch_id END) AS expired_batches,
  
  MIN(pb.expiry_date) AS nearest_expiry,
  
  p.reorder_level,
  CASE 
    WHEN COALESCE(SUM(pb.available_quantity), 0) <= p.reorder_level THEN 'LOW STOCK ALERT'
    WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN 'OUT OF STOCK'
    ELSE 'IN STOCK'
  END AS stock_status,
  
  p.status,
  p.created_at,
  p.updated_at

FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
LEFT JOIN categories c ON c.categories_id = p.categories_id
LEFT JOIN product_batches pb ON pb.product_id = p.product_id

GROUP BY p.product_id;

-- ============================================================
-- 11. BATCH EXPIRY ALERT VIEW
-- ============================================================
CREATE OR REPLACE VIEW `v_batch_expiry_alerts` AS
SELECT 
  pb.batch_id,
  pb.product_id,
  p.product_name,
  b.brand_name,
  pb.batch_number,
  pb.expiry_date,
  DATEDIFF(pb.expiry_date, CURDATE()) AS days_until_expiry,
  pb.available_quantity,
  pb.mrp,
  CASE 
    WHEN DATEDIFF(pb.expiry_date, CURDATE()) < 0 THEN 'EXPIRED'
    WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 30 THEN 'CRITICAL'
    WHEN DATEDIFF(pb.expiry_date, CURDATE()) <= 90 THEN 'WARNING'
    ELSE 'OK'
  END AS alert_status,
  pb.status
  
FROM product_batches pb
JOIN product p ON p.product_id = pb.product_id
LEFT JOIN brands b ON b.brand_id = p.brand_id
WHERE pb.status IN ('Active', 'Expired')
ORDER BY pb.expiry_date ASC;

-- ============================================================
-- 12. LOW STOCK ALERT VIEW
-- ============================================================
CREATE OR REPLACE VIEW `v_low_stock_alerts` AS
SELECT 
  p.product_id,
  p.product_name,
  b.brand_name,
  p.reorder_level,
  COALESCE(SUM(pb.available_quantity), 0) AS current_stock,
  (p.reorder_level - COALESCE(SUM(pb.available_quantity), 0)) AS quantity_needed,
  s.supplier_name,
  CASE 
    WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN 'OUT OF STOCK'
    WHEN COALESCE(SUM(pb.available_quantity), 0) < p.reorder_level THEN 'LOW STOCK'
    ELSE 'OK'
  END AS alert_type
  
FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
LEFT JOIN product_batches pb ON pb.product_id = p.product_id AND pb.status = 'Active'
LEFT JOIN reorder_management rm ON rm.product_id = p.product_id
LEFT JOIN suppliers s ON s.supplier_id = rm.preferred_supplier_id
WHERE p.status = 1
GROUP BY p.product_id
HAVING current_stock <= p.reorder_level
ORDER BY current_stock ASC;

-- ============================================================
-- SAMPLE DATA FOR TESTING
-- ============================================================

-- If tables need sample batch data:
INSERT INTO `product_batches` 
(`product_id`, `supplier_id`, `batch_number`, `expiry_date`, `manufacturing_date`, 
 `available_quantity`, `purchase_rate`, `mrp`, `status`)
SELECT 
  p.product_id, 1, 
  CONCAT('BATCH', p.product_id, DATE_FORMAT(NOW(), '%Y%m%d')),
  DATE_ADD(CURDATE(), INTERVAL 18 MONTH),
  DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
  p.reorder_level * 2,
  50.00,
  100.00,
  'Active'
FROM product p
WHERE p.product_id NOT IN (SELECT DISTINCT product_id FROM product_batches)
LIMIT 0;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
