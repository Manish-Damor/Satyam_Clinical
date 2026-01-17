-- Purchase Order Database Tables

-- Create purchase_orders table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` varchar(50) NOT NULL UNIQUE,
  `po_date` date NOT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `vendor_contact` varchar(20),
  `vendor_email` varchar(100),
  `vendor_address` text,
  `expected_delivery_date` date,
  `po_status` enum('Pending','Approved','Received','Cancelled') DEFAULT 'Pending',
  `sub_total` decimal(10,2) DEFAULT 0,
  `discount` decimal(5,2) DEFAULT 0,
  `gst` decimal(5,2) DEFAULT 0,
  `grand_total` decimal(10,2) DEFAULT 0,
  `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
  `notes` text,
  `delete_status` int(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_delete_status` (`delete_status`),
  INDEX `idx_po_date` (`po_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create po_items table
CREATE TABLE IF NOT EXISTS `po_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_master_id` int(11) NOT NULL,
  `product_id` int(11),
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `added_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`po_master_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  INDEX `po_master_id` (`po_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
