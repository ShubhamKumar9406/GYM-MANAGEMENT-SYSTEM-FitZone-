-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 07:34 PM
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
-- Database: `gym_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `status` enum('working','maintenance','damaged') DEFAULT 'working',
  `notes` text DEFAULT NULL,
  `equipment_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `purchase_date`, `purchase_price`, `status`, `notes`, `equipment_image`, `created_at`, `updated_at`) VALUES
(1, 'Treadmill - Model A', '2024-01-01', 50000.00, 'working', '', 'equipment_treadmillmodela_1764785954.png', '2025-12-03 13:47:14', '2025-12-03 18:19:14'),
(2, 'Exercise Bike - Model B', '2024-01-01', 30000.00, 'working', '', 'equipment_exercisebikemodelb_1764786026.jpg', '2025-12-03 13:47:14', '2025-12-03 18:20:26'),
(3, 'Dumbbells Set (5-50kg)', '2024-01-05', 40000.00, 'working', '', 'equipment_dumbbellsset550kg_1764786075.jpg', '2025-12-03 13:47:14', '2025-12-03 18:21:15'),
(4, 'Leg Press Machine', '2024-01-10', 80000.00, 'working', '', 'equipment_legpressmachine_1764786197.png', '2025-12-03 13:47:14', '2025-12-03 18:23:17'),
(5, 'Chest Press Machine', '2024-01-10', 75000.00, 'working', '', 'equipment_chestpressmachine_1764786293.png', '2025-12-03 13:47:14', '2025-12-03 18:24:53'),
(6, 'Rowing Machine', '2024-01-15', 45000.00, 'working', '', 'equipment_rowingmachine_1764786368.png', '2025-12-03 13:47:14', '2025-12-03 18:26:08');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `time_slot_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','inactive') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `plan_id`, `time_slot_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 3, 4, 2, '2025-12-03', '2026-12-03', 'active', '2025-12-03 14:41:03', '2025-12-03 16:36:51'),
(3, 4, 2, 4, '2025-12-03', '2026-03-03', 'active', '2025-12-03 16:50:13', '2025-12-03 17:04:06'),
(4, 5, 3, 2, '2025-12-03', '2026-06-01', 'active', '2025-12-03 17:06:55', '2025-12-03 18:00:51'),
(5, 6, 2, NULL, NULL, NULL, 'inactive', '2025-12-03 17:58:30', '2025-12-03 17:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('paid','due','pending') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `member_id`, `user_id`, `plan_id`, `amount`, `payment_date`, `status`, `payment_method`, `notes`, `created_at`) VALUES
(2, 2, 3, 4, 14000.00, '2025-12-03', 'paid', 'UPI', 'Method: UPI | Transaction ID: 507426283436', '2025-12-03 14:43:02'),
(3, 3, 4, 2, 4000.00, '2025-12-03', 'paid', 'Cash', 'Method: Cash', '2025-12-03 16:58:35'),
(4, 4, 5, 3, 7500.00, '2025-12-03', 'paid', 'Net Banking', 'Method: Net Banking | Transaction ID: 13124325325235', '2025-12-03 17:07:51'),
(5, 5, 6, 2, 4000.00, '2025-12-03', 'due', 'Cash', 'Method: Cash | Transaction ID: Cash', '2025-12-03 17:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `price`, `duration_days`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Monthly Basic', 1500.00, 30, 'Access to gym equipment and basic training', '2025-12-03 13:47:14', '2025-12-03 13:47:14'),
(2, 'Quarterly Standard', 4000.00, 90, 'Access to gym equipment, group classes, and diet consultation', '2025-12-03 13:47:14', '2025-12-03 13:47:14'),
(3, 'Half-Yearly Premium', 7500.00, 180, 'Full access including personal training and diet plan', '2025-12-03 13:47:14', '2025-12-03 13:47:14'),
(4, 'Yearly Elite', 14000.00, 365, 'All features plus spa and advanced training programs', '2025-12-03 13:47:14', '2025-12-03 13:47:14');

-- --------------------------------------------------------

--
-- Table structure for table `slot_requests`
--

CREATE TABLE `slot_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_slot_id` int(11) NOT NULL,
  `current_slot_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `role`, `phone`, `email`, `salary`, `join_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Shubham', 'Personal Trainer', '9876543210', 'shubham@gym.com', 25000.00, '2024-01-15', 'active', '2025-12-03 13:47:14', '2025-12-03 16:09:35'),
(2, 'Sarah Coach', 'Fitness Coach', '9876543211', 'sarah@gym.com', 22000.00, '2024-02-01', 'active', '2025-12-03 13:47:14', '2025-12-03 13:47:14'),
(3, 'Rahul Roy', 'Floor Manager', '9876543212', 'rahul@gym.com', 20000.00, '2024-01-10', 'active', '2025-12-03 13:47:14', '2025-12-03 16:10:29');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `slot_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_members` int(11) DEFAULT 20,
  `current_members` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `slot_name`, `start_time`, `end_time`, `max_members`, `current_members`, `status`, `created_at`) VALUES
