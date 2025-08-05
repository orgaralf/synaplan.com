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
-- Table structure for table `BMESSAGEMETA`
--

DROP TABLE IF EXISTS `BMESSAGEMETA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BMESSAGEMETA` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BMESSID` bigint(20) NOT NULL,
  `BTOKEN` varchar(64) NOT NULL,
  `BVALUE` varchar(128) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BMESSID` (`BMESSID`),
  KEY `BTOKEN` (`BTOKEN`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BMESSAGEMETA`
--

LOCK TABLES `BMESSAGEMETA` WRITE;
/*!40000 ALTER TABLE `BMESSAGEMETA` DISABLE KEYS */;
INSERT INTO `BMESSAGEMETA` VALUES
(1,1,'SORTBYTES','36'),
(2,1,'AISERVICE','AIGroq'),
(3,1,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(4,1,'AIMODELID','49'),
(5,3,'SORTBYTES','36'),
(6,3,'AISERVICE','AIGroq'),
(7,3,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(8,3,'AIMODELID','49'),
(9,5,'SORTBYTES','36'),
(10,5,'AISERVICE','AIGroq'),
(11,5,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(12,5,'AIMODELID','49'),
(13,7,'SORTBYTES','36'),
(14,7,'AISERVICE','AIGroq'),
(15,7,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(16,7,'AIMODELID','49'),
(17,9,'SORTBYTES','36'),
(18,9,'AISERVICE','AIGroq'),
(19,9,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(20,9,'AIMODELID','49'),
(21,11,'SORTBYTES','36'),
(22,11,'AISERVICE','AIGroq'),
(23,11,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(24,11,'AIMODELID','49'),
(25,13,'SORTBYTES','36'),
(26,13,'AISERVICE','AIGroq'),
(27,13,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(28,13,'AIMODELID','49'),
(29,13,'AISERVICE','AIOpenAI'),
(30,13,'AIMODEL','gpt-image-1'),
(31,13,'AIMODELID','29'),
(32,13,'AISERVICE','AIOpenAI'),
(33,13,'AIMODEL','gpt-image-1'),
(34,13,'AIMODELID','29'),
(35,15,'SORTBYTES','36'),
(36,15,'AISERVICE','AIGroq'),
(37,15,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(38,15,'AIMODELID','49'),
(39,15,'AISERVICE','AIOpenAI'),
(40,15,'AIMODEL','gpt-image-1'),
(41,15,'AIMODELID','29'),
(42,15,'AISERVICE','AIOpenAI'),
(43,15,'AIMODEL','gpt-image-1'),
(44,15,'AIMODELID','29'),
(45,18,'SORTBYTES','45'),
(46,18,'AISERVICE','AIGroq'),
(47,18,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(48,18,'AIMODELID','49'),
(49,19,'SORTBYTES','45'),
(50,19,'AISERVICE','AIGroq'),
(51,19,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(52,19,'AIMODELID','49'),
(53,20,'SORTBYTES','45'),
(54,20,'AISERVICE','AIGroq'),
(55,20,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(56,20,'AIMODELID','49'),
(57,21,'CHATBYTES','5264'),
(58,21,'SORTBYTES','5264'),
(59,21,'AISYSPROMPT','general'),
(60,22,'SORTBYTES','19'),
(61,22,'AISERVICE','AIGroq'),
(62,22,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(63,22,'AIMODELID','49'),
(64,23,'CHATBYTES','5561'),
(65,23,'SORTBYTES','5561'),
(66,23,'AISYSPROMPT','general'),
(67,23,'AISERVICE','AIGroq'),
(68,23,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(69,24,'SORTBYTES','30'),
(70,24,'AISERVICE','AIGroq'),
(71,24,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(72,24,'AIMODELID','49'),
(73,25,'CHATBYTES','8631'),
(74,25,'SORTBYTES','8631'),
(75,25,'AISYSPROMPT','general'),
(76,25,'AISERVICE','AIGroq'),
(77,25,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(78,26,'SORTBYTES','40'),
(79,26,'AISERVICE','AIGroq'),
(80,26,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(81,26,'AIMODELID','49'),
(82,26,'AISERVICE','AIOpenAI'),
(83,26,'AIMODEL','o3'),
(84,27,'CHATBYTES','8482'),
(85,27,'SORTBYTES','8482'),
(86,27,'AISYSPROMPT','general'),
(87,27,'AISERVICE','AIOpenAI'),
(88,27,'AIMODEL','o3'),
(89,28,'AISERVICE','AIOpenAI'),
(90,28,'AIMODEL','gpt-image-1'),
(91,28,'AIMODELID','29'),
(92,29,'AISERVICE','AIOpenAI'),
(93,29,'AIMODEL','gpt-image-1'),
(94,29,'AIMODELID','29'),
(95,29,'AITOOL','/pic'),
(96,30,'FILEBYTES','1561392'),
(97,30,'CHATBYTES','76'),
(98,30,'SORTBYTES','76'),
(99,30,'AISYSPROMPT',''),
(100,30,'AISERVICE','AIOpenAI'),
(101,30,'AIMODEL','gpt-image-1'),
(102,31,'SORTBYTES','54'),
(103,31,'AISERVICE','AIGroq'),
(104,31,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(105,31,'AIMODELID','49'),
(106,0,'AISERVICE','AIOpenAI'),
(107,31,'AISERVICE','AIOpenAI'),
(108,31,'AIMODEL','gpt-image-1'),
(109,31,'AIMODELID','29'),
(110,31,'AIMODEL','image'),
(111,31,'AISERVICE','AIOpenAI'),
(112,31,'AIMODEL','gpt-image-1'),
(113,31,'AIMODELID','29'),
(114,31,'AITOOL','/pic'),
(115,32,'FILEBYTES','1531293'),
(116,32,'CHATBYTES','348'),
(117,32,'SORTBYTES','348'),
(118,32,'AISYSPROMPT','mediamaker'),
(119,32,'AISERVICE','AIOpenAI'),
(120,32,'AIMODEL','gpt-image-1'),
(121,33,'PROMPTID','general'),
(122,33,'AISERVICE','AIOpenAI'),
(123,33,'AIMODEL','o3'),
(124,34,'CHATBYTES','77'),
(125,34,'SORTBYTES','77'),
(126,34,'AISYSPROMPT','general'),
(127,34,'AISERVICE','AIOpenAI'),
(128,34,'AIMODEL','o3'),
(129,35,'PROMPTID','general'),
(130,36,'PROMPTID','general'),
(131,36,'AISERVICE','AIOpenAI'),
(132,36,'AIMODEL','o3'),
(133,37,'CHATBYTES','43'),
(134,37,'SORTBYTES','43'),
(135,37,'AISYSPROMPT','general'),
(136,37,'AISERVICE','AIOpenAI'),
(137,37,'AIMODEL','o3'),
(138,38,'PROMPTID','general'),
(139,38,'AISERVICE','AIOpenAI'),
(140,38,'AIMODEL','o3'),
(141,39,'CHATBYTES','1427'),
(142,39,'SORTBYTES','1427'),
(143,39,'AISYSPROMPT','general'),
(144,39,'AISERVICE','AIOpenAI'),
(145,39,'AIMODEL','o3'),
(146,40,'PROMPTID','general'),
(147,41,'PROMPTID','general'),
(148,41,'AISERVICE','AIOpenAI'),
(149,41,'AIMODEL','o3'),
(150,42,'CHATBYTES','161'),
(151,42,'SORTBYTES','161'),
(152,42,'AISYSPROMPT','general'),
(153,42,'AISERVICE','AIOpenAI'),
(154,42,'AIMODEL','o3'),
(155,43,'SORTBYTES','40'),
(156,43,'AISERVICE','AIGroq'),
(157,43,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(158,43,'AIMODELID','49'),
(159,43,'AISERVICE','AIAnthropic'),
(160,43,'AIMODEL','o3'),
(161,44,'CHATBYTES','615'),
(162,44,'SORTBYTES','615'),
(163,44,'AISYSPROMPT','general'),
(164,44,'AISERVICE','AIAnthropic'),
(165,44,'AIMODEL','o3'),
(166,45,'SORTBYTES','55'),
(167,45,'AISERVICE','AIGroq'),
(168,45,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(169,45,'AIMODELID','49'),
(170,45,'AISERVICE','AIAnthropic'),
(171,45,'AIMODEL','o3'),
(172,46,'CHATBYTES','829'),
(173,46,'SORTBYTES','829'),
(174,46,'AISYSPROMPT','general'),
(175,46,'AISERVICE','AIAnthropic'),
(176,46,'AIMODEL','o3'),
(177,47,'SORTBYTES','36'),
(178,47,'AISERVICE','AIGroq'),
(179,47,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(180,47,'AIMODELID','49'),
(181,47,'AISERVICE','AIAnthropic'),
(182,47,'AIMODEL','o3'),
(183,48,'CHATBYTES','952'),
(184,48,'SORTBYTES','952'),
(185,48,'AISYSPROMPT','general'),
(186,48,'AISERVICE','AIAnthropic'),
(187,48,'AIMODEL','o3'),
(188,49,'SORTBYTES','81'),
(189,49,'AISERVICE','AIGroq'),
(190,49,'AIMODEL','meta-llama/llama-4-maverick-17b-128e-instruct'),
(191,49,'AIMODELID','49'),
(192,49,'AISERVICE','AIAnthropic'),
(193,49,'AIMODEL','claude-opus-4-20250514'),
(194,50,'CHATBYTES','849'),
(195,50,'SORTBYTES','849'),
(196,50,'AISYSPROMPT','general'),
(197,50,'AISERVICE','AIAnthropic'),
(198,50,'AIMODEL','claude-opus-4-20250514');
/*!40000 ALTER TABLE `BMESSAGEMETA` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-08-05 11:22:35
