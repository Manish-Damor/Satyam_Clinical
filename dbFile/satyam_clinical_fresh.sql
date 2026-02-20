-- ============================================================
-- SATYAM CLINICAL - PROFESSIONAL PHARMACY ERP SYSTEM
-- COMPLETE DATABASE SCHEMA
-- ============================================================
-- Complete, clean database schema with all foreign key 
-- constraints properly matched. Safe to import fresh.
-- ============================================================

-- Drop existing database first
-- DROP DATABASE IF EXISTS satyam_clinical;

-- Create fresh database
CREATE DATABASE satyam_clinical_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE satyam_clinical_new;

SET FOREIGN_KEY_CHECKS=0;

-- ============================================================
-- 1. BRANDS TABLE (Master Data)
-- ============================================================
CREATE TABLE brands (
  brand_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  brand_name VARCHAR(150) NOT NULL,
  brand_active TINYINT(1) NOT NULL DEFAULT 1,
  brand_status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (brand_id),
  UNIQUE KEY unique_brand_name (brand_name),
  KEY idx_status (brand_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO brands (brand_id, brand_name, brand_active, brand_status) VALUES
(1, 'Cipla', 1, 1),
(2, 'Mankind', 1, 1),
(3, 'Sun Pharma', 1, 1),
(4, 'MicroLabs', 1, 1),
(5, 'Dr. Reddy', 1, 1);

-- ============================================================
-- 2. CATEGORIES TABLE (Master Data)
-- ============================================================
CREATE TABLE categories (
  categories_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categories_name VARCHAR(150) NOT NULL,
  categories_active TINYINT(1) NOT NULL DEFAULT 1,
  categories_status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (categories_id),
  UNIQUE KEY unique_category_name (categories_name),
  KEY idx_status (categories_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (categories_id, categories_name, categories_active, categories_status) VALUES
(1, 'Tablets', 1, 1),
(2, 'Syrup', 1, 1),
(3, 'Injection', 1, 1),
(4, 'Capsule', 1, 1),
(5, 'Ointment', 1, 1);

-- ============================================================
-- 3. USERS TABLE (For Audit Trail)
-- ============================================================
CREATE TABLE users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL,
  user_type VARCHAR(50),
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  
  UNIQUE KEY unique_username (username),
  UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (user_id, username, password, email, user_type, status) VALUES
(1, 'admin', '0f2cdafc6b1adf94892b17f355bd9110', 'admin@satyamclinical.com', 'admin', 1),
(2, 'satyam_clinic', '0f2cdafc6b1adf94892b17f355bd9110', 'satyamclinical@gmail.com', 'manager', 1);

-- ============================================================
-- 4. SUPPLIERS TABLE (Master Data)
-- ============================================================
CREATE TABLE suppliers (
  supplier_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  supplier_code VARCHAR(50) UNIQUE,
  supplier_name VARCHAR(150) NOT NULL,
  company_name VARCHAR(150),
  
  contact_person VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20) NOT NULL,
  alternate_phone VARCHAR(20),
  
  address TEXT NOT NULL,
  city VARCHAR(100),
  state VARCHAR(100),
  pincode VARCHAR(10),
  country VARCHAR(50) DEFAULT 'India',
  
  gst_number VARCHAR(15),
  pan_number VARCHAR(10),
  
  credit_days INT DEFAULT 30,
  payment_terms VARCHAR(255),
  
  supplier_status ENUM('Active','Inactive','Blocked') DEFAULT 'Active',
  is_verified TINYINT(1) DEFAULT 0,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (supplier_id),
  KEY idx_supplier_code (supplier_code),
  KEY idx_supplier_name (supplier_name),
  KEY idx_status (supplier_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO suppliers (supplier_id, supplier_code, supplier_name, phone, address, city, state, supplier_status) VALUES
(1, 'SUP001', 'Cipla Limited', '9876543210', '123 Pharma Street', 'Mumbai', 'Maharashtra', 'Active'),
(2, 'SUP002', 'Mankind Pharma', '9876543211', '456 Medical Road', 'Delhi', 'Delhi', 'Active'),
(3, 'SUP003', 'Sun Pharmaceuticals', '9876543212', '789 Science Park', 'Ahmedabad', 'Gujarat', 'Active');

-- ============================================================
-- 5. PRODUCT TABLE (Main Product Master)
-- ============================================================
CREATE TABLE product (
  product_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  -- Basic Information
  product_name VARCHAR(150) NOT NULL,
  content VARCHAR(255) NOT NULL,
  brand_id INT UNSIGNED NOT NULL,
  categories_id INT UNSIGNED NOT NULL,
  
  -- Packaging Information
  product_type ENUM('Tablet','Capsule','Syrup','Injection','Ointment','Drops','Others') NOT NULL DEFAULT 'Tablet',
  unit_type ENUM('Strip','Box','Bottle','Vial','Tube','Piece','Sachet') NOT NULL DEFAULT 'Strip',
  pack_size VARCHAR(100) NOT NULL,
  
  -- Tax & Compliance
  hsn_code VARCHAR(20) NOT NULL,
  gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  
  -- Inventory Control
  reorder_level INT UNSIGNED NOT NULL DEFAULT 0,
  
  -- Status & Audit
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  KEY idx_brand_id (brand_id),
  KEY idx_category_id (categories_id),
  KEY idx_product_name (product_name),
  KEY idx_status (status),
  UNIQUE KEY unique_product (product_name, brand_id),
  
  PRIMARY KEY (product_id),
  
  CONSTRAINT fk_product_brand
    FOREIGN KEY (brand_id)
    REFERENCES brands(brand_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_product_category
    FOREIGN KEY (categories_id)
    REFERENCES categories(categories_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product (product_id, product_name, content, brand_id, categories_id, product_type, unit_type, pack_size, hsn_code, gst_rate, reorder_level, status) VALUES
(1, 'Paracetamol 650mg', 'Paracetamol 650mg', 1, 1, 'Tablet', 'Strip', '10x10', '30045010', 5.00, 50, 1),
(2, 'Amoxicillin 500mg', 'Amoxicillin 500mg', 2, 1, 'Capsule', 'Strip', '10x10', '30042010', 5.00, 30, 1),
(3, 'Azithromycin 250mg', 'Azithromycin 250mg', 3, 1, 'Capsule', 'Strip', '6x10', '30042010', 5.00, 25, 1),
(4, 'Pantoprazole 40mg', 'Pantoprazole 40mg', 2, 4, 'Capsule', 'Strip', '10x15', '30049099', 5.00, 40, 1),
(5, 'Ibuprofen 400mg', 'Ibuprofen 400mg', 4, 1, 'Tablet', 'Strip', '10x10', '30045010', 5.00, 60, 1),
(6, 'Vitamin C 500mg', 'Vitamin C 500mg', 5, 1, 'Tablet', 'Strip', '10x10', '30045010', 5.00, 45, 1),
(7, 'Cough Syrup DX', 'Cough Syrup', 2, 2, 'Syrup', 'Bottle', '100ml', '30049099', 12.00, 20, 1),
(8, 'Calcium Syrup', 'Calcium Carbonate', 1, 2, 'Syrup', 'Bottle', '200ml', '30049099', 12.00, 15, 1);

-- ============================================================
-- 6. PRODUCT BATCHES TABLE
-- ============================================================
CREATE TABLE product_batches (
  batch_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  product_id INT UNSIGNED NOT NULL,
  supplier_id INT UNSIGNED,
  
  batch_number VARCHAR(50) NOT NULL,
  manufacturing_date DATE,
  expiry_date DATE NOT NULL,
  
  available_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  reserved_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  damaged_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  
  purchase_rate DECIMAL(10,2) NOT NULL,
  mrp DECIMAL(10,2) NOT NULL,
  
  status ENUM('Active','Expired','Blocked','Damaged') DEFAULT 'Active',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (batch_id),
  UNIQUE KEY unique_batch (product_id, batch_number),
  KEY idx_product_id (product_id),
  KEY idx_supplier_id (supplier_id),
  KEY idx_expiry_date (expiry_date),
  KEY idx_status (status),
  
  CONSTRAINT fk_batch_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_batch_supplier
    FOREIGN KEY (supplier_id)
    REFERENCES suppliers(supplier_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_batches (product_id, supplier_id, batch_number, manufacturing_date, expiry_date, available_quantity, purchase_rate, mrp, status) VALUES
(1, 1, 'PCM240101', '2024-01-01', '2026-06-30', 150, 18.00, 35.00, 'Active'),
(1, 1, 'PCM240102', '2024-02-15', '2026-08-31', 100, 18.50, 35.00, 'Active'),
(2, 2, 'AMX240201', '2024-02-01', '2026-02-28', 80, 85.00, 120.00, 'Active'),
(3, 3, 'AZT240301', '2024-03-01', '2026-03-31', 60, 65.00, 95.00, 'Active'),
(4, 2, 'PAN240401', '2024-04-01', '2026-10-31', 90, 55.00, 85.00, 'Active'),
(5, 4, 'IBU240501', '2024-05-01', '2026-11-30', 120, 40.00, 75.00, 'Active'),
(6, 5, 'VTC240601', '2024-06-01', '2027-06-30', 100, 30.00, 60.00, 'Active'),
(7, 1, 'CSY240701', '2024-07-01', '2025-07-31', 50, 70.00, 110.00, 'Active'),
(8, 2, 'CAL240801', '2024-08-01', '2025-08-31', 40, 95.00, 150.00, 'Active');

-- ============================================================
-- 7. STOCK MOVEMENTS TABLE (Audit Trail)
-- ============================================================
CREATE TABLE stock_movements (
  movement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  product_id INT UNSIGNED NOT NULL,
  batch_id INT UNSIGNED,
  
  movement_type ENUM('Purchase','Sales','Adjustment','Return','Damage','Sample','Expiry','Opening') NOT NULL,
  quantity INT NOT NULL,
  movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  reference_number VARCHAR(100),
  reference_type VARCHAR(50),
  
  reason VARCHAR(255),
  notes TEXT,
  
  created_by INT UNSIGNED,
  verified_by INT UNSIGNED,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (movement_id),
  KEY idx_product_id (product_id),
  KEY idx_batch_id (batch_id),
  KEY idx_movement_type (movement_type),
  KEY idx_movement_date (movement_date),
  KEY idx_reference_number (reference_number),
  
  CONSTRAINT fk_movement_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_movement_batch
    FOREIGN KEY (batch_id)
    REFERENCES product_batches(batch_id)
    ON DELETE SET NULL,
    
  CONSTRAINT fk_movement_created_by
    FOREIGN KEY (created_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. PURCHASE ORDERS TABLE
-- ============================================================
CREATE TABLE purchase_orders (
  po_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  po_number VARCHAR(50) NOT NULL UNIQUE,
  po_date DATE NOT NULL,
  supplier_id INT UNSIGNED NOT NULL,
  
  expected_delivery_date DATE,
  delivery_location VARCHAR(255),
  
  subtotal DECIMAL(12,2) DEFAULT 0,
  discount_percentage DECIMAL(5,2) DEFAULT 0,
  discount_amount DECIMAL(10,2) DEFAULT 0,
  gst_percentage DECIMAL(5,2) DEFAULT 0,
  gst_amount DECIMAL(10,2) DEFAULT 0,
  other_charges DECIMAL(10,2) DEFAULT 0,
  grand_total DECIMAL(12,2) DEFAULT 0,
  
  po_status ENUM('Draft','Submitted','Approved','PartialReceived','Received','Cancelled') DEFAULT 'Draft',
  payment_status ENUM('NotDue','Due','PartialPaid','Paid','Overdue') DEFAULT 'NotDue',
  
  notes TEXT,
  delete_status TINYINT(1) DEFAULT 0,
  
  created_by INT UNSIGNED,
  approved_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (po_id),
  UNIQUE KEY uk_po_number (po_number),
  KEY idx_supplier_id (supplier_id),
  KEY idx_po_date (po_date),
  KEY idx_po_status (po_status),
  KEY idx_delete_status (delete_status),
  
  CONSTRAINT fk_po_supplier
    FOREIGN KEY (supplier_id)
    REFERENCES suppliers(supplier_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_po_created_by
    FOREIGN KEY (created_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PURCHASE ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE po_items (
  po_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  po_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  
  quantity_ordered INT UNSIGNED NOT NULL,
  quantity_received INT UNSIGNED DEFAULT 0,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  
  batch_number VARCHAR(50),
  expiry_date DATE,
  manufacturing_date DATE,
  
  item_status ENUM('Pending','PartialReceived','Received','Cancelled') DEFAULT 'Pending',
  notes VARCHAR(255),
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (po_item_id),
  KEY idx_po_id (po_id),
  KEY idx_product_id (product_id),
  KEY idx_item_status (item_status),
  
  CONSTRAINT fk_po_items_po
    FOREIGN KEY (po_id)
    REFERENCES purchase_orders(po_id)
    ON DELETE CASCADE,
    
  CONSTRAINT fk_po_items_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. INVENTORY ADJUSTMENTS TABLE
-- ============================================================
CREATE TABLE inventory_adjustments (
  adjustment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  adjustment_number VARCHAR(50) NOT NULL UNIQUE,
  product_id INT UNSIGNED NOT NULL,
  batch_id INT UNSIGNED,
  
  adjustment_type ENUM('PhysicalCount','Damage','Loss','Excess','Return','Writing','Other') NOT NULL,
  quantity_variance INT NOT NULL,
  old_quantity INT,
  new_quantity INT,
  
  reason VARCHAR(255) NOT NULL,
  notes TEXT,
  
  requested_by INT UNSIGNED,
  approved_by INT UNSIGNED,
  approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  
  adjustment_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (adjustment_id),
  UNIQUE KEY uk_adjustment_number (adjustment_number),
  KEY idx_product_id (product_id),
  KEY idx_batch_id (batch_id),
  KEY idx_adjustment_type (adjustment_type),
  KEY idx_approval_status (approval_status),
  
  CONSTRAINT fk_adjustment_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_adjustment_batch
    FOREIGN KEY (batch_id)
    REFERENCES product_batches(batch_id)
    ON DELETE SET NULL,
    
  CONSTRAINT fk_adjustment_requested_by
    FOREIGN KEY (requested_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. REORDER MANAGEMENT TABLE
-- ============================================================
CREATE TABLE reorder_management (
  reorder_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  product_id INT UNSIGNED NOT NULL,
  
  reorder_level INT UNSIGNED NOT NULL,
  reorder_quantity INT UNSIGNED NOT NULL,
  
  current_stock INT UNSIGNED DEFAULT 0,
  is_low_stock TINYINT(1) DEFAULT 0,
  alert_date DATETIME,
  
  preferred_supplier_id INT UNSIGNED,
  
  is_active TINYINT(1) DEFAULT 1,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (reorder_id),
  UNIQUE KEY uk_product_id (product_id),
  KEY idx_is_low_stock (is_low_stock),
  KEY idx_preferred_supplier_id (preferred_supplier_id),
  
  CONSTRAINT fk_reorder_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_reorder_supplier
    FOREIGN KEY (preferred_supplier_id)
    REFERENCES suppliers(supplier_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reorder_management (product_id, reorder_level, reorder_quantity, preferred_supplier_id, is_active) VALUES
(1, 50, 200, 1, 1),
(2, 30, 150, 2, 1),
(3, 25, 100, 3, 1),
(4, 40, 180, 2, 1),
(5, 60, 250, 4, 1),
(6, 45, 150, 5, 1),
(7, 20, 100, 1, 1),
(8, 15, 80, 2, 1);

-- ============================================================
-- 12. EXPIRY TRACKING TABLE
-- ============================================================
CREATE TABLE expiry_tracking (
  expiry_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  batch_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  
  batch_number VARCHAR(50),
  expiry_date DATE NOT NULL,
  days_remaining INT,
  
  alert_level ENUM('Green','Yellow','Red','Expired') DEFAULT 'Green',
  alert_date DATETIME,
  
  stock_quantity INT UNSIGNED,
  
  action_taken VARCHAR(255),
  action_date DATETIME,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (expiry_id),
  KEY idx_batch_id (batch_id),
  KEY idx_product_id (product_id),
  KEY idx_expiry_date (expiry_date),
  KEY idx_alert_level (alert_level),
  
  CONSTRAINT fk_expiry_batch
    FOREIGN KEY (batch_id)
    REFERENCES product_batches(batch_id)
    ON DELETE CASCADE,
    
  CONSTRAINT fk_expiry_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. ORDERS TABLE (Sales/Invoices)
-- ============================================================
CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  order_number VARCHAR(50) NOT NULL UNIQUE,
  orderDate DATE NOT NULL,
  clientName VARCHAR(255) NOT NULL,
  projectName VARCHAR(100),
  clientContact VARCHAR(20),
  address TEXT,
  
  subTotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discountPercent DECIMAL(5,2) DEFAULT 0,
  gstPercent INT DEFAULT 18,
  gstn DECIMAL(12,2) NOT NULL DEFAULT 0,
  grandTotalValue DECIMAL(12,2) NOT NULL DEFAULT 0,
  
  paid DECIMAL(12,2) DEFAULT 0,
  dueValue DECIMAL(12,2) DEFAULT 0,
  
  paymentType VARCHAR(50),
  paymentStatus ENUM('Pending','PartialPaid','Paid','Cancelled') DEFAULT 'Pending',
  paymentPlace VARCHAR(100),
  
  delete_status TINYINT(1) DEFAULT 0,
  
  created_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  UNIQUE KEY uk_order_number (order_number),
  KEY idx_orderDate (orderDate),
  KEY idx_paymentStatus (paymentStatus),
  KEY idx_delete_status (delete_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE order_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  batch_id INT UNSIGNED,
  
  quantity INT UNSIGNED NOT NULL,
  rate DECIMAL(10,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  
  order_item_state TINYINT(1) DEFAULT 0,
  
  added_date DATE,
  
  PRIMARY KEY (id),
  KEY idx_order_id (order_id),
  KEY idx_product_id (product_id),
  KEY idx_batch_id (batch_id),
  
  CONSTRAINT fk_order_item_order
    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON DELETE CASCADE,
    
  CONSTRAINT fk_order_item_product
    FOREIGN KEY (product_id)
    REFERENCES product(product_id)
    ON DELETE RESTRICT,
    
  CONSTRAINT fk_order_item_batch
    FOREIGN KEY (batch_id)
    REFERENCES product_batches(batch_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEWS FOR ANALYTICS
-- ============================================================

-- Inventory Summary View
CREATE OR REPLACE VIEW v_inventory_summary AS
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
    WHEN COALESCE(SUM(pb.available_quantity), 0) <= p.reorder_level THEN 'LOW_STOCK_ALERT'
    WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN 'OUT_OF_STOCK'
    ELSE 'IN_STOCK'
  END AS stock_status,
  
  p.status,
  p.created_at,
  p.updated_at

FROM product p
LEFT JOIN brands b ON b.brand_id = p.brand_id
LEFT JOIN categories c ON c.categories_id = p.categories_id
LEFT JOIN product_batches pb ON pb.product_id = p.product_id

GROUP BY p.product_id;

-- Batch Expiry Alerts View
CREATE OR REPLACE VIEW v_batch_expiry_alerts AS
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

-- Low Stock Alerts View
CREATE OR REPLACE VIEW v_low_stock_alerts AS
SELECT 
  p.product_id,
  p.product_name,
  b.brand_name,
  p.reorder_level,
  COALESCE(SUM(pb.available_quantity), 0) AS current_stock,
  (p.reorder_level - COALESCE(SUM(pb.available_quantity), 0)) AS quantity_needed,
  s.supplier_name,
  CASE 
    WHEN COALESCE(SUM(pb.available_quantity), 0) = 0 THEN 'OUT_OF_STOCK'
    WHEN COALESCE(SUM(pb.available_quantity), 0) < p.reorder_level THEN 'LOW_STOCK'
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
-- ENABLE FOREIGN KEY CHECKS
-- ============================================================
SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- DATABASE READY
-- ============================================================
-- Clean database with all tables properly created
-- All foreign keys correctly configured
-- Sample data included
-- Ready for use
-- ============================================================
