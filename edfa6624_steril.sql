-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 04, 2025 at 05:25 PM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.3.22

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

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action_type`, `target_type`, `target_id`, `details`, `ip_address`, `log_timestamp`) VALUES
(1, 1, 'CREATE_INSTRUMENT', 'instrument', 84, 'Instrumen baru ditambahkan: \'Gunting jaringan\' (Kode: N/A).', '101.128.90.62', '2025-06-25 11:49:05'),
(2, 1, 'EXPORT_LABELS', NULL, NULL, 'Mengekspor data riwayat label ke CSV.', '101.128.90.62', '2025-06-25 12:22:32'),
(3, 1, 'MARK_LABEL_USED', 'label', 99, 'Label (UID: 3F5A3E3B) ditandai sebagai \'Digunakan\'.', '101.128.90.62', '2025-06-25 12:30:30'),
(4, 1, 'CREATE_LABEL', 'label', 111, 'Label baru dibuat untuk Instrument: \'BAK ALAT\' (UID: 83DAFFFC, Status: active).', '101.128.90.62', '2025-06-25 12:40:25'),
(5, 1, 'CREATE_SET', 'set', 47, 'Set instrumen baru ditambahkan: \'3123213123\' (Kode: N/A) dengan 0 item.', '101.128.90.62', '2025-06-25 13:47:53'),
(6, 1, 'CREATE_CYCLE', 'cycle', 1, 'Siklus sterilisasi baru ditambahkan: A-4324324 pada mesin Autoclave A', '101.128.90.62', '2025-06-25 15:39:29'),
(7, 1, 'CREATE_CYCLE', 'cycle', 2, 'Siklus sterilisasi baru ditambahkan: 123 pada mesin Autoclave B', '101.128.90.62', '2025-06-25 15:39:41'),
(8, 1, 'CREATE_CYCLE', 'cycle', 3, 'Siklus sterilisasi baru ditambahkan: adsa pada mesin Autoclave', '101.128.90.62', '2025-06-25 15:40:02'),
(9, 1, 'CREATE_CYCLE', 'cycle', 4, 'Siklus sterilisasi baru ditambahkan: 421412415 pada mesin Autoclave C', '101.128.90.62', '2025-06-25 15:49:17'),
(10, 1, 'DELETE_CYCLE', 'cycle', 1, 'Siklus sterilisasi dihapus: A-4324324 (ID: 1)', '101.128.90.62', '2025-06-25 15:49:58'),
(11, 1, 'DELETE_CYCLE', 'cycle', 2, 'Siklus sterilisasi dihapus: 123 (ID: 2)', '101.128.90.62', '2025-06-25 15:50:04'),
(12, 1, 'DELETE_CYCLE', 'cycle', 3, 'Siklus sterilisasi dihapus: adsa (ID: 3)', '101.128.90.62', '2025-06-25 15:50:07'),
(13, 1, 'CREATE_INSTRUMENT', 'instrument', 85, 'Instrumen baru ditambahkan: \'Gunting\' (Kode: test).', '101.128.90.62', '2025-06-25 16:40:14'),
(14, 1, 'CREATE_LABEL', 'label', 112, 'Label baru dibuat untuk Instrument: \'ABORTUS TANG BENGKOK\' (UID: B1A8479C, Status: active, Siklus ID: 4).', '101.128.90.62', '2025-06-25 16:49:52'),
(15, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 70, 'Status instrumen (ID: 70) diubah menjadi \'rusak\'. Catatan: Test', '101.128.90.62', '2025-06-25 17:00:33'),
(16, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 71, 'Status instrumen (ID: 71) diubah menjadi \'sterilisasi\'.', '101.128.90.62', '2025-06-25 17:00:46'),
(17, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 5, 'Status instrumen (ID: 5) diubah menjadi \'rusak\'. Catatan: GG', '101.128.90.62', '2025-06-25 17:01:40'),
(18, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 6, 'Status instrumen (ID: 6) diubah menjadi \'sterilisasi\'.', '101.128.90.62', '2025-06-25 17:04:00'),
(19, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 6, 'Status instrumen (ID: 6) diubah menjadi \'perbaikan\'.', '101.128.90.62', '2025-06-25 17:04:53'),
(20, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 70, 'Status instrumen (ID: 70) diubah menjadi \'tersedia\'.', '101.128.90.62', '2025-06-26 00:18:35'),
(21, 1, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 71, 'Status instrumen (ID: 71) diubah menjadi \'tersedia\'.', '101.128.90.62', '2025-06-26 00:20:58'),
(22, 1, 'DELETE_SET', 'set', 47, 'Set instrumen dihapus: \'3123213123\' (ID: 47).', '36.65.118.24', '2025-06-26 04:14:06'),
(23, 1, 'CREATE_LABEL', 'label', 113, 'Label baru dibuat untuk Set: \'ABORTUS TANG BENGKOK\' (UID: CC5739BA, Status: active, Siklus ID: 4).', '36.65.118.24', '2025-06-26 06:32:06'),
(24, 1, 'TOGGLE_INSTRUMENT_TYPE_STATUS', NULL, NULL, 'Status diubah untuk ID: 7', '101.128.90.62', '2025-06-26 15:44:43'),
(25, 1, 'TOGGLE_INSTRUMENT_TYPE_STATUS', NULL, NULL, 'Status diubah untuk ID: 7', '101.128.90.62', '2025-06-26 15:44:47'),
(26, 1, 'CREATE_LABEL', 'label', 114, 'Label baru dibuat untuk Set: \'Set Lahiran Caesar\' (UID: 40CA7683, Status: active, Siklus ID: 4).', '101.128.90.62', '2025-06-26 16:00:55'),
(27, 1, 'CREATE_LABEL', 'label', 115, 'Label baru dibuat untuk Set: \'Set Bedah Mayor\' (UID: B5392700, Status: active, Siklus ID: 4).', '101.128.90.62', '2025-06-27 02:10:47'),
(28, 1, 'CREATE_LABEL', 'label', 116, 'Label baru dibuat untuk Set: \'Ellys klem\' (UID: FA67B4FC, Status: active, Siklus ID: 4).', '101.128.90.62', '2025-06-27 02:13:54'),
(29, 1, 'CREATE_LABEL', 'label', 117, 'Label baru dibuat untuk Set: \'SET BEDAH ORTHO\' (UID: C1BD61FE).', '101.128.90.62', '2025-06-27 02:17:39'),
(30, 1, 'CREATE_LABEL', 'label', 118, 'Label baru dibuat untuk Instrument: \'Testing\' (UID: 6F49299D).', '101.128.90.62', '2025-06-27 02:19:15'),
(31, 1, 'CREATE_LABEL', 'label', 119, 'Label baru dibuat untuk Instrument: \'Nama judul\' (UID: F9141CE8).', '101.128.90.62', '2025-06-27 02:25:38'),
(32, 1, 'CREATE_LABEL', 'label', 120, 'Label baru dibuat untuk Instrument: \'Namaaaa\' (UID: 5342B291).', '101.128.90.62', '2025-06-27 02:31:22'),
(33, 1, 'CREATE_LABEL', 'label', 121, 'Label baru dibuat untuk Instrument: \'Namaaaa\' (UID: C0281A66).', '101.128.90.62', '2025-06-27 02:34:54'),
(34, 1, 'CREATE_LABEL', 'label', 122, 'Label baru dibuat untuk Set: \'Namaaaa\' (UID: 46977432).', '101.128.90.62', '2025-06-27 02:37:54'),
(35, 1, 'CREATE_LABEL', 'label', 123, 'Label baru dibuat untuk Set: \'3242432\' (UID: CB3B9D60).', '101.128.90.62', '2025-06-27 02:42:44'),
(36, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 1) diproses dan ditautkan ke Siklus baru (ID: 5).', '101.128.90.62', '2025-06-27 04:23:18'),
(37, 1, 'RECALL_LOAD', NULL, NULL, 'Siklus (ID: 5) gagal. 0 label dari Muatan ID: 1 ditarik kembali (recalled).', '101.128.90.62', '2025-06-27 04:23:48'),
(38, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 5) divalidasi. Hasil: failed', '101.128.90.62', '2025-06-27 04:23:48'),
(39, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 3) diproses dan ditautkan ke Siklus baru (ID: 7).', '101.128.90.62', '2025-06-27 04:25:41'),
(40, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 7) divalidasi. Hasil: completed', '101.128.90.62', '2025-06-27 04:25:49'),
(41, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 7 label untuk Muatan ID: 3.', '101.128.90.62', '2025-06-27 04:25:52'),
(42, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 7 label untuk Muatan ID: 3.', '101.128.90.62', '2025-06-27 04:28:11'),
(43, 1, 'CREATE_DEPARTMENT', NULL, NULL, 'Master data Departemen baru ditambahkan: Administrasi', '101.128.90.62', '2025-06-27 04:35:52'),
(44, 1, 'CREATE_MACHINE', NULL, NULL, 'Master data Mesin baru ditambahkan: Autoklaf A (Kode: AA)', '101.128.90.62', '2025-06-27 04:39:00'),
(45, 1, 'CREATE_LOAD', 'load', 4, 'Muatan baru dibuat: AA-270625-PAGI - Alat Perang', '101.128.90.62', '2025-06-27 04:39:17'),
(46, 1, 'CREATE_LOAD', 'load', 5, 'Muatan baru dibuat: AA-270625-SIANG - Alat Cukur', '101.128.90.62', '2025-06-27 04:41:57'),
(47, 1, 'CREATE_LOAD', 'load', 6, 'Muatan baru dibuat: AA-270625-SORE - Alat Bedah', '101.128.90.62', '2025-06-27 04:47:46'),
(48, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 6) diproses dan ditautkan ke Siklus baru (ID: 8) dengan nomor AA-270625-01.', '101.128.90.62', '2025-06-27 05:20:01'),
(49, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 8) divalidasi. Hasil: completed', '101.128.90.62', '2025-06-27 05:21:08'),
(50, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-27 05:21:11'),
(51, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-27 08:49:25'),
(52, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-27 08:51:06'),
(53, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-27 08:57:04'),
(54, 1, 'CREATE_LOAD', 'load', 7, 'Muatan baru dibuat: AA-270625-MALAM - Alat Perang', '101.128.90.62', '2025-06-27 10:07:13'),
(55, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 7) diproses dan ditautkan ke Siklus baru (ID: 9) dengan nomor AA-270625-02.', '101.128.90.62', '2025-06-27 10:07:22'),
(56, 1, 'EXPORT_LABELS', NULL, NULL, 'Mengekspor data riwayat label ke CSV.', '101.128.90.62', '2025-06-27 15:09:58'),
(57, 1, 'CREATE_USER', 'user', 6, 'Pengguna baru ditambahkan: spv (Nama: Supervisor, Peran: supervisor).', '101.128.90.62', '2025-06-27 23:47:22'),
(58, 6, 'CREATE_LOAD', 'load', 8, 'Muatan baru dibuat: AA-280625-PAGI - ABC', '101.128.90.62', '2025-06-27 23:56:04'),
(59, 6, 'CREATE_LOAD', 'load', 9, 'Muatan baru dibuat: AA-280625-PAGI - ABC', '101.128.90.62', '2025-06-27 23:56:16'),
(60, NULL, 'PUBLIC_MARK_USED', 'label', 147, 'Label (UID: B1D590CD) ditandai \'Digunakan\' via aksi publik dengan verifikasi PIN.', '101.128.90.62', '2025-06-28 03:30:45'),
(61, NULL, 'PUBLIC_MARK_USED', 'label', 149, 'Label (UID: 5F8D3F3D) ditandai \'Digunakan\' via aksi publik dengan verifikasi PIN.', '101.128.90.62', '2025-06-28 03:38:42'),
(62, 5, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-28 06:22:02'),
(63, 5, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-28 06:22:26'),
(64, 5, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-28 06:22:33'),
(65, 5, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-28 06:22:41'),
(66, 5, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-06-28 06:22:46'),
(67, 5, 'CREATE_LOAD', 'load', 10, 'Muatan baru dibuat: AA-280625-03', '101.128.90.62', '2025-06-28 06:27:56'),
(68, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 10) diproses dan ditautkan ke Siklus baru (ID: 10) dengan nomor AA-280625-01.', '101.128.90.62', '2025-06-28 08:13:27'),
(69, 5, 'CREATE_LOAD', 'load', 11, 'Muatan baru dibuat: MUATAN-280625-02', '101.128.90.62', '2025-06-28 08:49:58'),
(70, 5, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 11) diproses dan ditautkan ke Siklus baru (ID: 11) dengan nomor SIKLUS-AA-280625-01.', '101.128.90.62', '2025-06-28 08:50:22'),
(71, 1, 'CREATE_LOAD', 'load', 12, 'Muatan baru dibuat: MUATAN-280625-03', '101.128.90.62', '2025-06-28 08:53:36'),
(72, 1, 'PROCESS_LOAD', NULL, NULL, 'Muatan (ID: 12) diproses dan ditautkan ke Siklus baru (ID: 12) dengan nomor SIKLUS-AA-280625-02.', '101.128.90.62', '2025-06-28 08:53:57'),
(73, 6, 'CREATE_LOAD', 'load', 13, 'Muatan baru dibuat: MUATAN-280625-04', '101.128.90.62', '2025-06-28 09:25:28'),
(74, 6, 'MERGE_LOAD_TO_CYCLE', NULL, NULL, 'Muatan ID: 13 digabungkan ke Siklus ID: 12.', '101.128.90.62', '2025-06-28 09:26:08'),
(75, 1, 'CREATE_LOAD', 'load', 14, 'Muatan baru dibuat: MUATAN-280625-05', '101.128.90.62', '2025-06-28 09:40:29'),
(76, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 13, No: SIKLUS-AA-280625-03) dibuat untuk Muatan ID: 14.', '101.128.90.62', '2025-06-28 09:41:00'),
(77, 1, 'CREATE_LOAD', 'load', 15, 'Muatan baru dibuat: MUATAN-280625-06', '101.128.90.62', '2025-06-28 10:01:46'),
(78, 1, 'UPDATE_LOAD', 'load', 15, 'Detail muatan (ID: 15) telah diperbarui via modal.', '101.128.90.62', '2025-06-28 10:24:27'),
(79, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 14, No: SIKLUS-AA-280625-04) dibuat untuk Muatan ID: 15.', '101.128.90.62', '2025-06-28 10:27:05'),
(80, 1, 'UPDATE_LOAD', 'load', 9, 'Detail muatan (ID: 9) telah diperbarui via modal.', '101.128.90.62', '2025-06-28 10:42:48'),
(81, 1, 'CREATE_MACHINE', NULL, NULL, 'Master data Mesin baru ditambahkan: Autoklaf B (Kode: AB)', '101.128.90.62', '2025-06-28 17:25:46'),
(82, 1, 'TOGGLE_SESSION_STATUS', NULL, NULL, 'Status diubah untuk ID: 1 di tabel sessions', '101.128.90.62', '2025-06-28 17:28:02'),
(83, 1, 'TOGGLE_SESSION_STATUS', NULL, NULL, 'Status diubah untuk ID: 1 di tabel sessions', '101.128.90.62', '2025-06-28 17:28:05'),
(84, 1, 'TOGGLE_SESSION_STATUS', NULL, NULL, 'Status diubah untuk ID: 1 di tabel sessions', '101.128.90.62', '2025-06-28 17:29:57'),
(85, 1, 'TOGGLE_SESSION_STATUS', NULL, NULL, 'Status diubah untuk ID: 1 di tabel sessions', '101.128.90.62', '2025-06-28 17:29:59'),
(86, 1, 'CREATE_INSTRUMENT', 'instrument', 86, 'Instrumen baru ditambahkan: \'Kasa\' (Kode: JOJOLE).', '101.128.90.62', '2025-06-28 17:31:52'),
(87, 1, 'CREATE_USER', 'user', 7, 'Pengguna baru ditambahkan: staff (Nama: Staff EDR, Peran: staff).', '101.128.90.62', '2025-06-28 18:06:08'),
(88, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk machine ID: 2 di tabel machines', '101.128.90.62', '2025-06-28 20:41:31'),
(89, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk machine ID: 2 di tabel machines', '101.128.90.62', '2025-06-28 20:41:33'),
(90, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 15, No: SIKLUS-AA-290625-01) dibuat untuk Muatan ID: 9.', '101.128.90.62', '2025-06-28 20:53:04'),
(91, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 45, 'Snapshot untuk load_item_id: 45 diperbarui.', '101.128.90.62', '2025-06-29 01:39:09'),
(92, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 45, 'Snapshot untuk load_item_id: 45 diperbarui.', '101.128.90.62', '2025-06-29 01:39:22'),
(93, 1, 'CREATE_LOAD', 'load', 16, 'Muatan baru dibuat: MUATAN-290625-01', '146.75.160.27', '2025-06-29 02:29:25'),
(94, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 02:49:34'),
(95, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:08:08'),
(96, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:21:39'),
(97, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:30:14'),
(98, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:33:30'),
(99, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:44:27'),
(100, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 03:56:45'),
(101, 1, 'CREATE_LOAD', 'load', 17, 'Muatan baru dibuat: MUATAN-290625-02', '101.128.90.62', '2025-06-29 03:57:51'),
(102, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 49, 'Snapshot untuk load_item_id: 49 diperbarui.', '101.128.90.62', '2025-06-29 04:26:20'),
(103, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 49, 'Snapshot untuk load_item_id: 49 diperbarui.', '101.128.90.62', '2025-06-29 04:27:12'),
(104, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 49, 'Snapshot untuk load_item_id: 49 diperbarui.', '101.128.90.62', '2025-06-29 04:28:11'),
(105, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 49, 'Snapshot untuk load_item_id: 49 diperbarui.', '101.128.90.62', '2025-06-29 04:30:16'),
(106, 1, 'CREATE_LOAD', 'load', 18, 'Muatan baru dibuat: MUATAN-290625-03', '101.128.90.62', '2025-06-29 04:31:37'),
(107, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 51, 'Snapshot untuk load_item_id: 51 diperbarui.', '101.128.90.62', '2025-06-29 04:32:08'),
(108, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 16, No: SIKLUS-AA-290625-02) dibuat untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 04:32:45'),
(109, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 16) divalidasi dengan hasil: completed', '101.128.90.62', '2025-06-29 04:32:58'),
(110, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 04:42:10'),
(111, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 04:43:38'),
(112, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 04:44:04'),
(113, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 04:47:14'),
(114, 6, 'UPDATE_INSTRUMENT_STATUS', 'instrument', 70, 'Status instrumen (ID: 70) diubah menjadi \'rusak\'. Catatan: Patah', '101.128.90.62', '2025-06-29 05:22:36'),
(115, 6, 'CREATE_LOAD', 'load', 19, 'Muatan baru dibuat: MUATAN-290625-04', '101.128.90.62', '2025-06-29 15:14:10'),
(116, 6, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 54, 'Snapshot untuk load_item_id: 54 diperbarui.', '101.128.90.62', '2025-06-29 15:16:33'),
(117, 6, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 54, 'Snapshot untuk load_item_id: 54 diperbarui.', '101.128.90.62', '2025-06-29 15:25:51'),
(118, 6, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-06-29 16:41:44'),
(119, 6, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 16:48:24'),
(120, 6, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-06-29 16:49:29'),
(121, 6, 'CREATE_LOAD', 'load', 20, 'Muatan baru dibuat: MUATAN-300625-05', '101.128.90.62', '2025-06-29 17:54:39'),
(122, 6, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '172.225.78.215', '2025-06-29 18:26:50'),
(123, 1, 'UPDATE_LOAD', 'load', 20, 'Detail muatan (ID: 20) telah diperbarui via modal.', '101.128.90.62', '2025-06-30 14:12:52'),
(124, 1, 'UPDATE_LOAD', 'load', 20, 'Detail muatan (ID: 20) telah diperbarui via modal.', '101.128.90.62', '2025-06-30 14:13:07'),
(125, 1, 'CREATE_INSTRUMENT', 'instrument', 87, 'Instrumen baru ditambahkan: \'ABC\' (Kode: INST-1751295586).', '101.128.90.62', '2025-06-30 14:59:46'),
(126, 1, 'CREATE_INSTRUMENT', 'instrument', 88, 'Instrumen baru ditambahkan: \'Cukil\' (Kode: N/A).', '101.128.90.62', '2025-06-30 15:23:32'),
(127, 1, 'UPDATE_INSTRUMENT', 'instrument', 87, 'Data instrumen diperbarui untuk: \'ABC\' (ID: 87).', '101.128.90.62', '2025-06-30 16:04:34'),
(128, 1, 'UPDATE_INSTRUMENT', 'instrument', 87, 'Data instrumen diperbarui untuk: \'ABC\' (ID: 87).', '101.128.90.62', '2025-06-30 16:04:38'),
(129, 1, 'UPDATE_INSTRUMENT', 'instrument', 87, 'Data instrumen diperbarui untuk: \'ABC\' (ID: 87).', '101.128.90.62', '2025-06-30 16:09:10'),
(130, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk type ID: 7 di tabel instrument_types', '101.128.90.62', '2025-06-30 16:33:41'),
(131, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk type ID: 7 di tabel instrument_types', '101.128.90.62', '2025-06-30 16:33:44'),
(132, 1, 'CREATE_INSTRUMENT', 'instrument', 89, 'Instrumen baru ditambahkan: \'ABC\'.', '36.78.129.255', '2025-07-01 03:01:43'),
(133, 1, 'CREATE_INSTRUMENT', 'instrument', 90, 'Instrumen baru ditambahkan: \'ABC\'.', '36.78.129.255', '2025-07-01 03:02:03'),
(134, 6, 'RECALL_CYCLE', NULL, NULL, 'Siklus (ID: 15) gagal. 0 label ditarik kembali (recalled).', '36.78.129.255', '2025-07-01 07:36:45'),
(135, 6, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 15) divalidasi dengan hasil: failed', '36.78.129.255', '2025-07-01 07:36:45'),
(136, 1, 'UPDATE_INSTRUMENT', 'instrument', 87, 'Data instrumen diperbarui untuk: \'ABC\' (ID: 87).', '36.78.129.255', '2025-07-01 08:15:26'),
(137, 5, 'CREATE_LOAD', 'load', 21, 'Muatan baru dibuat: MUATAN-010725-01', '36.78.129.255', '2025-07-01 08:48:29'),
(138, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 56, 'Snapshot untuk load_item_id: 56 diperbarui.', '36.78.129.255', '2025-07-01 08:50:16'),
(139, 5, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 17, No: SIKLUS-AA-010725-01) dibuat untuk Muatan ID: 21.', '36.78.129.255', '2025-07-01 08:50:27'),
(140, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 55, 'Snapshot untuk load_item_id: 55 diperbarui.', '36.78.129.255', '2025-07-01 09:02:41'),
(141, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 58, 'Snapshot untuk load_item_id: 58 diperbarui.', '36.78.129.255', '2025-07-01 09:07:41'),
(142, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 59, 'Snapshot untuk load_item_id: 59 diperbarui.', '36.78.129.255', '2025-07-01 09:11:55'),
(143, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 59, 'Snapshot untuk load_item_id: 59 diperbarui.', '36.78.129.255', '2025-07-01 09:12:01'),
(144, 5, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 58, 'Snapshot untuk load_item_id: 58 diperbarui.', '36.78.129.255', '2025-07-01 09:19:29'),
(145, 1, 'CREATE_USER', 'user', 8, 'Pengguna baru ditambahkan: demo (Nama: Demo, Peran: supervisor).', '36.78.129.255', '2025-07-01 09:21:57'),
(146, 1, 'DELETE_INSTRUMENT', 'instrument', 89, 'Instrumen dihapus: \'ABC\' (ID: 89).', '36.78.129.255', '2025-07-01 09:23:00'),
(147, 1, 'DELETE_INSTRUMENT', 'instrument', 90, 'Instrumen dihapus: \'ABC\' (ID: 90).', '36.78.129.255', '2025-07-01 09:23:57'),
(148, 1, 'CREATE_INSTRUMENT', 'instrument', 91, 'Instrumen baru ditambahkan: \'ABC\'.', '36.78.129.255', '2025-07-01 09:34:30'),
(149, 1, 'DELETE_INSTRUMENT', 'instrument', 91, 'Instrumen dihapus: \'ABC\' (ID: 91).', '36.78.129.255', '2025-07-01 09:37:22'),
(150, 1, 'CREATE_INSTRUMENT', 'instrument', 92, 'Instrumen baru ditambahkan: \'Abc\'.', '104.28.118.70', '2025-07-01 09:43:11'),
(151, 1, 'DELETE_INSTRUMENT', 'instrument', 92, 'Instrumen dihapus: \'Abc\' (ID: 92).', '36.78.129.255', '2025-07-01 09:45:47'),
(152, 1, 'CREATE_INSTRUMENT', 'instrument', 93, 'Instrumen baru ditambahkan: \'Abca\'.', '104.28.121.63', '2025-07-01 09:46:27'),
(153, 1, 'DELETE_INSTRUMENT', 'instrument', 93, 'Instrumen dihapus: \'Abca\' (ID: 93).', '36.78.129.255', '2025-07-01 09:49:18'),
(154, 1, 'CREATE_INSTRUMENT', 'instrument', 94, 'Instrumen baru ditambahkan: \'abc\'.', '36.78.129.255', '2025-07-01 09:51:21'),
(155, 6, 'CREATE_INSTRUMENT', 'instrument', 95, 'Instrumen baru ditambahkan: \'abc\'.', '101.128.90.62', '2025-07-01 13:07:22'),
(156, 6, 'CREATE_INSTRUMENT', 'instrument', 96, 'Instrumen baru ditambahkan: \'abv\'.', '101.128.90.62', '2025-07-01 13:07:38'),
(157, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: MUATAN-290625-01 (ID: 16).', '101.128.90.62', '2025-07-01 13:09:19'),
(158, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: AA-280625-PAGI - ABC (ID: 8).', '101.128.90.62', '2025-07-01 13:09:46'),
(159, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: AA-270625-SIANG - Alat Cukur (ID: 5).', '101.128.90.62', '2025-07-01 13:13:35'),
(160, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: AA-270625-PAGI - Alat Perang (ID: 4).', '101.128.90.62', '2025-07-01 13:13:43'),
(161, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: Autoklaf A - Sesi Sore 30 Juni 2025 (ID: 2).', '101.128.90.62', '2025-07-01 13:13:49'),
(162, 6, 'DELETE_LOAD', NULL, NULL, 'Muatan dihapus: MUATAN-290625-02 (ID: 17).', '101.128.90.62', '2025-07-01 13:13:54'),
(163, 1, 'CREATE_DEPARTMENT', NULL, NULL, 'Master data Departemen baru ditambahkan: EDR', '101.128.90.62', '2025-07-01 13:23:35'),
(164, 1, 'CREATE_DEPARTMENT', NULL, NULL, 'Master data Departemen baru ditambahkan: BOH', '101.128.90.62', '2025-07-01 13:23:44'),
(165, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk department ID: 10 di tabel departments', '101.128.90.62', '2025-07-01 13:23:50'),
(166, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk department ID: 9 di tabel departments', '101.128.90.62', '2025-07-01 13:23:53'),
(167, 1, 'DELETE_INSTRUMENT', 'instrument', 95, 'Instrumen dihapus: \'abc\' (ID: 95).', '101.128.90.62', '2025-07-01 13:40:22'),
(168, 1, 'DELETE_INSTRUMENT', 'instrument', 94, 'Instrumen dihapus: \'abc\' (ID: 94).', '101.128.90.62', '2025-07-01 13:40:31'),
(169, 1, 'CREATE_USER', 'user', 9, 'Pengguna baru ditambahkan: 123 (Nama: 123, Peran: staff).', '101.128.90.62', '2025-07-01 14:14:20'),
(170, 1, 'DELETE_INSTRUMENT', 'instrument', 96, 'Instrumen dihapus: \'abv\' (ID: 96).', '101.128.90.62', '2025-07-01 14:16:27'),
(171, 1, 'CREATE_INSTRUMENT', 'instrument', 97, 'Instrumen baru ditambahkan: \'abc\'.', '101.128.90.62', '2025-07-01 15:29:51'),
(172, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 61, 'Snapshot untuk load_item_id: 61 diperbarui.', '101.128.90.62', '2025-07-01 16:27:11'),
(173, 1, 'CREATE_LOAD', 'load', 22, 'Muatan baru dibuat: MUATAN-010725-02', '101.128.90.62', '2025-07-01 16:27:31'),
(174, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 62, 'Snapshot untuk load_item_id: 62 diperbarui.', '101.128.90.62', '2025-07-01 16:27:52'),
(175, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 18, No: SIKLUS-AA-010725-02) dibuat untuk Muatan ID: 22.', '101.128.90.62', '2025-07-01 16:27:56'),
(176, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-07-01 16:30:45'),
(177, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-07-01 16:31:03'),
(178, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 2 label untuk Muatan ID: 18.', '101.128.90.62', '2025-07-01 16:31:13'),
(179, 1, 'CREATE_LOAD', 'load', 23, 'Muatan baru dibuat: MUATAN-010725-03', '101.128.90.62', '2025-07-01 16:34:05'),
(180, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 3 label untuk Muatan ID: 6.', '101.128.90.62', '2025-07-01 16:50:34'),
(181, 1, 'CREATE_INSTRUMENT', 'instrument', 98, 'Instrumen baru ditambahkan: \'test\'.', '101.128.90.62', '2025-07-01 17:29:05'),
(182, 1, 'DELETE_USER', 'user', 9, 'Pengguna dihapus: 123 (ID: 9).', '36.78.129.255', '2025-07-02 04:00:17'),
(183, NULL, 'PUBLIC_RECALL_LABEL', 'label', 112, 'Label (UID: B1A8479C) ditandai \'Recalled\' via aksi publik. Alasan: dsfsdfsd', '36.78.129.255', '2025-07-02 08:06:51'),
(184, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 18) divalidasi dengan hasil: completed', '36.78.129.255', '2025-07-02 09:23:12'),
(185, 6, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 19, No: SIKLUS-AA-030725-01) dibuat untuk Muatan ID: 23.', '36.78.129.255', '2025-07-03 03:45:06'),
(186, 1, 'CREATE_LOAD', 'load', 24, 'Muatan baru dibuat: MUATAN-030725-01 (Prioritas: Normal, Jenis: Rutin)', '36.78.129.255', '2025-07-03 05:01:51'),
(187, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 65, 'Snapshot untuk load_item_id: 65 diperbarui.', '101.128.90.62', '2025-07-03 14:15:25'),
(188, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 1 label untuk Muatan ID: 22.', '101.128.90.62', '2025-07-03 14:23:26'),
(189, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 20, No: SIKLUS-AA-030725-02) dibuat untuk Muatan ID: 24.', '101.128.90.62', '2025-07-03 14:40:46'),
(190, 1, 'CREATE_LOAD', 'load', 25, 'Muatan baru dibuat: MUATAN-030725-02', '101.128.90.62', '2025-07-03 14:40:59'),
(191, 1, 'MERGE_LOAD_TO_CYCLE', NULL, NULL, 'Muatan ID: 25 digabungkan ke Siklus ID: 17.', '101.128.90.62', '2025-07-03 14:41:19'),
(192, 1, 'CREATE_LOAD', 'load', 26, 'Muatan baru dibuat: MUATAN-030725-03', '101.128.90.62', '2025-07-03 14:43:45'),
(193, 1, 'CREATE_LOAD', 'load', 27, 'Muatan baru dibuat: MUATAN-030725-04', '101.128.90.62', '2025-07-03 14:43:55'),
(194, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 21, No: SIKLUS-AA-030725-03) dibuat untuk Muatan ID: 27.', '101.128.90.62', '2025-07-03 14:44:20'),
(195, 1, 'MERGE_LOAD_TO_CYCLE', NULL, NULL, 'Muatan ID: 26 digabungkan ke Siklus ID: 21.', '101.128.90.62', '2025-07-03 14:44:38'),
(196, 1, 'EXPORT_LABELS', NULL, NULL, 'Mengekspor data riwayat label ke CSV.', '101.128.90.62', '2025-07-03 15:46:36'),
(197, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-07-03 15:47:11'),
(198, 1, 'CREATE_SET', 'set', 48, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-321312).', '101.128.90.62', '2025-07-03 16:05:58'),
(199, 1, 'CREATE_SET', 'set', 49, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: N/A).', '101.128.90.62', '2025-07-03 16:08:45'),
(200, 1, 'CREATE_SET', 'set', 50, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: N/A).', '101.128.90.62', '2025-07-03 16:10:49'),
(201, 1, 'CREATE_SET', 'set', 51, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: N/A).', '101.128.90.62', '2025-07-03 16:12:10'),
(202, 1, 'CREATE_SET', 'set', 52, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-60053774).', '101.128.90.62', '2025-07-03 16:29:14'),
(203, 5, 'MARK_LABEL_USED', 'label', 186, 'Label (UID: E28852C0) ditandai sebagai \'Digunakan\'.', '101.128.90.62', '2025-07-03 17:15:17'),
(204, 1, 'EXPORT_LOADS', NULL, NULL, 'Mengekspor data riwayat muatan ke CSV.', '101.128.90.62', '2025-07-03 17:17:18'),
(205, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk department ID: 10 di tabel departments', '101.128.90.62', '2025-07-03 17:23:26'),
(206, 1, 'TOGGLE_MASTER_STATUS', NULL, NULL, 'Status diubah untuk department ID: 10 di tabel departments', '101.128.90.62', '2025-07-03 17:23:32'),
(207, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 54, 'Snapshot untuk load_item_id: 54 diperbarui.', '101.128.90.62', '2025-07-03 17:30:55'),
(208, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 54, 'Snapshot untuk load_item_id: 54 diperbarui.', '101.128.90.62', '2025-07-03 17:31:07'),
(209, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 54, 'Snapshot untuk load_item_id: 54 diperbarui.', '101.128.90.62', '2025-07-03 17:39:37'),
(210, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 55, 'Snapshot untuk load_item_id: 55 diperbarui.', '101.128.90.62', '2025-07-03 17:54:15'),
(211, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 61, 'Snapshot untuk load_item_id: 61 diperbarui.', '101.128.90.62', '2025-07-03 18:04:09'),
(212, 1, 'CREATE_SET', 'set', 53, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-66696367).', '101.128.90.62', '2025-07-03 18:18:21'),
(213, 1, 'CREATE_SET', 'set', 54, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-66725605).', '101.128.90.62', '2025-07-03 18:18:51'),
(214, 1, 'CREATE_SET', 'set', 55, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-66870632).', '101.128.90.62', '2025-07-03 18:21:15'),
(215, 1, 'CREATE_SET', 'set', 56, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-67226155).', '101.128.90.62', '2025-07-03 18:27:11'),
(216, 1, 'CREATE_SET', 'set', 57, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-67483744).', '101.128.90.62', '2025-07-03 18:31:26'),
(217, 1, 'CREATE_SET', 'set', 58, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68295127).', '101.128.90.62', '2025-07-03 18:45:02'),
(218, 1, 'CREATE_SET', 'set', 59, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68393638).', '101.128.90.62', '2025-07-03 18:46:37'),
(219, 1, 'CREATE_SET', 'set', 60, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68402294).', '101.128.90.62', '2025-07-03 18:46:46'),
(220, 1, 'CREATE_SET', 'set', 61, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68440488).', '101.128.90.62', '2025-07-03 18:47:25'),
(221, 1, 'CREATE_SET', 'set', 62, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68452394).', '101.128.90.62', '2025-07-03 18:48:12'),
(222, 1, 'CREATE_SET', 'set', 63, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-68769063).', '101.128.90.62', '2025-07-03 18:52:53'),
(223, 1, 'CREATE_SET', 'set', 64, 'Set instrumen baru dibuat (langkah 1): \'Set Exclusive\' (Kode: SET-69031134).', '101.128.90.62', '2025-07-03 18:57:17'),
(224, 1, 'CREATE_SET', 'set', 65, 'Set instrumen baru dibuat (langkah 1): \'Ffff\' (Kode: SET-39567335).', '114.10.153.193', '2025-07-04 14:32:58'),
(225, 1, 'CREATE_SET', 'set', 66, 'Set instrumen baru dibuat (langkah 1): \'Shshs\' (Kode: SET-39600869).', '114.10.153.193', '2025-07-04 14:33:25'),
(226, 1, 'UPDATE_SET', 'set', 66, 'Data set diperbarui untuk: \'Shshs\' (ID: 66).', '114.10.153.193', '2025-07-04 14:34:06'),
(227, 1, 'EXPORT_LABELS', NULL, NULL, 'Mengekspor data riwayat label ke CSV.', '114.10.153.193', '2025-07-04 14:35:21'),
(228, 1, 'CREATE_LOAD', 'load', 28, 'Muatan baru dibuat: MUATAN-050725-01', '101.128.90.62', '2025-07-04 17:04:28'),
(229, 1, 'UPDATE_LOAD_ITEM_SNAPSHOT', 'load_item', 72, 'Snapshot untuk load_item_id: 72 diperbarui.', '101.128.90.62', '2025-07-04 17:06:17'),
(230, 1, 'CREATE_CYCLE_FROM_LOAD', NULL, NULL, 'Siklus baru (ID: 22, No: SIKLUS-AA-050725-01) dibuat untuk Muatan ID: 28.', '101.128.90.62', '2025-07-04 17:07:03'),
(231, 1, 'VALIDATE_CYCLE', NULL, NULL, 'Siklus (ID: 22) divalidasi dengan hasil: completed', '101.128.90.62', '2025-07-04 17:07:27'),
(232, 1, 'GENERATE_LABELS', NULL, NULL, 'Membuat 4 label untuk Muatan ID: 28.', '101.128.90.62', '2025-07-04 17:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `app_notifications`
--

CREATE TABLE `app_notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'campaign',
  `target_role` enum('all','admin','staff') DEFAULT 'all',
  `is_published` tinyint(1) DEFAULT 1,
  `published_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_notifications`
