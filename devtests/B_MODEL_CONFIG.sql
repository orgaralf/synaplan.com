-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 05, 2025 at 02:30 PM
-- Server version: 11.7.1-MariaDB-ubu2204-log
-- PHP Version: 8.3.16

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
-- Table structure for table `BCONFIG`
--

DROP TABLE IF EXISTS `BCONFIG`;
CREATE TABLE `BCONFIG` (
  `BID` bigint(20) NOT NULL,
  `BOWNERID` bigint(20) NOT NULL DEFAULT 0,
  `BGROUP` varchar(64) NOT NULL DEFAULT '',
  `BSETTING` varchar(96) NOT NULL DEFAULT '',
  `BVALUE` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `BCONFIG`
--

INSERT INTO `BCONFIG` (`BID`, `BOWNERID`, `BGROUP`, `BSETTING`, `BVALUE`) VALUES
(1, 0, 'DEFAULTMODEL', 'CHAT', '9'),
(5, 0, 'DEFAULTMODEL', 'SORT', '9'),
(9, 0, 'DEFAULTMODEL', 'SUMMARIZE', '9'),
(13, 0, 'DEFAULTMODEL', 'TEXT2PIC', '29'),
(17, 0, 'DEFAULTMODEL', 'TEXT2SOUND', '41'),
(21, 0, 'DEFAULTMODEL', 'SOUND2TEXT', '21'),
(25, 0, 'DEFAULTMODEL', 'PIC2TEXT', '17'),
(29, 0, 'DEFAULTMODEL', 'VECTORIZE', '13'),
(33, 0, 'DEFAULTMODEL', 'TEXT2VID', '45');

-- --------------------------------------------------------

--
-- Table structure for table `BMODELS`
--

DROP TABLE IF EXISTS `BMODELS`;
CREATE TABLE `BMODELS` (
  `BID` bigint(20) NOT NULL,
  `BSERVICE` varchar(32) NOT NULL DEFAULT '',
  `BNAME` varchar(48) NOT NULL DEFAULT '',
  `BTAG` varchar(24) NOT NULL DEFAULT '',
  `BSELECTABLE` int(11) NOT NULL DEFAULT 0 COMMENT 'User can pick this model for a prompt.',
  `BPROVID` varchar(96) NOT NULL DEFAULT '',
  `BJSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BJSON`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `BMODELS`
--

INSERT INTO `BMODELS` (`BID`, `BSERVICE`, `BNAME`, `BTAG`, `BSELECTABLE`, `BPROVID`, `BJSON`) VALUES
(1, 'Ollama', 'deepseek-r1:14b', 'chat', 1, 'deepseek-r1:14b', '{\"description\":\"Local model on synaplans company server in Germany. DeepSeek R1 is a Chinese Open Source LLM.\"}'),
(2, 'Ollama', 'Llama 3.3 70b', 'chat', 1, 'llama3.3:70b', '{\"description\":\"Local model on synaplans company server in Germany. Metas Llama Model Version 3.3 with 70b parameters. Heavy load model and relatively slow, even on a dedicated NVIDIA card. Yet good quality!\"}'),
(3, 'Ollama', 'deepseek-r1:32b', 'chat', 1, 'deepseek-r1:32b', '{\"description\":\"Local model on synaplans company server in Germany. DeepSeek R1 is a Chinese Open Source LLM. This is the bigger version with 32b parameters. A bit slower, but more accurate!\"}'),
(6, 'Ollama', 'mistral', 'chat', 1, 'mistral', '{\"description\":\"Local model on synaplans company server in Germany. Mistral 8b model - internally used for RAG retrieval.\"}'),
(9, 'Groq', 'Llama 3.3 70b versatile', 'chat', 1, 'llama-3.3-70b-versatile', '{\"description\":\"Fast API service via groq\",\"params\":{\"model\":\"llama-3.3-70b-versatile\",\"reasoning_format\":\"hidden\",\"messages\":[]}}'),
(13, 'Ollama', 'bge-m3', 'vectorize', 0, 'bge-m3', '{\"description\":\"Vectorize text into synaplans MariaDB vector DB (local) for RAG\",\"params\":{\"model\":\"bge-m3\",\"input\":[]}}'),
(17, 'Groq', 'llama-4-scout-17b-16e-instruct', 'pic2text', 1, 'meta-llama/llama-4-scout-17b-16e-instruct', '{\"description\":\"Groq image processing and text extraction\",\"prompt\":\"Describe image! List the texts in the image, if possible. If not, describe the image in short.\",\"params\":{\"model\":\"llama-3.2-90b-vision-preview\"}}'),
(21, 'Groq', 'whisper-large-v3', 'sound2text', 1, 'whisper-large-v3', '{\"description\":\"Groq whisper model to extract text from a sound file.\",\"params\":{\"file\":\"*LOCALFILEPATH*\",\"model\":\"whisper-large-v3\",\"response_format\":\"text\"}}'),
(25, 'OpenAI', 'dall-e-3', 'text2pic', 1, 'dall-e-3', '{\"description\":\"Open AIs famous text to image model on OpenAI cloud. Costs are 1:1 funneled.\"}'),
(29, 'OpenAI', 'gpt-image-1', 'text2pic', 1, 'gpt-image-1', '{\"description\":\"Open AIs powerful image generation model on OpenAI cloud. Costs are 1:1 funneled.\"}'),
(30, 'OpenAI', 'gpt-4.1', 'chat', 1, 'gpt-4.1', '{\"description\":\"Open AIs text model\"}'),
(33, 'Google', 'ImaGen 3.0', 'text2pic', 1, 'models/imagen-3.0-generate-002', '{\"description\":\"Google Imagen 3.0\"}'),
(37, 'Google', 'Gemini 2.0 Flash', 'text2sound', 1, 'models/gemini-2.0-flash', '{\"description\":\"Google Speech Generation with Gemini 2.0 Flash\"}'),
(41, 'OpenAI', 'tts-1 with Nova', 'text2sound', 1, 'tts-1', '{\"description\":\"Open AIs text to speech, defaulting on voice NOVA.\"}'),
(45, 'Google', 'Veo 2.0', 'text2vid', 1, 'models/veo-2.0-generate-001', '{\"description\":\"Google Video Generation model Veo2\"}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `BCONFIG`
--
ALTER TABLE `BCONFIG`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BOWNERID` (`BOWNERID`),
  ADD KEY `BSETTING` (`BSETTING`),
  ADD KEY `BGROUP` (`BGROUP`);

--
-- Indexes for table `BMODELS`
--
ALTER TABLE `BMODELS`
  ADD PRIMARY KEY (`BID`),
  ADD KEY `BTAG` (`BTAG`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `BCONFIG`
--
ALTER TABLE `BCONFIG`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `BMODELS`
--
ALTER TABLE `BMODELS`
  MODIFY `BID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
