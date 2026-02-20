-- ========================================
-- PHARMACY PO SYSTEM - ENHANCED SCHEMA
-- Professional Tax Invoice / Purchase Order
-- CLEAN VERSION (NO WARNINGS)
-- ========================================

-- =========================================
-- 1. SUPPLIERS / VENDORS
-- =========================================
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplier_id` INT(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(20),
  `supplier_name` VARCHAR(255) NOT NULL,
  `supplier_type` ENUM('Distributor','Manufacturer','Importer','Wholesaler') DEFAULT 'Distributor',
  `gst_number` VARCHAR(15),
  `pan_number` VARCHAR(10),
  `contact_person` VARCHAR(100),
  `phone_number` VARCHAR(20) NOT NULL,
  `secondary_contact` VARCHAR(20),
  `email` VARCHAR(100),
  `billing_address` TEXT NOT NULL,
  `billing_city` VARCHAR(100),
  `billing_state` VARCHAR(100),
  `billing_pincode` VARCHAR(10),
  `shipping_address` TEXT,
  `shipping_city` VARCHAR(100),
  `shipping_state` VARCHAR(100),
  `shipping_pincode` VARCHAR(10),
  `payment_terms` VARCHAR(100),
  `payment_days` INT(11) DEFAULT 30,
  `credit_limit` DECIMAL(12,2) DEFAULT 0,
  `total_orders` INT(11) DEFAULT 0,
  `total_amount_ordered` DECIMAL(15,2) DEFAULT 0,
  `total_amount_paid` DECIMAL(15,2) DEFAULT 0,
  `bank_account_name` VARCHAR(100),
  `bank_account_number` VARCHAR(25),
  `bank_ifsc_code` VARCHAR(11),
  `bank_name` VARCHAR(100),
  `notes` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `idx_supplier_code` (`supplier_code`),
  INDEX `idx_supplier_name` (`supplier_name`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_gst_number` (`gst_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2. MEDICINE / PRODUCT DETAILS
-- =========================================
CREATE TABLE IF NOT EXISTS `medicine_details` (
  `medicine_id` INT(11) NOT NULL AUTO_INCREMENT,
  `medicine_code` VARCHAR(20),
  `medicine_name` VARCHAR(255) NOT NULL,
  `pack_size` VARCHAR(50),
  `manufacturer_name` VARCHAR(255),
  `manufacturer_address` TEXT,
  `hsn_code` VARCHAR(20),
  `gst_rate` DECIMAL(5,2) DEFAULT 18,
  `current_batch_number` VARCHAR(50),
  `current_expiry_date` DATE,
  `manufacturing_date` DATE,
  `mrp` DECIMAL(10,2) NOT NULL,
  `ptr` DECIMAL(10,2),
  `supplier_id` INT(11),
  `reorder_level` INT(11) DEFAULT 50,
  `reorder_quantity` INT(11) DEFAULT 100,
  `current_stock` INT(11) DEFAULT 0,
  `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`medicine_id`),
  UNIQUE KEY `idx_medicine_code` (`medicine_code`),
  INDEX `idx_medicine_name` (`medicine_name`),
  INDEX `idx_hsn_code` (`hsn_code`),
  INDEX `idx_is_active` (`is_active`),
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 3. PURCHASE ORDER MASTER
-- =========================================
CREATE TABLE IF NOT EXISTS `purchase_order` (
  `po_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_number` VARCHAR(50) NOT NULL,
  `po_date` DATE NOT NULL,
  `po_type` ENUM('Regular','Express','Urgent') DEFAULT 'Regular',
  `supplier_id` INT(11) NOT NULL,
  `supplier_name` VARCHAR(255) NOT NULL,
  `supplier_contact` VARCHAR(20),
  `supplier_email` VARCHAR(100),
  `supplier_gst` VARCHAR(15),
  `supplier_address` TEXT,
  `supplier_city` VARCHAR(100),
  `supplier_state` VARCHAR(100),
  `supplier_pincode` VARCHAR(10),

  `delivery_address` TEXT,
  `delivery_city` VARCHAR(100),
  `delivery_state` VARCHAR(100),
  `delivery_pincode` VARCHAR(10),
  `expected_delivery_date` DATE,
  `actual_delivery_date` DATE,

  `reference_number` VARCHAR(50),
  `reference_date` DATE,

  `sub_total` DECIMAL(12,2) DEFAULT 0,
  `total_discount` DECIMAL(12,2) DEFAULT 0,
  `discount_percent` DECIMAL(5,2) DEFAULT 0,
  `taxable_amount` DECIMAL(12,2) DEFAULT 0,
  `cgst_percent` DECIMAL(5,2) DEFAULT 9,
  `cgst_amount` DECIMAL(12,2) DEFAULT 0,
  `sgst_percent` DECIMAL(5,2) DEFAULT 9,
  `sgst_amount` DECIMAL(12,2) DEFAULT 0,
  `igst_percent` DECIMAL(5,2) DEFAULT 18,
  `igst_amount` DECIMAL(12,2) DEFAULT 0,
  `round_off` DECIMAL(10,2) DEFAULT 0,
  `grand_total` DECIMAL(12,2) DEFAULT 0,

  `po_status` ENUM('Draft','Sent','Pending','Confirmed','Partially Received','Received','Rejected','Cancelled') DEFAULT 'Draft',
  `payment_status` ENUM('Pending','Partial','Paid','Overdue') DEFAULT 'Pending',
  `payment_terms` VARCHAR(100),
  `payment_method` ENUM('Cash','Cheque','Online Transfer','Credit','NEFT','RTGS','Other') DEFAULT 'Online Transfer',

  `notes` TEXT,
  `terms_conditions` TEXT,

  `cancelled_status` TINYINT(1) DEFAULT 0,
  `cancelled_by` INT(11),
  `cancelled_date` DATE,
  `cancellation_reason` VARCHAR(255),
  `cancellation_details` TEXT,

  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT(11),
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`po_id`),
  UNIQUE KEY `idx_po_number` (`po_number`),
  INDEX `idx_po_date` (`po_date`),
  INDEX `idx_supplier_id` (`supplier_id`),
  INDEX `idx_po_status` (`po_status`),
  INDEX `idx_cancelled_status` (`cancelled_status`),
  INDEX `idx_created_by` (`created_by`),
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE purchase_order
MODIFY supplier_id INT UNSIGNED NOT NULL;


