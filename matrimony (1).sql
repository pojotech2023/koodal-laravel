-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2026 at 01:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `matrimony`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `auditable_type`, `auditable_id`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 12:05:56', '2026-08-27 12:05:56'),
(2, 1, 'profile.approved', 'App\\Models\\UserProfile', 296, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 05:13:50', '2026-08-28 05:13:50'),
(3, 1, 'user.verify', 'App\\Models\\User', 505, NULL, NULL, '{\"status\":\"pending\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 04:31:03', '2026-09-05 04:31:03'),
(4, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 1, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 04:35:14', '2026-09-05 04:35:14'),
(5, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 07:02:03', '2026-09-05 07:02:03'),
(6, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 3, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 07:11:28', '2026-09-05 07:11:28'),
(7, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 4, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 07:29:34', '2026-09-05 07:29:34'),
(8, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 5, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 07:33:05', '2026-09-05 07:33:05'),
(9, 1, 'subscription.cancelled', 'App\\Models\\Subscription', 3, 'this fake', NULL, '{\"status\":\"cancelled\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:19:53', '2026-09-05 08:19:53'),
(10, 1, 'chat.transcript_viewed', 'App\\Models\\Conversation', 1, 'Read the transcript of conversation #1 between members 505 and 508', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:23:13', '2026-09-05 08:23:13'),
(11, 1, 'media.uploaded', 'App\\Models\\MediaUpload', 1, 'Media uploaded to the library', NULL, '{\"path\":\"cms\\/2026\\/09\\/c932acd8-6623-4917-a0c6-c37897d408e4.jpg\",\"original_name\":\"6540131b4a5bce3199933434d822ac5f.jpg\",\"collection\":\"banners\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:30:17', '2026-09-05 08:30:17'),
(12, 1, 'media.uploaded', 'App\\Models\\MediaUpload', 2, 'Media uploaded to the library', NULL, '{\"path\":\"cms\\/2026\\/09\\/61674a5e-c531-42f3-ab78-240007267f3d.jpg\",\"original_name\":\"57dc4c8c5656c97404a4443ca5b7209f.jpg\",\"collection\":\"success-stories\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:31:11', '2026-09-05 08:31:11'),
(13, 1, 'cms.created', 'App\\Models\\CmsPage', 6, NULL, NULL, '{\"slug\":\"new-page\",\"title\":\"newpage\",\"content\":\"tedfd\",\"meta_title\":\"newpage\",\"meta_description\":\"newpage\",\"og_image_path\":null,\"is_published\":\"1\",\"published_at\":\"2026-09-05 14:02:40\",\"updated_at\":\"2026-09-05 14:02:40\",\"created_at\":\"2026-09-05 14:02:40\",\"id\":6}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:32:41', '2026-09-05 08:32:41'),
(14, 1, 'cms.updated', 'App\\Models\\CmsPage', 6, 'Page edited', '{\"is_published\":false}', '{\"is_published\":\"0\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:34:40', '2026-09-05 08:34:40'),
(15, 1, 'cms.deleted', 'App\\Models\\CmsPage', 6, 'Page deleted', '{\"id\":6,\"slug\":\"new-page\",\"title\":\"newpage\",\"content\":\"tedfd\",\"meta_title\":\"newpage\",\"meta_description\":\"newpage\",\"og_image_path\":null,\"is_published\":0,\"published_at\":\"2026-09-05 14:02:40\",\"created_at\":\"2026-09-05 14:02:40\",\"updated_at\":\"2026-09-05 14:04:40\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 08:35:07', '2026-09-05 08:35:07'),
(16, 1, 'profile.approved', 'App\\Models\\UserProfile', 501, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 09:52:36', '2026-09-05 09:52:36'),
(17, 1, 'user.verify', 'App\\Models\\User', 510, NULL, NULL, '{\"status\":\"active\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 10:00:35', '2026-09-05 10:00:35'),
(18, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-05 13:19:04', '2026-09-05 13:19:04'),
(19, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 04:08:29', '2026-09-07 04:08:29'),
(20, 1, 'staff.created', 'App\\Models\\User', 511, 'Staff account created with role Moderator', NULL, '{\"email\":\"banupriya.info1310cs@gmail.com\",\"role\":\"moderator\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 04:10:46', '2026-09-07 04:10:46'),
(21, NULL, 'admin.login', 'App\\Models\\User', 511, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 04:11:56', '2026-09-07 04:11:56'),
(22, 1, 'staff.created', 'App\\Models\\User', 512, 'Staff account created with role Support Staff', NULL, '{\"email\":\"kumar@gmail.com\",\"role\":\"support\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 04:13:26', '2026-09-07 04:13:26'),
(23, 512, 'admin.login', 'App\\Models\\User', 512, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 04:14:59', '2026-09-07 04:14:59'),
(24, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 04:33:08', '2026-09-08 04:33:08'),
(25, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:18:48', '2026-09-08 05:18:48'),
(26, 1, 'profile.approved', 'App\\Models\\UserProfile', 510, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:19:46', '2026-09-08 05:19:46'),
(27, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 6, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:31:14', '2026-09-08 05:31:14'),
(28, 1, 'profile.approved', 'App\\Models\\UserProfile', 511, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:33:35', '2026-09-08 05:33:35'),
(29, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 8, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:42:43', '2026-09-08 05:42:43'),
(30, 1, 'profile.approved', 'App\\Models\\UserProfile', 510, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:42:55', '2026-09-08 05:42:55'),
(31, 1, 'user.verify', 'App\\Models\\User', 516, NULL, NULL, '{\"status\":\"active\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 05:43:11', '2026-09-08 05:43:11'),
(32, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 04:18:21', '2026-09-10 04:18:21'),
(33, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 9, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 04:26:12', '2026-09-10 04:26:12'),
(34, 1, 'photo.approved', 'App\\Models\\ProfilePhoto', 10, NULL, NULL, '{\"approval_status\":\"approved\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 04:28:46', '2026-09-10 04:28:46'),
(35, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 09:25:44', '2026-09-12 09:25:44'),
(36, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Symfony', '2026-09-12 10:36:36', '2026-09-12 10:36:36'),
(37, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Symfony', '2026-09-12 10:36:37', '2026-09-12 10:36:37'),
(38, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Symfony', '2026-09-12 10:36:38', '2026-09-12 10:36:38'),
(39, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 10:37:51', '2026-09-12 10:37:51'),
(40, 1, 'admin.login', 'App\\Models\\User', 1, 'Staff signed in (web)', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 10:54:31', '2026-09-12 10:54:31');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `mobile_image_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `position` varchar(30) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `blocked_user_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
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

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('matrimony_cache_1e2edf514f74bcc0c50dcdbb4bb3684c', 'i:1;', 1789210636),
('matrimony_cache_1e2edf514f74bcc0c50dcdbb4bb3684c:timer', 'i:1789210636;', 1789210636),
('matrimony_cache_1eed5ad7767f7cb02f5ceccd5ada2e4f', 'i:1;', 1789208548),
('matrimony_cache_1eed5ad7767f7cb02f5ceccd5ada2e4f:timer', 'i:1789208548;', 1789208548),
('matrimony_cache_592e19c2689018b3648541c09a98e6c2', 'i:1;', 1789210649),
('matrimony_cache_592e19c2689018b3648541c09a98e6c2:timer', 'i:1789210649;', 1789210649),
('matrimony_cache_admin.dashboard.chart.active-users.30', 'a:30:{i:0;a:2:{s:4:\"date\";s:10:\"2026-08-14\";s:5:\"value\";d:0;}i:1;a:2:{s:4:\"date\";s:10:\"2026-08-15\";s:5:\"value\";d:0;}i:2;a:2:{s:4:\"date\";s:10:\"2026-08-16\";s:5:\"value\";d:0;}i:3;a:2:{s:4:\"date\";s:10:\"2026-08-17\";s:5:\"value\";d:0;}i:4;a:2:{s:4:\"date\";s:10:\"2026-08-18\";s:5:\"value\";d:0;}i:5;a:2:{s:4:\"date\";s:10:\"2026-08-19\";s:5:\"value\";d:0;}i:6;a:2:{s:4:\"date\";s:10:\"2026-08-20\";s:5:\"value\";d:0;}i:7;a:2:{s:4:\"date\";s:10:\"2026-08-21\";s:5:\"value\";d:0;}i:8;a:2:{s:4:\"date\";s:10:\"2026-08-22\";s:5:\"value\";d:0;}i:9;a:2:{s:4:\"date\";s:10:\"2026-08-23\";s:5:\"value\";d:0;}i:10;a:2:{s:4:\"date\";s:10:\"2026-08-24\";s:5:\"value\";d:0;}i:11;a:2:{s:4:\"date\";s:10:\"2026-08-25\";s:5:\"value\";d:0;}i:12;a:2:{s:4:\"date\";s:10:\"2026-08-26\";s:5:\"value\";d:0;}i:13;a:2:{s:4:\"date\";s:10:\"2026-08-27\";s:5:\"value\";d:0;}i:14;a:2:{s:4:\"date\";s:10:\"2026-08-28\";s:5:\"value\";d:0;}i:15;a:2:{s:4:\"date\";s:10:\"2026-08-29\";s:5:\"value\";d:0;}i:16;a:2:{s:4:\"date\";s:10:\"2026-08-30\";s:5:\"value\";d:0;}i:17;a:2:{s:4:\"date\";s:10:\"2026-08-31\";s:5:\"value\";d:0;}i:18;a:2:{s:4:\"date\";s:10:\"2026-09-01\";s:5:\"value\";d:0;}i:19;a:2:{s:4:\"date\";s:10:\"2026-09-02\";s:5:\"value\";d:0;}i:20;a:2:{s:4:\"date\";s:10:\"2026-09-03\";s:5:\"value\";d:0;}i:21;a:2:{s:4:\"date\";s:10:\"2026-09-04\";s:5:\"value\";d:0;}i:22;a:2:{s:4:\"date\";s:10:\"2026-09-05\";s:5:\"value\";d:0;}i:23;a:2:{s:4:\"date\";s:10:\"2026-09-06\";s:5:\"value\";d:0;}i:24;a:2:{s:4:\"date\";s:10:\"2026-09-07\";s:5:\"value\";d:0;}i:25;a:2:{s:4:\"date\";s:10:\"2026-09-08\";s:5:\"value\";d:0;}i:26;a:2:{s:4:\"date\";s:10:\"2026-09-09\";s:5:\"value\";d:8;}i:27;a:2:{s:4:\"date\";s:10:\"2026-09-10\";s:5:\"value\";d:0;}i:28;a:2:{s:4:\"date\";s:10:\"2026-09-11\";s:5:\"value\";d:0;}i:29;a:2:{s:4:\"date\";s:10:\"2026-09-12\";s:5:\"value\";d:3;}}', 1789210779),
('matrimony_cache_admin.dashboard.chart.demographics.30', 'a:2:{s:11:\"by_religion\";O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:0;O:8:\"stdClass\":2:{s:5:\"label\";s:5:\"Hindu\";s:5:\"value\";i:10;}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}s:11:\"by_age_band\";O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:15:\"App\\Models\\User\":34:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:5:\"label\";s:5:\"18-24\";s:5:\"value\";i:2;}s:11:\"\0*\0original\";a:2:{s:5:\"label\";s:5:\"18-24\";s:5:\"value\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:13:\"date_of_birth\";s:4:\"date\";s:17:\"email_verified_at\";s:8:\"datetime\";s:18:\"mobile_verified_at\";s:8:\"datetime\";s:14:\"last_active_at\";s:8:\"datetime\";s:13:\"last_login_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";s:14:\"\0*\0accessToken\";N;s:16:\"\0*\0forceDeleting\";b:0;}i:1;O:15:\"App\\Models\\User\":34:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:5:\"label\";s:5:\"25-29\";s:5:\"value\";i:4;}s:11:\"\0*\0original\";a:2:{s:5:\"label\";s:5:\"25-29\";s:5:\"value\";i:4;}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:13:\"date_of_birth\";s:4:\"date\";s:17:\"email_verified_at\";s:8:\"datetime\";s:18:\"mobile_verified_at\";s:8:\"datetime\";s:14:\"last_active_at\";s:8:\"datetime\";s:13:\"last_login_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";s:14:\"\0*\0accessToken\";N;s:16:\"\0*\0forceDeleting\";b:0;}i:2;O:15:\"App\\Models\\User\":34:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:5:\"label\";s:5:\"30-34\";s:5:\"value\";i:5;}s:11:\"\0*\0original\";a:2:{s:5:\"label\";s:5:\"30-34\";s:5:\"value\";i:5;}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:13:\"date_of_birth\";s:4:\"date\";s:17:\"email_verified_at\";s:8:\"datetime\";s:18:\"mobile_verified_at\";s:8:\"datetime\";s:14:\"last_active_at\";s:8:\"datetime\";s:13:\"last_login_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";s:14:\"\0*\0accessToken\";N;s:16:\"\0*\0forceDeleting\";b:0;}i:3;O:15:\"App\\Models\\User\":34:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:5:\"label\";s:3:\"35+\";s:5:\"value\";i:1;}s:11:\"\0*\0original\";a:2:{s:5:\"label\";s:3:\"35+\";s:5:\"value\";i:1;}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:13:\"date_of_birth\";s:4:\"date\";s:17:\"email_verified_at\";s:8:\"datetime\";s:18:\"mobile_verified_at\";s:8:\"datetime\";s:14:\"last_active_at\";s:8:\"datetime\";s:13:\"last_login_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";s:14:\"\0*\0accessToken\";N;s:16:\"\0*\0forceDeleting\";b:0;}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}', 1789210779),
('matrimony_cache_admin.dashboard.chart.locations.30', 'a:1:{i:0;O:8:\"stdClass\":2:{s:5:\"label\";s:7:\"Chennai\";s:5:\"value\";i:10;}}', 1789210779),
('matrimony_cache_admin.dashboard.chart.registrations.30', 'a:30:{i:0;a:2:{s:4:\"date\";s:10:\"2026-08-14\";s:5:\"value\";d:0;}i:1;a:2:{s:4:\"date\";s:10:\"2026-08-15\";s:5:\"value\";d:0;}i:2;a:2:{s:4:\"date\";s:10:\"2026-08-16\";s:5:\"value\";d:0;}i:3;a:2:{s:4:\"date\";s:10:\"2026-08-17\";s:5:\"value\";d:0;}i:4;a:2:{s:4:\"date\";s:10:\"2026-08-18\";s:5:\"value\";d:0;}i:5;a:2:{s:4:\"date\";s:10:\"2026-08-19\";s:5:\"value\";d:0;}i:6;a:2:{s:4:\"date\";s:10:\"2026-08-20\";s:5:\"value\";d:0;}i:7;a:2:{s:4:\"date\";s:10:\"2026-08-21\";s:5:\"value\";d:0;}i:8;a:2:{s:4:\"date\";s:10:\"2026-08-22\";s:5:\"value\";d:0;}i:9;a:2:{s:4:\"date\";s:10:\"2026-08-23\";s:5:\"value\";d:0;}i:10;a:2:{s:4:\"date\";s:10:\"2026-08-24\";s:5:\"value\";d:0;}i:11;a:2:{s:4:\"date\";s:10:\"2026-08-25\";s:5:\"value\";d:0;}i:12;a:2:{s:4:\"date\";s:10:\"2026-08-26\";s:5:\"value\";d:0;}i:13;a:2:{s:4:\"date\";s:10:\"2026-08-27\";s:5:\"value\";d:0;}i:14;a:2:{s:4:\"date\";s:10:\"2026-08-28\";s:5:\"value\";d:0;}i:15;a:2:{s:4:\"date\";s:10:\"2026-08-29\";s:5:\"value\";d:0;}i:16;a:2:{s:4:\"date\";s:10:\"2026-08-30\";s:5:\"value\";d:0;}i:17;a:2:{s:4:\"date\";s:10:\"2026-08-31\";s:5:\"value\";d:0;}i:18;a:2:{s:4:\"date\";s:10:\"2026-09-01\";s:5:\"value\";d:0;}i:19;a:2:{s:4:\"date\";s:10:\"2026-09-02\";s:5:\"value\";d:0;}i:20;a:2:{s:4:\"date\";s:10:\"2026-09-03\";s:5:\"value\";d:0;}i:21;a:2:{s:4:\"date\";s:10:\"2026-09-04\";s:5:\"value\";d:0;}i:22;a:2:{s:4:\"date\";s:10:\"2026-09-05\";s:5:\"value\";d:0;}i:23;a:2:{s:4:\"date\";s:10:\"2026-09-06\";s:5:\"value\";d:0;}i:24;a:2:{s:4:\"date\";s:10:\"2026-09-07\";s:5:\"value\";d:0;}i:25;a:2:{s:4:\"date\";s:10:\"2026-09-08\";s:5:\"value\";d:0;}i:26;a:2:{s:4:\"date\";s:10:\"2026-09-09\";s:5:\"value\";d:10;}i:27;a:2:{s:4:\"date\";s:10:\"2026-09-10\";s:5:\"value\";d:1;}i:28;a:2:{s:4:\"date\";s:10:\"2026-09-11\";s:5:\"value\";d:0;}i:29;a:2:{s:4:\"date\";s:10:\"2026-09-12\";s:5:\"value\";d:1;}}', 1789210779),
('matrimony_cache_admin.dashboard.chart.revenue.30', 'a:30:{i:0;a:2:{s:4:\"date\";s:10:\"2026-08-14\";s:5:\"value\";d:0;}i:1;a:2:{s:4:\"date\";s:10:\"2026-08-15\";s:5:\"value\";d:0;}i:2;a:2:{s:4:\"date\";s:10:\"2026-08-16\";s:5:\"value\";d:0;}i:3;a:2:{s:4:\"date\";s:10:\"2026-08-17\";s:5:\"value\";d:0;}i:4;a:2:{s:4:\"date\";s:10:\"2026-08-18\";s:5:\"value\";d:0;}i:5;a:2:{s:4:\"date\";s:10:\"2026-08-19\";s:5:\"value\";d:0;}i:6;a:2:{s:4:\"date\";s:10:\"2026-08-20\";s:5:\"value\";d:0;}i:7;a:2:{s:4:\"date\";s:10:\"2026-08-21\";s:5:\"value\";d:0;}i:8;a:2:{s:4:\"date\";s:10:\"2026-08-22\";s:5:\"value\";d:0;}i:9;a:2:{s:4:\"date\";s:10:\"2026-08-23\";s:5:\"value\";d:0;}i:10;a:2:{s:4:\"date\";s:10:\"2026-08-24\";s:5:\"value\";d:0;}i:11;a:2:{s:4:\"date\";s:10:\"2026-08-25\";s:5:\"value\";d:0;}i:12;a:2:{s:4:\"date\";s:10:\"2026-08-26\";s:5:\"value\";d:0;}i:13;a:2:{s:4:\"date\";s:10:\"2026-08-27\";s:5:\"value\";d:0;}i:14;a:2:{s:4:\"date\";s:10:\"2026-08-28\";s:5:\"value\";d:0;}i:15;a:2:{s:4:\"date\";s:10:\"2026-08-29\";s:5:\"value\";d:0;}i:16;a:2:{s:4:\"date\";s:10:\"2026-08-30\";s:5:\"value\";d:0;}i:17;a:2:{s:4:\"date\";s:10:\"2026-08-31\";s:5:\"value\";d:0;}i:18;a:2:{s:4:\"date\";s:10:\"2026-09-01\";s:5:\"value\";d:0;}i:19;a:2:{s:4:\"date\";s:10:\"2026-09-02\";s:5:\"value\";d:0;}i:20;a:2:{s:4:\"date\";s:10:\"2026-09-03\";s:5:\"value\";d:0;}i:21;a:2:{s:4:\"date\";s:10:\"2026-09-04\";s:5:\"value\";d:0;}i:22;a:2:{s:4:\"date\";s:10:\"2026-09-05\";s:5:\"value\";d:0;}i:23;a:2:{s:4:\"date\";s:10:\"2026-09-06\";s:5:\"value\";d:0;}i:24;a:2:{s:4:\"date\";s:10:\"2026-09-07\";s:5:\"value\";d:0;}i:25;a:2:{s:4:\"date\";s:10:\"2026-09-08\";s:5:\"value\";d:0;}i:26;a:2:{s:4:\"date\";s:10:\"2026-09-09\";s:5:\"value\";d:0;}i:27;a:2:{s:4:\"date\";s:10:\"2026-09-10\";s:5:\"value\";d:6998;}i:28;a:2:{s:4:\"date\";s:10:\"2026-09-11\";s:5:\"value\";d:0;}i:29;a:2:{s:4:\"date\";s:10:\"2026-09-12\";s:5:\"value\";d:0;}}', 1789210779),
('matrimony_cache_admin.dashboard.chart.subscriptions.30', 'a:30:{i:0;a:2:{s:4:\"date\";s:10:\"2026-08-14\";s:5:\"value\";d:0;}i:1;a:2:{s:4:\"date\";s:10:\"2026-08-15\";s:5:\"value\";d:0;}i:2;a:2:{s:4:\"date\";s:10:\"2026-08-16\";s:5:\"value\";d:0;}i:3;a:2:{s:4:\"date\";s:10:\"2026-08-17\";s:5:\"value\";d:0;}i:4;a:2:{s:4:\"date\";s:10:\"2026-08-18\";s:5:\"value\";d:0;}i:5;a:2:{s:4:\"date\";s:10:\"2026-08-19\";s:5:\"value\";d:0;}i:6;a:2:{s:4:\"date\";s:10:\"2026-08-20\";s:5:\"value\";d:0;}i:7;a:2:{s:4:\"date\";s:10:\"2026-08-21\";s:5:\"value\";d:0;}i:8;a:2:{s:4:\"date\";s:10:\"2026-08-22\";s:5:\"value\";d:0;}i:9;a:2:{s:4:\"date\";s:10:\"2026-08-23\";s:5:\"value\";d:0;}i:10;a:2:{s:4:\"date\";s:10:\"2026-08-24\";s:5:\"value\";d:0;}i:11;a:2:{s:4:\"date\";s:10:\"2026-08-25\";s:5:\"value\";d:0;}i:12;a:2:{s:4:\"date\";s:10:\"2026-08-26\";s:5:\"value\";d:0;}i:13;a:2:{s:4:\"date\";s:10:\"2026-08-27\";s:5:\"value\";d:0;}i:14;a:2:{s:4:\"date\";s:10:\"2026-08-28\";s:5:\"value\";d:0;}i:15;a:2:{s:4:\"date\";s:10:\"2026-08-29\";s:5:\"value\";d:0;}i:16;a:2:{s:4:\"date\";s:10:\"2026-08-30\";s:5:\"value\";d:0;}i:17;a:2:{s:4:\"date\";s:10:\"2026-08-31\";s:5:\"value\";d:0;}i:18;a:2:{s:4:\"date\";s:10:\"2026-09-01\";s:5:\"value\";d:0;}i:19;a:2:{s:4:\"date\";s:10:\"2026-09-02\";s:5:\"value\";d:0;}i:20;a:2:{s:4:\"date\";s:10:\"2026-09-03\";s:5:\"value\";d:0;}i:21;a:2:{s:4:\"date\";s:10:\"2026-09-04\";s:5:\"value\";d:0;}i:22;a:2:{s:4:\"date\";s:10:\"2026-09-05\";s:5:\"value\";d:0;}i:23;a:2:{s:4:\"date\";s:10:\"2026-09-06\";s:5:\"value\";d:0;}i:24;a:2:{s:4:\"date\";s:10:\"2026-09-07\";s:5:\"value\";d:0;}i:25;a:2:{s:4:\"date\";s:10:\"2026-09-08\";s:5:\"value\";d:0;}i:26;a:2:{s:4:\"date\";s:10:\"2026-09-09\";s:5:\"value\";d:0;}i:27;a:2:{s:4:\"date\";s:10:\"2026-09-10\";s:5:\"value\";d:2;}i:28;a:2:{s:4:\"date\";s:10:\"2026-09-11\";s:5:\"value\";d:0;}i:29;a:2:{s:4:\"date\";s:10:\"2026-09-12\";s:5:\"value\";d:0;}}', 1789210779),
('matrimony_cache_admin.dashboard.stats', 'a:4:{s:5:\"users\";a:8:{s:5:\"total\";i:12;s:6:\"active\";i:12;s:9:\"new_today\";i:1;s:14:\"new_this_month\";i:12;s:4:\"male\";i:5;s:6:\"female\";i:7;s:8:\"verified\";i:10;s:7:\"premium\";i:2;}s:7:\"pending\";a:4:{s:17:\"profile_approvals\";i:2;s:16:\"photo_moderation\";i:0;s:13:\"verifications\";i:0;s:7:\"reports\";i:0;}s:10:\"engagement\";a:3:{s:15:\"interests_today\";i:1;s:14:\"messages_today\";i:0;s:12:\"logins_today\";i:3;}s:7:\"revenue\";a:5:{s:5:\"today\";d:0;s:10:\"this_month\";d:6998;s:8:\"currency\";s:3:\"INR\";s:20:\"active_subscriptions\";i:2;s:18:\"expiring_in_7_days\";i:0;}}', 1789210779),
('matrimony_cache_bf6e2a8c02ff59a50082b5392beec29f', 'i:1;', 1789205972),
('matrimony_cache_bf6e2a8c02ff59a50082b5392beec29f:timer', 'i:1789205972;', 1789205972),
('matrimony_cache_cd9bf2772ee6894b0fdf8bb41c99aad8', 'i:2;', 1789210419),
('matrimony_cache_cd9bf2772ee6894b0fdf8bb41c99aad8:timer', 'i:1789210419;', 1789210419),
('matrimony_cache_cms.page.forgot-password', 'N;', 1789208276),
('matrimony_cache_cms.page.l', 'N;', 1789208770),
('matrimony_cache_de82d691794bb51814b6761dbc5776a1', 'i:1;', 1789205513),
('matrimony_cache_de82d691794bb51814b6761dbc5776a1:timer', 'i:1789205513;', 1789205513),
('matrimony_cache_e049f4a58e70fceb055a0cb2bc02d48e', 'i:1;', 1789210204),
('matrimony_cache_e049f4a58e70fceb055a0cb2bc02d48e:timer', 'i:1789210204;', 1789210204),
('matrimony_cache_e45444ecc678a271a6330f468a373360', 'i:2;', 1789210504),
('matrimony_cache_e45444ecc678a271a6330f468a373360:timer', 'i:1789210504;', 1789210504),
('matrimony_cache_fa53b91ccc1b78668d5af58e1ed3a485', 'i:2;', 1789210504),
('matrimony_cache_fa53b91ccc1b78668d5af58e1ed3a485:timer', 'i:1789210504;', 1789210504),
('matrimony_cache_features:1', 'a:11:{s:19:\"interests_per_month\";i:5;s:21:\"profile_views_per_day\";i:20;s:23:\"contact_views_per_month\";i:0;s:11:\"photo_limit\";i:3;s:8:\"can_chat\";b:0;s:16:\"can_view_contact\";b:0;s:23:\"can_see_profile_viewers\";b:0;s:15:\"advanced_search\";b:0;s:16:\"featured_profile\";b:0;s:17:\"priority_matching\";b:0;s:16:\"support_priority\";s:8:\"standard\";}', 1789208368),
('matrimony_cache_features:524', 'a:11:{s:19:\"interests_per_month\";i:100;s:21:\"profile_views_per_day\";i:300;s:23:\"contact_views_per_month\";i:50;s:11:\"photo_limit\";i:10;s:8:\"can_chat\";b:1;s:16:\"can_view_contact\";b:1;s:23:\"can_see_profile_viewers\";b:1;s:15:\"advanced_search\";b:1;s:16:\"featured_profile\";b:0;s:17:\"priority_matching\";b:1;s:16:\"support_priority\";s:8:\"standard\";}', 1789206234),
('matrimony_cache_master-data.lifestyle-options.71f7bd7ef9c6f1995d4f3af5abd3d92f', 'a:4:{i:0;a:4:{s:2:\"id\";i:13;s:4:\"name\";s:4:\"Slim\";s:4:\"slug\";s:4:\"slim\";s:4:\"type\";s:9:\"body_type\";}i:1;a:4:{s:2:\"id\";i:14;s:4:\"name\";s:8:\"Athletic\";s:4:\"slug\";s:8:\"athletic\";s:4:\"type\";s:9:\"body_type\";}i:2;a:4:{s:2:\"id\";i:15;s:4:\"name\";s:7:\"Average\";s:4:\"slug\";s:7:\"average\";s:4:\"type\";s:9:\"body_type\";}i:3;a:4:{s:2:\"id\";i:16;s:4:\"name\";s:5:\"Heavy\";s:4:\"slug\";s:5:\"heavy\";s:4:\"type\";s:9:\"body_type\";}}', 1789291693),
('matrimony_cache_master-data.lifestyle-options.e3ed76354f0742635d33e922ac8c8c4f', 'a:5:{i:0;a:4:{s:2:\"id\";i:17;s:4:\"name\";s:9:\"Very Fair\";s:4:\"slug\";s:9:\"very-fair\";s:4:\"type\";s:10:\"complexion\";}i:1;a:4:{s:2:\"id\";i:18;s:4:\"name\";s:4:\"Fair\";s:4:\"slug\";s:4:\"fair\";s:4:\"type\";s:10:\"complexion\";}i:2;a:4:{s:2:\"id\";i:19;s:4:\"name\";s:8:\"Wheatish\";s:4:\"slug\";s:8:\"wheatish\";s:4:\"type\";s:10:\"complexion\";}i:3;a:4:{s:2:\"id\";i:20;s:4:\"name\";s:14:\"Wheatish Brown\";s:4:\"slug\";s:14:\"wheatish-brown\";s:4:\"type\";s:10:\"complexion\";}i:4;a:4:{s:2:\"id\";i:21;s:4:\"name\";s:4:\"Dark\";s:4:\"slug\";s:4:\"dark\";s:4:\"type\";s:10:\"complexion\";}}', 1789291693),
('matrimony_cache_master-data.marital-statuses', 'a:6:{i:0;a:3:{s:2:\"id\";i:6;s:4:\"name\";s:7:\"Married\";s:4:\"slug\";s:7:\"married\";}i:1;a:3:{s:2:\"id\";i:1;s:4:\"name\";s:13:\"Never Married\";s:4:\"slug\";s:13:\"never-married\";}i:2;a:3:{s:2:\"id\";i:2;s:4:\"name\";s:8:\"Divorced\";s:4:\"slug\";s:8:\"divorced\";}i:3;a:3:{s:2:\"id\";i:3;s:4:\"name\";s:7:\"Widowed\";s:4:\"slug\";s:7:\"widowed\";}i:4;a:3:{s:2:\"id\";i:4;s:4:\"name\";s:16:\"Awaiting Divorce\";s:4:\"slug\";s:16:\"awaiting-divorce\";}i:5;a:3:{s:2:\"id\";i:5;s:4:\"name\";s:8:\"Annulled\";s:4:\"slug\";s:8:\"annulled\";}}', 1789291683),
('matrimony_cache_master-data.mother-tongues', 'a:40:{i:0;a:3:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"Hindi\";s:4:\"slug\";s:5:\"hindi\";}i:1;a:3:{s:2:\"id\";i:2;s:4:\"name\";s:7:\"Bengali\";s:4:\"slug\";s:7:\"bengali\";}i:2;a:3:{s:2:\"id\";i:3;s:4:\"name\";s:7:\"Marathi\";s:4:\"slug\";s:7:\"marathi\";}i:3;a:3:{s:2:\"id\";i:4;s:4:\"name\";s:6:\"Telugu\";s:4:\"slug\";s:6:\"telugu\";}i:4;a:3:{s:2:\"id\";i:5;s:4:\"name\";s:5:\"Tamil\";s:4:\"slug\";s:5:\"tamil\";}i:5;a:3:{s:2:\"id\";i:6;s:4:\"name\";s:8:\"Gujarati\";s:4:\"slug\";s:8:\"gujarati\";}i:6;a:3:{s:2:\"id\";i:7;s:4:\"name\";s:4:\"Urdu\";s:4:\"slug\";s:4:\"urdu\";}i:7;a:3:{s:2:\"id\";i:8;s:4:\"name\";s:7:\"Kannada\";s:4:\"slug\";s:7:\"kannada\";}i:8;a:3:{s:2:\"id\";i:9;s:4:\"name\";s:4:\"Odia\";s:4:\"slug\";s:4:\"odia\";}i:9;a:3:{s:2:\"id\";i:10;s:4:\"name\";s:9:\"Malayalam\";s:4:\"slug\";s:9:\"malayalam\";}i:10;a:3:{s:2:\"id\";i:11;s:4:\"name\";s:7:\"Punjabi\";s:4:\"slug\";s:7:\"punjabi\";}i:11;a:3:{s:2:\"id\";i:12;s:4:\"name\";s:8:\"Assamese\";s:4:\"slug\";s:8:\"assamese\";}i:12;a:3:{s:2:\"id\";i:13;s:4:\"name\";s:8:\"Maithili\";s:4:\"slug\";s:8:\"maithili\";}i:13;a:3:{s:2:\"id\";i:14;s:4:\"name\";s:8:\"Bhojpuri\";s:4:\"slug\";s:8:\"bhojpuri\";}i:14;a:3:{s:2:\"id\";i:15;s:4:\"name\";s:7:\"Santali\";s:4:\"slug\";s:7:\"santali\";}i:15;a:3:{s:2:\"id\";i:16;s:4:\"name\";s:8:\"Kashmiri\";s:4:\"slug\";s:8:\"kashmiri\";}i:16;a:3:{s:2:\"id\";i:17;s:4:\"name\";s:6:\"Nepali\";s:4:\"slug\";s:6:\"nepali\";}i:17;a:3:{s:2:\"id\";i:18;s:4:\"name\";s:6:\"Sindhi\";s:4:\"slug\";s:6:\"sindhi\";}i:18;a:3:{s:2:\"id\";i:19;s:4:\"name\";s:7:\"Konkani\";s:4:\"slug\";s:7:\"konkani\";}i:19;a:3:{s:2:\"id\";i:20;s:4:\"name\";s:5:\"Dogri\";s:4:\"slug\";s:5:\"dogri\";}i:20;a:3:{s:2:\"id\";i:21;s:4:\"name\";s:8:\"Manipuri\";s:4:\"slug\";s:8:\"manipuri\";}i:21;a:3:{s:2:\"id\";i:22;s:4:\"name\";s:4:\"Bodo\";s:4:\"slug\";s:4:\"bodo\";}i:22;a:3:{s:2:\"id\";i:23;s:4:\"name\";s:8:\"Sanskrit\";s:4:\"slug\";s:8:\"sanskrit\";}i:23;a:3:{s:2:\"id\";i:24;s:4:\"name\";s:4:\"Tulu\";s:4:\"slug\";s:4:\"tulu\";}i:24;a:3:{s:2:\"id\";i:25;s:4:\"name\";s:6:\"Kodava\";s:4:\"slug\";s:6:\"kodava\";}i:25;a:3:{s:2:\"id\";i:26;s:4:\"name\";s:10:\"Rajasthani\";s:4:\"slug\";s:10:\"rajasthani\";}i:26;a:3:{s:2:\"id\";i:27;s:4:\"name\";s:8:\"Haryanvi\";s:4:\"slug\";s:8:\"haryanvi\";}i:27;a:3:{s:2:\"id\";i:28;s:4:\"name\";s:13:\"Chhattisgarhi\";s:4:\"slug\";s:13:\"chhattisgarhi\";}i:28;a:3:{s:2:\"id\";i:29;s:4:\"name\";s:6:\"Magahi\";s:4:\"slug\";s:6:\"magahi\";}i:29;a:3:{s:2:\"id\";i:30;s:4:\"name\";s:6:\"Awadhi\";s:4:\"slug\";s:6:\"awadhi\";}i:30;a:3:{s:2:\"id\";i:31;s:4:\"name\";s:7:\"Marwari\";s:4:\"slug\";s:7:\"marwari\";}i:31;a:3:{s:2:\"id\";i:32;s:4:\"name\";s:6:\"Kutchi\";s:4:\"slug\";s:6:\"kutchi\";}i:32;a:3:{s:2:\"id\";i:33;s:4:\"name\";s:8:\"Garhwali\";s:4:\"slug\";s:8:\"garhwali\";}i:33;a:3:{s:2:\"id\";i:34;s:4:\"name\";s:7:\"Kumaoni\";s:4:\"slug\";s:7:\"kumaoni\";}i:34;a:3:{s:2:\"id\";i:35;s:4:\"name\";s:5:\"Khasi\";s:4:\"slug\";s:5:\"khasi\";}i:35;a:3:{s:2:\"id\";i:36;s:4:\"name\";s:4:\"Mizo\";s:4:\"slug\";s:4:\"mizo\";}i:36;a:3:{s:2:\"id\";i:37;s:4:\"name\";s:7:\"Ladakhi\";s:4:\"slug\";s:7:\"ladakhi\";}i:37;a:3:{s:2:\"id\";i:38;s:4:\"name\";s:7:\"Tibetan\";s:4:\"slug\";s:7:\"tibetan\";}i:38;a:3:{s:2:\"id\";i:39;s:4:\"name\";s:7:\"English\";s:4:\"slug\";s:7:\"english\";}i:39;a:3:{s:2:\"id\";i:40;s:4:\"name\";s:5:\"Other\";s:4:\"slug\";s:5:\"other\";}}', 1789291683),
('matrimony_cache_master-data.religions', 'a:10:{i:0;a:3:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"Hindu\";s:4:\"slug\";s:5:\"hindu\";}i:1;a:3:{s:2:\"id\";i:2;s:4:\"name\";s:6:\"Muslim\";s:4:\"slug\";s:6:\"muslim\";}i:2;a:3:{s:2:\"id\";i:3;s:4:\"name\";s:9:\"Christian\";s:4:\"slug\";s:9:\"christian\";}i:3;a:3:{s:2:\"id\";i:4;s:4:\"name\";s:4:\"Sikh\";s:4:\"slug\";s:4:\"sikh\";}i:4;a:3:{s:2:\"id\";i:5;s:4:\"name\";s:4:\"Jain\";s:4:\"slug\";s:4:\"jain\";}i:5;a:3:{s:2:\"id\";i:6;s:4:\"name\";s:8:\"Buddhist\";s:4:\"slug\";s:8:\"buddhist\";}i:6;a:3:{s:2:\"id\";i:7;s:4:\"name\";s:5:\"Parsi\";s:4:\"slug\";s:5:\"parsi\";}i:7;a:3:{s:2:\"id\";i:8;s:4:\"name\";s:6:\"Jewish\";s:4:\"slug\";s:6:\"jewish\";}i:8;a:3:{s:2:\"id\";i:9;s:4:\"name\";s:14:\"Inter-Religion\";s:4:\"slug\";s:14:\"inter-religion\";}i:9;a:3:{s:2:\"id\";i:10;s:4:\"name\";s:11:\"No Religion\";s:4:\"slug\";s:11:\"no-religion\";}}', 1789291683);

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
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `state_id`, `name`, `slug`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Chennai', 'chennai', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 1, 'Coimbatore', 'coimbatore', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(3, 1, 'Madurai', 'madurai', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(4, 1, 'Tiruchirappalli', 'tiruchirappalli', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(5, 1, 'Salem', 'salem', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(6, 1, 'Tirunelveli', 'tirunelveli', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(7, 1, 'Erode', 'erode', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(8, 1, 'Vellore', 'vellore', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(9, 1, 'Thoothukudi', 'thoothukudi', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(10, 1, 'Thanjavur', 'thanjavur', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(11, 1, 'Dindigul', 'dindigul', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(12, 1, 'Tiruppur', 'tiruppur', NULL, NULL, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(13, 1, 'Nagercoil', 'nagercoil', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(14, 1, 'Kanchipuram', 'kanchipuram', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(15, 1, 'Cuddalore', 'cuddalore', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(16, 1, 'Karur', 'karur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(17, 1, 'Namakkal', 'namakkal', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(18, 1, 'Sivakasi', 'sivakasi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(19, 1, 'Hosur', 'hosur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(20, 1, 'Ooty', 'ooty', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(21, 2, 'Bengaluru', 'bengaluru', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(22, 2, 'Mysuru', 'mysuru', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(23, 2, 'Mangaluru', 'mangaluru', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(24, 2, 'Hubballi', 'hubballi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(25, 2, 'Belagavi', 'belagavi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(26, 2, 'Davanagere', 'davanagere', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(27, 2, 'Ballari', 'ballari', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(28, 2, 'Kalaburagi', 'kalaburagi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(29, 2, 'Shivamogga', 'shivamogga', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(30, 2, 'Tumakuru', 'tumakuru', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(31, 2, 'Udupi', 'udupi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(32, 2, 'Dharwad', 'dharwad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(33, 3, 'Mumbai', 'mumbai', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(34, 3, 'Pune', 'pune', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(35, 3, 'Nagpur', 'nagpur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(36, 3, 'Nashik', 'nashik', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(37, 3, 'Thane', 'thane', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(38, 3, 'Aurangabad', 'aurangabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(39, 3, 'Solapur', 'solapur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(40, 3, 'Kolhapur', 'kolhapur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(41, 3, 'Amravati', 'amravati', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(42, 3, 'Navi Mumbai', 'navi-mumbai', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(43, 3, 'Sangli', 'sangli', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(44, 3, 'Jalgaon', 'jalgaon', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(45, 4, 'Thiruvananthapuram', 'thiruvananthapuram', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(46, 4, 'Kochi', 'kochi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(47, 4, 'Kozhikode', 'kozhikode', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(48, 4, 'Thrissur', 'thrissur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(49, 4, 'Kollam', 'kollam', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(50, 4, 'Kannur', 'kannur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(51, 4, 'Alappuzha', 'alappuzha', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(52, 4, 'Palakkad', 'palakkad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(53, 4, 'Kottayam', 'kottayam', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(54, 4, 'Malappuram', 'malappuram', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(55, 5, 'Hyderabad', 'hyderabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(56, 5, 'Warangal', 'warangal', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(57, 5, 'Nizamabad', 'nizamabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(58, 5, 'Karimnagar', 'karimnagar', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(59, 5, 'Khammam', 'khammam', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(60, 5, 'Secunderabad', 'secunderabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(61, 5, 'Ramagundam', 'ramagundam', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(62, 6, 'Visakhapatnam', 'visakhapatnam', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(63, 6, 'Vijayawada', 'vijayawada', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(64, 6, 'Guntur', 'guntur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(65, 6, 'Nellore', 'nellore', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(66, 6, 'Tirupati', 'tirupati', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(67, 6, 'Kurnool', 'kurnool', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(68, 6, 'Rajahmundry', 'rajahmundry', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(69, 6, 'Kakinada', 'kakinada', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(70, 6, 'Anantapur', 'anantapur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(71, 6, 'Kadapa', 'kadapa', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(72, 7, 'New Delhi', 'new-delhi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(73, 7, 'North Delhi', 'north-delhi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(74, 7, 'South Delhi', 'south-delhi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(75, 7, 'East Delhi', 'east-delhi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(76, 7, 'West Delhi', 'west-delhi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(77, 7, 'Dwarka', 'dwarka', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(78, 7, 'Rohini', 'rohini', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(79, 8, 'Lucknow', 'lucknow', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(80, 8, 'Kanpur', 'kanpur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(81, 8, 'Ghaziabad', 'ghaziabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(82, 8, 'Agra', 'agra', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(83, 8, 'Varanasi', 'varanasi', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(84, 8, 'Meerut', 'meerut', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(85, 8, 'Prayagraj', 'prayagraj', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(86, 8, 'Noida', 'noida', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(87, 8, 'Bareilly', 'bareilly', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(88, 8, 'Aligarh', 'aligarh', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(89, 8, 'Moradabad', 'moradabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(90, 8, 'Gorakhpur', 'gorakhpur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(91, 9, 'Ahmedabad', 'ahmedabad', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(92, 9, 'Surat', 'surat', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(93, 9, 'Vadodara', 'vadodara', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(94, 9, 'Rajkot', 'rajkot', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(95, 9, 'Bhavnagar', 'bhavnagar', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(96, 9, 'Jamnagar', 'jamnagar', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(97, 9, 'Gandhinagar', 'gandhinagar', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(98, 9, 'Junagadh', 'junagadh', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(99, 9, 'Anand', 'anand', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(100, 10, 'Kolkata', 'kolkata', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(101, 10, 'Howrah', 'howrah', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(102, 10, 'Durgapur', 'durgapur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(103, 10, 'Asansol', 'asansol', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(104, 10, 'Siliguri', 'siliguri', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(105, 10, 'Bardhaman', 'bardhaman', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(106, 10, 'Malda', 'malda', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(107, 10, 'Kharagpur', 'kharagpur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(108, 11, 'Jaipur', 'jaipur', NULL, NULL, 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(109, 11, 'Jodhpur', 'jodhpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(110, 11, 'Udaipur', 'udaipur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(111, 11, 'Kota', 'kota', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(112, 11, 'Ajmer', 'ajmer', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(113, 11, 'Bikaner', 'bikaner', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(114, 11, 'Alwar', 'alwar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(115, 11, 'Bhilwara', 'bhilwara', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(116, 12, 'Ludhiana', 'ludhiana', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(117, 12, 'Amritsar', 'amritsar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(118, 12, 'Jalandhar', 'jalandhar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(119, 12, 'Patiala', 'patiala', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(120, 12, 'Bathinda', 'bathinda', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(121, 12, 'Mohali', 'mohali', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(122, 12, 'Pathankot', 'pathankot', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(123, 13, 'Gurugram', 'gurugram', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(124, 13, 'Faridabad', 'faridabad', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(125, 13, 'Panipat', 'panipat', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(126, 13, 'Ambala', 'ambala', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(127, 13, 'Hisar', 'hisar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(128, 13, 'Karnal', 'karnal', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(129, 13, 'Rohtak', 'rohtak', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(130, 13, 'Sonipat', 'sonipat', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(131, 14, 'Indore', 'indore', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(132, 14, 'Bhopal', 'bhopal', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(133, 14, 'Jabalpur', 'jabalpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(134, 14, 'Gwalior', 'gwalior', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(135, 14, 'Ujjain', 'ujjain', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(136, 14, 'Sagar', 'sagar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(137, 14, 'Satna', 'satna', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(138, 15, 'Patna', 'patna', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(139, 15, 'Gaya', 'gaya', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(140, 15, 'Bhagalpur', 'bhagalpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(141, 15, 'Muzaffarpur', 'muzaffarpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(142, 15, 'Darbhanga', 'darbhanga', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(143, 15, 'Purnia', 'purnia', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(144, 16, 'Bhubaneswar', 'bhubaneswar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(145, 16, 'Cuttack', 'cuttack', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(146, 16, 'Rourkela', 'rourkela', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(147, 16, 'Berhampur', 'berhampur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(148, 16, 'Sambalpur', 'sambalpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(149, 16, 'Puri', 'puri', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(150, 17, 'Guwahati', 'guwahati', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(151, 17, 'Silchar', 'silchar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(152, 17, 'Dibrugarh', 'dibrugarh', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(153, 17, 'Jorhat', 'jorhat', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(154, 17, 'Nagaon', 'nagaon', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(155, 17, 'Tezpur', 'tezpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(156, 18, 'Ranchi', 'ranchi', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(157, 18, 'Jamshedpur', 'jamshedpur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(158, 18, 'Dhanbad', 'dhanbad', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(159, 18, 'Bokaro', 'bokaro', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(160, 18, 'Deoghar', 'deoghar', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(161, 19, 'Raipur', 'raipur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(162, 19, 'Bhilai', 'bhilai', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(163, 19, 'Bilaspur', 'bilaspur', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(164, 19, 'Korba', 'korba', NULL, NULL, 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(165, 19, 'Durg', 'durg', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(166, 20, 'Dehradun', 'dehradun', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(167, 20, 'Haridwar', 'haridwar', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(168, 20, 'Roorkee', 'roorkee', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(169, 20, 'Haldwani', 'haldwani', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(170, 20, 'Rishikesh', 'rishikesh', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(171, 20, 'Nainital', 'nainital', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(172, 21, 'Shimla', 'shimla', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(173, 21, 'Solan', 'solan', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(174, 21, 'Dharamshala', 'dharamshala', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(175, 21, 'Mandi', 'mandi', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(176, 21, 'Kullu', 'kullu', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(177, 22, 'Panaji', 'panaji', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(178, 22, 'Margao', 'margao', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(179, 22, 'Vasco da Gama', 'vasco-da-gama', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(180, 22, 'Mapusa', 'mapusa', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(181, 23, 'Srinagar', 'srinagar', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(182, 23, 'Jammu', 'jammu', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(183, 23, 'Anantnag', 'anantnag', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(184, 23, 'Baramulla', 'baramulla', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(185, 24, 'Chandigarh', 'chandigarh', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(186, 25, 'Puducherry', 'puducherry', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(187, 25, 'Karaikal', 'karaikal', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(188, 25, 'Yanam', 'yanam', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(189, 25, 'Mahe', 'mahe', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(190, 26, 'Agartala', 'agartala', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(191, 26, 'Udaipur', 'udaipur', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(192, 26, 'Dharmanagar', 'dharmanagar', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(193, 27, 'Imphal', 'imphal', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(194, 27, 'Thoubal', 'thoubal', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(195, 27, 'Bishnupur', 'bishnupur', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(196, 28, 'Shillong', 'shillong', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(197, 28, 'Tura', 'tura', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(198, 28, 'Jowai', 'jowai', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(199, 29, 'Kohima', 'kohima', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(200, 29, 'Dimapur', 'dimapur', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(201, 29, 'Mokokchung', 'mokokchung', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(202, 30, 'Aizawl', 'aizawl', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(203, 30, 'Lunglei', 'lunglei', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(204, 30, 'Champhai', 'champhai', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(205, 31, 'Itanagar', 'itanagar', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(206, 31, 'Naharlagun', 'naharlagun', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(207, 31, 'Pasighat', 'pasighat', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(208, 32, 'Gangtok', 'gangtok', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(209, 32, 'Namchi', 'namchi', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(210, 32, 'Gyalshing', 'gyalshing', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(211, 33, 'Leh', 'leh', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(212, 33, 'Kargil', 'kargil', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(213, 34, 'Port Blair', 'port-blair', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(214, 35, 'Daman', 'daman', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(215, 35, 'Silvassa', 'silvassa', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(216, 35, 'Diu', 'diu', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(217, 36, 'Kavaratti', 'kavaratti', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(218, 37, 'San Francisco', 'san-francisco', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(219, 37, 'San Jose', 'san-jose', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(220, 37, 'Los Angeles', 'los-angeles', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(221, 37, 'San Diego', 'san-diego', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(222, 37, 'Fremont', 'fremont', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(223, 37, 'Irvine', 'irvine', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(224, 38, 'Dallas', 'dallas', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(225, 38, 'Houston', 'houston', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(226, 38, 'Austin', 'austin', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(227, 38, 'Plano', 'plano', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(228, 38, 'Irving', 'irving', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(229, 39, 'Jersey City', 'jersey-city', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(230, 39, 'Edison', 'edison', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(231, 39, 'Newark', 'newark', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(232, 39, 'Princeton', 'princeton', NULL, NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(233, 40, 'New York City', 'new-york-city', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(234, 40, 'Buffalo', 'buffalo', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(235, 40, 'Albany', 'albany', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(236, 41, 'Chicago', 'chicago', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(237, 41, 'Naperville', 'naperville', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(238, 41, 'Schaumburg', 'schaumburg', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(239, 42, 'Seattle', 'seattle', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(240, 42, 'Bellevue', 'bellevue', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(241, 42, 'Redmond', 'redmond', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(242, 43, 'Atlanta', 'atlanta', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(243, 43, 'Alpharetta', 'alpharetta', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(244, 44, 'Boston', 'boston', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(245, 44, 'Cambridge', 'cambridge', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(246, 45, 'Dubai', 'dubai', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(247, 45, 'Deira', 'deira', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(248, 45, 'Jumeirah', 'jumeirah', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(249, 46, 'Abu Dhabi', 'abu-dhabi', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(250, 46, 'Al Ain', 'al-ain', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(251, 47, 'Sharjah', 'sharjah', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(252, 48, 'Ajman', 'ajman', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(253, 49, 'London', 'london', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(254, 49, 'Birmingham', 'birmingham', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(255, 49, 'Manchester', 'manchester', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(256, 49, 'Leicester', 'leicester', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(257, 49, 'Leeds', 'leeds', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(258, 49, 'Coventry', 'coventry', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(259, 50, 'Glasgow', 'glasgow', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(260, 50, 'Edinburgh', 'edinburgh', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(261, 51, 'Cardiff', 'cardiff', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(262, 52, 'Belfast', 'belfast', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(263, 53, 'Toronto', 'toronto', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(264, 53, 'Ottawa', 'ottawa', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(265, 53, 'Mississauga', 'mississauga', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(266, 53, 'Brampton', 'brampton', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(267, 53, 'Waterloo', 'waterloo', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(268, 54, 'Vancouver', 'vancouver', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(269, 54, 'Surrey', 'surrey', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(270, 54, 'Burnaby', 'burnaby', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(271, 55, 'Calgary', 'calgary', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(272, 55, 'Edmonton', 'edmonton', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(273, 56, 'Montreal', 'montreal', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(274, 57, 'Sydney', 'sydney', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(275, 57, 'Newcastle', 'newcastle', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(276, 58, 'Melbourne', 'melbourne', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(277, 58, 'Geelong', 'geelong', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(278, 59, 'Brisbane', 'brisbane', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(279, 59, 'Gold Coast', 'gold-coast', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(280, 60, 'Perth', 'perth', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(281, 61, 'Singapore', 'singapore', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(282, 62, 'Doha', 'doha', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(283, 62, 'Al Rayyan', 'al-rayyan', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(284, 63, 'Riyadh', 'riyadh', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(285, 64, 'Jeddah', 'jeddah', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(286, 64, 'Makkah', 'makkah', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(287, 65, 'Dammam', 'dammam', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(288, 65, 'Al Khobar', 'al-khobar', NULL, NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(289, 66, 'Kuwait City', 'kuwait-city', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(290, 66, 'Hawalli', 'hawalli', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(291, 67, 'Muscat', 'muscat', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(292, 67, 'Salalah', 'salalah', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(293, 68, 'Kuala Lumpur', 'kuala-lumpur', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(294, 69, 'Petaling Jaya', 'petaling-jaya', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(295, 69, 'Shah Alam', 'shah-alam', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(296, 70, 'George Town', 'george-town', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(297, 71, 'Auckland', 'auckland', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(298, 72, 'Wellington', 'wellington', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(299, 73, 'Christchurch', 'christchurch', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(300, 74, 'Munich', 'munich', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(301, 75, 'Berlin', 'berlin', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(302, 76, 'Frankfurt', 'frankfurt', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(303, 77, 'Dublin', 'dublin', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(304, 78, 'Cork', 'cork', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(305, 78, 'Limerick', 'limerick', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(306, 79, 'Colombo', 'colombo', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(307, 79, 'Negombo', 'negombo', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(308, 80, 'Jaffna', 'jaffna', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(309, 81, 'Kathmandu', 'kathmandu', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(310, 81, 'Lalitpur', 'lalitpur', NULL, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

CREATE TABLE `cms_pages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `og_image_path` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `og_image_path`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'about-us', 'About Us', '<h2>About Matrimony</h2>\n<p>We help people find a life partner who fits their values, family and\ncircumstances. Every profile is reviewed before it goes live, contact\ndetails are never shown without consent, and matches are ranked by\nstated preferences rather than by who paid the most.</p>\n<h3>How we are different</h3>\n<ul>\n  <li><strong>Verified profiles.</strong> Government ID verification is\n  offered to every member and displayed as a badge once approved.</li>\n  <li><strong>Real privacy controls.</strong> You decide who sees your\n  photos, your contact details and whether you appear in search at all.</li>\n  <li><strong>Honest matching.</strong> Recommendations are scored in\n  both directions, so you are shown people for whom you are also a\n  good match.</li>\n</ul>', 'About Us — Matrimony', 'Who we are and how we approach matchmaking.', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(2, 'privacy-policy', 'Privacy Policy', '<h2>Privacy Policy</h2>\n<p>This policy explains what we collect, why, and the control you have.</p>\n<h3>What we collect</h3>\n<p>Account details (name, mobile, email, date of birth), the profile\ninformation you choose to enter, photos you upload, verification\ndocuments if you submit them, and technical data such as IP address\nand device type.</p>\n<h3>How we use it</h3>\n<p>To create and rank matches, to operate the service, to prevent fraud\nand abuse, and to meet legal obligations. We do not sell personal data.</p>\n<h3>Who can see your information</h3>\n<p>Your privacy settings govern every read. Contact details are shown\nonly to members whose plan permits it and only where you have allowed\nthat audience. Verification documents are never shown to other members\nunder any circumstances, and staff access to them is logged.</p>\n<h3>Retention</h3>\n<p>Deleting your account hides it immediately and permanently erases\nyour data after 30 days. Financial records are retained as long as tax\nlaw requires.</p>\n<h3>Your rights</h3>\n<p>You can export your data, correct it, restrict its visibility or\ndelete it from your account settings at any time.</p>', 'Privacy Policy — Matrimony', 'What we collect, why we collect it and how you stay in control.', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(3, 'terms-conditions', 'Terms & Conditions', '<h2>Terms &amp; Conditions</h2>\n<h3>Eligibility</h3>\n<p>You must be of legal marriageable age in India — 21 for men, 18 for\nwomen — and legally free to marry. Accounts that misstate marital\nstatus are removed.</p>\n<h3>Your conduct</h3>\n<p>Provide accurate information. Do not impersonate anyone, solicit\nmoney, harass other members or share another member\'s details outside\nthe platform. These are grounds for immediate suspension.</p>\n<h3>Our role</h3>\n<p>We provide a platform for introductions. We verify what we\nreasonably can, but we do not guarantee the accuracy of any member\'s\nclaims. Meet safely, verify independently, and never send money to\nanyone you have met here.</p>\n<h3>Memberships</h3>\n<p>Paid plans grant the features listed at the time of purchase for the\nstated duration. Changes to plan pricing or features do not affect a\nmembership already purchased.</p>', 'Terms & Conditions — Matrimony', 'The rules for using this service.', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(4, 'refund-policy', 'Refund & Cancellation Policy', '<h2>Refund &amp; Cancellation Policy</h2>\n<p>Memberships are digital services activated immediately on payment.</p>\n<h3>Eligible for refund</h3>\n<ul>\n  <li>A duplicate charge for the same order.</li>\n  <li>A payment debited where the membership was not activated.</li>\n  <li>A request made within 7 days of purchase where no paid feature —\n  contact views, chat or interests beyond the free allowance — has been\n  used.</li>\n</ul>\n<h3>Not eligible</h3>\n<ul>\n  <li>Requests made after the 7-day window.</li>\n  <li>Memberships where paid features have already been consumed.</li>\n  <li>Accounts suspended for violating these terms.</li>\n</ul>\n<h3>How to request</h3>\n<p>Write to support with your registered mobile number and the payment\nreference. Approved refunds are returned to the original payment method\nwithin 5–7 working days.</p>', 'Refund Policy — Matrimony', 'When a membership payment can be refunded.', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(5, 'contact-us', 'Contact Us', '<h2>Contact Us</h2>\n<p>Support is available Monday to Saturday, 9:00 to 18:00 IST.</p>\n<p>Email: support@matrimony.test<br>Phone: +91 90000 00000</p>\n<p>For anything involving safety, harassment or a suspected fake\nprofile, use the Report button on the profile itself — those reach the\nmoderation queue directly and are actioned ahead of general support.</p>', 'Contact Us — Matrimony', 'How to reach our support team.', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', '2026-08-27 11:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `religion_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`id`, `name`, `slug`, `sort_order`, `is_active`, `religion_id`, `created_at`, `updated_at`) VALUES
(1, 'Brahmin', 'hindu-brahmin', 0, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(2, 'Kshatriya', 'hindu-kshatriya', 1, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(3, 'Vaishya', 'hindu-vaishya', 2, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(4, 'Nadar', 'hindu-nadar', 3, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(5, 'Mudaliar', 'hindu-mudaliar', 4, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(6, 'Chettiar', 'hindu-chettiar', 5, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(7, 'Gounder', 'hindu-gounder', 6, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(8, 'Vanniyar', 'hindu-vanniyar', 7, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(9, 'Reddy', 'hindu-reddy', 8, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(10, 'Kamma', 'hindu-kamma', 9, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(11, 'Naidu', 'hindu-naidu', 10, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(12, 'Yadav', 'hindu-yadav', 11, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(13, 'Kurmi', 'hindu-kurmi', 12, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(14, 'Jat', 'hindu-jat', 13, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(15, 'Lingayat', 'hindu-lingayat', 14, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(16, 'Vokkaliga', 'hindu-vokkaliga', 15, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(17, 'Nair', 'hindu-nair', 16, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(18, 'Ezhava', 'hindu-ezhava', 17, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(19, 'Menon', 'hindu-menon', 18, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(20, 'Patel', 'hindu-patel', 19, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(21, 'Kayastha', 'hindu-kayastha', 20, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(22, 'Arora', 'hindu-arora', 21, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(23, 'Khatri', 'hindu-khatri', 22, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(24, 'Saini', 'hindu-saini', 23, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(25, 'Scheduled Caste', 'hindu-scheduled-caste', 24, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(26, 'Scheduled Tribe', 'hindu-scheduled-tribe', 25, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(27, 'Caste No Bar', 'hindu-caste-no-bar', 26, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(28, 'Sunni', 'muslim-sunni', 0, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(29, 'Shia', 'muslim-shia', 1, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(30, 'Ansari', 'muslim-ansari', 2, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(31, 'Dawoodi Bohra', 'muslim-dawoodi-bohra', 3, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(32, 'Memon', 'muslim-memon', 4, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(33, 'Mapila', 'muslim-mapila', 5, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(34, 'Rowther', 'muslim-rowther', 6, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(35, 'Sheikh', 'muslim-sheikh', 7, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(36, 'Syed', 'muslim-syed', 8, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(37, 'Pathan', 'muslim-pathan', 9, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(38, 'Mughal', 'muslim-mughal', 10, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(39, 'Others', 'muslim-others', 11, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(40, 'Roman Catholic', 'christian-roman-catholic', 0, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(41, 'Protestant', 'christian-protestant', 1, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(42, 'Syrian Catholic', 'christian-syrian-catholic', 2, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(43, 'Syro Malabar', 'christian-syro-malabar', 3, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(44, 'Jacobite', 'christian-jacobite', 4, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(45, 'Marthoma', 'christian-marthoma', 5, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(46, 'Orthodox', 'christian-orthodox', 6, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(47, 'Pentecostal', 'christian-pentecostal', 7, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(48, 'CSI', 'christian-csi', 8, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(49, 'Born Again', 'christian-born-again', 9, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(50, 'Latin Catholic', 'christian-latin-catholic', 10, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(51, 'Others', 'christian-others', 11, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(52, 'Jat Sikh', 'sikh-jat-sikh', 0, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(53, 'Ramgarhia', 'sikh-ramgarhia', 1, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(54, 'Khatri Sikh', 'sikh-khatri-sikh', 2, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(55, 'Arora Sikh', 'sikh-arora-sikh', 3, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(56, 'Ramdasia', 'sikh-ramdasia', 4, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(57, 'Ahluwalia', 'sikh-ahluwalia', 5, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(58, 'Others', 'sikh-others', 6, 1, 4, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(59, 'Digambar', 'jain-digambar', 0, 1, 5, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(60, 'Shwetambar', 'jain-shwetambar', 1, 1, 5, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(61, 'Vania', 'jain-vania', 2, 1, 5, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(62, 'Others', 'jain-others', 3, 1, 5, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(63, 'Mahayana', 'buddhist-mahayana', 0, 1, 6, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(64, 'Theravada', 'buddhist-theravada', 1, 1, 6, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(65, 'Navayana', 'buddhist-navayana', 2, 1, 6, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(66, 'Others', 'buddhist-others', 3, 1, 6, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(67, 'Zoroastrian', 'parsi-zoroastrian', 0, 1, 7, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(68, 'Bene Israel', 'jewish-bene-israel', 0, 1, 8, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(69, 'Cochin Jews', 'jewish-cochin-jews', 1, 1, 8, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(70, 'Open to all', 'inter-religion-open-to-all', 0, 1, 9, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(71, 'Atheist', 'no-religion-atheist', 0, 1, 10, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(72, 'Spiritual', 'no-religion-spiritual', 1, 1, 10, '2026-08-27 11:57:34', '2026-08-27 11:57:34');

-- --------------------------------------------------------

--
-- Table structure for table `contact_enquiries`
--

CREATE TABLE `contact_enquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `mobile` varchar(16) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `handled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_enquiries`
--

INSERT INTO `contact_enquiries` (`id`, `name`, `email`, `mobile`, `subject`, `message`, `status`, `handled_by`, `response`, `created_at`, `updated_at`) VALUES
(1, 'test', 'test@pojotechnology.com', '+916374532660', 'test', 'many fake profile is there please check it', 'open', NULL, NULL, '2026-09-05 08:25:49', '2026-09-05 08:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `contact_views`
--

CREATE TABLE `contact_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `viewer_id` bigint(20) UNSIGNED NOT NULL,
  `viewed_id` bigint(20) UNSIGNED NOT NULL,
  `period` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_one_id` bigint(20) UNSIGNED NOT NULL,
  `user_two_id` bigint(20) UNSIGNED NOT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `last_message_preview` varchar(160) DEFAULT NULL,
  `last_message_sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_one_unread_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_two_unread_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `messages_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `match_id`, `user_one_id`, `user_two_id`, `last_message_at`, `last_message_preview`, `last_message_sender_id`, `user_one_unread_count`, `user_two_unread_count`, `messages_count`, `status`, `created_at`, `updated_at`) VALUES
(3, 3, 519, 524, '2026-09-10 04:36:21', 'hi', 519, 0, 0, 3, 'active', '2026-09-10 04:29:25', '2026-09-10 04:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `iso2` char(2) NOT NULL,
  `iso3` char(3) NOT NULL,
  `phone_code` varchar(8) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `slug`, `iso2`, `iso3`, `phone_code`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'India', 'india', 'IN', 'IND', '+91', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 'United States', 'united-states', 'US', 'USA', '+1', 1, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(3, 'United Arab Emirates', 'united-arab-emirates', 'AE', 'ARE', '+971', 2, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(4, 'United Kingdom', 'united-kingdom', 'GB', 'GBR', '+44', 3, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(5, 'Canada', 'canada', 'CA', 'CAN', '+1', 4, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(6, 'Australia', 'australia', 'AU', 'AUS', '+61', 5, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(7, 'Singapore', 'singapore', 'SG', 'SGP', '+65', 6, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(8, 'Qatar', 'qatar', 'QA', 'QAT', '+974', 7, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(9, 'Saudi Arabia', 'saudi-arabia', 'SA', 'SAU', '+966', 8, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(10, 'Kuwait', 'kuwait', 'KW', 'KWT', '+965', 9, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(11, 'Oman', 'oman', 'OM', 'OMN', '+968', 10, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(12, 'Malaysia', 'malaysia', 'MY', 'MYS', '+60', 11, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(13, 'New Zealand', 'new-zealand', 'NZ', 'NZL', '+64', 12, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(14, 'Germany', 'germany', 'DE', 'DEU', '+49', 13, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(15, 'Ireland', 'ireland', 'IE', 'IRL', '+353', 14, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(16, 'Sri Lanka', 'sri-lanka', 'LK', 'LKA', '+94', 15, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(17, 'Nepal', 'nepal', 'NP', 'NPL', '+977', 16, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `platform` varchar(10) NOT NULL,
  `device_id` varchar(120) DEFAULT NULL,
  `app_version` varchar(20) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `educations`
--

CREATE TABLE `educations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(40) NOT NULL DEFAULT 'graduate',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `educations`
--

INSERT INTO `educations` (`id`, `name`, `slug`, `sort_order`, `is_active`, `category`, `created_at`, `updated_at`) VALUES
(1, 'High School', 'high-school', 0, 1, 'school', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(2, 'Higher Secondary', 'higher-secondary', 1, 1, 'school', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(3, 'Diploma', 'diploma', 2, 1, 'diploma', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(4, 'Polytechnic', 'polytechnic', 3, 1, 'diploma', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(5, 'ITI', 'iti', 4, 1, 'diploma', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(6, 'B.A', 'ba', 5, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(7, 'B.Com', 'bcom', 6, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(8, 'B.Sc', 'bsc', 7, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(9, 'B.B.A', 'bba', 8, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(10, 'B.C.A', 'bca', 9, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(11, 'B.E / B.Tech', 'be-btech', 10, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(12, 'B.Arch', 'barch', 11, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(13, 'MBBS', 'mbbs', 12, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(14, 'B.D.S', 'bds', 13, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(15, 'B.A.M.S', 'bams', 14, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(16, 'B.H.M.S', 'bhms', 15, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(17, 'B.Pharm', 'bpharm', 16, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(18, 'B.Ed', 'bed', 17, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(19, 'LLB', 'llb', 18, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(20, 'B.Des', 'bdes', 19, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(21, 'B.F.A', 'bfa', 20, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(22, 'B.P.T', 'bpt', 21, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(23, 'B.Sc Nursing', 'bsc-nursing', 22, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(24, 'Bachelor of Hotel Management', 'bachelor-of-hotel-management', 23, 1, 'graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(25, 'M.A', 'ma', 24, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(26, 'M.Com', 'mcom', 25, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(27, 'M.Sc', 'msc', 26, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(28, 'M.B.A', 'mba', 27, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(29, 'M.C.A', 'mca', 28, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(30, 'M.E / M.Tech', 'me-mtech', 29, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(31, 'M.Arch', 'march', 30, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(32, 'M.D', 'md', 31, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(33, 'M.S (Medicine)', 'ms-medicine', 32, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(34, 'M.D.S', 'mds', 33, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(35, 'M.Pharm', 'mpharm', 34, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(36, 'M.Ed', 'med', 35, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(37, 'LLM', 'llm', 36, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(38, 'M.Des', 'mdes', 37, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(39, 'M.F.A', 'mfa', 38, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(40, 'M.P.T', 'mpt', 39, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(41, 'C.A', 'ca', 40, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(42, 'C.S', 'cs', 41, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(43, 'I.C.W.A', 'icwa', 42, 1, 'post_graduate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(44, 'Ph.D', 'phd', 43, 1, 'doctorate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(45, 'D.Sc', 'dsc', 44, 1, 'doctorate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(46, 'D.Litt', 'dlitt', 45, 1, 'doctorate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(47, 'M.Phil', 'mphil', 46, 1, 'doctorate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(48, 'Post Doctoral', 'post-doctoral', 47, 1, 'doctorate', '2026-08-27 11:57:34', '2026-08-27 11:57:34');

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
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'general',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `category`, `question`, `answer`, `sort_order`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'getting_started', 'How do I create a profile?', 'Register with your mobile number, verify the OTP, then complete the profile sections. A profile at 80% or more completion appears far higher in search results.', 0, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(2, 'getting_started', 'Why is my profile pending approval?', 'Every new profile is reviewed by our moderation team before it becomes visible. This usually takes a few hours and protects everyone from fake accounts.', 1, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(3, 'getting_started', 'Can my parents create a profile for me?', 'Yes. Choose who is creating the profile during registration — parent, sibling, relative or friend — and the wording throughout adapts.', 2, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(4, 'privacy', 'Who can see my photos?', 'You choose: everyone, registered members, premium members only, or only people you have connected with. Anyone outside that audience sees a blurred version.', 3, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(5, 'privacy', 'Who can see my mobile number?', 'Only members whose plan includes contact access and who fall within the audience you selected in privacy settings. You can restrict it to connections only.', 4, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(6, 'privacy', 'Can I hide my profile temporarily?', 'Yes. Deactivate from your profile settings — your data is kept and you can restore it simply by logging in again.', 5, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(7, 'matching', 'How are matches calculated?', 'We score compatibility across religion, community, age, language, location, education, occupation, lifestyle, income and height, weighted by what you said matters. Scores are calculated in both directions, so you see people for whom you are also a good match.', 6, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(8, 'matching', 'Why do I see profiles outside my preferences?', 'Preferences are ranking signals rather than hard filters, so a strong match that misses one criterion is not hidden from you. Use search filters when you want strict boundaries.', 7, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(9, 'interests', 'What happens when I send an interest?', 'The other member is notified and can accept or decline. If they accept, you become a connection and can start chatting.', 8, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(10, 'interests', 'Can I resend a declined interest?', 'Not immediately. There is a 30-day cooling-off period before an interest can be sent to the same profile again.', 9, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(11, 'membership', 'What do paid plans include?', 'More interests, contact access, chat, seeing who viewed your profile and priority in match results. The plans page lists exactly what each tier grants.', 10, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(12, 'membership', 'What happens to my plan if prices change?', 'Nothing. Your membership keeps the features it had when you bought it, for its full duration.', 11, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(13, 'membership', 'How do I get an invoice?', 'Every successful payment generates a GST invoice, downloadable from Payment History in your account.', 12, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(14, 'safety', 'How do I report someone?', 'Use the Report button on their profile or in the chat. Reports go straight to our moderation team and are reviewed ahead of general support tickets.', 13, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(15, 'safety', 'Someone is asking me for money. What do I do?', 'Do not send anything. Report the profile immediately using the \"Fraud or money request\" category. This is the most common scam on matrimonial platforms and we act on it quickly.', 14, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(16, 'safety', 'What does the verified badge mean?', 'That member submitted a government ID and our team confirmed it matches their profile name. It does not verify anything else they have claimed.', 15, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(17, 'account', 'How do I delete my account?', 'Profile settings → Delete account. Your profile disappears immediately and all personal data is permanently erased after 30 days.', 16, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(18, 'account', 'I did not receive my OTP.', 'Wait for the resend timer, then request a new code. Check that the number is correct and that your phone has signal. If it still fails, contact support.', 17, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `income_ranges`
--

CREATE TABLE `income_ranges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `min_amount` bigint(20) UNSIGNED NOT NULL,
  `max_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `income_ranges`
--

INSERT INTO `income_ranges` (`id`, `name`, `slug`, `min_amount`, `max_amount`, `currency`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'No Income', 'no-income', 0, 0, 'INR', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 'Below ₹1 Lakh', 'below-rs1-lakh', 0, 100000, 'INR', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(3, '₹1 - 2 Lakh', 'rs1-2-lakh', 100000, 200000, 'INR', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(4, '₹2 - 3 Lakh', 'rs2-3-lakh', 200000, 300000, 'INR', 3, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(5, '₹3 - 5 Lakh', 'rs3-5-lakh', 300000, 500000, 'INR', 4, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(6, '₹5 - 7.5 Lakh', 'rs5-75-lakh', 500000, 750000, 'INR', 5, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(7, '₹7.5 - 10 Lakh', 'rs75-10-lakh', 750000, 1000000, 'INR', 6, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(8, '₹10 - 15 Lakh', 'rs10-15-lakh', 1000000, 1500000, 'INR', 7, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(9, '₹15 - 20 Lakh', 'rs15-20-lakh', 1500000, 2000000, 'INR', 8, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(10, '₹20 - 30 Lakh', 'rs20-30-lakh', 2000000, 3000000, 'INR', 9, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(11, '₹30 - 50 Lakh', 'rs30-50-lakh', 3000000, 5000000, 'INR', 10, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(12, '₹50 - 75 Lakh', 'rs50-75-lakh', 5000000, 7500000, 'INR', 11, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(13, '₹75 Lakh - 1 Crore', 'rs75-lakh-1-crore', 7500000, 10000000, 'INR', 12, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(14, 'Above ₹1 Crore', 'above-rs1-crore', 10000000, NULL, 'INR', 13, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `message` varchar(500) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interests`
--

INSERT INTO `interests` (`id`, `sender_id`, `receiver_id`, `status`, `message`, `responded_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 519, 526, 'pending', NULL, NULL, '2026-09-09 14:22:40', '2026-09-09 14:22:40', NULL),
(4, 519, 524, 'accepted', NULL, '2026-09-10 04:29:25', '2026-09-10 04:29:10', '2026-09-10 04:29:25', NULL),
(5, 1, 519, 'pending', NULL, NULL, '2026-09-12 09:35:49', '2026-09-12 09:35:49', NULL);

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

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'reports', '{\"uuid\":\"e81c7309-dd53-46a3-af4c-4fb28f192c81\",\"displayName\":\"App\\\\Jobs\\\\ExportUsers\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":2,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":900,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ExportUsers\",\"command\":\"O:20:\\\"App\\\\Jobs\\\\ExportUsers\\\":3:{s:11:\\\"requesterId\\\";i:1;s:7:\\\"filters\\\";a:0:{}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1787893811, 1787893811),
(2, 'high', '{\"uuid\":\"5c1beb1c-a421-47f8-86c6-539848531e7c\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:1;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1787894030, 1787894030),
(3, 'media', '{\"uuid\":\"9efbf7b2-05cc-4672-8d1a-2b86d2558beb\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:1;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1787903517, 1787903517),
(5, 'default', '{\"uuid\":\"c6a3013a-ba59-4696-9c61-5ac1b8e83ca9\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1787906285, 1787906285),
(6, 'default', '{\"uuid\":\"3a0bd658-084d-44d3-b8b4-b1c4a7b204a3\",\"displayName\":\"App\\\\Listeners\\\\SendShortlistNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:39:\\\"App\\\\Listeners\\\\SendShortlistNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\ProfileShortlisted\\\":1:{s:9:\\\"shortlist\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:20:\\\"App\\\\Models\\\\Shortlist\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1787906547, 1787906547),
(7, 'default', '{\"uuid\":\"82d51a86-ff0d-4759-aa94-a31c35632925\",\"displayName\":\"App\\\\Listeners\\\\SendShortlistNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:39:\\\"App\\\\Listeners\\\\SendShortlistNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\ProfileShortlisted\\\":1:{s:9:\\\"shortlist\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:20:\\\"App\\\\Models\\\\Shortlist\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1787906547, 1787906547),
(8, 'high', '{\"uuid\":\"e563573b-3114-4d25-a4c4-dfecb28e6d4b\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:2;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788582917, 1788582917),
(9, 'media', '{\"uuid\":\"8c29fbbe-3630-4d03-b815-a986b2d994db\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:2;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788588666, 1788588666),
(10, 'media', '{\"uuid\":\"b794f263-66d6-4501-924c-641f04b29e66\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:3;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788592187, 1788592187),
(11, 'high', '{\"uuid\":\"cdffff74-81ce-44e1-a54e-8271d1d839a1\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:3;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788592288, 1788592288),
(12, 'default', '{\"uuid\":\"6a7ee726-e405-43a4-a90d-f013a2f40213\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592553, 1788592553),
(13, 'default', '{\"uuid\":\"30fabbda-ea47-4e88-ae93-e5aed6ff7541\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592553, 1788592553),
(14, 'default', '{\"uuid\":\"b0bf371b-a7e5-4460-8951-ec977a662e73\",\"displayName\":\"App\\\\Listeners\\\\SendShortlistNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:39:\\\"App\\\\Listeners\\\\SendShortlistNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\ProfileShortlisted\\\":1:{s:9:\\\"shortlist\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:20:\\\"App\\\\Models\\\\Shortlist\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592560, 1788592560),
(15, 'default', '{\"uuid\":\"81ce838a-02f2-45fd-8550-1b571d42d8ed\",\"displayName\":\"App\\\\Listeners\\\\SendShortlistNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:39:\\\"App\\\\Listeners\\\\SendShortlistNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\ProfileShortlisted\\\":1:{s:9:\\\"shortlist\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:20:\\\"App\\\\Models\\\\Shortlist\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592560, 1788592560),
(16, 'default', '{\"uuid\":\"91b7b41a-7abf-401c-bc9e-639e38a828b7\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592635, 1788592635),
(17, 'default', '{\"uuid\":\"7938ce18-77b8-47f5-bc9d-7cd65e732e6f\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592635, 1788592635),
(18, 'default', '{\"uuid\":\"8a823693-491e-49f5-ace8-c499f4691244\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592635, 1788592635),
(19, 'default', '{\"uuid\":\"39dfc703-5118-48fc-9a79-57c42adb30ea\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788592635, 1788592635),
(20, 'media', '{\"uuid\":\"19292378-3509-4db7-8f68-ad4a7c7d40fb\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:4;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788593057, 1788593057),
(21, 'high', '{\"uuid\":\"139542f9-dc33-4d0f-915e-0b7dd7a5fdc9\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:4;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788593374, 1788593374),
(22, 'media', '{\"uuid\":\"0748f447-f6a5-48e3-9126-e3b3d6813e46\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:5;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788593569, 1788593569),
(23, 'high', '{\"uuid\":\"345a268d-3cb3-4bfb-a4c5-2a91f1630e9e\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:5;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788593585, 1788593585),
(24, 'default', '{\"uuid\":\"348ca68a-2d1a-45e7-abe8-9ec5fe4dcb7d\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594770, 1788594770),
(25, 'default', '{\"uuid\":\"66fe9fef-5ca1-489c-a7c5-f19b45c26b32\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594770, 1788594770),
(26, 'high', '{\"uuid\":\"06c6d489-4412-4e0e-a6e3-89eafed17bfe\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594975, 1788594975),
(27, 'high', '{\"uuid\":\"f2f947b5-033a-44d6-b379-ffa6cc56da82\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594975, 1788594975),
(28, 'high', '{\"uuid\":\"5cefac45-3fbf-4774-bce1-bb21abcaf5a5\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594975, 1788594975),
(29, 'high', '{\"uuid\":\"14fc69fd-00b9-4692-a094-3ed1c3380ec7\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:508;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:1;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788594999, 1788594999),
(30, 'high', '{\"uuid\":\"c01864ce-212c-4d05-bf40-0ac225d66ead\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595023, 1788595023),
(31, 'high', '{\"uuid\":\"bd4e00ea-b2f8-464d-a04d-3744cec3dfa1\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595023, 1788595023),
(32, 'high', '{\"uuid\":\"b5f7d53b-5019-4d41-a9ca-99d36b41feed\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595023, 1788595023),
(33, 'high', '{\"uuid\":\"6c74527c-4046-433d-910c-1a33687b8129\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:505;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:2;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595026, 1788595026),
(34, 'high', '{\"uuid\":\"533502f0-29b6-4530-a994-d08c0188d9f2\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595091, 1788595091),
(35, 'high', '{\"uuid\":\"5116fb59-c743-4837-9747-b4e977682b49\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595091, 1788595091),
(36, 'high', '{\"uuid\":\"0ee62e75-e23f-4023-a3b0-1f8cf71d1e18\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595091, 1788595091),
(37, 'high', '{\"uuid\":\"13a806bf-c566-4d55-bf1a-a6be64b663ff\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:508;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:3;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595112, 1788595112),
(38, 'high', '{\"uuid\":\"0edd34b1-b507-4f29-b1fb-c19a772f9511\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595505, 1788595505),
(39, 'high', '{\"uuid\":\"f757a7f4-954b-4208-8a8a-77af54ff5fe4\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595505, 1788595505);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(40, 'high', '{\"uuid\":\"e90c3a87-b55d-4f91-8ca3-ccfee3fa7e1c\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595505, 1788595505),
(41, 'high', '{\"uuid\":\"2706791b-0c94-4a95-84b9-56b90e44ee43\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:508;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:4;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595592, 1788595592),
(42, 'high', '{\"uuid\":\"5e989908-0aa5-444e-aa7a-d0077dc7d910\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595612, 1788595612),
(43, 'high', '{\"uuid\":\"b7c19823-4b59-41d4-a040-2d7262304892\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595612, 1788595612),
(44, 'high', '{\"uuid\":\"b5684ff1-a74d-450a-bf53-729df0e47986\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595612, 1788595612),
(45, 'high', '{\"uuid\":\"58c06c77-31ad-44a5-9c83-c97c356a1d9d\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595932, 1788595932),
(46, 'high', '{\"uuid\":\"b080fc6e-ab48-462f-a66c-ff5ae583c147\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595932, 1788595932),
(47, 'high', '{\"uuid\":\"a1322764-72c9-45fa-b44f-3ec74d1c8269\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788595932, 1788595932),
(48, 'default', '{\"uuid\":\"67563417-c252-49d7-80ae-7a5ef77c6e98\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596003, 1788596003),
(49, 'default', '{\"uuid\":\"c39c9392-c93d-48f0-8c57-aec16ac07e09\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596003, 1788596003),
(50, 'high', '{\"uuid\":\"936b8927-7bc3-4f44-a815-ce18cbbfb468\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:508;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:6;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596122, 1788596122),
(51, 'high', '{\"uuid\":\"2bfcf524-7878-43f2-aa28-23ddcdeb1719\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:505;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:6;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596149, 1788596149),
(52, 'default', '{\"uuid\":\"5f3be134-d4de-4810-8099-ab5eaa25ed6b\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596263, 1788596263),
(53, 'default', '{\"uuid\":\"32d76bfa-413f-4e8c-8258-adb332177105\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596263, 1788596263),
(54, 'default', '{\"uuid\":\"2d286b23-7dae-4f2c-a7ff-0370932bc22c\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596263, 1788596263),
(55, 'default', '{\"uuid\":\"690100eb-59a6-4fc9-96b2-9723bf2a43d5\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596263, 1788596263),
(56, 'high', '{\"uuid\":\"33756646-f501-41d9-900f-5349f7b24c96\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:6;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788596393, 1788596393),
(57, 'high', '{\"uuid\":\"4c214600-e165-4bb6-8f01-5bb811d43f21\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596490, 1788596490),
(58, 'high', '{\"uuid\":\"1106a789-a0ee-46e4-9bcb-7e9e79bdc942\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596490, 1788596490),
(59, 'high', '{\"uuid\":\"7967cd27-d970-40bd-ab6a-d7ae02af6adf\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596490, 1788596490),
(60, 'high', '{\"uuid\":\"17e37e84-97a6-45e1-9f9d-99196c96b69b\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:506;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:7;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788596499, 1788596499),
(61, 'high', '{\"uuid\":\"155f8506-4597-48ad-8998-91f169ce6d23\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:7;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788601956, 1788601956),
(62, 'reports', '{\"uuid\":\"6fa71029-a6c4-44ae-919b-434cedf71f7a\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1788841220, 1788841220),
(63, 'default', '{\"uuid\":\"9ffd83b7-d9d9-4e63-be31-af259e0d2680\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788841220, 1788841220),
(64, 'default', '{\"uuid\":\"ed899fd4-715f-4803-ad4b-523bd5814d2a\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788841220, 1788841220),
(65, 'reports', '{\"uuid\":\"4ce58a7a-00b9-45dc-8310-32757f0da46f\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1788842118, 1788842118),
(66, 'default', '{\"uuid\":\"000fa65c-2aea-4a60-9b1f-9ecf6c3f41af\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788842118, 1788842118),
(67, 'default', '{\"uuid\":\"3725c72f-720d-4702-b179-522a60aacd36\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788842118, 1788842118),
(68, 'reports', '{\"uuid\":\"5c65eaff-4e54-4dd7-9414-968938942666\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1788844251, 1788844251),
(69, 'default', '{\"uuid\":\"3edeb9a4-fbf3-4b18-b2b9-15bd4e24ff4c\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788844251, 1788844251),
(70, 'default', '{\"uuid\":\"4c6baa1a-22a4-4fee-825b-3b7d65305218\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788844251, 1788844251),
(71, 'reports', '{\"uuid\":\"12a10ccb-4868-4992-88a9-66511cfdc406\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1788844565, 1788844565),
(72, 'default', '{\"uuid\":\"a0548f6e-8b38-4a60-9b59-06d0b870329f\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788844565, 1788844565);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(73, 'default', '{\"uuid\":\"2221fdfd-c2be-4454-a3fb-964460b315f4\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788844565, 1788844565),
(74, 'high', '{\"uuid\":\"171d70ba-a5b0-4ccb-b743-21fb4d04432d\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:8;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788844786, 1788844786),
(75, 'media', '{\"uuid\":\"c7e06b4c-8043-4b34-a562-60ffd5cec4aa\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:6;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788845428, 1788845428),
(76, 'high', '{\"uuid\":\"b114f268-780e-4ee6-a53a-b4c43ddbb4c1\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:9;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788845474, 1788845474),
(77, 'high', '{\"uuid\":\"4856d241-6e16-4f6d-93c4-c879043a3cb1\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:10;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788845615, 1788845615),
(78, 'media', '{\"uuid\":\"20223755-146a-4ac1-a0d6-bea0503ca0cb\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:7;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788846081, 1788846081),
(79, 'media', '{\"uuid\":\"f1da765a-31f1-4448-849f-421a6390b057\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:8;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1788846144, 1788846144),
(80, 'high', '{\"uuid\":\"81c2f3d4-b14a-4c13-834f-c93a57d4ed44\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:11;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788846163, 1788846163),
(81, 'high', '{\"uuid\":\"029dd511-475f-4779-909c-c4226988d178\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:12;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1788846175, 1788846175),
(82, 'default', '{\"uuid\":\"e8734caa-3597-46e4-8f2f-e5a82525ce18\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788846411, 1788846411),
(83, 'default', '{\"uuid\":\"23e5580e-99fb-42c2-8158-5f7c69fc823a\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788846411, 1788846411),
(84, 'default', '{\"uuid\":\"4d65af2f-7a46-4768-bf70-bd98afa9cbe8\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788957533, 1788957533),
(85, 'default', '{\"uuid\":\"0d4a4baf-e4ab-4af6-9ff4-30c25d931f81\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788957533, 1788957533),
(86, 'default', '{\"uuid\":\"7537b3f1-9fb8-4d09-afe1-3516b4df626a\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788957551, 1788957551),
(87, 'default', '{\"uuid\":\"1e3a4440-c528-4ed8-9653-37cbeda6b53f\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:7;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788957551, 1788957551),
(88, 'default', '{\"uuid\":\"7682263c-1bf0-49e6-84f2-6024437cf9bd\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788963760, 1788963760),
(89, 'default', '{\"uuid\":\"b3b7d3f5-0ddd-4f12-b22e-f6df4a14c699\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1788963760, 1788963760),
(90, 'media', '{\"uuid\":\"d59d5b50-1527-4ed2-ad0d-db18d6ab4b23\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:9;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1789014354, 1789014354),
(91, 'high', '{\"uuid\":\"0f819041-f1a1-4e44-8d17-49acc7d0732c\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:13;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1789014372, 1789014372),
(92, 'media', '{\"uuid\":\"62b625a5-5c71-47fe-a6b7-75a1130de8f0\",\"displayName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessProfilePhoto\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessProfilePhoto\\\":2:{s:7:\\\"photoId\\\";i:10;s:5:\\\"queue\\\";s:5:\\\"media\\\";}\"}}', 0, NULL, 1789014502, 1789014502),
(93, 'high', '{\"uuid\":\"d539e2d2-6cc2-4adf-b094-0263efa3286f\",\"displayName\":\"App\\\\Jobs\\\\SendPushNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendPushNotification\",\"command\":\"O:29:\\\"App\\\\Jobs\\\\SendPushNotification\\\":2:{s:14:\\\"notificationId\\\";i:14;s:5:\\\"queue\\\";s:4:\\\"high\\\";}\"}}', 0, NULL, 1789014526, 1789014526),
(94, 'default', '{\"uuid\":\"55a3548e-c6f9-457b-a853-d5f27f472d12\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014550, 1789014550),
(95, 'default', '{\"uuid\":\"f7a10d0e-e848-4fd4-bd23-8c660dc3379f\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014550, 1789014550),
(96, 'default', '{\"uuid\":\"1d122033-328c-4a02-8407-64c87dacb000\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014565, 1789014565),
(97, 'default', '{\"uuid\":\"aa18177c-8df1-44e0-ae75-cb2689b9bc57\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014565, 1789014565),
(98, 'default', '{\"uuid\":\"9d3ed454-b62e-4772-a2c0-663e684985c3\",\"displayName\":\"App\\\\Listeners\\\\CreateConversationForMatch\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:40:\\\"App\\\\Listeners\\\\CreateConversationForMatch\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014565, 1789014565),
(99, 'default', '{\"uuid\":\"d7d6fa4a-1e55-4a8f-854e-f441a3f86ca2\",\"displayName\":\"App\\\\Listeners\\\\SendMatchNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMatchNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:27:\\\"App\\\\Events\\\\InterestAccepted\\\":2:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014565, 1789014565),
(100, 'reports', '{\"uuid\":\"24eff824-91ea-41d6-a8c7-98e87ae92212\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1789014745, 1789014745),
(101, 'default', '{\"uuid\":\"214fff36-a74c-45c9-a91c-e07ce2efa996\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:11;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014745, 1789014745),
(102, 'default', '{\"uuid\":\"a2371428-d642-4b3a-93e6-538172766dd3\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:11;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014745, 1789014745),
(103, 'high', '{\"uuid\":\"2b897d3d-efb9-400d-ad34-ac0d7e222060\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014776, 1789014776),
(104, 'high', '{\"uuid\":\"15375436-926e-448d-8969-b54046b3650d\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014776, 1789014776),
(105, 'high', '{\"uuid\":\"e2ebd5ce-ba93-477f-9ca4-c37dff0f554b\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014776, 1789014776),
(106, 'high', '{\"uuid\":\"50cbbcbb-efe5-4ed8-9ea3-b3df2660ef2e\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014792, 1789014792),
(107, 'high', '{\"uuid\":\"74e02f46-a538-4e8b-99c0-22de155696ef\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014792, 1789014792),
(108, 'high', '{\"uuid\":\"afcaf40c-13c0-49c2-aaaf-c194ea4aace9\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014792, 1789014792),
(109, 'high', '{\"uuid\":\"0ce3d5ca-b51d-405c-9490-38f4afb136fd\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:519;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:9;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014822, 1789014822),
(110, 'reports', '{\"uuid\":\"0a827b70-8123-448e-b46e-042f0982fdff\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,600\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoice\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\GenerateInvoice\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"queue\\\";s:7:\\\"reports\\\";}\"}}', 0, NULL, 1789014965, 1789014965),
(111, 'default', '{\"uuid\":\"1048b915-5838-4bf6-80fe-9ee921546725\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:12;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014965, 1789014965);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(112, 'default', '{\"uuid\":\"b1833dd6-7805-4d6c-8706-38f9ed1fa504\",\"displayName\":\"App\\\\Listeners\\\\SendMembershipReceipt\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:35:\\\"App\\\\Listeners\\\\SendMembershipReceipt\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:26:\\\"App\\\\Events\\\\PaymentVerified\\\":2:{s:7:\\\"payment\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Payment\\\";s:2:\\\"id\\\";i:6;s:9:\\\"relations\\\";a:3:{i:0;s:4:\\\"plan\\\";i:1;s:4:\\\"user\\\";i:2;s:23:\\\"user.activeSubscription\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Subscription\\\";s:2:\\\"id\\\";i:12;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014965, 1789014965),
(113, 'high', '{\"uuid\":\"42be0149-a410-4fd7-9c88-9e950cee3508\",\"displayName\":\"App\\\\Events\\\\MessageSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014981, 1789014981),
(114, 'high', '{\"uuid\":\"803acd2c-b184-4fd6-9e4d-59a8d772fc37\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014981, 1789014981),
(115, 'high', '{\"uuid\":\"2581dbbd-902b-4b4a-b23d-f720fe8e3f13\",\"displayName\":\"App\\\\Listeners\\\\SendMessageNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"5,30,120\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:37:\\\"App\\\\Listeners\\\\SendMessageNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:22:\\\"App\\\\Events\\\\MessageSent\\\":3:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"message\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Message\\\";s:2:\\\"id\\\";i:10;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:5;i:1;i:30;i:2;i:120;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789014981, 1789014981),
(116, 'high', '{\"uuid\":\"1612e291-5d8d-4cc1-baf6-cc729c3b8b3f\",\"displayName\":\"App\\\\Events\\\\MessageRead\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":14:{s:5:\\\"event\\\";O:22:\\\"App\\\\Events\\\\MessageRead\\\":4:{s:14:\\\"broadcastQueue\\\";s:4:\\\"high\\\";s:12:\\\"conversation\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:23:\\\"App\\\\Models\\\\Conversation\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"reader\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";i:524;s:9:\\\"relations\\\";a:1:{i:0;s:15:\\\"privacySettings\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:13:\\\"upToMessageId\\\";i:10;}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789015001, 1789015001),
(117, 'default', '{\"uuid\":\"4bc4c40b-3adf-4f4e-b21c-609fa4df7bac\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789205749, 1789205749),
(118, 'default', '{\"uuid\":\"a68c8f23-3eed-4eb2-b92c-642fe7a91ac2\",\"displayName\":\"App\\\\Listeners\\\\SendInterestNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:38:\\\"App\\\\Listeners\\\\SendInterestNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:23:\\\"App\\\\Events\\\\InterestSent\\\":1:{s:8:\\\"interest\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:19:\\\"App\\\\Models\\\\Interest\\\";s:2:\\\"id\\\";i:5;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789205749, 1789205749),
(119, 'default', '{\"uuid\":\"6ac13688-30ff-4735-8b15-117323af7f41\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789205934, 1789205934),
(120, 'default', '{\"uuid\":\"d1e43088-1b6a-4604-9cc9-e6d918b323fd\",\"displayName\":\"App\\\\Listeners\\\\SendViewNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":20:{s:5:\\\"class\\\";s:34:\\\"App\\\\Listeners\\\\SendViewNotification\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:24:\\\"App\\\\Events\\\\ProfileViewed\\\":1:{s:4:\\\"view\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\ProfileView\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:3:{i:0;i:10;i:1;i:60;i:2;i:300;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"}}', 0, NULL, 1789205934, 1789205934);

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
-- Table structure for table `lifestyle_options`
--

CREATE TABLE `lifestyle_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lifestyle_options`
--

INSERT INTO `lifestyle_options` (`id`, `type`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'diet', 'Vegetarian', 'vegetarian', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 'diet', 'Non Vegetarian', 'non-vegetarian', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(3, 'diet', 'Eggetarian', 'eggetarian', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(4, 'diet', 'Vegan', 'vegan', 3, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(5, 'diet', 'Jain Vegetarian', 'jain-vegetarian', 4, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(6, 'diet', 'Occasionally Non-Veg', 'occasionally-non-veg', 5, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(7, 'smoking', 'No', 'no', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(8, 'smoking', 'Occasionally', 'occasionally', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(9, 'smoking', 'Yes', 'yes', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(10, 'drinking', 'No', 'no', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(11, 'drinking', 'Occasionally', 'occasionally', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(12, 'drinking', 'Yes', 'yes', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(13, 'body_type', 'Slim', 'slim', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(14, 'body_type', 'Athletic', 'athletic', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(15, 'body_type', 'Average', 'average', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(16, 'body_type', 'Heavy', 'heavy', 3, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(17, 'complexion', 'Very Fair', 'very-fair', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(18, 'complexion', 'Fair', 'fair', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(19, 'complexion', 'Wheatish', 'wheatish', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(20, 'complexion', 'Wheatish Brown', 'wheatish-brown', 3, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(21, 'complexion', 'Dark', 'dark', 4, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `marital_statuses`
--

CREATE TABLE `marital_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marital_statuses`
--

INSERT INTO `marital_statuses` (`id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Never Married', 'never-married', 0, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 'Divorced', 'divorced', 1, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(3, 'Widowed', 'widowed', 2, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(4, 'Awaiting Divorce', 'awaiting-divorce', 3, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(5, 'Annulled', 'annulled', 4, 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(6, 'Married', 'married', 0, 1, '2026-09-09 14:13:25', '2026-09-09 14:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_one_id` bigint(20) UNSIGNED NOT NULL,
  `user_two_id` bigint(20) UNSIGNED NOT NULL,
  `matched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `conversation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `user_one_id`, `user_two_id`, `matched_at`, `conversation_id`, `status`, `created_at`, `updated_at`) VALUES
(3, 519, 524, '2026-09-10 04:29:25', 3, 'active', '2026-09-10 04:29:25', '2026-09-10 04:29:25');

-- --------------------------------------------------------

--
-- Table structure for table `match_scores`
--

CREATE TABLE `match_scores` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `candidate_id` bigint(20) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breakdown`)),
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matrimony_limits`
--

CREATE TABLE `matrimony_limits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan_type` varchar(40) NOT NULL DEFAULT 'basic',
  `available_user_count` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `balance_count` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matrimony_limits`
--

INSERT INTO `matrimony_limits` (`id`, `plan_type`, `available_user_count`, `used_count`, `balance_count`, `created_at`, `updated_at`) VALUES
(1, 'basic', 100, 12, 88, '2026-09-12 09:09:36', '2026-09-12 11:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `media_uploads`
--

CREATE TABLE `media_uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `disk` varchar(30) NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL,
  `width` smallint(5) UNSIGNED DEFAULT NULL,
  `height` smallint(5) UNSIGNED DEFAULT NULL,
  `collection` varchar(40) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_uploads`
--

INSERT INTO `media_uploads` (`id`, `uploaded_by`, `disk`, `path`, `original_name`, `mime_type`, `size_bytes`, `width`, `height`, `collection`, `created_at`, `updated_at`) VALUES
(1, 1, 'public', 'cms/2026/09/c932acd8-6623-4917-a0c6-c37897d408e4.jpg', '6540131b4a5bce3199933434d822ac5f.jpg', 'image/jpeg', 144690, 736, 1104, 'banners', '2026-09-05 08:30:17', '2026-09-05 08:30:17'),
(2, 1, 'public', 'cms/2026/09/61674a5e-c531-42f3-ab78-240007267f3d.jpg', '57dc4c8c5656c97404a4443ca5b7209f.jpg', 'image/jpeg', 122426, 736, 1104, 'success-stories', '2026-09-05 08:31:11', '2026-09-05 08:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `duration_days` smallint(5) UNSIGNED DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `badge_text` varchar(40) DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`id`, `name`, `slug`, `description`, `price`, `currency`, `duration_days`, `features`, `is_default`, `is_active`, `is_featured`, `badge_text`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Free', 'free', 'Create a profile and start exploring.', 0.00, 'INR', NULL, '{\"interests_per_month\":5,\"profile_views_per_day\":20,\"contact_views_per_month\":0,\"photo_limit\":3,\"can_chat\":false,\"can_view_contact\":false,\"can_see_profile_viewers\":false,\"advanced_search\":false,\"featured_profile\":false,\"priority_matching\":false,\"support_priority\":\"standard\"}', 1, 1, 0, NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40', NULL),
(2, 'Silver', 'silver', 'More reach, more conversations.', 1999.00, 'INR', 90, '{\"interests_per_month\":30,\"profile_views_per_day\":100,\"contact_views_per_month\":10,\"photo_limit\":6,\"can_chat\":true,\"can_view_contact\":true,\"can_see_profile_viewers\":false,\"advanced_search\":true,\"featured_profile\":false,\"priority_matching\":false,\"support_priority\":\"standard\"}', 0, 1, 0, NULL, 2, '2026-08-27 11:57:40', '2026-08-27 11:57:40', NULL),
(3, 'Gold', 'gold', 'Full contact access and priority matches.', 4999.00, 'INR', 90, '{\"interests_per_month\":100,\"profile_views_per_day\":300,\"contact_views_per_month\":50,\"photo_limit\":10,\"can_chat\":true,\"can_view_contact\":true,\"can_see_profile_viewers\":true,\"advanced_search\":true,\"featured_profile\":false,\"priority_matching\":true,\"support_priority\":\"standard\"}', 0, 1, 1, 'Most popular', 3, '2026-08-27 11:57:40', '2026-08-27 11:57:40', NULL),
(4, 'Premium', 'premium', 'Maximum visibility and unlimited communication.', 9999.00, 'INR', 180, '{\"interests_per_month\":-1,\"profile_views_per_day\":-1,\"contact_views_per_month\":-1,\"photo_limit\":20,\"can_chat\":true,\"can_view_contact\":true,\"can_see_profile_viewers\":true,\"advanced_search\":true,\"featured_profile\":true,\"priority_matching\":true,\"support_priority\":\"priority\"}', 0, 1, 0, NULL, 4, '2026-08-27 11:57:40', '2026-08-27 11:57:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_meta`)),
  `status` varchar(20) NOT NULL DEFAULT 'sent',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `client_message_id` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `type`, `body`, `attachment_path`, `attachment_meta`, `status`, `delivered_at`, `read_at`, `client_message_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(8, 3, 524, 'text', 'hi', NULL, NULL, 'read', '2026-09-10 04:33:42', '2026-09-10 04:33:42', '071385fa-e66b-4745-9178-55c976a1ce47', '2026-09-10 04:32:56', '2026-09-10 04:33:42', NULL),
(9, 3, 524, 'image', NULL, 'chat/3/82dac380-8b5c-48a6-99e7-be1aa25dd1e9.webp', '{\"width\":576,\"height\":1280,\"size_bytes\":95046,\"mime_type\":\"image\\/webp\"}', 'read', '2026-09-10 04:33:42', '2026-09-10 04:33:42', '9c8acff0-7f0b-484d-a14a-584930338aeb', '2026-09-10 04:33:12', '2026-09-10 04:33:42', NULL),
(10, 3, 519, 'text', 'hi', NULL, NULL, 'read', '2026-09-10 04:36:41', '2026-09-10 04:36:41', '90be4cb7-d873-4dfb-8f47-5920ffac98dc', '2026-09-10 04:36:21', '2026-09-10 04:36:41', NULL);

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
(1, '2026_01_01_000001_create_roles_and_permissions_tables', 1),
(2, '2026_01_01_000002_create_users_table', 1),
(3, '2026_01_01_000003_create_framework_tables', 1),
(4, '2026_01_01_000100_create_master_data_tables', 1),
(5, '2026_01_01_000101_create_location_tables', 1),
(6, '2026_01_01_000200_create_user_profiles_table', 1),
(7, '2026_01_01_000201_create_user_preferences_table', 1),
(8, '2026_01_01_000202_create_profile_photos_and_documents_tables', 1),
(9, '2026_01_01_000300_create_interaction_tables', 1),
(10, '2026_01_01_000400_create_chat_tables', 1),
(11, '2026_01_01_000500_create_commerce_tables', 1),
(12, '2026_01_01_000600_create_system_tables', 1),
(13, '2026_01_01_000700_create_media_uploads_table', 1),
(14, '2026_09_12_000001_create_matrimony_limits_table', 2),
(15, '2026_09_12_000002_create_user_jadhagams_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `mother_tongues`
--

CREATE TABLE `mother_tongues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mother_tongues`
--

INSERT INTO `mother_tongues` (`id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Hindi', 'hindi', 0, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(2, 'Bengali', 'bengali', 1, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(3, 'Marathi', 'marathi', 2, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(4, 'Telugu', 'telugu', 3, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(5, 'Tamil', 'tamil', 4, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(6, 'Gujarati', 'gujarati', 5, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(7, 'Urdu', 'urdu', 6, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(8, 'Kannada', 'kannada', 7, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(9, 'Odia', 'odia', 8, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(10, 'Malayalam', 'malayalam', 9, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(11, 'Punjabi', 'punjabi', 10, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(12, 'Assamese', 'assamese', 11, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(13, 'Maithili', 'maithili', 12, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(14, 'Bhojpuri', 'bhojpuri', 13, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(15, 'Santali', 'santali', 14, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(16, 'Kashmiri', 'kashmiri', 15, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(17, 'Nepali', 'nepali', 16, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(18, 'Sindhi', 'sindhi', 17, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(19, 'Konkani', 'konkani', 18, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(20, 'Dogri', 'dogri', 19, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(21, 'Manipuri', 'manipuri', 20, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(22, 'Bodo', 'bodo', 21, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(23, 'Sanskrit', 'sanskrit', 22, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(24, 'Tulu', 'tulu', 23, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(25, 'Kodava', 'kodava', 24, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(26, 'Rajasthani', 'rajasthani', 25, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(27, 'Haryanvi', 'haryanvi', 26, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(28, 'Chhattisgarhi', 'chhattisgarhi', 27, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(29, 'Magahi', 'magahi', 28, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(30, 'Awadhi', 'awadhi', 29, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(31, 'Marwari', 'marwari', 30, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(32, 'Kutchi', 'kutchi', 31, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(33, 'Garhwali', 'garhwali', 32, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(34, 'Kumaoni', 'kumaoni', 33, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(35, 'Khasi', 'khasi', 34, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(36, 'Mizo', 'mizo', 35, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(37, 'Ladakhi', 'ladakhi', 36, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(38, 'Tibetan', 'tibetan', 37, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(39, 'English', 'english', 38, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(40, 'Other', 'other', 39, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `channel` varchar(20) NOT NULL DEFAULT 'push',
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `data`, `channel`, `read_at`, `sent_at`, `failed_at`, `failure_reason`, `created_at`, `updated_at`) VALUES
(13, 519, 'photo_approved', 'Your photo is live', 'Your photo has been approved and is now visible on your profile.', '{\"photo_id\":9,\"route\":\"\\/profile\\/photos\"}', 'in_app', NULL, NULL, NULL, NULL, '2026-09-10 04:26:12', '2026-09-10 04:26:12'),
(14, 524, 'photo_approved', 'Your photo is live', 'Your photo has been approved and is now visible on your profile.', '{\"photo_id\":10,\"route\":\"\\/profile\\/photos\"}', 'in_app', NULL, NULL, NULL, NULL, '2026-09-10 04:28:46', '2026-09-10 04:28:46');

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`types`)),
  `quiet_hours_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `user_id`, `push_enabled`, `email_enabled`, `sms_enabled`, `types`, `quiet_hours_enabled`, `quiet_hours_start`, `quiet_hours_end`, `created_at`, `updated_at`) VALUES
(7, 529, 1, 1, 0, NULL, 0, NULL, NULL, '2026-09-10 06:33:13', '2026-09-10 06:33:13'),
(8, 530, 1, 1, 0, NULL, 0, NULL, NULL, '2026-09-12 10:47:13', '2026-09-12 10:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `occupations`
--

CREATE TABLE `occupations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `occupations`
--

INSERT INTO `occupations` (`id`, `name`, `slug`, `sort_order`, `is_active`, `category`, `created_at`, `updated_at`) VALUES
(1, 'Software Engineer', 'software-engineer', 0, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(2, 'Software Architect', 'software-architect', 1, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(3, 'Data Scientist', 'data-scientist', 2, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(4, 'DevOps Engineer', 'devops-engineer', 3, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(5, 'QA Engineer', 'qa-engineer', 4, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(6, 'UI/UX Designer', 'uiux-designer', 5, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(7, 'Product Manager', 'product-manager', 6, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(8, 'IT Consultant', 'it-consultant', 7, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(9, 'Cyber Security Analyst', 'cyber-security-analyst', 8, 1, 'IT & Software', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(10, 'Doctor', 'doctor', 9, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(11, 'Surgeon', 'surgeon', 10, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(12, 'Dentist', 'dentist', 11, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(13, 'Nurse', 'nurse', 12, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(14, 'Physiotherapist', 'physiotherapist', 13, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(15, 'Pharmacist', 'pharmacist', 14, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(16, 'Veterinarian', 'veterinarian', 15, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(17, 'Medical Technician', 'medical-technician', 16, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(18, 'Ayurvedic Practitioner', 'ayurvedic-practitioner', 17, 1, 'Medical', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(19, 'Civil Engineer', 'civil-engineer', 18, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(20, 'Mechanical Engineer', 'mechanical-engineer', 19, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(21, 'Electrical Engineer', 'electrical-engineer', 20, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(22, 'Electronics Engineer', 'electronics-engineer', 21, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(23, 'Chemical Engineer', 'chemical-engineer', 22, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(24, 'Aerospace Engineer', 'aerospace-engineer', 23, 1, 'Engineering', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(25, 'Chartered Accountant', 'chartered-accountant', 24, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(26, 'Company Secretary', 'company-secretary', 25, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(27, 'Investment Banker', 'investment-banker', 26, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(28, 'Financial Analyst', 'financial-analyst', 27, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(29, 'Auditor', 'auditor', 28, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(30, 'Accountant', 'accountant', 29, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(31, 'Actuary', 'actuary', 30, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(32, 'Insurance Professional', 'insurance-professional', 31, 1, 'Finance', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(33, 'IAS / IPS / IFS', 'ias-ips-ifs', 32, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(34, 'Civil Services', 'civil-services', 33, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(35, 'Defence Services', 'defence-services', 34, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(36, 'Police Services', 'police-services', 35, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(37, 'Railway Employee', 'railway-employee', 36, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(38, 'Bank Officer (PSU)', 'bank-officer-psu', 37, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(39, 'Government Teacher', 'government-teacher', 38, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(40, 'Clerk', 'clerk', 39, 1, 'Government', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(41, 'Teacher', 'teacher', 40, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(42, 'Professor', 'professor', 41, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(43, 'Lecturer', 'lecturer', 42, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(44, 'Research Scholar', 'research-scholar', 43, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(45, 'Principal', 'principal', 44, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(46, 'Education Consultant', 'education-consultant', 45, 1, 'Education', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(47, 'Lawyer', 'lawyer', 46, 1, 'Legal', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(48, 'Advocate', 'advocate', 47, 1, 'Legal', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(49, 'Legal Consultant', 'legal-consultant', 48, 1, 'Legal', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(50, 'Judge', 'judge', 49, 1, 'Legal', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(51, 'Company Legal Counsel', 'company-legal-counsel', 50, 1, 'Legal', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(52, 'Business Owner', 'business-owner', 51, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(53, 'Entrepreneur', 'entrepreneur', 52, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(54, 'Trader', 'trader', 53, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(55, 'Contractor', 'contractor', 54, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(56, 'Real Estate Professional', 'real-estate-professional', 55, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(57, 'Agriculturist', 'agriculturist', 56, 1, 'Business', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(58, 'Marketing Professional', 'marketing-professional', 57, 1, 'Corporate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(59, 'Sales Professional', 'sales-professional', 58, 1, 'Corporate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(60, 'HR Professional', 'hr-professional', 59, 1, 'Corporate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(61, 'Operations Manager', 'operations-manager', 60, 1, 'Corporate', '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(62, 'Business Analyst', 'business-analyst', 61, 1, 'Corporate', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(63, 'Supply Chain Professional', 'supply-chain-professional', 62, 1, 'Corporate', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(64, 'Customer Support', 'customer-support', 63, 1, 'Corporate', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(65, 'Architect', 'architect', 64, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(66, 'Interior Designer', 'interior-designer', 65, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(67, 'Graphic Designer', 'graphic-designer', 66, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(68, 'Journalist', 'journalist', 67, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(69, 'Writer', 'writer', 68, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(70, 'Photographer', 'photographer', 69, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(71, 'Film Professional', 'film-professional', 70, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(72, 'Artist', 'artist', 71, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(73, 'Musician', 'musician', 72, 1, 'Creative', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(74, 'Airline Professional', 'airline-professional', 73, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(75, 'Pilot', 'pilot', 74, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(76, 'Cabin Crew', 'cabin-crew', 75, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(77, 'Hotel Management', 'hotel-management', 76, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(78, 'Chef', 'chef', 77, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(79, 'Merchant Navy', 'merchant-navy', 78, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(80, 'Fitness Trainer', 'fitness-trainer', 79, 1, 'Service', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(81, 'Student', 'student', 80, 1, 'Other', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(82, 'Not Working', 'not-working', 81, 1, 'Other', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(83, 'Retired', 'retired', 82, 1, 'Other', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(84, 'Homemaker', 'homemaker', 83, 1, 'Other', '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(85, 'Other', 'other', 84, 1, 'Other', '2026-08-27 11:57:35', '2026-08-27 11:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `channel` varchar(10) NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consumed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `identifier`, `channel`, `purpose`, `code_hash`, `attempts`, `expires_at`, `consumed_at`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, '+919042985433', 'sms', 'registration', '$2y$12$hM3k66tj9b4NYwY/2dEelO4He21xga.1D.l1r0u3hXedKQTM.AQCy', 0, '2026-08-28 05:37:27', '2026-08-28 05:37:27', '127.0.0.1', '2026-08-28 05:36:05', '2026-08-28 05:37:27'),
(2, '+919042985433', 'sms', 'registration', '$2y$12$wbOY/ErFQyotv8AMhjOPVeQ5hQZP99I2gN5aTY0I6if/uEUMP4RFK', 0, '2026-08-28 05:42:27', NULL, '127.0.0.1', '2026-08-28 05:37:27', '2026-08-28 05:37:27'),
(3, '+919042985433', 'sms', 'registration', '$2y$12$M/GSbv7xhMuN1AEmoijTje2927W/BZhKPWwL/CCX5h6vuJDsOYSmy', 0, '2026-08-28 06:09:21', '2026-08-28 06:09:21', '127.0.0.1', '2026-08-28 06:09:19', '2026-08-28 06:09:21'),
(4, '+919042985433', 'sms', 'registration', '$2y$12$iSLwbk2yapGK0MFf102fWecBfs1aRBq/i0SAbKCGeDf3aaKdm3Eza', 0, '2026-08-28 06:14:21', NULL, '127.0.0.1', '2026-08-28 06:09:21', '2026-08-28 06:09:21'),
(5, '+919000009601', 'sms', 'registration', '$2y$12$.DhWFmk7juC8Vyg43JV1AucmaggrToj3mwVtDavO2/d6tcV3ifRle', 0, '2026-09-05 06:11:20', NULL, '127.0.0.1', '2026-09-05 06:06:20', '2026-09-05 06:06:20'),
(6, '+918489859256', 'sms', 'registration', '$2y$12$lm4zPWlU1p14j0jXx60PrOL05UIAznQN0km7j/bd.U63HOSutqMe2', 0, '2026-09-07 04:41:50', '2026-09-07 04:41:50', '127.0.0.1', '2026-09-07 04:41:26', '2026-09-07 04:41:50'),
(7, '+918489859256', 'sms', 'registration', '$2y$12$A3YR5PgFo/u6mb.9mhIlDObbjNpYYIBjxkwZLoPQw3fyFdSUu3Wsu', 0, '2026-09-07 05:01:53', '2026-09-07 05:01:53', '127.0.0.1', '2026-09-07 05:01:35', '2026-09-07 05:01:53'),
(8, '+919876543210', 'sms', 'test', '$2y$12$gnyfcGh31JIpUZ5guTkHJOXc102OgZEoneh67HYKi6hVoofeA6ivu', 0, '2026-09-07 05:10:12', '2026-09-07 05:10:12', NULL, '2026-09-07 05:10:12', '2026-09-07 05:10:12'),
(9, '+919876543210', 'sms', 'test', '$2y$12$NkIcpVAqhwbwy0l92EKotuV/CZ44TecOV0xTffwx1UEIW82IH0ywe', 0, '2026-09-07 05:15:13', NULL, NULL, '2026-09-07 05:10:13', '2026-09-07 05:10:13'),
(10, '+918489859256', 'sms', 'registration', '$2y$12$Jc1tJqrzyihWdaMou4Mwe.3S/KFsjVQNJgQqiaYOzz8vLtSLlshW2', 0, '2026-09-07 05:14:43', '2026-09-07 05:14:43', '127.0.0.1', '2026-09-07 05:14:23', '2026-09-07 05:14:43'),
(11, '+918489859256', 'sms', 'registration', '$2y$12$rEs4YeU6Yi2A.f9qs/wxGuq2U8AsP2Sg7RsbLv6Do.fXEQC/lymSa', 0, '2026-09-07 09:17:07', '2026-09-07 09:17:07', '127.0.0.1', '2026-09-07 09:16:47', '2026-09-07 09:17:07'),
(12, '+918489859256', 'sms', 'registration', '$2y$12$D3Cd9UA/MidFFlcN/UIhfu/s75xLP7x9h6cJZUGN1/f9ztJTHI.xq', 0, '2026-09-10 06:33:48', '2026-09-10 06:33:48', '127.0.0.1', '2026-09-10 06:33:14', '2026-09-10 06:33:48'),
(13, '+919000000001', 'sms', 'password_reset', '$2y$12$zZ34UCXudw3ozmdSUit5uup8s53gv49AqCPV9dVOP1oCL1R3rczeO', 0, '2026-09-12 09:59:07', NULL, '127.0.0.1', '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(14, '+919000000001', 'sms', 'password_reset', '$2y$12$fosx2IB.GBrNGn4dPGsk8.9LuTBykMUKYuChGmUqjRQb6jTIJVs2C', 0, '2026-09-12 10:05:36', '2026-09-12 10:05:36', '127.0.0.1', '2026-09-12 10:05:35', '2026-09-12 10:05:36'),
(15, '+919000000001', 'sms', 'password_reset', '$2y$12$SrfHh9TeiR7joIgLD1E1ye595/Q0taPCEmSzRVyRHyAmpp5ngx4li', 0, '2026-09-12 10:15:37', '2026-09-12 10:05:37', '127.0.0.1', '2026-09-12 10:05:37', '2026-09-12 10:05:37'),
(16, '+918489859256', 'sms', 'registration', '$2y$12$R6Fo4cFKG.euleZ9CMB2mu20TQ1zS2ECEh83ySbKPovnHh/sm1W0a', 0, '2026-09-12 10:47:30', '2026-09-12 10:47:30', '127.0.0.1', '2026-09-12 10:47:13', '2026-09-12 10:47:30'),
(17, '+918489859256', 'sms', 'password_reset', '$2y$12$Q1YMI.72/uDP.rVTaRon6O1KtaHNUTs3dhESn6BUdFnTZUGntZhT2', 0, '2026-09-12 10:49:04', '2026-09-12 10:49:04', '127.0.0.1', '2026-09-12 10:48:16', '2026-09-12 10:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `membership_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `razorpay_order_id` varchar(255) NOT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `status` varchar(20) NOT NULL DEFAULT 'created',
  `method` varchar(30) DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `refund_id` varchar(255) DEFAULT NULL,
  `refunded_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes`)),
  `verified_at` timestamp NULL DEFAULT NULL,
  `invoice_number` varchar(30) DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `membership_plan_id`, `subscription_id`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `amount`, `currency`, `status`, `method`, `failure_reason`, `refund_id`, `refunded_amount`, `refund_reason`, `notes`, `verified_at`, `invoice_number`, `invoice_path`, `created_at`, `updated_at`) VALUES
(5, 524, 3, 11, 'order_cs_wBjPbTlXZIijwPJt', 'pay_TaCePFC0PbFjxP', NULL, 4999.00, 'INR', 'paid', 'razorpay', NULL, NULL, NULL, NULL, '{\"plan\":\"Gold\",\"checkout_mode\":\"standard_client\"}', '2026-09-10 04:32:25', 'INV-2026-000005', NULL, '2026-09-10 04:29:50', '2026-09-10 04:32:25'),
(6, 519, 2, 12, 'order_cs_RtIWFhmzBmBFLhdm', 'pay_TaCiUqFSg8J3i0', NULL, 1999.00, 'INR', 'paid', 'razorpay', NULL, NULL, NULL, NULL, '{\"plan\":\"Silver\",\"checkout_mode\":\"standard_client\"}', '2026-09-10 04:36:05', 'INV-2026-000006', NULL, '2026-09-10 04:35:01', '2026-09-10 04:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `group` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `group`, `label`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'dashboard', 'Dashboard view', '2026-08-27 11:57:32', '2026-08-27 11:57:32'),
(2, 'users.view', 'users', 'Users view', '2026-08-27 11:57:32', '2026-08-27 11:57:32'),
(3, 'users.edit', 'users', 'Users edit', '2026-08-27 11:57:32', '2026-08-27 11:57:32'),
(4, 'users.suspend', 'users', 'Users suspend', '2026-08-27 11:57:32', '2026-08-27 11:57:32'),
(5, 'users.block', 'users', 'Users block', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(6, 'users.verify', 'users', 'Users verify', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(7, 'users.delete', 'users', 'Users delete', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(8, 'users.export', 'users', 'Users export', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(9, 'profiles.view', 'profiles', 'Profiles view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(10, 'profiles.moderate', 'profiles', 'Profiles moderate', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(11, 'photos.moderate', 'photos', 'Photos moderate', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(12, 'verification.review', 'verification', 'Verification review', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(13, 'interests.view', 'interests', 'Interests view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(14, 'memberships.view', 'memberships', 'Memberships view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(15, 'memberships.manage', 'memberships', 'Memberships manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(16, 'subscriptions.view', 'subscriptions', 'Subscriptions view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(17, 'subscriptions.manage', 'subscriptions', 'Subscriptions manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(18, 'payments.view', 'payments', 'Payments view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(19, 'payments.refund', 'payments', 'Payments refund', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(20, 'payments.export', 'payments', 'Payments export', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(21, 'reports.view', 'reports', 'Reports view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(22, 'reports.resolve', 'reports', 'Reports resolve', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(23, 'chat.moderate', 'chat', 'Chat moderate', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(24, 'notifications.view', 'notifications', 'Notifications view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(25, 'notifications.broadcast', 'notifications', 'Notifications broadcast', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(26, 'cms.view', 'cms', 'Cms view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(27, 'cms.manage', 'cms', 'Cms manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(28, 'enquiries.view', 'enquiries', 'Enquiries view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(29, 'enquiries.respond', 'enquiries', 'Enquiries respond', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(30, 'master_data.view', 'master_data', 'Master data view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(31, 'master_data.manage', 'master_data', 'Master data manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(32, 'locations.view', 'locations', 'Locations view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(33, 'locations.manage', 'locations', 'Locations manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(34, 'staff.view', 'staff', 'Staff view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(35, 'staff.manage', 'staff', 'Staff manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(36, 'settings.view', 'settings', 'Settings view', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(37, 'settings.manage', 'settings', 'Settings manage', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(38, 'settings.audit', 'settings', 'Settings audit', '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(39, 'analytics.view', 'analytics', 'Analytics view', '2026-08-27 11:57:33', '2026-08-27 11:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(4, 3),
(5, 1),
(5, 2),
(5, 3),
(6, 1),
(6, 2),
(6, 3),
(7, 1),
(7, 2),
(8, 1),
(8, 2),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(10, 1),
(10, 2),
(10, 3),
(11, 1),
(11, 2),
(11, 3),
(12, 1),
(12, 2),
(12, 3),
(13, 1),
(13, 2),
(14, 1),
(14, 2),
(15, 1),
(15, 2),
(16, 1),
(16, 2),
(16, 4),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(18, 4),
(19, 1),
(19, 2),
(20, 1),
(20, 2),
(21, 1),
(21, 2),
(21, 3),
(21, 4),
(22, 1),
(22, 2),
(22, 3),
(22, 4),
(23, 1),
(23, 2),
(23, 3),
(24, 1),
(24, 2),
(25, 1),
(25, 2),
(26, 1),
(26, 2),
(27, 1),
(27, 2),
(28, 1),
(28, 2),
(28, 4),
(29, 1),
(29, 2),
(29, 4),
(30, 1),
(30, 2),
(31, 1),
(31, 2),
(32, 1),
(32, 2),
(33, 1),
(33, 2),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(39, 2);

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 505, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '1b4f48c73f232b9511705dd1b9c3da093ecd4ec1538863dc73afbd9a5afb3055', '[\"user\"]', NULL, NULL, '2026-08-28 06:28:27', '2026-08-28 06:28:27'),
(2, 'App\\Models\\User', 505, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '5a93c6a511e3a1c8275a40bbca19c0c0db3fc8547921f062d5fb7e14af555599', '[\"user\"]', NULL, NULL, '2026-09-05 04:26:58', '2026-09-05 04:26:58'),
(3, 'App\\Models\\User', 506, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'a2a773439bdf739477413ba24e570c369bb23dc683aef7fedfc8912a37d05f18', '[\"user\"]', NULL, NULL, '2026-09-05 06:10:22', '2026-09-05 06:10:22'),
(4, 'App\\Models\\User', 505, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'aac93c618003ea475758952783fc615400b59ff293b20368a43704ce244542ae', '[\"user\"]', NULL, NULL, '2026-09-05 07:05:11', '2026-09-05 07:05:11'),
(5, 'App\\Models\\User', 508, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '87b231075c8ca66876c42e6fa5c30eb5ac16cfab32fef939ee7c238253af2bee', '[\"user\"]', NULL, NULL, '2026-09-05 07:08:57', '2026-09-05 07:08:57'),
(6, 'App\\Models\\User', 505, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'd82716bcc73573c6aa767e747c1573b3d0170c6be90f1d360274bb618af8bdc9', '[\"user\"]', NULL, NULL, '2026-09-05 07:14:01', '2026-09-05 07:14:01'),
(7, 'App\\Models\\User', 506, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'cab69bcfda88731e66f19590d6818b54277ac3d26d1d94e97102b8f9018d346d', '[\"user\"]', NULL, NULL, '2026-09-05 07:22:33', '2026-09-05 07:22:33'),
(8, 'App\\Models\\User', 505, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'a4807bdc439f6ac5e8a145c904a3af94459b1b759402cae40b35b276723abaed', '[\"user\"]', NULL, NULL, '2026-09-05 07:32:20', '2026-09-05 07:32:20'),
(9, 'App\\Models\\User', 507, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '141a7fb0d62473772722bb0457eea0b6b4d60a10effe0ac7bef1a9804a2eeb15', '[\"user\"]', NULL, NULL, '2026-09-08 04:25:00', '2026-09-08 04:25:00'),
(10, 'App\\Models\\User', 516, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'bf55b903dc206645e39caa1372efc2d9526541be20c5b97de21d9a25c7b80e75', '[\"user\"]', NULL, NULL, '2026-09-08 04:35:00', '2026-09-08 04:35:00'),
(11, 'App\\Models\\User', 516, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '9d5dfec8c45a784178cfbcbfb0bde331d15a004ff27b5ae67bb93e20b9bc6364', '[\"user\"]', NULL, NULL, '2026-09-08 05:36:13', '2026-09-08 05:36:13'),
(12, 'App\\Models\\User', 519, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '7d54348d45cb4094fc8192da9b4d25c52d6a2aa4192c4da071e1a7e2db33aa2a', '[\"user\"]', NULL, NULL, '2026-09-09 14:22:05', '2026-09-09 14:22:05'),
(13, 'App\\Models\\User', 519, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '4669e823183e20da5e90e58fae82170773e0e9f8bf2a67f9e8f9b063fb9f4188', '[\"user\"]', NULL, NULL, '2026-09-10 04:22:20', '2026-09-10 04:22:20'),
(14, 'App\\Models\\User', 524, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '06f0c2d6cb285a61c16ed41afe444a7a9dd36954e4262ac4ecd939db44cc6fa1', '[\"user\"]', NULL, NULL, '2026-09-10 04:27:49', '2026-09-10 04:27:49'),
(15, 'App\\Models\\User', 519, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'be07bc99c8bf1009f4200976ba389c0a88d1a60426b6a800d9314067ab4f5b87', '[\"user\"]', NULL, NULL, '2026-09-12 09:30:53', '2026-09-12 09:30:53'),
(16, 'App\\Models\\User', 524, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'eddaac28d1fb0a18a0e0c4785659a15206a6fbed569d5127ef694ff8054a9c01', '[\"user\"]', NULL, NULL, '2026-09-12 09:38:32', '2026-09-12 09:38:32'),
(17, 'App\\Models\\User', 530, 'web Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'fc5ac265ce6fd3552f883c812817c48ef8de0a1eadb4371d66141a5beaf92c5c', '[\"user\"]', NULL, NULL, '2026-09-12 10:49:28', '2026-09-12 10:49:28');

-- --------------------------------------------------------

--
-- Table structure for table `privacy_settings`
--

CREATE TABLE `privacy_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `profile_visibility` varchar(20) NOT NULL DEFAULT 'members',
  `photo_visibility` varchar(20) NOT NULL DEFAULT 'members',
  `contact_visibility` varchar(20) NOT NULL DEFAULT 'premium',
  `show_online_status` tinyint(1) NOT NULL DEFAULT 1,
  `appear_in_search` tinyint(1) NOT NULL DEFAULT 1,
  `show_profile_views` tinyint(1) NOT NULL DEFAULT 1,
  `who_can_message` varchar(20) NOT NULL DEFAULT 'matches_only',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `privacy_settings`
--

INSERT INTO `privacy_settings` (`id`, `user_id`, `profile_visibility`, `photo_visibility`, `contact_visibility`, `show_online_status`, `appear_in_search`, `show_profile_views`, `who_can_message`, `created_at`, `updated_at`) VALUES
(511, 519, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(512, 520, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(513, 521, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(514, 522, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(515, 523, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(516, 524, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(517, 525, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(518, 526, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(519, 527, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(520, 528, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(521, 529, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-10 06:33:13', '2026-09-10 06:33:13'),
(522, 530, 'members', 'members', 'premium', 1, 1, 1, 'matches_only', '2026-09-12 10:47:13', '2026-09-12 10:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `profile_photos`
--

CREATE TABLE `profile_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `blurred_path` varchar(255) DEFAULT NULL,
  `disk` varchar(30) NOT NULL DEFAULT 'public',
  `mime_type` varchar(60) NOT NULL,
  `size_bytes` int(10) UNSIGNED NOT NULL,
  `width` smallint(5) UNSIGNED DEFAULT NULL,
  `height` smallint(5) UNSIGNED DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `approval_status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `moderated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_photos`
--

INSERT INTO `profile_photos` (`id`, `user_id`, `path`, `thumbnail_path`, `blurred_path`, `disk`, `mime_type`, `size_bytes`, `width`, `height`, `is_primary`, `sort_order`, `approval_status`, `rejection_reason`, `moderated_by`, `moderated_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(9, 519, 'profiles/519/originals/229b0f70-a406-45e9-8129-d240a11bffc9.jpg', NULL, NULL, 'public', 'image/jpeg', 45543, NULL, NULL, 1, 1, 'approved', NULL, 1, '2026-09-10 04:26:12', '2026-09-10 04:25:54', '2026-09-10 04:26:12', NULL),
(10, 524, 'profiles/524/originals/46aa9f6d-a032-4f29-b623-769a134cee1a.jpg', NULL, NULL, 'public', 'image/jpeg', 32089, NULL, NULL, 1, 1, 'approved', NULL, 1, '2026-09-10 04:28:46', '2026-09-10 04:28:22', '2026-09-10 04:28:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profile_views`
--

CREATE TABLE `profile_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `viewer_id` bigint(20) UNSIGNED NOT NULL,
  `viewed_id` bigint(20) UNSIGNED NOT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'direct',
  `ip_hash` varchar(64) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_views`
--

INSERT INTO `profile_views` (`id`, `viewer_id`, `viewed_id`, `source`, `ip_hash`, `viewed_at`, `created_at`, `updated_at`) VALUES
(8, 524, 519, 'direct', '12ca17b49af2289436f303e0166030a21e525d266e209267433801a8fd4071a0', '2026-09-12 09:38:54', '2026-09-12 09:38:54', '2026-09-12 09:38:54');

-- --------------------------------------------------------

--
-- Table structure for table `religions`
--

CREATE TABLE `religions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `religions`
--

INSERT INTO `religions` (`id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Hindu', 'hindu', 0, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(2, 'Muslim', 'muslim', 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(3, 'Christian', 'christian', 2, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(4, 'Sikh', 'sikh', 3, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(5, 'Jain', 'jain', 4, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(6, 'Buddhist', 'buddhist', 5, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(7, 'Parsi', 'parsi', 6, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(8, 'Jewish', 'jewish', 7, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(9, 'Inter-Religion', 'inter-religion', 8, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34'),
(10, 'No Religion', 'no-religion', 9, 1, '2026-08-27 11:57:34', '2026-08-27 11:57:34');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reporter_id` bigint(20) UNSIGNED NOT NULL,
  `reported_user_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `evidence_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_paths`)),
  `evidence_message_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_message_ids`)),
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `action_taken` varchar(20) DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `label`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Admin', NULL, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(2, 'admin', 'Admin', NULL, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(3, 'moderator', 'Moderator', NULL, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(4, 'support', 'Support Staff', NULL, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(5, 'user', 'Member', NULL, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters`)),
  `alert_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_alerted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('aejVd790RcmK4k8GNp52Y5KcvnoMMW3FIEECNej5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicjhlcElqUjFoNmdvZkhtNzZTUk9sREJIeHFlZGZ0WTIzT3JPR3RlRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893345),
('biAZoBxIcCGlztyM9QBlqEOO45ZhXjKoe3xcXM7T', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZWZ0ZkEzRGhXb0FWaDR4UjFJTG1UZkxMcEZrQnFSMFlqVmRKcUVNWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yZWdpc3RlciI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787832247),
('bjhMAVDG2nAEQ92vxbYiFHd0KeCR9axlwnEI3Ygk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiV0c4QVV1Tk1wZUF3Z2tXN2pVMHYxaHE0VFNSSGlXVUpyVzB2SEVCcSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893119),
('Cu0Qd8ElUygdwNIJKrsBodx8iNbCKPCneXfWKSs1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSGFqRW5Xd2M0ZXlpd2NQdDJMdFZ3cGZPdXFDWnRUamsxUERBZWtYNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893082),
('dtqtCzrPQFVyT75cQeNhrG59woIChfNcCc8e3Qww', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSUVNeU9YUEVlVHVkSEN6ejdnemxwMlhzZlZjVUpwQVJ6SVhORVVTZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787832260),
('ex2p487z8gdomthmqhnPcCY2l5eRHTcHis7iWDo6', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOWlmaWVNU0x0cWZZbDhIck9kVWNsbk05Z2JDSGE3Z1VSSlM1eTRvYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787832279),
('fNOxdKpA2ZHPYneiwqBDZvd3rB4YyHvNuXqZhgOL', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiODk0RkFvbUlacFY0Sm9Bc1R4S2pJajhxQVRNWDJSVjZZTkhvYmJGRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893280),
('FzJCHomYU8LQIDVc4nQ7ROJ8TZHcIt49d5AjTQW2', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNm5Ba2FjbHNaYmp2ZmRUSmVJRnZFek9hZGFkaHZmRnpTalBNU2lIWSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893344),
('ijC4Ub54c6aQks6CUOZT9baEmAbIql3YbuXqNKYR', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVm1wdGFJb0dyN1plQmxHOVdreEtwaHh6bXU1eGxGVWVyU2VPZWdyNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wbGFucyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787832257),
('KSoXUhW1N2hzlUEpFauLp6igxorgyvjEIbO82WCE', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiTGVuODJ3ZUd5ak1aZHpHZDFuZFJkTTMzT2k4ZUdTQTl1dDBGdThxNiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787893064),
('lErDBGbg8E3PoMHmBnSeHuXQpq6Gjr4W9kspN1PR', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiS3pWaVVSdUZ1TWlMQkJCT1VXV0xxNjZJRmhodTlRTzQ4Q3dhQWpKaSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYWRtaW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1787833963),
('Nha5SzAD15j2Pw1iyc3Qea9KFVyPccqn87UNPWKi', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiWlFRdWtUUUtEOTZxaVpzREZ2cHo2V0NOT1JZZE5IT2VLQXZOMHZubiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787893454),
('Noh7Eh1e812JobqUIy96b5MNXkQof2BDn97FypDp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidUdNamdoS0tGOTE3d1Rqa3hXbEhFNzJ5b2ZpckZmeDVIaXFKV1piQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787892486),
('PTp84Fpps244AveqEUhDLki4ywcyx7vL2nw0Rg0j', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTDA2VjlITVo2SnFPWTdYaWtaRlo3TDNZOUNtdU1xcUJCcUszVDhmeiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787892965),
('R1HY2C7s6EljPLgfjuU4STnnrZJoc1LVk4H9QgPu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoidE1FN1d6elp0azk5V09mdEhVUVNRN2JzZnU5cmY4YUxZbWlsSnF4YSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787893102),
('RP91wuwBkI1r0NWci7xJSVMJx6JK4p6nmPp3U0v4', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiZVFpQW1DNjh6Vm5kQ3M0OWJ6WFI3Q1N6c0tDWlBIM3FMMlFKUVhGYiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787893326),
('TUNrcuCm1hrQV9qnoJ3NZfy7xviYRCAo2L6s1WCp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUlNXNTNQTllEQTdtTllSSDBKZXNtRGZTMU9CYml4RU1Fd2NxSEhJTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9icm93c2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1787832267),
('uEUgMGEepsyxcjRdliiQWXDQmKS8bqLCpSUkKwS8', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYVFkUW84ZUxiZkVhQzdrcTM0OEc2S1NhcjRkSWlWODB1aEhOSmNMUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787892913),
('VDZS8C6dvutdUqF1fLa5YkqvR2m98P9NUKdS3Ckc', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ3UzRW5lSFI1YVF6NFRNMkE1NnNGRXpGZFUxWGV6QXdmWU14YUIwWSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893080),
('VsRg2gxxld0vfwh1q8XV2tGgJFQsjlgVfrJ7nBPM', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiYUw5dmtuc3l5amxqRlJGMHpBMzcxUGdNbWF0RUpiV1cyYWlrU1lVaSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787893142),
('wLqtl7wYPqa1QBt1qipvFEyXXQwQcotiZiJtfUlO', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibmVoeXJaS1Zqb0ROcmZHR3hFcVJuNXBpbXlQOWU2a2g2TFc1M3pMNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787832238),
('Wn3SvmQkSXZfQwv2NNVzu1mgnh2ZKPfClNhEvQkC', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQVcwTFpROVJzMmdHUjJsQ3U4MlRwb0F0UE9UaWpncG9YdkZ4dDJjVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9icm93c2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1787832253),
('WNKwMbavlobnYlHkjbUOhqUD4GMxhMTBlIKrXsmY', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoianViaDY5NVlqbU5RUHI3VEF6ZFRpeWptV2VFS244eG9hbGlBbXZPcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1787893120),
('zgaN8NrbmkgGMtdTZsCmXc1VYSp0BOzNug2UkZeu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoicFZ6eWhJWmtWMGpJQVZtZEdWc1B1NktLY2c1bUN5WGJDdVU4UHl3RyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787892956);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Matrimony', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(2, 'site_tagline', 'Find someone who fits your life', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(3, 'support_email', 'support@matrimony.test', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(4, 'support_phone', '+919000000000', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(5, 'office_address', 'Coimbatore, Tamil Nadu, India', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(6, 'maintenance_message', '', 'string', 'general', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(7, 'facebook_url', '', 'string', 'social', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(8, 'instagram_url', '', 'string', 'social', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(9, 'youtube_url', '', 'string', 'social', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(10, 'whatsapp_number', '', 'string', 'social', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(11, 'otp_required', 'true', 'bool', 'registration', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(12, 'min_age_male', '21', 'int', 'registration', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(13, 'min_age_female', '18', 'int', 'registration', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(14, 'allow_email_signup', 'true', 'bool', 'registration', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(15, 'min_completion_to_browse', '40', 'int', 'registration', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(16, 'profile_approval_required', 'true', 'bool', 'moderation', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(17, 'photo_moderation_required', 'true', 'bool', 'moderation', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(18, 'auto_approve_after_hours', '24', 'int', 'moderation', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(19, 'free_daily_profile_views', '10', 'int', 'limits', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(20, 'max_photos_free', '3', 'int', 'limits', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(21, 'search_results_cap', '500', 'int', 'limits', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(22, 'currency', 'INR', 'string', 'payments', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(23, 'gst_percentage', '18', 'int', 'payments', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(24, 'invoice_prefix', 'INV', 'string', 'payments', 0, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(25, 'refund_window_days', '7', 'int', 'payments', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(26, 'meta_title', 'Matrimony — Trusted Matrimonial Matchmaking', 'string', 'seo', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(27, 'meta_description', 'Verified profiles, real privacy controls and matches that fit your preferences.', 'string', 'seo', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(28, 'og_image', '', 'string', 'seo', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(29, 'chat_enabled', 'true', 'bool', 'features', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(30, 'success_stories_enabled', 'true', 'bool', 'features', 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(31, 'horoscope_enabled', 'false', 'bool', 'features', 1, '2026-08-27 11:57:41', '2026-08-27 11:57:41');

-- --------------------------------------------------------

--
-- Table structure for table `shortlists`
--

CREATE TABLE `shortlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `shortlisted_user_id` bigint(20) UNSIGNED NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `country_id`, `name`, `slug`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Tamil Nadu', 'tamil-nadu', 'TN', 1, '2026-08-27 11:57:35', '2026-08-27 11:57:35'),
(2, 1, 'Karnataka', 'karnataka', 'KA', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(3, 1, 'Maharashtra', 'maharashtra', 'MH', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(4, 1, 'Kerala', 'kerala', 'KL', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(5, 1, 'Telangana', 'telangana', 'TG', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(6, 1, 'Andhra Pradesh', 'andhra-pradesh', 'AP', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(7, 1, 'Delhi', 'delhi', 'DL', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(8, 1, 'Uttar Pradesh', 'uttar-pradesh', 'UP', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(9, 1, 'Gujarat', 'gujarat', 'GJ', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(10, 1, 'West Bengal', 'west-bengal', 'WB', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(11, 1, 'Rajasthan', 'rajasthan', 'RJ', 1, '2026-08-27 11:57:36', '2026-08-27 11:57:36'),
(12, 1, 'Punjab', 'punjab', 'PB', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(13, 1, 'Haryana', 'haryana', 'HR', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(14, 1, 'Madhya Pradesh', 'madhya-pradesh', 'MP', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(15, 1, 'Bihar', 'bihar', 'BR', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(16, 1, 'Odisha', 'odisha', 'OD', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(17, 1, 'Assam', 'assam', 'AS', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(18, 1, 'Jharkhand', 'jharkhand', 'JH', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(19, 1, 'Chhattisgarh', 'chhattisgarh', 'CG', 1, '2026-08-27 11:57:37', '2026-08-27 11:57:37'),
(20, 1, 'Uttarakhand', 'uttarakhand', 'UK', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(21, 1, 'Himachal Pradesh', 'himachal-pradesh', 'HP', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(22, 1, 'Goa', 'goa', 'GA', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(23, 1, 'Jammu and Kashmir', 'jammu-and-kashmir', 'JK', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(24, 1, 'Chandigarh', 'chandigarh', 'CH', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(25, 1, 'Puducherry', 'puducherry', 'PY', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(26, 1, 'Tripura', 'tripura', 'TR', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(27, 1, 'Manipur', 'manipur', 'MN', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(28, 1, 'Meghalaya', 'meghalaya', 'ML', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(29, 1, 'Nagaland', 'nagaland', 'NL', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(30, 1, 'Mizoram', 'mizoram', 'MZ', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(31, 1, 'Arunachal Pradesh', 'arunachal-pradesh', 'AR', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(32, 1, 'Sikkim', 'sikkim', 'SK', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(33, 1, 'Ladakh', 'ladakh', 'LA', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(34, 1, 'Andaman and Nicobar Islands', 'andaman-and-nicobar-islands', 'AN', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(35, 1, 'Dadra and Nagar Haveli and Daman and Diu', 'dadra-and-nagar-haveli-and-daman-and-diu', 'DN', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(36, 1, 'Lakshadweep', 'lakshadweep', 'LD', 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(37, 2, 'California', 'california', NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(38, 2, 'Texas', 'texas', NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(39, 2, 'New Jersey', 'new-jersey', NULL, 1, '2026-08-27 11:57:38', '2026-08-27 11:57:38'),
(40, 2, 'New York', 'new-york', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(41, 2, 'Illinois', 'illinois', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(42, 2, 'Washington', 'washington', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(43, 2, 'Georgia', 'georgia', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(44, 2, 'Massachusetts', 'massachusetts', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(45, 3, 'Dubai', 'dubai', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(46, 3, 'Abu Dhabi', 'abu-dhabi', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(47, 3, 'Sharjah', 'sharjah', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(48, 3, 'Ajman', 'ajman', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(49, 4, 'England', 'england', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(50, 4, 'Scotland', 'scotland', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(51, 4, 'Wales', 'wales', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(52, 4, 'Northern Ireland', 'northern-ireland', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(53, 5, 'Ontario', 'ontario', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(54, 5, 'British Columbia', 'british-columbia', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(55, 5, 'Alberta', 'alberta', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(56, 5, 'Quebec', 'quebec', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(57, 6, 'New South Wales', 'new-south-wales', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(58, 6, 'Victoria', 'victoria', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(59, 6, 'Queensland', 'queensland', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(60, 6, 'Western Australia', 'western-australia', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(61, 7, 'Singapore', 'singapore', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(62, 8, 'Doha', 'doha', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(63, 9, 'Riyadh', 'riyadh', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(64, 9, 'Makkah', 'makkah', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(65, 9, 'Eastern Province', 'eastern-province', NULL, 1, '2026-08-27 11:57:39', '2026-08-27 11:57:39'),
(66, 10, 'Kuwait', 'kuwait', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(67, 11, 'Muscat', 'muscat', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(68, 12, 'Kuala Lumpur', 'kuala-lumpur', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(69, 12, 'Selangor', 'selangor', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(70, 12, 'Penang', 'penang', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(71, 13, 'Auckland', 'auckland', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(72, 13, 'Wellington', 'wellington', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(73, 13, 'Canterbury', 'canterbury', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(74, 14, 'Bavaria', 'bavaria', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(75, 14, 'Berlin', 'berlin', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(76, 14, 'Hesse', 'hesse', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(77, 15, 'Leinster', 'leinster', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(78, 15, 'Munster', 'munster', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(79, 16, 'Western', 'western', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(80, 16, 'Northern', 'northern', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40'),
(81, 17, 'Bagmati', 'bagmati', NULL, 1, '2026-08-27 11:57:40', '2026-08-27 11:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `membership_plan_id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `features_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features_snapshot`)),
  `reminders_sent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reminders_sent`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `membership_plan_id`, `payment_id`, `starts_at`, `expires_at`, `status`, `auto_renew`, `cancelled_at`, `cancellation_reason`, `features_snapshot`, `reminders_sent`, `created_at`, `updated_at`) VALUES
(11, 524, 3, 5, '2026-09-10 04:32:25', '2026-12-09 04:32:25', 'active', 0, NULL, NULL, '{\"interests_per_month\":100,\"profile_views_per_day\":300,\"contact_views_per_month\":50,\"photo_limit\":10,\"can_chat\":true,\"can_view_contact\":true,\"can_see_profile_viewers\":true,\"advanced_search\":true,\"featured_profile\":false,\"priority_matching\":true,\"support_priority\":\"standard\"}', NULL, '2026-09-10 04:32:25', '2026-09-10 04:32:25'),
(12, 519, 2, 6, '2026-09-10 04:36:05', '2026-12-09 04:36:05', 'active', 0, NULL, NULL, '{\"interests_per_month\":30,\"profile_views_per_day\":100,\"contact_views_per_month\":10,\"photo_limit\":6,\"can_chat\":true,\"can_view_contact\":true,\"can_see_profile_viewers\":false,\"advanced_search\":true,\"featured_profile\":false,\"priority_matching\":false,\"support_priority\":\"standard\"}', NULL, '2026-09-10 04:36:05', '2026-09-10 04:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `sub_communities`
--

CREATE TABLE `sub_communities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_communities`
--

INSERT INTO `sub_communities` (`id`, `name`, `slug`, `sort_order`, `is_active`, `community_id`, `created_at`, `updated_at`) VALUES
(1, 'Iyer', 'hindu-brahmin-iyer', 0, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(2, 'Iyengar', 'hindu-brahmin-iyengar', 1, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(3, 'Smartha', 'hindu-brahmin-smartha', 2, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(4, 'Kanyakubja', 'hindu-brahmin-kanyakubja', 3, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(5, 'Deshastha', 'hindu-brahmin-deshastha', 4, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(6, 'Kokanastha', 'hindu-brahmin-kokanastha', 5, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(7, 'Gaur', 'hindu-brahmin-gaur', 6, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(8, 'Maithil', 'hindu-brahmin-maithil', 7, 1, 1, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(9, 'Rajput', 'hindu-kshatriya-rajput', 0, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(10, 'Thakur', 'hindu-kshatriya-thakur', 1, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(11, 'Maratha', 'hindu-kshatriya-maratha', 2, 1, 2, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(12, 'Agarwal', 'hindu-vaishya-agarwal', 0, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(13, 'Bania', 'hindu-vaishya-bania', 1, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(14, 'Gupta', 'hindu-vaishya-gupta', 2, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(15, 'Khandelwal', 'hindu-vaishya-khandelwal', 3, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(16, 'Maheshwari', 'hindu-vaishya-maheshwari', 4, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(17, 'Oswal', 'hindu-vaishya-oswal', 5, 1, 3, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(18, 'Arcot', 'hindu-mudaliar-arcot', 0, 1, 5, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(19, 'Saiva', 'hindu-mudaliar-saiva', 1, 1, 5, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(20, 'Sengunthar', 'hindu-mudaliar-sengunthar', 2, 1, 5, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(21, 'Nattukottai', 'hindu-chettiar-nattukottai', 0, 1, 6, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(22, 'Devanga', 'hindu-chettiar-devanga', 1, 1, 6, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(23, 'Vaniya', 'hindu-chettiar-vaniya', 2, 1, 6, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(24, 'Kongu Vellalar', 'hindu-gounder-kongu-vellalar', 0, 1, 7, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(25, 'Vettuva', 'hindu-gounder-vettuva', 1, 1, 7, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(26, 'Kapu', 'hindu-reddy-kapu', 0, 1, 9, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(27, 'Munnuru', 'hindu-reddy-munnuru', 1, 1, 9, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(28, 'Balija', 'hindu-naidu-balija', 0, 1, 11, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(29, 'Gavara', 'hindu-naidu-gavara', 1, 1, 11, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(30, 'Kamma Naidu', 'hindu-naidu-kamma-naidu', 2, 1, 11, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(31, 'Banajiga', 'hindu-lingayat-banajiga', 0, 1, 15, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(32, 'Panchamsali', 'hindu-lingayat-panchamsali', 1, 1, 15, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(33, 'Leva Patil', 'hindu-patel-leva-patil', 0, 1, 20, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(34, 'Kadva Patil', 'hindu-patel-kadva-patil', 1, 1, 20, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(35, 'Hanafi', 'muslim-sunni-hanafi', 0, 1, 28, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(36, 'Shafi', 'muslim-sunni-shafi', 1, 1, 28, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(37, 'Maliki', 'muslim-sunni-maliki', 2, 1, 28, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(38, 'Hanbali', 'muslim-sunni-hanbali', 3, 1, 28, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(39, 'Ithna Ashari', 'muslim-shia-ithna-ashari', 0, 1, 29, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(40, 'Bohra', 'muslim-shia-bohra', 1, 1, 29, '2026-08-27 11:57:33', '2026-08-27 11:57:33'),
(41, 'Ismaili', 'muslim-shia-ismaili', 2, 1, 29, '2026-08-27 11:57:33', '2026-08-27 11:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `success_stories`
--

CREATE TABLE `success_stories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `groom_name` varchar(120) NOT NULL,
  `bride_name` varchar(120) NOT NULL,
  `groom_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bride_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `marriage_date` date DEFAULT NULL,
  `story` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_counters`
--

CREATE TABLE `usage_counters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(60) NOT NULL,
  `period` varchar(10) NOT NULL,
  `used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usage_counters`
--

INSERT INTO `usage_counters` (`id`, `user_id`, `key`, `period`, `used`, `created_at`, `updated_at`) VALUES
(9, 519, 'interests_per_month', '2026-09', 2, '2026-09-09 14:22:40', '2026-09-09 14:22:40'),
(11, 1, 'interests_per_month', '2026-09', 1, '2026-09-12 09:35:49', '2026-09-12 09:35:49'),
(12, 524, 'profile_views_per_day', '2026-09-12', 1, '2026-09-12 09:38:54', '2026-09-12 09:38:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `profile_code` varchar(16) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `mobile` varchar(16) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `date_of_birth` date NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `suspension_reason` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `mobile_verified_at` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `registration_source` varchar(20) NOT NULL DEFAULT 'web',
  `referral_code` varchar(20) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `profile_code`, `name`, `email`, `mobile`, `password`, `gender`, `date_of_birth`, `role_id`, `status`, `suspension_reason`, `email_verified_at`, `mobile_verified_at`, `last_active_at`, `last_login_at`, `registration_source`, `referral_code`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'MAT2026000001', 'Super Admin', 'admin@matrimony.test', '+919000000001', '$2y$12$0p6UtubZrMFdFMXNUaZBwuRaAVIIeFe.AQt0CeH4ml8ElClQGG0i2', 'male', '1991-08-27', 1, 'active', NULL, '2026-08-27 11:57:41', '2026-08-27 11:57:41', NULL, NULL, 'web', NULL, 'nBJsBpSKfCaVmPmaK4oJSSebJdpxYjxPd9xDmlqT6MkjhhgQ1TETRsmm7TgV', '2026-08-27 11:57:41', '2026-09-12 10:37:51', NULL),
(2, 'MAT2026000002', 'Platform Admin', 'manager@matrimony.test', '+919000000002', '$2y$12$ROVorvzbcevdLm56EuzG9OAJ.k9dqizHys.vk0F0L580gRG123bZq', 'male', '1991-08-27', 2, 'active', NULL, '2026-08-27 11:57:41', '2026-08-27 11:57:41', NULL, NULL, 'web', NULL, NULL, '2026-08-27 11:57:41', '2026-09-12 10:35:33', NULL),
(3, 'MAT2026000003', 'Content Moderator', 'moderator@matrimony.test', '+919000000003', '$2y$12$C1fTRnwNSGu68Jv7I8DupOUcjcy21mKRXAq5S050UGvV8eQznvA1.', 'male', '1991-08-27', 3, 'active', NULL, '2026-08-27 11:57:41', '2026-08-27 11:57:41', NULL, NULL, 'web', NULL, NULL, '2026-08-27 11:57:42', '2026-09-12 10:35:34', NULL),
(4, 'MAT2026000004', 'Support Agent', 'support@matrimony.test', '+919000000004', '$2y$12$8hprPsSkv6dwg2KOWyRuqO/z4ILmI/aB7KCsGtralJ0NNkb7UlUu.', 'male', '1991-08-27', 4, 'active', NULL, '2026-08-27 11:57:42', '2026-08-27 11:57:42', NULL, NULL, 'web', NULL, NULL, '2026-08-27 11:57:42', '2026-09-12 10:35:34', NULL),
(512, 'MAT2026000512', 'kumar', 'kumar@gmail.com', '+9188888888', '$2y$12$OAeL2niv6yl89yYbBbyBQu7I1Ue8ARltdVGb2Kq0PRojxQD6k7wei', 'male', '1996-09-07', 4, 'active', NULL, '2026-09-07 04:13:26', '2026-09-07 04:13:26', NULL, NULL, 'web', NULL, NULL, '2026-09-07 04:13:26', '2026-09-07 04:13:26', NULL),
(519, 'MAT2026000513', 'Priya Sundaram', 'priya.s@matrimony.test', '+919876543210', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'female', '2001-06-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-12 09:30:53', '2026-09-12 09:30:53', 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-12 09:30:53', NULL),
(520, 'MAT2026000520', 'Ananya Ramachandran', 'ananya.r@matrimony.test', '+919876543211', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'female', '1998-04-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(521, 'MAT2026000521', 'Divya Murugan', 'divya.m@matrimony.test', '+919876543212', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'female', '1995-02-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(522, 'MAT2026000522', 'Meenakshi Krishnan', 'meenakshi.k@matrimony.test', '+919876543213', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'female', '1993-07-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(523, 'MAT2026000523', 'Kavitha Natarajan', 'kavitha.n@matrimony.test', '+919876543214', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'female', '1996-12-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(524, 'MAT2026000524', 'Arjun Reddy', 'arjun.r@matrimony.test', '+919876543215', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'male', '1998-05-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-12 09:38:32', '2026-09-12 09:38:32', 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-12 09:38:32', NULL),
(525, 'MAT2026000525', 'Karthik Venkat', 'karthik.v@matrimony.test', '+919876543216', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'male', '1994-01-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(526, 'MAT2026000526', 'Rajesh Palaniswamy', 'rajesh.p@matrimony.test', '+919876543217', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'male', '1991-10-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(527, 'MAT2026000527', 'Suresh Balaji', 'suresh.b@matrimony.test', '+919876543218', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'male', '1990-03-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(528, 'MAT2026000528', 'Vignesh Mohan', 'vignesh.m@matrimony.test', '+919876543219', '$2y$12$ddFk5jlEqS9DAEl7d7J4W.iWO30kuTfoxgXuPiX7oTFJd1DQo1s26', 'male', '1996-08-09', 5, 'active', NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL, 'web', NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(529, 'MAT2026000529', 'Banupriya Chokkaiah', 'banupriya.caskb@gmail.com', '+919042985433', '$2y$12$rIjw5l9Aop4p1FK29J6aMuGmsnON4NXdi56GsIV2DnQM6E.EvuXc.', 'female', '2004-09-07', 5, 'active', NULL, NULL, '2026-09-10 06:33:48', NULL, NULL, 'web', NULL, NULL, '2026-09-10 06:33:13', '2026-09-10 06:33:48', NULL),
(530, 'MAT2026000530', 'Banupriya Chokkaiah', 'banupriya.info13101cs@gmail.com', '+917806890466', '$2y$12$ZRNP99mSB3ZsSHtix6YkFuw7MiotcOMYkb7sWCD0a4QNKAuT.6AWC', 'female', '2002-02-13', 5, 'active', NULL, NULL, '2026-09-12 10:47:30', '2026-09-12 10:49:28', '2026-09-12 10:49:28', 'web', NULL, NULL, '2026-09-12 10:47:13', '2026-09-12 10:49:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_jadhagams`
--

CREATE TABLE `user_jadhagams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `disk` varchar(30) NOT NULL DEFAULT 'public',
  `mime_type` varchar(60) NOT NULL,
  `size_bytes` int(10) UNSIGNED NOT NULL,
  `approval_status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `moderated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_jadhagams`
--

INSERT INTO `user_jadhagams` (`id`, `user_id`, `path`, `disk`, `mime_type`, `size_bytes`, `approval_status`, `rejection_reason`, `moderated_by`, `moderated_at`, `created_at`, `updated_at`) VALUES
(2, 519, 'jadhagams/gMzR4s43MjtJ96XXRDdQmEP4tDKC0NJKrkAOF5Yj.jpg', 'public', 'image/jpeg', 130069, 'approved', NULL, 1, '2026-09-12 09:34:46', '2026-09-12 09:31:32', '2026-09-12 09:34:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `age_min` tinyint(3) UNSIGNED DEFAULT NULL,
  `age_max` tinyint(3) UNSIGNED DEFAULT NULL,
  `height_min_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `height_max_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `marital_status_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`marital_status_ids`)),
  `religion_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`religion_ids`)),
  `community_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`community_ids`)),
  `mother_tongue_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mother_tongue_ids`)),
  `education_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education_ids`)),
  `occupation_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`occupation_ids`)),
  `country_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`country_ids`)),
  `state_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`state_ids`)),
  `city_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`city_ids`)),
  `income_min` bigint(20) UNSIGNED DEFAULT NULL,
  `income_max` bigint(20) UNSIGNED DEFAULT NULL,
  `diet` varchar(30) DEFAULT NULL,
  `smoking` varchar(20) DEFAULT NULL,
  `drinking` varchar(20) DEFAULT NULL,
  `manglik_preference` varchar(20) DEFAULT NULL,
  `physical_status_preference` varchar(30) DEFAULT NULL,
  `about_partner` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `age_min`, `age_max`, `height_min_cm`, `height_max_cm`, `marital_status_ids`, `religion_ids`, `community_ids`, `mother_tongue_ids`, `education_ids`, `occupation_ids`, `country_ids`, `state_ids`, `city_ids`, `income_min`, `income_max`, `diet`, `smoking`, `drinking`, `manglik_preference`, `physical_status_preference`, `about_partner`, `created_at`, `updated_at`) VALUES
(511, 519, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(512, 520, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(513, 521, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(514, 522, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(515, 523, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(516, 524, 21, 45, 150, 195, NULL, '[1]', NULL, '[4]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(517, 525, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(518, 526, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(519, 527, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(520, 528, 21, 45, 150, 195, NULL, '[1]', NULL, '[5]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 14:15:24', '2026-09-09 14:15:24'),
(521, 529, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 06:33:13', '2026-09-10 06:33:13'),
(522, 530, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 10:47:13', '2026-09-12 10:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `profile_created_by` varchar(20) NOT NULL DEFAULT 'self',
  `height_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `weight_kg` smallint(5) UNSIGNED DEFAULT NULL,
  `marital_status_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body_type` varchar(30) DEFAULT NULL,
  `complexion` varchar(30) DEFAULT NULL,
  `physical_status` varchar(30) NOT NULL DEFAULT 'normal',
  `children_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `children_living_status` varchar(20) DEFAULT NULL,
  `religion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `community_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_community_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mother_tongue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `caste` varchar(80) DEFAULT NULL,
  `gothra` varchar(80) DEFAULT NULL,
  `star` varchar(60) DEFAULT NULL,
  `raasi` varchar(60) DEFAULT NULL,
  `manglik` varchar(20) DEFAULT NULL,
  `horoscope_path` varchar(255) DEFAULT NULL,
  `birth_time` time DEFAULT NULL,
  `birth_place` varchar(120) DEFAULT NULL,
  `education_id` bigint(20) UNSIGNED DEFAULT NULL,
  `education_detail` varchar(150) DEFAULT NULL,
  `college` varchar(150) DEFAULT NULL,
  `university` varchar(150) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `occupation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `job_type` varchar(30) DEFAULT NULL,
  `income_range_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `state_id` bigint(20) UNSIGNED DEFAULT NULL,
  `city_id` bigint(20) UNSIGNED DEFAULT NULL,
  `residency_status` varchar(40) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `father_occupation` varchar(120) DEFAULT NULL,
  `mother_occupation` varchar(120) DEFAULT NULL,
  `brothers_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `brothers_married` tinyint(3) UNSIGNED DEFAULT NULL,
  `sisters_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `sisters_married` tinyint(3) UNSIGNED DEFAULT NULL,
  `family_type` varchar(30) DEFAULT NULL,
  `family_status` varchar(30) DEFAULT NULL,
  `family_values` varchar(30) DEFAULT NULL,
  `family_location` varchar(120) DEFAULT NULL,
  `family_description` text DEFAULT NULL,
  `diet` varchar(30) DEFAULT NULL,
  `smoking` varchar(20) DEFAULT NULL,
  `drinking` varchar(20) DEFAULT NULL,
  `hobbies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hobbies`)),
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `about_me` text DEFAULT NULL,
  `expectations` text DEFAULT NULL,
  `completion_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `approval_status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `primary_photo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_views_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `shortlisted_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `interests_received_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `profile_created_by`, `height_cm`, `weight_kg`, `marital_status_id`, `body_type`, `complexion`, `physical_status`, `children_count`, `children_living_status`, `religion_id`, `community_id`, `sub_community_id`, `mother_tongue_id`, `caste`, `gothra`, `star`, `raasi`, `manglik`, `horoscope_path`, `birth_time`, `birth_place`, `education_id`, `education_detail`, `college`, `university`, `specialization`, `occupation_id`, `company`, `job_type`, `income_range_id`, `work_country_id`, `work_state_id`, `work_city_id`, `country_id`, `state_id`, `city_id`, `residency_status`, `pincode`, `father_occupation`, `mother_occupation`, `brothers_count`, `brothers_married`, `sisters_count`, `sisters_married`, `family_type`, `family_status`, `family_values`, `family_location`, `family_description`, `diet`, `smoking`, `drinking`, `hobbies`, `interests`, `about_me`, `expectations`, `completion_percentage`, `is_verified`, `verified_at`, `approval_status`, `rejection_reason`, `primary_photo_id`, `profile_views_count`, `shortlisted_count`, `interests_received_count`, `created_at`, `updated_at`, `deleted_at`) VALUES
(512, 519, 'self', 162, 54, 1, NULL, NULL, 'normal', NULL, NULL, 1, 1, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'B.Tech - Computer Science', NULL, NULL, NULL, 2, NULL, 'private', 2, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'upper_middle', 'moderate', NULL, NULL, 'vegetarian', 'no', 'no', NULL, NULL, 'Warm, ambitious software professional who enjoys classical music, reading, and weekend treks.', 'Looking for a kind, understanding partner with shared family values.', 80, 1, '2026-09-09 14:15:24', 'approved', NULL, 9, 1, 0, 1, '2026-09-09 14:15:24', '2026-09-12 09:38:54', NULL),
(513, 520, 'self', 165, 58, 6, NULL, NULL, 'normal', NULL, NULL, 1, 5, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 'MBA - Finance', NULL, NULL, NULL, 3, NULL, 'private', 1, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'middle_class', 'moderate', NULL, NULL, 'eggetarian', 'no', 'no', NULL, NULL, 'Analytical mindset with a passion for creative arts and family gatherings.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(514, 521, 'self', 160, 55, 2, NULL, NULL, 'normal', NULL, NULL, 1, 6, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 'MBBS, MD - General Medicine', NULL, NULL, NULL, 4, NULL, 'private', 3, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'upper_middle', 'moderate', NULL, NULL, 'non-vegetarian', 'no', 'no', NULL, NULL, 'Dedicated healthcare professional looking for an understanding, mature partner. Believes in mutual respect and open communication.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(515, 522, 'self', 158, 52, 3, NULL, NULL, 'normal', NULL, NULL, 1, 4, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 'M.Sc, B.Ed - Mathematics', NULL, NULL, NULL, 5, NULL, 'govt', 8, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'middle_class', 'moderate', NULL, NULL, 'vegetarian', 'no', 'no', NULL, NULL, 'Compassionate high school educator who values family bonding, simple living, and cultural values.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(516, 523, 'self', 163, 56, 4, NULL, NULL, 'normal', NULL, NULL, 1, 7, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 'B.Des - UI/UX Product Design', NULL, NULL, NULL, 6, NULL, 'self_employed', 9, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'middle_class', 'moderate', NULL, NULL, 'vegetarian', 'no', 'no', NULL, NULL, 'Creative soul working in digital design. Looking for a fresh, positive chapter with a supportive partner.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(517, 524, 'self', 178, 74, 1, NULL, NULL, 'normal', NULL, NULL, 1, 9, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 'M.S. - Data Science & AI', NULL, NULL, NULL, 7, NULL, 'private', 4, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'upper_middle', 'moderate', NULL, NULL, 'non-vegetarian', 'no', 'no', NULL, NULL, 'Technology enthusiast and fitness buff. Enjoys road trips, photography, and good conversations.', 'Looking for a kind, understanding partner with shared family values.', 80, 1, '2026-09-09 14:15:24', 'approved', NULL, 10, 0, 0, 1, '2026-09-09 14:15:24', '2026-09-10 04:29:10', NULL),
(518, 525, 'self', 175, 70, 6, NULL, NULL, 'normal', NULL, NULL, 1, 1, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8, 'B.E., MBA - Product Strategy', NULL, NULL, NULL, 8, NULL, 'private', 10, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'rich', 'moderate', NULL, NULL, 'vegetarian', 'no', 'no', NULL, NULL, 'Product strategist in fintech. Passionate about innovation, culinary experiments, and books.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(519, 526, 'self', 180, 76, 2, NULL, NULL, 'normal', NULL, NULL, 1, 11, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, 'B.Arch - Architecture & Urban Planning', NULL, NULL, NULL, 9, NULL, 'business', 5, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'upper_middle', 'moderate', NULL, NULL, 'eggetarian', 'no', 'no', NULL, NULL, 'Independent entrepreneur and architect. Grounded, family-oriented, seeking an understanding companion.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 1, '2026-09-09 14:15:24', '2026-09-09 14:22:40', NULL),
(520, 527, 'self', 172, 71, 3, NULL, NULL, 'normal', NULL, NULL, 1, 5, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, 'M.Tech - Mechanical Engineering', NULL, NULL, NULL, 10, NULL, 'private', 11, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'middle_class', 'moderate', NULL, NULL, 'non-vegetarian', 'no', 'no', NULL, NULL, 'Patient, family-centered engineering manager who enjoys music and nature walks. Ready for a new beginning.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(521, 528, 'self', 174, 68, 4, NULL, NULL, 'normal', NULL, NULL, 1, 8, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, 'Chartered Accountant (FCA)', NULL, NULL, NULL, 11, NULL, 'private', 6, NULL, NULL, NULL, 1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nuclear', 'upper_middle', 'moderate', NULL, NULL, 'vegetarian', 'no', 'no', NULL, NULL, 'Analytical, calm, and grounded audit consultant. Believes in transparency, personal growth, and companionship.', 'Looking for a kind, understanding partner with shared family values.', 100, 1, '2026-09-09 14:15:24', 'approved', NULL, NULL, 0, 0, 0, '2026-09-09 14:15:24', '2026-09-09 14:15:24', NULL),
(522, 529, 'self', NULL, NULL, NULL, NULL, NULL, 'normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'pending', NULL, NULL, 0, 0, 0, '2026-09-10 06:33:13', '2026-09-10 06:33:13', NULL),
(523, 530, 'self', NULL, NULL, NULL, NULL, NULL, 'normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 'pending', NULL, NULL, 0, 0, 0, '2026-09-12 10:47:13', '2026-09-12 10:47:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `verification_documents`
--

CREATE TABLE `verification_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL,
  `document_number_hash` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `disk` varchar(30) NOT NULL DEFAULT 'private',
  `mime_type` varchar(60) NOT NULL,
  `size_bytes` int(10) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `audit_logs_action_created_at_index` (`action`,`created_at`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banners_position_is_active_sort_order_index` (`position`,`is_active`,`sort_order`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blocked_users_user_id_blocked_user_id_unique` (`user_id`,`blocked_user_id`),
  ADD KEY `blocked_users_blocked_user_id_index` (`blocked_user_id`);

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
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cities_state_id_slug_unique` (`state_id`,`slug`),
  ADD KEY `cities_state_id_name_index` (`state_id`,`name`),
  ADD KEY `cities_name_index` (`name`);

--
-- Indexes for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cms_pages_slug_unique` (`slug`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `communities_slug_unique` (`slug`),
  ADD KEY `communities_religion_id_foreign` (`religion_id`),
  ADD KEY `communities_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `contact_enquiries`
--
ALTER TABLE `contact_enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_enquiries_handled_by_foreign` (`handled_by`),
  ADD KEY `contact_enquiries_status_created_at_index` (`status`,`created_at`);

--
-- Indexes for table `contact_views`
--
ALTER TABLE `contact_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact_views_viewer_id_viewed_id_period_unique` (`viewer_id`,`viewed_id`,`period`),
  ADD KEY `contact_views_viewed_id_foreign` (`viewed_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversations_user_one_id_user_two_id_unique` (`user_one_id`,`user_two_id`),
  ADD KEY `conversations_match_id_foreign` (`match_id`),
  ADD KEY `conversations_last_message_sender_id_foreign` (`last_message_sender_id`),
  ADD KEY `conversations_user_one_id_last_message_at_index` (`user_one_id`,`last_message_at`),
  ADD KEY `conversations_user_two_id_last_message_at_index` (`user_two_id`,`last_message_at`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_slug_unique` (`slug`),
  ADD KEY `countries_iso2_index` (`iso2`);

--
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_tokens_token_unique` (`token`),
  ADD KEY `device_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `educations`
--
ALTER TABLE `educations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `educations_slug_unique` (`slug`),
  ADD KEY `educations_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faqs_category_sort_order_index` (`category`,`sort_order`);

--
-- Indexes for table `income_ranges`
--
ALTER TABLE `income_ranges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `income_ranges_slug_unique` (`slug`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interests_sender_id_receiver_id_unique` (`sender_id`,`receiver_id`),
  ADD KEY `interests_receiver_id_status_created_at_index` (`receiver_id`,`status`,`created_at`),
  ADD KEY `interests_sender_id_status_created_at_index` (`sender_id`,`status`,`created_at`);

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
-- Indexes for table `lifestyle_options`
--
ALTER TABLE `lifestyle_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lifestyle_options_type_slug_unique` (`type`,`slug`),
  ADD KEY `lifestyle_options_type_is_active_index` (`type`,`is_active`);

--
-- Indexes for table `marital_statuses`
--
ALTER TABLE `marital_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `marital_statuses_slug_unique` (`slug`),
  ADD KEY `marital_statuses_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matches_user_one_id_user_two_id_unique` (`user_one_id`,`user_two_id`),
  ADD KEY `matches_user_one_id_status_index` (`user_one_id`,`status`),
  ADD KEY `matches_user_two_id_status_index` (`user_two_id`,`status`),
  ADD KEY `matches_conversation_id_foreign` (`conversation_id`);

--
-- Indexes for table `match_scores`
--
ALTER TABLE `match_scores`
  ADD PRIMARY KEY (`user_id`,`candidate_id`),
  ADD KEY `match_scores_candidate_id_foreign` (`candidate_id`),
  ADD KEY `match_scores_user_id_score_index` (`user_id`,`score`);

--
-- Indexes for table `matrimony_limits`
--
ALTER TABLE `matrimony_limits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_uploads`
--
ALTER TABLE `media_uploads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `media_uploads_path_unique` (`path`),
  ADD KEY `media_uploads_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `media_uploads_collection_created_at_index` (`collection`,`created_at`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `membership_plans_slug_unique` (`slug`),
  ADD KEY `membership_plans_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `messages_conversation_id_client_message_id_unique` (`conversation_id`,`client_message_id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`),
  ADD KEY `messages_conversation_id_id_index` (`conversation_id`,`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mother_tongues`
--
ALTER TABLE `mother_tongues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mother_tongues_slug_unique` (`slug`),
  ADD KEY `mother_tongues_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_read_at_created_at_index` (`user_id`,`read_at`,`created_at`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_settings_user_id_unique` (`user_id`);

--
-- Indexes for table `occupations`
--
ALTER TABLE `occupations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `occupations_slug_unique` (`slug`),
  ADD KEY `occupations_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otp_verifications_identifier_purpose_expires_at_index` (`identifier`,`purpose`,`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_razorpay_order_id_unique` (`razorpay_order_id`),
  ADD UNIQUE KEY `payments_razorpay_payment_id_unique` (`razorpay_payment_id`),
  ADD UNIQUE KEY `payments_invoice_number_unique` (`invoice_number`),
  ADD KEY `payments_membership_plan_id_foreign` (`membership_plan_id`),
  ADD KEY `payments_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `payments_status_created_at_index` (`status`,`created_at`),
  ADD KEY `payments_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD KEY `permissions_group_index` (`group`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `privacy_settings_user_id_unique` (`user_id`);

--
-- Indexes for table `profile_photos`
--
ALTER TABLE `profile_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_photos_moderated_by_foreign` (`moderated_by`),
  ADD KEY `profile_photos_user_id_sort_order_index` (`user_id`,`sort_order`),
  ADD KEY `profile_photos_approval_status_index` (`approval_status`);

--
-- Indexes for table `profile_views`
--
ALTER TABLE `profile_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_views_viewed_id_viewed_at_index` (`viewed_id`,`viewed_at`),
  ADD KEY `profile_views_viewer_id_viewed_at_index` (`viewer_id`,`viewed_at`);

--
-- Indexes for table `religions`
--
ALTER TABLE `religions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `religions_slug_unique` (`slug`),
  ADD KEY `religions_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_reporter_id_foreign` (`reporter_id`),
  ADD KEY `reports_assigned_to_foreign` (`assigned_to`),
  ADD KEY `reports_resolved_by_foreign` (`resolved_by`),
  ADD KEY `reports_status_created_at_index` (`status`,`created_at`),
  ADD KEY `reports_reported_user_id_index` (`reported_user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saved_searches_user_id_created_at_index` (`user_id`,`created_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`),
  ADD KEY `settings_group_index` (`group`);

--
-- Indexes for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shortlists_user_id_shortlisted_user_id_unique` (`user_id`,`shortlisted_user_id`),
  ADD KEY `shortlists_shortlisted_user_id_created_at_index` (`shortlisted_user_id`,`created_at`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `states_country_id_slug_unique` (`country_id`,`slug`),
  ADD KEY `states_country_id_name_index` (`country_id`,`name`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_membership_plan_id_foreign` (`membership_plan_id`),
  ADD KEY `subscriptions_payment_id_foreign` (`payment_id`),
  ADD KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  ADD KEY `subscriptions_status_expires_at_index` (`status`,`expires_at`);

--
-- Indexes for table `sub_communities`
--
ALTER TABLE `sub_communities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_communities_slug_unique` (`slug`),
  ADD KEY `sub_communities_community_id_foreign` (`community_id`),
  ADD KEY `sub_communities_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `success_stories`
--
ALTER TABLE `success_stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `success_stories_groom_user_id_foreign` (`groom_user_id`),
  ADD KEY `success_stories_bride_user_id_foreign` (`bride_user_id`),
  ADD KEY `success_stories_is_published_sort_order_index` (`is_published`,`sort_order`);

--
-- Indexes for table `usage_counters`
--
ALTER TABLE `usage_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usage_counters_user_id_key_period_unique` (`user_id`,`key`,`period`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_profile_code_unique` (`profile_code`),
  ADD UNIQUE KEY `users_mobile_unique` (`mobile`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_status_gender_index` (`status`,`gender`),
  ADD KEY `users_last_active_at_index` (`last_active_at`),
  ADD KEY `users_date_of_birth_index` (`date_of_birth`),
  ADD KEY `users_created_at_index` (`created_at`);

--
-- Indexes for table `user_jadhagams`
--
ALTER TABLE `user_jadhagams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_jadhagams_moderated_by_foreign` (`moderated_by`),
  ADD KEY `user_jadhagams_user_id_approval_status_index` (`user_id`,`approval_status`),
  ADD KEY `user_jadhagams_approval_status_index` (`approval_status`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_preferences_user_id_unique` (`user_id`),
  ADD KEY `user_preferences_age_min_age_max_index` (`age_min`,`age_max`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_profiles_user_id_unique` (`user_id`),
  ADD KEY `user_profiles_community_id_foreign` (`community_id`),
  ADD KEY `user_profiles_sub_community_id_foreign` (`sub_community_id`),
  ADD KEY `user_profiles_mother_tongue_id_foreign` (`mother_tongue_id`),
  ADD KEY `user_profiles_occupation_id_foreign` (`occupation_id`),
  ADD KEY `user_profiles_income_range_id_foreign` (`income_range_id`),
  ADD KEY `user_profiles_work_country_id_foreign` (`work_country_id`),
  ADD KEY `user_profiles_work_state_id_foreign` (`work_state_id`),
  ADD KEY `user_profiles_work_city_id_foreign` (`work_city_id`),
  ADD KEY `user_profiles_state_id_foreign` (`state_id`),
  ADD KEY `user_profiles_city_id_foreign` (`city_id`),
  ADD KEY `idx_search_core` (`religion_id`,`community_id`,`mother_tongue_id`),
  ADD KEY `idx_search_location` (`country_id`,`state_id`,`city_id`),
  ADD KEY `idx_search_ec` (`education_id`,`occupation_id`,`income_range_id`),
  ADD KEY `idx_marital` (`marital_status_id`),
  ADD KEY `idx_approval` (`approval_status`,`is_verified`),
  ADD KEY `idx_completion` (`completion_percentage`),
  ADD KEY `user_profiles_primary_photo_id_foreign` (`primary_photo_id`);

--
-- Indexes for table `verification_documents`
--
ALTER TABLE `verification_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verification_documents_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `verification_documents_user_id_status_index` (`user_id`,`status`),
  ADD KEY `verification_documents_status_index` (`status`),
  ADD KEY `verification_documents_document_number_hash_index` (`document_number_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `contact_enquiries`
--
ALTER TABLE `contact_enquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_views`
--
ALTER TABLE `contact_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `educations`
--
ALTER TABLE `educations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `income_ranges`
--
ALTER TABLE `income_ranges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `lifestyle_options`
--
ALTER TABLE `lifestyle_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `marital_statuses`
--
ALTER TABLE `marital_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `matrimony_limits`
--
ALTER TABLE `matrimony_limits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `media_uploads`
--
ALTER TABLE `media_uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `mother_tongues`
--
ALTER TABLE `mother_tongues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `occupations`
--
ALTER TABLE `occupations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- AUTO_INCREMENT for table `profile_photos`
--
ALTER TABLE `profile_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `profile_views`
--
ALTER TABLE `profile_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `religions`
--
ALTER TABLE `religions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `shortlists`
--
ALTER TABLE `shortlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sub_communities`
--
ALTER TABLE `sub_communities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `success_stories`
--
ALTER TABLE `success_stories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_counters`
--
ALTER TABLE `usage_counters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=531;

--
-- AUTO_INCREMENT for table `user_jadhagams`
--
ALTER TABLE `user_jadhagams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=524;

--
-- AUTO_INCREMENT for table `verification_documents`
--
ALTER TABLE `verification_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `blocked_users_blocked_user_id_foreign` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocked_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_religion_id_foreign` FOREIGN KEY (`religion_id`) REFERENCES `religions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_enquiries`
--
ALTER TABLE `contact_enquiries`
  ADD CONSTRAINT `contact_enquiries_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_views`
--
ALTER TABLE `contact_views`
  ADD CONSTRAINT `contact_views_viewed_id_foreign` FOREIGN KEY (`viewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_views_viewer_id_foreign` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_last_message_sender_id_foreign` FOREIGN KEY (`last_message_sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_user_one_id_foreign` FOREIGN KEY (`user_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_user_two_id_foreign` FOREIGN KEY (`user_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interests`
--
ALTER TABLE `interests`
  ADD CONSTRAINT `interests_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interests_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_user_one_id_foreign` FOREIGN KEY (`user_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_user_two_id_foreign` FOREIGN KEY (`user_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `match_scores`
--
ALTER TABLE `match_scores`
  ADD CONSTRAINT `match_scores_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_uploads`
--
ALTER TABLE `media_uploads`
  ADD CONSTRAINT `media_uploads_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_membership_plan_id_foreign` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  ADD CONSTRAINT `privacy_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_photos`
--
ALTER TABLE `profile_photos`
  ADD CONSTRAINT `profile_photos_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `profile_photos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_views`
--
ALTER TABLE `profile_views`
  ADD CONSTRAINT `profile_views_viewed_id_foreign` FOREIGN KEY (`viewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_views_viewer_id_foreign` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_reported_user_id_foreign` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD CONSTRAINT `saved_searches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD CONSTRAINT `shortlists_shortlisted_user_id_foreign` FOREIGN KEY (`shortlisted_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shortlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_membership_plan_id_foreign` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`),
  ADD CONSTRAINT `subscriptions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_communities`
--
ALTER TABLE `sub_communities`
  ADD CONSTRAINT `sub_communities_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `success_stories`
--
ALTER TABLE `success_stories`
  ADD CONSTRAINT `success_stories_bride_user_id_foreign` FOREIGN KEY (`bride_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `success_stories_groom_user_id_foreign` FOREIGN KEY (`groom_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `usage_counters`
--
ALTER TABLE `usage_counters`
  ADD CONSTRAINT `usage_counters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_jadhagams`
--
ALTER TABLE `user_jadhagams`
  ADD CONSTRAINT `user_jadhagams_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_jadhagams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_education_id_foreign` FOREIGN KEY (`education_id`) REFERENCES `educations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_income_range_id_foreign` FOREIGN KEY (`income_range_id`) REFERENCES `income_ranges` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_marital_status_id_foreign` FOREIGN KEY (`marital_status_id`) REFERENCES `marital_statuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_mother_tongue_id_foreign` FOREIGN KEY (`mother_tongue_id`) REFERENCES `mother_tongues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_occupation_id_foreign` FOREIGN KEY (`occupation_id`) REFERENCES `occupations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_primary_photo_id_foreign` FOREIGN KEY (`primary_photo_id`) REFERENCES `profile_photos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_religion_id_foreign` FOREIGN KEY (`religion_id`) REFERENCES `religions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_sub_community_id_foreign` FOREIGN KEY (`sub_community_id`) REFERENCES `sub_communities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_profiles_work_city_id_foreign` FOREIGN KEY (`work_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_work_country_id_foreign` FOREIGN KEY (`work_country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_work_state_id_foreign` FOREIGN KEY (`work_state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `verification_documents`
--
ALTER TABLE `verification_documents`
  ADD CONSTRAINT `verification_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `verification_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
