-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 04:44 AM
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
-- Database: `fyp`
--

-- --------------------------------------------------------

--
-- Table structure for table `codes`
--

CREATE TABLE `codes` (
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_string` varchar(19) NOT NULL,
  `item_name` text NOT NULL,
  `date_purchased` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leagues`
--

CREATE TABLE `leagues` (
  `league_id` int(11) NOT NULL,
  `league_name` text NOT NULL,
  `creator_id` int(11) NOT NULL,
  `code` varchar(8) NOT NULL,
  `end_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leagues`
--

INSERT INTO `leagues` (`league_id`, `league_name`, `creator_id`, `code`, `end_date`) VALUES
(8, 'Example League - In Progress', 52, 'U5zihcUv', '2025-11-06 02:20:14'),
(9, 'Example League - Finished', 57, 'DWjdu8IK', '2025-04-05 02:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `league_entries`
--

CREATE TABLE `league_entries` (
  `league_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `final_reward_claimed` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `league_entries`
--

INSERT INTO `league_entries` (`league_id`, `user_id`, `final_reward_claimed`) VALUES
(8, 52, 0),
(8, 53, 0),
(8, 54, 0),
(8, 55, 0),
(8, 56, 0),
(9, 57, 0);

-- --------------------------------------------------------

--
-- Table structure for table `league_leaderboards`
--

CREATE TABLE `league_leaderboards` (
  `league_leaderboard_id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `exercise` text NOT NULL,
  `num_reps` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `league_leaderboards`
--

INSERT INTO `league_leaderboards` (`league_leaderboard_id`, `league_id`, `exercise`, `num_reps`) VALUES
(36, 8, 'Barbell Bench Press (Flat)', 1),
(37, 8, 'Barbell Bicep Curls', 10),
(38, 8, 'Dumbell Bench Press (Incline)', 3),
(39, 8, 'Barbell Squats', 1),
(40, 8, 'Pull ups', 5),
(41, 9, 'Barbell Bicep Curls', 3),
(42, 9, 'Barbell Rows', 10),
(43, 9, 'Barbell Shoulder Press', 1),
(44, 9, 'Barbell Squats', 5),
(45, 9, 'Deadlifts', 1);

-- --------------------------------------------------------

--
-- Table structure for table `league_leaderboards_entries`
--

CREATE TABLE `league_leaderboards_entries` (
  `leaderboard_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `video` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `league_leaderboards_entries`
--

INSERT INTO `league_leaderboards_entries` (`leaderboard_id`, `user_id`, `score`, `video`) VALUES
(36, 52, 80, '../uploads/video_6819569489a0f9.08027836.mov'),
(36, 53, 65, '../uploads/video_681957363ef097.80862943.mov'),
(36, 54, 110, '../uploads/video_68195a1e455fe9.89733936.mov'),
(36, 55, 90, '../uploads/video_68195b19c4fc57.44590143.mov'),
(36, 56, 45, '../uploads/video_68195c306c6271.08609663.mov'),
(37, 52, 22.5, '../uploads/video_681958d424bdd4.21145056.mov'),
(37, 53, 30, '../uploads/video_6819578b8e7982.84774114.mov'),
(37, 54, 35, '../uploads/video_68195a3343f8d7.12542242.mov'),
(37, 55, 17.5, '../uploads/video_68195b34931523.88849232.mov'),
(37, 56, 12.5, '../uploads/video_68195cefd7b930.40358376.mov'),
(38, 52, 26, '../uploads/video_6819591c0df2e0.64511640.mov'),
(38, 53, 28, '../uploads/video_681957ebc99c14.22661073.mov'),
(38, 54, 42, '../uploads/video_68195a44081e91.93846038.mov'),
(38, 55, 30, '../uploads/video_68195b47a171f0.35761129.mov'),
(38, 56, 12, '../uploads/video_68195d0c71f0f3.11400981.mov'),
(39, 52, 85, '../uploads/video_6819592d30e283.56989884.mov'),
(39, 53, 112.5, '../uploads/video_6819580f568cc2.29519350.mov'),
(39, 54, 60, '../uploads/video_68195a55431d10.82217124.mov'),
(39, 55, 95, '../uploads/video_68195b5785ccc9.81443430.mov'),
(39, 56, 40, '../uploads/video_68195d1e6af687.48696364.mov'),
(40, 52, 2.5, '../uploads/video_68195940ced8e0.30907316.mov'),
(40, 53, 7.5, '../uploads/video_6819588bc243b7.36008000.mov'),
(40, 54, 15, '../uploads/video_68195a66cd5d11.72013216.mov'),
(40, 55, 10, '../uploads/video_68195b67ec9d24.09193979.mov'),
(40, 56, 0, 'null'),
(41, 57, 0, 'null'),
(42, 57, 0, 'null'),
(43, 57, 60, '../uploads/video_68195dff07bc14.87046122.mov'),
(44, 57, 0, 'null'),
(45, 57, 0, 'null');

-- --------------------------------------------------------

--
-- Table structure for table `private_leaderboards`
--

CREATE TABLE `private_leaderboards` (
  `private_leaderboard_id` int(11) NOT NULL,
  `leaderboard_name` text NOT NULL,
  `code` varchar(8) NOT NULL,
  `exercise` text NOT NULL,
  `num_reps` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_leaderboards`
--

INSERT INTO `private_leaderboards` (`private_leaderboard_id`, `leaderboard_name`, `code`, `exercise`, `num_reps`, `creator_id`) VALUES
(34, 'Example Private Leaderboard', 'hqOEKSH0', 'Dips', 10, 52);

-- --------------------------------------------------------

--
-- Table structure for table `private_leaderboards_entries`
--

CREATE TABLE `private_leaderboards_entries` (
  `leaderboard_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `video` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_leaderboards_entries`
--

INSERT INTO `private_leaderboards_entries` (`leaderboard_id`, `user_id`, `score`, `video`) VALUES
(34, 52, 20, '../uploads/video_6819544ba8c4f8.88668407.mov'),
(34, 53, 27.5, '../uploads/video_6819556a68d1b5.40412768.mov'),
(34, 54, 35, '../uploads/video_681959d6845e42.60601291.mov'),
(34, 55, 25, '../uploads/video_68195aecb83939.94220816.mov'),
(34, 56, 2.5, '../uploads/video_68195be436da32.14675569.mov');

-- --------------------------------------------------------

--
-- Table structure for table `public_leaderboards`
--

CREATE TABLE `public_leaderboards` (
  `public_entry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exercise` text NOT NULL,
  `num_reps` int(11) NOT NULL,
  `score` float NOT NULL,
  `video` varchar(255) NOT NULL,
  `flags` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `public_leaderboards`
--

INSERT INTO `public_leaderboards` (`public_entry_id`, `user_id`, `exercise`, `num_reps`, `score`, `video`, `flags`) VALUES
(79, 52, 'Barbell Bench Press (Flat)', 1, 60, '../uploads/video_681953cacf4380.28455407.mov', 0),
(80, 53, 'Barbell Bench Press (Flat)', 1, 82.5, '../uploads/video_68195522172b35.57650066.mov', 0),
(81, 54, 'Barbell Bench Press (Flat)', 1, 110, '../uploads/video_68195992f3ed03.71195778.mov', 0),
(82, 55, 'Barbell Bench Press (Flat)', 1, 90, '../uploads/video_68195ab41faef0.63998778.mov', 0),
(83, 56, 'Barbell Bench Press (Flat)', 1, 45, '../uploads/video_68195bacd905d2.10229229.mov', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(128) NOT NULL,
  `last_name` varchar(128) NOT NULL,
  `current_level` int(11) NOT NULL,
  `balance` float NOT NULL,
  `daily_claimed_time` datetime NOT NULL,
  `current_xp` int(11) NOT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expiration` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `current_level`, `balance`, `daily_claimed_time`, `current_xp`, `reset_token_hash`, `reset_token_expiration`) VALUES
(52, 'user1', 'user1@gmail.com', '$2y$10$EAf8mxcvAp8eFrxfAp409.gsowSw8e6WW3guxlV9GjhYtRTT3IQ6.', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 250, NULL, NULL),
(53, 'user2', 'user2@gmail.com', '$2y$10$9kXrbjGYoTyE4Y2M5UR.WOMXOMTUQlO3lhekIhtrrqwR8njT8s5wK', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 250, NULL, NULL),
(54, 'user3', 'user3@gmail.com', '$2y$10$OZtM8N2JZE.Ajr4hxU3ZwOlUK89Ya5CHhDSHOm3X40hFi31xtI6b6', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 250, NULL, NULL),
(55, 'user4', 'user4@gmail.com', '$2y$10$NedX0XszCehypZ2lA2aE9.4/2QVxfWDrz7YpCn/b7d6Kb07cXttvu', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 250, NULL, NULL),
(56, 'user5', 'user5@gmail.com', '$2y$10$CVllgsarrHB0ZQKDg85RH.SiE6MCBxAcDeVUZsbjpjJ.ZjhpK5r.O', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 250, NULL, NULL),
(57, 'LeagueRewardTester', 'endofleague@gmail.com', '$2y$10$jhElVamnjsAeuyU4HRzS2ufIAFIu6o2jSkmYbR54L2QyoIhNU2w0u', 'test', 'user', 1, 100, '2001-01-01 00:00:00', 0, NULL, NULL),
(58, 'experiencedUser', 'experienceduser@gmail.com', '$2y$10$7blanIsDZLlI7TBa.lJwWuT3m8LkOpgtv0guGDrwPdn0/eKHPd7l6', 'test', 'user', 34, 25000, '2001-01-01 00:00:00', 34250, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `codes`
--
ALTER TABLE `codes`
  ADD PRIMARY KEY (`code_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leagues`
--
ALTER TABLE `leagues`
  ADD PRIMARY KEY (`league_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `league_entries`
--
ALTER TABLE `league_entries`
  ADD PRIMARY KEY (`league_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `league_leaderboards`
--
ALTER TABLE `league_leaderboards`
  ADD PRIMARY KEY (`league_leaderboard_id`),
  ADD KEY `league_id` (`league_id`);

--
-- Indexes for table `league_leaderboards_entries`
--
ALTER TABLE `league_leaderboards_entries`
  ADD PRIMARY KEY (`leaderboard_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `private_leaderboards`
--
ALTER TABLE `private_leaderboards`
  ADD PRIMARY KEY (`private_leaderboard_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `private_leaderboards_entries`
--
ALTER TABLE `private_leaderboards_entries`
  ADD PRIMARY KEY (`leaderboard_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `public_leaderboards`
--
ALTER TABLE `public_leaderboards`
  ADD PRIMARY KEY (`public_entry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `codes`
--
ALTER TABLE `codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `leagues`
--
ALTER TABLE `leagues`
  MODIFY `league_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `league_leaderboards`
--
ALTER TABLE `league_leaderboards`
  MODIFY `league_leaderboard_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `private_leaderboards`
--
ALTER TABLE `private_leaderboards`
  MODIFY `private_leaderboard_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `public_leaderboards`
--
ALTER TABLE `public_leaderboards`
  MODIFY `public_entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `codes`
--
ALTER TABLE `codes`
  ADD CONSTRAINT `codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `leagues`
--
ALTER TABLE `leagues`
  ADD CONSTRAINT `leagues_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `league_entries`
--
ALTER TABLE `league_entries`
  ADD CONSTRAINT `league_entries_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`league_id`),
  ADD CONSTRAINT `league_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `league_leaderboards`
--
ALTER TABLE `league_leaderboards`
  ADD CONSTRAINT `league_leaderboards_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`league_id`);

--
-- Constraints for table `league_leaderboards_entries`
--
ALTER TABLE `league_leaderboards_entries`
  ADD CONSTRAINT `league_leaderboards_entries_ibfk_1` FOREIGN KEY (`leaderboard_id`) REFERENCES `league_leaderboards` (`league_leaderboard_id`),
  ADD CONSTRAINT `league_leaderboards_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `private_leaderboards`
--
ALTER TABLE `private_leaderboards`
  ADD CONSTRAINT `private_leaderboards_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `private_leaderboards_entries`
--
ALTER TABLE `private_leaderboards_entries`
  ADD CONSTRAINT `private_leaderboards_entries_ibfk_1` FOREIGN KEY (`leaderboard_id`) REFERENCES `private_leaderboards` (`private_leaderboard_id`),
  ADD CONSTRAINT `private_leaderboards_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `public_leaderboards`
--
ALTER TABLE `public_leaderboards`
  ADD CONSTRAINT `public_leaderboards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
