-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 15, 2025 at 09:18 AM
-- Server version: 11.4.8-MariaDB-cll-lve-log
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `regsys_poster`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `coauth1name` varchar(255) DEFAULT NULL,
  `coauth1nationality` varchar(255) DEFAULT NULL,
  `coauth2name` varchar(255) DEFAULT NULL,
  `coauth2nationality` varchar(255) DEFAULT NULL,
  `coauth3name` varchar(255) DEFAULT NULL,
  `coauth3nationality` varchar(255) DEFAULT NULL,
  `coauth4name` varchar(255) DEFAULT NULL,
  `coauth4nationality` varchar(255) DEFAULT NULL,
  `coauth5name` varchar(255) DEFAULT NULL,
  `coauth5nationality` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profession` varchar(50) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `password` varchar(300) DEFAULT NULL,
  `contactno` varchar(20) DEFAULT NULL,
  `userip` varchar(255) NOT NULL,
  `companyref` varchar(100) DEFAULT NULL,
  `paypalref` varchar(100) DEFAULT NULL,
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `coauth1email` varchar(255) DEFAULT NULL,
  `coauth2email` varchar(255) DEFAULT NULL,
  `coauth3email` varchar(255) DEFAULT NULL,
  `coauth4email` varchar(255) DEFAULT NULL,
  `coauth5email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `nationality`, `coauth1name`, `coauth1nationality`, `coauth2name`, `coauth2nationality`, `coauth3name`, `coauth3nationality`, `coauth4name`, `coauth4nationality`, `coauth5name`, `coauth5nationality`, `email`, `profession`, `organization`, `category`, `password`, `contactno`, `userip`, `companyref`, `paypalref`, `posting_date`, `coauth1email`, `coauth2email`, `coauth3email`, `coauth4email`, `coauth5email`) VALUES
