-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: satyam_clinical_new
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `approval_logs`
--

DROP TABLE IF EXISTS `approval_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique approval log ID',
  `entity_type` varchar(50) NOT NULL COMMENT 'Type: PO, GRN, INVOICE, SALES_ORDER, SUPPLIER_PAYMENT',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'ID of the entity being approved (po_id, grn_id, etc)',
  `status_from` varchar(20) NOT NULL DEFAULT 'DRAFT' COMMENT 'Previous status: DRAFT, SUBMITTED, etc',
  `status_to` varchar(20) NOT NULL COMMENT 'New status: SUBMITTED, APPROVED, REJECTED, POSTED, CANCELLED',
  `action` varchar(50) NOT NULL COMMENT 'Action taken: SUBMIT, APPROVE, REJECT, POST, CANCEL',
  `approved_by` int(10) unsigned NOT NULL COMMENT 'User ID who performed the action',
  `approved_at` datetime DEFAULT current_timestamp() COMMENT 'Timestamp of approval action',
  `remarks` text DEFAULT NULL COMMENT 'Approval remarks or rejection reasons',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of approver',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'Browser user agent',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_approved_by` (`approved_by`),
  KEY `idx_approved_at` (`approved_at`),
  KEY `idx_status_to` (`status_to`),
  CONSTRAINT `fk_approval_user` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all approval/rejection actions across the system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_logs`
--

LOCK TABLES `approval_logs` WRITE;
/*!40000 ALTER TABLE `approval_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique audit log ID',
  `table_name` varchar(100) NOT NULL COMMENT 'Table being audited',
  `record_id` int(10) unsigned NOT NULL COMMENT 'Primary key of affected record',
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL COMMENT 'Type of operation',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'User who performed the action',
  `old_data` longtext DEFAULT NULL COMMENT 'JSON serialized old values (for UPDATE/DELETE)',
  `new_data` longtext DEFAULT NULL COMMENT 'JSON serialized new values (for INSERT/UPDATE)',
  `changes_summary` varchar(255) DEFAULT NULL COMMENT 'Summary of changes for quick review',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of user',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'Browser user agent',
  `action_timestamp` datetime DEFAULT current_timestamp() COMMENT 'When action occurred',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_action_timestamp` (`action_timestamp`),
  KEY `idx_table_action` (`table_name`,`action`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Complete audit trail of all financial and sensitive operations';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,'test_table',123,'UPDATE',1,'{\"test_field\":\"old_value\",\"amount\":4000}','{\"test_field\":\"test_value\",\"amount\":5000}','2 field(s) modified','UNKNOWN','','2026-02-17 16:07:07','2026-02-17 16:07:07'),(2,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 16:38:13','2026-02-17 16:38:13'),(3,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 16:38:13','2026-02-17 16:38:13'),(4,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 17:51:58','2026-02-17 17:51:58'),(5,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 17:51:58','2026-02-17 17:51:58'),(6,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 17:54:20','2026-02-17 17:54:20'),(7,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 17:54:20','2026-02-17 17:54:20'),(8,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 17:55:31','2026-02-17 17:55:31'),(9,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 17:55:31','2026-02-17 17:55:31'),(10,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 17:57:22','2026-02-17 17:57:22'),(11,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 17:57:22','2026-02-17 17:57:22'),(12,'test_table',123,'INSERT',1,'null','{\"field\":\"value\"}','','UNKNOWN','','2026-02-17 17:57:33','2026-02-17 17:57:33'),(13,'test_table',123,'UPDATE',1,'{\"old\":\"value\"}','{\"new\":\"value\"}','2 field(s) modified','UNKNOWN','','2026-02-17 17:57:33','2026-02-17 17:57:33');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_recalls`
--

DROP TABLE IF EXISTS `batch_recalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_recalls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique recall ID',
  `batch_id` int(10) unsigned NOT NULL COMMENT 'Batch being recalled',
  `product_id` int(10) unsigned NOT NULL COMMENT 'Product reference',
  `recall_reason` varchar(255) NOT NULL COMMENT 'Reason for recall: DEFECT, EXPIRY_ALERT, CONTAMINANT, QUALITY, REGULATORY',
  `recall_severity` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM' COMMENT 'Recall severity',
  `recall_date` date NOT NULL DEFAULT curdate() COMMENT 'Date recall was initiated',
  `recall_initiated_by` int(10) unsigned DEFAULT NULL COMMENT 'User who initiated recall',
  `status` enum('ACTIVE','COMPLETED','CANCELLED') DEFAULT 'ACTIVE' COMMENT 'Recall status',
  `description` text DEFAULT NULL COMMENT 'Detailed description of recall',
  `internal_notes` text DEFAULT NULL COMMENT 'Internal notes',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_recall_date` (`recall_date`),
  KEY `idx_status` (`status`),
  KEY `fk_recall_user` (`recall_initiated_by`),
  CONSTRAINT `fk_recall_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`),
  CONSTRAINT `fk_recall_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  CONSTRAINT `fk_recall_user` FOREIGN KEY (`recall_initiated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track product batch recalls for safety and compliance';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_recalls`
--

