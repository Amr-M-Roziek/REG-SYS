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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1234','Afghanistan','2345','Barbados','555222@test.com','','','','','','','','','','','','','2345','Pharmacist','852852','Poster Competetion','$2y$10$TBEdfK99m9nkgnjBrxMvYObXI3BaPPUQK1.zH0RwwDEKHGP4.MPSS','123456789','127.0.0.1','','','2025-12-15 08:10:06'),(2,'1234','Afghanistan','2345','Barbados','555222@test.com','','','','','','','','','','','','','23455','Pharmacist','852852','Poster Competetion','$2y$10$HYX.d.gZBg4ZYulRNk34TuPyw45PewnOi9w9caECiQXv5RrLrkJE.','123456789','127.0.0.1','','','2025-12-15 08:11:15'),(3,'852','Barbados','iiii','Cuba','iop@iop.com','','','','','','','','','','','','','ppp@ppp.v','Pharmacist','iop','Poster Competetion','$2y$10$5Hv.sy2jZbdzHwK0wvqvjeKwWrJPfjTQKjPft/Invclw8MTxD2Gcu','85258','127.0.0.1','iop','','2025-12-15 08:21:46'),(4,'rfv','Faroe Islands','sdasd','Nauru','asd@gh.f','dsaf','Nepal','sfda@vm.y','','','','','','','','','','rfv@fff.k','Pharmacist','asdf','Poster Competetion','$2y$10$NnJmC2e.dLBuqDtWkegdFOapdEDveSvhWyv5fLAUiN7JmVW6LzUTi','asdf','127.0.0.1','asfd','','2025-12-15 08:26:38'),(5,'Local User 1','Afghanistan',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'','local+972156@example.com','Student','Test Org','Students','$2y$10$JPKkcXUKvBq1qvwlWMl2WOjiyfOp7R7Pvgs9T0jx4ofpMX6OLmLwy','5551','127.0.0.1','COMP1','PAY1','2025-12-15 08:28:36'),(6,'Local User 2','Afghanistan',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'','local+196463@example.com','Student','Test Org','Students','$2y$10$7ktM5RtLivxefpad7V.0AuTz4pMz2yAI2pR7H55sUiEilELO8Ux7q','5552','127.0.0.1','COMP2','PAY2','2025-12-15 08:28:42'),(7,'Local User 3','Afghanistan',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'',NULL,NULL,'','local+186807@example.com','Student','Test Org','Students','$2y$10$huyy9HXvjrkXwF223AorwOQAC3RRKnYTwQNXz2Zy7wQPix4KVvDUK','5553','127.0.0.1','COMP3','PAY3','2025-12-15 08:28:49'),(8,'rfv','Faroe Islands','sdasd','Nauru','asd@gh.f','dsaf','Nepal','sfda@vm.y','','','','','','','','','','rfsv@fff.k','Pharmacist','asdf','Poster Competetion','$2y$10$15A4ogCLDH2U21L3vzr69erod0vlomXKxlOMjSJL95oBzGUr2iGee','asdf','127.0.0.1','asfd','','2025-12-15 08:30:52');
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

-- Dump completed on 2025-12-15 18:21:08
