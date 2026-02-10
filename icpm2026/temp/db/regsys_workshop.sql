-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 09, 2026 at 12:03 PM
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
-- Database: `regsys_workshop`
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
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e'),
(2, 'info@icpm.ae', 'e950dcfd96058100b2621978f62abbc8');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profession` varchar(50) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `password` varchar(300) DEFAULT NULL,
  `contactno` varchar(255) DEFAULT NULL,
  `userip` varchar(255) NOT NULL,
  `companyref` varchar(50) DEFAULT NULL,
  `paypalref` varchar(50) DEFAULT NULL,
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `fullname`, `nationality`, `email`, `profession`, `organization`, `category`, `password`, `contactno`, `userip`, `companyref`, `paypalref`, `posting_date`) VALUES
(202101660, 'Mubark ', 'AlaAlabudahash', 'Alabudahash, Mubark Mohammed', 'Saudi Arabia', 'Dahash2013@gmail.com', 'Pharmacist', 'SFDA', 'Workshop', 'BeHappy2025', 'Â±966563014750', '78.95.81.55', '', '4029357733', '2025-01-14 20:14:37'),
(202101661, 'Nahed', 'El-Najjar', 'Nahed El-Najjar', 'Lebanon', 'ne09@aub.edu.lb', 'Pharmacist', 'American University of Beirut', 'Workshop', 'Ilovecortina2010$', '0096181816700', '193.188.130.59', '', '0C752913SW8243843', '2025-12-03 06:57:30'),
(202101662, 'ALI', 'ALSHAWWAF', 'ALI F. M. AlSHAWWAF', 'Palestinian Territory, Occupied', 'alialshawwaf2020@gmail.com', 'Pharmacist', 'Alhabib Pharmacy', 'Workshop', 'Aali1999-2015.', '0595615627', '213.6.13.237', '', '', '2026-01-08 14:46:35'),
(202101663, 'Mufarreh', 'Asmari', 'Mufarreh Mohammed Asmari ', 'Saudi Arabia', 'ph.mufarreh@gmail.com', 'Pharmacist', 'King Khalid University ', 'Workshop', '55775577Dr', '0530189891', '37.243.45.63', '', '', '2026-01-14 14:12:07');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202101664;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
