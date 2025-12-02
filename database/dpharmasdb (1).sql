-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 01:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_orders`
--

CREATE TABLE `cancelled_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `cancellation_reason` varchar(255) NOT NULL,
  `additional_message` text DEFAULT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Medicine', NULL, '2025-07-19 03:48:45', '2025-07-19 03:48:45'),
(2, 'Supplements', NULL, '2025-07-19 03:48:45', '2025-07-19 03:48:45'),
(3, 'Baby Products', NULL, '2025-07-19 03:48:45', '2025-07-19 03:48:45'),
(4, 'Medical Supplies', NULL, '2025-07-19 03:48:45', '2025-07-19 03:48:45'),
(5, 'Vitamins', NULL, '2025-07-19 03:48:46', '2025-07-19 03:48:46');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','file','image','system') NOT NULL DEFAULT 'text',
  `is_from_customer` tinyint(1) NOT NULL DEFAULT 1,
  `is_internal_note` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `customer_id`, `admin_id`, `message`, `message_type`, `is_from_customer`, `is_internal_note`, `read_at`, `created_at`, `updated_at`) VALUES
(16, 11, NULL, 1, 'Good morning sir', 'text', 0, 0, NULL, '2025-09-23 16:21:27', '2025-09-23 16:21:27'),
(17, 11, 2, NULL, '', 'file', 1, 0, NULL, '2025-09-23 16:21:56', '2025-09-23 16:21:56'),
(18, 14, NULL, 1, 'Hello', 'text', 0, 0, NULL, '2025-09-24 06:31:00', '2025-09-24 06:31:00'),
(19, 11, NULL, 1, 'hello', 'text', 0, 0, NULL, '2025-09-24 06:32:16', '2025-09-24 06:32:16'),
(20, 11, 2, NULL, 'Test', 'text', 1, 0, NULL, '2025-09-24 14:51:30', '2025-09-24 14:51:30'),
(21, 11, 2, NULL, 'safasfasfs', 'text', 1, 0, NULL, '2025-09-24 14:51:50', '2025-09-24 14:51:50'),
(22, 11, 2, NULL, 'jk', 'text', 1, 0, NULL, '2025-09-24 15:07:20', '2025-09-24 15:07:20'),
(23, 11, NULL, 1, 'ad', 'text', 0, 0, NULL, '2025-09-24 15:30:10', '2025-09-24 15:30:10'),
(24, 11, 2, NULL, 'hi', 'text', 1, 0, NULL, '2025-09-25 02:25:14', '2025-09-25 02:25:14'),
(25, 11, NULL, 1, 'Hello', 'text', 0, 0, NULL, '2025-09-25 02:25:24', '2025-09-25 02:25:24'),
(26, 11, NULL, 1, 'shdgc;a', 'text', 0, 0, NULL, '2025-09-25 03:39:15', '2025-09-25 03:39:15'),
(27, 11, 2, NULL, '', 'file', 1, 0, NULL, '2025-09-25 03:39:28', '2025-09-25 03:39:28'),
(28, 11, 2, NULL, '', 'file', 1, 0, NULL, '2025-09-29 07:53:08', '2025-09-29 07:53:08'),
(29, 11, 2, NULL, 'q', 'text', 1, 0, NULL, '2025-09-29 07:53:22', '2025-09-29 07:53:22'),
(30, 14, NULL, 1, 'efe', 'text', 0, 0, NULL, '2025-09-29 09:11:56', '2025-09-29 09:11:56'),
(31, 11, NULL, 1, 'j', 'text', 0, 0, NULL, '2025-09-29 09:16:09', '2025-09-29 09:16:09'),
(32, 11, NULL, 1, '', 'file', 0, 0, NULL, '2025-09-29 09:16:18', '2025-09-29 09:16:18'),
(33, 11, 2, NULL, 'Hi', 'text', 1, 0, NULL, '2025-10-07 02:15:10', '2025-10-07 02:15:10'),
(34, 11, NULL, 1, 'hELLO', 'text', 0, 0, NULL, '2025-10-07 02:16:48', '2025-10-07 02:16:48'),
(35, 11, 2, NULL, '', 'file', 1, 0, NULL, '2025-10-07 06:18:20', '2025-10-07 06:18:20'),
(36, 11, NULL, 1, 'wow', 'text', 0, 0, NULL, '2025-10-07 06:18:35', '2025-10-07 06:18:35'),
(37, 11, 2, NULL, 'hello', 'text', 1, 0, NULL, '2025-10-11 03:15:48', '2025-10-11 03:15:48'),
(38, 11, NULL, 1, 'Hi po', 'text', 0, 0, NULL, '2025-10-11 03:16:00', '2025-10-11 03:16:00'),
(39, 11, NULL, 1, 'how can i help you?', 'text', 0, 0, NULL, '2025-10-11 03:16:07', '2025-10-11 03:16:07'),
(40, 11, 2, NULL, '', 'file', 1, 0, NULL, '2025-10-11 03:16:27', '2025-10-11 03:16:27'),
(41, 11, 2, NULL, 'hi', 'text', 1, 0, NULL, '2025-11-18 07:43:11', '2025-11-18 07:43:11');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` enum('prescription_inquiry','order_concern','general_support','complaint','product_inquiry') NOT NULL,
  `status` enum('active','resolved','closed','pending') NOT NULL DEFAULT 'active',
  `priority` enum('normal','high','urgent') NOT NULL DEFAULT 'normal',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `customer_id`, `admin_id`, `title`, `type`, `status`, `priority`, `last_message_at`, `created_at`, `updated_at`) VALUES
(11, 2, NULL, 'Chat with John Carlo L. Sumugat', 'general_support', 'active', 'normal', '2025-11-18 07:43:11', '2025-09-23 16:03:53', '2025-11-18 07:43:11'),
(12, 1, 1, 'Chat with Jay Arthur', 'general_support', 'active', 'normal', '2025-09-23 16:22:42', '2025-09-23 16:22:42', '2025-09-23 16:22:42'),
(13, 3, 1, 'Chat with Jomar Nambong', 'general_support', 'active', 'normal', '2025-09-24 02:36:11', '2025-09-24 02:36:11', '2025-09-24 02:36:11'),
(14, 4, 1, 'Chat with Ailyn Alolod', 'general_support', 'active', 'normal', '2025-09-29 09:11:56', '2025-09-24 06:27:55', '2025-09-29 09:11:56');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `customer_id`, `admin_id`, `last_read_message_id`, `joined_at`, `left_at`, `created_at`, `updated_at`) VALUES
(21, 11, NULL, 1, 16, '2025-09-23 16:21:27', NULL, '2025-09-23 16:21:27', '2025-09-23 16:21:27'),
(22, 12, 1, NULL, NULL, '2025-09-23 16:22:42', NULL, '2025-09-23 16:22:42', '2025-09-23 16:22:42'),
(23, 12, NULL, 1, NULL, '2025-09-23 16:22:42', NULL, '2025-09-23 16:22:42', '2025-09-23 16:22:42'),
(24, 13, 3, NULL, NULL, '2025-09-24 02:36:11', NULL, '2025-09-24 02:36:11', '2025-09-24 02:36:11'),
(25, 13, NULL, 1, NULL, '2025-09-24 02:36:11', NULL, '2025-09-24 02:36:11', '2025-09-24 02:36:11'),
(26, 14, 4, NULL, NULL, '2025-09-24 06:27:55', NULL, '2025-09-24 06:27:55', '2025-09-24 06:27:55'),
(27, 14, NULL, 1, 18, '2025-09-24 06:27:55', NULL, '2025-09-24 06:27:55', '2025-09-24 06:31:00'),
(28, 11, NULL, NULL, 16, '2025-10-07 02:14:53', NULL, '2025-10-07 02:14:53', '2025-10-07 02:14:53');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
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
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `auto_restore_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `address`, `birthdate`, `sex`, `email_address`, `contact_number`, `password`, `deleted_at`, `created_at`, `updated_at`, `status`, `status_changed_at`, `auto_restore_at`) VALUES
(1, 1, 'Jay Arthur', 'cCZcZc', '2002-09-09', 'male', 'jcsumugat12223@gmail.com', '09567460173', '$2y$12$24S3GiOSPd5MqV4BAiuWlOypKJz/9YR/e7JhdN9dpBRm2XN6kLk1C', NULL, '2025-09-23 15:59:46', '2025-09-23 15:59:46', 'active', NULL, NULL),
(18, 2, 'John Carlo L. Sumugat', 'Culasi Antique', '2003-08-08', 'male', 'jcsumugatxd@gmail.com', '09567460163', '$2y$12$6inO5qrgeKlZYGlMY7mHluA0qENFIgePqpxfxEaQ7LCYhJha0Nlja', NULL, '2025-09-23 16:03:04', '2025-10-03 14:23:55', 'active', '2025-10-03 14:23:55', NULL),
(19, 3, 'Jomar Nambong', 'Malabon, Philippines', '2003-09-09', 'male', 'jomarnambongxd@gmail.com', '09567460162', '$2y$12$Wtj30v.2gChwfU3lw89q8OAM1qRVgtdf0MXPZyIS83i06FX4luNA.', NULL, '2025-09-23 16:30:29', '2025-09-23 16:30:29', 'active', NULL, NULL),
(20, 4, 'Ailyn Alolod', 'Sta. Ana', '2002-06-12', 'female', 'ailynalolod2@gmail.com', '09264017009', '$2y$12$LuzozJLube/uRlNlC9LsMeZmek7rsfCXXmykE8q40s9ouktT8OggG', NULL, '2025-09-24 06:14:33', '2025-10-08 16:27:40', 'active', '2025-10-08 16:27:40', NULL),
(21, 5, 'Jay Arthur', 'Malabor', '2003-09-09', 'male', 'jcsumugat122333@gmail.com', '09567460169', '$2y$12$sE/DIF8W0uNBNvaD1WAj2uSmE9/Hy.FJUGAf9C7BMaGOuFhIQv/4G', NULL, '2025-09-25 02:22:25', '2025-10-03 14:54:04', 'active', '2025-10-03 14:54:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers_chat`
--

CREATE TABLE `customers_chat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_active` timestamp NULL DEFAULT NULL,
  `chat_status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers_chat`
--

INSERT INTO `customers_chat` (`id`, `customer_id`, `email_address`, `full_name`, `is_online`, `last_active`, `chat_status`, `created_at`, `updated_at`) VALUES
(11, 1, 'jcsumugat12223@gmail.com', 'Jay Arthur', 0, '2025-09-23 15:59:46', 'offline', '2025-09-23 15:59:46', '2025-09-23 15:59:46'),
(12, 2, 'jcsumugatxd@gmail.com', 'John Carlo L. Sumugat', 1, '2025-12-02 12:37:05', 'online', '2025-09-23 16:03:04', '2025-12-02 12:37:05'),
(13, 3, 'jomarnambongxd@gmail.com', 'Jomar Nambong', 0, '2025-09-23 16:30:29', 'offline', '2025-09-23 16:30:29', '2025-09-23 16:30:29'),
(14, 4, 'ailynalolod2@gmail.com', 'Ailyn Alolod', 0, '2025-09-24 06:15:46', 'offline', '2025-09-24 06:14:33', '2025-09-24 06:15:46'),
(15, 5, 'jcsumugat122333@gmail.com', 'Jay Arthur', 0, '2025-09-25 02:22:25', 'offline', '2025-09-25 02:22:25', '2025-09-25 02:22:25');

-- --------------------------------------------------------

--
-- Table structure for table `customer_notifications`
--

CREATE TABLE `customer_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `prescription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_notifications`
--

INSERT INTO `customer_notifications` (`id`, `customer_id`, `prescription_id`, `title`, `message`, `type`, `is_read`, `data`, `created_at`, `updated_at`) VALUES
(141, 18, 137, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":137,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-06 02:13:49', '2025-10-06 02:13:49'),
(142, 18, 138, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":138,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-06 02:14:45', '2025-10-06 02:14:45'),
(143, 18, 139, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":139,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 02:28:14', '2025-10-07 02:28:14'),
(144, 18, 140, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":140,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 05:07:31', '2025-10-07 05:07:31'),
(145, 18, 141, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":141,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 05:11:22', '2025-10-07 05:11:22'),
(146, 18, 142, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":142,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 05:12:20', '2025-10-07 05:12:20'),
(147, 18, 143, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":143,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 05:14:56', '2025-10-07 05:14:56'),
(148, 18, 144, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":144,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-10-07 06:16:45', '2025-10-07 06:16:45'),
(149, 18, 139, 'Order Ready for Pickup 🎉', 'Your prescription order #P139 is ready for pickup! Total amount: ₱28.00. Payment method: Cash. Please bring a valid ID when picking up your medications.', 'order_ready', 0, '{\"prescription_id\":139,\"status\":\"completed\",\"sale_id\":41,\"total_amount\":\"28.00\",\"payment_method\":\"cash\"}', '2025-10-08 16:21:50', '2025-10-08 16:21:50'),
(150, 18, 143, 'Order Ready for Pickup 🎉', 'Your prescription order #P143 is ready for pickup! Total amount: ₱14.00. Payment method: Cash. Please bring a valid ID when picking up your medications.', 'order_ready', 0, '{\"prescription_id\":143,\"status\":\"completed\",\"sale_id\":42,\"total_amount\":\"14.00\",\"payment_method\":\"cash\"}', '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(151, 18, 145, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":145,\"status\":\"pending\",\"mobile_number\":\"09264017009\"}', '2025-10-11 03:26:10', '2025-10-11 03:26:10'),
(152, 18, 145, 'Order Ready for Pickup 🎉', 'Your prescription order #P145 is ready for pickup! Total amount: ₱35.00. Payment method: Cash. Please bring a valid ID when picking up your medications.', 'order_ready', 0, '{\"prescription_id\":145,\"status\":\"completed\",\"sale_id\":43,\"total_amount\":\"35.00\",\"payment_method\":\"cash\"}', '2025-10-11 03:26:52', '2025-10-11 03:26:52'),
(153, 18, 146, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":146,\"status\":\"pending\",\"mobile_number\":\"09567460164\"}', '2025-11-18 08:05:33', '2025-11-18 08:05:33'),
(154, 18, 147, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":147,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-11-18 08:08:06', '2025-11-18 08:08:06'),
(155, 18, 148, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":148,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-12-02 02:40:46', '2025-12-02 02:40:46'),
(156, 18, 149, 'Order Received', 'Your order has been received and is being reviewed. You will receive updates on the status of your order.', 'order_received', 0, '{\"prescription_id\":149,\"status\":\"pending\",\"mobile_number\":\"09567460163\"}', '2025-12-02 02:41:41', '2025-12-02 02:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `expiry_dates`
--

CREATE TABLE `expiry_dates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_attachments`
--

INSERT INTO `message_attachments` (`id`, `message_id`, `file_name`, `file_path`, `file_size`, `file_type`, `mime_type`, `created_at`, `updated_at`) VALUES
(10, 17, 'carbonara.png', 'chat-attachments/2025/09/It9b1ZCrV8zJePbV1SRPmXEuIwJOAxoV8ROWkeRY.png', 130742, 'png', 'image/png', '2025-09-23 16:21:57', '2025-09-23 16:21:57'),
(11, 27, 'images (3).jpeg', 'chat-attachments/2025/09/HrOpvvdcU8Jy1WUWRpEAdm82QOsRtgefwYjwRqIF.jpg', 19616, 'jpeg', 'image/jpeg', '2025-09-25 03:39:28', '2025-09-25 03:39:28'),
(12, 28, 'images (3).jpeg', 'chat-attachments/2025/09/j8g6d7aw5FrYOFE14hCYnRySDkbVOXfkEpCPAs9x.jpg', 19616, 'jpeg', 'image/jpeg', '2025-09-29 07:53:09', '2025-09-29 07:53:09'),
(13, 32, 'images (3).jpeg', 'chat-attachments/2025/09/dRhZgeRuxmalwYFRCQMrjnfFFKxlrFpGe72k58hQ.jpg', 19616, 'jpeg', 'image/jpeg', '2025-09-29 09:16:18', '2025-09-29 09:16:18'),
(14, 35, 'OIP (1).jpg', 'chat-attachments/2025/10/AdkOisJtanfgjbI2OuxGKDiAsUJoCySylj6LxgGV.jpg', 15159, 'jpg', 'image/jpeg', '2025-10-07 06:18:20', '2025-10-07 06:18:20'),
(15, 40, '17601525723088197777539015838333.jpg', 'chat-attachments/2025/10/O7kLqqtfNhKeWhLvUyfHPQ20hfAwavhg50gD4N4m.jpg', 1720097, 'jpg', 'image/jpeg', '2025-10-11 03:16:28', '2025-10-11 03:16:28');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_05_03_045357_create_customers_table', 1),
(5, '2025_05_10_000000_create_suppliers_table', 1),
(6, '2025_05_17_113947_create_products_table', 1),
(7, '2025_05_18_000637_add_password_to_customers_table', 1),
(8, '2025_05_19_000956_create_expiry_dates_table', 1),
(9, '2025_05_24_093535_create_prescriptions_table', 1),
(10, '2025_05_24_093536_create_orders_table', 1),
(11, '2025_05_24_093803_create_order_items_table', 1),
(12, '2025_05_25_110440_create_sales_table', 1),
(13, '2025_05_25_110626_create_reorder_flags_table', 1),
(14, '2025_05_25_114922_create_prescription_item_table', 1),
(15, '2025_05_27_101642_add_user_id_to_prescriptions_table', 1),
(16, '2025_05_30_104711_add_admin_message_to_prescriptions_table', 1),
(17, '2025_06_02_150129_add_qr_code_and_admin_message_to_prescriptions_table', 1),
(18, '2025_06_03_200005_create_notifications_table', 1),
(19, '2025_07_17_151359_add_order_id_to_prescription_items_table', 2),
(20, '2025_07_17_165254_add_order_id_to_prescription_items_table', 3),
(21, '2025_07_17_210354_create_sales_table', 4),
(22, '2025_07_17_210518_create_sale_items_table', 4),
(23, '2025_07_18_121614_add_status_to_customers_table', 5),
(24, '2025_07_19_015901_add_customer_id_to_prescriptions_table', 6),
(25, '2025_07_19_023031_add_customer_columns_to_prescriptions_table', 7),
(26, '2025_07_19_030624_add_customer_id_to_customers_table', 8),
(27, '2025_07_19_104424_create_categories_table', 9),
(28, '2025_07_19_105436_add_batch_number_and_category_id_to_products_table', 10),
(29, '2025_07_20_004556_create_notifications_table', 11),
(30, '2025_07_20_114314_create_admin_users_table', 12),
(31, '2025_07_22_192359_update_prescriptions_table_for_order_completion', 13),
(32, '2025_07_22_192704_add_status_and_completed_at_to_orders_table', 13),
(33, '2025_07_22_192939_create_stock_movements_table', 13),
(35, '2025_07_23_130237_add_missing_columns_to_orders_and_sales', 14),
(36, '2025_07_23_130345_add_total_items_to_sales_table', 15),
(37, '2025_07_23_140338_change_customer_id_to_sales_table', 16),
(38, '2025_08_08_013046_create_notifications_table', 17),
(39, '2025_08_20_001417_create_customer_notifications_table', 18),
(40, '2025_08_20_150458_stocks_updates_table', 18),
(41, '2025_08_25_014958_create_product_batches_table', 18),
(42, '2025_08_25_105426_fix_classification_field_type_in_products_table', 19),
(43, '2025_08_25_223212_add_columns_to_prescriptions_table', 20),
(44, '2025_08_27_223218_add_batch_id_to_prescription_items_table', 21),
(45, '2025_08_28_123415_create_categories_table', 22),
(46, '2025_08_31_230627_add_stock_quantity_and_indexes_to_products', 22),
(47, '2025_08_31_234110_add_batch_id_to_stock_movements_table', 22),
(48, '2025_09_03_024600_add_order_type_to_prescriptions_table', 23),
(49, '2025_09_03_115642_create_prescription_messages_table', 24),
(50, '2025_09_14_225032_create_conversations_table', 25),
(51, '2025_09_14_225035_create_messages_table', 26),
(52, '2025_09_14_225038_create_conversation_participants_table', 27),
(53, '2025_09_14_235859_create_table_1', 28),
(54, '2025_09_14_235902_create_table_2', 28),
(55, '2025_09_14_235905_create_table_3', 28),
(56, '2025_09_14_235909_create_table_4', 28),
(57, '2025_09_14_235912_create_table_5', 28),
(58, '2025_09_15_000049_create_table_6', 28),
(59, '2025_09_18_205553_create_cancelled_orders_table', 29),
(60, '2025_09_23_140155_cleanup_customer_ids_table', 30),
(61, '2025_09_24_233722_create_sessions_table', 31),
(62, '2025_10_04_235128_add_duplicate_detection_fields_to_prescriptions_table', 31);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `updated_at`) VALUES
(45, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-23 17:04:25', '2025-09-23 17:04:51'),
(46, 1, 'Order Completed', 'Sale #32 completed for jcsumugatxd@gmail.com. Total amount: ₱30.00. Payment method: cash.', 1, '2025-09-23 17:42:43', '2025-09-25 03:03:51'),
(47, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-23 17:44:49', '2025-09-25 03:03:51'),
(48, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-23 18:06:23', '2025-09-25 03:03:51'),
(49, 1, 'New Order Received', 'New order received from ailynalolod2@gmail.com. Status: pending.', 1, '2025-09-24 06:26:16', '2025-09-25 03:03:51'),
(50, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-24 06:48:34', '2025-09-25 03:03:51'),
(51, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-24 07:10:56', '2025-09-25 03:03:51'),
(52, 1, 'Order Completed', 'Sale #33 completed for jcsumugatxd@gmail.com. Total amount: ₱30.00. Payment method: cash.', 1, '2025-09-24 15:39:11', '2025-09-25 03:03:51'),
(53, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 01:40:31', '2025-09-25 03:03:51'),
(54, 1, 'Order Completed', 'Sale #34 completed for jcsumugatxd@gmail.com. Total amount: ₱26.00. Payment method: cash.', 1, '2025-09-25 01:41:15', '2025-09-25 03:03:51'),
(55, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 02:07:43', '2025-09-25 03:03:51'),
(56, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 02:24:01', '2025-09-25 03:03:51'),
(57, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 02:47:42', '2025-09-25 03:03:51'),
(58, 1, 'Order Completed', 'Sale #35 completed for jcsumugatxd@gmail.com. Total amount: ₱105.00. Payment method: cash.', 1, '2025-09-25 02:50:15', '2025-09-25 03:03:51'),
(59, 1, 'Order Completed', 'Sale #36 completed for jcsumugatxd@gmail.com. Total amount: ₱78.00. Payment method: cash.', 1, '2025-09-25 02:55:44', '2025-09-25 03:03:51'),
(60, 1, 'Order Completed', 'Sale #37 completed for jcsumugatxd@gmail.com. Total amount: ₱17.00. Payment method: cash.', 1, '2025-09-25 02:56:22', '2025-09-25 03:03:51'),
(61, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 03:12:01', '2025-10-07 02:17:07'),
(62, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 03:12:16', '2025-10-07 02:17:07'),
(63, 1, 'Order Completed', 'Sale #38 completed for jcsumugatxd@gmail.com. Total amount: ₱90.00. Payment method: cash.', 1, '2025-09-25 03:13:29', '2025-10-07 02:17:07'),
(64, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 03:33:49', '2025-10-07 02:17:07'),
(65, 1, 'Order Completed', 'Sale #39 completed for jcsumugatxd@gmail.com. Total amount: ₱85.00. Payment method: cash.', 1, '2025-09-25 03:35:17', '2025-10-07 02:17:07'),
(66, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-09-25 05:00:14', '2025-10-07 02:17:07'),
(67, 1, 'Order Completed', 'Sale #40 completed for jcsumugatxd@gmail.com. Total amount: ₱23.00. Payment method: cash.', 1, '2025-09-25 05:08:28', '2025-10-07 02:17:07'),
(68, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 14:38:34', '2025-10-07 02:17:07'),
(69, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:08:56', '2025-10-07 02:17:07'),
(70, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:09:37', '2025-10-07 02:17:07'),
(71, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:13:35', '2025-10-07 02:17:07'),
(72, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:17:10', '2025-10-07 02:17:07'),
(73, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:19:33', '2025-10-07 02:17:07'),
(74, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:21:44', '2025-10-07 02:17:07'),
(75, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:36:58', '2025-10-07 02:17:07'),
(76, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 16:46:05', '2025-10-07 02:17:07'),
(77, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:09:28', '2025-10-07 02:17:07'),
(78, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:09:38', '2025-10-07 02:17:07'),
(79, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:09:39', '2025-10-07 02:17:07'),
(80, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:09:51', '2025-10-07 02:17:07'),
(81, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:10:06', '2025-10-07 02:17:07'),
(82, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:12:05', '2025-10-07 02:17:07'),
(83, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:12:23', '2025-10-07 02:17:07'),
(84, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:23:24', '2025-10-07 02:17:07'),
(85, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 17:44:45', '2025-10-07 02:17:07'),
(86, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 18:21:14', '2025-10-07 02:17:07'),
(87, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 18:44:23', '2025-10-07 02:17:07'),
(88, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 18:47:46', '2025-10-07 02:17:07'),
(89, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 18:48:58', '2025-10-07 02:17:07'),
(90, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:05:58', '2025-10-07 02:17:07'),
(91, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:11:37', '2025-10-07 02:17:07'),
(92, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:12:31', '2025-10-07 02:17:07'),
(93, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:22:15', '2025-10-07 02:17:07'),
(94, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:44:08', '2025-10-07 02:17:07'),
(95, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:50:01', '2025-10-07 02:17:07'),
(96, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:56:18', '2025-10-07 02:17:07'),
(97, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 19:56:49', '2025-10-07 02:17:07'),
(98, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:11:39', '2025-10-07 02:17:07'),
(99, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:15:43', '2025-10-07 02:17:07'),
(100, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:16:32', '2025-10-07 02:17:07'),
(101, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:21:42', '2025-10-07 02:17:07'),
(102, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:28:31', '2025-10-07 02:17:07'),
(103, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:31:38', '2025-10-07 02:17:07'),
(104, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:32:16', '2025-10-07 02:17:07'),
(105, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:32:32', '2025-10-07 02:17:07'),
(106, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:33:18', '2025-10-07 02:17:07'),
(107, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:34:37', '2025-10-07 02:17:07'),
(108, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:37:19', '2025-10-07 02:17:07'),
(109, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:37:38', '2025-10-07 02:17:07'),
(110, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:37:58', '2025-10-07 02:17:07'),
(111, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:39:08', '2025-10-07 02:17:07'),
(112, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:39:26', '2025-10-07 02:17:07'),
(113, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:39:59', '2025-10-07 02:17:07'),
(114, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:21', '2025-10-07 02:17:07'),
(115, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:22', '2025-10-07 02:17:07'),
(116, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:23', '2025-10-07 02:17:07'),
(117, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:24', '2025-10-07 02:17:07'),
(118, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:25', '2025-10-07 02:17:07'),
(119, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:26', '2025-10-07 02:17:07'),
(120, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:27', '2025-10-07 02:17:07'),
(121, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:28', '2025-10-07 02:17:07'),
(122, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:29', '2025-10-07 02:17:07'),
(123, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:30', '2025-10-07 02:17:07'),
(124, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:31', '2025-10-07 02:17:07'),
(125, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:32', '2025-10-07 02:17:07'),
(126, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:33', '2025-10-07 02:17:07'),
(127, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:34', '2025-10-07 02:17:07'),
(128, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:34', '2025-10-07 02:17:07'),
(129, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:35', '2025-10-07 02:17:07'),
(130, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:36', '2025-10-07 02:17:07'),
(131, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:37', '2025-10-07 02:17:07'),
(132, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:38', '2025-10-07 02:17:07'),
(133, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:39', '2025-10-07 02:17:07'),
(134, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:40', '2025-10-07 02:17:07'),
(135, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:40', '2025-10-07 02:17:07'),
(136, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:42', '2025-10-07 02:17:07'),
(137, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-04 20:40:42', '2025-10-07 02:17:07'),
(138, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 09:40:52', '2025-10-07 02:17:07'),
(139, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 09:46:47', '2025-10-07 02:17:07'),
(140, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 09:48:04', '2025-10-07 02:17:07'),
(141, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 10:05:24', '2025-10-07 02:17:07'),
(142, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 10:18:04', '2025-10-07 02:17:07'),
(143, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-05 10:31:28', '2025-10-07 02:17:07'),
(144, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-06 02:13:49', '2025-10-07 02:17:07'),
(145, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-06 02:14:45', '2025-10-07 02:17:07'),
(146, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 02:28:14', '2025-12-01 06:32:50'),
(147, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 05:07:31', '2025-12-01 06:32:50'),
(148, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 05:11:22', '2025-12-01 06:32:50'),
(149, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 05:12:20', '2025-12-01 06:32:50'),
(150, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 05:14:56', '2025-12-01 06:32:50'),
(151, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-07 06:16:45', '2025-12-01 06:32:50'),
(152, 1, 'Order Completed', 'Sale #41 completed for jcsumugatxd@gmail.com. Total amount: ₱28.00. Payment method: cash.', 1, '2025-10-08 16:21:50', '2025-12-01 06:32:50'),
(153, 1, 'Order Completed', 'Sale #42 completed for jcsumugatxd@gmail.com. Total amount: ₱14.00. Payment method: cash.', 1, '2025-10-08 16:24:16', '2025-12-01 06:32:50'),
(154, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-10-11 03:26:10', '2025-11-07 03:07:12'),
(155, 1, 'Order Completed', 'Sale #43 completed for jcsumugatxd@gmail.com. Total amount: ₱35.00. Payment method: cash.', 1, '2025-10-11 03:26:52', '2025-11-07 03:06:50'),
(156, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-11-18 08:05:33', '2025-12-01 06:32:50'),
(157, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 1, '2025-11-18 08:08:06', '2025-12-01 06:32:07'),
(158, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 0, '2025-12-02 02:40:46', '2025-12-02 02:40:46'),
(159, 1, 'New Order Received', 'New order received from jcsumugatxd@gmail.com. Status: pending.', 0, '2025-12-02 02:41:41', '2025-12-02 02:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `status` enum('pending','approved','partially_approved','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `prescription_id`, `order_id`, `status`, `created_at`, `updated_at`, `completed_at`) VALUES
(141, NULL, 137, 'RX00001', 'pending', '2025-10-06 02:13:49', '2025-10-06 02:13:49', NULL),
(142, NULL, 138, 'RX00142', 'pending', '2025-10-06 02:14:45', '2025-10-06 02:14:45', NULL),
(143, NULL, 139, 'OD00143', 'completed', '2025-10-07 02:28:14', '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(144, NULL, 140, 'RX00144', 'pending', '2025-10-07 05:07:31', '2025-10-07 05:07:31', NULL),
(145, NULL, 141, 'RX00145', 'pending', '2025-10-07 05:11:22', '2025-10-07 05:11:22', NULL),
(146, NULL, 142, 'RX00146', 'pending', '2025-10-07 05:12:20', '2025-10-07 05:12:20', NULL),
(147, NULL, 143, 'RX00147', 'completed', '2025-10-07 05:14:56', '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(148, NULL, 144, 'RX00148', 'pending', '2025-10-07 06:16:45', '2025-10-07 06:16:45', NULL),
(149, NULL, 145, 'RX00149', 'completed', '2025-10-11 03:26:10', '2025-10-11 03:26:52', '2025-10-11 03:26:52'),
(150, NULL, 146, 'RX00150', 'pending', '2025-11-18 08:05:33', '2025-11-18 08:05:33', NULL),
(151, NULL, 147, 'RX00151', 'pending', '2025-11-18 08:08:06', '2025-11-18 08:08:06', NULL),
(152, NULL, 148, 'RX00152', 'pending', '2025-12-02 02:40:46', '2025-12-02 02:40:46', NULL),
(153, NULL, 149, 'OD00153', 'pending', '2025-12-02 02:41:41', '2025-12-02 02:41:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `available`, `created_at`, `updated_at`) VALUES
(64, 143, 3, 4, 1, '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(65, 147, 3, 2, 1, '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(66, 149, 3, 5, 1, '2025-10-11 03:26:51', '2025-10-11 03:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `pos_transactions`
--

CREATE TABLE `pos_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `customer_type` enum('walk_in','regular') NOT NULL DEFAULT 'walk_in',
  `customer_name` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','gcash') NOT NULL DEFAULT 'cash',
  `status` enum('completed','cancelled','refunded') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_transactions`
--

INSERT INTO `pos_transactions` (`id`, `transaction_id`, `customer_type`, `customer_name`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `amount_paid`, `change_amount`, `payment_method`, `status`, `notes`, `processed_by`, `created_at`, `updated_at`) VALUES
(1, 'TXN-20250903-0001', 'walk_in', NULL, 30.00, 0.00, 0.00, 30.00, 50.00, 20.00, 'cash', 'completed', NULL, 1, '2025-09-02 18:11:42', '2025-09-02 18:11:42'),
(2, 'TXN-20250903-0002', 'walk_in', NULL, 30.00, 0.00, 0.00, 30.00, 100.00, 70.00, 'cash', 'completed', NULL, 1, '2025-09-02 18:14:31', '2025-09-02 18:14:31'),
(3, 'TXN-20250903-0003', 'walk_in', NULL, 24.00, 0.00, 0.00, 24.00, 50.00, 26.00, 'cash', 'completed', NULL, 1, '2025-09-02 18:16:35', '2025-09-02 18:16:35'),
(4, 'TXN-20250903-0004', 'walk_in', NULL, 18.00, 0.00, 0.00, 18.00, 20.00, 2.00, 'cash', 'completed', NULL, 1, '2025-09-02 19:20:07', '2025-09-02 19:20:07'),
(5, 'TXN-20250903-0005', 'walk_in', NULL, 18.00, 0.00, 0.00, 18.00, 20.00, 2.00, 'cash', 'completed', NULL, 1, '2025-09-02 19:22:35', '2025-09-02 19:22:35'),
(6, 'TXN-20250905-0001', 'walk_in', 'uncle boy', 28.00, 0.00, 0.00, 28.00, 50.00, 22.00, 'cash', 'completed', NULL, 1, '2025-09-05 06:34:11', '2025-09-05 06:34:11'),
(7, 'TXN-20250910-0001', 'walk_in', NULL, 38.00, 0.00, 0.00, 38.00, 100.00, 62.00, 'cash', 'completed', NULL, 1, '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(8, 'TXN-20250914-0001', 'walk_in', NULL, 35.00, 0.00, 0.00, 35.00, 50.00, 15.00, 'cash', 'completed', NULL, 1, '2025-09-14 03:22:16', '2025-09-14 03:22:16'),
(9, 'TXN-20250915-0001', 'walk_in', NULL, 980.00, 0.00, 0.00, 980.00, 1000.00, 20.00, 'cash', 'completed', NULL, 1, '2025-09-15 08:33:33', '2025-09-15 08:33:33'),
(10, 'TXN-20250921-0001', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-21 13:14:27', '2025-09-21 13:14:27'),
(11, 'TXN-20250921-0002', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-21 13:21:48', '2025-09-21 13:21:48'),
(12, 'TXN-20250924-0001', 'walk_in', NULL, 120.00, 0.00, 0.00, 120.00, 150.00, 30.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:17:07', '2025-09-23 18:17:07'),
(13, 'TXN-20250924-0002', 'walk_in', NULL, 6.00, 0.00, 0.00, 6.00, 10.00, 4.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:18:10', '2025-09-23 18:18:10'),
(14, 'TXN-20250924-0003', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:19:16', '2025-09-23 18:19:16'),
(15, 'TXN-20250924-0004', 'walk_in', NULL, 17.00, 0.00, 0.00, 17.00, 20.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:21:24', '2025-09-23 18:21:24'),
(16, 'TXN-20250924-0005', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:23:25', '2025-09-23 18:23:25'),
(17, 'TXN-20250924-0006', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:24:57', '2025-09-23 18:24:57'),
(18, 'TXN-20250924-0007', 'walk_in', NULL, 17.00, 0.00, 0.00, 17.00, 20.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:28:23', '2025-09-23 18:28:23'),
(19, 'TXN-20250924-0008', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:33:05', '2025-09-23 18:33:05'),
(20, 'TXN-20250924-0009', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:34:26', '2025-09-23 18:34:26'),
(21, 'TXN-20250924-0010', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:38:57', '2025-09-23 18:38:57'),
(22, 'TXN-20250924-0011', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:39:57', '2025-09-23 18:39:57'),
(23, 'TXN-20250924-0012', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:40:40', '2025-09-23 18:40:40'),
(24, 'TXN-20250924-0013', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:41:57', '2025-09-23 18:41:57'),
(25, 'TXN-20250924-0014', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:44:53', '2025-09-23 18:44:53'),
(26, 'TXN-20250924-0015', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:46:52', '2025-09-23 18:46:52'),
(27, 'TXN-20250924-0016', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:47:47', '2025-09-23 18:47:47'),
(28, 'TXN-20250924-0017', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-09-23 18:48:19', '2025-09-23 18:48:19'),
(29, 'TXN-20250925-0001', 'walk_in', NULL, 14.00, 0.00, 0.00, 14.00, 20.00, 6.00, 'cash', 'completed', NULL, 1, '2025-09-25 01:38:19', '2025-09-25 01:38:19'),
(30, 'TXN-20250925-0002', 'walk_in', NULL, 17.00, 0.00, 0.00, 17.00, 50.00, 33.00, 'gcash', 'completed', NULL, 1, '2025-09-25 02:01:40', '2025-09-25 02:01:40'),
(31, 'TXN-20250925-0003', 'walk_in', NULL, 85.00, 0.00, 0.00, 85.00, 100.00, 15.00, 'cash', 'completed', NULL, 1, '2025-09-25 02:46:11', '2025-09-25 02:46:11'),
(32, 'TXN-20250925-0004', 'walk_in', NULL, 25.00, 0.00, 0.00, 25.00, 50.00, 25.00, 'gcash', 'completed', NULL, 1, '2025-09-25 03:10:32', '2025-09-25 03:10:32'),
(33, 'TXN-20250925-0005', 'walk_in', NULL, 25.00, 0.00, 0.00, 25.00, 50.00, 25.00, 'cash', 'completed', NULL, 1, '2025-09-25 03:31:33', '2025-09-25 03:31:33'),
(34, 'TXN-20250925-0006', 'walk_in', NULL, 25.00, 0.00, 0.00, 25.00, 50.00, 25.00, 'cash', 'completed', NULL, 1, '2025-09-25 04:53:38', '2025-09-25 04:53:38'),
(35, 'TXN-20251008-0001', 'walk_in', NULL, 7.00, 0.00, 0.00, 7.00, 10.00, 3.00, 'cash', 'completed', NULL, 1, '2025-10-08 14:09:10', '2025-10-08 14:09:10'),
(36, 'TXN-20251008-0002', 'walk_in', NULL, 55.00, 0.00, 0.00, 55.00, 70.00, 15.00, 'cash', 'completed', NULL, 1, '2025-10-08 14:17:34', '2025-10-08 14:17:34'),
(37, 'TXN-20251009-0001', 'walk_in', NULL, 21.00, 0.00, 0.00, 21.00, 21.00, 0.00, 'cash', 'completed', NULL, 1, '2025-10-08 16:23:50', '2025-10-08 16:23:50'),
(38, 'TXN-20251009-0002', 'walk_in', NULL, 35.00, 0.00, 0.00, 35.00, 50.00, 15.00, 'cash', 'completed', NULL, 1, '2025-10-08 16:26:36', '2025-10-08 16:26:36'),
(39, 'TXN-20251009-0003', 'walk_in', NULL, 35.00, 0.00, 0.00, 35.00, 50.00, 15.00, 'cash', 'completed', NULL, 1, '2025-10-08 16:35:04', '2025-10-08 16:35:04'),
(40, 'TXN-20251130-0001', 'walk_in', NULL, 24.00, 0.00, 0.00, 24.00, 50.00, 26.00, 'cash', 'completed', NULL, 1, '2025-11-30 14:46:09', '2025-11-30 14:46:09'),
(41, 'TXN-20251201-0001', 'walk_in', 'CHRISTIAN RAE', 23.00, 0.00, 0.00, 23.00, 23.00, 0.00, 'cash', 'completed', NULL, 1, '2025-12-01 06:30:45', '2025-12-01 06:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `pos_transaction_items`
--

CREATE TABLE `pos_transaction_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_transaction_items`
--

INSERT INTO `pos_transaction_items` (`id`, `transaction_id`, `product_id`, `product_name`, `brand_name`, `quantity`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Paracetamol', 'Biogesic', 5, 6.00, 30.00, '2025-09-02 18:11:42', '2025-09-02 18:11:42'),
(2, 2, 1, 'Paracetamol', 'Biogesic', 5, 6.00, 30.00, '2025-09-02 18:14:31', '2025-09-02 18:14:31'),
(3, 3, 1, 'Paracetamol', 'Biogesic', 4, 6.00, 24.00, '2025-09-02 18:16:35', '2025-09-02 18:16:35'),
(4, 4, 1, 'Paracetamol', 'Biogesic', 3, 6.00, 18.00, '2025-09-02 19:20:07', '2025-09-02 19:20:07'),
(5, 5, 1, 'Paracetamol', 'Biogesic', 3, 6.00, 18.00, '2025-09-02 19:22:35', '2025-09-02 19:22:35'),
(6, 6, 3, 'Amoxicillin', 'Amoxicillin', 4, 7.00, 28.00, '2025-09-05 06:34:11', '2025-09-05 06:34:11'),
(7, 7, 3, 'Amoxicillin', 'Amoxicillin', 2, 7.00, 14.00, '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(8, 7, 1, 'Paracetamol', 'Biogesic', 2, 6.00, 12.00, '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(9, 7, 4, 'Ibuprofen 200mg Tablet', 'Ibufrofen', 2, 6.00, 12.00, '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(10, 8, 3, 'Amoxicillin', 'Amoxicillin', 5, 7.00, 35.00, '2025-09-14 03:22:16', '2025-09-14 03:22:16'),
(11, 9, 3, 'Amoxicillin', 'Amoxicillin', 140, 7.00, 980.00, '2025-09-15 08:33:33', '2025-09-15 08:33:33'),
(12, 10, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-21 13:14:27', '2025-09-21 13:14:27'),
(13, 11, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-21 13:21:48', '2025-09-21 13:21:48'),
(14, 12, 4, 'Ibuprofen', 'Ibufrofen', 10, 6.00, 60.00, '2025-09-23 18:17:07', '2025-09-23 18:17:07'),
(15, 12, 1, 'Paracetamol', 'Biogesic', 10, 6.00, 60.00, '2025-09-23 18:17:07', '2025-09-23 18:17:07'),
(16, 13, 4, 'Ibuprofen', 'Ibufrofen', 1, 6.00, 6.00, '2025-09-23 18:18:10', '2025-09-23 18:18:10'),
(17, 14, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:19:16', '2025-09-23 18:19:16'),
(18, 15, 5, 'Ascorbic Acid', 'RiteMed', 1, 17.00, 17.00, '2025-09-23 18:21:24', '2025-09-23 18:21:24'),
(19, 16, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:23:25', '2025-09-23 18:23:25'),
(20, 17, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:24:57', '2025-09-23 18:24:57'),
(21, 18, 5, 'Ascorbic Acid', 'RiteMed', 1, 17.00, 17.00, '2025-09-23 18:28:23', '2025-09-23 18:28:23'),
(22, 19, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:33:05', '2025-09-23 18:33:05'),
(23, 20, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:34:26', '2025-09-23 18:34:26'),
(24, 21, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:38:57', '2025-09-23 18:38:57'),
(25, 22, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:39:57', '2025-09-23 18:39:57'),
(26, 23, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:40:40', '2025-09-23 18:40:40'),
(27, 24, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:41:57', '2025-09-23 18:41:57'),
(28, 25, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:44:53', '2025-09-23 18:44:53'),
(29, 26, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:46:52', '2025-09-23 18:46:52'),
(30, 27, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:47:47', '2025-09-23 18:47:47'),
(31, 28, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-09-23 18:48:19', '2025-09-23 18:48:19'),
(32, 29, 3, 'Amoxicillin', 'Amoxicillin', 2, 7.00, 14.00, '2025-09-25 01:38:19', '2025-09-25 01:38:19'),
(33, 30, 5, 'Ascorbic Acid', 'RiteMed', 1, 17.00, 17.00, '2025-09-25 02:01:40', '2025-09-25 02:01:40'),
(34, 31, 5, 'Ascorbic Acid', 'RiteMed', 5, 17.00, 85.00, '2025-09-25 02:46:11', '2025-09-25 02:46:11'),
(35, 32, 6, 'Cetirizen', 'ritemed', 5, 5.00, 25.00, '2025-09-25 03:10:32', '2025-09-25 03:10:32'),
(36, 33, 6, 'Cetirizen', 'ritemed', 5, 5.00, 25.00, '2025-09-25 03:31:33', '2025-09-25 03:31:33'),
(37, 34, 6, 'Cetirizen', 'ritemed', 5, 5.00, 25.00, '2025-09-25 04:53:38', '2025-09-25 04:53:38'),
(38, 35, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-10-08 14:09:10', '2025-10-08 14:09:10'),
(39, 36, 6, 'Cetirizen', 'ritemed', 5, 5.00, 25.00, '2025-10-08 14:17:34', '2025-10-08 14:17:34'),
(40, 36, 1, 'Paracetamol', 'Biogesic', 5, 6.00, 30.00, '2025-10-08 14:17:34', '2025-10-08 14:17:34'),
(41, 37, 3, 'Amoxicillin', 'Amoxicillin', 3, 7.00, 21.00, '2025-10-08 16:23:50', '2025-10-08 16:23:50'),
(42, 38, 3, 'Amoxicillin', 'Amoxicillin', 5, 7.00, 35.00, '2025-10-08 16:26:36', '2025-10-08 16:26:36'),
(43, 39, 3, 'Amoxicillin', 'Amoxicillin', 5, 7.00, 35.00, '2025-10-08 16:35:04', '2025-10-08 16:35:04'),
(44, 40, 3, 'Amoxicillin', 'Amoxicillin', 1, 7.00, 7.00, '2025-11-30 14:46:09', '2025-11-30 14:46:09'),
(45, 40, 5, 'Ascorbic Acid', 'RiteMed', 1, 17.00, 17.00, '2025-11-30 14:46:10', '2025-11-30 14:46:10'),
(46, 41, 5, 'Ascorbic Acid', 'RiteMed', 1, 17.00, 17.00, '2025-12-01 06:30:45', '2025-12-01 06:30:45'),
(47, 41, 1, 'Paracetamol', 'Biogesic', 1, 6.00, 6.00, '2025-12-01 06:30:45', '2025-12-01 06:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `perceptual_hash` varchar(64) DEFAULT NULL,
  `extracted_text` text DEFAULT NULL,
  `prescription_number` varchar(255) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `prescription_issue_date` date DEFAULT NULL,
  `prescription_expiry_date` date DEFAULT NULL,
  `duplicate_check_status` enum('pending','verified','duplicate','suspicious') NOT NULL DEFAULT 'pending',
  `duplicate_of_id` bigint(20) UNSIGNED DEFAULT NULL,
  `similarity_score` decimal(5,2) DEFAULT NULL,
  `duplicate_checked_at` timestamp NULL DEFAULT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `status` enum('pending','approved','partially_approved','declined','completed','cancelled') DEFAULT 'pending',
  `order_type` varchar(255) NOT NULL DEFAULT 'prescription',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id_string` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `is_encrypted`, `original_filename`, `file_mime_type`, `file_size`, `file_hash`, `perceptual_hash`, `extracted_text`, `prescription_number`, `doctor_name`, `prescription_issue_date`, `prescription_expiry_date`, `duplicate_check_status`, `duplicate_of_id`, `similarity_score`, `duplicate_checked_at`, `mobile_number`, `notes`, `file_path`, `qr_code_path`, `token`, `status`, `order_type`, `user_id`, `customer_id_string`, `created_at`, `updated_at`, `completed_at`, `admin_message`, `customer_id`) VALUES
