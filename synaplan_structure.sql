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
-- Table structure for table `BCAPABILITIES`
--

DROP TABLE IF EXISTS `BCAPABILITIES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BCAPABILITIES` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BKEY` varchar(64) NOT NULL,
  `BCOMMENT` varchar(128) NOT NULL,
  PRIMARY KEY (`BID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BCONFIG`
--

DROP TABLE IF EXISTS `BCONFIG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BCONFIG` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BGROUP` varchar(64) NOT NULL DEFAULT '',
  `BSETTING` varchar(96) NOT NULL DEFAULT '',
  `BVALUE` varchar(250) NOT NULL,
  KEY `BOWNERID` (`BOWNERID`),
  KEY `BSETTING` (`BSETTING`),
  KEY `BGROUP` (`BGROUP`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BLISTS`
--

DROP TABLE IF EXISTS `BLISTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BLISTS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BOWNERID` bigint(20) NOT NULL,
  `BLISTKEY` varchar(32) NOT NULL,
  `BLISTFORM` varchar(32) NOT NULL DEFAULT 'DEFAULT' COMMENT 'Can have different forms of JSON entries like\r\n\r\nDEFAULT\r\nSHOPPING\r\nRECIPE\r\nTODO\r\nREMINDERS\r\netc.',
  `BLNAME` varchar(96) NOT NULL,
  `BENTRY` text NOT NULL DEFAULT '',
  PRIMARY KEY (`BID`),
  KEY `BOWNERID` (`BOWNERID`),
  KEY `BLISTKEY` (`BLISTKEY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BMAILS`
--

DROP TABLE IF EXISTS `BMAILS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BMAILS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BUSERTAG` varchar(48) NOT NULL DEFAULT '',
  PRIMARY KEY (`BID`),
  KEY `BOWNERID` (`BOWNERID`),
  KEY `BUSERTAG` (`BUSERTAG`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BMESSAGES`
--

DROP TABLE IF EXISTS `BMESSAGES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BMESSAGES` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'primary index',
  `BUSERID` bigint(20) NOT NULL COMMENT 'User id in the DB. can be the phone number or Telegram user name',
  `BTRACKID` bigint(20) NOT NULL COMMENT 'Group the messages along a session, if possible',
  `BPROVIDX` varchar(96) NOT NULL DEFAULT '',
  `BUNIXTIMES` bigint(20) NOT NULL DEFAULT 0,
  `BDATETIME` varchar(20) NOT NULL DEFAULT '',
  `BMESSTYPE` varchar(4) NOT NULL DEFAULT 'WA' COMMENT 'From WA for WhatsApp to TGRM for Telegram or WEB...',
  `BFILE` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is file (>0) or not ==0',
  `BFILEPATH` varchar(255) NOT NULL DEFAULT '' COMMENT 'Path to the file',
  `BFILETYPE` varchar(8) NOT NULL DEFAULT '' COMMENT 'Ending of file - like OGG or DOCX - maximum of 4 letters',
  `BTOPIC` varchar(16) NOT NULL DEFAULT 'UNKNOWN',
  `BLANG` varchar(2) NOT NULL DEFAULT 'NN',
  `BTEXT` text NOT NULL DEFAULT '' COMMENT 'Message of the user',
  `BDIRECT` varchar(3) NOT NULL DEFAULT 'OUT' COMMENT 'Incoming or outgoing?',
  `BSTATUS` varchar(24) NOT NULL DEFAULT '',
  `BFILETEXT` text NOT NULL DEFAULT '\'\'',
  PRIMARY KEY (`BID`),
  KEY `BUSERID` (`BUSERID`),
  KEY `BMESSTYPE` (`BMESSTYPE`),
  KEY `BTRACKID` (`BTRACKID`),
  KEY `BFILE` (`BFILE`),
  KEY `BDIRECT` (`BDIRECT`),
  KEY `BLANG` (`BLANG`),
  KEY `BTOPIC` (`BTOPIC`),
  FULLTEXT KEY `BFILETEXT` (`BFILETEXT`)
) ENGINE=InnoDB AUTO_INCREMENT=584 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BMODELS`
--

DROP TABLE IF EXISTS `BMODELS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BMODELS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BSERVICE` varchar(32) NOT NULL DEFAULT '',
  `BNAME` varchar(48) NOT NULL DEFAULT '',
  `BTAG` varchar(24) NOT NULL DEFAULT '',
  `BSELECTABLE` int(11) NOT NULL DEFAULT 0 COMMENT 'User can pick this model for a prompt.',
  `BPROVID` varchar(96) NOT NULL DEFAULT '',
  `BJSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BJSON`)),
  PRIMARY KEY (`BID`),
  KEY `BTAG` (`BTAG`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BPROMPT2MODEL`
--

