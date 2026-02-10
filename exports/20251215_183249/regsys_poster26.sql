-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: regsys_poster26
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','21232f297a57a5a743894a0e4a801fc3');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `coauth1name` varchar(255) DEFAULT NULL,
  `coauth1nationality` varchar(255) DEFAULT NULL,
  `coauth1email` varchar(190) DEFAULT NULL,
  `coauth2name` varchar(255) DEFAULT NULL,
  `coauth2nationality` varchar(255) DEFAULT NULL,
  `coauth2email` varchar(190) DEFAULT NULL,
  `coauth3name` varchar(255) DEFAULT NULL,
  `coauth3nationality` varchar(255) DEFAULT NULL,
  `coauth3email` varchar(190) DEFAULT NULL,
  `coauth4name` varchar(255) DEFAULT NULL,
  `coauth4nationality` varchar(255) DEFAULT NULL,
  `coauth4email` varchar(190) DEFAULT NULL,
  `coauth5name` varchar(255) DEFAULT NULL,
  `coauth5nationality` varchar(255) DEFAULT NULL,
  `coauth5email` varchar(190) DEFAULT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2104070278 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2104070256,'Mohammed Khalid Al-Juboori','Iraq','Siti Maisharah Sheikh Ghadzi','Malaysia',NULL,'','',NULL,'','',NULL,'','',NULL,'','',NULL,'moekalj@gmail.com','Pharmacist','Universiti Sains Malaysia','Poster Competetion','wimsiz-wYdxos-9huhki','','202.170.60.228','Universiti Sains Malaysia','','2025-11-09 12:00:58'),(2104070257,'Dana Maze Zaher','Palestinian Territory, Occupied','Fatema Hersi','Somalia',NULL,'Mohammed M M Ghaleb','Jordan',NULL,'Hany A. Omar','Egypt',NULL,'','',NULL,'','',NULL,'dzaher@sharjah.ac.ae','Pharmacist','University of Sharjah','Poster Competetion','Mazon#1234','','194.170.95.210','University of Sharjah','','2025-11-12 03:11:37'),(2104070258,'Rama M Z Abdalmenem ','Palestinian Territory, Occupied','Ahmed Abdalla Gaili','Sudan',NULL,'Samer Alalalmeh','Jordan',NULL,'','',NULL,'','',NULL,'','',NULL,'202110538@ajmanuni.ac.ae','Pharmacist','Ajman University ','Poster Competetion','t2WJp86Z@','','109.177.75.74','Ajman University ','','2025-11-21 13:27:47'),(2104070259,'Angela Mary Alexander','India','Asma Nalakath Vattaparambil','India',NULL,'Fathimath Najiya Mohammed Jamal Bunder','India',NULL,'Wahbi Noureddin Ghaouji','Syrian Arab Republic',NULL,'Ahmed Gaili','Sudan',NULL,'Amira Elassar','Egypt',NULL,'202310031@ajmanuni.ac.ae','Pharmacist','Ajman University','Poster Competetion','3kKwwNB8','','109.177.84.225','Ajman University ','','2025-11-24 05:48:53'),(2104070260,'Shahed Mhd Samer Alhabbal','Syrian Arab Republic','Ahmed Abdalla Mhd Gaili','Sudan',NULL,'Elshemaa Mohamed Saifelnasr','Sudan',NULL,'Ghaydaa Ibrahim Fawaz','Syrian Arab Republic',NULL,'Wahbi Noureddin Ghaouji','Syrian Arab Republic',NULL,'Amira Ashraf Ahmed Elasar','Egypt',NULL,'Shoshoalhabbal12@gmail.com','Pharmacist','Ajman University ','Poster Competetion','Myq7BB5t','','109.177.25.243','Ajman University ','','2025-11-27 10:56:17'),(2104070261,'Ilham Moulhem','Canada','Nagham Elsayed','Egypt',NULL,'','',NULL,'','',NULL,'','',NULL,'','',NULL,'202410742@ajmanuni.ac.ae','Other','Ajman University','Poster Competetion','Mrsholmes@1999','','91.73.20.34','Ajman University','','2025-11-29 18:17:28'),(2104070262,'kiarash zohori pour','Iran, Islamic Republic of','marjan fallah','Iran, Islamic Republic of',NULL,'shirin hekmati rad','Iran, Islamic Republic of',NULL,'','',NULL,'','',NULL,'kiarash zohori pour','',NULL,'kiarashzohori@yahoo.com','Pharmacist','Isfahan medical university ','Poster Competetion','@Diazpam10','','147.93.4.103','Isfahan medical university','','2025-12-02 11:58:30'),(2104070263,'Basma Ahmed Elawady','Egypt','','',NULL,'','',NULL,'','',NULL,'','',NULL,'','',NULL,'basma.elawady@kasralainy.edu.eg','Physician','Cairo University','Poster Competetion','RST@@2025','','41.33.236.109','Faculty of Medicine, Cairo University','','2025-12-03 04:22:52'),(2104070264,'george zakaria anwar nagiub','Egypt','','',NULL,'','',NULL,'','',NULL,'','',NULL,'','',NULL,'kingjoojoo1@gmail.com','Pharmacist','Ajman University','Poster Competetion','Joojoo_2005','','109.177.163.5','Ajman university','','2025-12-03 06:32:41'),(2104070265,'Heba syaj','Jordan','Alaa Hammad ','Jordan',NULL,'Heba syaj ','Jordan',NULL,'','',NULL,'','',NULL,'','',NULL,'hebasyaj1997@gmail.com','Pharmacist','Al Zaytooheh university ','Poster Competetion','Hh202017010#','','176.29.157.78','Al zaytooneh university ','','2025-12-09 02:58:59'),(2104070266,'Ruba Amjad Zenati','Palestinian Territory, Occupied','Mohammad Harb Semreen ','Jordan',NULL,'Hasan  Al Niss','Jordan',NULL,'Ahmad Abuhelwa','Austria',NULL,' Shereen  Aleidi','Jordan',NULL,'Yasser Bustanji','Jordan',NULL,'ruba.zenati@sharjah.ac.ae','Pharmacist','University of Sharjah','Poster Competetion','Ruba1994.','','31.219.230.152','University of Sharjah','','2025-12-11 14:41:29'),(2104070267,'Mohammad Harb Semreen','Jordan','Mariam Karim Mohamed Fawy Farrag','Egypt',NULL,'Fatema Said Abou Hussein','Palestinian Territory, Occupied',NULL,'Mallak????Ahmad????Abusaqer','Jordan',NULL,'Ruba Amjad Zenati','Palestinian Territory, Occupied',NULL,'Reem A. Qannita','Palestinian Territory, Occupied',NULL,'msemreen@sharjah.ac.ae','Pharmacist','University of Sharjah ','Poster Competetion','Semreen1970.','','31.219.230.152','University of Sharjah ','','2025-12-11 15:00:54'),(2104070269,'heba syaj','Jordan','Alaa hammad ','Jordan',NULL,'osama abu sara ','Jordan',NULL,'','',NULL,'','',NULL,'','',NULL,'drtariqalnaji@gmail.com','Pharmacist','Al zaytooneh private university ','Poster Competetion','Hh202017010#','','109.107.253.175','Al zaytooneh private university ','','2025-12-13 14:18:31'),(2104070274,'9999','Jordan','9999','Jordan',NULL,'99999','Jordan',NULL,'','',NULL,'','',NULL,'','',NULL,'9999999','Pharmacist','99999','Poster Competetion','99999','','94.207.199.11','99999','','2025-12-14 04:56:45'),(2104070275,'99999','Albania','99999','Aland Islands',NULL,'99999','American Samoa',NULL,'99999','American Samoa',NULL,'99999','Andorra',NULL,'99999','American Samoa',NULL,'9999','Pharmacist','aaaa','Poster Competetion','aaaa','','94.207.199.11','','','2025-12-14 04:59:20'),(2104070276,'Shery Jacob','India','Fathima Sheik Kather','India',NULL,'Shakta Mani Satyam','India',NULL,'Sai H. S. Boddu','India',NULL,'Firas Assaf','United Arab Emirates',NULL,'Anroop B Nair','India',NULL,'dr.sheryjacob@gmu.ac.ae','Other','Gulf Medical University','Poster Competetion','7777','','83.110.156.146','Gulf Medical University','','2025-12-15 01:53:32'),(2104070277,'Mohammed Haseeb','India','Dr shery Jacob ','India',NULL,'Hassan Javed','Dominica',NULL,'Mohammed wasim','Pakistan',NULL,'Shabana akbar','Pakistan',NULL,'','',NULL,'2022ph30@mygmu.ac.ae','Other','Gulf medical university ','Poster Competetion','Haseebvavi@123','','5.107.166.217','Gulf medical university ','','2025-12-15 04:37:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'regsys_poster26'
--

--
-- Dumping routines for database 'regsys_poster26'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-15 18:32:50