-- =========================================
-- 4. PURCHASE ORDER ITEMS
-- =========================================
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `medicine_id` INT(11),
  `medicine_code` VARCHAR(20),
  `medicine_name` VARCHAR(255) NOT NULL,
  `pack_size` VARCHAR(50),
  `hsn_code` VARCHAR(20),
  `manufacturer_name` VARCHAR(255),
  `batch_number` VARCHAR(50),
  `expiry_date` DATE,
  `manufacturing_date` DATE,

  `unit_of_measure` ENUM('Tablets','Capsules','Bottles','Strips','Vials','Packs','Units','Boxes','Cartons','Others') DEFAULT 'Boxes',
  `quantity_ordered` INT(11) NOT NULL,
  `quantity_received` INT(11) DEFAULT 0,
  `quantity_rejected` INT(11) DEFAULT 0,

  `mrp` DECIMAL(10,2),
  `ptr` DECIMAL(10,2),
  `unit_price` DECIMAL(12,2) NOT NULL,
  `line_amount` DECIMAL(12,2) NOT NULL,

  `item_discount` DECIMAL(12,2) DEFAULT 0,
  `item_discount_percent` DECIMAL(5,2) DEFAULT 0,
  `taxable_amount` DECIMAL(12,2) NOT NULL,

  `tax_percent` DECIMAL(5,2) DEFAULT 18,
  `tax_amount` DECIMAL(12,2) DEFAULT 0,
  `item_total` DECIMAL(12,2) NOT NULL,

  `item_status` ENUM('Pending','Partial','Received','Rejected','Cancelled') DEFAULT 'Pending',
  `notes` TEXT,

  `added_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`item_id`),
  INDEX `idx_po_id` (`po_id`),
  INDEX `idx_medicine_id` (`medicine_id`),
  INDEX `idx_item_status` (`item_status`),
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`po_id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicine_details` (`medicine_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 5. PO CANCELLATION LOG
-- =========================================
CREATE TABLE IF NOT EXISTS `po_cancellation_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `cancellation_date` DATE NOT NULL,
  `cancellation_reason` ENUM('Supplier Request','Incorrect Order','Product Discontinued','Duplicate Order','Budget Issue','Quality Issue','Delivery Issue','Other') DEFAULT 'Other',
  `reason_details` TEXT NOT NULL,
  `refund_status` ENUM('Pending','Initiated','Completed','NA') DEFAULT 'Pending',
  `refund_amount` DECIMAL(12,2),
  `refund_date` DATE,
  `cancelled_by_id` INT(11),
  `cancelled_by_name` VARCHAR(100),
  `approval_by_id` INT(11),
  `approval_by_name` VARCHAR(100),
  `approval_status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  INDEX `idx_po_id` (`po_id`),
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 6. PO PAYMENT LOG
-- =========================================
CREATE TABLE IF NOT EXISTS `po_payment_log` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('Cash','Cheque','Online Transfer','Credit Card','NEFT','RTGS','Demand Draft','Other') DEFAULT 'Online Transfer',
  `payment_reference` VARCHAR(100),
  `cheque_number` VARCHAR(50),
  `transaction_id` VARCHAR(100),
  `amount_paid` DECIMAL(12,2) NOT NULL,
  `notes` TEXT,
  `recorded_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  INDEX `idx_po_id` (`po_id`),
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7. PO GOODS RECEIPT
-- =========================================
CREATE TABLE IF NOT EXISTS `po_receipt` (
  `receipt_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `receipt_number` VARCHAR(50) NOT NULL,
  `receipt_date` DATE NOT NULL,
  `received_items` INT(11) NOT NULL,
  `rejected_items` INT(11) DEFAULT 0,
  `received_by` INT(11),
  `verified_by` INT(11),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `idx_receipt_number` (`receipt_number`),
  INDEX `idx_po_id` (`po_id`),
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8. PO AMENDMENTS LOG
-- =========================================
CREATE TABLE IF NOT EXISTS `po_amendments` (
  `amendment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `amendment_date` DATE NOT NULL,
  `amendment_type` ENUM('Quantity Change','Price Change','Date Change','Item Added','Item Removed','Status Change','Full Amendment') DEFAULT 'Quantity Change',
  `old_value` TEXT,
  `new_value` TEXT,
  `reason` TEXT,
  `amended_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`amendment_id`),
  INDEX `idx_po_id` (`po_id`),
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- SAMPLE DATA (CLEAN GST)
-- =========================================
INSERT IGNORE INTO `suppliers`
(`supplier_code`,`supplier_name`,`supplier_type`,`gst_number`,`contact_person`,
 `primary_contact`,`email`,`billing_address`,`billing_city`,`billing_state`,
 `billing_pincode`,`payment_terms`,`payment_days`)
VALUES
('SUP001','AB ALLCARE BIOTECH','Manufacturer','27AABCU9603R1Z0','Ravi Kumar',
 '9876543210','sales@abcare.com','Off Haridwar No.234, Khata No.456/B',
 'Virar','Maharashtra','400001','30 days net',30),

('SUP002','Medico Pharma Pvt Ltd','Distributor','27MEDCP1234A1Z1','Anita Verma',
 '9000000002','contact@medicopharma.com','Andheri East',
 'Mumbai','Maharashtra','400069','45 days net',45),

('SUP003','HealthCare Supplies','Wholesaler','24ABCDE1234F1Z5','Suresh Patel',
 '9000000003','info@healthcaresupp.com','Navrangpura',
 'Ahmedabad','Gujarat','380009','30 days net',30);
