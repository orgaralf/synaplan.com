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
  `BPRICEIN` float NOT NULL DEFAULT 0.2 COMMENT 'Always US$',
  `BINUNIT` varchar(24) NOT NULL DEFAULT 'per1M',
  `BPRICEOUT` float NOT NULL DEFAULT 0.05 COMMENT 'Always US$',
  `BOUTUNIT` varchar(24) NOT NULL DEFAULT 'per1M',
  `BQUALITY` float NOT NULL DEFAULT 7,
  `BRATING` float NOT NULL DEFAULT 0.5,
  `BJSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`BJSON`)),
  PRIMARY KEY (`BID`),
  KEY `BTAG` (`BTAG`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BMODELS`
--

LOCK TABLES `BMODELS` WRITE;
/*!40000 ALTER TABLE `BMODELS` DISABLE KEYS */;
INSERT INTO `BMODELS` VALUES
(1,'Ollama','deepseek-r1:14b','chat',1,'deepseek-r1:14b',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Local model on synaplans company server in Germany. DeepSeek R1 is a Chinese Open Source LLM.\"}'),
(2,'Ollama','Llama 3.3 70b','chat',1,'llama3.3:70b',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Local model on synaplans company server in Germany. Metas Llama Model Version 3.3 with 70b parameters. Heavy load model and relatively slow, even on a dedicated NVIDIA card. Yet good quality!\"}'),
(3,'Ollama','deepseek-r1:32b','chat',1,'deepseek-r1:32b',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Local model on synaplans company server in Germany. DeepSeek R1 is a Chinese Open Source LLM. This is the bigger version with 32b parameters. A bit slower, but more accurate!\"}'),
(6,'Ollama','mistral','chat',1,'mistral',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Local model on synaplans company server in Germany. Mistral 8b model - internally used for RAG retrieval.\"}'),
(9,'Groq','Llama 3.3 70b versatile','chat',1,'llama-3.3-70b-versatile',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Fast API service via groq\",\"params\":{\"model\":\"llama-3.3-70b-versatile\",\"reasoning_format\":\"hidden\",\"messages\":[]}}'),
(13,'Ollama','bge-m3','vectorize',0,'bge-m3',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Vectorize text into synaplans MariaDB vector DB (local) for RAG\",\"params\":{\"model\":\"bge-m3\",\"input\":[]}}'),
(17,'Groq','llama-4-scout-17b-16e-instruct','pic2text',1,'meta-llama/llama-4-scout-17b-16e-instruct',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Groq image processing and text extraction\",\"prompt\":\"Describe image! List the texts in the image, if possible. If not, describe the image in short.\",\"params\":{\"model\":\"llama-3.2-90b-vision-preview\"}}'),
(21,'Groq','whisper-large-v3','sound2text',1,'whisper-large-v3',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Groq whisper model to extract text from a sound file.\",\"params\":{\"file\":\"*LOCALFILEPATH*\",\"model\":\"whisper-large-v3\",\"response_format\":\"text\"}}'),
(25,'OpenAI','dall-e-3','text2pic',1,'dall-e-3',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Open AIs famous text to image model on OpenAI cloud. Costs are 1:1 funneled.\"}'),
(29,'OpenAI','gpt-image-1','text2pic',1,'gpt-image-1',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Open AIs powerful image generation model on OpenAI cloud. Costs are 1:1 funneled.\"}'),
(30,'OpenAI','gpt-4.1','chat',1,'gpt-4.1',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Open AIs text model\"}'),
(33,'Google','ImaGen 3.0','text2pic',1,'models/imagen-3.0-generate-002',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Google Imagen 3.0\"}'),
(37,'Google','Gemini 2.0 Flash','text2sound',1,'models/gemini-2.0-flash',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Google Speech Generation with Gemini 2.0 Flash\"}'),
(41,'OpenAI','tts-1 with Nova','text2sound',1,'tts-1',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Open AIs text to speech, defaulting on voice NOVA.\"}'),
(45,'Google','Veo 2.0','text2vid',1,'models/veo-2.0-generate-001',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Google Video Generation model Veo2\"}'),
(49,'Groq','llama-4-maverick-17b-128e-instruct','chat',1,'meta-llama/llama-4-maverick-17b-128e-instruct',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Groq Llama4 128e processing and text extraction\",\"prompt\":\"\",\"params\":{\"model\":\"meta-llama/llama-4-maverick-17b-128e-instruct\"}}'),
(53,'Groq','deepseek-r1-distill-llama-70b','chat',1,'deepseek-r1-distill-llama-70b',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Groq DeepSeek R1 Distill on Llama\",\"prompt\":\"\",\"params\":{\"model\":\"deepseek-r1-distill-llama-70b\"}}'),
(57,'OpenAI','o3','chat',1,'o3',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Open AIs actual reasoning model.\"}'),
(61,'Google','Gemini 2.5 Pro','chat',1,'gemini-2.5-pro-preview-06-05',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Googles Answer to the other LLM models\"}'),
(65,'Google','Gemini 2.5 Pro','pic2text',1,'gemini-2.5-pro-preview-06-05',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Googles Powerhouse can also process images, not just text\"}'),
(69,'Anthropic','Claude Opus 4','chat',1,'claude-opus-4-20250514',0.2,'per1M',0.05,'per1M',7,0.5,'{\"description\":\"Claude Opus 4 of Anthropic as the alternative chat method.\"}');
/*!40000 ALTER TABLE `BMODELS` ENABLE KEYS */;
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
