-- Default Model Configuration for BCONFIG table
-- These settings define the default AI models for different capabilities
-- BOWNERID = 0 represents the default user/system settings

-- Chat Default Model (Groq's llama-3.3-70b-versatile)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'CHAT', '9');

-- Chat Sort Model (Ollama's deepseek-r1:32b for local processing)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'SORT', '9');

-- Chat Summarize Model (Groq's llama-3.3-70b-versatile)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'SUMMARIZE', '9');

-- Text to Picture Default (OpenAI's gpt-image-1)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'TEXT2PIC', '29');

-- Text to Sound Default (OpenAI's tts-1 with Nova)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'TEXT2SOUND', '41');

-- Sound to Text Default (Groq's whisper-large-v3)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'SOUND2TEXT', '21');

-- Picture to Text Default (Groq's llama-4-scout)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'PIC2TEXT', '17');

-- Vectorize Default (Ollama's bge-m3)
INSERT INTO BCONFIG (BID, BOWNERID, BGROUP, BSETTING, BVALUE) VALUES 
(DEFAULT, 0, 'DEFAULTMODEL', 'VECTORIZE', '13');

-- Note: PIC2PIC_DEFAULT and PIC2VID_DEFAULT are not available in current BMODELS table
-- These can be added when the corresponding models are available 