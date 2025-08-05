/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: synaplan
-- ------------------------------------------------------
-- Server version	11.7.2-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `BUSER`
--

DROP TABLE IF EXISTS `BUSER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BUSER` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BCREATED` varchar(20) NOT NULL DEFAULT '',
  `BINTYPE` varchar(4) NOT NULL DEFAULT 'WA',
  `BMAIL` varchar(128) NOT NULL DEFAULT '',
  `BPW` varchar(64) NOT NULL DEFAULT '',
  `BPROVIDERID` varchar(32) NOT NULL DEFAULT '',
  `BUSERLEVEL` varchar(32) NOT NULL DEFAULT 'NEW',
  `BUSERDETAILS` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BUSERDETAILS`)),
  PRIMARY KEY (`BID`),
  KEY `BINTYPE` (`BINTYPE`),
  KEY `BPROVIDERID` (`BPROVIDERID`),
  KEY `BUSERLEVEL` (`BUSERLEVEL`),
  KEY `BMAIL` (`BMAIL`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BUSER`
--

LOCK TABLES `BUSER` WRITE;
/*!40000 ALTER TABLE `BUSER` DISABLE KEYS */;
INSERT INTO `BUSER` VALUES
(2,'20250123171248','WA','rs@metadist.de','9c2d9bae47d9cc20d000570d770cf366','491754070111','NEW','{\n    \"firstName\": \"Ralfs Kram\",\n    \"lastName\": \"Schwöbel\",\n    \"phone\": \"+491754070111\",\n    \"companyName\": \"metadist data management GmbH\",\n    \"vatId\": \"DE301805620\",\n    \"street\": \"Huschberger Str. 12, c\\/o SeedVC\",\n    \"zipCode\": \"40212\",\n    \"city\": \"Düsseldorf\",\n    \"country\": \"DE\",\n    \"language\": \"en\",\n    \"timezone\": \"Europe\\/Berlin\",\n    \"invoiceEmail\": \"rs@metadist.de\"\n}'),
(78,'20250218164328','MAIL','','','12522461215804672598478fcba98bf0','NEW','{\"MAIL\":\"hodyroff@gmail.com\",\"PHONE\":\"\",\"CREATED\":\"202502181643\"}'),
(94,'20250222131001','MAIL','','','e7f94c387b57f27fd6ce39fe6c762b7c','NEW','{\"MAIL\":\"rs@orgamail.net\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"202502221310\"}'),
(98,'20250223204553','WA','','','491726950567','NEW','{\"MAIL\":\"\",\"MAILCHECKED\":\"bdee53\",\"PHONE\":\"491726950567\",\"CREATED\":\"202502232045\"}'),
(102,'20250223211204','WA','','','491735135755','NEW','{\"MAIL\":\"\",\"MAILCHECKED\":\"e87d04\",\"PHONE\":\"491735135755\",\"CREATED\":\"202502232112\"}'),
(106,'20250225173329','MAIL','','','8733e5e627039f4bf32d78c0b115ecbd','NEW','{\"MAIL\":\"orgazone@icloud.com\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"202502251733\"}'),
(110,'20250228185926','WA','','','4917620132348','NEW','{\"MAIL\":\"\",\"MAILCHECKED\":\"524926\",\"PHONE\":\"4917620132348\",\"CREATED\":\"202502281859\"}'),
(114,'20250304083629','MAIL','stefan.voelker@voelkerdigital.de','35fc59ed847edd6d8ec49231de85bea9','688e11fa653ab6640f813d32e362b3af','NEW','{\"MAIL\":\"stefan.voelker@voelkerdigital.de\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"202503040836\"}'),
(118,'20250313174329','MAIL','','','84b0406f504d7ec845c1a43f5644292e','NEW','{\"MAIL\":\"puzzler@runbox.com\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"202503131743\"}'),
(122,'20250316184029','MAIL','','','bea56088adb05b66c6c9d3d1a7a8236e','NEW','{\"MAIL\":\"sebastian.wenzel@gmail.com\",\"MAILCHECKED\":\"257a29\",\"PHONE\":\"\",\"CREATED\":\"202503161840\"}'),
(129,'202506011200','MAIL','naverianiana7@gmail.com','471a35e8cd7dc75c56644d905d712e17','manual100','NEW','{\"MAIL\":\"naverianiana7@gmail.com\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(133,'202506011200','MAIL','poojanethraa@gmail.com','993a264b1ab269b848161c61599e019b','manual101','NEW','{\"MAIL\":\"poojanethraa@gmail.com\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(134,'202506011200','MAIL','peter.braun@jacobs.com.mt','211b69a696bc30d7dc9add15a7042cec','manual102','NEW','{\"MAIL\":\"peter.braun@jacobs.com.mt\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(135,'202506011200','MAIL','oliver.braun@jacobs.com.mt','41aa75997e55f61d6b6180240f857d80','manual102','NEW','{\n    \"firstName\": \"Oliver\",\n    \"lastName\": \"Braun\",\n    \"phone\": \"+35699000002\",\n    \"companyName\": \"\",\n    \"vatId\": \"\",\n    \"street\": \"Queensgate, Forrest Street, Flat 20\",\n    \"zipCode\": \"2030\",\n    \"city\": \"St Julians \",\n    \"country\": \"OTHER\",\n    \"language\": \"de\",\n    \"timezone\": \"Europe\\/Berlin\",\n    \"invoiceEmail\": \"oliver@braun-privat.com\"\n}'),
(136,'202506011200','MAIL','hodyroff@gmail.com','d17206df55be46078720a26cc3bd7dd1','manual102','NEW','{\"MAIL\":\"hodyroff@gmail.com\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(137,'202506011200','MAIL','dev@dominik-schmidt.de','5c5180a22b35e9806897d9f0b7bd252c','manual102','NEW','{\"MAIL\":\"dev@dominik-schmidt.de\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(138,'202506011200','MAIL','eby@alien.de','8830413bf7d173e040faf631d80dbfe3','manual102','NEW','{\"MAIL\":\"eby@alien.de\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(142,'202506011200','MAIL','dr.nemat@t-online.de','1aebd8cee1b8aff0c91f7dd4384b34c2','manual102','NEW','{\"MAIL\":\"dr.nemat@t-online.de\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}'),
(146,'202506011200','MAIL','jester@arcor.de','9734d1ffe09a6324eadeff1abe323416','manual102','NEW','{\"MAIL\":\"jester@arcor.de\",\"MAILCHECKED\":1,\"PHONE\":\"\",\"CREATED\":\"20250601\"}');
/*!40000 ALTER TABLE `BUSER` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-08-05 11:22:36
