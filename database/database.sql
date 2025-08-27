-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: dpharmasdb
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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Medicine',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),(2,'Supplements',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),(3,'Baby Products',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),(4,'Medical Supplies',NULL,'2025-07-19 03:48:45','2025-07-19 03:48:45'),(5,'Vitamins',NULL,'2025-07-19 03:48:46','2025-07-19 03:48:46');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_notifications`
--

DROP TABLE IF EXISTS `customer_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_notifications`
--

LOCK TABLES `customer_notifications` WRITE;
/*!40000 ALTER TABLE `customer_notifications` DISABLE KEYS */;
INSERT INTO `customer_notifications` VALUES (1,1,NULL,'Order Approved ✅','Good news! Your prescription order #P29 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":29,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-25 05:31:16','2025-08-25 05:31:16'),(2,1,NULL,'Order Approved ✅','Good news! Your prescription order #P29 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":29,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-25 12:32:13','2025-08-25 12:32:13'),(3,1,NULL,'Order Ready for Pickup 🎉','Your prescription order #P29 is ready for pickup! Total amount: ₱12.00. Payment method: Cash. Please bring a valid ID when picking up your medications.','order_ready',0,'{\"prescription_id\":29,\"status\":\"completed\",\"sale_id\":19,\"total_amount\":\"12.00\",\"payment_method\":\"cash\"}','2025-08-25 12:32:18','2025-08-25 12:32:18'),(4,1,NULL,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":30,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-25 14:32:58','2025-08-25 14:32:58'),(5,1,31,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":31,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-25 14:36:25','2025-08-25 14:36:25'),(6,1,31,'Order Approved ✅','Good news! Your prescription order #P31 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":31,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-25 14:51:30','2025-08-25 14:51:30'),(7,1,31,'Order Ready for Pickup 🎉','Your prescription order #P31 is ready for pickup! Total amount: ₱24.00. Payment method: Cash. Please bring a valid ID when picking up your medications.','order_ready',0,'{\"prescription_id\":31,\"status\":\"completed\",\"sale_id\":20,\"total_amount\":\"24.00\",\"payment_method\":\"cash\"}','2025-08-25 14:51:39','2025-08-25 14:51:39'),(8,1,32,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":32,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-25 14:52:10','2025-08-25 14:52:10'),(9,1,32,'Order Approved ✅','Good news! Your prescription order #P32 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":32,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-25 14:52:27','2025-08-25 14:52:27'),(10,1,33,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":33,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-25 15:49:49','2025-08-25 15:49:49'),(11,9,34,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":34,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-26 06:03:17','2025-08-26 06:03:17'),(12,9,34,'Order Approved ✅','Good news! Your prescription order #P34 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":34,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-26 06:04:12','2025-08-26 06:04:12'),(13,9,34,'Order Ready for Pickup 🎉','Your prescription order #P34 is ready for pickup! Total amount: ₱24.00. Payment method: Gcash. Please bring a valid ID when picking up your medications.','order_ready',0,'{\"prescription_id\":34,\"status\":\"completed\",\"sale_id\":21,\"total_amount\":\"24.00\",\"payment_method\":\"gcash\"}','2025-08-26 06:04:32','2025-08-26 06:04:32'),(14,1,35,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":35,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-26 08:15:36','2025-08-26 08:15:36'),(15,1,35,'Order Approved ✅','Good news! Your prescription order #P35 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":35,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-26 08:16:14','2025-08-26 08:16:14'),(16,1,35,'Order Ready for Pickup 🎉','Your prescription order #P35 is ready for pickup! Total amount: ₱30.00. Payment method: Cash. Please bring a valid ID when picking up your medications.','order_ready',0,'{\"prescription_id\":35,\"status\":\"completed\",\"sale_id\":22,\"total_amount\":\"30.00\",\"payment_method\":\"cash\"}','2025-08-26 08:19:20','2025-08-26 08:19:20'),(17,1,33,'Order Approved ✅','Good news! Your prescription order #P33 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":33,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-26 08:20:33','2025-08-26 08:20:33'),(18,1,33,'Order Ready for Pickup 🎉','Your prescription order #P33 is ready for pickup! Total amount: ₱30.00. Payment method: Cash. Please bring a valid ID when picking up your medications.','order_ready',0,'{\"prescription_id\":33,\"status\":\"completed\",\"sale_id\":23,\"total_amount\":\"30.00\",\"payment_method\":\"cash\"}','2025-08-26 08:20:54','2025-08-26 08:20:54'),(19,1,36,'Order Received','Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.','order_received',0,'{\"prescription_id\":36,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}','2025-08-26 08:22:20','2025-08-26 08:22:20'),(20,1,36,'Order Approved ✅','Good news! Your prescription order #P36 has been approved by our pharmacist. Message from pharmacist: Your order is ready to pick up.','order_approved',0,'{\"prescription_id\":36,\"status\":\"approved\",\"admin_message\":\"Your order is ready to pick up.\"}','2025-08-26 08:25:31','2025-08-26 08:25:31'),(21,1,36,'Order Ready for Pickup 🎉','Your prescription order #P36 is ready for pickup! Total amount: ₱186.00. Payment method: Cash. Please bring a valid ID when picking up your medications.','order_ready',1,'{\"prescription_id\":36,\"status\":\"completed\",\"sale_id\":24,\"total_amount\":\"186.00\",\"payment_method\":\"cash\"}','2025-08-26 08:25:39','2025-08-26 08:32:32');
/*!40000 ALTER TABLE `customer_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST000001','John Carlo L. Sumugat','Naba, Culasi Antique','2003-08-08','male','jcsumugatxd@gmail.com','09567460163','$2y$12$9zh.LM3ARCQRZ31GwKzkdeiWaJKKbZX5R9rjbkVwH81WZ9TSmHQIq',NULL,'2025-06-04 00:42:55','2025-06-04 00:42:55','active'),(2,'CUST000002','Jemuel Rosh Barrientos','Malabor Tibiao, Antique','2002-09-09','male','jemuelrosh@gmail.com','09567460165','$2y$12$lMzA4bRmga.JdZwpSLu3Q.VXjMNwWQgUBHOFxkApNYUId2dWWuZMu',NULL,'2025-07-18 04:52:23','2025-07-18 04:52:23','active'),(3,'1','Jay Arthur','xcxvx','2001-09-09','male','jcsumugat@gmail.com','09567460169','$2y$12$pIIY0eABcvmDbZ9DUG5tS.pR7NbTY0qZ2c0.xxYmZX1IEiOcDVaTW',NULL,'2025-07-18 20:11:13','2025-07-18 20:11:13','active'),(4,'2','John Doe','sdgzg','2006-09-09','male','jcsumugatxd123@gmail.com','09567460161','$2y$12$wO8g0lbM/Um0tOzz6zz6WuKaE.B/bi2H6zz11TsKyySotrKMJDZ76',NULL,'2025-07-19 18:17:41','2025-07-19 18:17:41','active'),(5,'3','John Doe','California','2007-09-09','male','jcsumugat1223@gmail.com','09567460162','$2y$12$r0lf.CAHLGp2B6DSmcegD.ydpCBxSoOfaXd6/5Nf88YkRZTKVsmVK',NULL,'2025-07-19 18:38:28','2025-07-19 18:38:28','active'),(6,'4','John Doe','California','2003-08-08','male','jcsumugat123@gmail.com','09567460164','$2y$12$cGf4azNuMR6CF57T0cp44uUbWx9ITR60o9KN1YBlD2vzUS2oJMUzy',NULL,'2025-07-19 18:49:49','2025-07-19 18:49:49','active'),(7,'5','Jay Arthur','Antique','2004-09-01','male','jcsumugat1233@gmail.com','09567460153','$2y$12$Sw91LF8BRcjW1a3eevpPNOyeZjlMT0fn4VpgD4BTuXk3jYbAvShv.',NULL,'2025-07-19 19:11:26','2025-08-25 15:14:23','active'),(8,'8','Jay Arthur','ScASv','2003-08-08','male','jcsumugat12234@gmail.com','09567460167','$2y$12$daNtbv0zVQ7aAGSCpKpDdOoOmM/ApngSojgVhTFUFgo1iU20FyUuS',NULL,'2025-08-03 18:14:22','2025-08-03 18:19:58','deleted'),(9,'9','Jomar Nambong','Binondo, Manila Philippines','2003-09-06','male','jomarnambong@gmail.com','09391520652','$2y$12$fpLSDmNLcmfNMvEfs8koEub/xtaXhtzEZElxqVuEXD4abU8W0paWu',NULL,'2025-08-26 06:01:47','2025-08-26 06:01:47','active');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expiry_dates`
--

DROP TABLE IF EXISTS `expiry_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expiry_dates`
--

LOCK TABLES `expiry_dates` WRITE;
/*!40000 ALTER TABLE `expiry_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `expiry_dates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_05_03_045357_create_customers_table',1),(5,'2025_05_10_000000_create_suppliers_table',1),(6,'2025_05_17_113947_create_products_table',1),(7,'2025_05_18_000637_add_password_to_customers_table',1),(8,'2025_05_19_000956_create_expiry_dates_table',1),(9,'2025_05_24_093535_create_prescriptions_table',1),(10,'2025_05_24_093536_create_orders_table',1),(11,'2025_05_24_093803_create_order_items_table',1),(12,'2025_05_25_110440_create_sales_table',1),(13,'2025_05_25_110626_create_reorder_flags_table',1),(14,'2025_05_25_114922_create_prescription_item_table',1),(15,'2025_05_27_101642_add_user_id_to_prescriptions_table',1),(16,'2025_05_30_104711_add_admin_message_to_prescriptions_table',1),(17,'2025_06_02_150129_add_qr_code_and_admin_message_to_prescriptions_table',1),(18,'2025_06_03_200005_create_notifications_table',1),(19,'2025_07_17_151359_add_order_id_to_prescription_items_table',2),(20,'2025_07_17_165254_add_order_id_to_prescription_items_table',3),(21,'2025_07_17_210354_create_sales_table',4),(22,'2025_07_17_210518_create_sale_items_table',4),(23,'2025_07_18_121614_add_status_to_customers_table',5),(24,'2025_07_19_015901_add_customer_id_to_prescriptions_table',6),(25,'2025_07_19_023031_add_customer_columns_to_prescriptions_table',7),(26,'2025_07_19_030624_add_customer_id_to_customers_table',8),(27,'2025_07_19_104424_create_categories_table',9),(28,'2025_07_19_105436_add_batch_number_and_category_id_to_products_table',10),(29,'2025_07_20_004556_create_notifications_table',11),(30,'2025_07_20_114314_create_admin_users_table',12),(31,'2025_07_22_192359_update_prescriptions_table_for_order_completion',13),(32,'2025_07_22_192704_add_status_and_completed_at_to_orders_table',13),(33,'2025_07_22_192939_create_stock_movements_table',13),(35,'2025_07_23_130237_add_missing_columns_to_orders_and_sales',14),(36,'2025_07_23_130345_add_total_items_to_sales_table',15),(37,'2025_07_23_140338_change_customer_id_to_sales_table',16),(38,'2025_08_08_013046_create_notifications_table',17),(39,'2025_08_20_001417_create_customer_notifications_table',18),(40,'2025_08_20_150458_stocks_updates_table',18),(41,'2025_08_25_014958_create_product_batches_table',18),(42,'2025_08_25_105426_fix_classification_field_type_in_products_table',19),(43,'2025_08_25_223212_add_columns_to_prescriptions_table',20);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'Low Stock Alert','Paracetamol 500mg is running low. Current stock: 8 units.',1,'2025-08-09 06:42:29','2025-08-25 15:03:38'),(2,1,'New Order Received','New prescription order #P123 has been submitted and requires review.',1,'2025-08-09 06:42:29','2025-08-25 15:03:38'),(3,1,'Out of Stock Alert','Amoxicillin 250mg is now out of stock. Immediate restocking required.',1,'2025-08-09 06:42:29','2025-08-25 15:03:38'),(4,1,'New Order Received','New prescription order #P29 received from . Status: pending.',1,'2025-08-09 08:26:35','2025-08-25 15:03:38'),(5,1,'Order Approved','Prescription order #P29 for  has been approved and is ready for processing.',1,'2025-08-09 08:26:58','2025-08-13 04:46:58'),(6,1,'Order Approved','Prescription order #P29 for jcsumugatxd@gmail.com has been approved and is ready for processing.',1,'2025-08-25 05:31:16','2025-08-25 15:03:38'),(7,1,'Order Approved','Prescription order #P29 for jcsumugatxd@gmail.com has been approved and is ready for processing.',1,'2025-08-25 12:32:13','2025-08-25 15:03:38'),(8,1,'Order Completed','Sale #19 completed for jcsumugatxd@gmail.com. Total amount: ₱12.00. Payment method: cash.',1,'2025-08-25 12:32:18','2025-08-25 15:03:38'),(9,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',1,'2025-08-25 14:32:58','2025-08-25 15:03:38'),(10,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',1,'2025-08-25 14:36:25','2025-08-25 15:03:38'),(11,1,'Order Approved','Prescription order #P31 for jcsumugatxd@gmail.com has been approved and is ready for processing.',1,'2025-08-25 14:51:30','2025-08-25 15:03:38'),(12,1,'Order Completed','Sale #20 completed for jcsumugatxd@gmail.com. Total amount: ₱24.00. Payment method: cash.',1,'2025-08-25 14:51:39','2025-08-25 15:03:38'),(13,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',1,'2025-08-25 14:52:10','2025-08-25 15:03:38'),(14,1,'Order Approved','Prescription order #P32 for jcsumugatxd@gmail.com has been approved and is ready for processing.',1,'2025-08-25 14:52:27','2025-08-25 15:03:38'),(15,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',0,'2025-08-25 15:49:49','2025-08-25 15:49:49'),(16,1,'New Order Received','New order received from jomarnambong@gmail.com. Status: pending.',0,'2025-08-26 06:03:17','2025-08-26 06:03:17'),(17,1,'Order Approved','Prescription order #P34 for jomarnambong@gmail.com has been approved and is ready for processing.',0,'2025-08-26 06:04:12','2025-08-26 06:04:12'),(18,1,'Order Completed','Sale #21 completed for jomarnambong@gmail.com. Total amount: ₱24.00. Payment method: gcash.',0,'2025-08-26 06:04:32','2025-08-26 06:04:32'),(19,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',0,'2025-08-26 08:15:36','2025-08-26 08:15:36'),(20,1,'Order Approved','Prescription order #P35 for jcsumugatxd@gmail.com has been approved and is ready for processing.',0,'2025-08-26 08:16:14','2025-08-26 08:16:14'),(21,1,'Order Completed','Sale #22 completed for jcsumugatxd@gmail.com. Total amount: ₱30.00. Payment method: cash.',0,'2025-08-26 08:19:20','2025-08-26 08:19:20'),(22,1,'Order Approved','Prescription order #P33 for jcsumugatxd@gmail.com has been approved and is ready for processing.',0,'2025-08-26 08:20:33','2025-08-26 08:20:33'),(23,1,'Order Completed','Sale #23 completed for jcsumugatxd@gmail.com. Total amount: ₱30.00. Payment method: cash.',0,'2025-08-26 08:20:54','2025-08-26 08:20:54'),(24,1,'New Order Received','New order received from jcsumugatxd@gmail.com. Status: pending.',0,'2025-08-26 08:22:20','2025-08-26 08:22:20'),(25,1,'Order Approved','Prescription order #P36 for jcsumugatxd@gmail.com has been approved and is ready for processing.',0,'2025-08-26 08:25:31','2025-08-26 08:25:31'),(26,1,'Order Completed','Sale #24 completed for jcsumugatxd@gmail.com. Total amount: ₱186.00. Payment method: cash.',0,'2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (38,35,1,4,1,'2025-08-25 14:51:39','2025-08-25 14:51:39'),(39,38,1,4,1,'2025-08-26 06:04:32','2025-08-26 06:04:32'),(40,39,1,5,1,'2025-08-26 08:19:20','2025-08-26 08:19:20'),(41,37,1,5,1,'2025-08-26 08:20:54','2025-08-26 08:20:54'),(42,40,1,31,1,'2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (35,NULL,31,'RX00001','completed','2025-08-25 14:36:25','2025-08-25 14:51:39','2025-08-25 14:51:39'),(36,NULL,32,'RX00036','approved','2025-08-25 14:52:10','2025-08-25 14:52:27',NULL),(37,NULL,33,'RX00037','completed','2025-08-25 15:49:49','2025-08-26 08:20:54','2025-08-26 08:20:54'),(38,NULL,34,'RX00038','completed','2025-08-26 06:03:17','2025-08-26 06:04:32','2025-08-26 06:04:32'),(39,NULL,35,'RX00039','completed','2025-08-26 08:15:36','2025-08-26 08:19:20','2025-08-26 08:19:20'),(40,NULL,36,'RX00040','completed','2025-08-26 08:22:20','2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_items`
--

LOCK TABLES `prescription_items` WRITE;
/*!40000 ALTER TABLE `prescription_items` DISABLE KEYS */;
INSERT INTO `prescription_items` VALUES (80,31,1,4,'available','2025-08-25 14:51:35','2025-08-25 14:51:35'),(82,34,1,4,'available','2025-08-26 06:04:06','2025-08-26 06:04:06'),(83,35,1,5,'available','2025-08-26 08:16:57','2025-08-26 08:16:57'),(84,33,1,5,'available','2025-08-26 08:20:25','2025-08-26 08:20:25'),(85,32,1,31,'available','2025-08-26 08:21:58','2025-08-26 08:21:58'),(86,36,1,31,'available','2025-08-26 08:25:20','2025-08-26 08:25:20');
/*!40000 ALTER TABLE `prescription_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (31,1,'Screenshot 2025-05-16 085104.png','image/png',6688,'09567460163','asasas','prescriptions/encrypted/1756132585_MJezip_customer_1.enc','qrcodes/RX00001.svg','20z9XpojvBks2psSR60hODi888TKCLPF','completed',1,NULL,'2025-08-25 14:36:25','2025-08-25 14:51:39',NULL,'Your order is ready to pick up.','1'),(32,1,'Screenshot 2025-05-16 085104.png','image/png',6688,'09567460163',NULL,'prescriptions/encrypted/1756133530_mbHw3b_customer_1.enc','qrcodes/RX00036.svg','0hluOwmRzv4WXPRIiSoMAjKeUcgOq5uF','approved',1,NULL,'2025-08-25 14:52:10','2025-08-25 14:52:27',NULL,'Your order is ready to pick up.','1'),(33,1,'Screenshot 2025-05-16 232006.png','image/png',62655,'09567460163','Axxa','prescriptions/encrypted/1756136989_y0D7ls_customer_1.enc','qrcodes/RX00037.svg','m97j9QRfa7IXcm0Hj6gIC1EsRyfxV8fz','completed',1,NULL,'2025-08-25 15:49:49','2025-08-26 08:20:54',NULL,'Your order is ready to pick up.','1'),(34,1,'Screenshot 2025-05-16 085104.png','image/png',6688,'09567460163','qweqe','prescriptions/encrypted/1756188195_kUDd9Z_customer_9.enc','qrcodes/RX00038.svg','vApDHSeoTc8XQx51IpKlXjq5hYDoeLBS','completed',9,NULL,'2025-08-26 06:03:17','2025-08-26 06:04:32',NULL,'Your order is ready to pick up.','9'),(35,1,'Screenshot 2025-05-16 085104.png','image/png',6688,'09567460163',NULL,'prescriptions/encrypted/1756196135_RRw9Vm_customer_1.enc','qrcodes/RX00039.svg','VnEyYsaGPNOPAmkJc1EeL6BjxTJjddPn','completed',1,NULL,'2025-08-26 08:15:36','2025-08-26 08:19:20',NULL,'Your order is partially approved due to stock shortages of some products.','1'),(36,1,'Screenshot 2025-05-16 085104.png','image/png',6688,'09567460163',NULL,'prescriptions/encrypted/1756196540_Skv7DH_customer_1.enc','qrcodes/RX00040.svg','wmPhkNFPf77QiXXUmh9Lji59iaOM9KyR','completed',1,NULL,'2025-08-26 08:22:20','2025-08-26 08:25:39',NULL,'Your order is ready to pick up.','1');
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  KEY `product_batches_batch_number_index` (`batch_number`),
  KEY `product_batches_expiration_date_index` (`expiration_date`),
  KEY `product_batches_quantity_remaining_index` (`quantity_remaining`),
  KEY `product_batches_received_date_index` (`received_date`),
  CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_batches_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES (3,1,'598-20250825-001','2026-09-09',100,49,4.00,6.00,'2025-08-25',2,NULL,'2025-08-25 12:15:56','2025-08-26 08:25:39'),(4,1,'598-20250826-001','2025-08-31',100,100,4.00,6.00,'2025-08-26',2,NULL,'2025-08-26 08:27:52','2025-08-26 08:27:52');
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'5989','Paracetamol','Mercury Drug','OTC','Tablet','mg','2','Room Temperature',50,NULL,1,2,'Biogesic',NULL,'2025-08-25 11:59:37','2025-08-25 11:59:37');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reorder_flags`
--

DROP TABLE IF EXISTS `reorder_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reorder_flags`
--

LOCK TABLES `reorder_flags` WRITE;
/*!40000 ALTER TABLE `reorder_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `reorder_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (26,20,1,4,6.00,24.00,'2025-08-25 14:51:39','2025-08-25 14:51:39'),(27,21,1,4,6.00,24.00,'2025-08-26 06:04:32','2025-08-26 06:04:32'),(28,22,1,5,6.00,30.00,'2025-08-26 08:19:20','2025-08-26 08:19:20'),(29,23,1,5,6.00,30.00,'2025-08-26 08:20:54','2025-08-26 08:20:54'),(30,24,1,31,6.00,186.00,'2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (20,4,31,35,1,24.00,'2025-08-25 14:51:39','completed','cash',NULL,'2025-08-25 14:51:39','2025-08-25 14:51:39'),(21,4,34,38,9,24.00,'2025-08-26 06:04:32','completed','gcash',NULL,'2025-08-26 06:04:32','2025-08-26 06:04:32'),(22,5,35,39,1,30.00,'2025-08-26 08:19:20','completed','cash',NULL,'2025-08-26 08:19:20','2025-08-26 08:19:20'),(23,5,33,37,1,30.00,'2025-08-26 08:20:54','completed','cash',NULL,'2025-08-26 08:20:54','2025-08-26 08:20:54'),(24,31,36,40,1,186.00,'2025-08-26 08:25:39','completed','cash',NULL,'2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (20,1,'sale',-2,'sale',19,'Sale for prescription #29, batch 3','2025-08-25 12:32:18','2025-08-25 12:32:18'),(21,1,'sale',-4,'sale',20,'Sale for prescription #31, batch 3','2025-08-25 14:51:39','2025-08-25 14:51:39'),(22,1,'sale',-4,'sale',21,'Sale for prescription #34, batch 3','2025-08-26 06:04:32','2025-08-26 06:04:32'),(23,1,'sale',-5,'sale',22,'Sale for prescription #35, batch 3','2025-08-26 08:19:20','2025-08-26 08:19:20'),(24,1,'sale',-5,'sale',23,'Sale for prescription #33, batch 3','2025-08-26 08:20:54','2025-08-26 08:20:54'),(25,1,'sale',-31,'sale',24,'Sale for prescription #36, batch 3','2025-08-26 08:25:39','2025-08-26 08:25:39');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Medicine Manufacturer','John Carlo Sumugat','09567460163','jcsumugatxd@gmail.com','Poblacion, Culasi Antique','2025-06-03 14:13:11','2025-06-03 14:13:11'),(2,'Mercury Drug Store','Mrs. Jane Yap','09567456772','mrsjane@gmail.com','Poblacion, Culasi Antique','2025-06-03 14:13:39','2025-06-03 14:13:39');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'MJS Pharmacy Admin','mjspharmacy@gmail.com','2025-06-03 14:11:07','$2y$12$uTGaKNqnFsuhYWOPAwAwHewoptmEFl2IPP99ojuf/FPVeuZXX6Ptu','admin',NULL,'2025-06-03 14:11:07','2025-06-03 14:11:07'),(2,'Admin User','admin@example.com',NULL,'$2y$12$seFYAPSUS.InuIt/l9061.cYf5Qkb18mbOy5XyZqSLK6kGCqkQVh.','admin',NULL,'2025-07-19 14:13:47','2025-07-19 14:13:47');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-27 12:03:54