(1, 'Early Morning', '06:00:00', '07:00:00', 20, 0, 'active', '2025-12-03 13:47:14'),
(2, 'Morning', '07:00:00', '08:00:00', 25, 2, 'active', '2025-12-03 13:47:14'),
(3, 'Late Morning', '08:00:00', '09:00:00', 25, 0, 'active', '2025-12-03 13:47:14'),
(4, 'Mid Morning', '09:00:00', '10:00:00', 20, 1, 'active', '2025-12-03 13:47:14'),
(5, 'Afternoon', '16:00:00', '17:00:00', 20, 0, 'active', '2025-12-03 13:47:14'),
(6, 'Evening', '17:00:00', '18:00:00', 30, 0, 'active', '2025-12-03 13:47:14'),
(7, 'Late Evening', '18:00:00', '19:00:00', 30, 0, 'active', '2025-12-03 13:47:14'),
(8, 'Night', '19:00:00', '20:00:00', 25, 0, 'active', '2025-12-03 13:47:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('pending','approved','blocked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `gender`, `profile_image`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gym.com', '1234567890', 'male', NULL, '$2y$10$k7gcA5vWvOX5/cCYmOevqOHzdodSSuJFnklVTfB4Ul0i9YF6qu3MW', 'admin', 'approved', '2025-12-03 13:47:14', '2025-12-03 16:46:12'),
(3, 'Vivek Sharma', 'vivek@gym.com', '7777555500', 'male', 'user_3_viveksharma.jpg', '$2y$10$zCT2dxjABMJYSXCdybf7suskAyJ5R5fpClb9QmgeLapSa8thE7SUi', 'user', 'approved', '2025-12-03 14:41:03', '2025-12-03 18:05:53'),
(4, 'Shubham', 'shubham@gym.com', '7894560000', 'male', NULL, '$2y$10$xnbOE0rOYFwzufgyjEP/ZOYNB683UnGCn3U4Z/n0ul6ujKHTU8tzm', 'user', 'approved', '2025-12-03 16:50:13', '2025-12-03 16:54:39'),
(5, 'Muskan Sharma', 'muskan@gym.com', '8856458552', 'female', NULL, '$2y$10$yZNv1.PEIvnaQi0ubv83kOjUb6JgiL2BWwtX2lQrGD3FL/rjuaKG6', 'user', 'approved', '2025-12-03 17:06:55', '2025-12-03 17:12:11'),
(6, 'Rahul', 'rahul@gym.com', '1234567895', 'male', NULL, '$2y$10$yuLpo20cXMT8/Vf4LIgFke5ar3Q04QmEf4M1l5iQu79aZwfqLGMzi', 'user', 'pending', '2025-12-03 17:58:30', '2025-12-03 17:58:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slot_requests`
--
ALTER TABLE `slot_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pending_request` (`user_id`,`status`),
  ADD KEY `requested_slot_id` (`requested_slot_id`),
  ADD KEY `current_slot_id` (`current_slot_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `slot_requests`
--
ALTER TABLE `slot_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `members_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `slot_requests`
--
ALTER TABLE `slot_requests`
  ADD CONSTRAINT `slot_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `slot_requests_ibfk_2` FOREIGN KEY (`requested_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `slot_requests_ibfk_3` FOREIGN KEY (`current_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
