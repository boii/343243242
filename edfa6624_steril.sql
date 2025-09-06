-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 06, 2025 at 08:38 PM
-- Server version: 10.11.13-MariaDB-cll-lve
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edfa6624_steril`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID pengguna yang melakukan aksi, NULL jika oleh sistem.',
  `action_type` varchar(50) NOT NULL COMMENT 'Tipe aksi, e.g., LOGIN, CREATE_USER, UPDATE_INSTRUMENT.',
  `target_type` varchar(50) DEFAULT NULL COMMENT 'Tipe item yang terpengaruh, e.g., user, instrument, label.',
  `target_id` int(11) DEFAULT NULL COMMENT 'ID dari item yang terpengaruh.',
  `details` text DEFAULT NULL COMMENT 'Detail tambahan tentang aksi dalam format teks atau JSON.',
  `ip_address` varchar(45) DEFAULT NULL,
  `log_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mencatat semua aktivitas penting dalam sistem.';

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `client_name` varchar(100) NOT NULL COMMENT 'Nama sistem/klien yang menggunakan kunci ini',
  `api_key` varchar(255) NOT NULL COMMENT 'Kunci API yang sebenarnya',
  `permissions` varchar(255) DEFAULT 'read_only' COMMENT 'Hak akses (contoh: read_only, read_write)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `client_name`, `api_key`, `permissions`, `is_active`, `created_at`, `last_used_at`) VALUES