DROP TABLE IF EXISTS `BPROMPT2MODEL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BPROMPT2MODEL` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BPROMPTID` bigint(20) NOT NULL,
  `BMODELID` bigint(20) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BPROMPTID` (`BPROMPTID`),
  KEY `BMODELID` (`BMODELID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BPROMPTS`
--

DROP TABLE IF EXISTS `BPROMPTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BPROMPTS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BLANG` varchar(2) NOT NULL DEFAULT 'en',
  `BTOPIC` varchar(64) NOT NULL,
  `BSHORTDESC` text NOT NULL DEFAULT '',
  `BPROMPT` text NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BTOPIC` (`BTOPIC`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BRAG`
--

DROP TABLE IF EXISTS `BRAG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BRAG` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BUID` bigint(20) NOT NULL COMMENT 'User ID',
  `BMID` bigint(20) NOT NULL COMMENT 'Message ID',
  `BGROUPKEY` varchar(64) NOT NULL DEFAULT 'DEFAULT',
  `BTYPE` int(11) NOT NULL DEFAULT 0 COMMENT '0 = text, 1 = file',
  `BSTART` bigint(20) NOT NULL DEFAULT 0,
  `BEND` bigint(20) NOT NULL DEFAULT 0,
  `BEMBED` vector(1024) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BGROUPKEY` (`BGROUPKEY`),
  KEY `BUID` (`BUID`),
  VECTOR KEY `BEMBED` (`BEMBED`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BSESSIONS`
--

DROP TABLE IF EXISTS `BSESSIONS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BSESSIONS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BUSERID` bigint(20) NOT NULL,
  `BASSID` bigint(20) NOT NULL,
  `BLASTMESSAGE` bigint(20) NOT NULL,
  `BSTATE` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`BID`),
  KEY `BUSERID` (`BUSERID`),
  KEY `BASSID` (`BASSID`),
  KEY `BLASTMESSAGE` (`BLASTMESSAGE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BSOCIAL`
--

DROP TABLE IF EXISTS `BSOCIAL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BSOCIAL` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BSRCUSERID` bigint(20) NOT NULL,
  `BDESTUSERID` bigint(20) NOT NULL,
  `BCONFIRM` int(11) NOT NULL DEFAULT 9999 COMMENT 'Needs to be 1 to be confirmed',
  `BSORTKEY` varchar(32) NOT NULL DEFAULT 'CONNECTED' COMMENT 'Can be used for different levels of relation later',
  PRIMARY KEY (`BID`),
  KEY `BSORTKEY` (`BSORTKEY`),
  KEY `BSRCUSERID` (`BSRCUSERID`),
  KEY `BDESTUSERID` (`BDESTUSERID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BTOKENS`
--

DROP TABLE IF EXISTS `BTOKENS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BTOKENS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BNAME` varchar(96) NOT NULL DEFAULT '',
  `BTOKEN` text NOT NULL DEFAULT '',
  PRIMARY KEY (`BID`),
  KEY `BOWNERID` (`BOWNERID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BTRANSLATE`
--

DROP TABLE IF EXISTS `BTRANSLATE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BTRANSLATE` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BTARGET` varchar(2) NOT NULL DEFAULT '',
  `BTOKEN` varchar(64) NOT NULL DEFAULT '',
  `BTEXT` text NOT NULL DEFAULT '',
  PRIMARY KEY (`BID`),
  KEY `BTARGET` (`BTARGET`),
  KEY `BTOKEN` (`BTOKEN`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BUSELOG`
--

DROP TABLE IF EXISTS `BUSELOG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BUSELOG` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BTIMESTAMP` bigint(20) NOT NULL,
  `BUSERID` bigint(20) NOT NULL,
  `BMSGID` bigint(20) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BTIMESTAMP` (`BTIMESTAMP`),
  KEY `BUSERID` (`BUSERID`),
  KEY `BMSGID` (`BMSGID`)
) ENGINE=InnoDB AUTO_INCREMENT=1146 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `BPROVIDERID` varchar(32) NOT NULL DEFAULT '',
  `BUSERLEVEL` varchar(32) NOT NULL DEFAULT 'NEW',
  `BUSERDETAILS` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BUSERDETAILS`)),
  PRIMARY KEY (`BID`),
  KEY `BINTYPE` (`BINTYPE`),
  KEY `BPROVIDERID` (`BPROVIDERID`),
  KEY `BUSERLEVEL` (`BUSERLEVEL`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BWAIDS`
--

DROP TABLE IF EXISTS `BWAIDS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BWAIDS` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BMID` bigint(20) NOT NULL,
  `BWAPHONEID` bigint(20) NOT NULL,
  `BWAPHONENO` bigint(20) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BMID` (`BMID`)
) ENGINE=InnoDB AUTO_INCREMENT=1338 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BWAPHONES`
--

DROP TABLE IF EXISTS `BWAPHONES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BWAPHONES` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BWAID` varchar(64) NOT NULL DEFAULT '',
  `BWAOUTID` varchar(64) NOT NULL DEFAULT '',
  `BWAOUTNUMBER` varchar(64) NOT NULL,
  PRIMARY KEY (`BID`),
  KEY `BWAID` (`BWAID`),
  KEY `BWAOUTID` (`BWAOUTID`),
  KEY `BWAOUTNUMBER` (`BWAOUTNUMBER`),
  KEY `BOWNERID` (`BOWNERID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-05-21 13:19:31