--

INSERT INTO `app_notifications` (`notification_id`, `title`, `message`, `link`, `icon`, `target_role`, `is_published`, `published_at`) VALUES
(1, 'Fitur Baru: Laporan Kedaluwarsa!', 'Kini Anda dapat melihat laporan label yang akan dan sudah kedaluwarsa di menu Laporan.', 'label_history.php?status=expired', 'summarize', 'all', 1, '2025-05-30 15:22:39'),
(2, 'Pembaruan: Tampilan Cetak Thermal', 'Template cetak thermal kini lebih fleksibel dengan pengaturan ukuran kertas dan posisi QR Code.', 'settings.php', 'print', 'admin', 1, '2025-05-30 15:22:39'),
(3, 'Info: Pemeliharaan Sistem', 'Akan ada pemeliharaan sistem terjadwal pada hari Sabtu pukul 02:00 - 04:00 WIB.', NULL, 'engineering', 'all', 1, '2025-05-30 15:22:39');

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
('app_instance_name', 'Sterilabel', '2025-07-01 16:08:48'),
('app_logo_filename', '', '2025-07-03 04:46:14'),
('default_expiry_days', '7', '2025-06-01 18:25:14'),
('enable_pending_validation', '0', '2025-06-04 07:24:55'),
('print_template', 'thermal', '2025-06-01 18:09:46'),
('public_usage_pin', '$2y$10$T2wOaSoM1lJ2n4lzewESbeitJk4yr2Srz3S1F3rEhFQKwaAFvuHde', '2025-06-28 03:22:46'),
('show_app_name_beside_logo', '1', '2025-07-03 17:04:20'),
('show_status_block_on_detail_page', '0', '2025-06-04 16:15:13'),
('staff_can_manage_instruments', '0', '2025-06-28 05:51:00'),
('staff_can_manage_sets', '0', '2025-06-28 05:51:00'),
('staff_can_validate_cycles', '0', '2025-06-28 05:51:00'),
('staff_can_view_activity_log', '0', '2025-06-28 05:51:00'),
('thermal_custom_text_1', 'CSSD RSUD Dr. Soeroto', '2025-06-11 08:24:07'),
('thermal_custom_text_2', 'Ngawi', '2025-06-11 00:09:58'),
('thermal_fields_config', '{\"item_name\":{\"visible\":false,\"order\":2,\"hide_label\":true,\"custom_label\":\"\"},\"label_unique_id\":{\"visible\":false,\"order\":3,\"hide_label\":true,\"custom_label\":\"\"},\"label_title\":{\"visible\":true,\"order\":2,\"hide_label\":true,\"custom_label\":\"\"},\"created_at\":{\"visible\":true,\"order\":4,\"hide_label\":false,\"custom_label\":\"Prd\"},\"expiry_date\":{\"visible\":true,\"order\":5,\"hide_label\":false,\"custom_label\":\"Exp\"},\"used_at\":{\"visible\":false,\"order\":6,\"hide_label\":false,\"custom_label\":\"\"},\"validated_at\":{\"visible\":false,\"order\":7,\"hide_label\":false,\"custom_label\":\"\"},\"validator_username\":{\"visible\":false,\"order\":8,\"hide_label\":false,\"custom_label\":\"\"},\"creator_username\":{\"visible\":false,\"order\":9,\"hide_label\":false,\"custom_label\":\"\"},\"notes\":{\"visible\":false,\"order\":10,\"hide_label\":false,\"custom_label\":\"\"},\"custom_text_1\":{\"visible\":true,\"order\":1,\"hide_label\":true,\"custom_label\":\"\"},\"custom_text_2\":{\"visible\":false,\"order\":12,\"hide_label\":false,\"custom_label\":\"\"}}', '2025-06-27 02:43:07'),
('thermal_fields_visibility', '{\"item_name\":false,\"label_unique_id\":false,\"autoclave_cycle_id\":false,\"created_at\":false,\"expiry_date\":false,\"creator_username\":false,\"notes\":false}', '2025-05-29 16:26:33'),
('thermal_paper_height_mm', '70', '2025-07-03 16:58:21'),
('thermal_paper_width_mm', '70', '2025-05-31 09:39:00'),
('thermal_qr_position', 'top_left_aligned', '2025-06-09 15:47:11'),
('thermal_qr_size', 'large', '2025-06-09 15:46:20');

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
(10, 'BOH', 0, '2025-07-01 13:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `instruments`
--

CREATE TABLE `instruments` (
  `instrument_id` int(11) NOT NULL,
  `instrument_name` varchar(255) NOT NULL,
  `instrument_code` varchar(100) DEFAULT NULL,
  `instrument_type` varchar(100) DEFAULT NULL,
  `instrument_type_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'tersedia',
  `expiry_in_days` int(5) DEFAULT NULL COMMENT 'Masa kedaluwarsa standar untuk instrumen ini dalam hari. NULL berarti menggunakan pengaturan global.',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instruments`
--

INSERT INTO `instruments` (`instrument_id`, `instrument_name`, `instrument_code`, `instrument_type`, `instrument_type_id`, `department`, `department_id`, `notes`, `image_filename`, `status`, `expiry_in_days`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(5, 'Arteri Klem Bengkok Besar', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'rusak', NULL, 1, '2025-05-30 06:42:18', '2025-06-26 15:39:21'),
(6, 'Arteri klem bengkok kecil', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'perbaikan', NULL, 1, '2025-05-30 06:43:02', '2025-06-26 15:39:21'),
(7, 'Arteri klem koker bengkok besar', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:43:26', '2025-06-26 15:39:21'),
(8, 'Arteri klem koker bengkok kecil', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:43:43', '2025-06-26 15:39:21'),
(9, 'Arteri klem koker lurus besar', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:44:44', '2025-06-26 15:39:21'),
(10, 'Arteri klem koker lurus kecil', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:44:59', '2025-06-26 15:39:21'),
(11, 'Arteri klem lurus', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:45:26', '2025-06-26 15:39:21'),
(12, 'Bak instrumen', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:45:55', '2025-06-26 15:39:21'),
(13, 'Bak jarum', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:46:05', '2025-06-26 15:39:21'),
(14, 'Bekok / klem tuba', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:46:21', '2025-06-26 15:39:21'),
(15, 'Bengkok', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:46:38', '2025-06-26 15:39:21'),
(16, 'Depres tang', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:46:52', '2025-06-26 15:39:21'),
(17, 'Duk klem', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:47:02', '2025-06-26 15:39:21'),
(18, 'Ellys klem', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:47:15', '2025-06-26 15:39:21'),
(19, 'Gunting benang', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:47:31', '2025-06-26 15:39:21'),
(20, 'Gunting jaringan', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:47:40', '2025-06-26 15:39:21'),
(21, 'Gunting mest sembung', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:48:01', '2025-06-26 15:39:21'),
(22, 'Haag besar / roo haag', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:48:27', '2025-06-26 15:39:21'),
(23, 'Haag blast', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:48:37', '2025-06-26 15:39:21'),
(24, 'Haag kecil / langen hag', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:49:12', '2025-06-26 15:39:21'),
(25, 'Haag sedang', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:49:24', '2025-06-26 15:39:21'),
(26, 'Handel mess', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:49:42', '2025-06-26 15:39:21'),
(27, 'Kanul section', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:49:58', '2025-06-26 15:39:21'),
(28, 'Kogel tang / Tenakulum', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:50:18', '2025-06-26 15:39:21'),
(29, 'Kom betadin', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:50:35', '2025-06-26 15:39:21'),
(30, 'Micolik / Klem Peritoneum', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:51:04', '2025-06-26 15:39:21'),
(31, 'Nald Voeder', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:51:16', '2025-06-26 15:39:21'),
(32, 'Pinset Anatomis', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:51:27', '2025-06-26 15:39:21'),
(33, 'Pinset Cirurgis', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:51:50', '2025-06-26 15:39:21'),
(34, 'Pinset Ring', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:52:02', '2025-06-26 15:39:21'),
(35, 'Ring Klem / Ring Tang', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:52:16', '2025-06-26 15:39:21'),
(36, 'Spatel', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:52:24', '2025-06-26 15:39:21'),
(37, 'Stintang', NULL, 'Instrument Bedah', 1, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 1, '2025-05-30 06:52:32', '2025-06-26 15:39:21'),
(39, 'BOOR MIOMA', NULL, 'KANDUNGAN', 2, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:30:08', '2025-06-26 15:39:21'),
(40, 'TIMAN', NULL, 'KANDUNGAN', 2, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:30:58', '2025-06-26 15:39:21'),
(41, 'SPERNHAAG', NULL, 'VASKULER', 3, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:33:36', '2025-06-26 15:39:21'),
(42, 'BUSI', NULL, 'SET PEDIATRIK', 4, 'PEDIATRIK', 2, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:36:54', '2025-06-26 15:39:21'),
(43, 'BAK ALAT', NULL, 'SET PEDIATRIK', 4, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:37:16', '2025-06-26 15:39:21'),
(44, 'DARM KLEM', NULL, 'SET PEDIATRIK', 4, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:37:53', '2025-06-26 15:39:21'),
(45, 'HAAG CAKAR', NULL, 'SET PEDIATRIK', 4, 'PEDIATRIK', 2, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:38:28', '2025-06-26 15:39:21'),
(46, 'HAG OTOMATIS', NULL, 'SET PEDIATRIK', 4, 'PEDIATRIK', 2, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:38:45', '2025-06-26 15:39:21'),
(47, 'KLEM GASTER', NULL, 'SET PEDIATRIK', 4, 'PEDIATRIK', 2, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:39:06', '2025-06-26 15:39:21'),
(48, 'MIKOLIK', NULL, 'SET PEDIATRIK', 4, 'PEDIATRIK', 2, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:39:22', '2025-06-26 15:39:21'),
(49, 'GUNTING LANCIP', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:42:17', '2025-06-26 15:39:21'),
(50, 'GUNTING MATSEMBAUM', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:42:37', '2025-06-26 15:39:21'),
(51, 'HAAG GIGI', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:42:49', '2025-06-26 15:39:21'),
(52, 'HAAG MULUT OTOMATIS', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:03', '2025-06-26 15:39:21'),
(53, 'HAAG U', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:15', '2025-06-26 15:39:21'),
(54, 'HANDLE SAULDER', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:27', '2025-06-26 15:39:21'),
(55, 'HANDLE WAYER', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:36', '2025-06-26 15:39:21'),
(56, 'KEROK', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:43', '2025-06-26 15:39:21'),
(57, 'KLEM POLIP', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:43:55', '2025-06-26 15:39:21'),
(58, 'KLEM TONSIL', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:44:04', '2025-06-26 15:39:21'),
(59, 'KROM BENGKOK BESAR', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:44:18', '2025-06-26 15:39:21'),
(60, 'MATA BOOR', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:44:26', '2025-06-26 15:39:21'),
(61, 'PENGIKAT BENANG', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:44:40', '2025-06-26 15:39:21'),
(62, 'PISAU LANCIP', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:44:54', '2025-06-26 15:39:21'),
(63, 'PISAU SOULDER', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:45:13', '2025-06-26 15:39:21'),
(64, 'RESPARATORIUM', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:45:23', '2025-06-26 15:39:21'),
(65, 'SONDE', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:45:30', '2025-06-26 15:39:21'),
(66, 'SONDE ULIR', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:45:40', '2025-06-26 15:39:21'),
(67, 'TONGUE SPATEL', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:45:54', '2025-06-26 15:39:21'),
(68, 'TONSIL HAAG', NULL, 'SET THT', 5, 'THT', 3, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 05:46:04', '2025-06-26 15:39:21'),
(70, 'ABORTUS TANG BENGKOK', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'rusak', NULL, 5, '2025-06-11 06:26:26', '2025-06-29 05:22:36'),
(71, 'ABORTUS TANG LURUS', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:26:43', '2025-06-26 15:39:21'),
(72, 'BUSI BESAR', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:27:48', '2025-06-26 15:39:21'),
(73, 'BUSI KECIL', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:28:10', '2025-06-26 15:39:21'),
(74, 'CUCING', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:28:27', '2025-06-26 15:39:21'),
(75, 'CURET UTERUS', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:28:49', '2025-06-26 15:39:21'),
(76, 'HANDIE SPEKULUM', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:29:12', '2025-06-26 15:39:21'),
(77, 'KLEM PA', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:29:30', '2025-06-26 15:39:21'),
(78, 'KRITELER', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:29:49', '2025-06-26 15:39:21'),
(79, 'VAGINA SPEKULUM', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:30:22', '2025-06-26 15:39:21'),
(80, 'TENA KULUM', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:30:39', '2025-06-26 15:39:21'),
(81, 'UTERUS KLEM', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:31:00', '2025-06-26 15:39:21'),
(82, 'WEIGHT BANDUL', NULL, 'CURRETE', 6, 'IBS', 1, NULL, NULL, 'tersedia', NULL, 5, '2025-06-11 06:31:21', '2025-06-26 15:39:21'),
(85, 'Gunting', 'test', 'Instrument Bedah', 1, 'CSSD', 4, 'saadsad', NULL, 'tersedia', NULL, 1, '2025-06-25 16:40:14', '2025-06-26 15:39:21'),
(86, 'Kasa', 'JOJOLE', NULL, 1, NULL, 2, 'Catattt', NULL, 'tersedia', NULL, 1, '2025-06-28 17:31:52', '2025-06-28 17:31:52'),
(87, 'ABC', 'INST-999', NULL, 6, NULL, 4, 'CATATAN SI BOY', 'inst_6862b6a65f1f32.34462909.gif', 'tersedia', NULL, 1, '2025-06-30 14:59:46', '2025-07-01 08:15:26'),
(88, 'Cukil', NULL, NULL, 6, NULL, 4, 'Catantaan si boy', 'inst_6862abf45b9ad5.39175624.png', 'tersedia', NULL, 1, '2025-06-30 15:23:32', '2025-06-30 15:23:32'),
(97, 'abc', 'INST-83787748', NULL, 7, NULL, 8, NULL, NULL, 'tersedia', NULL, 1, '2025-07-01 15:29:51', '2025-07-01 15:29:51'),
(98, 'test', 'INST-90931825', NULL, 7, NULL, 8, NULL, NULL, 'tersedia', NULL, 1, '2025-07-01 17:29:05', '2025-07-01 17:29:05');

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

--
-- Dumping data for table `instrument_history`
--

INSERT INTO `instrument_history` (`history_id`, `instrument_id`, `changed_to_status`, `user_id`, `change_timestamp`, `notes`) VALUES
(1, 70, 'rusak', 1, '2025-06-25 17:00:33', 'Test'),
(2, 71, 'sterilisasi', 1, '2025-06-25 17:00:46', NULL),
(3, 5, 'rusak', 1, '2025-06-25 17:01:40', 'GG'),
(4, 6, 'sterilisasi', 1, '2025-06-25 17:04:00', NULL),
(5, 6, 'perbaikan', 1, '2025-06-25 17:04:53', NULL),
(6, 70, 'tersedia', 1, '2025-06-26 00:18:35', NULL),
(7, 71, 'tersedia', 1, '2025-06-26 00:20:58', NULL),
(8, 70, 'rusak', 6, '2025-06-29 05:22:36', 'Patah');

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

--
-- Dumping data for table `instrument_sets`
--

INSERT INTO `instrument_sets` (`set_id`, `set_name`, `set_code`, `special_instructions`, `expiry_in_days`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(12, 'SET BEDAH IBS', 'INSTALASI BEDAH SENTRAL', NULL, NULL, 5, '2025-06-11 05:28:21', '2025-06-11 05:28:21'),
(13, 'SET KANDUNGAN IBS', 'IBS', NULL, NULL, 5, '2025-06-11 05:32:22', '2025-06-11 06:04:38'),
(14, 'SET TKV/VASKULER IBS', 'VASKULER', NULL, NULL, 5, '2025-06-11 05:34:32', '2025-06-11 06:07:09'),
(15, 'SET PEDIATRIK IBS', NULL, NULL, NULL, 5, '2025-06-11 05:41:19', '2025-06-11 06:05:40'),
(16, 'SET THT IBS', 'THT', NULL, NULL, 5, '2025-06-11 05:49:20', '2025-06-11 06:06:53'),
(17, 'CURET IBS', NULL, NULL, NULL, 5, '2025-06-11 06:37:41', '2025-06-11 12:07:42'),
(18, 'IBS Kandungan 4', '110104', NULL, NULL, 5, '2025-06-11 08:10:13', '2025-06-15 05:52:27'),
(19, 'Set SC Perinatologi', '160101', NULL, NULL, 5, '2025-06-14 05:02:20', '2025-06-19 11:47:33'),
(20, 'IBS Kandungan 2', '110102', NULL, NULL, 5, '2025-06-15 05:51:52', '2025-06-15 05:51:52'),
(21, 'MAWAR Rawat luka kecil', '140101', NULL, NULL, 5, '2025-06-15 05:57:14', '2025-06-15 05:57:14'),
(22, 'IBS Bedah 1 bak panjang ram', '110201', NULL, NULL, 5, '2025-06-17 02:27:53', '2025-06-19 12:01:30'),
(23, 'IBS Pediatrik 1', '110301', NULL, NULL, 5, '2025-06-17 03:20:00', '2025-06-17 07:58:48'),
(24, 'IBS Kandungan 3', '110103', NULL, NULL, 5, '2025-06-17 07:55:09', '2025-06-17 07:55:09'),
(25, 'IBS Kandungan 7', '110107', NULL, NULL, 5, '2025-06-18 01:58:51', '2025-06-18 01:58:51'),
(26, 'IBS Kandungan 6', '110106', 'MASTER BAK INSTRUMEN', NULL, 5, '2025-06-18 02:08:50', '2025-06-18 02:08:50'),
(27, 'IBS Bedah 2', '110202', NULL, NULL, 5, '2025-06-18 04:47:29', '2025-06-18 04:47:29'),
(28, 'IBS Kandungan 1', '110101', NULL, NULL, 5, '2025-06-18 05:02:36', '2025-06-18 05:02:36'),
(29, 'IBS Kandungan 5', '110105', NULL, NULL, 5, '2025-06-18 05:11:39', '2025-06-18 05:11:39'),
(30, 'IBS Pediatrik 2', '110302', NULL, NULL, 5, '2025-06-18 05:18:04', '2025-06-18 05:18:04'),
(31, 'IBS Pediatrik 3', '110303', NULL, NULL, 5, '2025-06-18 05:26:01', '2025-06-18 05:26:01'),
(32, 'MAWAR Rawat luka sedang', '140201', NULL, NULL, 5, '2025-06-18 05:38:49', '2025-06-18 05:38:49'),
(33, 'IBS Bedah 7', '110207', NULL, NULL, 5, '2025-06-19 10:46:01', '2025-06-19 10:46:01'),
(34, 'IBS Bedah 6', '110206', NULL, NULL, 5, '2025-06-19 10:52:54', '2025-06-19 10:52:54'),
(35, 'IGD Rawat luka sedang 1', '120101', NULL, NULL, 5, '2025-06-19 11:48:46', '2025-06-19 11:48:46'),
(36, 'IGD Rawat luka sedang 2', '120102', NULL, NULL, 5, '2025-06-19 11:49:59', '2025-06-19 11:49:59'),
(37, 'IGD Rawat luka sedang 3', '120103', NULL, NULL, 5, '2025-06-19 11:53:01', '2025-06-19 11:53:01'),
(38, 'IGD Rawat luka sedang 4', '120104', NULL, NULL, 5, '2025-06-19 11:54:11', '2025-06-19 11:54:11'),
(39, 'IGD Rawat luka sedang 5', '120105', NULL, NULL, 5, '2025-06-19 11:55:08', '2025-06-19 11:55:08'),
(40, 'IGD Rawat luka sedang 6', '120106', NULL, NULL, 5, '2025-06-19 11:56:02', '2025-06-19 11:56:02'),
(41, 'IGD Rawat luka sedang 7', '120107', NULL, NULL, 5, '2025-06-19 11:56:54', '2025-06-19 11:56:54'),
(42, 'IGD Rawat luka sedang 8', '120108', NULL, NULL, 5, '2025-06-19 11:58:13', '2025-06-19 11:58:13'),
(43, 'MELATI Rawat luka kecil 1', '150101', NULL, NULL, 5, '2025-06-19 12:00:01', '2025-06-19 12:00:01'),
(44, 'MELATI Rawat luka sedang 1', '150201', NULL, NULL, 5, '2025-06-19 12:01:00', '2025-06-19 12:01:00'),
(45, 'IBS Bedah 5', '110205', NULL, NULL, 5, '2025-06-20 02:41:07', '2025-06-20 02:41:07'),
(46, 'IBS Bedah 3', '110203', NULL, NULL, 5, '2025-06-20 02:45:04', '2025-06-20 02:45:04'),
(48, 'Set Exclusive', 'SET-321312', NULL, 365, 1, '2025-07-03 16:05:58', '2025-07-03 16:05:58'),
(49, 'Set Exclusive', NULL, NULL, 365, 1, '2025-07-03 16:08:45', '2025-07-03 16:08:45'),
(50, 'Set Exclusive', NULL, NULL, NULL, 1, '2025-07-03 16:10:49', '2025-07-03 16:10:49'),
(51, 'Set Exclusive', NULL, NULL, NULL, 1, '2025-07-03 16:12:10', '2025-07-03 16:12:10'),
(52, 'Set Exclusive', 'SET-60053774', NULL, NULL, 1, '2025-07-03 16:29:14', '2025-07-03 16:29:14'),
(53, 'Set Exclusive', 'SET-66696367', NULL, 365, 1, '2025-07-03 18:18:21', '2025-07-03 18:18:21'),
(54, 'Set Exclusive', 'SET-66725605', NULL, 30, 1, '2025-07-03 18:18:51', '2025-07-03 18:18:51'),
(55, 'Set Exclusive', 'SET-66870632', NULL, 365, 1, '2025-07-03 18:21:15', '2025-07-03 18:21:15'),
(56, 'Set Exclusive', 'SET-67226155', NULL, 365, 1, '2025-07-03 18:27:11', '2025-07-03 18:27:11'),
(57, 'Set Exclusive', 'SET-67483744', NULL, NULL, 1, '2025-07-03 18:31:26', '2025-07-03 18:31:26'),
(58, 'Set Exclusive', 'SET-68295127', NULL, 365, 1, '2025-07-03 18:45:02', '2025-07-03 18:45:02'),
(59, 'Set Exclusive', 'SET-68393638', NULL, NULL, 1, '2025-07-03 18:46:37', '2025-07-03 18:46:37'),
(60, 'Set Exclusive', 'SET-68402294', NULL, 7, 1, '2025-07-03 18:46:46', '2025-07-03 18:46:46'),
(61, 'Set Exclusive', 'SET-68440488', 'asdadasdsa', NULL, 1, '2025-07-03 18:47:25', '2025-07-03 18:47:25'),
(62, 'Set Exclusive', 'SET-68452394', NULL, 365, 1, '2025-07-03 18:48:12', '2025-07-03 18:48:12'),
(63, 'Set Exclusive', 'SET-68769063', '13213', 321321, 1, '2025-07-03 18:52:53', '2025-07-03 18:52:53'),
(64, 'Set Exclusive', 'SET-69031134', NULL, 30, 1, '2025-07-03 18:57:17', '2025-07-03 18:57:17'),
(65, 'Ffff', 'SET-39567335', NULL, 365, 1, '2025-07-04 14:32:58', '2025-07-04 14:32:58'),
(66, 'Shshs', 'SET-39600869', NULL, 365, 1, '2025-07-04 14:33:25', '2025-07-04 14:34:06');

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

--
-- Dumping data for table `instrument_set_items`
--

INSERT INTO `instrument_set_items` (`set_item_id`, `set_id`, `instrument_id`, `quantity`) VALUES
(131, 12, 5, 1),
(132, 12, 6, 1),
(133, 12, 7, 1),
(134, 12, 8, 1),
(135, 12, 9, 1),
(136, 12, 10, 1),
(137, 12, 11, 1),
(138, 12, 12, 1),
(139, 12, 13, 1),
(140, 12, 14, 1),
(141, 12, 15, 1),
(142, 12, 16, 1),
(143, 12, 17, 1),
(144, 12, 18, 1),
(145, 12, 19, 1),
(146, 12, 20, 1),
(147, 12, 21, 1),
(148, 12, 22, 1),
(149, 12, 23, 1),
(150, 12, 24, 1),
(151, 12, 25, 1),
(152, 12, 26, 1),
(153, 12, 27, 1),
(154, 12, 28, 1),
(155, 12, 29, 1),
(156, 12, 30, 1),
(157, 12, 31, 1),
(158, 12, 32, 1),
(159, 12, 33, 1),
(160, 12, 34, 1),
(161, 12, 35, 1),
(162, 12, 36, 1),
(163, 12, 37, 1),
(458, 13, 5, 1),
(459, 13, 6, 1),
(460, 13, 7, 1),
(461, 13, 8, 1),
(462, 13, 9, 1),
(463, 13, 10, 1),
(464, 13, 11, 1),
(465, 13, 12, 1),
(466, 13, 13, 1),
(467, 13, 14, 1),
(468, 13, 15, 1),
(469, 13, 39, 1),
(470, 13, 16, 1),
(471, 13, 17, 1),
(472, 13, 18, 1),
(473, 13, 19, 1),
(474, 13, 20, 1),
(475, 13, 21, 1),
(476, 13, 22, 1),
(477, 13, 23, 1),
(478, 13, 24, 1),
(479, 13, 25, 1),
(480, 13, 26, 1),
(481, 13, 27, 1),
(482, 13, 28, 1),
(483, 13, 29, 1),
(484, 13, 30, 1),
(485, 13, 31, 1),
(486, 13, 32, 1),
(487, 13, 33, 1),
(488, 13, 34, 1),
(489, 13, 35, 1),
(490, 13, 36, 1),
(491, 13, 40, 1),
(492, 15, 5, 1),
(493, 15, 6, 1),
(494, 15, 7, 1),
(495, 15, 8, 1),
(496, 15, 9, 1),
(497, 15, 10, 1),
(498, 15, 11, 1),
(499, 15, 43, 1),
(500, 15, 13, 1),
(501, 15, 14, 1),
(502, 15, 15, 1),
(503, 15, 42, 1),
(504, 15, 44, 1),
(505, 15, 16, 1),
(506, 15, 17, 1),
(507, 15, 18, 1),
(508, 15, 19, 1),
(509, 15, 20, 1),
(510, 15, 21, 1),
(511, 15, 22, 1),
(512, 15, 45, 1),
(513, 15, 24, 1),
(514, 15, 25, 1),
(515, 15, 46, 1),
(516, 15, 26, 1),
(517, 15, 27, 1),
(518, 15, 47, 1),
(519, 15, 29, 1),
(520, 15, 48, 1),
(521, 15, 31, 1),
(522, 15, 32, 1),
(523, 15, 33, 1),
(524, 15, 35, 1),
(525, 15, 36, 1),
(526, 15, 37, 1),
(527, 16, 17, 1),
(528, 16, 18, 1),
(529, 16, 49, 1),
(530, 16, 50, 1),
(531, 16, 51, 1),
(532, 16, 24, 1),
(533, 16, 52, 1),
(534, 16, 25, 1),
(535, 16, 53, 1),
(536, 16, 26, 1),
(537, 16, 54, 1),
(538, 16, 55, 1),
(539, 16, 27, 1),
(540, 16, 56, 1),
(541, 16, 57, 1),
(542, 16, 58, 1),
(543, 16, 29, 1),
(544, 16, 59, 1),
(545, 16, 60, 1),
(546, 16, 31, 1),
(547, 16, 61, 1),
(548, 16, 32, 1),
(549, 16, 33, 1),
(550, 16, 62, 1),
(551, 16, 63, 1),
(552, 16, 64, 1),
(553, 16, 65, 1),
(554, 16, 66, 1),
(555, 16, 67, 1),
(556, 16, 68, 1),
(557, 14, 5, 1),
(558, 14, 6, 1),
(559, 14, 7, 1),
(560, 14, 8, 1),
(561, 14, 9, 1),
(562, 14, 10, 1),
(563, 14, 11, 1),
(564, 14, 12, 1),
(565, 14, 13, 1),
(566, 14, 14, 1),
(567, 14, 15, 1),
(568, 14, 16, 1),
(569, 14, 17, 1),
(570, 14, 18, 1),
(571, 14, 19, 1),
(572, 14, 20, 1),
(573, 14, 21, 1),
(574, 14, 22, 1),
(575, 14, 23, 1),
(576, 14, 24, 1),
(577, 14, 25, 1),
(578, 14, 26, 1),
(579, 14, 27, 1),
(580, 14, 28, 1),
(581, 14, 29, 1),
(582, 14, 31, 1),
(583, 14, 32, 1),
(584, 14, 33, 1),
(585, 14, 34, 1),
(586, 14, 35, 1),
(587, 14, 41, 1),
(727, 17, 70, 1),
(728, 17, 71, 1),
(729, 17, 12, 1),
(730, 17, 15, 1),
(731, 17, 72, 1),
(732, 17, 73, 1),
(733, 17, 74, 1),
(734, 17, 75, 1),
(735, 17, 16, 1),
(736, 17, 17, 1),
(737, 17, 76, 1),
(738, 17, 77, 1),
(739, 17, 78, 1),
(740, 17, 31, 1),
(741, 17, 32, 1),
(742, 17, 35, 1),
(744, 17, 80, 1),
(745, 17, 81, 1),
(746, 17, 79, 1),
(747, 17, 82, 1),
(816, 21, 6, 1),
(817, 21, 11, 1),
(818, 21, 12, 1),
(819, 21, 49, 1),
(820, 21, 32, 1),
(867, 18, 6, 5),
(868, 18, 7, 4),
(869, 18, 11, 3),
(870, 18, 12, 1),
(871, 18, 14, 1),
(872, 18, 15, 1),
(873, 18, 16, 1),
(874, 18, 17, 5),
(875, 18, 18, 1),
(876, 18, 19, 1),
(877, 18, 20, 1),
(878, 18, 21, 1),
(879, 18, 22, 1),
(880, 18, 23, 1),
(881, 18, 24, 1),
(882, 18, 25, 1),
(883, 18, 26, 1),
(884, 18, 27, 1),
(885, 18, 28, 1),
(886, 18, 29, 1),
(887, 18, 31, 2),
(888, 18, 32, 1),
(889, 18, 33, 2),
(890, 18, 35, 5),
(891, 18, 36, 1),
(892, 18, 40, 1),
(893, 24, 6, 5),
(894, 24, 7, 5),
(895, 24, 10, 4),
(896, 24, 12, 1),
(897, 24, 14, 1),
(898, 24, 15, 1),
(899, 24, 39, 1),
(900, 24, 17, 5),
(901, 24, 18, 1),
(902, 24, 19, 1),
(903, 24, 20, 1),
(904, 24, 50, 2),
(905, 24, 22, 1),
(906, 24, 23, 1),
(907, 24, 24, 1),
(908, 24, 25, 1),
(909, 24, 26, 1),
(910, 24, 28, 1),
(911, 24, 29, 1),
(912, 24, 30, 1),
(913, 24, 31, 2),
(914, 24, 32, 2),
(915, 24, 33, 2),
(916, 24, 35, 5),
(917, 24, 36, 1),
(918, 24, 40, 1),
(965, 23, 6, 7),
(966, 23, 10, 3),
(967, 23, 11, 7),
(968, 23, 12, 1),
(969, 23, 15, 1),
(970, 23, 17, 3),
(971, 23, 18, 2),
(972, 23, 19, 2),
(973, 23, 50, 2),
(974, 23, 45, 1),
(975, 23, 24, 2),
(976, 23, 25, 4),
(977, 23, 26, 1),
(978, 23, 27, 1),
(979, 23, 47, 1),
(980, 23, 29, 1),
(981, 23, 31, 2),
(982, 23, 32, 3),
(983, 23, 33, 1),
(984, 23, 35, 2),
(985, 23, 65, 1),
(986, 23, 36, 2),
(1011, 25, 6, 5),
(1012, 25, 7, 2),
(1013, 25, 10, 4),
(1014, 25, 12, 1),
(1015, 25, 13, 1),
(1016, 25, 14, 1),
(1017, 25, 15, 1),
(1018, 25, 17, 5),
(1019, 25, 19, 2),
(1020, 25, 20, 1),
(1021, 25, 50, 1),
(1022, 25, 22, 1),
(1023, 25, 23, 1),
(1024, 25, 24, 1),
(1025, 25, 26, 1),
(1026, 25, 27, 1),
(1027, 25, 28, 1),
(1028, 25, 29, 1),
(1029, 25, 30, 3),
(1030, 25, 31, 2),
(1031, 25, 32, 2),
(1032, 25, 33, 2),
(1033, 25, 35, 5),
(1034, 25, 36, 1),
(1035, 26, 6, 5),
(1036, 26, 7, 1),
(1037, 26, 10, 1),
(1038, 26, 11, 3),
(1039, 26, 12, 1),
(1040, 26, 14, 1),
(1041, 26, 15, 1),
(1042, 26, 16, 1),
(1043, 26, 17, 3),
(1044, 26, 18, 1),
(1045, 26, 19, 1),
(1046, 26, 20, 1),
(1047, 26, 50, 1),
(1048, 26, 22, 1),
(1049, 26, 23, 1),
(1050, 26, 25, 1),
(1051, 26, 26, 1),
(1052, 26, 27, 1),
(1053, 26, 28, 1),
(1054, 26, 29, 1),
(1055, 26, 30, 4),
(1056, 26, 31, 2),
(1057, 26, 32, 2),
(1058, 26, 33, 2),
(1059, 26, 34, 1),
(1060, 26, 35, 5),
(1061, 26, 36, 1),
(1062, 26, 40, 1),
(1063, 27, 6, 5),
(1064, 27, 10, 1),
(1065, 27, 11, 4),
(1066, 27, 12, 1),
(1067, 27, 14, 1),
(1068, 27, 15, 1),
(1069, 27, 17, 5),
(1070, 27, 18, 1),
(1071, 27, 19, 1),
(1072, 27, 20, 1),
(1073, 27, 50, 1),
(1074, 27, 24, 2),
(1075, 27, 25, 2),
(1076, 27, 26, 1),
(1077, 27, 27, 1),
(1078, 27, 29, 1),
(1079, 27, 30, 4),
(1080, 27, 31, 2),
(1081, 27, 32, 2),
(1082, 27, 33, 2),
(1083, 27, 35, 2),
(1084, 27, 36, 1),
(1085, 27, 37, 1),
(1086, 28, 6, 5),
(1087, 28, 7, 3),
(1088, 28, 11, 2),
(1089, 28, 12, 1),
(1090, 28, 14, 1),
(1091, 28, 15, 1),
(1092, 28, 16, 1),
(1093, 28, 17, 5),
(1094, 28, 18, 1),
(1095, 28, 19, 1),
(1096, 28, 20, 1),
(1097, 28, 50, 1),
(1098, 28, 22, 1),
(1099, 28, 23, 1),
(1100, 28, 24, 1),
(1101, 28, 25, 1),
(1102, 28, 26, 1),
(1103, 28, 27, 1),
(1104, 28, 28, 1),
(1105, 28, 29, 1),
(1106, 28, 31, 2),
(1107, 28, 32, 2),
(1108, 28, 33, 2),
(1109, 28, 35, 5),
(1110, 28, 36, 1),
(1111, 28, 40, 1),
(1112, 29, 6, 5),
(1113, 29, 10, 4),
(1114, 29, 11, 2),
(1115, 29, 12, 1),
(1116, 29, 14, 1),
(1117, 29, 15, 1),
(1118, 29, 17, 5),
(1119, 29, 18, 1),
(1120, 29, 50, 2),
(1121, 29, 22, 1),
(1122, 29, 23, 1),
(1123, 29, 24, 1),
(1124, 29, 25, 1),
(1125, 29, 26, 1),
(1126, 29, 28, 1),
(1127, 29, 29, 1),
(1128, 29, 30, 4),
(1129, 29, 31, 2),
(1130, 29, 32, 2),
(1131, 29, 33, 2),
(1132, 29, 35, 4),
(1133, 29, 36, 1),
(1134, 29, 40, 1),
(1135, 30, 6, 8),
(1136, 30, 7, 2),
(1137, 30, 10, 4),
(1138, 30, 11, 6),
(1139, 30, 12, 1),
(1140, 30, 14, 1),
(1141, 30, 15, 1),
(1142, 30, 16, 1),
(1143, 30, 17, 4),
(1144, 30, 18, 1),
(1145, 30, 19, 1),
(1146, 30, 50, 2),
(1147, 30, 22, 2),
(1148, 30, 24, 2),
(1149, 30, 25, 4),
(1150, 30, 26, 1),
(1151, 30, 27, 1),
(1152, 30, 29, 1),
(1153, 30, 31, 2),
(1154, 30, 32, 3),
(1155, 30, 33, 2),
(1156, 30, 35, 2),
(1157, 31, 6, 7),
(1158, 31, 7, 2),
(1159, 31, 10, 4),
(1160, 31, 11, 5),
(1161, 31, 12, 1),
(1162, 31, 15, 1),
(1163, 31, 42, 2),
(1164, 31, 17, 3),
(1165, 31, 19, 2),
(1166, 31, 20, 1),
(1167, 31, 50, 2),
(1168, 31, 22, 1),
(1169, 31, 45, 1),
(1170, 31, 24, 3),
(1171, 31, 26, 1),
(1172, 31, 27, 1),
(1173, 31, 29, 1),
(1174, 31, 31, 2),
(1175, 31, 32, 3),
(1176, 31, 33, 2),
(1177, 31, 35, 1),
(1178, 31, 36, 1),
(1179, 32, 6, 1),
(1180, 32, 11, 1),
(1181, 32, 49, 1),
(1182, 32, 32, 1),
(1183, 33, 6, 3),
(1184, 33, 7, 2),
(1185, 33, 8, 1),
(1186, 33, 10, 1),
(1187, 33, 11, 3),
(1188, 33, 12, 1),
(1189, 33, 14, 1),
(1190, 33, 15, 1),
(1191, 33, 16, 1),
(1192, 33, 17, 5),
(1193, 33, 18, 1),
(1194, 33, 19, 1),
(1195, 33, 20, 1),
(1196, 33, 50, 1),
(1197, 33, 24, 2),
(1198, 33, 25, 3),
(1199, 33, 26, 1),
(1200, 33, 27, 1),
(1201, 33, 29, 1),
(1202, 33, 30, 1),
(1203, 33, 31, 2),
(1204, 33, 32, 2),
(1205, 33, 33, 1),
(1206, 34, 6, 2),
(1207, 34, 8, 3),
(1208, 34, 10, 1),
(1209, 34, 11, 5),
(1210, 34, 12, 1),
(1211, 34, 14, 1),
(1212, 34, 15, 1),
(1213, 34, 16, 1),
(1214, 34, 17, 5),
(1215, 34, 19, 2),
(1216, 34, 20, 1),
(1217, 34, 50, 1),
(1218, 34, 22, 1),
(1219, 34, 24, 2),
(1220, 34, 25, 2),
(1221, 34, 26, 1),
(1222, 34, 29, 1),
(1223, 34, 30, 4),
(1224, 34, 31, 2),
(1225, 34, 32, 2),
(1226, 34, 33, 2),
(1227, 34, 35, 2),
(1228, 19, 11, 2),
(1229, 19, 43, 1),
(1230, 19, 49, 1),
(1236, 36, 6, 1),
(1237, 36, 11, 1),
(1238, 36, 12, 1),
(1239, 36, 19, 1),
(1240, 36, 31, 1),
(1241, 36, 32, 1),
(1242, 35, 6, 1),
(1243, 35, 11, 1),
(1244, 35, 12, 1),
(1245, 35, 19, 1),
(1246, 35, 31, 1),
(1247, 35, 32, 1),
(1248, 37, 6, 1),
(1249, 37, 11, 1),
(1250, 37, 12, 1),
(1251, 37, 19, 1),
(1252, 37, 31, 1),
(1253, 37, 32, 1),
(1254, 38, 6, 1),
(1255, 38, 11, 1),
(1256, 38, 12, 1),
(1257, 38, 19, 1),
(1258, 38, 31, 1),
(1259, 38, 32, 1),
(1260, 39, 6, 1),
(1261, 39, 11, 1),
(1262, 39, 12, 1),
(1263, 39, 19, 1),
(1264, 39, 31, 1),
(1265, 39, 32, 1),
(1266, 40, 6, 1),
(1267, 40, 11, 1),
(1268, 40, 12, 1),
(1269, 40, 19, 1),
(1270, 40, 31, 1),
(1271, 40, 32, 1),
(1272, 41, 6, 1),
(1273, 41, 11, 1),
(1274, 41, 12, 1),
(1275, 41, 19, 1),
(1276, 41, 31, 1),
(1277, 41, 32, 1),
(1278, 42, 6, 1),
(1279, 42, 11, 1),
(1280, 42, 12, 1),
(1281, 42, 19, 1),
(1282, 42, 31, 1),
(1283, 42, 32, 1),
(1284, 43, 6, 1),
(1285, 43, 12, 1),
(1286, 43, 32, 1),
(1287, 44, 6, 1),
(1288, 44, 11, 1),
(1289, 44, 12, 1),
(1290, 44, 19, 1),
(1291, 44, 32, 1),
(1292, 22, 6, 4),
(1293, 22, 7, 3),
(1294, 22, 10, 2),
(1295, 22, 11, 5),
(1296, 22, 12, 1),
(1297, 22, 13, 1),
(1298, 22, 14, 1),
(1299, 22, 15, 1),
(1300, 22, 17, 5),
(1301, 22, 18, 1),
(1302, 22, 19, 1),
(1303, 22, 50, 2),
(1304, 22, 22, 1),
(1305, 22, 25, 5),
(1306, 22, 26, 1),
(1307, 22, 27, 1),
(1308, 22, 29, 1),
(1309, 22, 30, 1),
(1310, 22, 31, 2),
(1311, 22, 32, 2),
(1312, 22, 33, 1),
(1313, 22, 35, 1),
(1314, 22, 36, 1),
(1315, 22, 37, 1),
(1316, 20, 6, 2),
(1317, 20, 7, 4),
(1318, 20, 10, 2),
(1319, 20, 12, 1),
(1320, 20, 15, 1),
(1321, 20, 16, 1),
(1322, 20, 17, 5),
(1323, 20, 18, 1),
(1324, 20, 19, 2),
(1325, 20, 20, 2),
(1326, 20, 22, 1),
(1327, 20, 24, 1),
(1328, 20, 25, 1),
(1329, 20, 26, 1),
(1330, 20, 27, 1),
(1331, 20, 29, 1),
(1332, 20, 31, 2),
(1333, 20, 32, 2),
(1334, 20, 33, 2),
(1335, 20, 35, 5),
(1336, 20, 36, 1),
(1337, 20, 40, 1),
(1338, 45, 6, 4),
(1339, 45, 10, 1),
(1340, 45, 11, 4),
(1341, 45, 12, 1),
(1342, 45, 15, 1),
(1343, 45, 16, 1),
(1344, 45, 17, 5),
(1345, 45, 18, 2),
(1346, 45, 19, 2),
(1347, 45, 50, 2),
(1348, 45, 22, 1),
(1349, 45, 24, 1),
(1350, 45, 25, 3),
(1351, 45, 26, 1),
(1352, 45, 27, 1),
(1353, 45, 29, 1),
(1354, 45, 30, 4),
(1355, 45, 31, 2),
(1356, 45, 32, 2),
(1357, 45, 36, 1),
(1358, 46, 5, 2),
(1359, 46, 6, 4),
(1360, 46, 7, 2),
(1361, 46, 10, 4),
(1362, 46, 11, 4),
(1363, 46, 12, 1),
(1364, 46, 15, 1),
(1365, 46, 16, 1),
(1366, 46, 17, 5),
(1367, 46, 18, 2),
(1368, 46, 19, 2),
(1369, 46, 20, 1),
(1370, 46, 50, 1),
(1371, 46, 22, 1),
(1372, 46, 24, 1),
(1373, 46, 25, 2),
(1374, 46, 26, 1),
(1375, 46, 27, 1),
(1376, 46, 28, 1),
(1377, 46, 29, 1),
(1378, 46, 30, 4),
(1379, 46, 31, 2),
(1380, 46, 32, 2),
(1381, 46, 33, 3),
(1382, 46, 35, 2),
(1383, 46, 36, 1),
(1384, 66, 97, 1),
(1385, 66, 98, 1);

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
(8, 'Cutting', 1, '2025-06-26 15:39:21');

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
(1, 'Autoklaf A', 'AA', 1),
(2, 'Autoklaf B', 'AB', 1);

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
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data untuk sesi kerja, misal: Pagi, Siang, Malam.';

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `session_name`, `is_active`, `created_at`) VALUES
(1, 'Pagi', 1, '2025-06-28 08:44:21'),
(2, 'Siang', 1, '2025-06-28 08:44:21'),
(3, 'Sore', 1, '2025-06-28 08:44:21'),
(4, 'Malam', 1, '2025-06-28 08:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_cycles`
--

CREATE TABLE `sterilization_cycles` (
  `cycle_id` int(11) NOT NULL,
  `machine_name` varchar(255) NOT NULL,
  `cycle_number` varchar(100) NOT NULL,
  `sterilization_method` varchar(50) DEFAULT NULL COMMENT 'Metode yang digunakan, e.g., Steam, EtO, Plasma',
  `cycle_date` datetime NOT NULL,
  `operator_user_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'menunggu_validasi' COMMENT 'completed, failed, menunggu_validasi',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sterilization_cycles`
--

INSERT INTO `sterilization_cycles` (`cycle_id`, `machine_name`, `cycle_number`, `sterilization_method`, `cycle_date`, `operator_user_id`, `status`, `notes`, `created_at`) VALUES
(4, 'Autoclave C', '421412415', NULL, '2025-06-25 22:49:00', 1, 'completed', NULL, '2025-06-25 15:49:17'),
(5, 'Autoclave B', 'A-4324324', NULL, '2025-06-27 04:23:18', 1, 'failed', NULL, '2025-06-27 04:23:18'),
(7, 'Autoclave B', 'A-4324328766', NULL, '2025-06-27 04:25:41', 1, 'completed', NULL, '2025-06-27 04:25:41'),
(8, 'Autoklaf A', 'AA-270625-01', NULL, '2025-06-27 05:20:01', 1, 'completed', NULL, '2025-06-27 05:20:01'),
(9, 'Autoklaf A', 'AA-270625-02', NULL, '2025-06-27 10:07:22', 1, 'menunggu_validasi', NULL, '2025-06-27 10:07:22'),
(10, 'Autoklaf A', 'AA-280625-01', NULL, '2025-06-28 08:13:27', 1, 'menunggu_validasi', NULL, '2025-06-28 08:13:27'),
(11, 'Autoklaf A', 'SIKLUS-AA-280625-01', NULL, '2025-06-28 08:50:22', 5, 'menunggu_validasi', NULL, '2025-06-28 08:50:22'),
(12, 'Autoklaf A', 'SIKLUS-AA-280625-02', NULL, '2025-06-28 08:53:57', 1, 'menunggu_validasi', NULL, '2025-06-28 08:53:57'),
(13, 'Autoklaf A', 'SIKLUS-AA-280625-03', NULL, '2025-06-28 09:41:00', 1, 'menunggu_validasi', NULL, '2025-06-28 09:41:00'),
(14, 'Autoklaf A', 'SIKLUS-AA-280625-04', NULL, '2025-06-28 10:27:05', 1, 'menunggu_validasi', NULL, '2025-06-28 10:27:05'),
(15, 'Autoklaf A', 'SIKLUS-AA-290625-01', NULL, '2025-06-28 20:53:04', 1, 'failed', NULL, '2025-06-28 20:53:04'),
(16, 'Autoklaf A', 'SIKLUS-AA-290625-02', NULL, '2025-06-29 04:32:45', 1, 'completed', NULL, '2025-06-29 04:32:45'),
(17, 'Autoklaf A', 'SIKLUS-AA-010725-01', NULL, '2025-07-01 15:50:27', 5, 'menunggu_validasi', NULL, '2025-07-01 08:50:27'),
(18, 'Autoklaf A', 'SIKLUS-AA-010725-02', NULL, '2025-07-01 23:27:56', 1, 'completed', NULL, '2025-07-01 16:27:56'),
(19, 'Autoklaf A', 'SIKLUS-AA-030725-01', NULL, '2025-07-03 10:45:06', 6, 'menunggu_validasi', NULL, '2025-07-03 03:45:06'),
(20, 'Autoklaf A', 'SIKLUS-AA-030725-02', NULL, '2025-07-03 21:40:46', 1, 'menunggu_validasi', NULL, '2025-07-03 14:40:46'),
(21, 'Autoklaf A', 'SIKLUS-AA-030725-03', 'Steam', '2025-07-03 21:44:20', 1, 'menunggu_validasi', NULL, '2025-07-03 14:44:20'),
(22, 'Autoklaf A', 'SIKLUS-AA-050725-01', 'Steam', '2025-07-05 00:07:03', 1, 'completed', '', '2025-07-04 17:07:03');

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
  `session_id` int(11) DEFAULT NULL,
  `load_type` enum('Rutin','Cito/Darurat','Jadwal Operasi') NOT NULL DEFAULT 'Rutin' COMMENT 'Jenis muatan untuk klasifikasi',
  `priority` enum('Normal','Tinggi','Urgent') NOT NULL DEFAULT 'Normal' COMMENT 'Prioritas pengerjaan muatan',
  `destination_department_id` int(11) DEFAULT NULL,
  `cycle_id` int(11) DEFAULT NULL COMMENT 'Diisi saat muatan dijalankan dalam siklus',
  `status` varchar(50) NOT NULL DEFAULT 'persiapan' COMMENT 'persiapan, berjalan, menunggu_validasi, selesai, gagal',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sterilization_loads`
--

INSERT INTO `sterilization_loads` (`load_id`, `load_name`, `notes`, `created_by_user_id`, `machine_id`, `session_id`, `load_type`, `priority`, `destination_department_id`, `cycle_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Autoklaf A - Sesi Siang 30 Juni 2025', NULL, 1, NULL, NULL, 'Rutin', 'Normal', NULL, 5, 'gagal', '2025-06-27 03:45:05', '2025-06-27 04:23:48'),
(3, 'Autoklaf A - Sesi Malam 30 Juni 2025', NULL, 1, NULL, NULL, 'Rutin', 'Normal', NULL, 7, 'selesai', '2025-06-27 04:24:30', '2025-06-27 04:25:49'),
(6, 'AA-270625-SORE - Alat Bedah', NULL, 1, 1, NULL, 'Rutin', 'Normal', NULL, 8, 'selesai', '2025-06-27 04:47:46', '2025-06-27 05:21:08'),
(7, 'AA-270625-MALAM - Alat Perang', NULL, 1, 1, NULL, 'Rutin', 'Normal', NULL, 9, 'menunggu_validasi', '2025-06-27 10:07:13', '2025-06-27 10:07:22'),
(9, 'AA-280625-PAGI - ABC', 'Alat Bedah', 6, 1, 4, 'Rutin', 'Normal', 3, 15, 'gagal', '2025-06-27 23:56:16', '2025-07-01 07:36:45'),
(10, 'AA-280625-03', 'Alat Perang', 5, 1, NULL, 'Rutin', 'Normal', NULL, 10, 'menunggu_validasi', '2025-06-28 06:27:56', '2025-06-28 08:13:27'),
(11, 'MUATAN-280625-02', 'Alat Perang', 5, 1, NULL, 'Rutin', 'Normal', NULL, 11, 'menunggu_validasi', '2025-06-28 08:49:58', '2025-06-28 08:50:22'),
(12, 'MUATAN-280625-03', 'Alat Bedah', 1, 1, 4, 'Rutin', 'Normal', NULL, 12, 'menunggu_validasi', '2025-06-28 08:53:36', '2025-06-28 08:53:57'),
(13, 'MUATAN-280625-04', 'Alat Perang', 6, 1, 1, 'Rutin', 'Normal', NULL, 12, 'menunggu_validasi', '2025-06-28 09:25:28', '2025-06-28 09:26:08'),
(14, 'MUATAN-280625-05', 'Alat Bedah', 1, 1, 2, 'Rutin', 'Normal', 1, 13, 'menunggu_validasi', '2025-06-28 09:40:29', '2025-06-28 09:41:00'),
(15, 'MUATAN-280625-06', 'Alat Cukur XXX', 1, 1, 3, 'Rutin', 'Normal', 3, 14, 'menunggu_validasi', '2025-06-28 10:01:46', '2025-06-28 10:27:05'),
(18, 'MUATAN-290625-03', 'Alat Bedah', 1, 1, 1, 'Rutin', 'Normal', 4, 16, 'selesai', '2025-06-29 04:31:37', '2025-06-29 04:32:58'),
(19, 'MUATAN-290625-04', NULL, 6, 1, 1, 'Rutin', 'Normal', 2, NULL, 'persiapan', '2025-06-29 15:14:10', '2025-06-29 15:14:10'),
(20, 'MUATAN-300625-05', 'Catatan yang panjang kali lebar kali tinggi kalu kali terus tanpa hentiCatatan yang panjang kali lebar kali tinggi kalu kali terus tanpa hentiCatatan yang panjang kali lebar kali tinggi kalu kali terus tanpa henti', 6, 2, 4, 'Rutin', 'Normal', 2, NULL, 'persiapan', '2025-06-29 17:54:39', '2025-06-30 14:13:07'),
(21, 'MUATAN-010725-01', NULL, 5, 1, 1, 'Rutin', 'Normal', 8, 17, 'menunggu_validasi', '2025-07-01 08:48:29', '2025-07-01 08:50:27'),
(22, 'MUATAN-010725-02', NULL, 1, 1, 1, 'Rutin', 'Normal', 4, 18, 'selesai', '2025-07-01 16:27:31', '2025-07-02 09:23:12'),
(23, 'MUATAN-010725-03', NULL, 1, 1, 1, 'Rutin', 'Normal', 4, 19, 'menunggu_validasi', '2025-07-01 16:34:05', '2025-07-03 03:45:06'),
(24, 'MUATAN-030725-01', 'Catatan', 1, 1, 1, 'Rutin', 'Normal', 2, 20, 'menunggu_validasi', '2025-07-03 05:01:51', '2025-07-03 14:40:46'),
(25, 'MUATAN-030725-02', NULL, 1, 1, 1, 'Rutin', 'Normal', 8, 17, 'menunggu_validasi', '2025-07-03 14:40:59', '2025-07-03 14:41:19'),
(26, 'MUATAN-030725-03', NULL, 1, 1, 1, 'Rutin', 'Normal', 8, 21, 'menunggu_validasi', '2025-07-03 14:43:45', '2025-07-03 14:44:38'),
(27, 'MUATAN-030725-04', NULL, 1, 1, 2, 'Rutin', 'Normal', 4, 21, 'menunggu_validasi', '2025-07-03 14:43:55', '2025-07-03 14:44:20'),
(28, 'MUATAN-050725-01', NULL, 1, 1, 2, 'Rutin', 'Normal', 1, 22, 'selesai', '2025-07-04 17:04:28', '2025-07-04 17:07:27');

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

--
-- Dumping data for table `sterilization_load_items`
--

INSERT INTO `sterilization_load_items` (`load_item_id`, `load_id`, `item_id`, `item_type`, `quantity`, `item_snapshot`) VALUES
(1, 3, 27, 'set', 1, NULL),
(4, 3, 85, 'instrument', 1, NULL),
(5, 3, 19, 'instrument', 1, NULL),
(7, 3, 19, 'instrument', 1, NULL),
(8, 3, 19, 'instrument', 1, NULL),
(9, 3, 19, 'instrument', 1, NULL),
(10, 3, 19, 'instrument', 1, NULL),
(14, 6, 17, 'set', 1, NULL),
(15, 6, 46, 'set', 1, NULL),
(16, 6, 45, 'set', 1, NULL),
(17, 7, 19, 'instrument', 1, NULL),
(18, 7, 20, 'instrument', 1, NULL),
(19, 10, 27, 'set', 1, NULL),
(20, 10, 27, 'set', 1, NULL),
(21, 10, 27, 'set', 1, NULL),
(22, 10, 22, 'set', 1, NULL),
(23, 10, 19, 'instrument', 1, NULL),
(24, 11, 20, 'instrument', 1, NULL),
(25, 11, 46, 'set', 1, NULL),
(26, 12, 14, 'instrument', 1, NULL),
(27, 12, 45, 'set', 1, NULL),
(28, 12, 70, 'instrument', 1, NULL),
(29, 13, 27, 'set', 1, NULL),
(30, 13, 28, 'instrument', 1, NULL),
(31, 14, 27, 'set', 1, NULL),
(32, 14, 19, 'instrument', 1, NULL),
(33, 15, 84, 'instrument', 1, NULL),
(39, 9, 22, 'set', 1, NULL),
(40, 9, 22, 'set', 1, NULL),
(51, 18, 45, 'set', 1, '[{\"instrument_id\":83,\"quantity\":100},{\"instrument_id\":70,\"quantity\":50},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]'),
(52, 18, 84, 'instrument', 1, NULL),
(53, 19, 19, 'instrument', 1, NULL),
(54, 19, 31, 'set', 1, '[{\"instrument_id\":97,\"quantity\":100},{\"instrument_id\":6,\"quantity\":99},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":42,\"quantity\":2},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]'),
(55, 20, 46, 'set', 1, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":100}]'),
(56, 21, 23, 'set', 1, '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":10,\"quantity\":3},{\"instrument_id\":11,\"quantity\":7},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":2},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":65,\"quantity\":99}]'),
(57, 20, 19, 'instrument', 1, NULL),
(58, 20, 12, 'set', 1, '[{\"instrument_id\":89,\"quantity\":2},{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]'),
(59, 20, 30, 'set', 1, '[{\"instrument_id\":6,\"quantity\":8},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":6},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":4},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":22,\"quantity\":2},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":50,\"quantity\":2}]'),
(61, 20, 22, 'set', 1, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]'),
(62, 22, 27, 'set', 1, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]'),
(63, 23, 46, 'set', 1, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]'),
(64, 20, 19, 'set', 1, '[{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":43,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1}]'),
(65, 24, 12, 'set', 1, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":85,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]'),
(66, 25, 17, 'set', 1, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1}]'),
(67, 26, 27, 'set', 1, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]'),
(68, 27, 46, 'set', 1, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]'),
(69, 19, 12, 'set', 1, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]'),
(70, 28, 22, 'set', 1, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]'),
(71, 28, 16, 'set', 1, '[{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":51,\"quantity\":1},{\"instrument_id\":52,\"quantity\":1},{\"instrument_id\":53,\"quantity\":1},{\"instrument_id\":54,\"quantity\":1},{\"instrument_id\":55,\"quantity\":1},{\"instrument_id\":56,\"quantity\":1},{\"instrument_id\":57,\"quantity\":1},{\"instrument_id\":58,\"quantity\":1},{\"instrument_id\":59,\"quantity\":1},{\"instrument_id\":60,\"quantity\":1},{\"instrument_id\":61,\"quantity\":1},{\"instrument_id\":62,\"quantity\":1},{\"instrument_id\":63,\"quantity\":1},{\"instrument_id\":64,\"quantity\":1},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":66,\"quantity\":1},{\"instrument_id\":67,\"quantity\":1},{\"instrument_id\":68,\"quantity\":1}]'),
(72, 28, 17, 'set', 1, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":12,\"quantity\":2},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1}]'),
(73, 28, 19, 'instrument', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sterilization_methods`
--

CREATE TABLE `sterilization_methods` (
  `method_id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Master data untuk metode sterilisasi';

--
-- Dumping data for table `sterilization_methods`
--

INSERT INTO `sterilization_methods` (`method_id`, `method_name`, `description`, `is_active`) VALUES
(1, 'Steam', NULL, 1),
(2, 'EtO', NULL, 1),
(3, 'Plasma', NULL, 1);

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
  `batch_number` varchar(100) DEFAULT NULL,
  `sterilization_method` varchar(50) DEFAULT NULL,
  `destination_department_id` int(11) DEFAULT NULL,
  `item_type` enum('instrument','set') NOT NULL,
  `label_title` varchar(255) NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','used','expired','pending_validation','recalled') NOT NULL DEFAULT 'pending_validation',
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'Tanggal dan waktu label ditandai sebagai digunakan',
  `validated_by_user_id` int(11) DEFAULT NULL COMMENT 'ID pengguna yang melakukan validasi (mengubah status dari pending_validation ke active)',
  `validated_at` timestamp NULL DEFAULT NULL COMMENT 'Tanggal dan waktu label divalidasi dan diaktifkan',
  `notes` text DEFAULT NULL,
  `label_items_snapshot` text DEFAULT NULL COMMENT 'JSON snapshot dari item dan kuantitas untuk label ini jika item_type adalah set',
  `print_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, printed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Judul atau nama deskriptif untuk label ini, diberikan oleh pengguna.';

--
-- Dumping data for table `sterilization_records`
--

INSERT INTO `sterilization_records` (`record_id`, `label_unique_id`, `item_id`, `cycle_id`, `load_id`, `batch_number`, `sterilization_method`, `destination_department_id`, `item_type`, `label_title`, `created_by_user_id`, `created_at`, `expiry_date`, `status`, `used_at`, `validated_by_user_id`, `validated_at`, `notes`, `label_items_snapshot`, `print_status`) VALUES
(52, 'E523CD4B', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH IBS', 5, '2025-06-11 05:56:01', '2025-07-17 05:55:00', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(53, '5F2A6400', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH IBS', 5, '2025-06-11 05:56:52', '2025-07-17 05:55:00', 'active', NULL, NULL, NULL, 'SET KANDUNGAN RUANGAN IBS', '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":39,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(54, '0B17F736', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN', 5, '2025-06-11 06:01:30', '2025-06-18 06:01:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":39,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(55, '79221F78', 15, NULL, NULL, NULL, NULL, NULL, 'set', 'SET PEDIATRIK IBS', 5, '2025-06-11 06:06:02', '2025-06-30 06:05:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":43,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":42,\"quantity\":1},{\"instrument_id\":44,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":46,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":48,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(56, '1BD5B721', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN IBS', 5, '2025-06-11 06:14:58', '2025-07-09 06:13:00', 'active', NULL, NULL, NULL, 'JANUAR', '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":8,\"quantity\":2},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":39,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(57, '89A2B151', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN IBS', 5, '2025-06-11 06:16:16', '2025-06-18 06:15:00', 'expired', NULL, NULL, NULL, 'AGUNG', '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":39,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(58, '6744B1F8', 17, NULL, NULL, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-11 06:39:23', '2025-06-21 06:37:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1}]', 'pending'),
(59, 'A191435B', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN 4 IBS', 5, '2025-06-11 08:12:21', '2025-06-18 08:12:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(60, '96700CE7', 17, NULL, NULL, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-11 08:50:53', '2025-06-18 08:50:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1}]', 'pending'),
(61, '3844BEF2', 15, NULL, NULL, NULL, NULL, NULL, 'set', 'SET PEDIATRIK IBS', 5, '2025-06-11 09:00:46', '2025-06-18 08:59:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":1},{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":43,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":42,\"quantity\":1},{\"instrument_id\":44,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":46,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":48,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(62, 'E83075D0', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'KANDUNGAN 4 IBS', 5, '2025-06-14 04:54:04', '2025-06-21 04:53:00', 'used', '2025-06-19 11:12:58', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(63, '60C62312', 19, NULL, NULL, NULL, NULL, NULL, 'set', 'Set SC Perinatologi', 5, '2025-06-14 05:02:33', '2025-06-21 05:02:00', 'used', '2025-06-16 07:49:53', NULL, NULL, NULL, '[{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":43,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1}]', 'pending'),
(64, 'BD9B9203', 19, NULL, NULL, NULL, NULL, NULL, 'set', 'Set SC Perinatologi', 5, '2025-06-15 05:47:54', '2025-06-22 05:47:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":43,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1}]', 'pending'),
(65, 'D4FD6A64', 20, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 2', 5, '2025-06-15 05:52:46', '2025-06-22 05:52:00', 'used', '2025-06-17 05:24:21', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(66, 'A2EA104B', 17, NULL, NULL, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-15 05:53:15', '2025-06-22 05:53:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1}]', 'pending'),
(67, '24AABBEB', 21, NULL, NULL, NULL, NULL, NULL, 'set', 'MAWAR Rawat luka kecil', 5, '2025-06-15 05:57:30', '2025-06-22 05:57:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1}]', 'pending'),
(68, 'F62950AB', 21, NULL, NULL, NULL, NULL, NULL, 'set', 'MAWAR Rawat luka kecil', 5, '2025-06-15 05:59:22', '2025-06-25 05:58:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1}]', 'pending'),
(69, '12D8C4BD', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 4', 5, '2025-06-15 06:01:55', '2025-06-22 06:01:00', 'used', '2025-06-19 13:37:09', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(70, 'A3159736', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 4', 5, '2025-06-15 06:02:09', '2025-06-22 06:02:00', 'used', '2025-06-19 13:37:35', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(71, '445D3479', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH  3 IBS', 5, '2025-06-16 05:02:30', '2025-06-26 04:47:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(72, '838ACC1D', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH IBS 5', 5, '2025-06-16 05:08:14', '2025-06-26 05:03:00', 'used', '2025-06-17 05:06:33', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":21,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(73, 'FE276ADC', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH 7 IBS', 5, '2025-06-16 05:25:26', '2025-06-26 05:17:00', 'used', '2025-06-17 05:06:17', NULL, NULL, 'packing bagas', '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1}]', 'pending'),
(74, 'E41FDB89', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH 6 IBS', 5, '2025-06-16 05:37:48', '2025-06-26 05:27:00', 'used', '2025-06-17 05:05:39', NULL, NULL, 'Packing Bagas', '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":8,\"quantity\":3},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(75, '2ADB8E6C', 12, NULL, NULL, NULL, NULL, NULL, 'set', 'SET BEDAH BAK PANJANG/RAM IBS', 5, '2025-06-16 05:55:00', '2025-06-26 05:51:00', 'used', '2025-06-17 02:33:18', NULL, NULL, 'PACKING Bagas', '[{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":21,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(76, '9EB677A2', 22, NULL, NULL, NULL, NULL, NULL, 'set', 'Set bedah bak panjang ram', 5, '2025-06-17 02:28:26', '2025-06-24 02:27:00', 'used', '2025-06-17 02:32:30', NULL, NULL, 'awal', '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(77, '1980BFD3', 22, NULL, NULL, NULL, NULL, NULL, 'set', 'Set bedah bak panjang ram', 5, '2025-06-17 02:29:32', '2025-06-24 02:29:00', 'used', '2025-06-19 07:13:23', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(78, '8C816A04', 22, NULL, NULL, NULL, NULL, NULL, 'set', 'Set bedah bak panjang ram', 5, '2025-06-17 02:30:04', '2025-06-27 02:29:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(79, 'A4BBE083', 22, NULL, NULL, NULL, NULL, NULL, 'set', 'Set bedah bak panjang ram', 5, '2025-06-17 02:30:29', '2025-06-27 02:30:00', 'expired', NULL, NULL, NULL, 'Bagas', '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(80, '6C100AEA', 23, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 1', 5, '2025-06-17 03:20:29', '2025-06-24 03:20:00', 'expired', NULL, NULL, NULL, 'UTAMA', '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":10,\"quantity\":3},{\"instrument_id\":11,\"quantity\":7},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":36,\"quantity\":2}]', 'pending'),
(81, '830E4A2F', 23, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 1', 5, '2025-06-17 03:21:17', '2025-06-27 03:20:00', 'expired', NULL, NULL, NULL, 'BAGAS', '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":10,\"quantity\":3},{\"instrument_id\":11,\"quantity\":7},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":36,\"quantity\":2}]', 'pending'),
(82, 'E50B0E20', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN 5 IBS', 5, '2025-06-17 05:51:16', '2025-06-27 05:29:00', 'expired', NULL, NULL, NULL, 'petugas : agung', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":21,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":4},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(83, 'BC6141FC', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN 2 IBS', 5, '2025-06-17 06:10:30', '2025-06-27 06:06:00', 'expired', NULL, NULL, NULL, 'petugas :agung', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":9,\"quantity\":1},{\"instrument_id\":11,\"quantity\":1},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":3},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(84, 'BC3B9FED', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 4', 5, '2025-06-17 07:26:11', '2025-06-27 07:20:00', 'expired', NULL, NULL, NULL, 'packing yudi', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":2},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(85, 'E8A33089', 20, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 1', 5, '2025-06-17 07:31:34', '2025-06-26 07:27:00', 'expired', NULL, NULL, NULL, 'packing yudi', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":5},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":26,\"quantity\":2},{\"instrument_id\":27,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(86, '020D8357', 13, NULL, NULL, NULL, NULL, NULL, 'set', 'SET KANDUNGAN 3 IBS', 5, '2025-06-17 07:36:54', '2025-06-27 07:32:00', 'expired', NULL, NULL, NULL, 'packing kasbi', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":5},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":39,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(87, 'C4EA8947', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 4', 5, '2025-06-17 08:02:54', '2025-06-27 08:02:00', 'used', '2025-06-19 07:15:57', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":4},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(88, 'D598FDC4', 25, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 7', 5, '2025-06-18 02:00:23', '2025-06-19 01:58:00', 'used', '2025-06-18 06:37:05', NULL, NULL, 'LABEL DI BAK ALAT', '[{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":3},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(89, 'B6ECACD1', 25, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 7', 5, '2025-06-18 02:01:25', '2025-06-28 02:01:00', 'expired', NULL, NULL, NULL, 'CONDRO WIBOWO', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":3},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(90, '0838F89B', 26, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 6', 5, '2025-06-18 02:09:33', '2025-06-26 02:09:00', 'used', '2025-06-18 06:36:57', NULL, NULL, 'MASTER BAK INSTRUMEN', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(91, '9E4E345A', 26, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 6', 5, '2025-06-18 02:10:01', '2025-06-28 02:09:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":34,\"quantity\":1},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(92, '5C978655', 20, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 2', 5, '2025-06-18 04:23:33', '2025-06-25 04:23:00', 'used', '2025-06-18 06:36:49', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(93, '05B8F34A', 20, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 2', 5, '2025-06-18 04:23:56', '2025-06-28 04:23:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(94, 'DD8278AB', 27, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 5, '2025-06-18 04:48:09', '2025-06-19 04:47:00', 'used', '2025-06-18 04:54:10', NULL, NULL, 'MASTER BAK INSTRUMEN', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(95, 'D79EE4E5', 27, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 5, '2025-06-18 04:49:02', '2025-06-28 04:48:00', 'expired', NULL, NULL, NULL, 'CONDRO WIBOWO', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(96, 'E37432B9', 30, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 2', 5, '2025-06-18 05:18:59', '2025-06-19 05:18:00', 'used', '2025-06-18 06:36:34', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":8},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":6},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":4},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":2},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(97, 'CB6940D4', 30, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 2', 5, '2025-06-18 05:19:40', '2025-06-28 05:19:00', 'expired', NULL, NULL, NULL, 'condro wibowo', '[{\"instrument_id\":6,\"quantity\":8},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":6},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":4},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":2},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(98, 'CEE55777', 31, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 3', 5, '2025-06-18 05:27:55', '2025-06-19 05:26:00', 'used', '2025-06-18 06:36:43', NULL, NULL, 'MASTER BAK INSTRUMEN', '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":42,\"quantity\":2},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1}]', 'pending');
INSERT INTO `sterilization_records` (`record_id`, `label_unique_id`, `item_id`, `cycle_id`, `load_id`, `batch_number`, `sterilization_method`, `destination_department_id`, `item_type`, `label_title`, `created_by_user_id`, `created_at`, `expiry_date`, `status`, `used_at`, `validated_by_user_id`, `validated_at`, `notes`, `label_items_snapshot`, `print_status`) VALUES
(99, '3F5A3E3B', 31, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 3', 5, '2025-06-18 05:28:24', '2025-06-28 05:28:00', 'used', '2025-06-25 12:30:30', NULL, NULL, 'CONDRO WIBOWO', '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":42,\"quantity\":2},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(100, '8EA394FF', 23, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 1', 5, '2025-06-18 05:33:00', '2025-06-28 05:32:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":7},{\"instrument_id\":10,\"quantity\":3},{\"instrument_id\":11,\"quantity\":7},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":3},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":45,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":47,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":36,\"quantity\":2}]', 'pending'),
(101, '60224331', 18, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 4', 5, '2025-06-18 07:20:06', '2025-06-28 07:19:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":7,\"quantity\":4},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":21,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(102, '706F2C37', 30, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Pediatrik 2', 5, '2025-06-19 10:41:14', '2025-06-29 10:41:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":8},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":6},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":4},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":2},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":4},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":3},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(103, '7DC498EE', 27, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 5, '2025-06-19 10:42:12', '2025-06-29 10:41:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(104, '12F393B1', 33, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 7', 5, '2025-06-19 10:46:48', '2025-06-20 10:46:00', 'used', '2025-06-19 11:13:20', NULL, NULL, 'MASTER BAK INSTRUMEN', '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1}]', 'pending'),
(105, '1D331411', 33, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 7', 5, '2025-06-19 10:47:23', '2025-06-29 10:47:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":3},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":8,\"quantity\":1},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":3},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1}]', 'pending'),
(106, '56CC7947', 34, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 6', 5, '2025-06-19 10:53:35', '2025-06-20 10:53:00', 'used', '2025-06-19 11:13:40', NULL, NULL, 'MASTER BAK INSTRUMEN', '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":8,\"quantity\":3},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(107, '19B3E1AC', 34, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 6', 5, '2025-06-19 10:54:12', '2025-06-29 10:54:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":8,\"quantity\":3},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2}]', 'pending'),
(108, '62FAD760', 20, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 2', 5, '2025-06-20 03:45:30', '2025-06-30 03:45:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":2},{\"instrument_id\":7,\"quantity\":4},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":5},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(109, '32B92537', 27, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 5, '2025-06-20 03:45:50', '2025-06-30 03:45:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(110, '80DFB6DD', 29, NULL, NULL, NULL, NULL, NULL, 'set', 'IBS Kandungan 5', 5, '2025-06-20 06:41:20', '2025-06-30 06:40:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":2},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":23,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":4},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":40,\"quantity\":1}]', 'pending'),
(111, '83DAFFFC', 43, NULL, NULL, NULL, NULL, NULL, 'instrument', 'BAK ALAT', 1, '2025-06-25 12:40:25', '2025-07-02 19:40:00', 'expired', NULL, NULL, NULL, 'Catatan', NULL, 'pending'),
(112, 'B1A8479C', 70, 4, NULL, NULL, NULL, NULL, 'instrument', 'ABORTUS TANG BENGKOK', 1, '2025-06-25 16:49:52', '2025-07-02 23:49:00', 'recalled', NULL, NULL, NULL, 'RECALLED (public): 02-07-2025 15:06 - dsfsdfsd\n-----------------\n', NULL, 'pending'),
(113, 'CC5739BA', 70, 4, NULL, NULL, NULL, NULL, 'set', 'ABORTUS TANG BENGKOK', 1, '2025-06-26 06:32:06', '2025-07-03 13:30:00', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":2},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'pending'),
(114, '40CA7683', 17, 4, NULL, NULL, NULL, NULL, 'set', 'Set Lahiran Caesar', 1, '2025-06-26 16:00:55', '2025-07-03 22:59:00', 'expired', NULL, NULL, NULL, NULL, '[]', 'pending'),
(115, 'B5392700', 46, 4, NULL, 'BATCH X', 'EtO', 2, 'set', '0', 1, '2025-06-27 02:10:47', '2025-07-04 09:09:00', 'expired', NULL, NULL, NULL, '', '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'pending'),
(116, 'FA67B4FC', 27, 4, NULL, 'BATCH X', 'Steam', 4, 'set', '0', 1, '2025-06-27 02:13:54', '2025-07-04 09:13:00', 'expired', NULL, NULL, NULL, 'Catatan', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'pending'),
(117, 'C1BD61FE', 27, 4, NULL, 'BATCH X', 'Steam', 3, 'set', '0', 1, '2025-06-27 02:17:39', '2025-07-04 09:17:00', 'expired', NULL, NULL, NULL, '', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(118, '6F49299D', 29, 4, NULL, 'BATCH X', 'Steam', 4, 'instrument', '0', 1, '2025-06-27 02:19:15', '2025-07-04 09:18:00', 'expired', NULL, NULL, NULL, 'Catatan', NULL, 'pending'),
(119, 'F9141CE8', 7, 4, NULL, 'Baatch', 'Steam', 1, 'instrument', '0', 1, '2025-06-27 02:25:38', '2025-07-04 09:25:00', 'expired', NULL, NULL, NULL, 'Catatatn', NULL, 'pending'),
(120, '5342B291', 20, 4, NULL, 'BATCH X', 'Steam', 1, 'instrument', '0', 1, '2025-06-27 02:31:22', '2025-07-04 09:29:00', 'expired', NULL, NULL, NULL, 'Cataat', NULL, 'pending'),
(121, 'C0281A66', 12, 4, NULL, 'Batchhh', 'Steam', 2, 'instrument', '0', 1, '2025-06-27 02:34:54', '2025-07-04 09:34:00', 'expired', NULL, NULL, NULL, 'vvvv', NULL, 'pending'),
(122, '46977432', 27, 4, NULL, 'csacsaa', 'Steam', NULL, 'set', '0', 1, '2025-06-27 02:37:54', '2025-07-04 09:37:00', 'expired', NULL, NULL, NULL, '', '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1}]', 'pending'),
(123, 'CB3B9D60', 46, 4, NULL, '32432423', 'Steam', NULL, 'set', '3242432', 1, '2025-06-27 02:42:44', '2025-07-04 09:42:00', 'expired', NULL, NULL, NULL, '', '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1}]', 'pending'),
(124, 'E77B8137', 27, 7, 3, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'pending'),
(125, 'F29B632D', 85, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(126, 'FE437590', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(127, '509D9924', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(128, 'D58F8B66', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(129, '91A85BED', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(130, 'CFB70723', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:25:52', '2025-07-04 11:25:52', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(131, '2FEA61FA', 27, 7, 3, NULL, NULL, NULL, 'set', 'IBS Bedah 2', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'pending'),
(132, '34CA912E', 85, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(133, 'A10B25F8', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(134, '42B8F833', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(135, '2133543B', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(136, 'EA9F294F', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(137, 'B30D11E1', 19, 7, 3, NULL, NULL, NULL, 'instrument', 'Gunting benang', 1, '2025-06-27 04:28:11', '2025-07-04 11:28:11', 'expired', NULL, NULL, NULL, NULL, NULL, 'pending'),
(138, '64F64DA2', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 1, '2025-06-27 05:21:11', '2025-07-04 12:21:11', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'pending'),
(139, '52D47378', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 1, '2025-06-27 05:21:11', '2025-07-04 12:21:11', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'pending'),
(140, '7526B03F', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 1, '2025-06-27 05:21:11', '2025-07-04 12:21:11', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'pending'),
(141, '02AFDA92', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 1, '2025-06-27 08:49:25', '2025-07-04 15:49:25', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(142, '38D9A292', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 1, '2025-06-27 08:49:25', '2025-07-04 15:49:25', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(143, 'D2B4C6FC', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 1, '2025-06-27 08:49:25', '2025-07-04 15:49:25', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(144, '530859E7', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 1, '2025-06-27 08:51:06', '2025-07-04 15:51:06', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(145, 'CC649697', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 1, '2025-06-27 08:51:06', '2025-07-04 15:51:06', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(146, 'A6034925', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 1, '2025-06-27 08:51:06', '2025-07-04 15:51:06', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(147, 'B1D590CD', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 1, '2025-06-27 08:57:04', '2025-07-04 15:57:04', 'used', '2025-06-28 03:30:45', NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(148, 'A3573D89', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 1, '2025-06-27 08:57:04', '2025-07-04 15:57:04', 'expired', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(149, '5F8D3F3D', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 1, '2025-06-27 08:57:04', '2025-07-04 15:57:04', 'used', '2025-06-28 03:38:42', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(150, 'F351C1B7', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-28 06:22:02', '2025-07-05 13:22:02', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(151, '8E9084E5', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 5, '2025-06-28 06:22:02', '2025-07-05 13:22:02', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(152, 'F00CC21C', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 5, '2025-06-28 06:22:02', '2025-07-05 13:22:02', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(153, '9B017563', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-28 06:22:26', '2025-07-05 13:22:26', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(154, 'F8D353D0', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 5, '2025-06-28 06:22:26', '2025-07-05 13:22:26', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(155, 'E074A2F3', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 5, '2025-06-28 06:22:26', '2025-07-05 13:22:26', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(156, '688FC1F3', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-28 06:22:33', '2025-07-05 13:22:33', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(157, 'A6AF6B85', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 5, '2025-06-28 06:22:33', '2025-07-05 13:22:33', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(158, 'B2E63308', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 5, '2025-06-28 06:22:33', '2025-07-05 13:22:33', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(159, '639ADA3C', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-28 06:22:41', '2025-07-05 13:22:41', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(160, 'B098AA9E', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 5, '2025-06-28 06:22:41', '2025-07-05 13:22:41', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(161, '87FEB92D', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 5, '2025-06-28 06:22:41', '2025-07-05 13:22:41', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed');
INSERT INTO `sterilization_records` (`record_id`, `label_unique_id`, `item_id`, `cycle_id`, `load_id`, `batch_number`, `sterilization_method`, `destination_department_id`, `item_type`, `label_title`, `created_by_user_id`, `created_at`, `expiry_date`, `status`, `used_at`, `validated_by_user_id`, `validated_at`, `notes`, `label_items_snapshot`, `print_status`) VALUES
(162, '032B8A97', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 5, '2025-06-28 06:22:46', '2025-07-05 13:22:46', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":83,\"quantity\":1}]', 'printed'),
(163, '07E01901', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 5, '2025-06-28 06:22:46', '2025-07-05 13:22:46', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(164, 'C7FA45CF', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 5, '2025-06-28 06:22:46', '2025-07-05 13:22:46', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(165, 'A0A9EDC4', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-06-29 04:42:10', '2025-07-06 11:42:10', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(166, '8706C4AF', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 1, '2025-06-29 04:42:10', '2025-07-06 11:42:10', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(167, '4B0F9530', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-06-29 04:43:38', '2025-07-06 11:43:38', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(168, 'E0888C1D', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 1, '2025-06-29 04:43:38', '2025-07-06 11:43:38', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(169, 'F4D90A8F', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-06-29 04:44:04', '2025-07-06 11:44:04', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(170, '7709B0F3', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 1, '2025-06-29 04:44:04', '2025-07-06 11:44:04', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(171, 'AB0B2A4F', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-06-29 04:47:14', '2025-07-06 11:47:14', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(172, 'ED37C943', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 1, '2025-06-29 04:47:14', '2025-07-06 11:47:14', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(173, 'E199F7F9', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 6, '2025-06-29 16:48:24', '2025-07-06 23:48:24', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(174, '481FB99E', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 6, '2025-06-29 16:48:24', '2025-07-06 23:48:24', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(175, 'AFB9D4C3', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 6, '2025-06-29 16:49:29', '2025-07-06 23:49:29', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(176, '76EA25F2', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Gunting jaringan', 6, '2025-06-29 16:49:29', '2025-07-06 23:49:29', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(177, 'F1FF394A', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-07-01 16:30:45', '2026-07-01 16:30:45', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(178, 'F2CF0855', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Item Tidak Dikenal', 1, '2025-07-01 16:30:45', '2026-01-01 16:30:45', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(179, '8C76C9AC', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-07-01 16:31:03', '2026-07-01 16:31:03', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(180, '8D16ECA6', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Item Tidak Dikenal', 1, '2025-07-01 16:31:03', '2026-07-01 16:31:03', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(181, '835DBF5E', 45, 16, 18, NULL, NULL, 4, 'set', 'IBS Bedah 5', 1, '2025-07-01 16:31:13', '2026-07-01 16:31:13', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(182, 'F87BA889', 84, 16, 18, NULL, NULL, 4, 'instrument', 'Item Tidak Dikenal', 1, '2025-07-01 16:31:13', '2026-07-01 16:31:13', 'active', NULL, NULL, NULL, NULL, NULL, 'printed'),
(183, 'B94FA921', 17, 8, 6, NULL, NULL, NULL, 'set', 'CURET IBS', 1, '2025-07-01 16:50:34', '2025-07-08 16:50:34', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1}]', 'printed'),
(184, '49C8D172', 46, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 3', 1, '2025-07-01 16:50:34', '2025-07-08 16:50:34', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":5,\"quantity\":2},{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":2},{\"instrument_id\":10,\"quantity\":4},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":28,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":3},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(185, 'B0FCEBE7', 45, 8, 6, NULL, NULL, NULL, 'set', 'IBS Bedah 5', 1, '2025-07-01 16:50:34', '2025-07-08 16:50:34', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":2},{\"instrument_id\":19,\"quantity\":2},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":3},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(186, 'E28852C0', 27, 18, 22, NULL, NULL, 4, 'set', 'IBS Bedah 2', 1, '2025-07-03 14:23:26', '2025-07-10 14:23:26', 'used', '2025-07-03 17:15:17', NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":5},{\"instrument_id\":10,\"quantity\":1},{\"instrument_id\":11,\"quantity\":4},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":20,\"quantity\":1},{\"instrument_id\":24,\"quantity\":2},{\"instrument_id\":25,\"quantity\":2},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":4},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":2},{\"instrument_id\":35,\"quantity\":2},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1}]', 'printed'),
(187, 'C0CDDFA7', 22, 22, 28, NULL, NULL, 1, 'set', 'IBS Bedah 1 bak panjang ram', 1, '2025-07-04 17:07:47', '2025-07-11 17:07:47', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":6,\"quantity\":4},{\"instrument_id\":7,\"quantity\":3},{\"instrument_id\":10,\"quantity\":2},{\"instrument_id\":11,\"quantity\":5},{\"instrument_id\":12,\"quantity\":1},{\"instrument_id\":13,\"quantity\":1},{\"instrument_id\":14,\"quantity\":1},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":17,\"quantity\":5},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":19,\"quantity\":1},{\"instrument_id\":22,\"quantity\":1},{\"instrument_id\":25,\"quantity\":5},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":30,\"quantity\":1},{\"instrument_id\":31,\"quantity\":2},{\"instrument_id\":32,\"quantity\":2},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1},{\"instrument_id\":36,\"quantity\":1},{\"instrument_id\":37,\"quantity\":1},{\"instrument_id\":50,\"quantity\":2}]', 'printed'),
(188, '1AC47064', 16, 22, 28, NULL, NULL, 1, 'set', 'SET THT IBS', 1, '2025-07-04 17:07:47', '2025-07-11 17:07:47', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":18,\"quantity\":1},{\"instrument_id\":24,\"quantity\":1},{\"instrument_id\":25,\"quantity\":1},{\"instrument_id\":26,\"quantity\":1},{\"instrument_id\":27,\"quantity\":1},{\"instrument_id\":29,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":33,\"quantity\":1},{\"instrument_id\":49,\"quantity\":1},{\"instrument_id\":50,\"quantity\":1},{\"instrument_id\":51,\"quantity\":1},{\"instrument_id\":52,\"quantity\":1},{\"instrument_id\":53,\"quantity\":1},{\"instrument_id\":54,\"quantity\":1},{\"instrument_id\":55,\"quantity\":1},{\"instrument_id\":56,\"quantity\":1},{\"instrument_id\":57,\"quantity\":1},{\"instrument_id\":58,\"quantity\":1},{\"instrument_id\":59,\"quantity\":1},{\"instrument_id\":60,\"quantity\":1},{\"instrument_id\":61,\"quantity\":1},{\"instrument_id\":62,\"quantity\":1},{\"instrument_id\":63,\"quantity\":1},{\"instrument_id\":64,\"quantity\":1},{\"instrument_id\":65,\"quantity\":1},{\"instrument_id\":66,\"quantity\":1},{\"instrument_id\":67,\"quantity\":1},{\"instrument_id\":68,\"quantity\":1}]', 'printed'),
(189, 'CAB1BB2B', 17, 22, 28, NULL, NULL, 1, 'set', 'CURET IBS', 1, '2025-07-04 17:07:47', '2025-07-11 17:07:47', 'active', NULL, NULL, NULL, NULL, '[{\"instrument_id\":70,\"quantity\":1},{\"instrument_id\":71,\"quantity\":1},{\"instrument_id\":72,\"quantity\":1},{\"instrument_id\":73,\"quantity\":1},{\"instrument_id\":74,\"quantity\":1},{\"instrument_id\":75,\"quantity\":1},{\"instrument_id\":76,\"quantity\":1},{\"instrument_id\":77,\"quantity\":1},{\"instrument_id\":78,\"quantity\":1},{\"instrument_id\":80,\"quantity\":1},{\"instrument_id\":81,\"quantity\":1},{\"instrument_id\":79,\"quantity\":1},{\"instrument_id\":82,\"quantity\":1},{\"instrument_id\":12,\"quantity\":2},{\"instrument_id\":15,\"quantity\":1},{\"instrument_id\":16,\"quantity\":1},{\"instrument_id\":17,\"quantity\":1},{\"instrument_id\":31,\"quantity\":1},{\"instrument_id\":32,\"quantity\":1},{\"instrument_id\":35,\"quantity\":1}]', 'printed'),
(190, '65BCF3E8', 19, 22, 28, NULL, NULL, 1, 'instrument', 'Gunting benang', 1, '2025-07-04 17:07:47', '2025-07-11 17:07:47', 'active', NULL, NULL, NULL, NULL, NULL, 'printed');

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
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`notification_id`, `user_id`, `icon`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-01\' untuk muatan \'MUATAN-010725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=17', 1, '2025-07-01 08:50:27'),
(2, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-01\' untuk muatan \'MUATAN-010725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=17', 1, '2025-07-01 08:50:27'),
(3, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-02\' untuk muatan \'MUATAN-010725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=18', 1, '2025-07-01 16:27:56'),
(4, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-02\' untuk muatan \'MUATAN-010725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=18', 0, '2025-07-01 16:27:56'),
(5, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-02\' untuk muatan \'MUATAN-010725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=18', 0, '2025-07-01 16:27:56'),
(6, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-01\' untuk muatan \'MUATAN-010725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=19', 0, '2025-07-03 03:45:06'),
(7, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-01\' untuk muatan \'MUATAN-010725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=19', 0, '2025-07-03 03:45:06'),
(8, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-01\' untuk muatan \'MUATAN-010725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=19', 0, '2025-07-03 03:45:06'),
(9, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-02\' untuk muatan \'MUATAN-030725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=20', 0, '2025-07-03 14:40:46'),
(10, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-02\' untuk muatan \'MUATAN-030725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=20', 0, '2025-07-03 14:40:46'),
(11, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-02\' untuk muatan \'MUATAN-030725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=20', 0, '2025-07-03 14:40:46'),
(12, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-01\' untuk muatan \'MUATAN-030725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=17', 0, '2025-07-03 14:41:19'),
(13, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-01\' untuk muatan \'MUATAN-030725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=17', 0, '2025-07-03 14:41:19'),
(14, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-010725-01\' untuk muatan \'MUATAN-030725-02\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=17', 0, '2025-07-03 14:41:19'),
(15, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-04\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:20'),
(16, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-04\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:20'),
(17, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-04\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:20'),
(18, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:38'),
(19, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:38'),
(20, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-030725-03\' untuk muatan \'MUATAN-030725-03\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=21', 0, '2025-07-03 14:44:38'),
(21, 1, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-050725-01\' untuk muatan \'MUATAN-050725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=22', 0, '2025-07-04 17:07:03'),
(22, 6, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-050725-01\' untuk muatan \'MUATAN-050725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=22', 0, '2025-07-04 17:07:03'),
(23, 8, 'hourglass_top', 'Validasi Siklus Diperlukan', 'Siklus \'SIKLUS-AA-050725-01\' untuk muatan \'MUATAN-050725-01\' siap untuk divalidasi.', 'cycle_detail.php?cycle_id=22', 0, '2025-07-04 17:07:03');

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
-- Indexes for table `app_notifications`
--
ALTER TABLE `app_notifications`
  ADD PRIMARY KEY (`notification_id`);

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
-- Indexes for table `print_queue`
--
ALTER TABLE `print_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD UNIQUE KEY `unique_record_id` (`record_id`),
  ADD KEY `fk_queue_to_record` (`record_id`),
  ADD KEY `fk_queue_to_load` (`load_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_name_unique` (`session_name`);

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
  ADD KEY `fk_load_to_session` (`session_id`),
  ADD KEY `fk_load_to_department` (`destination_department_id`);

--
-- Indexes for table `sterilization_load_items`
--
ALTER TABLE `sterilization_load_items`
  ADD PRIMARY KEY (`load_item_id`),
  ADD KEY `fk_item_to_load` (`load_id`);

--
-- Indexes for table `sterilization_methods`
--
ALTER TABLE `sterilization_methods`
  ADD PRIMARY KEY (`method_id`),
  ADD UNIQUE KEY `method_name_unique` (`method_name`);

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
  ADD KEY `fk_record_to_load` (`load_id`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `app_notifications`
--
ALTER TABLE `app_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `instruments`
--
ALTER TABLE `instruments`
  MODIFY `instrument_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `instrument_history`
--
ALTER TABLE `instrument_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `instrument_sets`
--
ALTER TABLE `instrument_sets`
  MODIFY `set_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `instrument_set_items`
--
ALTER TABLE `instrument_set_items`
  MODIFY `set_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1386;

--
-- AUTO_INCREMENT for table `instrument_types`
--
ALTER TABLE `instrument_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `machine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `print_queue`
--
ALTER TABLE `print_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sterilization_cycles`
--
ALTER TABLE `sterilization_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `sterilization_loads`
--
ALTER TABLE `sterilization_loads`
  MODIFY `load_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `sterilization_load_items`
--
ALTER TABLE `sterilization_load_items`
  MODIFY `load_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `sterilization_methods`
--
ALTER TABLE `sterilization_methods`
  MODIFY `method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sterilization_records`
--
ALTER TABLE `sterilization_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

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
  ADD CONSTRAINT `fk_load_to_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE SET NULL ON UPDATE CASCADE,
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
