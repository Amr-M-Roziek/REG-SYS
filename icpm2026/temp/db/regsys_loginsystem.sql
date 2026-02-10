-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 09, 2026 at 11:59 AM
-- Server version: 11.4.9-MariaDB-cll-lve-log
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `regsys_loginsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'f925916e2754e5e03f75dd58a5733251');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(300) DEFAULT NULL,
  `contactno` varchar(11) DEFAULT NULL,
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `email`, `password`, `contactno`, `posting_date`) VALUES
(9, 'Anuj', 'Kumar', 'demouser@gmail.com', 'Test@123', '21222222', '2020-04-15 18:30:00'),
(11, 'Anuj', 'Kumar', 'phpgurukulofficial@gmail.com', 'Test@123', '1234567890', '2020-04-15 18:30:00'),
(13, '123', '132', '123', '123', '123', '2025-01-07 18:57:39'),
(14, '1234', '1234', 'amrroziek@gmail.com', '1234', '1234', '2025-01-07 22:18:28'),
(15, '12345', '12345', 'weela.tk@gmail.com', '12345', '12345', '2025-01-07 22:19:11'),
(16, 'amr', 'roziek', 'a0529936233@gmail.com', '123', '123', '2025-01-08 11:01:46'),
(17, 'amr', 'roziek', '321', '321', '123', '2025-01-08 11:04:50'),
(18, '4321', '4321', '4321', '4321', '4321', '2025-01-08 11:05:24'),
(19, '135', '135', '135', '135', '135', '2025-01-08 11:07:34'),
(20, '654', '654', '654', '654', '654', '2025-01-08 11:29:08'),
(21, '987', '987', '987', '987', '987', '2025-01-08 11:31:36'),
(22, '963', '963', '963', '963', '963', '2025-01-08 11:33:03'),
(23, '852', '852', '852', '852', '852', '2025-01-08 14:33:37'),
(24, '258', '258', '258', '258', '258', '2025-01-08 15:09:27'),
(25, '123', '123', '1234567', '123', '123', '2025-01-08 17:36:43'),
(26, 'muneer', 'rayan', 'bydass@yahoo.com.au', '123', '0501170290', '2025-01-08 17:37:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
