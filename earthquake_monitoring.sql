-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 19, 2026 at 08:12 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `earthquake_monitoring` 
--
CREATE DATABASE IF NOT EXISTS `earthquake_monitoring` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `earthquake_monitoring`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users` 
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_users` 
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `created_at`, `last_login`) VALUES(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@ndscpm.edu.ph', '2026-04-02 23:10:46', '2026-04-17 14:00:39');
INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `created_at`, `last_login`) VALUES(2, 'adminSienna', '$2y$10$9I9TQFEMPhYjA3HjLBaTAuu.rep56IYAQf/OUJhFxHn1CTuogdw2a', 'Sienna Administrator', 'admin@ndscpm.edu.ph', '2026-04-02 23:15:30', '2026-07-19 14:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `alert_recipients` 
--

CREATE TABLE `alert_recipients` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `category` enum('student','faculty','staff','admin') DEFAULT 'student',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `alert_recipients` 
--

INSERT INTO `alert_recipients` (`id`, `name`, `phone_number`, `category`, `is_active`, `created_at`) VALUES(4, 'Mac', '09518235942', 'student', 0, '2026-04-04 21:40:52');
INSERT INTO `alert_recipients` (`id`, `name`, `phone_number`, `category`, `is_active`, `created_at`) VALUES(6, 'Tressia', '09518235943', 'student', 1, '2026-06-06 15:55:10');

-- --------------------------------------------------------

--
-- Table structure for table `seismic_logs` 
--

CREATE TABLE `seismic_logs` (
  `id` int NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `intensity` decimal(10,2) NOT NULL,
  `magnitude` decimal(3,1) DEFAULT NULL,
  `mmi_level` varchar(10) DEFAULT NULL,
  `mmi_name` varchar(50) DEFAULT NULL,
  `percent_g` float DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `alert_sent` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs` 
--

CREATE TABLE `sms_logs` (
  `id` int NOT NULL,
  `log_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users` 
--
ALTER TABLE `admin_users` 
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `alert_recipients` 
--
ALTER TABLE `alert_recipients` 
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_phone` (`phone_number`);

--
-- Indexes for table `seismic_logs` 
--
ALTER TABLE `seismic_logs` 
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_intensity` (`intensity`),
  ADD KEY `idx_magnitude` (`magnitude`);

--
-- Indexes for table `sms_logs` 
--
ALTER TABLE `sms_logs` 
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_id` (`log_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users` 
--
ALTER TABLE `admin_users` 
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alert_recipients` 
--
ALTER TABLE `alert_recipients` 
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seismic_logs` 
--
ALTER TABLE `seismic_logs` 
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs` 
--
ALTER TABLE `sms_logs` 
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sms_logs` 
--
ALTER TABLE `sms_logs` 
  ADD CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `seismic_logs` (`id`),
  ADD CONSTRAINT `sms_logs_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `alert_recipients` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;