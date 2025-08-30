-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Database: dpharmasdb

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS `customer_notifications`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `prescription_items`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `product_batches`;
DROP TABLE IF EXISTS `expiry_dates`;
DROP TABLE IF EXISTS `reorder_flags`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `migrations`;

-- Create tables in correct dependency order

-- Independent tables first
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `sex` varchar(10) NOT NULL,
  `email_address` varchar(50) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','restricted','deactivated','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_email_address_unique` (`email_address`),
  UNIQUE KEY `customers_customer_id_unique` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `status` enum('pending','approved','partially_approved','declined','completed') NOT NULL DEFAULT 'pending',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id_string` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_token_unique` (`token`),
  KEY `prescriptions_user_id_foreign` (`user_id`),
  CONSTRAINT `prescriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `manufacturer` varchar(100) NOT NULL,
  `product_type` varchar(50) NOT NULL,
  `form_type` varchar(50) NOT NULL,
  `dosage_unit` varchar(20) NOT NULL,
  `classification` varchar(50) NOT NULL,
  `storage_requirements` text DEFAULT NULL,
  `reorder_level` int(11) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_product_code_unique` (`product_code`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tables with foreign key dependencies
CREATE TABLE `customer_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `prescription_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_notifications_prescription_id_foreign` (`prescription_id`),
  KEY `customer_notifications_customer_id_is_read_index` (`customer_id`,`is_read`),
  KEY `customer_notifications_customer_id_created_at_index` (`customer_id`,`created_at`),
  CONSTRAINT `customer_notifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_notifications_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expiry_dates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expiry_dates_product_id_unique` (`product_id`),
  CONSTRAINT `expiry_dates_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `status` enum('pending','approved','partially_approved','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_id_unique` (`order_id`),
  KEY `orders_prescription_id_foreign` (`prescription_id`),
  KEY `orders_customer_id_index` (`customer_id`),
  CONSTRAINT `orders_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('available','out_of_stock') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prescription_id` (`prescription_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescription_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `expiration_date` date NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0,
  `quantity_remaining` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `received_date` date NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_batches_product_id_batch_number_unique` (`product_id`,`batch_number`),
  KEY `product_batches_supplier_id_foreign` (`supplier_id`),
  KEY `product_batches_product_id_quantity_remaining_index` (`product_id`,`quantity_remaining`),
  KEY `product_batches_product_id_expiration_date_index` (`product_id`,`expiration_date`),
  KEY `product_batches_expiration_date_quantity_remaining_index` (`expiration_date`,`quantity_remaining`),
  CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_batches_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reorder_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reorder_flags_product_id_foreign` (`product_id`),
  CONSTRAINT `reorder_flags_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','online','gcash') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_prescription_id_index` (`prescription_id`),
  KEY `sales_customer_id_index` (`customer_id`),
  KEY `sales_sale_date_index` (`sale_date`),
  KEY `sales_status_index` (`status`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(8,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_items_sale_id_product_id_unique` (`sale_id`,`product_id`),
  KEY `sale_items_sale_id_index` (`sale_id`),
  KEY `sale_items_product_id_index` (`product_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remaining system tables
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data
INSERT INTO `categories` VALUES
(1,'Medicine',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),
(2,'Supplements',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),
(3,'Baby Products',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),
(4,'Medical Supplies',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),
(5,'Vitamins',NULL,'2025-07-19 03:48:46','2025-07-19 03:48:46');

INSERT INTO `customers` VALUES
(1,'CUST000001','John Carlo L. Sumugat','Naba, Culasi Antique','2003-08-08','male','jcsumugatxd@gmail.com','09567460163','$2y$12$9zh.LM3ARCQRZ31GwKzkdeiWaJKKbZX5R9rjbkVwH81WZ9TSmHQIq',NULL,'2025-06-04 00:42:55','2025-06-04 00:42:55','active'),
(9,'9','Jomar Nambong','Binondo, Manila Philippines','2003-09-06','male','jomarnambong@gmail.com','09391520652','$2y$12$fpLSDmNLcmfNMvEfs8koEub/xtaXhtzEZElxqVuEXD4abU8W0paWu',NULL,'2025-08-26 06:01:47','2025-08-26 06:01:47','active');

INSERT INTO `product_batches` VALUES
(3,1,'598-20250825-001','2026-09-09',100,49,4.00,6.00,'2025-08-25',2,NULL,'2025-08-25 12:15:56','2025-08-26 08:25:39'),
(4,1,'598-20250826-001','2025-08-31',100,100,4.00,6.00,'2025-08-26',2,NULL,'2025-08-26 08:27:52','2025-08-26 08:27:52');

INSERT INTO `products` VALUES
(1,'5989','Paracetamol','Mercury Drug','OTC','Tablet','mg','2','Room Temperature',50,NULL,1,2,'Biogesic',NULL,'2025-08-25 11:59:37','2025-08-25 11:59:37');

INSERT INTO `suppliers` VALUES
(1,'Medicine Manufacturer','John Carlo Sumugat','09567460163','jcsumugatxd@gmail.com','Poblacion, Culasi Antique','2025-06-03 14:13:11','2025-06-03 14:13:11'),
(2,'Mercury Drug Store','Mrs. Jane Yap','09567456772','mrsjane@gmail.com','Poblacion, Culasi Antique','2025-06-03 14:13:39','2025-06-03 14:13:39');

INSERT INTO `users` VALUES
(1,'MJS Pharmacy Admin','mjspharmacy@gmail.com','2025-06-03 14:11:07','$2y$12$uTGaKNqnFsuhYWOPAwAwHewoptmEFl2IPP99ojuf/FPVeuZXX6Ptu','admin',NULL,'2025-06-03 14:11:07','2025-06-03 14:11:07'),
(2,'Admin User','admin@example.com',NULL,'$2y$12$seFYAPSUS.InuIt/l9061.cYf5Qkb18mbOy5XyZqSLK6kGCqkQVh.','admin',NULL,'2025-07-19 14:13:47','2025-07-19 14:13:47');

-- Restore settings
SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET SQL_NOTES=@OLD_SQL_NOTES;
