-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2022 at 09:17 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.28

-- Create database if it does not exist
CREATE DATABASE IF NOT EXISTS satyam_clinical;

-- Use the database
USE satyam_clinical;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `satyam_clinical`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `brand_active` int(11) NOT NULL DEFAULT 0,
  `brand_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`, `brand_active`, `brand_status`) VALUES
(1, 'Cipla', 1, 1),
(2, 'Mankind', 1, 1),
(3, 'Sunpharma', 1, 1),
(4, 'MicroLabs', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `categories_id` int(11) NOT NULL,
  `categories_name` varchar(255) NOT NULL,
  `categories_active` int(11) NOT NULL DEFAULT 0,
  `categories_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`categories_id`, `categories_name`, `categories_active`, `categories_status`) VALUES
(1, 'Tablets', 1, 1),
(2, 'Syrup', 1, 1),
(3, 'SkinLiquid', 1, 1),
(4, 'PainKiller', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(15) NOT NULL,
  `uno` varchar(50) NOT NULL,
  `orderDate` date NOT NULL,
  `clientName` text NOT NULL,
  `projectName` varchar(30) NOT NULL,
  `clientContact` int(15) NOT NULL,
  `address` varchar(30) NOT NULL,
  `subTotal` int(100) NOT NULL,
  `totalAmount` int(100) NOT NULL,
  `discount` int(100) NOT NULL,
  `grandTotalValue` int(100) NOT NULL,
  `gstn` int(100) NOT NULL,
  `paid` int(100) NOT NULL,
  `dueValue` int(100) NOT NULL,
  `paymentType` int(15) NOT NULL,
  `paymentStatus` int(15) NOT NULL,
  `paymentPlace` int(5) NOT NULL,
  `delete_status` tinyint(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `uno`, `orderDate`, `clientName`, `projectName`, `clientContact`, `address`, `subTotal`, `totalAmount`, `discount`, `grandTotalValue`, `gstn`, `paid`, `dueValue`, `paymentType`, `paymentStatus`, `paymentPlace`, `delete_status`) VALUES
(1, 'INV-0001', '2022-02-28', 'Santosh Kadam', '', 2147483647, '', 100, 10, 108, 49, 0, 49, 49, 2, 1, 0, 0),
(2, 'INV-0002', '2022-03-24', 'Aishwarya Joshi', '', 2147483647, '', 300, 0, 354, 0, 0, 354, 354, 3, 3, 1, 0),
(3, 'INV-0003', '2022-04-15', 'Saurabh Katkar', '', 2147483647, '', 860, 1015, 10, 1005, 155, 500, 505, 2, 2, 1, 0),
(4, 'INV-0004', '2022-04-15', 'Rohan Surti', '', 2147483647, '', 60, 71, 0, 71, 11, 50, 21, 5, 2, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `id` int(15) NOT NULL,
  `productName` int(100) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `rate` varchar(255) NOT NULL,
  `total` varchar(255) NOT NULL,
  `lastid` int(50) NOT NULL,
  `added_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`id`, `productName`, `quantity`, `rate`, `total`, `lastid`, `added_date`) VALUES
(5, 2, '1', '100', '100.00', 1, '0000-00-00'),
(6, 2, '2', '150', '300.00', 2, '0000-00-00'),
(7, 1, '2', '30', '60.00', 3, '2022-04-15'),
(8, 2, '4', '150', '600.00', 3, '2022-04-15'),
(9, 3, '1', '200', '200.00', 3, '2022-04-15'),
(10, 1, '2', '30', '60.00', 4, '2022-04-15');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