(2104070256, 'Mohammed Khalid Al-Juboori', 'Iraq', 'Siti Maisharah Sheikh Ghadzi', 'Malaysia', '', '', '', '', '', '', '', '', 'moekalj@gmail.com', 'Pharmacist', 'Universiti Sains Malaysia', 'Poster Competetion', 'wimsiz-wYdxos-9huhki', '', '202.170.60.228', 'Universiti Sains Malaysia', '', '2025-11-09 16:00:58', NULL, NULL, NULL, NULL, NULL),
(2104070257, 'Dana Maze Zaher', 'Palestinian Territory, Occupied', 'Fatema Hersi', 'Somalia', 'Mohammed M M Ghaleb', 'Jordan', 'Hany A. Omar', 'Egypt', '', '', '', '', 'dzaher@sharjah.ac.ae', 'Pharmacist', 'University of Sharjah', 'Poster Competetion', 'Mazon#1234', '', '194.170.95.210', 'University of Sharjah', '', '2025-11-12 07:11:37', NULL, NULL, NULL, NULL, NULL),
(2104070258, 'Rama M Z Abdalmenem ', 'Palestinian Territory, Occupied', 'Ahmed Abdalla Gaili', 'Sudan', 'Samer Alalalmeh', 'Jordan', '', '', '', '', '', '', '202110538@ajmanuni.ac.ae', 'Pharmacist', 'Ajman University ', 'Poster Competetion', 't2WJp86Z@', '', '109.177.75.74', 'Ajman University ', '', '2025-11-21 17:27:47', NULL, NULL, NULL, NULL, NULL),
(2104070259, 'Angela Mary Alexander', 'India', 'Asma Nalakath Vattaparambil', 'India', 'Fathimath Najiya Mohammed Jamal Bunder', 'India', 'Wahbi Noureddin Ghaouji', 'Syrian Arab Republic', 'Ahmed Gaili', 'Sudan', 'Amira Elassar', 'Egypt', '202310031@ajmanuni.ac.ae', 'Pharmacist', 'Ajman University', 'Poster Competetion', '3kKwwNB8', '', '109.177.84.225', 'Ajman University ', '', '2025-11-24 09:48:53', NULL, NULL, NULL, NULL, NULL),
(2104070260, 'Shahed Mhd Samer Alhabbal', 'Syrian Arab Republic', 'Ahmed Abdalla Mhd Gaili', 'Sudan', 'Elshemaa Mohamed Saifelnasr', 'Sudan', 'Ghaydaa Ibrahim Fawaz', 'Syrian Arab Republic', 'Wahbi Noureddin Ghaouji', 'Syrian Arab Republic', 'Amira Ashraf Ahmed Elasar', 'Egypt', 'Shoshoalhabbal12@gmail.com', 'Pharmacist', 'Ajman University ', 'Poster Competetion', 'Myq7BB5t', '', '109.177.25.243', 'Ajman University ', '', '2025-11-27 14:56:17', NULL, NULL, NULL, NULL, NULL),
(2104070261, 'Ilham Moulhem', 'Canada', 'Nagham Elsayed', 'Egypt', '', '', '', '', '', '', '', '', '202410742@ajmanuni.ac.ae', 'Other', 'Ajman University', 'Poster Competetion', 'Mrsholmes@1999', '', '91.73.20.34', 'Ajman University', '', '2025-11-29 22:17:28', NULL, NULL, NULL, NULL, NULL),
(2104070262, 'kiarash zohori pour', 'Iran, Islamic Republic of', 'marjan fallah', 'Iran, Islamic Republic of', 'shirin hekmati rad', 'Iran, Islamic Republic of', '', '', '', '', 'kiarash zohori pour', '', 'kiarashzohori@yahoo.com', 'Pharmacist', 'Isfahan medical university ', 'Poster Competetion', '@Diazpam10', '', '147.93.4.103', 'Isfahan medical university', '', '2025-12-02 15:58:30', NULL, NULL, NULL, NULL, NULL),
(2104070263, 'Basma Ahmed Elawady', 'Egypt', '', '', '', '', '', '', '', '', '', '', 'basma.elawady@kasralainy.edu.eg', 'Physician', 'Cairo University', 'Poster Competetion', 'RST@@2025', '', '41.33.236.109', 'Faculty of Medicine, Cairo University', '', '2025-12-03 08:22:52', NULL, NULL, NULL, NULL, NULL),
(2104070264, 'george zakaria anwar nagiub', 'Egypt', '', '', '', '', '', '', '', '', '', '', 'kingjoojoo1@gmail.com', 'Pharmacist', 'Ajman University', 'Poster Competetion', 'Joojoo_2005', '', '109.177.163.5', 'Ajman university', '', '2025-12-03 10:32:41', NULL, NULL, NULL, NULL, NULL),
(2104070265, 'Heba syaj', 'Jordan', 'Alaa Hammad ', 'Jordan', 'Heba syaj ', 'Jordan', '', '', '', '', '', '', 'hebasyaj1997@gmail.com', 'Pharmacist', 'Al Zaytooheh university ', 'Poster Competetion', 'Hh202017010#', '', '176.29.157.78', 'Al zaytooneh university ', '', '2025-12-09 06:58:59', NULL, NULL, NULL, NULL, NULL),
(2104070266, 'Ruba Amjad Zenati', 'Palestinian Territory, Occupied', 'Mohammad Harb Semreen ', 'Jordan', 'Hasan  Al Niss', 'Jordan', 'Ahmad Abuhelwa', 'Austria', ' Shereen  Aleidi', 'Jordan', 'Yasser Bustanji', 'Jordan', 'ruba.zenati@sharjah.ac.ae', 'Pharmacist', 'University of Sharjah', 'Poster Competetion', 'Ruba1994.', '', '31.219.230.152', 'University of Sharjah', '', '2025-12-11 18:41:29', NULL, NULL, NULL, NULL, NULL),
(2104070267, 'Mohammad Harb Semreen', 'Jordan', 'Mariam Karim Mohamed Fawy Farrag', 'Egypt', 'Fatema Said Abou Hussein', 'Palestinian Territory, Occupied', 'MallakÂ AhmadÂ Abusaqer', 'Jordan', 'Ruba Amjad Zenati', 'Palestinian Territory, Occupied', 'Reem A. Qannita', 'Palestinian Territory, Occupied', 'msemreen@sharjah.ac.ae', 'Pharmacist', 'University of Sharjah ', 'Poster Competetion', 'Semreen1970.', '', '31.219.230.152', 'University of Sharjah ', '', '2025-12-11 19:00:54', NULL, NULL, NULL, NULL, NULL),
(2104070269, 'heba syaj', 'Jordan', 'Alaa hammad ', 'Jordan', 'osama abu sara ', 'Jordan', '', '', '', '', '', '', 'drtariqalnaji@gmail.com', 'Pharmacist', 'Al zaytooneh private university ', 'Poster Competetion', 'Hh202017010#', '', '109.107.253.175', 'Al zaytooneh private university ', '', '2025-12-13 18:18:31', NULL, NULL, NULL, NULL, NULL),
(2104070274, '9999', 'Jordan', '9999', 'Jordan', '99999', 'Jordan', '', '', '', '', '', '', '9999999', 'Pharmacist', '99999', 'Poster Competetion', '99999', '', '94.207.199.11', '99999', '', '2025-12-14 08:56:45', NULL, NULL, NULL, NULL, NULL),
(2104070275, '99999', 'Albania', '99999', 'Aland Islands', '99999', 'American Samoa', '99999', 'American Samoa', '99999', 'Andorra', '99999', 'American Samoa', '9999', 'Pharmacist', 'aaaa', 'Poster Competetion', 'aaaa', '', '94.207.199.11', '', '', '2025-12-14 08:59:20', NULL, NULL, NULL, NULL, NULL),
(2104070276, 'Shery Jacob', 'India', 'Fathima Sheik Kather', 'India', 'Shakta Mani Satyam', 'India', 'Sai H. S. Boddu', 'India', 'Firas Assaf', 'United Arab Emirates', 'Anroop B Nair', 'India', 'dr.sheryjacob@gmu.ac.ae', 'Other', 'Gulf Medical University', 'Poster Competetion', '7777', '', '83.110.156.146', 'Gulf Medical University', '', '2025-12-15 05:53:32', NULL, NULL, NULL, NULL, NULL),
(2104070277, 'Mohammed Haseeb', 'India', 'Dr shery Jacob ', 'India', 'Hassan Javed', 'Dominica', 'Mohammed wasim', 'Pakistan', 'Shabana akbar', 'Pakistan', '', '', '2022ph30@mygmu.ac.ae', 'Other', 'Gulf medical university ', 'Poster Competetion', 'Haseebvavi@123', '', '5.107.166.217', 'Gulf medical university ', '', '2025-12-15 08:37:17', NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2104070278;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
