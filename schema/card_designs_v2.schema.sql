-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 26, 2026 at 05:53 AM
-- Server version: 10.11.18-MariaDB
-- PHP Version: 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `heavenlyid_heavenly_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `card_designs_v2`
--

CREATE TABLE `card_designs_v2` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_code` char(20) DEFAULT NULL,
  `owner_type` enum('user','guest') NOT NULL,
  `user_site_id` int(10) UNSIGNED DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `design_title` varchar(120) NOT NULL DEFAULT 'My Heavenly ID',
  `full_name` varchar(255) NOT NULL DEFAULT '',
  `iam_status` varchar(255) NOT NULL DEFAULT '',
  `spiritual_gifts` varchar(255) NOT NULL DEFAULT '',
  `received_jesus_date` date DEFAULT NULL,
  `favorite_verse_ref` varchar(120) NOT NULL DEFAULT '',
  `verse_text` text DEFAULT NULL,
  `letter_of_intent` text DEFAULT NULL,
  `name_font_resized` tinyint(1) NOT NULL DEFAULT 0,
  `name_font_size_px` decimal(5,2) NOT NULL DEFAULT 82.00,
  `name_layout_left_px` decimal(7,2) NOT NULL DEFAULT 405.00,
  `name_layout_top_px` decimal(7,2) NOT NULL DEFAULT 252.00,
  `name_layout_width_px` decimal(7,2) NOT NULL DEFAULT 473.00,
  `name_layout_height_px` decimal(7,2) NOT NULL DEFAULT 112.00,
  `name_safe_right_px` decimal(7,2) NOT NULL DEFAULT 896.00,
  `name_available_width_px` decimal(7,2) NOT NULL DEFAULT 473.00,
  `name_text_align` varchar(12) NOT NULL DEFAULT 'left',
  `name_padding_left_px` decimal(7,2) NOT NULL DEFAULT 0.00,
  `letter_font_resized` tinyint(1) NOT NULL DEFAULT 0,
  `letter_font_size_px` decimal(5,2) NOT NULL DEFAULT 18.00,
  `front_theme_file` varchar(255) NOT NULL DEFAULT '',
  `back_theme_file` varchar(255) NOT NULL DEFAULT '',
  `front_theme_style` varchar(120) NOT NULL DEFAULT '',
  `foreground_file` varchar(255) NOT NULL DEFAULT '',
  `preview_front_png_path` varchar(255) DEFAULT NULL,
  `preview_back_png_path` varchar(255) DEFAULT NULL,
  `payload_json` mediumtext NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `shopify_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shopify_order_name` varchar(64) DEFAULT NULL,
  `download_token` char(64) DEFAULT NULL,
  `print_file_path` varchar(255) DEFAULT NULL,
  `checkout_token` varchar(128) DEFAULT NULL,
  `checkout_token_created_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `card_designs_v2`
--
ALTER TABLE `card_designs_v2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_card_designs_v2_design_code` (`design_code`),
  ADD UNIQUE KEY `uq_card_designs_v2_download_token` (`download_token`),
  ADD KEY `idx_card_designs_v2_owner_user` (`owner_type`,`user_site_id`,`updated_at`),
  ADD KEY `idx_card_designs_v2_guest_email` (`guest_email`),
  ADD KEY `idx_card_designs_v2_shopify_order` (`shopify_order_id`),
  ADD KEY `idx_card_designs_v2_paid` (`is_paid`,`paid_at`),
  ADD KEY `idx_card_designs_v2_checkout_token` (`checkout_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `card_designs_v2`
--
ALTER TABLE `card_designs_v2`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
