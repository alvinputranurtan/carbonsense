-- phpMyAdmin SQL Dump
-- Struktur saja (tanpa data)
-- Database: `telkomam_carbonsense`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `billing`
-- --------------------------------------------------------
CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `avg_env_score` float DEFAULT 0,
  `payment_amount` decimal(12,2) DEFAULT 0.00,
  `card_last4` varchar(4) DEFAULT NULL,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
-- Table structure for table `sensor_data`
-- --------------------------------------------------------
CREATE TABLE `sensor_data` (
  `id` bigint(20) NOT NULL,
  `node_id` int(11) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
-- Table structure for table `sensor_nodes`
-- --------------------------------------------------------
CREATE TABLE `sensor_nodes` (
  `id` int(11) NOT NULL,
  `node_name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_label` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `profile_bio` text DEFAULT NULL,
  `profile_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profile_meta`)),
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Indexes
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_billing_user` (`user_id`);

ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `node_id` (`node_id`,`created_at`);

ALTER TABLE `sensor_nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- AUTO_INCREMENT
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sensor_data`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sensor_nodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign keys
ALTER TABLE `billing`
  ADD CONSTRAINT `fk_billing_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sensor_data`
  ADD CONSTRAINT `sensor_data_ibfk_1`
    FOREIGN KEY (`node_id`) REFERENCES `sensor_nodes` (`id`);

ALTER TABLE `sensor_nodes`
  ADD CONSTRAINT `sensor_nodes_ibfk_1`
    FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