-- CREATE TABLE `product` (
--   `product_id` int(11) NOT NULL,
--   `product_name` varchar(255) NOT NULL,
--   `product_image` text NOT NULL,
--   `brand_id` int(11) NOT NULL,
--   `categories_id` int(11) NOT NULL,
--   `quantity` varchar(255) NOT NULL,
--   `rate` varchar(255) NOT NULL,
--   `mrp` int(100) NOT NULL,
--   `bno` varchar(50) NOT NULL,
--   `expdate` date NOT NULL,
--   `added_date` date NOT NULL,
--   `active` int(11) NOT NULL DEFAULT 0,
--   `status` int(11) NOT NULL DEFAULT 0
-- ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- NEW PRODUCT TABLE --

CREATE TABLE product (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Basic Information
    product_name VARCHAR(150) NOT NULL,
    content VARCHAR(255) NOT NULL,  -- Composition / Content
    brand_id INT UNSIGNED NOT NULL,
    categories_id INT UNSIGNED NOT NULL,

    -- Packaging Information
    product_type ENUM('Tablet','Capsule','Syrup','Injection','Ointment','Drops','Others') NOT NULL,
    unit_type ENUM('Strip','Box','Bottle','Vial','Tube','Piece') NOT NULL,
    pack_size VARCHAR(100) NOT NULL,

    -- Tax & Compliance
    hsn_code VARCHAR(20) NOT NULL,
    gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,

    -- Inventory Control
    reorder_level INT UNSIGNED NOT NULL DEFAULT 0,

    -- Status & Audit
    status TINYINT(1) NOT NULL DEFAULT 1,  -- 1 = Active, 0 = Inactive
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE KEY unique_product (product_name, brand_id),

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
);

CREATE INDEX idx_product_name ON product(product_name);
CREATE INDEX idx_content ON product(content);


--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `product_image`, `brand_id`, `categories_id`, `quantity`, `rate`, `mrp`, `bno`, `expdate`, `added_date`, `active`, `status`) VALUES
(1, 'Cipla Inhaler', 'tab.jpg', 1, 1, '50', '30', 40, '307002', '2022-02-28', '2022-02-28', 1, 1),
(2, 'Abevia 200 SR Tablet', 'tab1.jpg', 2, 1, '30', '150', 200, '307003', '2022-02-16', '2022-02-28', 1, 1),
(3, 'Arpizol 20 Tablet', 'tab3.jpg', 3, 3, '70', '200', 300, '307004', '2022-03-13', '2022-02-28', 1, 1),
(4, 'DOLO 650mg', 'tab4.jpg', 4, 1, '500', '25', 30, '307005', '2022-05-31', '2022-04-15', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`) VALUES
(1, 'Satyam_Clinic', '0f2cdafc6b1adf94892b17f355bd9110', 'satyamclinical@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`categories_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `categories_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

ALTER TABLE orders 
  MODIFY clientContact VARCHAR(20) NOT NULL;

ALTER TABLE order_item
  ADD COLUMN order_item_state TINYINT(5) DEFAULT 0;

ALTER TABLE `orders`
ADD COLUMN `gstPercents` INT(5) NOT NULL DEFAULT 0
AFTER `grandTotalValue`;

ALTER TABLE `product`

-- Add master medicine fields
ADD COLUMN `pack_size` VARCHAR(50) AFTER `product_name`,
ADD COLUMN `hsn_code` VARCHAR(20) AFTER `pack_size`,
ADD COLUMN `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER `hsn_code`,

-- Fix quantity to numeric (total stock summary)
MODIFY `quantity` INT(11) NOT NULL DEFAULT 0,

-- Fix rate & mrp to proper decimal types
MODIFY `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
MODIFY `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00;



-- sample data
INSERT INTO product
(product_id, product_name, pack_size, hsn_code, gst_rate, product_image,
 brand_id, categories_id, quantity, rate, mrp, added_date, active, status)
VALUES
(100, 'Paracetamol 650', '10x10', '30045010', 5.00, 'para.jpg', 4, 1, 120, 0, 35.00, CURDATE(), 1, 1),
(102, 'Amoxicillin 500', '10x10', '30042010', 5.00, 'amoxi.jpg', 1, 1, 80, 0, 120.00, CURDATE(), 1, 1),
(103, 'Azithromycin 250', '6x10', '30042010', 5.00, 'azithro.jpg', 3, 1, 60, 0, 95.00, CURDATE(), 1, 1),
(104, 'Pantoprazole 40', '10x15', '30049099', 5.00, 'panto.jpg', 2, 1, 90, 0, 85.00, CURDATE(), 1, 1),
(105, 'Cough Syrup DX', '100ml', '30049099', 12.00, 'cough.jpg', 2, 2, 50, 0, 110.00, CURDATE(), 1, 1),
(106, 'Vitamin C Tablets', '10x10', '30045010', 5.00, 'vitc.jpg', 3, 1, 70, 0, 60.00, CURDATE(), 1, 1),
(107, 'Calcium Syrup', '200ml', '30049099', 12.00, 'calcium.jpg', 1, 2, 40, 0, 150.00, CURDATE(), 1, 1),
(108, 'Ibuprofen 400', '10x10', '30045010', 5.00, 'ibu.jpg', 4, 1, 100, 0, 75.00, CURDATE(), 1, 1),
(109, 'Antacid Gel', '170ml', '30049099', 12.00, 'antacid.jpg', 2, 2, 55, 0, 95.00, CURDATE(), 1, 1),
(110, 'ORS Powder', '21g Sachet', '30049099', 5.00, 'ors.jpg', 1, 4, 200, 0, 20.00, CURDATE(), 1, 1);


INSERT INTO product_batches
(product_id, batch_number, expiry_date, available_quantity,
 purchase_rate, mrp, status)
VALUES
-- Paracetamol
(100, 'PCM2401', '2026-01-31', 70, 18.00, 35.00, 'Active'),
(100, 'PCM2402', '2026-06-30', 50, 18.50, 35.00, 'Active'),

-- Amoxicillin
(102, 'AMX2309', '2025-12-31', 40, 85.00, 120.00, 'Active'),
(102, 'AMX2310', '2026-03-31', 40, 86.00, 120.00, 'Active'),

-- Azithromycin
(103, 'AZT2401', '2026-02-28', 60, 65.00, 95.00, 'Active'),

-- Pantoprazole
(104, 'PAN2403', '2026-05-31', 90, 55.00, 85.00, 'Active'),

-- Cough Syrup
(105, 'CSX2311', '2025-11-30', 50, 70.00, 110.00, 'Active'),

-- Vitamin C
(106, 'VTC2402', '2026-08-31', 70, 30.00, 60.00, 'Active'),

-- Calcium Syrup
(107, 'CAL2310', '2025-10-31', 40, 95.00, 150.00, 'Active'),

-- Ibuprofen
(108, 'IBU2401', '2026-04-30', 100, 40.00, 75.00, 'Active'),

-- Antacid
(109, 'ANT2312', '2025-12-31', 55, 60.00, 95.00, 'Active'),

-- ORS
(110, 'ORS2401', '2026-09-30', 200, 8.00, 20.00, 'Active');




/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Create purchase_orders table
-- CREATE TABLE IF NOT EXISTS `purchase_orders` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `po_id` varchar(50) NOT NULL UNIQUE,
--   `po_date` date NOT NULL,
--   `vendor_name` varchar(100) NOT NULL,
--   `vendor_contact` varchar(20),
--   `vendor_email` varchar(100),
--   `vendor_address` text,
--   `expected_delivery_date` date,
--   `po_status` enum('Pending','Approved','Received','Cancelled') DEFAULT 'Pending',
--   `sub_total` decimal(10,2) DEFAULT 0,
--   `discount` decimal(5,2) DEFAULT 0,
--   `gst` decimal(5,2) DEFAULT 0,
--   `grand_total` decimal(10,2) DEFAULT 0,
--   `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
--   `notes` text,
--   `delete_status` int(1) DEFAULT 0,
--   `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
--   `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   INDEX `idx_delete_status` (`delete_status`),
--   INDEX `idx_po_date` (`po_date`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -- Create po_items table
-- CREATE TABLE IF NOT EXISTS `po_items` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `po_master_id` int(11) NOT NULL,
--   `product_id` int(11),
--   `quantity` int(11) NOT NULL,
--   `unit_price` decimal(10,2) NOT NULL,
--   `total` decimal(10,2) NOT NULL,
--   `added_date` timestamp DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   FOREIGN KEY (`po_master_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
--   INDEX `po_master_id` (`po_master_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;