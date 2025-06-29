-- Table structure for table `BPROMPTMETA`
--

DROP TABLE IF EXISTS `BPROMPTMETA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `BPROMPTMETA` (
  `BID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BPROMPTID` bigint(20) NOT NULL COMMENT 'Reference to BPROMPTS.BID',
  `BTOKEN` varchar(64) NOT NULL COMMENT 'Meta data key',
  `BVALUE` varchar(128) NOT NULL COMMENT 'Meta data value',
  PRIMARY KEY (`BID`),
  KEY `BPROMPTID` (`BPROMPTID`),
  KEY `BTOKEN` (`BTOKEN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */; 