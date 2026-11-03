-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 15, 2026 at 03:44 AM
-- Server version: 8.0.46
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pme_v2_1_newgen`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `user_id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `e_signature_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active_stat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rank` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `afpos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`user_id`, `username`, `password`, `e_signature_path`, `user_username`, `active_stat`, `rank`, `afpos`, `status`, `position`, `last_login_at`, `last_login_ip`) VALUES
(1, 'RECRUITER', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, NULL, '1', NULL, NULL, NULL, NULL, '2026-10-13 22:15:58', '127.0.0.1'),
(2, 'REGISTRAR', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, NULL, '1', NULL, NULL, NULL, NULL, '2026-09-13 19:34:40', '127.0.0.1'),
(6, 'DOCTOR', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/NC6wkrc9PrJbVYTWXAbLCokqiSypf6G3e3cK8Wov.png', '', '1', 'COL', 'MC', 'GSC', 'CHIEF DOCTOR', '2026-09-13 18:53:42', '127.0.0.1'),
(32, 'DOCTOR_ECG_XRAY', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/rXeiCPM717aNX4V6oA2Ab2XDqQOG26sOw18KhABl.png', 'jose p rizal', '1', 'LTC', 'MC', 'GSC', 'MEMBER', '2026-09-13 18:52:29', '127.0.0.1'),
(35, 'DOCTOR_NP', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/hXTSn1DmtZVgehyUZS5v8Z84itbxuUs3qJWNRHlN.png', 'baldonado m baltog', '1', 'COL', 'MC', 'GSC', 'CHAIRMAN', '2026-09-13 18:52:48', '127.0.0.1'),
(36, 'DOCTOR_OPTHA_EENT', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/1c2vlZM2okhBZkzqAZEs8BldtERz4hJD0H9Ug9kY.png', 'anastacio j balbitero', '1', 'MAJ', 'MC', 'GSC', 'MEMBER', '2026-09-13 18:51:51', '127.0.0.1'),
(39, 'DOCTOR_DENTAL', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/kYdoNzUNGsWdbNq2Ge9oySodE2SYJz60T90XA52j.png', 'lauro p lagim', '1', 'MAJ', 'MC', 'GSC', 'MEMBER', '2026-09-13 18:50:29', '127.0.0.1'),
(40, 'DENTAL', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, 'laag', '1', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'ECG', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, 'Pvt Jovy Japril', '1', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'NP', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, 'Lourd', '1', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'DOCTOR_LAB_DRUGTEST', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/QR9aZqrjEsGDkHPPJKeSVefkgGIaaJZ30kVQAZa7.png', 'bogart d macapagal', '1', 'LTC', 'MC', 'GSC', 'MEMBER', '2026-09-13 18:53:24', '127.0.0.1'),
(45, 'EXECUTIVE', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, 'Lourd', '1', NULL, NULL, NULL, NULL, '2026-10-04 17:54:11', '127.0.0.1'),
(53, 'DOCTOR_GPE', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', 'signatures/0Ghvs6AFCg7GvOztdxXpj65bFILZh4nYSYpMFwNE.png', 'Pedro D Penduko', '1', 'CPT', 'MC', NULL, 'MEMBER', '2026-09-13 18:36:53', '127.0.0.1'),
(54, 'GPE', '$2y$12$cGTafCxuoruytel1CA55z.heHd2u0cEd9mrXSJ/kvyyGs/aHqMB8e', NULL, 'Pvt Jovy Japril', '1', NULL, NULL, NULL, NULL, '2026-09-02 19:24:28', '127.0.0.1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