(137, 1, 'prescription.jpg', 'image/jpeg', 55885, '33b6e45ce55c339e49beb935ed810281', 'c319007f81820000', 'Juan Dela Cruz, MD “PoverA Bld, Boni Ave, Mandahyong City \"ido. 1-454 Cline Sete: Monday. LO0PM —5 COP Friday. 9004M — 12.0084 Toe Thur 10Q0AD 300M Satnday: 12007 3008 Name:__ Sah Gouzales ‘Address: Gout Mucnue, Mandaluyoug (ity Age Sex FP Date: 0/21/2012 Amoxicillin 250mglEuk Susp. # 2 late Recowstitute with water to make 60 ml suspension Sig, Take | tablespoon TID for VI9 days Physician\'s sie_Melgeras — Lic. No._@feede PTRNo.__ 234562 $2 No, ________', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-06 02:13:54', '09567460163', NULL, 'prescriptions/1759716827_9zCF9k_customer_18.enc', 'qrcodes/RX00001.svg', 'jSpedYMAaUjA1kImw82zzoxQpuNYKkfx', 'pending', 'prescription', 18, NULL, '2025-10-06 02:13:49', '2025-10-06 02:13:54', NULL, NULL, '18'),
(138, 1, 'prescription.jpg', 'image/jpeg', 55885, '33b6e45ce55c339e49beb935ed810281', 'c319007f81820000', 'Juan Dela Cruz, MD “PoverA Bld, Boni Ave, Mandahyong City \"ido. 1-454 Cline Sete: Monday. LO0PM —5 COP Friday. 9004M — 12.0084 Toe Thur 10Q0AD 300M Satnday: 12007 3008 Name:__ Sah Gouzales ‘Address: Gout Mucnue, Mandaluyoug (ity Age Sex FP Date: 0/21/2012 Amoxicillin 250mglEuk Susp. # 2 late Recowstitute with water to make 60 ml suspension Sig, Take | tablespoon TID for VI9 days Physician\'s sie_Melgeras — Lic. No._@feede PTRNo.__ 234562 $2 No, ________', NULL, NULL, NULL, NULL, 'duplicate', 137, 100.00, '2025-10-06 02:14:46', '09567460163', NULL, 'prescriptions/1759716884_Kz8iy6_customer_18.enc', 'qrcodes/RX00142.svg', 'ZDoEdEKkovAHMmkRnsEdu29BVJjPOxP3', 'pending', 'prescription', 18, NULL, '2025-10-06 02:14:44', '2025-10-06 02:14:46', NULL, '⚠️ EXACT DUPLICATE: User uploaded identical file previously (Order #RX00001). Customer was notified but chose to proceed. Please verify if this is a legitimate reorder or accidental duplicate.', '18'),
(139, 1, '132123213.jpg', 'image/jpeg', 13447, '55dec5858431206c8d7bcf38922ee065', '183c3c18181818', '', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-07 02:28:21', '09567460163', NULL, 'prescriptions/1759804091_QJi4JL_customer_18.enc', 'qrcodes/OD00143.svg', 'gYLyyg3PHwxPjCoREJ3Cb23weUCFbil8', 'completed', 'online_order', 18, NULL, '2025-10-07 02:28:14', '2025-10-08 16:21:49', NULL, NULL, '18'),
(140, 1, 'prescription.jpg', 'image/jpeg', 55885, '33b6e45ce55c339e49beb935ed810281', 'c319007f81820000', 'Juan Dela Cruz, MD “PoverA Bld, Boni Ave, Mandahyong City \"ido. 1-454 Cline Sete: Monday. LO0PM —5 COP Friday. 9004M — 12.0084 Toe Thur 10Q0AD 300M Satnday: 12007 3008 Name:__ Sah Gouzales ‘Address: Gout Mucnue, Mandaluyoug (ity Age Sex FP Date: 0/21/2012 Amoxicillin 250mglEuk Susp. # 2 late Recowstitute with water to make 60 ml suspension Sig, Take | tablespoon TID for VI9 days Physician\'s sie_Melgeras — Lic. No._@feede PTRNo.__ 234562 $2 No, ________', NULL, NULL, NULL, NULL, 'duplicate', 137, 100.00, '2025-10-07 05:07:32', '09567460163', NULL, 'prescriptions/1759813651_t5BYAk_customer_18.enc', 'qrcodes/RX00144.svg', 'VempYeHZcYn6OV5dvOZVQNlJ8TcGrbVQ', 'pending', 'prescription', 18, NULL, '2025-10-07 05:07:31', '2025-10-07 05:07:32', NULL, '⚠️ EXACT DUPLICATE: User uploaded identical file previously (Order #RX00001). Customer was notified but chose to proceed. Please verify if this is a legitimate reorder or accidental duplicate.', '18'),
(141, 1, 'Untitled Diagram.jpg', 'image/jpeg', 22298, 'c00927b01d21f6f58b77a087cbf46123', '9f0f0f9f900f1000', '( Start ) o = 4', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-07 05:11:22', '09567460163', NULL, 'prescriptions/1759813881_nzqRBO_customer_18.enc', 'qrcodes/RX00145.svg', '2mzHQPJ3vfEpwCNBGiv9rYO1c6ESATNC', 'pending', 'prescription', 18, NULL, '2025-10-07 05:11:22', '2025-10-07 05:11:22', NULL, NULL, '18'),
(142, 1, 'Untitled Diagram.jpg', 'image/jpeg', 22298, 'c00927b01d21f6f58b77a087cbf46123', '9f0f0f9f900f1000', '( Start ) o = 4', NULL, NULL, NULL, NULL, 'duplicate', 141, 100.00, '2025-10-07 05:12:21', '09567460163', NULL, 'prescriptions/1759813940_xMyrhB_customer_18.enc', 'qrcodes/RX00146.svg', '25A5eJNTYLtSNnrx5pld7YsrZiU2FjfT', 'pending', 'prescription', 18, NULL, '2025-10-07 05:12:20', '2025-10-07 05:12:21', NULL, '⚠️ EXACT DUPLICATE: User uploaded identical file previously (Order #RX00145). Customer was notified but chose to proceed. Please verify if this is a legitimate reorder or accidental duplicate.', '18'),
(143, 1, 'th.jpg', 'image/jpeg', 15005, '49f4e95960d0301c52cad6b98aed8d08', '4947cb8306064efe', '', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-07 05:14:57', '09567460163', NULL, 'prescriptions/1759814096_Uj9zgo_customer_18.enc', 'qrcodes/RX00147.svg', 'iXoghEWDFZNqSL567DZlSxcXpPqY1Kd7', 'completed', 'prescription', 18, NULL, '2025-10-07 05:14:56', '2025-10-08 16:24:16', NULL, NULL, '18'),
(144, 1, 'Screenshot 2025-09-20 154210.png', 'image/png', 109425, 'e234364062911d19f34b6452fbe46ffb', 'ffc3cb83c3c7e800', 'y a cia Ay AM Wh UX} Kd} ea a Ne KN} AN KE XY Ay aN LAY YTS —<7[ WY MINIMALIST CLOTHING', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-07 06:16:46', '09567460163', NULL, 'prescriptions/1759817805_CF4z0D_customer_18.enc', 'qrcodes/RX00148.svg', 'JUdc96FmNqPCR1J4hZzWhQIeki17f0j0', 'pending', 'prescription', 18, NULL, '2025-10-07 06:16:45', '2025-10-07 06:16:46', NULL, NULL, '18'),
(145, 1, 'Messenger_creation_6C865823-2F5D-4137-9A9E-41FB3687167A.png', 'image/jpeg', 71590, '053901edaff685aa8b344f9512dd3684', 'ffffffd000c08000', 'ro .— ; ~ i - aio or” ~~,', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-10-11 03:26:17', '09264017009', NULL, 'prescriptions/1760153168_2aQIO2_customer_18.enc', 'qrcodes/RX00149.svg', '5zqAdroZDN00avP6xDXgeML5vYtynQUN', 'completed', 'prescription', 18, NULL, '2025-10-11 03:26:10', '2025-10-11 03:26:52', NULL, NULL, '18'),
(146, 1, 'OIP (3).jpg', 'image/jpeg', 10514, '02aed262776fe819f0d126e3789c7bf9', '387c3c38781c30', 'aa egies =', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-11-18 08:05:40', '09567460164', NULL, 'prescriptions/1763453130_Qy908C_customer_18.enc', 'qrcodes/RX00150.svg', 'dBOi4mKb1isbH7rzIZY2mc8RRRa5tnhA', 'pending', 'prescription', 18, NULL, '2025-11-18 08:05:33', '2025-11-18 08:05:40', NULL, NULL, '18'),
(147, 1, 'OIP (3).jpg', 'image/jpeg', 10514, '02aed262776fe819f0d126e3789c7bf9', '387c3c38781c30', 'aa egies =', NULL, NULL, NULL, NULL, 'duplicate', 146, 100.00, '2025-11-18 08:08:10', '09567460163', NULL, 'prescriptions/1763453285_0Pg7jN_customer_18.enc', 'qrcodes/RX00151.svg', 'rJ4uHceCAkX2bSaNcCiNlZUc0gr7TyBO', 'pending', 'prescription', 18, NULL, '2025-11-18 08:08:06', '2025-11-18 08:08:10', NULL, '⚠️ EXACT DUPLICATE: User uploaded identical file previously (Order #RX00150). Customer was notified but chose to proceed. Please verify if this is a legitimate reorder or accidental duplicate.', '18'),
(148, 1, 'OIP (2).jpg', 'image/jpeg', 13713, 'ab493155eb1cf866b7c30a63abd2c3b6', 'fcfc5e1e0c448000', '<a — te “sy AG', NULL, NULL, NULL, NULL, 'verified', NULL, 0.00, '2025-12-02 02:40:53', '09567460163', NULL, 'prescriptions/1764643243_BAhzAb_customer_18.enc', 'qrcodes/RX00152.svg', '71OUHvFpRVudK6H2mAaWqRufstTcgnsp', 'pending', 'prescription', 18, NULL, '2025-12-02 02:40:46', '2025-12-02 02:40:53', NULL, NULL, '18'),
(149, 1, 'prescription.jpg', 'image/jpeg', 55885, '5862357c53a0d14f9d65921058041bd4', 'c319007f81820000', 'Juan Dela Cruz, MD “PoverA Bld, Boni Ave, Mandahyong City \"ido. 1-454 Cline Sete: Monday. LO0PM —5 COP Friday. 9004M — 12.0084 Toe Thur 10Q0AD 300M Satnday: 12007 3008 Name:__ Sah Gouzales ‘Address: Gout Mucnue, Mandaluyoug (ity Age Sex FP Date: 0/21/2012 Amoxicillin 250mglEuk Susp. # 2 late Recowstitute with water to make 60 ml suspension Sig, Take | tablespoon TID for VI9 days Physician\'s sie_Melgeras — Lic. No._@feede PTRNo.__ 234562 $2 No, ________', NULL, NULL, NULL, NULL, 'duplicate', 137, 100.00, '2025-12-02 02:41:42', '09567460163', NULL, 'prescriptions/1764643301_f89ZO3_customer_18.enc', 'qrcodes/OD00153.svg', 'Tb45g9UIDyjrocF2y8Y8664BPCQup6t8', 'pending', 'online_order', 18, NULL, '2025-12-02 02:41:41', '2025-12-02 02:41:42', NULL, '⚠️ Potential duplicate detected (100% similar match with Order #RX00001). Please verify with customer before processing.', '18');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('available','out_of_stock') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`id`, `prescription_id`, `product_id`, `batch_id`, `quantity`, `status`, `created_at`, `updated_at`) VALUES
(29, 139, 3, 6, 4, 'available', '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(30, 143, 3, 6, 2, 'available', '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(31, 145, 3, 16, 5, 'available', '2025-10-11 03:26:51', '2025-10-11 03:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) NOT NULL,
  `product_type` varchar(50) NOT NULL,
  `form_type` varchar(50) NOT NULL,
  `dosage_unit` varchar(50) DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'piece',
  `unit_quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `classification` varchar(50) NOT NULL,
  `storage_requirements` text DEFAULT NULL,
  `reorder_level` int(10) UNSIGNED DEFAULT 0,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `batch_number` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `generic_name`, `manufacturer`, `product_type`, `form_type`, `dosage_unit`, `unit`, `unit_quantity`, `classification`, `storage_requirements`, `reorder_level`, `stock_quantity`, `batch_number`, `category_id`, `supplier_id`, `brand_name`, `notification_sent_at`, `created_at`, `updated_at`) VALUES
(1, '5989', 'Paracetamol', NULL, 'Mercury Drug', 'OTC', 'Tablet', '500mg', 'piece', 1.00, '2', 'Room Temperature', 50, 361, NULL, 1, 2, 'Biogesic', NULL, '2025-08-25 11:59:37', '2025-12-01 06:30:45'),
(3, '5971', 'Amoxicillin', NULL, 'Pfizer Inc.', 'Prescription', 'Capsule', '500mg', 'piece', 1.00, '1', NULL, 50, 294, NULL, 1, 2, 'Amoxicillin', NULL, '2025-09-04 05:05:25', '2025-11-30 14:46:10'),
(4, '8918', 'Ibuprofen', NULL, 'GlaxoSmithKline', 'OTC', 'Tablet', '200mg', 'piece', 1.00, '2', 'Room Temperature', 50, 164, NULL, 1, 1, 'Ibufrofen', NULL, '2025-09-04 05:11:33', '2025-09-25 03:13:29'),
(5, '4098', 'Ascorbic Acid', NULL, 'Zuellig Pharma', 'OTC', 'Syrup', '500mg/5ml', 'piece', 1.00, '5', 'Room Temperature', 50, 58, NULL, 1, 1, 'RiteMed', NULL, '2025-09-15 09:34:31', '2025-12-01 06:30:45'),
(6, '8374', 'Cetirizen', NULL, 'Other', 'OTC', 'Tablet', '10mg', 'piece', 1.00, '1', NULL, 50, 180, NULL, 1, 2, 'ritemed', NULL, '2025-09-25 02:16:12', '2025-10-08 14:17:34'),
(7, '3685', 'Paracetamol', NULL, 'Other', 'OTC', 'Tablet', '500mg', 'piece', 1.00, '2', 'Room Temperature', 100, 0, NULL, 1, 2, 'RiteMed', NULL, '2025-09-25 05:15:29', '2025-09-25 05:15:29'),
(8, '1546', 'Kremil-S', 'Aspirin', 'Pfizer Inc.', 'OTC', 'Capsule', '250mg', 'box', 120.00, '5', 'Room Temperature', 100, 960, NULL, 1, 2, NULL, NULL, '2025-10-06 13:26:02', '2025-10-06 13:26:02'),
(9, '7193', 'Kremil-S', NULL, 'Pfizer Inc.', 'OTC', 'Capsule', '250mg', 'box', 120.00, '5', 'Room Temperature', 100, 240, NULL, 1, 2, NULL, NULL, '2025-10-06 13:48:57', '2025-10-06 13:48:57'),
(10, '8889', 'Paracetamol', NULL, 'Mercury Drug', 'OTC', 'Tablet', '250mg', 'box', 120.00, '2', 'Room Temperature', 100, 240, NULL, 1, 1, NULL, NULL, '2025-10-06 13:53:12', '2025-10-06 13:53:12'),
(11, '9382', 'Loperamide', NULL, 'Johnson & Johnson', 'OTC', 'Capsule', '500mg', 'box', 60.00, '11', 'Room Temperature', 50, 120, NULL, 1, 2, NULL, NULL, '2025-11-18 07:58:17', '2025-11-18 07:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `expiration_date` date NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0,
  `quantity_remaining` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `received_date` date NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `unit_quantity` decimal(10,2) DEFAULT NULL,
  `expiration_notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `product_id`, `batch_number`, `expiration_date`, `quantity_received`, `quantity_remaining`, `unit_cost`, `sale_price`, `received_date`, `supplier_id`, `notes`, `unit`, `unit_quantity`, `expiration_notification_sent_at`, `created_at`, `updated_at`) VALUES
(3, 1, '598-20250825-001', '2026-09-09', 500, 361, 4.00, 6.00, '2025-09-03', 2, NULL, NULL, NULL, NULL, '2025-08-25 12:15:56', '2025-12-01 06:30:45'),
(5, 4, '891-20250904-001', '2026-09-09', 200, 164, 4.00, 6.00, '2025-09-04', 1, NULL, NULL, NULL, NULL, '2025-09-04 05:31:43', '2025-09-25 03:13:29'),
(6, 3, '597-20250904-001', '2027-09-07', 320, 110, 5.00, 7.00, '2025-10-02', 2, NULL, NULL, NULL, NULL, '2025-09-04 05:32:06', '2025-10-08 16:24:16'),
(7, 3, '597-20250911-001', '2025-09-12', 40, 0, 4.00, 5.00, '2025-09-11', 2, NULL, NULL, NULL, NULL, '2025-09-11 08:59:02', '2025-09-11 08:59:02'),
(8, 5, '409-20250915-001', '2027-09-09', 40, 18, 15.00, 17.00, '2025-09-15', 1, NULL, NULL, NULL, NULL, '2025-09-15 09:35:31', '2025-12-01 06:30:45'),
(9, 3, '597-20250924-001', '2025-09-25', 14, 0, 4.00, 4.00, '2025-09-24', 2, NULL, NULL, NULL, NULL, '2025-09-24 07:55:35', '2025-09-24 07:55:35'),
(10, 6, '837-20250925-001', '2027-09-09', 200, 180, 4.00, 5.00, '2025-09-25', 2, NULL, NULL, NULL, NULL, '2025-09-25 02:16:48', '2025-10-08 14:17:34'),
(11, 3, '597-20250925-001', '2025-09-26', 10, 0, 4.00, 6.00, '2025-09-25', 2, NULL, NULL, NULL, NULL, '2025-09-25 02:53:13', '2025-09-25 02:55:44'),
(12, 5, '409-20250925-001', '2027-09-09', 40, 40, 4.00, 6.00, '2025-09-25', 1, NULL, NULL, NULL, NULL, '2025-09-25 05:06:30', '2025-09-25 05:06:30'),
(13, 8, 'KRE251007001', '2027-09-09', 960, 960, 10.00, 12.00, '2025-10-07', 2, NULL, NULL, NULL, NULL, '2025-10-07 05:25:31', '2025-10-07 05:25:31'),
(14, 10, 'PAR251008001', '2027-09-09', 240, 240, 4.00, 6.00, '2025-10-08', 1, NULL, NULL, NULL, NULL, '2025-10-08 14:43:50', '2025-10-08 14:43:50'),
(15, 3, 'AMO251009001', '2027-09-09', 100, 100, 4.00, 6.00, '2025-10-08', 2, NULL, NULL, NULL, NULL, '2025-10-08 16:25:03', '2025-10-08 16:25:03'),
(16, 3, 'AMO251009002', '2027-06-06', 100, 84, 5.00, 7.00, '2025-10-08', 2, NULL, NULL, NULL, NULL, '2025-10-08 16:26:00', '2025-11-30 14:46:09'),
(17, 11, 'LOP251118001', '2025-11-19', 120, 120, 20.00, 23.00, '2025-11-18', 2, NULL, NULL, NULL, NULL, '2025-11-18 08:00:11', '2025-11-18 08:00:11'),
(18, 9, 'KRE251202001', '2025-12-20', 240, 240, 8.00, 10.00, '2025-12-02', 2, NULL, NULL, NULL, NULL, '2025-12-02 02:37:27', '2025-12-02 02:37:27');

-- --------------------------------------------------------

--
-- Table structure for table `reorder_flags`
--

CREATE TABLE `reorder_flags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','online','gcash') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `total_items`, `prescription_id`, `order_id`, `customer_id`, `total_amount`, `sale_date`, `status`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(41, 4, 139, 143, 18, 28.00, '2025-10-08 16:21:49', 'completed', 'cash', 'Order processed and completed via admin panel', '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(42, 2, 143, 147, 18, 14.00, '2025-10-08 16:24:16', 'completed', 'cash', 'Order processed and completed via admin panel', '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(43, 5, 145, 149, 18, 35.00, '2025-10-11 03:26:51', 'completed', 'cash', 'Order processed and completed via admin panel', '2025-10-11 03:26:51', '2025-10-11 03:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(8,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`) VALUES
(52, 41, 3, 4, 7.00, 28.00, '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(53, 42, 3, 2, 7.00, 14.00, '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(54, 43, 3, 5, 7.00, 35.00, '2025-10-11 03:26:51', '2025-10-11 03:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `batch_id`, `type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_at`, `updated_at`) VALUES
(20, 1, NULL, 'sale', -2, 'sale', 19, 'Sale for prescription #29, batch 3', '2025-08-25 12:32:18', '2025-08-25 12:32:18'),
(21, 1, NULL, 'sale', -4, 'sale', 20, 'Sale for prescription #31, batch 3', '2025-08-25 14:51:39', '2025-08-25 14:51:39'),
(22, 1, NULL, 'sale', -4, 'sale', 21, 'Sale for prescription #34, batch 3', '2025-08-26 06:04:32', '2025-08-26 06:04:32'),
(23, 1, NULL, 'sale', -5, 'sale', 22, 'Sale for prescription #35, batch 3', '2025-08-26 08:19:20', '2025-08-26 08:19:20'),
(24, 1, NULL, 'sale', -5, 'sale', 23, 'Sale for prescription #33, batch 3', '2025-08-26 08:20:54', '2025-08-26 08:20:54'),
(25, 1, NULL, 'sale', -31, 'sale', 24, 'Sale for prescription #36, batch 3', '2025-08-26 08:25:39', '2025-08-26 08:25:39'),
(26, 1, NULL, 'sale', -9, 'sale', 25, 'Sale for prescription #37, batch 3 (non-expired)', '2025-09-02 19:07:07', '2025-09-02 19:07:07'),
(27, 1, NULL, 'sale', -6, 'sale', 26, 'Sale for prescription #32, batch 3 (non-expired)', '2025-09-02 19:18:44', '2025-09-02 19:18:44'),
(28, 1, NULL, 'sale', -3, 'pos_transaction', 5, 'POS sale - Transaction #5', '2025-09-02 19:22:35', '2025-09-02 19:22:35'),
(29, 1, NULL, 'stock_addition', 400, 'manual', NULL, 'Added 400 units to batch #598-20250825-001', '2025-09-03 02:46:30', '2025-09-03 02:46:30'),
(30, 4, NULL, 'purchase', 200, 'purchase', NULL, 'Initial stock - Batch: 891-20250904-001', '2025-09-04 05:31:44', '2025-09-04 05:31:44'),
(31, 3, NULL, 'purchase', 200, 'purchase', NULL, 'Initial stock - Batch: 597-20250904-001', '2025-09-04 05:32:06', '2025-09-04 05:32:06'),
(32, 3, NULL, 'sale', -4, 'pos_transaction', 6, 'POS sale - Transaction #6', '2025-09-05 06:34:11', '2025-09-05 06:34:11'),
(33, 3, NULL, 'sale', -2, 'pos_transaction', 7, 'POS sale - Transaction #7', '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(34, 1, NULL, 'sale', -2, 'pos_transaction', 7, 'POS sale - Transaction #7', '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(35, 4, NULL, 'sale', -2, 'pos_transaction', 7, 'POS sale - Transaction #7', '2025-09-10 01:51:16', '2025-09-10 01:51:16'),
(36, 1, NULL, 'sale', -9, 'sale', 27, 'Sale for prescription #41, batch 3 (non-expired)', '2025-09-11 08:51:01', '2025-09-11 08:51:01'),
(37, 1, NULL, 'sale', -4, 'sale', 28, 'Sale for prescription #40, batch 3 (non-expired)', '2025-09-11 08:53:31', '2025-09-11 08:53:31'),
(38, 3, NULL, 'sale', -5, 'sale', 28, 'Sale for prescription #40, batch 6 (non-expired)', '2025-09-11 08:53:31', '2025-09-11 08:53:31'),
(39, 3, NULL, 'purchase', 40, 'purchase', NULL, 'Initial stock - Batch: 597-20250911-001', '2025-09-11 08:59:02', '2025-09-11 08:59:02'),
(40, 3, NULL, 'sale', -5, 'pos_transaction', 8, 'POS sale - Transaction #8', '2025-09-14 03:22:16', '2025-09-14 03:22:16'),
(41, 1, NULL, 'sale', -4, 'sale', 29, 'Sale for prescription #39, batch 3 (non-expired)', '2025-09-14 04:30:45', '2025-09-14 04:30:45'),
(42, 3, NULL, 'sale', -140, 'pos_transaction', 9, 'POS sale - Transaction #9', '2025-09-15 08:33:33', '2025-09-15 08:33:33'),
(43, 5, NULL, 'purchase', 40, 'purchase', NULL, 'Initial stock - Batch: 409-20250915-001', '2025-09-15 09:35:31', '2025-09-15 09:35:31'),
(44, 5, NULL, 'sale', -5, 'sale', 30, 'Sale for prescription #42, batch 8 (non-expired)', '2025-09-18 07:17:47', '2025-09-18 07:17:47'),
(45, 1, NULL, 'sale', -5, 'sale', 30, 'Sale for prescription #42, batch 3 (non-expired)', '2025-09-18 07:17:47', '2025-09-18 07:17:47'),
(46, 3, NULL, 'sale', -7, 'sale', 31, 'Sale for prescription #45, batch 6 (non-expired)', '2025-09-20 03:59:11', '2025-09-20 03:59:11'),
(47, 4, NULL, 'sale', -8, 'sale', 31, 'Sale for prescription #45, batch 5 (non-expired)', '2025-09-20 03:59:11', '2025-09-20 03:59:11'),
(48, 3, NULL, 'sale', -1, 'pos_transaction', 10, 'POS sale - Transaction #10', '2025-09-21 13:14:27', '2025-09-21 13:14:27'),
(49, 3, NULL, 'sale', -1, 'pos_transaction', 11, 'POS sale - Transaction #11', '2025-09-21 13:21:48', '2025-09-21 13:21:48'),
(50, 1, NULL, 'sale', -5, 'sale', 32, 'Sale for prescription #47, batch 3 (non-expired)', '2025-09-23 17:42:43', '2025-09-23 17:42:43'),
(51, 4, NULL, 'sale', -10, 'pos_transaction', 12, 'POS sale - Transaction #12', '2025-09-23 18:17:07', '2025-09-23 18:17:07'),
(52, 1, NULL, 'sale', -10, 'pos_transaction', 12, 'POS sale - Transaction #12', '2025-09-23 18:17:07', '2025-09-23 18:17:07'),
(53, 4, NULL, 'sale', -1, 'pos_transaction', 13, 'POS sale - Transaction #13', '2025-09-23 18:18:10', '2025-09-23 18:18:10'),
(54, 3, NULL, 'sale', -1, 'pos_transaction', 14, 'POS sale - Transaction #14', '2025-09-23 18:19:16', '2025-09-23 18:19:16'),
(55, 5, NULL, 'sale', -1, 'pos_transaction', 15, 'POS sale - Transaction #15', '2025-09-23 18:21:25', '2025-09-23 18:21:25'),
(56, 3, NULL, 'sale', -1, 'pos_transaction', 16, 'POS sale - Transaction #16', '2025-09-23 18:23:25', '2025-09-23 18:23:25'),
(57, 3, NULL, 'sale', -1, 'pos_transaction', 17, 'POS sale - Transaction #17', '2025-09-23 18:24:57', '2025-09-23 18:24:57'),
(58, 5, NULL, 'sale', -1, 'pos_transaction', 18, 'POS sale - Transaction #18', '2025-09-23 18:28:24', '2025-09-23 18:28:24'),
(59, 3, NULL, 'sale', -1, 'pos_transaction', 19, 'POS sale - Transaction #19', '2025-09-23 18:33:05', '2025-09-23 18:33:05'),
(60, 3, NULL, 'sale', -1, 'pos_transaction', 20, 'POS sale - Transaction #20', '2025-09-23 18:34:26', '2025-09-23 18:34:26'),
(61, 3, NULL, 'sale', -1, 'pos_transaction', 21, 'POS sale - Transaction #21', '2025-09-23 18:38:57', '2025-09-23 18:38:57'),
(62, 3, NULL, 'sale', -1, 'pos_transaction', 22, 'POS sale - Transaction #22', '2025-09-23 18:39:57', '2025-09-23 18:39:57'),
(63, 3, NULL, 'sale', -1, 'pos_transaction', 23, 'POS sale - Transaction #23', '2025-09-23 18:40:40', '2025-09-23 18:40:40'),
(64, 3, NULL, 'sale', -1, 'pos_transaction', 24, 'POS sale - Transaction #24', '2025-09-23 18:41:58', '2025-09-23 18:41:58'),
(65, 3, NULL, 'sale', -1, 'pos_transaction', 25, 'POS sale - Transaction #25', '2025-09-23 18:44:53', '2025-09-23 18:44:53'),
(66, 3, NULL, 'sale', -1, 'pos_transaction', 26, 'POS sale - Transaction #26', '2025-09-23 18:46:52', '2025-09-23 18:46:52'),
(67, 3, NULL, 'sale', -1, 'pos_transaction', 27, 'POS sale - Transaction #27', '2025-09-23 18:47:47', '2025-09-23 18:47:47'),
(68, 3, NULL, 'sale', -1, 'pos_transaction', 28, 'POS sale - Transaction #28', '2025-09-23 18:48:19', '2025-09-23 18:48:19'),
(69, 3, NULL, 'purchase', 14, 'purchase', NULL, 'Initial stock - Batch: 597-20250924-001', '2025-09-24 07:55:35', '2025-09-24 07:55:35'),
(70, 1, NULL, 'sale', -5, 'sale', 33, 'Sale for prescription #52, batch 3 (non-expired)', '2025-09-24 15:39:11', '2025-09-24 15:39:11'),
(71, 3, NULL, 'sale', -2, 'pos_transaction', 29, 'POS sale - Transaction #29', '2025-09-25 01:38:19', '2025-09-25 01:38:19'),
(72, 1, NULL, 'sale', -2, 'sale', 34, 'Sale for prescription #53, batch 3 (non-expired)', '2025-09-25 01:41:15', '2025-09-25 01:41:15'),
(73, 3, NULL, 'sale', -2, 'sale', 34, 'Sale for prescription #53, batch 6 (non-expired)', '2025-09-25 01:41:15', '2025-09-25 01:41:15'),
(74, 5, NULL, 'sale', -1, 'pos_transaction', 30, 'POS sale - Transaction #30', '2025-09-25 02:01:40', '2025-09-25 02:01:40'),
(75, 6, NULL, 'purchase', 200, 'purchase', NULL, 'Initial stock - Batch: 837-20250925-001', '2025-09-25 02:16:48', '2025-09-25 02:16:48'),
(76, 5, NULL, 'sale', -5, 'pos_transaction', 31, 'POS sale - Transaction #31', '2025-09-25 02:46:11', '2025-09-25 02:46:11'),
(77, 3, NULL, 'sale', -15, 'sale', 35, 'Sale for prescription #56, batch 6 (non-expired)', '2025-09-25 02:50:15', '2025-09-25 02:50:15'),
(78, 3, NULL, 'purchase', 10, 'purchase', NULL, 'Initial stock - Batch: 597-20250925-001', '2025-09-25 02:53:13', '2025-09-25 02:53:13'),
(79, 3, NULL, 'sale', -10, 'sale', 36, 'Sale for prescription #55, batch 11 (non-expired)', '2025-09-25 02:55:44', '2025-09-25 02:55:44'),
(80, 3, NULL, 'sale', -3, 'sale', 36, 'Sale for prescription #55, batch 6 (non-expired)', '2025-09-25 02:55:44', '2025-09-25 02:55:44'),
(81, 5, NULL, 'sale', -1, 'sale', 37, 'Sale for prescription #54, batch 8 (non-expired)', '2025-09-25 02:56:22', '2025-09-25 02:56:22'),
(82, 6, NULL, 'sale', -5, 'pos_transaction', 32, 'POS sale - Transaction #32', '2025-09-25 03:10:32', '2025-09-25 03:10:32'),
(83, 4, NULL, 'sale', -15, 'sale', 38, 'Sale for prescription #58, batch 5 (non-expired)', '2025-09-25 03:13:29', '2025-09-25 03:13:29'),
(84, 6, NULL, 'sale', -5, 'pos_transaction', 33, 'POS sale - Transaction #33', '2025-09-25 03:31:33', '2025-09-25 03:31:33'),
(85, 5, NULL, 'sale', -5, 'sale', 39, 'Sale for prescription #59, batch 8 (non-expired)', '2025-09-25 03:35:17', '2025-09-25 03:35:17'),
(86, 6, NULL, 'sale', -5, 'pos_transaction', 34, 'POS sale - Transaction #34', '2025-09-25 04:53:38', '2025-09-25 04:53:38'),
(87, 5, NULL, 'purchase', 40, 'purchase', NULL, 'Initial stock - Batch: 409-20250925-001', '2025-09-25 05:06:30', '2025-09-25 05:06:30'),
(88, 1, NULL, 'sale', -1, 'sale', 40, 'Sale for prescription #60, batch 3 (non-expired)', '2025-09-25 05:08:28', '2025-09-25 05:08:28'),
(89, 5, NULL, 'sale', -1, 'sale', 40, 'Sale for prescription #60, batch 8 (non-expired)', '2025-09-25 05:08:28', '2025-09-25 05:08:28'),
(90, 3, NULL, 'stock_addition', 20, 'manual', NULL, 'Added 20 units to batch #597-20250904-001', '2025-10-02 08:00:15', '2025-10-02 08:00:15'),
(91, 3, NULL, 'stock_addition', 100, 'manual', NULL, 'Added 100 units to batch #597-20250904-001', '2025-10-02 08:00:24', '2025-10-02 08:00:24'),
(92, 8, NULL, 'purchase', 960, 'purchase', NULL, 'New batch received - Batch: KRE251007001', '2025-10-07 05:25:31', '2025-10-07 05:25:31'),
(93, 3, NULL, 'sale', -1, 'pos_transaction', 35, 'POS sale - Transaction #35', '2025-10-08 14:09:11', '2025-10-08 14:09:11'),
(94, 6, NULL, 'sale', -5, 'pos_transaction', 36, 'POS sale - Transaction #36', '2025-10-08 14:17:34', '2025-10-08 14:17:34'),
(95, 1, NULL, 'sale', -5, 'pos_transaction', 36, 'POS sale - Transaction #36', '2025-10-08 14:17:34', '2025-10-08 14:17:34'),
(96, 10, NULL, 'purchase', 240, 'purchase', NULL, 'New batch received - Batch: PAR251008001', '2025-10-08 14:43:50', '2025-10-08 14:43:50'),
(97, 3, NULL, 'sale', -4, 'sale', 41, 'Sale for prescription #139, batch 6 (non-expired)', '2025-10-08 16:21:49', '2025-10-08 16:21:49'),
(98, 3, NULL, 'sale', -3, 'pos_transaction', 37, 'POS sale - Transaction #37', '2025-10-08 16:23:50', '2025-10-08 16:23:50'),
(99, 3, NULL, 'sale', -2, 'sale', 42, 'Sale for prescription #143, batch 6 (non-expired)', '2025-10-08 16:24:16', '2025-10-08 16:24:16'),
(100, 3, NULL, 'purchase', 100, 'purchase', NULL, 'New batch received - Batch: AMO251009001', '2025-10-08 16:25:03', '2025-10-08 16:25:03'),
(101, 3, NULL, 'purchase', 100, 'purchase', NULL, 'New batch received - Batch: AMO251009002', '2025-10-08 16:26:00', '2025-10-08 16:26:00'),
(102, 3, NULL, 'sale', -5, 'pos_transaction', 38, 'POS sale - Transaction #38', '2025-10-08 16:26:36', '2025-10-08 16:26:36'),
(103, 3, NULL, 'sale', -5, 'pos_transaction', 39, 'POS sale - Transaction #39', '2025-10-08 16:35:04', '2025-10-08 16:35:04'),
(104, 3, NULL, 'sale', -5, 'sale', 43, 'Sale for prescription #145, batch 16 (non-expired)', '2025-10-11 03:26:52', '2025-10-11 03:26:52'),
(105, 11, NULL, 'purchase', 120, 'purchase', NULL, 'New batch received - Batch: LOP251118001', '2025-11-18 08:00:12', '2025-11-18 08:00:12'),
(106, 3, NULL, 'sale', -1, 'pos_transaction', 40, 'POS sale - Transaction #40', '2025-11-30 14:46:10', '2025-11-30 14:46:10'),
(107, 5, NULL, 'sale', -1, 'pos_transaction', 40, 'POS sale - Transaction #40', '2025-11-30 14:46:10', '2025-11-30 14:46:10'),
(108, 5, NULL, 'sale', -1, 'pos_transaction', 41, 'POS sale - Transaction #41', '2025-12-01 06:30:45', '2025-12-01 06:30:45'),
(109, 1, NULL, 'sale', -1, 'pos_transaction', 41, 'POS sale - Transaction #41', '2025-12-01 06:30:45', '2025-12-01 06:30:45'),
(110, 9, NULL, 'purchase', 240, 'purchase', NULL, 'New batch received - Batch: KRE251202001', '2025-12-02 02:37:27', '2025-12-02 02:37:27');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Medicine Manufacturer', 'John Carlo Sumugat', '09567460163', 'jcsumugatxd@gmail.com', 'Poblacion, Culasi Antique', '2025-06-03 14:13:11', '2025-06-03 14:13:11'),
(2, 'Mercury Drug Store', 'Mrs. Jane Yap', '09567456772', 'mrsjane@gmail.com', 'Poblacion, Culasi Antique', '2025-06-03 14:13:39', '2025-06-03 14:13:39'),
(3, 'Ailyn', 'Mrs. Jane Yap', '09567456772', 'jcsumugatxd@gmail.com', 'MAlabor', '2025-09-25 02:19:40', '2025-09-25 02:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'MJS Pharmacy Admin', 'mjspharmacy@gmail.com', '2025-06-03 14:11:07', '$2y$12$uTGaKNqnFsuhYWOPAwAwHewoptmEFl2IPP99ojuf/FPVeuZXX6Ptu', 'admin', NULL, '2025-06-03 14:11:07', '2025-06-03 14:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_online_status`
--

CREATE TABLE `user_online_status` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cancelled_orders_order_id_index` (`order_id`),
  ADD KEY `idx_cancelled_orders_cancelled_at` (`cancelled_at`),
  ADD KEY `idx_cancelled_orders_prescription_cancelled_at` (`prescription_id`,`cancelled_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_messages_conversation_id_index` (`conversation_id`),
  ADD KEY `chat_messages_customer_id_index` (`customer_id`),
  ADD KEY `chat_messages_admin_id_index` (`admin_id`),
  ADD KEY `chat_messages_created_at_index` (`created_at`),
  ADD KEY `chat_messages_is_from_customer_index` (`is_from_customer`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversations_customer_id_index` (`customer_id`),
  ADD KEY `conversations_admin_id_index` (`admin_id`),
  ADD KEY `conversations_status_index` (`status`),
  ADD KEY `conversations_priority_index` (`priority`),
  ADD KEY `conversations_last_message_at_index` (`last_message_at`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_participants_conversation_id_index` (`conversation_id`),
  ADD KEY `conversation_participants_customer_id_index` (`customer_id`),
  ADD KEY `conversation_participants_admin_id_index` (`admin_id`),
  ADD KEY `conversation_participants_last_read_message_id_foreign` (`last_read_message_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_address_unique` (`email_address`),
  ADD UNIQUE KEY `customers_customer_id_unique` (`customer_id`),
  ADD KEY `customers_customer_id_index` (`customer_id`);

--
-- Indexes for table `customers_chat`
--
ALTER TABLE `customers_chat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_chat_customer_id_unique` (`customer_id`),
  ADD KEY `customers_chat_customer_id_index` (`customer_id`),
  ADD KEY `customers_chat_email_address_index` (`email_address`),
  ADD KEY `customers_chat_chat_status_index` (`chat_status`),
  ADD KEY `customers_chat_last_active_index` (`last_active`);

--
-- Indexes for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_notifications_prescription_id_foreign` (`prescription_id`),
  ADD KEY `customer_notifications_customer_id_is_read_index` (`customer_id`,`is_read`),
  ADD KEY `customer_notifications_customer_id_created_at_index` (`customer_id`,`created_at`);

--
-- Indexes for table `expiry_dates`
--
ALTER TABLE `expiry_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expiry_dates_product_id_unique` (`product_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_attachments_message_id_index` (`message_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_id_unique` (`order_id`),
  ADD KEY `orders_prescription_id_foreign` (`prescription_id`),
  ADD KEY `orders_customer_id_index` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pos_transactions_transaction_id_unique` (`transaction_id`),
  ADD KEY `pos_transactions_transaction_id_customer_type_status_index` (`transaction_id`,`customer_type`,`status`);

--
-- Indexes for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pos_transaction_items_transaction_id_foreign` (`transaction_id`),
  ADD KEY `pos_transaction_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescriptions_token_unique` (`token`),
  ADD KEY `prescriptions_user_id_foreign` (`user_id`),
  ADD KEY `idx_prescriptions_status_date` (`status`,`created_at`),
  ADD KEY `prescriptions_file_hash_index` (`file_hash`),
  ADD KEY `prescriptions_perceptual_hash_index` (`perceptual_hash`),
  ADD KEY `prescriptions_duplicate_of_id_foreign` (`duplicate_of_id`);
ALTER TABLE `prescriptions` ADD FULLTEXT KEY `prescriptions_extracted_text_fulltext` (`extracted_text`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prescription_id` (`prescription_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `prescription_items_batch_id_foreign` (`batch_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_product_code_unique` (`product_code`),
  ADD KEY `products_supplier_id_foreign` (`supplier_id`),
  ADD KEY `idx_products_reorder` (`reorder_level`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_batches_product_id_batch_number_unique` (`product_id`,`batch_number`),
  ADD KEY `product_batches_supplier_id_foreign` (`supplier_id`),
  ADD KEY `product_batches_product_id_quantity_remaining_index` (`product_id`,`quantity_remaining`),
  ADD KEY `product_batches_product_id_expiration_date_index` (`product_id`,`expiration_date`),
  ADD KEY `product_batches_expiration_date_quantity_remaining_index` (`expiration_date`,`quantity_remaining`),
  ADD KEY `product_batches_batch_number_index` (`batch_number`),
  ADD KEY `product_batches_expiration_date_index` (`expiration_date`),
  ADD KEY `product_batches_quantity_remaining_index` (`quantity_remaining`),
  ADD KEY `product_batches_received_date_index` (`received_date`),
  ADD KEY `idx_batches_availability` (`expiration_date`,`quantity_remaining`),
  ADD KEY `idx_batches_fifo` (`product_id`,`expiration_date`,`received_date`),
  ADD KEY `idx_batches_expired` (`expiration_date`,`quantity_remaining`),
  ADD KEY `idx_product_batches_expiry` (`expiration_date`,`quantity_remaining`),
  ADD KEY `product_batches_unit_index` (`unit`);

--
-- Indexes for table `reorder_flags`
--
ALTER TABLE `reorder_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reorder_flags_product_id_foreign` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_prescription_id_index` (`prescription_id`),
  ADD KEY `sales_customer_id_index` (`customer_id`),
  ADD KEY `sales_sale_date_index` (`sale_date`),
  ADD KEY `sales_status_index` (`status`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_items_sale_id_product_id_unique` (`sale_id`,`product_id`),
  ADD KEY `sale_items_sale_id_index` (`sale_id`),
  ADD KEY `sale_items_product_id_index` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_batch_id_foreign` (`batch_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`);

--
-- Indexes for table `user_online_status`
--
ALTER TABLE `user_online_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_online_status_customer_id_index` (`customer_id`),
  ADD KEY `user_online_status_admin_id_index` (`admin_id`),
  ADD KEY `user_online_status_is_online_index` (`is_online`),
  ADD KEY `user_online_status_last_seen_index` (`last_seen`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customers_chat`
--
ALTER TABLE `customers_chat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `expiry_dates`
--
ALTER TABLE `expiry_dates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reorder_flags`
--
ALTER TABLE `reorder_flags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_online_status`
--
ALTER TABLE `user_online_status`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  ADD CONSTRAINT `fk_cancelled_orders_prescription_id` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers_chat` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_last_read_message_id_foreign` FOREIGN KEY (`last_read_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD CONSTRAINT `customer_notifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_notifications_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expiry_dates`
--
ALTER TABLE `expiry_dates`
  ADD CONSTRAINT `expiry_dates_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `pos_transaction_items`
--
ALTER TABLE `pos_transaction_items`
  ADD CONSTRAINT `pos_transaction_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pos_transaction_items_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_duplicate_of_id_foreign` FOREIGN KEY (`duplicate_of_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_batches_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reorder_flags`
--
ALTER TABLE `reorder_flags`
  ADD CONSTRAINT `reorder_flags_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