LOCK TABLES `batch_recalls` WRITE;
/*!40000 ALTER TABLE `batch_recalls` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_recalls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batch_sales_map`
--

DROP TABLE IF EXISTS `batch_sales_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batch_sales_map` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique mapping ID',
  `batch_id` int(10) unsigned NOT NULL COMMENT 'Batch sold',
  `order_id` int(10) unsigned NOT NULL COMMENT 'Sales order',
  `order_item_id` int(10) unsigned DEFAULT NULL COMMENT 'Line item in order',
  `quantity_sold` decimal(10,2) NOT NULL COMMENT 'Quantity of this batch in order',
  `sale_date` date NOT NULL COMMENT 'Sale date',
  `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'Customer who purchased',
  `customer_name` varchar(255) DEFAULT NULL COMMENT 'Customer name (denormalized)',
  `customer_contact` varchar(20) DEFAULT NULL COMMENT 'Customer phone (denormalized)',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_sale_date` (`sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maps batches to sales orders for quick recall query';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batch_sales_map`
--

LOCK TABLES `batch_sales_map` WRITE;
/*!40000 ALTER TABLE `batch_sales_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `batch_sales_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `brand_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(150) NOT NULL,
  `brand_active` tinyint(1) NOT NULL DEFAULT 1,
  `brand_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `unique_brand_name` (`brand_name`),
  KEY `idx_status` (`brand_status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Cipla',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(2,'Mankind',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(3,'Sun Pharma',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(4,'MicroLabs',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(5,'Dr. Reddy',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `categories_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categories_name` varchar(150) NOT NULL,
  `categories_active` tinyint(1) NOT NULL DEFAULT 1,
  `categories_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`categories_id`),
  UNIQUE KEY `unique_category_name` (`categories_name`),
  KEY `idx_status` (`categories_status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Tablets',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(2,'Syrup',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(3,'Injection',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(4,'Capsule',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53'),(5,'Ointment',1,1,'2026-02-17 04:51:53','2026-02-17 04:51:53');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `client_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `gstin` varchar(15) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `credit_limit` decimal(14,2) DEFAULT NULL,
  `outstanding_balance` decimal(14,2) DEFAULT 0.00,
  `payment_terms` int(11) DEFAULT 30 COMMENT 'Payment days',
  `business_type` enum('Retail','Wholesale','Hospital','Clinic','Distributor','Other') DEFAULT 'Retail',
  `status` enum('ACTIVE','INACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`client_id`),
  UNIQUE KEY `client_code` (`client_code`),
  KEY `idx_client_code` (`client_code`),
  KEY `idx_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,'CL001','Sunrise Pharmacy','9876543210','sunrise@pharmacy.com','Shop No. 5, Rajendra Nagar, Nashik','Shop No. 5, Rajendra Nagar, Nashik','Nashik','Maharashtra','422001','India','27AABCT1234K1Z0',NULL,50000.00,0.00,30,'','ACTIVE',NULL,NULL,'2026-02-23 10:36:42',NULL,'2026-02-23 10:36:42'),(2,'CL002','Apollo Healthcare Distribution','9123456789','apollo@dist.com','Plot 42, Industrial Area, Mumbai','Plot 42, Industrial Area, Mumbai','Mumbai','Maharashtra','400012','India','27AABCT5678K1Z0',NULL,200000.00,0.00,45,'','ACTIVE',NULL,NULL,'2026-02-23 10:36:42',NULL,'2026-02-23 10:36:42'),(3,'CL003','City Hospital Pharmacy','9988776655','pharmacy@cityhospital.com','123 Medical Complex, Pune','123 Medical Complex, Pune','Pune','Maharashtra','411001','India','27AABCT9012K1Z0',NULL,100000.00,0.00,60,'','ACTIVE',NULL,NULL,'2026-02-23 10:36:42',NULL,'2026-02-23 10:36:42'),(4,'CL004','Dr. Sharma Clinic Pharmacy','9555444333','dr.sharma@clinic.com','Clinic Building, Belgaum','Clinic Building, Belgaum','Belgaum','Karnataka','590001','India',NULL,NULL,25000.00,0.00,15,'','ACTIVE',NULL,NULL,'2026-02-23 10:36:42',NULL,'2026-02-23 10:36:42');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'company_state','Gujarat','string','Company location state for GST determination','2026-02-17 17:54:40','2026-02-17 17:54:40'),(2,'company_gstin','','string','Company GSTIN number','2026-02-17 17:54:40','2026-02-17 17:54:40'),(3,'company_name','Satyam Clinical','string','Company name','2026-02-17 17:54:40','2026-02-17 17:54:40'),(4,'gst_registration_type','1','string','1=Regular, 2=Composition, 3=Not Registered','2026-02-17 17:54:40','2026-02-17 17:54:40'),(5,'default_payment_term_days','30','integer','Default credit days for invoices','2026-02-17 17:54:40','2026-02-17 17:54:40');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_credit_log`
--

DROP TABLE IF EXISTS `customer_credit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_credit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_limit` decimal(12,2) DEFAULT NULL,
  `new_limit` decimal(12,2) DEFAULT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_credit_log_user` (`changed_by`),
  CONSTRAINT `fk_credit_log_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for credit limit changes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_credit_log`
--

LOCK TABLES `customer_credit_log` WRITE;
/*!40000 ALTER TABLE `customer_credit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_credit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_payments`
--

DROP TABLE IF EXISTS `customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `payment_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` date NOT NULL DEFAULT curdate(),
  `recorded_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `fk_payment_recorder` (`recorded_by`),
  CONSTRAINT `fk_payment_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer payment receipts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_payments`
--

LOCK TABLES `customer_payments` WRITE;
/*!40000 ALTER TABLE `customer_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers_legacy_2026-02-23_1117`
--

DROP TABLE IF EXISTS `customers_legacy_2026-02-23_1117`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers_legacy_2026-02-23_1117` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `outstanding_balance` decimal(12,2) DEFAULT 0.00,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `idx_customer_name` (`name`),
  UNIQUE KEY `idx_customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers_legacy_2026-02-23_1117`
--

LOCK TABLES `customers_legacy_2026-02-23_1117` WRITE;
/*!40000 ALTER TABLE `customers_legacy_2026-02-23_1117` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers_legacy_2026-02-23_1117` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expiry_tracking`
--

DROP TABLE IF EXISTS `expiry_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expiry_tracking` (
  `expiry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `days_remaining` int(11) DEFAULT NULL,
  `alert_level` enum('Green','Yellow','Red','Expired') DEFAULT 'Green',
  `alert_date` datetime DEFAULT NULL,
  `stock_quantity` int(10) unsigned DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`expiry_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_alert_level` (`alert_level`),
  CONSTRAINT `fk_expiry_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_expiry_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expiry_tracking`
--

LOCK TABLES `expiry_tracking` WRITE;
/*!40000 ALTER TABLE `expiry_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `expiry_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_received`
--

DROP TABLE IF EXISTS `goods_received`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_received` (
  `grn_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned DEFAULT NULL,
  `grn_no` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete timestamp',
  `status` enum('DRAFT','SUBMITTED','APPROVED','POSTED','CANCELLED') DEFAULT 'DRAFT' COMMENT 'GRN lifecycle status',
  `submitted_at` datetime DEFAULT NULL COMMENT 'When GRN was submitted',
  `approved_by` int(10) unsigned DEFAULT NULL COMMENT 'User who approved GRN',
  `approved_at` datetime DEFAULT NULL COMMENT 'When GRN was approved',
  `approval_remarks` text DEFAULT NULL COMMENT 'Approval notes',
  `quality_check_status` enum('PENDING','PASSED','FAILED','CONDITIONAL') DEFAULT 'PENDING' COMMENT 'Quality inspection result',
  `quality_checked_by` int(10) unsigned DEFAULT NULL COMMENT 'QC person',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT 'Who created GRN',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`grn_id`),
  KEY `idx_grn_po` (`po_id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_at_gr` (`deleted_at`),
  KEY `idx_grn_status` (`status`),
  KEY `idx_grn_po_id` (`po_id`,`status`),
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_received`
--

LOCK TABLES `goods_received` WRITE;
/*!40000 ALTER TABLE `goods_received` DISABLE KEYS */;
/*!40000 ALTER TABLE `goods_received` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grn_items`
--

DROP TABLE IF EXISTS `grn_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grn_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `batch_no` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `qty_received` decimal(14,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  KEY `idx_grn` (`grn_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_grn_items_grn` FOREIGN KEY (`grn_id`) REFERENCES `goods_received` (`grn_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grn_items_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grn_items`
--

LOCK TABLES `grn_items` WRITE;
/*!40000 ALTER TABLE `grn_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `grn_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_adjustments`
--

DROP TABLE IF EXISTS `inventory_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_adjustments` (
  `adjustment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `adjustment_type` enum('PhysicalCount','Damage','Loss','Excess','Return','Writing','Other') NOT NULL,
  `quantity_variance` int(11) NOT NULL,
  `old_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `adjustment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`adjustment_id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  UNIQUE KEY `uk_adjustment_number` (`adjustment_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_adjustment_type` (`adjustment_type`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `fk_adjustment_requested_by` (`requested_by`),
  CONSTRAINT `fk_adjustment_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_adjustment_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  CONSTRAINT `fk_adjustment_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_adjustments`
--

LOCK TABLES `inventory_adjustments` WRITE;
/*!40000 ALTER TABLE `inventory_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_payments`
--

DROP TABLE IF EXISTS `invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `due_date` date NOT NULL,
  `invoice_date` date NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `payment_status` enum('UNPAID','PARTIAL','PAID','OVERDUE','CANCELLED') DEFAULT 'UNPAID',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invoice payment tracking';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_payments`
--

LOCK TABLES `invoice_payments` WRITE;
/*!40000 ALTER TABLE `invoice_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_sequence`
--

DROP TABLE IF EXISTS `invoice_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_sequence` (
  `year` int(4) NOT NULL,
  `next_number` int(5) unsigned NOT NULL DEFAULT 1,
  `last_reset` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_sequence`
--

LOCK TABLES `invoice_sequence` WRITE;
/*!40000 ALTER TABLE `invoice_sequence` DISABLE KEYS */;
INSERT INTO `invoice_sequence` VALUES (2026,1,'2026-02-23 15:48:08');
/*!40000 ALTER TABLE `invoice_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_item_legacy_2026-02-23_1117`
--

DROP TABLE IF EXISTS `order_item_legacy_2026-02-23_1117`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_item_legacy_2026-02-23_1117` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `order_item_state` tinyint(1) DEFAULT 0,
  `added_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  CONSTRAINT `fk_order_item_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders_legacy_2026-02-23_1117` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_item_legacy_2026-02-23_1117`
--

LOCK TABLES `order_item_legacy_2026-02-23_1117` WRITE;
/*!40000 ALTER TABLE `order_item_legacy_2026-02-23_1117` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_item_legacy_2026-02-23_1117` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders_legacy_2026-02-23_1117`
--

DROP TABLE IF EXISTS `orders_legacy_2026-02-23_1117`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders_legacy_2026-02-23_1117` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `orderDate` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `clientName` varchar(255) NOT NULL,
  `projectName` varchar(100) DEFAULT NULL,
  `clientContact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `subTotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discountPercent` decimal(5,2) DEFAULT 0.00,
  `gstPercent` int(11) DEFAULT 18,
  `gstn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grandTotalValue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid` decimal(12,2) DEFAULT 0.00,
  `dueValue` decimal(12,2) DEFAULT 0.00,
  `paymentType` varchar(50) DEFAULT NULL,
  `paymentStatus` enum('Pending','PartialPaid','Paid','Cancelled') DEFAULT 'Pending',
  `paymentPlace` varchar(100) DEFAULT NULL,
  `delete_status` tinyint(1) DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_status` enum('DRAFT','CONFIRMED','FULFILLED','CANCELLED') DEFAULT 'DRAFT',
  `payment_status` enum('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID',
  `submitted_at` datetime DEFAULT NULL COMMENT 'When order was submitted',
  `fulfilled_at` datetime DEFAULT NULL COMMENT 'When order was fulfilled',
  `updated_by` int(10) unsigned DEFAULT NULL COMMENT 'Last user to update order',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orderDate` (`orderDate`),
  KEY `idx_paymentStatus` (`paymentStatus`),
  KEY `idx_delete_status` (`delete_status`),
  KEY `fk_orders_created_by` (`created_by`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_orders_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers_legacy_2026-02-23_1117` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders_legacy_2026-02-23_1117`
--

LOCK TABLES `orders_legacy_2026-02-23_1117` WRITE;
/*!40000 ALTER TABLE `orders_legacy_2026-02-23_1117` DISABLE KEYS */;
INSERT INTO `orders_legacy_2026-02-23_1117` VALUES (999,NULL,'TEST-999','0000-00-00',NULL,NULL,'Test Customer 120532',NULL,'9999999999',NULL,0.00,0.00,0.00,18,0.00,0.00,0.00,0.00,NULL,'',NULL,0,NULL,'2026-02-17 11:05:32','2026-02-17 11:05:32','DRAFT','UNPAID',NULL,NULL,NULL);
/*!40000 ALTER TABLE `orders_legacy_2026-02-23_1117` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_items`
--

DROP TABLE IF EXISTS `po_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_items` (
  `po_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity_ordered` int(10) unsigned NOT NULL,
  `pending_qty` int(10) unsigned DEFAULT 0,
  `quantity_received` int(10) unsigned DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `item_status` enum('Pending','PartialReceived','Received','Cancelled') DEFAULT 'Pending',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gst_percentage` float DEFAULT 18,
  PRIMARY KEY (`po_item_id`),
  KEY `idx_po_id` (`po_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_item_status` (`item_status`),
  KEY `idx_po_product` (`po_id`,`product_id`),
  CONSTRAINT `fk_po_items_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_po_items_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_items`
--

LOCK TABLES `po_items` WRITE;
/*!40000 ALTER TABLE `po_items` DISABLE KEYS */;
INSERT INTO `po_items` VALUES (1,1,1,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:15:37','2026-02-22 09:15:37',18),(2,1,2,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:15:37','2026-02-22 09:15:37',18),(3,2,1,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:17:05','2026-02-22 09:17:05',18),(4,2,2,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:17:05','2026-02-22 09:17:05',18),(5,3,1,10,0,10,100.00,1000.00,'Received',NULL,'2026-02-22 09:17:22','2026-02-23 06:45:29',18),(6,3,2,10,0,10,100.00,1000.00,'Received',NULL,'2026-02-22 09:17:22','2026-02-23 06:45:29',18),(7,4,1,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:18:14','2026-02-22 09:18:14',18),(8,4,2,10,0,0,100.00,1000.00,'Pending',NULL,'2026-02-22 09:18:14','2026-02-22 09:18:14',18),(9,5,1,5,0,0,10.00,50.00,'Pending',NULL,'2026-02-23 04:24:37','2026-02-23 04:24:37',18),(10,7,1,100,0,0,10.00,1000.00,'Pending',NULL,'2026-02-23 05:44:48','2026-02-23 05:44:48',5),(11,7,2,1000,0,0,20.00,20000.00,'Pending',NULL,'2026-02-23 05:44:48','2026-02-23 05:44:48',5),(12,8,1,10,0,0,50.00,500.00,'Pending',NULL,'2026-02-23 06:39:00','2026-02-23 06:39:00',0),(13,8,2,5,0,0,100.00,500.00,'Pending',NULL,'2026-02-23 06:39:00','2026-02-23 06:39:00',0),(14,9,1,10,0,0,50.00,500.00,'Pending',NULL,'2026-02-23 06:39:09','2026-02-23 06:39:09',0),(15,9,2,5,0,0,100.00,500.00,'Pending',NULL,'2026-02-23 06:39:09','2026-02-23 06:39:09',0),(16,10,1,10,0,0,50.00,500.00,'Pending',NULL,'2026-02-23 06:39:40','2026-02-23 06:39:40',0),(17,10,2,5,0,0,100.00,500.00,'Pending',NULL,'2026-02-23 06:39:40','2026-02-23 06:39:40',0),(18,11,1,10,0,0,50.00,500.00,'Pending',NULL,'2026-02-23 06:39:50','2026-02-23 06:39:50',0),(19,11,2,5,0,0,100.00,500.00,'Pending',NULL,'2026-02-23 06:39:50','2026-02-23 06:39:50',0),(20,12,1,10,0,10,50.00,500.00,'Received',NULL,'2026-02-23 06:40:00','2026-02-23 06:41:15',0),(21,12,2,5,0,5,100.00,500.00,'Received',NULL,'2026-02-23 06:40:00','2026-02-23 06:41:15',0),(22,13,1,5,0,5,10.00,50.00,'Received',NULL,'2026-02-23 06:43:59','2026-02-23 06:47:51',18),(23,14,1,5,0,5,10.00,50.00,'Received',NULL,'2026-02-23 06:48:25','2026-02-23 06:51:12',18),(24,15,1,5,0,0,10.00,50.00,'Pending',NULL,'2026-02-23 07:00:35','2026-02-23 07:00:35',18),(25,16,1,5,0,0,10.00,50.00,'Pending',NULL,'2026-02-23 07:01:01','2026-02-23 07:01:01',18),(26,17,1,5,0,0,10.00,50.00,'Pending',NULL,'2026-02-23 07:09:34','2026-02-23 07:09:34',18),(27,19,1,1000,0,1000,10.00,10000.00,'Received',NULL,'2026-02-23 07:18:23','2026-02-23 07:19:30',5),(28,20,1,1000,0,0,5.00,5000.00,'Pending',NULL,'2026-02-23 07:27:21','2026-02-23 07:27:21',5),(29,20,2,500,0,0,12.00,6000.00,'Pending',NULL,'2026-02-23 07:27:21','2026-02-23 07:27:21',5);
/*!40000 ALTER TABLE `po_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `product_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_name` varchar(150) NOT NULL,
  `content` varchar(255) NOT NULL,
  `brand_id` int(10) unsigned NOT NULL,
  `categories_id` int(10) unsigned NOT NULL,
  `product_type` enum('Tablet','Capsule','Syrup','Injection','Ointment','Drops','Others') NOT NULL DEFAULT 'Tablet',
  `unit_type` enum('Strip','Box','Bottle','Vial','Tube','Piece','Sachet') NOT NULL DEFAULT 'Strip',
  `pack_size` varchar(100) NOT NULL,
  `hsn_code` varchar(20) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `expected_mrp` decimal(14,2) DEFAULT NULL COMMENT 'Expected MRP for products (can be overridden per batch)',
  `purchase_rate` decimal(14,4) DEFAULT NULL COMMENT 'PTR - Cost price from supplier',
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `unique_product` (`product_name`,`brand_id`),
  KEY `idx_brand_id` (`brand_id`),
  KEY `idx_category_id` (`categories_id`),
  KEY `idx_product_name` (`product_name`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_product_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`categories_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Paracetamol 650mg','Paracetamol 650mg',1,1,'Tablet','Strip','10x10','30045010',5.00,50,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(2,'Aspirin 500mg','Aspirin 500mg',2,1,'Tablet','Strip','10x10','30045010',5.00,40,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(3,'Amoxicillin 500mg','Amoxicillin 500mg',3,1,'Capsule','Strip','10x10','30049099',5.00,30,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(4,'Pantoprazole 40mg','Pantoprazole 40mg',2,4,'Capsule','Strip','10x15','30049099',5.00,40,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(5,'Ibuprofen 400mg','Ibuprofen 400mg',4,1,'Tablet','Strip','10x10','30045010',5.00,60,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(6,'Vitamin C 500mg','Vitamin C 500mg',5,1,'Tablet','Strip','10x10','30045010',5.00,45,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(7,'Cough Syrup DX','Cough Syrup',2,2,'Syrup','Bottle','100ml','30049099',12.00,20,1,'2026-02-17 10:21:54',NULL,NULL,NULL),(8,'Calcium Syrup','Calcium Carbonate',1,2,'Syrup','Bottle','200ml','30049099',12.00,15,1,'2026-02-17 10:21:54',NULL,NULL,NULL);
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_batches` (
  `batch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `available_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `reserved_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `damaged_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `purchase_rate` decimal(10,2) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `status` enum('Active','Expired','Blocked','Damaged') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`batch_id`),
  UNIQUE KEY `unique_batch` (`product_id`,`batch_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_batch_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  CONSTRAINT `fk_batch_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES (1,1,1,'PCM240101','2024-01-01','2026-06-30',150,0,0,18.00,35.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(2,1,1,'PCM240102','2024-02-15','2026-08-31',100,0,0,18.50,35.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(3,2,2,'ASP240101','2024-01-10','2026-07-15',120,0,0,15.00,30.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(4,3,3,'AMX240101','2024-01-05','2025-12-31',80,0,0,25.00,50.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(5,4,2,'PAN240101','2024-02-01','2026-09-30',200,0,0,40.00,80.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(6,5,4,'IBU240101','2024-01-15','2026-05-20',175,0,0,12.00,25.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(7,6,5,'VIT240101','2024-01-20','2026-10-15',130,0,0,22.00,45.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(8,7,2,'COFF240101','2024-01-01','2025-12-31',50,0,0,45.00,90.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54'),(9,8,1,'CALS240101','2024-02-10','2026-08-20',40,0,0,55.00,110.00,'Active','2026-02-17 04:51:54','2026-02-17 04:51:54');
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoice_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `batch_no` varchar(100) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `free_qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `effective_rate` decimal(14,4) DEFAULT NULL,
  `mrp` decimal(14,2) DEFAULT NULL,
  `discount_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `taxable_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `cgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `igst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `product_gst_rate` decimal(5,2) DEFAULT NULL COMMENT 'Product gst_rate from product table (audit trail)',
  `supplier_quoted_mrp` decimal(14,2) DEFAULT NULL COMMENT 'MRP as quoted by supplier for this batch',
  `our_selling_price` decimal(14,2) DEFAULT NULL COMMENT 'Our calculated selling price',
  `margin_amount` decimal(14,2) DEFAULT NULL COMMENT 'MRP - Cost',
  `margin_percent` decimal(6,2) DEFAULT NULL COMMENT 'Margin percentage: (MRP - Cost) / Cost * 100',
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_gst_rate` (`product_gst_rate`),
  KEY `idx_margin` (`margin_percent`),
  CONSTRAINT `fk_inv_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_items_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoice_items`
--

LOCK TABLES `purchase_invoice_items` WRITE;
/*!40000 ALTER TABLE `purchase_invoice_items` DISABLE KEYS */;
INSERT INTO `purchase_invoice_items` VALUES (1,1,1,'Paracetamol 650mg','30045010','batch333','2026-02-12','2026-03-07',1900.000,20.000,20.0000,NULL,25.00,2.00,760.00,37240.00,5.00,0.00,0.00,5.00,0.00,0.00,1862.00,1862.00,39102.00,NULL,NULL,NULL,NULL,NULL),(3,3,1,'Paracetamol 650mg','30045010','batch23','2026-02-11','2029-02-26',1000.000,50.000,5.0000,NULL,10.00,2.00,100.00,4900.00,5.00,2.50,2.50,0.00,122.50,122.50,0.00,245.00,5145.00,NULL,NULL,NULL,NULL,NULL),(4,4,1,'Paracetamol 650mg','30041090','WF-BATCH-f82ae138','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(5,5,1,'Paracetamol 650mg','30041090','WF-BATCH-f82ae138','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(6,6,1,'Paracetamol 650mg','30041090','WF-BATCH-3a73ebf5','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(7,7,1,'Paracetamol 650mg','30041090','WF-BATCH-3a73ebf5','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(8,8,1,'Paracetamol 650mg','30041090','WF-BATCH-5adbb9e3','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(9,9,1,'Paracetamol 650mg','30041090','WF-BATCH-5adbb9e3','2025-11-22','2027-02-22',100.000,10.000,50.0000,45.4545,65.00,5.00,250.00,4750.00,5.00,0.00,0.00,5.00,0.00,0.00,237.50,237.50,4987.50,NULL,NULL,NULL,NULL,NULL),(10,10,3,'Amoxicillin 500mg','30049099','batch99','2026-01-22','2030-11-22',1000.000,10.000,10.5000,10.3960,15.00,2.00,210.00,10290.00,5.00,0.00,0.00,5.00,0.00,0.00,514.50,514.50,10804.50,NULL,NULL,NULL,NULL,NULL),(11,11,1,'','30045010',NULL,NULL,NULL,10.000,0.000,100.0000,100.0000,NULL,0.00,0.00,1000.00,5.00,0.00,0.00,5.00,0.00,0.00,50.00,50.00,1050.00,5.00,NULL,NULL,NULL,NULL),(12,11,2,'','30045010',NULL,NULL,NULL,10.000,0.000,100.0000,100.0000,NULL,0.00,0.00,1000.00,5.00,0.00,0.00,5.00,0.00,0.00,50.00,50.00,1050.00,5.00,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `purchase_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `supplier_invoice_no` varchar(100) DEFAULT NULL,
  `supplier_invoice_date` date DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `po_reference` varchar(100) DEFAULT NULL,
  `grn_reference` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_tax` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('Cash','Credit','Bank','Cheque') DEFAULT 'Credit',
  `outstanding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Approved','Cancelled','Received','Paid') DEFAULT 'Draft',
  `attachment_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `matched_by` int(11) DEFAULT NULL,
  `matched_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete timestamp',
  `submitted_at` datetime DEFAULT NULL COMMENT 'When invoice submitted',
  `approval_remarks` text DEFAULT NULL COMMENT 'Approval notes',
  `posted_by` int(10) unsigned DEFAULT NULL COMMENT 'User who posted to ledger',
  `posted_at` datetime DEFAULT NULL COMMENT 'When posted',
  `amount_paid` decimal(12,2) DEFAULT 0.00 COMMENT 'Total paid against this invoice',
  `payment_status` enum('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID' COMMENT 'Payment status',
  `last_payment_date` date DEFAULT NULL COMMENT 'Last payment date',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_by` int(10) unsigned DEFAULT NULL,
  `company_location_state` varchar(100) DEFAULT 'Gujarat' COMMENT 'Our company state for GST determination',
  `supplier_location_state` varchar(100) DEFAULT NULL COMMENT 'Supplier state (denormalized for convenience)',
  `place_of_supply` varchar(100) DEFAULT 'Gujarat',
  `gst_determination_type` enum('intrastate','interstate') DEFAULT NULL COMMENT 'Auto-detected GST type',
  `is_gst_registered` tinyint(1) DEFAULT 1 COMMENT 'Is supplier GST registered',
  `supplier_gstin` varchar(15) DEFAULT NULL COMMENT 'Denormalized from suppliers table',
  `total_cgst` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Central GST total for intra-state',
  `total_sgst` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'State GST total for intra-state',
  `total_igst` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Integrated GST total for inter-state',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_supplier_invoice` (`supplier_id`,`invoice_no`),
  UNIQUE KEY `unique_supplier_invoice` (`supplier_id`,`supplier_invoice_no`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_at_pi` (`deleted_at`),
  KEY `idx_invoice_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_supplier_invoice` (`supplier_id`,`status`),
  KEY `idx_status` (`status`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_gst_type` (`gst_determination_type`),
  KEY `idx_state` (`supplier_location_state`),
  CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_invoices`
--

LOCK TABLES `purchase_invoices` WRITE;
/*!40000 ALTER TABLE `purchase_invoices` DISABLE KEYS */;
INSERT INTO `purchase_invoices` VALUES (1,2,'INV-26-00001','INV-01','2026-02-15','2026-02-21','PO-001',NULL,'','2026-03-23','INR',38000.00,760.00,1862.00,0.00,0.00,39102.00,39102.00,'Credit',0.00,'Approved',NULL,'',1,'2026-02-21 16:19:14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-21 16:19:14',NULL,'0','Delhi','Delhi','interstate',1,'0',0.00,0.00,1862.00),(3,3,'INV-26-00001','INV-01','2026-02-20','2026-02-22','PO-001',NULL,'','2026-03-24','INR',5000.00,100.00,245.00,0.00,0.00,5145.00,5145.00,'Cash',0.00,'Approved',NULL,'',1,'2026-02-22 10:17:02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 10:17:43',NULL,'0','Gujarat','Gujarat','intrastate',1,'0',122.50,122.50,0.00),(4,1,'WORKFLOW-TEST-20260222075434','SUP-TEST-20260222075434','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Draft',NULL,'Workflow test invoice',1,'2026-02-22 12:24:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:24:34',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(5,1,'WORKFLOW-APPR-20260222075434','SUP-APPR-20260222075434','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Approved',NULL,'Workflow test invoice',1,'2026-02-22 12:24:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:24:34',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(6,1,'WORKFLOW-TEST-20260222075544','SUP-TEST-20260222075544','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Approved',NULL,'Workflow test invoice',1,'2026-02-22 12:25:44',NULL,NULL,1,'2026-02-22 12:25:44',NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:25:44',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(7,1,'WORKFLOW-APPR-20260222075544','SUP-APPR-20260222075544','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Approved',NULL,'Workflow test invoice',1,'2026-02-22 12:25:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:25:44',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(8,1,'WORKFLOW-TEST-20260222075618','SUP-TEST-20260222075618','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Approved',NULL,'Workflow test invoice',1,'2026-02-22 12:26:18',NULL,NULL,1,'2026-02-22 12:26:18',NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:26:18',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(9,1,'WORKFLOW-APPR-20260222075618','SUP-APPR-20260222075618','2026-02-22','2026-02-22','WF-TEST-001',NULL,'Net 30',NULL,'INR',5000.00,250.00,237.50,50.00,0.00,5037.50,0.00,'Credit',5037.50,'Approved',NULL,'Workflow test invoice',1,'2026-02-22 12:26:18',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:26:18',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,237.50),(10,1,'INV-26-00001','INV-01','2026-02-20','2026-02-22','PO-001',NULL,'','2026-03-24','INR',10500.00,210.00,514.50,0.00,0.50,10805.00,0.00,'Credit',10805.00,'Approved',NULL,'',1,'2026-02-22 12:33:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 12:33:15',NULL,'0','Maharashtra','Maharashtra','interstate',1,'0',0.00,0.00,514.50),(11,1,'INV-Convert-20260222101814','','2026-02-22','2026-02-22','TEST-PO-20260222101814',NULL,NULL,NULL,'INR',1000.00,0.00,100.00,0.00,0.00,1100.00,0.00,'Credit',0.00,'Draft',NULL,'Converted from TEST-PO-20260222101814',1,'2026-02-22 14:48:14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'UNPAID',NULL,'2026-02-22 14:48:14',NULL,'Gujarat','Maharashtra','Maharashtra','interstate',1,NULL,0.00,0.00,100.00);
/*!40000 ALTER TABLE `purchase_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `po_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `po_date` date NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `delivery_location` varchar(255) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `gst_percentage` decimal(5,2) DEFAULT 0.00,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `other_charges` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `po_status` enum('Draft','Submitted','Approved','PartialReceived','Received','Cancelled') DEFAULT 'Draft',
  `payment_status` enum('NotDue','Due','PartialPaid','Paid','Overdue') DEFAULT 'NotDue',
  `notes` text DEFAULT NULL,
  `delete_status` tinyint(1) DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete timestamp',
  `status` enum('DRAFT','SUBMITTED','APPROVED','POSTED','DELIVERED','CANCELLED') DEFAULT 'DRAFT' COMMENT 'PO lifecycle status',
  `submitted_at` datetime DEFAULT NULL COMMENT 'When PO was submitted for approval',
  `approved_at` datetime DEFAULT NULL COMMENT 'When PO was approved',
  `approval_remarks` text DEFAULT NULL COMMENT 'Approval notes',
  `submitted_by` int(10) unsigned DEFAULT NULL COMMENT 'User who submitted PO',
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_number` (`po_number`),
  UNIQUE KEY `uk_po_number` (`po_number`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_po_date` (`po_date`),
  KEY `idx_po_status` (`po_status`),
  KEY `idx_delete_status` (`delete_status`),
  KEY `fk_po_created_by` (`created_by`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_status` (`status`),
  KEY `idx_approved_by` (`approved_by`),
  KEY `idx_supplier_status` (`supplier_id`,`status`),
  KEY `idx_po_submitted` (`submitted_at`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'TEST-PO-20260222101537','2026-02-22',1,'2026-03-01','Test Location',1000.00,0.00,0.00,0.00,100.00,0.00,1100.00,'Approved','NotDue','Test PO for module workflow',0,1,NULL,'2026-02-22 09:15:37','2026-02-22 09:15:37',NULL,'DRAFT',NULL,NULL,NULL,NULL),(2,'TEST-PO-20260222101705','2026-02-22',1,'2026-03-01','Test Location',1000.00,0.00,0.00,0.00,100.00,0.00,1100.00,'Approved','NotDue','Test PO for module workflow',0,1,NULL,'2026-02-22 09:17:05','2026-02-22 09:17:05',NULL,'DRAFT',NULL,NULL,NULL,NULL),(3,'TEST-PO-20260222101722','2026-02-22',1,'2026-03-01','Test Location',1000.00,0.00,0.00,0.00,100.00,0.00,1100.00,'Received','NotDue','Test PO for module workflow',0,1,NULL,'2026-02-22 09:17:22','2026-02-23 06:45:29',NULL,'DRAFT',NULL,NULL,NULL,NULL),(4,'TEST-PO-20260222101814','2026-02-22',1,'2026-03-01','Test Location',1000.00,0.00,0.00,0.00,100.00,0.00,1100.00,'','NotDue','Test PO for module workflow',0,1,NULL,'2026-02-22 09:18:14','2026-02-22 09:18:14',NULL,'DRAFT',NULL,NULL,NULL,NULL),(5,'CLI-AUTO-1771820677','2026-02-23',10000,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'Draft','NotDue','Created from form',0,1,NULL,'2026-02-23 04:24:37','2026-02-23 04:24:37',NULL,'DRAFT',NULL,NULL,NULL,NULL),(7,'PO-26-0001','2026-02-23',10003,'2026-02-24','main warehouse',21000.00,0.00,0.00,0.00,1050.00,0.00,22050.00,'Approved','NotDue','Created from form',0,1,NULL,'2026-02-23 05:44:48','2026-02-23 05:45:02',NULL,'DRAFT',NULL,NULL,NULL,NULL),(8,'TESTPO-1771828740','2026-02-23',10000,NULL,NULL,1000.00,0.00,0.00,0.00,0.00,0.00,1000.00,'Submitted','NotDue','CLI test',0,1,NULL,'2026-02-23 06:39:00','2026-02-23 06:39:00',NULL,'DRAFT',NULL,NULL,NULL,NULL),(9,'TESTPO-1771828749','2026-02-23',10000,NULL,NULL,1000.00,0.00,0.00,0.00,0.00,0.00,1000.00,'Submitted','NotDue','CLI test',0,1,NULL,'2026-02-23 06:39:09','2026-02-23 06:39:09',NULL,'DRAFT',NULL,NULL,NULL,NULL),(10,'TESTPO-1771828780','2026-02-23',10000,NULL,NULL,1000.00,0.00,0.00,0.00,0.00,0.00,1000.00,'Submitted','NotDue','CLI test',0,1,NULL,'2026-02-23 06:39:40','2026-02-23 06:39:40',NULL,'DRAFT',NULL,NULL,NULL,NULL),(11,'TESTPO-1771828790','2026-02-23',10000,NULL,NULL,1000.00,0.00,0.00,0.00,0.00,0.00,1000.00,'Submitted','NotDue','CLI test',0,1,NULL,'2026-02-23 06:39:50','2026-02-23 06:39:50',NULL,'DRAFT',NULL,NULL,NULL,NULL),(12,'TESTPO-1771828800','2026-02-23',10000,NULL,NULL,1000.00,0.00,0.00,0.00,0.00,0.00,1000.00,'','NotDue','CLI test',0,1,NULL,'2026-02-23 06:40:00','2026-02-23 06:41:15',NULL,'DRAFT',NULL,NULL,NULL,NULL),(13,'CLI-AUTO-1771829039','2026-02-23',999,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'','NotDue','Created from form',0,1,NULL,'2026-02-23 06:43:59','2026-02-23 06:48:10',NULL,'DRAFT',NULL,NULL,NULL,NULL),(14,'CLI-AUTO-1771829305','2026-02-23',999,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'','NotDue','Created from form',0,1,NULL,'2026-02-23 06:48:25','2026-02-23 06:51:27',NULL,'DRAFT',NULL,NULL,NULL,NULL),(15,'TEST-1771830035','2026-02-23',999,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'Draft','NotDue','Created from form',0,1,NULL,'2026-02-23 07:00:35','2026-02-23 07:00:35',NULL,'DRAFT',NULL,NULL,NULL,NULL),(16,'TEST-1771830061','2026-02-23',999,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'Draft','NotDue','Created from form',0,1,NULL,'2026-02-23 07:01:01','2026-02-23 07:01:01',NULL,'DRAFT',NULL,NULL,NULL,NULL),(17,'TEST-1771830574','2026-02-23',999,'2026-03-02','Main Warehouse',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'Draft','NotDue','Created from form',0,1,NULL,'2026-02-23 07:09:34','2026-02-23 07:09:34',NULL,'DRAFT',NULL,NULL,NULL,NULL),(19,'PO-26-0002','2026-02-23',10008,'2026-02-25','main warehouse',10000.00,0.00,0.00,0.00,500.00,0.00,10500.00,'Received','NotDue','Created from form',0,1,NULL,'2026-02-23 07:18:23','2026-02-23 07:19:30',NULL,'DRAFT',NULL,NULL,NULL,NULL),(20,'PO-26-0003','2026-02-23',10003,'2026-02-26','main warehouse',11000.00,0.00,0.00,0.00,550.00,0.00,11550.00,'Draft','NotDue','Created from form',0,1,NULL,'2026-02-23 07:27:21','2026-02-23 07:27:21',NULL,'DRAFT',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reorder_management`
--

DROP TABLE IF EXISTS `reorder_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reorder_management` (
  `reorder_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `reorder_level` int(10) unsigned NOT NULL,
  `reorder_quantity` int(10) unsigned NOT NULL,
  `current_stock` int(10) unsigned DEFAULT 0,
  `is_low_stock` tinyint(1) DEFAULT 0,
  `alert_date` datetime DEFAULT NULL,
  `preferred_supplier_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reorder_id`),
  UNIQUE KEY `uk_product_id` (`product_id`),
  KEY `idx_is_low_stock` (`is_low_stock`),
  KEY `idx_preferred_supplier_id` (`preferred_supplier_id`),
  CONSTRAINT `fk_reorder_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  CONSTRAINT `fk_reorder_supplier` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reorder_management`
--

LOCK TABLES `reorder_management` WRITE;
/*!40000 ALTER TABLE `reorder_management` DISABLE KEYS */;
INSERT INTO `reorder_management` VALUES (1,1,50,200,0,0,NULL,1,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(2,2,30,150,0,0,NULL,2,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(3,3,25,100,0,0,NULL,3,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(4,4,40,180,0,0,NULL,2,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(5,5,60,250,0,0,NULL,4,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(6,6,45,150,0,0,NULL,5,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(7,7,20,100,0,0,NULL,1,1,'2026-02-17 04:51:55','2026-02-17 04:51:55'),(8,8,15,80,0,0,NULL,2,1,'2026-02-17 04:51:55','2026-02-17 04:51:55');
/*!40000 ALTER TABLE `reorder_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_items`
--

DROP TABLE IF EXISTS `sales_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoice_items` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_rate` decimal(14,4) NOT NULL COMMENT 'Selling rate per unit (MRP)',
  `purchase_rate` decimal(14,4) DEFAULT NULL COMMENT 'Cost price (PTR - not shown on print)',
  `line_subtotal` decimal(14,2) NOT NULL,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `gst_amount` decimal(14,2) DEFAULT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  CONSTRAINT `sales_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`invoice_id`) ON DELETE CASCADE,
  CONSTRAINT `sales_invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_items`
--

LOCK TABLES `sales_invoice_items` WRITE;
/*!40000 ALTER TABLE `sales_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoices`
--

DROP TABLE IF EXISTS `sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoices` (
  `invoice_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL COMMENT 'Format: INV-YY-NNNNN',
  `client_id` int(10) unsigned NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_address` text DEFAULT NULL COMMENT 'If different from client address',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) DEFAULT 0.00 COMMENT 'Fixed amount discount',
  `discount_percent` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage discount',
  `gst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `gst_percent` int(11) DEFAULT 18 COMMENT 'GST rate in percentage',
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) DEFAULT 0.00,
  `due_amount` decimal(14,2) DEFAULT 0.00,
  `payment_type` varchar(50) DEFAULT NULL COMMENT 'Cash, Card, Cheque, Online',
  `payment_place` varchar(50) DEFAULT NULL COMMENT 'In India / Out Of India',
  `payment_notes` text DEFAULT NULL,
  `invoice_status` enum('DRAFT','SUBMITTED','FULFILLED','CANCELLED') DEFAULT 'DRAFT',
  `payment_status` enum('UNPAID','PARTIAL','PAID') DEFAULT 'UNPAID',
  `created_by` int(10) unsigned DEFAULT NULL,
  `submitted_by` int(10) unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `fulfilled_by` int(10) unsigned DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  UNIQUE KEY `uidx_invoice_number` (`invoice_number`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_invoice_status` (`invoice_status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sales_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoices`
--

LOCK TABLES `sales_invoices` WRITE;
/*!40000 ALTER TABLE `sales_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_batches`
--

DROP TABLE IF EXISTS `stock_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `batch_no` varchar(100) NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `qty` decimal(14,3) NOT NULL DEFAULT 0.000,
  `mrp` decimal(14,2) DEFAULT NULL,
  `cost_price` decimal(14,4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `supplier_id` int(10) unsigned DEFAULT NULL COMMENT 'Which supplier provided this batch',
  `invoice_id` int(10) unsigned DEFAULT NULL COMMENT 'Which purchase invoice received this batch',
  `gst_rate_applied` decimal(5,2) DEFAULT NULL COMMENT 'GST rate applied on purchase',
  `unit_cost_with_tax` decimal(14,4) DEFAULT NULL COMMENT 'Cost price after tax (for valuation)',
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created batch record',
  PRIMARY KEY (`id`),
  KEY `idx_product_batch` (`product_id`,`batch_no`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_invoice` (`invoice_id`),
  CONSTRAINT `fk_stock_batches_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_batches`
--

LOCK TABLES `stock_batches` WRITE;
/*!40000 ALTER TABLE `stock_batches` DISABLE KEYS */;
INSERT INTO `stock_batches` VALUES (1,1,'batch333','2026-02-12','2026-03-07',1920.000,25.00,20.0000,'2026-02-21 16:19:14',2,1,5.00,NULL,1),(2,1,'WF-BATCH-f82ae138','2025-11-22','2027-02-22',110.000,65.00,50.0000,'2026-02-22 12:24:34',1,5,5.00,NULL,1),(3,1,'WF-BATCH-3a73ebf5','2025-11-22','2027-02-22',220.000,65.00,50.0000,'2026-02-22 12:25:44',1,6,5.00,NULL,1),(4,1,'WF-BATCH-5adbb9e3','2025-11-22','2027-02-22',220.000,65.00,50.0000,'2026-02-22 12:26:18',1,8,5.00,NULL,1),(5,3,'batch99','2026-01-22','2030-11-22',1010.000,15.00,10.5000,'2026-02-22 12:33:15',1,10,5.00,NULL,1);
/*!40000 ALTER TABLE `stock_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `movement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `movement_type` enum('Purchase','Sales','Adjustment','Return','Damage','Sample','Expiry','Opening') NOT NULL,
  `quantity` int(11) NOT NULL,
  `balance_before` decimal(10,2) DEFAULT NULL,
  `balance_after` decimal(10,2) DEFAULT NULL,
  `movement_date` datetime DEFAULT current_timestamp(),
  `reference_number` varchar(100) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `recorded_by` int(10) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_reference_number` (`reference_number`),
  KEY `fk_movement_created_by` (`created_by`),
  CONSTRAINT `fk_movement_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movement_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movement_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_supplier` (`supplier_id`),
  KEY `idx_payment_invoice` (`invoice_id`),
  CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `supplier_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'India',
  `gst_number` varchar(15) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `credit_days` int(11) DEFAULT 30,
  `payment_terms` varchar(255) DEFAULT NULL,
  `supplier_status` enum('Active','Inactive','Blocked') DEFAULT 'Active',
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  KEY `idx_supplier_code` (`supplier_code`),
  KEY `idx_supplier_name` (`supplier_name`),
  KEY `idx_status` (`supplier_status`)
) ENGINE=InnoDB AUTO_INCREMENT=10015 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (999,'TEST999','Test Supplier 074234',NULL,'9876543210','test999@supplier.com','9876543210',NULL,'',NULL,NULL,NULL,'India',NULL,NULL,30,NULL,'Active',0,'2026-02-23 06:42:34','2026-02-23 06:42:34'),(10000,'SUP001','Sun Pharma Distributor','Sun Pharmaceutical Industries Ltd','Rakesh Sharma','rakesh@sunpharma.com','9876543210','9825012345','GIDC Industrial Estate, Makarpura','Vadodara','Gujarat','390010','India','24ABCDE1234F1Z5','ABCDE1234F',30,'Payment within 30 days','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10001,'SUP002','Cipla Regional Supply','Cipla Ltd','Amit Verma','amit@cipla.com','9876543211',NULL,'Plot 12, Pharma Zone, Andheri East','Mumbai','Maharashtra','400069','India','27ABCDE2345G1Z6','ABCDE2345G',45,'45 days credit','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10002,'SUP003','Zydus Healthcare Supply','Zydus Lifesciences Ltd','Nirav Patel','nirav@zydus.com','9876543212',NULL,'Zydus Tower, SG Highway','Ahmedabad','Gujarat','380015','India','24ABCDE3456H1Z7','ABCDE3456H',30,'Net 30 days','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10003,'SUP004','Torrent Pharma Distributor','Torrent Pharmaceuticals Ltd','Mehul Shah','mehul@torrentpharma.com','9876543213',NULL,'Ashram Road','Ahmedabad','Gujarat','380009','India','24ABCDE4567J1Z8','ABCDE4567J',60,'60 days credit period','Active',0,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10004,'SUP005','Alkem Wholesale Pharma','Alkem Laboratories Ltd','Sanjay Joshi','sanjay@alkem.com','9876543214',NULL,'MIDC Industrial Area','Nashik','Maharashtra','422007','India','27ABCDE5678K1Z9','ABCDE5678K',30,'Net 30 days','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10005,'SUP006','Mankind Pharma Supply','Mankind Pharma Ltd','Vikram Singh','vikram@mankind.com','9876543215',NULL,'Sector 62','Noida','Uttar Pradesh','201301','India','09ABCDE6789L1Z1','ABCDE6789L',45,'Payment within 45 days','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10006,'SUP007','Lupin Distribution','Lupin Ltd','Rahul Kapoor','rahul@lupin.com','9876543216',NULL,'Kalina, Santacruz East','Mumbai','Maharashtra','400098','India','27ABCDE7890M1Z2','ABCDE7890M',30,'Standard credit 30 days','Inactive',0,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10007,'SUP008','Dr Reddy Supply Chain','Dr Reddy’s Laboratories Ltd','Anjali Rao','anjali@drreddys.com','9876543217',NULL,'Banjara Hills','Hyderabad','Telangana','500034','India','36ABCDE8901N1Z3','ABCDE8901N',30,'Net 30','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10008,'SUP009','Intas Pharma Distributor','Intas Pharmaceuticals Ltd','Harsh Patel','harsh@intas.com','9876543218',NULL,'Corporate House, SG Highway','Ahmedabad','Gujarat','380054','India','24ABCDE9012P1Z4','ABCDE9012P',60,'60 days credit','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10009,'SUP010','Glenmark Pharma Supply','Glenmark Pharmaceuticals Ltd','Deepak Nair','deepak@glenmark.com','9876543219',NULL,'Andheri West','Mumbai','Maharashtra','400053','India','27ABCDE0123Q1Z5','ABCDE0123Q',45,'45 days payment term','Blocked',0,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10010,'SUP011','Cadila Pharma Trade','Cadila Pharmaceuticals Ltd','Bhavesh Trivedi','bhavesh@cadilapharma.com','9876543220',NULL,'Dholka Road','Ahmedabad','Gujarat','382210','India','24ABCDE1122R1Z6','ABCDE1122R',30,'Net 30','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10011,'SUP012','Micro Labs Distributor','Micro Labs Ltd','Suresh Kumar','suresh@microlabs.com','9876543221',NULL,'Bommasandra Industrial Area','Bangalore','Karnataka','560099','India','29ABCDE2233S1Z7','ABCDE2233S',30,'Payment in 30 days','Active',0,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10012,'SUP013','Abbott India Supply','Abbott India Ltd','Priya Mehta','priya@abbott.com','9876543222',NULL,'BKC Complex','Mumbai','Maharashtra','400051','India','27ABCDE3344T1Z8','ABCDE3344T',45,'45 days credit','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10013,'SUP014','Pfizer Pharma Distribution','Pfizer Ltd','Arjun Malhotra','arjun@pfizer.com','9876543223',NULL,'Bandra East','Mumbai','Maharashtra','400051','India','27ABCDE4455U1Z9','ABCDE4455U',30,'Net 30 days','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27'),(10014,'SUP015','Local Generic Supplier','Shree Medical Agencies','Mahesh Patel','mahesh@shreemedical.com','9876543224',NULL,'Raopura Main Road','Vadodara','Gujarat','390001','India','24ABCDE5566V1Z1','ABCDE5566V',15,'15 days short credit','Active',1,'2026-02-22 10:53:27','2026-02-22 10:53:27');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'satyam_clinic','0f2cdafc6b1adf94892b17f355bd9110','satyamclinical@gmail.com','manager',1,'2026-02-17 04:51:54','2026-02-17 04:51:54');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_audit_trail_recent`
--

DROP TABLE IF EXISTS `v_audit_trail_recent`;
/*!50001 DROP VIEW IF EXISTS `v_audit_trail_recent`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_audit_trail_recent` AS SELECT
 1 AS `id`,
  1 AS `table_name`,
  1 AS `record_id`,
  1 AS `action`,
  1 AS `user_name`,
  1 AS `ip_address`,
  1 AS `action_timestamp`,
  1 AS `changes_summary` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_batch_expiry_alerts`
--

DROP TABLE IF EXISTS `v_batch_expiry_alerts`;
/*!50001 DROP VIEW IF EXISTS `v_batch_expiry_alerts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_batch_expiry_alerts` AS SELECT
 1 AS `batch_id`,
  1 AS `product_id`,
  1 AS `product_name`,
  1 AS `brand_name`,
  1 AS `batch_number`,
  1 AS `expiry_date`,
  1 AS `days_until_expiry`,
  1 AS `available_quantity`,
  1 AS `mrp`,
  1 AS `alert_status`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_batch_stock_summary`
--

DROP TABLE IF EXISTS `v_batch_stock_summary`;
/*!50001 DROP VIEW IF EXISTS `v_batch_stock_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_batch_stock_summary` AS SELECT
 1 AS `batch_id`,
  1 AS `product_id`,
  1 AS `product_name`,
  1 AS `batch_number`,
  1 AS `mfg_date`,
  1 AS `exp_date`,
  1 AS `quantity_available`,
  1 AS `days_to_expiry`,
  1 AS `expiry_status`,
  1 AS `purchase_rate`,
  1 AS `stock_value` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_customer_credit_exposure`
--

DROP TABLE IF EXISTS `v_customer_credit_exposure`;
/*!50001 DROP VIEW IF EXISTS `v_customer_credit_exposure`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_customer_credit_exposure` AS SELECT
 1 AS `note` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_inventory_summary`
--

DROP TABLE IF EXISTS `v_inventory_summary`;
/*!50001 DROP VIEW IF EXISTS `v_inventory_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_inventory_summary` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `brand_name`,
  1 AS `categories_name`,
  1 AS `pack_size`,
  1 AS `hsn_code`,
  1 AS `gst_rate`,
  1 AS `total_stock`,
  1 AS `reserved_stock`,
  1 AS `damaged_stock`,
  1 AS `active_batches`,
  1 AS `expired_batches`,
  1 AS `nearest_expiry`,
  1 AS `reorder_level`,
  1 AS `stock_status`,
  1 AS `status`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_low_stock_alerts`
--

DROP TABLE IF EXISTS `v_low_stock_alerts`;
/*!50001 DROP VIEW IF EXISTS `v_low_stock_alerts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_low_stock_alerts` AS SELECT
 1 AS `product_id`,
  1 AS `product_name`,
  1 AS `brand_name`,
  1 AS `reorder_level`,
  1 AS `current_stock`,
  1 AS `quantity_needed`,
  1 AS `supplier_name`,
  1 AS `alert_type` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_overdue_invoices`
--

DROP TABLE IF EXISTS `v_overdue_invoices`;
/*!50001 DROP VIEW IF EXISTS `v_overdue_invoices`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_overdue_invoices` AS SELECT
 1 AS `note` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_pending_approvals`
--

DROP TABLE IF EXISTS `v_pending_approvals`;
/*!50001 DROP VIEW IF EXISTS `v_pending_approvals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_pending_approvals` AS SELECT
 1 AS `note`,
  1 AS `description` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_stock_movement_recent`
--

DROP TABLE IF EXISTS `v_stock_movement_recent`;
/*!50001 DROP VIEW IF EXISTS `v_stock_movement_recent`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_stock_movement_recent` AS SELECT
 1 AS `id`,
  1 AS `product_id`,
  1 AS `product_name`,
  1 AS `batch_id`,
  1 AS `batch_number`,
  1 AS `movement_type`,
  1 AS `quantity`,
  1 AS `balance_before`,
  1 AS `balance_after`,
  1 AS `reference_type`,
  1 AS `reference_id`,
  1 AS `recorded_by_name`,
  1 AS `recorded_at` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_audit_trail_recent`
--

/*!50001 DROP VIEW IF EXISTS `v_audit_trail_recent`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_audit_trail_recent` AS select `al`.`id` AS `id`,`al`.`table_name` AS `table_name`,`al`.`record_id` AS `record_id`,`al`.`action` AS `action`,`u`.`username` AS `user_name`,`al`.`ip_address` AS `ip_address`,`al`.`action_timestamp` AS `action_timestamp`,`al`.`changes_summary` AS `changes_summary` from (`audit_logs` `al` left join `users` `u` on(`al`.`user_id` = `u`.`user_id`)) order by `al`.`action_timestamp` desc limit 500 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_batch_expiry_alerts`
--

/*!50001 DROP VIEW IF EXISTS `v_batch_expiry_alerts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_batch_expiry_alerts` AS select `pb`.`batch_id` AS `batch_id`,`pb`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`b`.`brand_name` AS `brand_name`,`pb`.`batch_number` AS `batch_number`,`pb`.`expiry_date` AS `expiry_date`,to_days(`pb`.`expiry_date`) - to_days(curdate()) AS `days_until_expiry`,`pb`.`available_quantity` AS `available_quantity`,`pb`.`mrp` AS `mrp`,case when to_days(`pb`.`expiry_date`) - to_days(curdate()) < 0 then 'EXPIRED' when to_days(`pb`.`expiry_date`) - to_days(curdate()) <= 30 then 'CRITICAL' when to_days(`pb`.`expiry_date`) - to_days(curdate()) <= 90 then 'WARNING' else 'OK' end AS `alert_status`,`pb`.`status` AS `status` from ((`product_batches` `pb` join `product` `p` on(`p`.`product_id` = `pb`.`product_id`)) left join `brands` `b` on(`b`.`brand_id` = `p`.`brand_id`)) where `pb`.`status` in ('Active','Expired') order by `pb`.`expiry_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_batch_stock_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_batch_stock_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_batch_stock_summary` AS select `pb`.`batch_id` AS `batch_id`,`pb`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`pb`.`batch_number` AS `batch_number`,`pb`.`manufacturing_date` AS `mfg_date`,`pb`.`expiry_date` AS `exp_date`,`pb`.`available_quantity` AS `quantity_available`,to_days(`pb`.`expiry_date`) - to_days(curdate()) AS `days_to_expiry`,case when `pb`.`expiry_date` < curdate() then 'EXPIRED' when to_days(`pb`.`expiry_date`) - to_days(curdate()) < 30 then 'CRITICAL' when to_days(`pb`.`expiry_date`) - to_days(curdate()) < 90 then 'WARNING' else 'OK' end AS `expiry_status`,`pb`.`purchase_rate` AS `purchase_rate`,`pb`.`available_quantity` * `pb`.`purchase_rate` AS `stock_value` from (`product_batches` `pb` join `product` `p` on(`pb`.`product_id` = `p`.`product_id`)) where `pb`.`status` <> 'Damaged' order by `pb`.`expiry_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_customer_credit_exposure`
--

/*!50001 DROP VIEW IF EXISTS `v_customer_credit_exposure`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_customer_credit_exposure` AS select 'NO CUSTOMERS TABLE - VIEWS SKIPPED' AS `note` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_inventory_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_inventory_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_inventory_summary` AS select `p`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`b`.`brand_name` AS `brand_name`,`c`.`categories_name` AS `categories_name`,`p`.`pack_size` AS `pack_size`,`p`.`hsn_code` AS `hsn_code`,`p`.`gst_rate` AS `gst_rate`,coalesce(sum(`pb`.`available_quantity`),0) AS `total_stock`,coalesce(sum(`pb`.`reserved_quantity`),0) AS `reserved_stock`,coalesce(sum(`pb`.`damaged_quantity`),0) AS `damaged_stock`,count(distinct case when `pb`.`status` = 'Active' then `pb`.`batch_id` end) AS `active_batches`,count(distinct case when `pb`.`status` = 'Expired' then `pb`.`batch_id` end) AS `expired_batches`,min(`pb`.`expiry_date`) AS `nearest_expiry`,`p`.`reorder_level` AS `reorder_level`,case when coalesce(sum(`pb`.`available_quantity`),0) <= `p`.`reorder_level` then 'LOW STOCK ALERT' when coalesce(sum(`pb`.`available_quantity`),0) = 0 then 'OUT OF STOCK' else 'IN STOCK' end AS `stock_status`,`p`.`status` AS `status`,`p`.`created_at` AS `created_at`,`p`.`updated_at` AS `updated_at` from (((`product` `p` left join `brands` `b` on(`b`.`brand_id` = `p`.`brand_id`)) left join `categories` `c` on(`c`.`categories_id` = `p`.`categories_id`)) left join `product_batches` `pb` on(`pb`.`product_id` = `p`.`product_id`)) group by `p`.`product_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_low_stock_alerts`
--

/*!50001 DROP VIEW IF EXISTS `v_low_stock_alerts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_low_stock_alerts` AS select `p`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`b`.`brand_name` AS `brand_name`,`p`.`reorder_level` AS `reorder_level`,coalesce(sum(`pb`.`available_quantity`),0) AS `current_stock`,`p`.`reorder_level` - coalesce(sum(`pb`.`available_quantity`),0) AS `quantity_needed`,`s`.`supplier_name` AS `supplier_name`,case when coalesce(sum(`pb`.`available_quantity`),0) = 0 then 'OUT OF STOCK' when coalesce(sum(`pb`.`available_quantity`),0) < `p`.`reorder_level` then 'LOW STOCK' else 'OK' end AS `alert_type` from ((((`product` `p` left join `brands` `b` on(`b`.`brand_id` = `p`.`brand_id`)) left join `product_batches` `pb` on(`pb`.`product_id` = `p`.`product_id` and `pb`.`status` = 'Active')) left join `reorder_management` `rm` on(`rm`.`product_id` = `p`.`product_id`)) left join `suppliers` `s` on(`s`.`supplier_id` = `rm`.`preferred_supplier_id`)) where `p`.`status` = 1 group by `p`.`product_id` having `current_stock` <= `p`.`reorder_level` order by coalesce(sum(`pb`.`available_quantity`),0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_overdue_invoices`
--

/*!50001 DROP VIEW IF EXISTS `v_overdue_invoices`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_overdue_invoices` AS select 'NO CUSTOMERS TABLE - VIEWS SKIPPED' AS `note` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_pending_approvals`
--

/*!50001 DROP VIEW IF EXISTS `v_pending_approvals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pending_approvals` AS select 'IMPLEMENTATION_NOTE' AS `note`,'Pending approvals require service layer querying across multiple tables' AS `description` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_stock_movement_recent`
--

/*!50001 DROP VIEW IF EXISTS `v_stock_movement_recent`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_stock_movement_recent` AS select `sm`.`movement_id` AS `id`,`sm`.`product_id` AS `product_id`,`p`.`product_name` AS `product_name`,`sm`.`batch_id` AS `batch_id`,`pb`.`batch_number` AS `batch_number`,`sm`.`movement_type` AS `movement_type`,`sm`.`quantity` AS `quantity`,`sm`.`balance_before` AS `balance_before`,`sm`.`balance_after` AS `balance_after`,`sm`.`reference_type` AS `reference_type`,`sm`.`reference_id` AS `reference_id`,`u`.`username` AS `recorded_by_name`,coalesce(`sm`.`movement_date`,`sm`.`created_at`) AS `recorded_at` from (((`stock_movements` `sm` join `product` `p` on(`sm`.`product_id` = `p`.`product_id`)) join `product_batches` `pb` on(`sm`.`batch_id` = `pb`.`batch_id`)) left join `users` `u` on(`sm`.`recorded_by` = `u`.`user_id`)) order by coalesce(`sm`.`movement_date`,`sm`.`created_at`) desc limit 1000 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-23 19:17:49
