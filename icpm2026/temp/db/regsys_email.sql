-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 09, 2026 at 11:58 AM
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
-- Database: `regsys_email`
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
(1, 'admin', '25d55ad283aa400af464c76d713c07ad'),
(2, 'info@icpm.ae', 'e950dcfd96058100b2621978f62abbc8');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profession` varchar(50) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `password` varchar(300) DEFAULT NULL,
  `contactno` varchar(50) DEFAULT NULL,
  `userip` varchar(50) DEFAULT NULL,
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `nationality`, `email`, `profession`, `organization`, `category`, `password`, `contactno`, `userip`, `posting_date`) VALUES
(202106573, 'amr', 'roziek', '', 'amrroziek1@gmail.com', '', '', '', '', '', NULL, '2025-01-14 09:04:44'),
(202106574, 'amr', 'roziek', '', 'amrroziek@yahoo.com', '', '', '', '', '', NULL, '2025-01-14 09:14:43'),
(202106575, '111', '111', '', 'amrroziek@hotmail.com', '', '', '', '', '', NULL, '2025-01-14 09:16:29'),
(202106576, 'Yazan ', 'Abdullah ', '', 'Yazzhb@hotmail.com', '', '', '', '', '', NULL, '2025-01-14 09:20:07'),
(202106577, 'Mahra', 'Alkindi', '', 'Eljounaidikaoutar@gmail.com', '', '', '', '', '', NULL, '2025-01-14 09:48:27');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202106578;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
