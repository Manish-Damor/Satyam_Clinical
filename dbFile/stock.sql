CREATE TABLE `product_batches` (
  `batch_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  `product_id` INT UNSIGNED NOT NULL,

  `batch_number` VARCHAR(50) NOT NULL,
  `expiry_date` DATE NOT NULL,

  `available_quantity` INT UNSIGNED NOT NULL DEFAULT 0,

  `purchase_rate` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) NOT NULL,

  `status` ENUM('Active','Expired','Blocked') DEFAULT 'Active',

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`batch_id`),

  KEY `idx_product_id` (`product_id`),

  CONSTRAINT `fk_product_batches_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`product_id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

















-- CREATE TABLE `product_batches` (
--   `batch_id` INT(11) NOT NULL AUTO_INCREMENT,

--   `product_id` INT(11) NOT NULL,

--   `batch_number` VARCHAR(50) NOT NULL,
--   `expiry_date` DATE NOT NULL,

--   `available_quantity` INT(11) NOT NULL DEFAULT 0,

--   `purchase_rate` DECIMAL(10,2) NOT NULL,
--   `mrp` DECIMAL(10,2) NOT NULL,

--   `status` ENUM('Active','Expired','Blocked') DEFAULT 'Active',

--   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
--                ON UPDATE CURRENT_TIMESTAMP,

--   PRIMARY KEY (`batch_id`),

--   KEY `idx_product_id` (`product_id`),
--   KEY `idx_batch_number` (`batch_number`),
--   KEY `idx_expiry_date` (`expiry_date`),
--   KEY `idx_status` (`status`),

--   CONSTRAINT `fk_product_batches_product`
--     FOREIGN KEY (`product_id`)
--     REFERENCES `product` (`product_id`)
--     ON DELETE RESTRICT
-- ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- for expired stock
-- UPDATE product_batches
-- SET status = 'Expired'
-- WHERE expiry_date < CURDATE()
--   AND status = 'Active';
