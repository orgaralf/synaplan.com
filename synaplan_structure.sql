-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 23, 2025 at 03:33 PM
-- Server version: 11.7.2-MariaDB-ubu2204-log
-- PHP Version: 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `synaplan`
--

-- --------------------------------------------------------

--
-- Table structure for table `BCAPABILITIES`
--

CREATE TABLE `BCAPABILITIES` (
  `BID` bigint(20) NOT NULL,
  `BKEY` varchar(64) NOT NULL,
  `BCOMMENT` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BCONFIG`
--

CREATE TABLE `BCONFIG` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BGROUP` varchar(64) NOT NULL DEFAULT '',
  `BSETTING` varchar(96) NOT NULL DEFAULT '',
  `BVALUE` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BLISTS`
--

CREATE TABLE `BLISTS` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL,
  `BLISTKEY` varchar(32) NOT NULL,
  `BLISTFORM` varchar(32) NOT NULL DEFAULT 'DEFAULT' COMMENT 'Can have different forms of JSON entries like\r\n\r\nDEFAULT\r\nSHOPPING\r\nRECIPE\r\nTODO\r\nREMINDERS\r\netc.',
  `BLNAME` varchar(96) NOT NULL,
  `BENTRY` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BMAILS`
--

CREATE TABLE `BMAILS` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BUSERTAG` varchar(48) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BMESSAGEMETA`
--

CREATE TABLE `BMESSAGEMETA` (
  `BID` bigint(20) NOT NULL,
  `BMESSID` bigint(20) NOT NULL,
  `BTOKEN` varchar(64) NOT NULL,
  `BVALUE` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BMESSAGES`
--

CREATE TABLE `BMESSAGES` (
  `BID` bigint(20) NOT NULL COMMENT 'primary index',
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
  `BFILETEXT` longtext NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BMODELS`
--

CREATE TABLE `BMODELS` (
  `BID` bigint(20) NOT NULL,
  `BSERVICE` varchar(32) NOT NULL DEFAULT '',
  `BNAME` varchar(48) NOT NULL DEFAULT '',
  `BTAG` varchar(24) NOT NULL DEFAULT '',
  `BSELECTABLE` int(11) NOT NULL DEFAULT 0 COMMENT 'User can pick this model for a prompt.',
  `BPROVID` varchar(96) NOT NULL DEFAULT '',
  `BPRICEIN` float NOT NULL DEFAULT 0.2 COMMENT 'Always US$',
  `BINUNIT` varchar(24) NOT NULL DEFAULT 'per1M',
  `BPRICEOUT` float NOT NULL DEFAULT 0.05 COMMENT 'Always US$',
  `BOUTUNIT` varchar(24) NOT NULL DEFAULT 'per1M',
  `BQUALITY` float NOT NULL DEFAULT 7,
  `BRATING` float NOT NULL DEFAULT 0.5,
  `BJSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BJSON`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BPROMPT2MODEL`
--

CREATE TABLE `BPROMPT2MODEL` (
  `BID` bigint(20) NOT NULL,
  `BPROMPTID` bigint(20) NOT NULL,
  `BMODELID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BPROMPTMETA`
--

CREATE TABLE `BPROMPTMETA` (
  `BID` bigint(20) NOT NULL,
  `BPROMPTID` bigint(20) NOT NULL COMMENT 'Reference to BPROMPTS.BID',
  `BTOKEN` varchar(64) NOT NULL COMMENT 'Meta data key',
  `BVALUE` varchar(128) NOT NULL COMMENT 'Meta data value'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BPROMPTS`
--

CREATE TABLE `BPROMPTS` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BLANG` varchar(2) NOT NULL DEFAULT 'en',
  `BTOPIC` varchar(64) NOT NULL,
  `BSHORTDESC` text NOT NULL DEFAULT '',
  `BPROMPT` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BRAG`
--

CREATE TABLE `BRAG` (
  `BID` bigint(20) NOT NULL,
  `BUID` bigint(20) NOT NULL COMMENT 'User ID',
  `BMID` bigint(20) NOT NULL COMMENT 'Message ID',
  `BGROUPKEY` varchar(64) NOT NULL DEFAULT 'DEFAULT',
  `BTYPE` int(11) NOT NULL DEFAULT 0 COMMENT '0 = text, 1 = file',
  `BSTART` bigint(20) NOT NULL DEFAULT 0,
  `BEND` bigint(20) NOT NULL DEFAULT 0,
  `BEMBED` vector(1024) NOT NULL,
  `VECTOR` key
) ;

-- --------------------------------------------------------

--
-- Table structure for table `BSESSIONS`
--

CREATE TABLE `BSESSIONS` (
  `BID` bigint(20) NOT NULL,
  `BUSERID` bigint(20) NOT NULL,
  `BASSID` bigint(20) NOT NULL,
  `BLASTMESSAGE` bigint(20) NOT NULL,
  `BSTATE` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BSOCIAL`
--

CREATE TABLE `BSOCIAL` (
  `BID` bigint(20) NOT NULL,
  `BSRCUSERID` bigint(20) NOT NULL,
  `BDESTUSERID` bigint(20) NOT NULL,
  `BCONFIRM` int(11) NOT NULL DEFAULT 9999 COMMENT 'Needs to be 1 to be confirmed',
  `BSORTKEY` varchar(32) NOT NULL DEFAULT 'CONNECTED' COMMENT 'Can be used for different levels of relation later'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BTRANSLATE`
--

CREATE TABLE `BTRANSLATE` (
  `BID` bigint(20) NOT NULL,
  `BTARGET` varchar(2) NOT NULL DEFAULT '',
  `BTOKEN` varchar(64) NOT NULL DEFAULT '',
  `BTEXT` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BUSELOG`
--

CREATE TABLE `BUSELOG` (
  `BID` bigint(20) NOT NULL,
  `BTIMESTAMP` bigint(20) NOT NULL,
  `BUSERID` bigint(20) NOT NULL,
  `BMSGID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BUSER`
--

CREATE TABLE `BUSER` (
  `BID` bigint(20) NOT NULL,
  `BCREATED` varchar(20) NOT NULL DEFAULT '',
  `BINTYPE` varchar(4) NOT NULL DEFAULT 'WA',
  `BMAIL` varchar(128) NOT NULL DEFAULT '',
  `BPW` varchar(64) NOT NULL DEFAULT '',
  `BPROVIDERID` varchar(32) NOT NULL DEFAULT '',
  `BUSERLEVEL` varchar(32) NOT NULL DEFAULT 'NEW',
  `BUSERDETAILS` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BUSERDETAILS`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BWAIDS`
--

CREATE TABLE `BWAIDS` (
  `BID` bigint(20) NOT NULL,
  `BMID` bigint(20) NOT NULL,
  `BWAPHONEID` bigint(20) NOT NULL,
  `BWAPHONENO` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BWAPHONES`
--

CREATE TABLE `BWAPHONES` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BWAID` varchar(64) NOT NULL DEFAULT '',
  `BWAOUTID` varchar(64) NOT NULL DEFAULT '',
  `BWAOUTNUMBER` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `BCAPABILITIES`
--
ALTER TABLE `BCAPABILITIES`
  ADD PRIMARY KEY (`BID`);

--
-- Indexes for table `BCONFIG`
--
ALTER TABLE `BCONFIG`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BOWNERID` (`BOWNERID`),
  ADD KEY `BSETTING` (`BSETTING`),
  ADD KEY `BGROUP` (`BGROUP`);

--
-- Indexes for table `BLISTS`
--
ALTER TABLE `BLISTS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BOWNERID` (`BOWNERID`),
  ADD KEY `BLISTKEY` (`BLISTKEY`);

--
-- Indexes for table `BMAILS`
--
ALTER TABLE `BMAILS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BOWNERID` (`BOWNERID`),
  ADD KEY `BUSERTAG` (`BUSERTAG`);

--
-- Indexes for table `BMESSAGEMETA`
--
ALTER TABLE `BMESSAGEMETA`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BMESSID` (`BMESSID`),
  ADD KEY `BTOKEN` (`BTOKEN`);

--
-- Indexes for table `BMESSAGES`
--
ALTER TABLE `BMESSAGES`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BUSERID` (`BUSERID`),
  ADD KEY `BMESSTYPE` (`BMESSTYPE`),
  ADD KEY `BTRACKID` (`BTRACKID`),
  ADD KEY `BFILE` (`BFILE`),
  ADD KEY `BDIRECT` (`BDIRECT`),
  ADD KEY `BLANG` (`BLANG`),
  ADD KEY `BTOPIC` (`BTOPIC`);
ALTER TABLE `BMESSAGES` ADD FULLTEXT KEY `BFILETEXT` (`BFILETEXT`);

--
-- Indexes for table `BMODELS`
--
ALTER TABLE `BMODELS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BTAG` (`BTAG`);

--
-- Indexes for table `BPROMPT2MODEL`
--
ALTER TABLE `BPROMPT2MODEL`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BPROMPTID` (`BPROMPTID`),
  ADD KEY `BMODELID` (`BMODELID`);

--
-- Indexes for table `BPROMPTMETA`
--
ALTER TABLE `BPROMPTMETA`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BPROMPTID` (`BPROMPTID`),
  ADD KEY `BTOKEN` (`BTOKEN`);

--
-- Indexes for table `BPROMPTS`
--
ALTER TABLE `BPROMPTS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BTOPIC` (`BTOPIC`);

--
-- Indexes for table `BRAG`
--
ALTER TABLE `BRAG`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BGROUPKEY` (`BGROUPKEY`),
  ADD KEY `BUID` (`BUID`);

--
-- Indexes for table `BSESSIONS`
--
ALTER TABLE `BSESSIONS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BUSERID` (`BUSERID`),
  ADD KEY `BASSID` (`BASSID`),
  ADD KEY `BLASTMESSAGE` (`BLASTMESSAGE`);

--
-- Indexes for table `BSOCIAL`
--
ALTER TABLE `BSOCIAL`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BSORTKEY` (`BSORTKEY`),
  ADD KEY `BSRCUSERID` (`BSRCUSERID`),
  ADD KEY `BDESTUSERID` (`BDESTUSERID`);

--
-- Indexes for table `BTRANSLATE`
--
ALTER TABLE `BTRANSLATE`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BTARGET` (`BTARGET`),
  ADD KEY `BTOKEN` (`BTOKEN`);

--
-- Indexes for table `BUSELOG`
--
ALTER TABLE `BUSELOG`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BTIMESTAMP` (`BTIMESTAMP`),
  ADD KEY `BUSERID` (`BUSERID`),
  ADD KEY `BMSGID` (`BMSGID`);

--
-- Indexes for table `BUSER`
--
ALTER TABLE `BUSER`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BINTYPE` (`BINTYPE`),
  ADD KEY `BPROVIDERID` (`BPROVIDERID`),
  ADD KEY `BUSERLEVEL` (`BUSERLEVEL`),
  ADD KEY `BMAIL` (`BMAIL`);

--
-- Indexes for table `BWAIDS`
--
ALTER TABLE `BWAIDS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BMID` (`BMID`);

--
-- Indexes for table `BWAPHONES`
--
ALTER TABLE `BWAPHONES`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BWAID` (`BWAID`),
  ADD KEY `BWAOUTID` (`BWAOUTID`),
  ADD KEY `BWAOUTNUMBER` (`BWAOUTNUMBER`),
  ADD KEY `BOWNERID` (`BOWNERID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `BCAPABILITIES`
--
ALTER TABLE `BCAPABILITIES`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BCONFIG`
--
ALTER TABLE `BCONFIG`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BLISTS`
--
ALTER TABLE `BLISTS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BMAILS`
--
ALTER TABLE `BMAILS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BMESSAGEMETA`
--
ALTER TABLE `BMESSAGEMETA`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BMESSAGES`
--
ALTER TABLE `BMESSAGES`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'primary index';

--
-- AUTO_INCREMENT for table `BMODELS`
--
ALTER TABLE `BMODELS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BPROMPT2MODEL`
--
ALTER TABLE `BPROMPT2MODEL`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BPROMPTMETA`
--
ALTER TABLE `BPROMPTMETA`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BPROMPTS`
--
ALTER TABLE `BPROMPTS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BRAG`
--
ALTER TABLE `BRAG`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BSESSIONS`
--
ALTER TABLE `BSESSIONS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BSOCIAL`
--
ALTER TABLE `BSOCIAL`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BTRANSLATE`
--
ALTER TABLE `BTRANSLATE`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BUSELOG`
--
ALTER TABLE `BUSELOG`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BUSER`
--
ALTER TABLE `BUSER`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BWAIDS`
--
ALTER TABLE `BWAIDS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BWAPHONES`
--
ALTER TABLE `BWAPHONES`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