(1, 'Sistem Informasi Rumah Sakit Utama', 'bfa311292c0664153a042ac6d45a3ef76dee05607d73929e514fe7c5bcaeac39', 'read_write', 1, '2025-08-29 05:44:27', '2025-08-31 05:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `updated_at`) VALUES
('app_instance_name', 'Sterilabel', '2025-07-07 02:01:25'),
('app_logo_filename', 'logo.png', '2025-07-07 06:05:00'),
('default_expiry_days', '30', '2025-07-06 23:36:58'),
('enable_pending_validation', '0', '2025-06-04 07:24:55'),
('print_template', 'normal', '2025-08-29 13:22:01'),
('show_app_name_beside_logo', '1', '2025-07-05 22:25:43'),
('show_status_block_on_detail_page', '0', '2025-06-04 16:15:13'),
('staff_can_manage_instruments', '0', '2025-07-06 03:31:58'),
('staff_can_manage_sets', '0', '2025-07-06 01:39:50'),
('staff_can_view_activity_log', '0', '2025-07-06 01:39:50'),
('thermal_custom_text_1', 'CSSD LIFIRA MITRA ABADI', '2025-07-29 02:44:25'),
('thermal_custom_text_2', 'Surakarta', '2025-07-29 02:44:25'),
('thermal_fields_config', '{\"item_name\":{\"visible\":false,\"order\":3,\"hide_label\":true,\"custom_label\":\"\"},\"label_title\":{\"visible\":true,\"order\":2,\"hide_label\":true,\"custom_label\":\"\"},\"created_at\":{\"visible\":true,\"order\":5,\"hide_label\":false,\"custom_label\":\"Prd\"},\"expiry_date\":{\"visible\":true,\"order\":6,\"hide_label\":false,\"custom_label\":\"Exp\"},\"label_unique_id\":{\"visible\":false,\"order\":4,\"hide_label\":true,\"custom_label\":\"\"},\"load_name\":{\"visible\":false,\"order\":7,\"hide_label\":false,\"custom_label\":\"\"},\"cycle_number\":{\"visible\":false,\"order\":8,\"hide_label\":false,\"custom_label\":\"\"},\"machine_name\":{\"visible\":false,\"order\":9,\"hide_label\":false,\"custom_label\":\"\"},\"creator_username\":{\"visible\":true,\"order\":15,\"hide_label\":false,\"custom_label\":\"Dibuat\"},\"used_at\":{\"visible\":false,\"order\":14,\"hide_label\":false,\"custom_label\":\"\"},\"notes\":{\"visible\":false,\"order\":16,\"hide_label\":false,\"custom_label\":\"\"},\"custom_text_1\":{\"visible\":true,\"order\":1,\"hide_label\":true,\"custom_label\":\"\"},\"custom_text_2\":{\"visible\":false,\"order\":17,\"hide_label\":false,\"custom_label\":\"\"},\"cycle_operator_name\":{\"visible\":false,\"order\":10,\"hide_label\":false,\"custom_label\":\"\"},\"cycle_date\":{\"visible\":false,\"order\":11,\"hide_label\":false,\"custom_label\":\"\"},\"load_creator_name\":{\"visible\":false,\"order\":12,\"hide_label\":false,\"custom_label\":\"\"},\"destination_department_name\":{\"visible\":false,\"order\":13,\"hide_label\":false,\"custom_label\":\"Tujuan\"}}', '2025-08-29 13:22:17'),
('thermal_fields_visibility', '{\"item_name\":false,\"label_unique_id\":false,\"autoclave_cycle_id\":false,\"created_at\":false,\"expiry_date\":false,\"creator_username\":false,\"notes\":false}', '2025-05-29 16:26:33'),
('thermal_paper_height_mm', '70', '2025-08-27 12:22:36'),
('thermal_paper_width_mm', '70', '2025-05-31 09:39:00'),
('thermal_qr_position', 'top_center', '2025-08-29 13:22:06'),
('thermal_qr_size', 'medium', '2025-08-27 12:19:23');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data untuk nama departemen atau unit.';

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `is_active`, `created_at`) VALUES
(1, 'IBS', 1, '2025-06-26 15:39:21'),
(2, 'PEDIATRIK', 1, '2025-06-26 15:39:21'),
(3, 'THT', 1, '2025-06-26 15:39:21'),
(4, 'CSSD', 1, '2025-06-26 15:39:21'),
(8, 'Administrasi', 1, '2025-06-27 04:35:52'),
(9, 'EDR', 0, '2025-07-01 13:23:35'),
(10, 'BOH', 0, '2025-07-01 13:23:44'),
(11, 'IBS LANTAI 7 RUANG MAWAR', 1, '2025-08-23 09:16:41'),
(12, 'IBS LANTAI 6 RUANG MELATI', 1, '2025-08-23 09:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `instruments`
--

CREATE TABLE `instruments` (
  `instrument_id` int(11) NOT NULL,
  `instrument_name` varchar(255) NOT NULL,
  `instrument_code` varchar(100) DEFAULT NULL,
  `instrument_type_id` int(11) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'tersedia',
  `expiry_in_days` int(5) DEFAULT NULL COMMENT 'Masa kedaluwarsa standar untuk instrumen ini dalam hari. NULL berarti menggunakan pengaturan global.',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instrument_history`
--

CREATE TABLE `instrument_history` (
  `history_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `changed_to_status` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `change_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instrument_sets`
--

CREATE TABLE `instrument_sets` (
  `set_id` int(11) NOT NULL,
  `set_name` varchar(255) NOT NULL,
  `set_code` varchar(100) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `expiry_in_days` int(5) DEFAULT NULL COMMENT 'Masa kedaluwarsa standar untuk set ini dalam hari. NULL berarti menggunakan aturan terpendek dari item di dalamnya atau pengaturan global.',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instrument_set_items`
--

CREATE TABLE `instrument_set_items` (
  `set_item_id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instrument_types`
--

CREATE TABLE `instrument_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data untuk tipe atau kategori instrumen.';

--
-- Dumping data for table `instrument_types`
--

INSERT INTO `instrument_types` (`type_id`, `type_name`, `is_active`, `created_at`) VALUES
(1, 'Instrument Bedah', 1, '2025-06-26 15:39:21'),
(2, 'KANDUNGAN', 1, '2025-06-26 15:39:21'),
(3, 'VASKULER', 1, '2025-06-26 15:39:21'),
(4, 'SET PEDIATRIK', 1, '2025-06-26 15:39:21'),
(5, 'SET THT', 1, '2025-06-26 15:39:21'),
(6, 'CURRETE', 1, '2025-06-26 15:39:21'),
(7, 'Curret', 1, '2025-06-26 15:39:21'),
(8, 'Cutting', 1, '2025-06-26 15:39:21'),
(9, 'Instrument', 1, '2025-08-19 15:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `machine_id` int(11) NOT NULL,
  `machine_name` varchar(100) NOT NULL,
  `machine_code` varchar(10) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`machine_id`, `machine_name`, `machine_code`, `is_active`) VALUES
(3, 'Autoclave (Steam Sterilizer)', 'STEAM', 1),
(4, 'ETO Sterilizer (Ethylene Oxide)', 'ETO', 1),
(5, 'Plasma Sterilizer (H2O2 Plasma)', 'PLASMA', 1),
(6, 'Dry Heat Sterilizer', 'DH', 1),
(7, 'Formaldehyde Sterilizer', 'LTSF', 1),
(8, 'Radiation Sterilizer (Gamma atau e-Beam)', 'RADI', 1);

-- --------------------------------------------------------

--
-- Table structure for table `packaging_types`
--

CREATE TABLE `packaging_types` (
  `packaging_type_id` int(11) NOT NULL,
  `packaging_name` varchar(100) NOT NULL,
  `shelf_life_days` int(11) NOT NULL COMMENT 'Masa kedaluwarsa standar dalam hari',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_in_days` int(11) DEFAULT NULL COMMENT 'Masa kedaluwarsa dalam hari untuk jenis kemasan ini'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `packaging_types`
--

INSERT INTO `packaging_types` (`packaging_type_id`, `packaging_name`, `shelf_life_days`, `is_active`, `created_at`, `expiry_in_days`) VALUES
(1, 'Linen', 7, 1, '2025-09-05 16:26:45', NULL),
(2, 'Pouches', 30, 1, '2025-09-05 16:27:07', NULL),
(3, 'Linen 2', 7, 1, '2025-09-06 20:05:58', 7);

-- --------------------------------------------------------

--
-- Table structure for table `print_queue`
--

CREATE TABLE `print_queue` (
  `queue_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `load_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_cycles`
--

CREATE TABLE `sterilization_cycles` (
  `cycle_id` int(11) NOT NULL,
  `machine_name` varchar(255) NOT NULL,
  `cycle_number` varchar(100) NOT NULL,
  `cycle_date` datetime NOT NULL,
  `operator_user_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'menunggu_validasi' COMMENT 'completed, failed, menunggu_validasi',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_loads`
--

CREATE TABLE `sterilization_loads` (
  `load_id` int(11) NOT NULL,
  `load_name` varchar(255) NOT NULL COMMENT 'Nama deskriptif untuk muatan, misal: Autoklaf A - Pagi 25/06',
  `notes` text DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `machine_id` int(11) DEFAULT NULL,
  `load_type` enum('Rutin','Cito/Darurat','Jadwal Operasi') NOT NULL DEFAULT 'Rutin' COMMENT 'Jenis muatan untuk klasifikasi',
  `priority` enum('Normal','Tinggi','Urgent') NOT NULL DEFAULT 'Normal' COMMENT 'Prioritas pengerjaan muatan',
  `destination_department_id` int(11) DEFAULT NULL,
  `packaging_type_id` int(11) DEFAULT NULL,
  `cycle_id` int(11) DEFAULT NULL COMMENT 'Diisi saat muatan dijalankan dalam siklus',
  `status` varchar(50) NOT NULL DEFAULT 'persiapan' COMMENT 'persiapan, berjalan, menunggu_validasi, selesai, gagal',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_load_items`
--

CREATE TABLE `sterilization_load_items` (
  `load_item_id` int(11) NOT NULL,
  `load_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'Bisa merujuk ke instrument_id atau set_id',
  `item_type` enum('instrument','set') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `item_snapshot` text DEFAULT NULL COMMENT 'JSON snapshot dari isi set pada saat ditambahkan ke muatan, memungkinkan modifikasi on-the-fly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_records`
--

CREATE TABLE `sterilization_records` (
  `record_id` int(11) NOT NULL,
  `label_unique_id` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `load_id` int(11) DEFAULT NULL,
  `destination_department_id` int(11) DEFAULT NULL,
  `load_item_id` int(11) DEFAULT NULL,
  `item_type` enum('instrument','set') NOT NULL,
  `label_title` varchar(255) NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `status` varchar(25) NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'Tanggal dan waktu label ditandai sebagai digunakan',
  `validated_by_user_id` int(11) DEFAULT NULL COMMENT 'ID pengguna yang melakukan validasi (mengubah status dari pending_validation ke active)',
  `validated_at` timestamp NULL DEFAULT NULL COMMENT 'Tanggal dan waktu label divalidasi dan diaktifkan',
  `notes` text DEFAULT NULL,
  `label_items_snapshot` text DEFAULT NULL COMMENT 'JSON snapshot dari item dan kuantitas untuk label ini jika item_type adalah set',
  `print_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, printed',
  `action_ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address from public action',
  `action_user_agent` text DEFAULT NULL COMMENT 'User agent details from public action',
  `usage_proof_filename` varchar(255) DEFAULT NULL,
  `issue_proof_filename` varchar(255) DEFAULT NULL,
  `print_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Menghitung berapa kali label dicetak'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Judul atau nama deskriptif untuk label ini, diberikan oleh pengguna.';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff','supervisor') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', NULL, '$2y$10$Lz6oPEt0sw95yFnVm6YY7ucuYsbjZcTzIaLgvnQfaKRM2NRT/APp.', 'Administrator', 'admin', '2025-05-29 09:08:25'),
(5, 'user', NULL, '$2y$10$QGXYpghwjr.0l/gmV792qeA53h6W5nz7gQJrU38N1EZHIqn4c8DEa', 'Staff CSSD', 'staff', '2025-06-11 00:06:39'),
(6, 'spv', NULL, '$2y$10$bLBMHCgAzEdfLZjFvAlPpe8.cW4b7zwAL7NJzmcQj.mxxyAa49U/e', 'Supervisor', 'supervisor', '2025-06-27 23:47:22'),
(7, 'staff', NULL, '$2y$10$8raTazNpLTXcHXR5Hy92x.5dyaOxT5ptrEIjq7YpnMrBDVntoTvLG', 'Staff EDR', 'staff', '2025-06-28 18:06:08'),
(8, 'demo', NULL, '$2y$10$ARZnWNzvHH0Pi8kKujUC0O/uSMLfs.Cr76uorKVFsk68XeMgqzalm', 'Demo', 'supervisor', '2025-07-01 09:21:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID pengguna penerima notifikasi',
  `icon` varchar(50) DEFAULT 'campaign',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name_unique` (`department_name`);

--
-- Indexes for table `instruments`
--
ALTER TABLE `instruments`
  ADD PRIMARY KEY (`instrument_id`),
  ADD UNIQUE KEY `instrument_code` (`instrument_code`),
  ADD KEY `created_by_user_id` (`created_by_user_id`),
  ADD KEY `fk_instrument_type` (`instrument_type_id`),
  ADD KEY `fk_instrument_department` (`department_id`);

--
-- Indexes for table `instrument_history`
--
ALTER TABLE `instrument_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `fk_history_instrument` (`instrument_id`),
  ADD KEY `fk_history_user` (`user_id`);

--
-- Indexes for table `instrument_sets`
--
ALTER TABLE `instrument_sets`
  ADD PRIMARY KEY (`set_id`),
  ADD UNIQUE KEY `set_code` (`set_code`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `instrument_set_items`
--
ALTER TABLE `instrument_set_items`
  ADD PRIMARY KEY (`set_item_id`),
  ADD UNIQUE KEY `unique_set_instrument` (`set_id`,`instrument_id`),
  ADD KEY `instrument_id` (`instrument_id`);

--
-- Indexes for table `instrument_types`
--
ALTER TABLE `instrument_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name_unique` (`type_name`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`machine_id`),
  ADD UNIQUE KEY `machine_code_unique` (`machine_code`);

--
-- Indexes for table `packaging_types`
--
ALTER TABLE `packaging_types`
  ADD PRIMARY KEY (`packaging_type_id`),
  ADD UNIQUE KEY `packaging_name` (`packaging_name`);

--
-- Indexes for table `print_queue`
--
ALTER TABLE `print_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD UNIQUE KEY `unique_record_id` (`record_id`),
  ADD KEY `fk_queue_to_record` (`record_id`),
  ADD KEY `fk_queue_to_load` (`load_id`);

--
-- Indexes for table `sterilization_cycles`
--
ALTER TABLE `sterilization_cycles`
  ADD PRIMARY KEY (`cycle_id`),
  ADD UNIQUE KEY `cycle_number_unique` (`cycle_number`),
  ADD KEY `fk_cycle_operator` (`operator_user_id`);

--
-- Indexes for table `sterilization_loads`
--
ALTER TABLE `sterilization_loads`
  ADD PRIMARY KEY (`load_id`),
  ADD KEY `fk_load_user` (`created_by_user_id`),
  ADD KEY `fk_load_cycle` (`cycle_id`),
  ADD KEY `fk_load_to_machine` (`machine_id`),
  ADD KEY `fk_load_to_department` (`destination_department_id`),
  ADD KEY `packaging_type_id` (`packaging_type_id`);

--
-- Indexes for table `sterilization_load_items`
--
ALTER TABLE `sterilization_load_items`
  ADD PRIMARY KEY (`load_item_id`),
  ADD KEY `fk_item_to_load` (`load_id`);

--
-- Indexes for table `sterilization_records`
--
ALTER TABLE `sterilization_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `label_unique_id` (`label_unique_id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`),
  ADD KEY `idx_item_id_type` (`item_id`,`item_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `fk_validated_by` (`validated_by_user_id`),
  ADD KEY `fk_record_to_cycle` (`cycle_id`),
  ADD KEY `fk_label_destination_dept` (`destination_department_id`),
  ADD KEY `fk_record_to_load` (`load_id`),
  ADD KEY `fk_record_to_load_item` (`load_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_read_status` (`user_id`,`is_read`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=594;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `instruments`
--
ALTER TABLE `instruments`
  MODIFY `instrument_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2050;

--
-- AUTO_INCREMENT for table `instrument_history`
--
ALTER TABLE `instrument_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `instrument_sets`
--
ALTER TABLE `instrument_sets`
  MODIFY `set_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `instrument_set_items`
--
ALTER TABLE `instrument_set_items`
  MODIFY `set_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1463;

--
-- AUTO_INCREMENT for table `instrument_types`
--
ALTER TABLE `instrument_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `machine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `packaging_types`
--
ALTER TABLE `packaging_types`
  MODIFY `packaging_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `print_queue`
--
ALTER TABLE `print_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=360;

--
-- AUTO_INCREMENT for table `sterilization_cycles`
--
ALTER TABLE `sterilization_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `sterilization_loads`
--
ALTER TABLE `sterilization_loads`
  MODIFY `load_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `sterilization_load_items`
--
ALTER TABLE `sterilization_load_items`
  MODIFY `load_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `sterilization_records`
--
ALTER TABLE `sterilization_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `instruments`
--
ALTER TABLE `instruments`
  ADD CONSTRAINT `fk_instrument_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_instrument_type` FOREIGN KEY (`instrument_type_id`) REFERENCES `instrument_types` (`type_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `instruments_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `instrument_history`
--
ALTER TABLE `instrument_history`
  ADD CONSTRAINT `fk_history_instrument` FOREIGN KEY (`instrument_id`) REFERENCES `instruments` (`instrument_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `instrument_sets`
--
ALTER TABLE `instrument_sets`
  ADD CONSTRAINT `instrument_sets_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `instrument_set_items`
--
ALTER TABLE `instrument_set_items`
  ADD CONSTRAINT `instrument_set_items_ibfk_1` FOREIGN KEY (`set_id`) REFERENCES `instrument_sets` (`set_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `instrument_set_items_ibfk_2` FOREIGN KEY (`instrument_id`) REFERENCES `instruments` (`instrument_id`) ON DELETE CASCADE;

--
-- Constraints for table `print_queue`
--
ALTER TABLE `print_queue`
  ADD CONSTRAINT `fk_queue_to_load` FOREIGN KEY (`load_id`) REFERENCES `sterilization_loads` (`load_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_queue_to_record` FOREIGN KEY (`record_id`) REFERENCES `sterilization_records` (`record_id`) ON DELETE CASCADE;

--
-- Constraints for table `sterilization_cycles`
--
ALTER TABLE `sterilization_cycles`
  ADD CONSTRAINT `fk_cycle_operator` FOREIGN KEY (`operator_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sterilization_loads`
--
ALTER TABLE `sterilization_loads`
  ADD CONSTRAINT `fk_load_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `sterilization_cycles` (`cycle_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_load_to_department` FOREIGN KEY (`destination_department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_load_to_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`machine_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_load_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `sterilization_load_items`
--
ALTER TABLE `sterilization_load_items`
  ADD CONSTRAINT `fk_item_to_load` FOREIGN KEY (`load_id`) REFERENCES `sterilization_loads` (`load_id`) ON DELETE CASCADE;

--
-- Constraints for table `sterilization_records`
--
ALTER TABLE `sterilization_records`
  ADD CONSTRAINT `fk_label_destination_dept` FOREIGN KEY (`destination_department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_record_to_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `sterilization_cycles` (`cycle_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_record_to_load` FOREIGN KEY (`load_id`) REFERENCES `sterilization_loads` (`load_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_validated_by` FOREIGN KEY (`validated_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sterilization_records_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
