-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2025 at 11:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_parking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `hours` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `lot_id`, `hours`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, 2, 2, 20.00, NULL, 'Pending', '2025-07-18 10:41:29'),
(2, 1, 2, 2, 20.00, NULL, 'Pending', '2025-07-18 10:41:41'),
(3, 1, 2, 25, 250.00, 'UPI', 'Confirmed', '2025-07-18 10:41:55'),
(4, 1, 1, 0, 0.00, NULL, 'Pending', '2025-07-18 12:18:12'),
(5, 1, 1, 0, 0.00, NULL, 'Pending', '2025-07-18 12:19:03'),
(6, 1, 1, 0, 0.00, NULL, 'Pending', '2025-07-18 12:19:51'),
(7, 1, 1, 1, 10.00, NULL, 'Pending', '2025-07-18 12:25:24'),
(8, 1, 3, 0, 0.00, NULL, 'Pending', '2025-07-18 15:28:57'),
(9, 1, 3, 2, 20.00, NULL, 'Pending', '2025-07-18 15:29:39'),
(10, 1, 1, 2, 0.00, 'Test Payment', 'Confirmed', '2025-07-29 21:05:00'),
(11, 1, 3, 1, 10.00, 'card', 'Paid', '2025-07-29 21:09:13'),
(12, 1, 3, 2, 20.00, NULL, 'Paid', '2025-07-29 21:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `parking_lots`
--

CREATE TABLE `parking_lots` (
  `id` int(11) NOT NULL,
  `lot_name` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `price_per_hour` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_lots`
--
INSERT INTO `parking_lots` (`id`, `lot_name`, `location`, `price_per_hour`) VALUES
(1, 'PVP', 'Vijayawada', 10.00),
(2, 'DMART', 'Vijayawada', 20.00),
(3, 'DMART', 'Guntur', 20.00),
(4, 'PHEONIX MALL', 'Guntur', 20.00);


-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `last_four` varchar(4) NOT NULL,
  `expiry` varchar(5) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `vehicle_id` int(11) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `vehicle_number` varchar(20) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `slot_id`, `start_time`, `end_time`, `status`, `vehicle_id`, `booking_date`, `vehicle_number`, `total_cost`, `created_at`) VALUES
(1, 1, 1, '2025-07-18 15:56:00', '2025-07-19 15:56:00', 'active', NULL, NULL, NULL, 0.00, '2025-07-18 21:30:36'),
(2, 1, NULL, '2025-07-29 23:05:00', '2025-07-30 01:05:00', 'active', NULL, NULL, NULL, 0.00, '2025-07-30 02:35:00'),
(3, 1, NULL, '2025-07-29 23:09:47', '2025-07-30 00:09:47', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:39:47'),
(4, 1, NULL, '2025-07-29 23:09:53', '2025-07-30 00:09:53', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:39:53'),
(5, 1, NULL, '2025-07-29 23:11:20', '2025-07-30 00:11:20', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:41:20'),
(6, 1, NULL, '2025-07-29 23:12:09', '2025-07-30 00:12:09', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:42:09'),
(7, 1, NULL, '2025-07-29 23:13:59', '2025-07-30 00:13:59', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:43:59'),
(8, 1, NULL, '2025-07-29 23:15:33', '2025-07-30 00:15:33', 'active', 1, NULL, NULL, 10.00, '2025-07-30 02:45:33'),
(9, 1, NULL, '2025-07-29 23:17:47', '2025-07-30 01:17:47', 'active', 1, NULL, NULL, 20.00, '2025-07-30 02:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `slots`
--

CREATE TABLE `slots` (
  `id` int(11) NOT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `slot_number` varchar(50) DEFAULT NULL,
  `status` enum('available','booked') DEFAULT 'available',
  `slot_type` varchar(50) DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slots`
--

INSERT INTO `slots` (`id`, `lot_id`, `slot_number`, `status`, `slot_type`) VALUES
(1, 1, '3', '', 'vip'),
(2, 2, '7', 'available', 'Regular'),
(4, 3, '9', 'available', 'Regular'),
(5, 3, '9', 'available', 'Regular');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `member_since` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `name`, `email`, `password`, `role`, `created_at`, `wallet_balance`, `phone`, `address`, `dob`, `member_since`) VALUES
(1, NULL, 'sudheer', 'sudheervasamsetti566@gmail.com', 'Sudhir12@', 'user', '2025-07-18 10:15:25', 0.00, NULL, NULL, NULL, NULL),
(2, NULL, 'Admin', 'admin@parking.com', 'admin123', 'admin', '2025-07-18 10:19:47', 0.00, NULL, NULL, NULL, NULL),
(3, NULL, 'sudheer', 'sudheervasamsetti123@gmail.com', 'Sudhir12@', 'user', '2025-07-29 20:38:16', 0.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `license` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `user_id`, `make`, `model`, `year`, `license`, `color`, `is_default`, `created_at`) VALUES
(1, 1, 'Default', 'Vehicle', NULL, 'DEFAULT', NULL, 0, '2025-07-29 21:11:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parking_lots`
--
ALTER TABLE `parking_lots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `slots`
--
ALTER TABLE `slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lot_id` (`lot_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `parking_lots`
--
ALTER TABLE `parking_lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `slots`
--
ALTER TABLE `slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`);

--
-- Constraints for table `slots`
--
ALTER TABLE `slots`
  ADD CONSTRAINT `slots_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `parking_lots` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
