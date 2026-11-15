-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 15, 2026 at 02:12 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `froshgah`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address_line` text NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-11-11 16:34:31', '2026-11-12 15:44:14'),
(2, 2, '2026-11-11 16:35:39', '2026-11-14 15:49:17'),
(3, 3, '2026-11-12 05:44:23', '2026-11-12 06:03:25');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint UNSIGNED NOT NULL,
  `cart_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `created_at`) VALUES
(1, 1, 1, 25, '2026-11-11 16:34:31'),
(11, 1, 6, 1, '2026-11-12 15:44:01'),
(12, 1, 3, 1, '2026-11-12 15:44:14'),
(14, 2, 1, 1, '2026-11-14 15:49:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `status`, `created_at`) VALUES
(1, 'Electronics', 'electronics', 'Electronic devices and accessories', NULL, 'active', '2026-11-11 15:30:39'),
(2, 'Clothing', 'clothing', 'Fashion and clothing products', NULL, 'active', '2026-11-11 15:30:39'),
(3, 'Books', 'books', 'Books and educational materials', NULL, 'active', '2026-11-11 15:30:39'),
(4, 'Home & Kitchen', 'home-kitchen', 'Home and kitchen products', NULL, 'active', '2026-11-11 15:30:39'),
(5, 'Sports', 'sports', 'Sports equipment and accessories', NULL, 'active', '2026-11-11 15:30:39');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','read','replied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, NULL, 'Welcome to Froshgah', 'Your notification system is working successfully.', 'general', 1, '2026-11-11 19:40:23'),
(2, 2, NULL, 'Welcome to Froshgah', 'Your notification system is working successfully.', 'general', 1, '2026-11-11 19:41:11'),
(4, 3, NULL, 'Welcome to Froshgah!', 'Your notification system is working successfully.', 'general', 1, '2026-11-12 05:45:46'),
(5, 3, 6, 'Order placed successfully!', 'Your order FR-20261112-BA87F430 has been received successfully. Your payment method is Cash on Delivery. We will process your order soon.', 'order', 1, '2026-11-12 05:53:53'),
(6, 3, 7, 'Order placed successfully!', 'Your order FR-20261112-4731CC04 has been received successfully. Your payment method is Cash on Delivery. We will process your order soon.', 'order', 1, '2026-11-12 05:57:22'),
(7, 3, 7, 'Your order has been confirmed!', 'Your order has been confirmed by our administration team. We will prepare it for processing. Order number: FR-20261112-4731CC04.', 'order', 1, '2026-11-12 05:57:40'),
(8, 3, 8, 'Order placed successfully!', 'Your order FR-20261112-E6866AB7 has been received successfully. Your payment method is Cash on Delivery. We will process your order soon.', 'order', 0, '2026-11-12 06:03:32'),
(9, 2, 9, 'Order placed successfully!', 'Your order FR-20261112-BB070901 has been received successfully. Your payment method is Cash on Delivery. We will process your order soon.', 'order', 1, '2026-11-12 06:47:56'),
(10, 1, 9, 'New customer order received!', 'ahmad has placed a new order. Order number: FR-20261112-BB070901. Order total: 179.97. Please review and process this order.', 'new_order', 1, '2026-11-12 06:47:56'),
(11, 2, 9, 'Your order has been cancelled.', 'Your order has been cancelled. Please contact our support team if you have any questions. Order number: FR-20261112-BB070901.', 'order', 1, '2026-11-12 07:05:34'),
(12, 2, 10, 'Order placed successfully!', 'Your order FR-20261114-4E999D64 has been received successfully. Your payment method is Cash on Delivery. We will process your order soon.', 'order', 0, '2026-11-14 04:03:27'),
(13, 1, 10, 'New customer order received!', 'ahmadjan has placed a new order. Order number: FR-20261114-4E999D64. Order total: 34.99. Please review and process this order.', 'new_order', 0, '2026-11-14 04:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `shipping_address`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'FR-20261111-82352E17', 1349.85, 'Full Name: ahmad\nPhone: +989031070154\nCity: Nooristani\nAddress: Bagh-e-Qazi, 1st District', 'confirmed', '2026-11-11 16:36:38', '2026-11-11 16:37:11'),
(2, 2, 'FR-20261111-7BEAD378', 45.99, 'Full Name: ahmad\nPhone: +989031070154\nCity: Nooristani\nAddress: Bagh-e-Qazi, 1st District', 'confirmed', '2026-11-11 18:13:49', '2026-11-11 18:16:38'),
(3, 2, 'FR-20261111-5657402F', 89.99, 'Full Name: ahmad\nPhone: +989031070154\nCity: Nooristani\nAddress: Bagh-e-Qazi, 1st District', 'confirmed', '2026-11-11 18:15:58', '2026-11-11 18:16:36'),
(4, 2, 'FR-20261112-0FEFDEB8', 599.90, 'Full Name: ahmad\nPhone: +989031070154\nCity: Nooristani\nAddress: Bagh-e-Qazi, 1st District', 'confirmed', '2026-11-12 04:58:24', '2026-11-12 04:58:50'),
(5, 3, 'FR-20261112-3007F90C', 89.99, 'Full Name: jawad\nPhone: +93791895343\nCity: Kabul, Afghanistan\nAddress: Shorbazzar street, kabul , Afghanistan', 'pending', '2026-11-12 05:44:34', '2026-11-12 05:44:34'),
(6, 3, 'FR-20261112-BA87F430', 119.98, 'Full Name: jawad\nPhone: +93791895343\nCity: Kabul, Afghanistan\nAddress: Shorbazzar street, kabul , Afghanistan', 'confirmed', '2026-11-12 05:53:53', '2026-11-12 05:54:16'),
(7, 3, 'FR-20261112-4731CC04', 59.99, 'Full Name: jawad\nPhone: +93791895343\nCity: Kabul, Afghanistan\nAddress: Shorbazzar street, kabul , Afghanistan', 'confirmed', '2026-11-12 05:57:22', '2026-11-12 05:57:40'),
(8, 3, 'FR-20261112-E6866AB7', 59.99, 'Full Name: jawad\nPhone: +93791895343\nCity: Kabul, Afghanistan\nAddress: Shorbazzar street, kabul , Afghanistan', 'pending', '2026-11-12 06:03:32', '2026-11-12 06:03:32'),
(9, 2, 'FR-20261112-BB070901', 179.97, 'Full Name: ahmad\nPhone: +93791895343\nCity: Kabul, Afghanistan\nAddress: Shorbazzar street, kabul , Afghanistan', 'cancelled', '2026-11-12 06:47:56', '2026-11-12 07:05:34'),
(10, 2, 'FR-20261114-4E999D64', 34.99, 'Full Name: ahmadjan\nPhone: +989031070154\nCity: Nooristani\nAddress: Bagh-e-Qazi, 1st District', 'pending', '2026-11-14 04:03:27', '2026-11-14 04:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `price` decimal(12,2) NOT NULL
) ;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(1, 1, 2, 'Smart Watch', 15, 89.99),
(2, 2, 5, 'Kitchen Blender', 1, 45.99),
(3, 3, 2, 'Smart Watch', 1, 89.99),
(4, 4, 1, 'Wireless Headphones', 10, 59.99),
(5, 5, 2, 'Smart Watch', 1, 89.99),
(6, 6, 1, 'Wireless Headphones', 2, 59.99),
(7, 7, 1, 'Wireless Headphones', 1, 59.99),
(8, 8, 1, 'Wireless Headphones', 1, 59.99),
(9, 9, 1, 'Wireless Headphones', 3, 59.99),
(10, 10, 6, 'Sports Backpack', 1, 34.99);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(150) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `transaction_id`, `amount`, `status`, `created_at`) VALUES
(1, 1, 'cash_on_delivery', NULL, 1349.85, 'completed', '2026-11-11 16:36:38'),
(2, 2, 'cash_on_delivery', NULL, 45.99, 'completed', '2026-11-11 18:13:49'),
(3, 3, 'cash_on_delivery', NULL, 89.99, 'completed', '2026-11-11 18:15:58'),
(4, 4, 'cash_on_delivery', NULL, 599.90, 'completed', '2026-11-12 04:58:24'),
(5, 5, 'cash_on_delivery', NULL, 89.99, 'pending', '2026-11-12 05:44:34'),
(6, 6, 'cash_on_delivery', NULL, 119.98, 'completed', '2026-11-12 05:53:53'),
(7, 7, 'cash_on_delivery', NULL, 59.99, 'completed', '2026-11-12 05:57:22'),
(8, 8, 'cash_on_delivery', NULL, 59.99, 'pending', '2026-11-12 06:03:32'),
(9, 9, 'cash_on_delivery', NULL, 179.97, 'pending', '2026-11-12 06:47:56'),
(10, 10, 'cash_on_delivery', NULL, 34.99, 'pending', '2026-11-14 04:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text,
  `price` decimal(12,2) NOT NULL,
  `stock` int UNSIGNED DEFAULT '0',
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `sku`, `description`, `price`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Wireless Headphones', 'wireless-headphones', 'FR-ELE-001', 'High quality wireless headphones with clear sound and comfortable design.', 59.99, 11, 'active', '2026-11-11 15:30:55', '2026-11-12 07:05:34'),
(2, 1, 'Smart Watch', 'smart-watch', 'FR-ELE-002', 'Modern smart watch with fitness tracking and notifications.', 89.99, 0, 'out_of_stock', '2026-11-11 15:30:55', '2026-11-12 05:44:34'),
(3, 2, 'Men Casual T-Shirt', 'men-casual-tshirt', 'FR-CLO-001', 'Comfortable casual cotton T-shirt for everyday use.', 19.99, 50, 'active', '2026-11-11 15:30:55', '2026-11-11 15:30:55'),
(4, 3, 'Programming Book', 'programming-book', 'FR-BOO-001', 'A comprehensive programming book for software development learners.', 29.99, 30, 'active', '2026-11-11 15:30:55', '2026-11-11 15:30:55'),
(5, 4, 'Kitchen Blender', 'kitchen-blender', 'FR-HOM-001', 'Powerful kitchen blender for everyday food preparation.', 45.99, 11, 'active', '2026-11-11 15:30:55', '2026-11-11 18:13:49'),
(6, 5, 'Sports Backpack', 'sports-backpack', 'FR-SPR-001', 'Durable sports backpack with multiple storage compartments.', 34.99, 19, 'active', '2026-11-11 15:30:55', '2026-11-14 04:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 1, 'uploads/products/e7128635b8ddb4c1168696ad17266ddb.png', 1, '2026-11-11 16:33:31'),
(2, 2, 'uploads/products/9acb05887d73a39327defed53666fc17.png', 1, '2026-11-11 16:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `review_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'abdullah', 'abdullahnooristani331@gmail.com', '$2y$10$jn9TBbHo/tlONvnycDvrRu8lF2QxuF94IvVQebJaAsWKqZm3B1gca', '0791895343', 'admin', 'active', '2026-11-11 15:26:13', '2026-11-11 16:15:58'),
(2, 'ahmadjan', 'ahmad@gmail.com', '$2y$10$pgEKqi2yyayJPW4GFLyPGedtbcvpposcfjb/8JXwlQGmGtjYbUXcu', '0791895343', 'customer', 'active', '2026-11-11 16:32:03', '2026-11-12 06:48:33'),
(3, 'jawad', 'jawad@gmail.com', '$2y$10$KAIoTrtYJXSWbZVVmSN9aOtHai.vMseFBw7dxVz.A79y.WbBDFchm', '0791895343', 'customer', 'active', '2026-11-12 05:43:43', '2026-11-12 05:43:43'),
(4, 'fahim', 'fahim@gmail.com', '$2y$10$JnDsJU8hijTptK.7dMe37uC9U0aRmlGNgSdeR7obTaKvL./TGOSKa', '0791895343', 'customer', 'active', '2026-11-12 07:09:52', '2026-11-12 07:10:54'),
(5, 'ahmad', 'ahmd@gmail.com', '$2y$10$T835xTGxOac7DzZsa8SL5.46r/tnfK..3JQbLttsgmgNAyC.6wdOC', '079841242', 'customer', 'active', '2026-11-12 08:01:11', '2026-11-12 08:01:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_status` (`status`),
  ADD KEY `idx_contact_created` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_created` (`created_at`),
  ADD KEY `fk_notifications_order` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  ADD KEY `idx_product_reviews_product` (`product_id`),
  ADD KEY `idx_product_reviews_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
