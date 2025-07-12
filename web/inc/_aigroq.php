<?php
/**
 * AIGroq Class
 * 
 * Handles interactions with the Groq AI service for various AI processing tasks
 * including text generation, translation, and file processing.
 * 
 * @package AIGroq
 */

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;

class AIGroq {
    /** @var string Groq API key */
    private static $key;
    
    /** @var Groq Groq client instance */
    private static $client;

    /**
     * Initialize the Groq client
     * 
     * Loads the API key from centralized configuration and creates a new Groq client instance
     * 
     * @return bool True if initialization is successful
     */
    public static function init() {
        self::$key = ApiKeys::getGroq();
        if(!self::$key) {
            error_log("Groq API key not configured");
            return false;
        }
        self::$client = new Groq(self::$key);
        return true;
    }

    /**
     * Message sorting prompt handler
     * 
     * Analyzes and categorizes incoming messages to determine their intent and
     * appropriate handling method. This helps in routing messages to the correct
     * processing pipeline.
     * 
     * @param array $msgArr Current message array
     * @param array $threadArr Conversation thread history
     * @return array|string|bool Sorting result or error message
     */
    public static function sortingPrompt($msgArr, $threadArr): array|string|bool {
        // prompt builder
        $threadArr = [];
        $systemPrompt = BasicAI::getAprompt('tools:sort');

        $client = self::$client;
        
        $arrMessages = [
            ['role' => 'system', 'content' => $systemPrompt['BPROMPT']],
        ];

        // Build message history
        foreach($threadArr as $msg) {
            if($msg['BDIRECT'] == 'IN') {
                $msg['BTEXT'] = Tools::cleanTextBlock($msg['BTEXT']);
                $msgText = $msg['BTEXT'];
                if(strlen($msg['BFILETEXT']) > 1) {
                    $msgText .= " User provided a file: ".$msg['BFILETYPE'].", saying: '".$msg['BFILETEXT']."'\n\n";
                }
                $arrMessages[] = ['role' => 'user', 'content' => $msgText];
            } 
            if($msg['BDIRECT'] == 'OUT') {
                if(strlen($msg['BTEXT'])>200) {
                    // Truncate at word boundary to avoid breaking JSON or quotes
                    $truncatedText = substr($msg['BTEXT'], 0, 200);
                    // Find the last complete word
                    $lastSpace = strrpos($truncatedText, ' ');
                    if ($lastSpace !== false && $lastSpace > 150) {
                        $truncatedText = substr($truncatedText, 0, $lastSpace);
                    }
                    // Clean up any trailing quotes or incomplete JSON
                    $truncatedText = rtrim($truncatedText, '"\'{}[]');
                    $msg['BTEXT'] = $truncatedText . "...";
                }
                $arrMessages[] = ['role' => 'assistant', 'content' => "[".$msg['BID']."] ".$msg['BTEXT']];
            }
        }

        // Add current message
        $msgText = json_encode($msgArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $arrMessages[] = ['role' => 'user', 'content' => $msgText];

        // ------------------------------------------------
        try {
            // set model
            $AIGENERALmodel = $GLOBALS["AI_SORT"]["MODEL"];
            $chat = $client->chat()->completions()->create([
                'model' => $AIGENERALmodel,
                'reasoning_format' => 'hidden',
                'messages' => $arrMessages
            ]);
            
            // Debug: Log the raw response
            /*
            if (isset($chat['choices']) && is_array($chat['choices'])) {
                error_log("Number of choices: " . count($chat['choices']));
                if (isset($chat['choices'][0])) {
                    error_log("First choice structure: " . print_r(array_keys($chat['choices'][0]), true));
                    if (isset($chat['choices'][0]['message'])) {
                        error_log("Message structure: " . print_r(array_keys($chat['choices'][0]['message']), true));
                        error_log("Content length: " . strlen($chat['choices'][0]['message']['content']));
                        error_log("First 200 chars of content: " . substr($chat['choices'][0]['message']['content'], 0, 200));
                    }
                }
            }
            */
        } catch (GroqException $err) {
            error_log("GROQ API ERROR: " . $err->getMessage());
            return "*API sorting Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        // ------------------------------------------------
        // Clean and return response
        $answer = $chat['choices'][0]['message']['content'];
        
        $answer = str_replace("```json\n", "", $answer);
        $answer = str_replace("\n```", "", $answer);
        $answer = str_replace("```json", "", $answer);
        $answer = str_replace("```", "", $answer);
        $answer = trim($answer);
        return $answer;
    }
    /**
     * Topic-specific response generator
     * 
     * Generates responses based on the specific topic of the message.
     * Uses topic-specific prompts to create more focused and relevant responses.
     * 
     * @param array $msgArr Message array containing topic information
     * @param array $threadArr Thread context for conversation history
     * @return array|string|bool Topic-specific response or error message
     */
    public static function topicPrompt($msgArr, $threadArr, $stream = false): array|string|bool {
        //error_log('topicPrompt: '.print_r($msgArr, true));

        $systemPrompt = BasicAI::getAprompt($msgArr['BTOPIC'], $msgArr['BLANG'], $msgArr, true);

        if(isset($systemPrompt['TOOLS'])) {
            // call tools before the prompt is executed!
        }
        $client = self::$client;
        $arrMessages = [
            ['role' => 'system', 'content' => $systemPrompt['BPROMPT']],
        ];

        // Build message history
        foreach($threadArr as $msg) {
            $arrMessages[] = ['role' => 'user', 'content' => "[".$msg['BID']."] ".$msg['BTEXT']];
        }

        // Add current message
        $msgText = json_encode($msgArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $arrMessages[] = ['role' => 'user', 'content' => $msgText];
        
        // which model on groq?
        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];

        try {
            $chat = $client->chat()->completions()->create([
                'model' => $myModel,
                'reasoning_format' => 'hidden',
                'messages' => $arrMessages
            ]);
        } catch (GroqException $err) {
            return "*APItopic Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }

        $answer = $chat['choices'][0]['message']['content'];

        // error_log("***************** DEBUG: GROQ answer: ".$answer);
        // Clean JSON response - only if it starts with JSON markers
        if (strpos($answer, "```json\n") === 0) {
            $answer = substr($answer, 8); // Remove "```json\n" from start
            if (strpos($answer, "\n```") !== false) {
                $answer = str_replace("\n```", "", $answer);
            }
        } elseif (strpos($answer, "```json") === 0) {
            $answer = substr($answer, 7); // Remove "```json" from start
            if (strpos($answer, "```") !== false) {
                $answer = str_replace("```", "", $answer);
            }
        } elseif (strpos($answer, "```") === 0) {
            $answer = substr($answer, 3); // Remove "```" from start
            if (strpos($answer, "```") !== false) {
                $answer = str_replace("```", "", $answer);
            }
        }
        
        $answer = trim($answer);

        if(Tools::isValidJson($answer) == false) {
            // error_log(" __________________________ GROQ ANSWER: ".$answer);
            // return "*API topic Error - Ralf made a bubu - please mail that to him: * " . "Answer is not valid JSON";
            $arrAnswer = $msgArr;
            $arrAnswer['BTEXT'] = $answer;
            $arrAnswer['BDIRECT'] = 'OUT';
        } else {
            try {
                $arrAnswer = json_decode($answer, true);
            } catch (GroqException $err) {
                return "*APItopic Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
            }    
        }

        return $arrAnswer;
    }

    /**
     * Welcome message generator
     * 
     * Creates a personalized welcome message for new users.
     * Includes information about available commands and features.
     * 
     * @param array $msgArr Message array containing user information
     * @return array|string|bool Welcome message or error message
     */
    public static function welcomePrompt($msgArr): array|string|bool {
        $arrPrompt = BasicAI::getAprompt('tools:help');
        $systemPrompt = $arrPrompt['BPROMPT'];
        
        $client = self::$client;

        $arrMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        
        $msgText = '{"BCOMMAND":"/list","BLANG":"'.$msgArr['BLANG'].'"}';
        $arrMessages[] = ['role' => 'user', 'content' => $msgText];
        
        try {
            $chat = $client->chat()->completions()->create([
                'model' => 'llama-3.3-70b-versatile',
                'messages' => $arrMessages
            ]);
        } catch (GroqException $err) {
            return "*APIwelcome Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        return $chat['choices'][0]['message']['content'];
    }
    /**
     * Image content analyzer
     * 
     * Analyzes image content and generates a description using Groq's vision API.
     * Handles image resizing for large files and returns the analysis results.
     * 
     * @param array $arrMessage Message array containing image information
     * @return array|string|bool Image description or error message
     */
    public static function explainImage($arrMessage): array|string|bool {
        // Resize image if too large
        if(filesize('./up/'.$arrMessage['BFILEPATH']) > intval(1024*1024*3.5)) {
            $imageFile = Tools::giveSmallImage($arrMessage['BFILEPATH'], false, 1200);
            $savedFile = imagepng($imageFile, "./up/tmp_del_".$arrMessage['BID'].".png");
            chmod("./up/tmp_del_".$arrMessage['BID'].".png", 0755);
            $imagePath = "up/tmp_del_".$arrMessage['BID'].".png";
        } else {
            $imagePath = 'up/'.$arrMessage['BFILEPATH'];
        }

        $imageURL = 'https://wa.metadist.de/'.($imagePath);
        $client = self::$client;

        // Use the global prompt if available, otherwise use default
        $imgPrompt = isset($GLOBALS["AI_PIC2TEXT"]["PROMPT"]) 
        ? $GLOBALS["AI_PIC2TEXT"]["PROMPT"] 
        : 'Describe this image in detail. Be comprehensive and accurate.';

        try {
            $analysis = $client->vision()->analyze($imagePath, 
                $imgPrompt, 
                ['model' => $GLOBALS["AI_PIC2TEXT"]["MODEL"]]);
            $arrMessage['BFILETEXT'] = $analysis['choices'][0]['message']['content'];
        } catch (GroqException $err) {
            $arrMessage['BFILETEXT'] =  "*API Image Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        return $arrMessage;
    }
    /**
     * Audio to text converter
     * 
     * Transcribes MP3 audio files to text using Groq's audio processing API.
     * Handles audio file processing and returns the transcription.
     * 
     * @param array $arrMessage Message array containing audio file information
     * @return array|string|bool Transcription text or error message
     */
    public static function mp3ToText($arrMessage): array|string|bool {
        $client = self::$client;
        try {
            $transcription = $client->audio()->transcriptions()->create([
                'file' => './up/'.$arrMessage['BFILEPATH'],
                'model' => 'whisper-large-v3',
                'response_format' => 'text', /* Or 'text', 'json' */
                /*'language' => 'en', /* ISO 639-1 code (optional but recommended) */
                /* 'prompt' => 'Audio transcription...' /* (optional) */
            ]);
        
            $fullText = $transcription;

        } catch (\LucianoTonet\GroqPHP\GroqException $e) {
            $fullText = "Error: " . $e->getMessage();
        }
        return $fullText;
    } 
    /**
     * Text translator
     * 
     * Translates text content to a specified language using Groq's translation capabilities.
     * Supports multiple languages and handles translation errors gracefully.
     * 
     * @param array $msgArr Message array containing text to translate
     * @param string $lang Target language code (optional)
     * @param string $sourceText Field containing text to translate (optional)
     * @return array Translated message array
     */
    public static function translateTo($msgArr, $lang='en', $sourceText='BTEXT'): array {    
        $targetLang = $msgArr['BLANG'];
        
        if(strlen($lang) == 2) {
            $targetLang = $lang;
        }

        $qTerm = $msgArr[$sourceText];
        
        if(substr($qTerm, 0, 1) != '/') {
            $qTerm = "/translate ".$lang." ".$qTerm;
        }

        $client = self::$client;

        $tPrompt = BasicAI::getAprompt('tools:lang');
        error_log("tPrompt: ".$tPrompt);
        $arrMessages = [
            ['role' => 'system', 'content' => $tPrompt]
        ];

        $arrMessages[] = ['role' => 'user', 'content' => $qTerm];


        try {
            $chat = $client->chat()->completions()->create([
                'model' => 'llama-3.3-70b-versatile',
                'messages' => $arrMessages
            ]);
        } catch (GroqException $err) {
            $msgArr['BTEXT'] = "*APItranslate Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
            return $msgArr;
        }

        $msgArr[$sourceText] = $chat['choices'][0]['message']['content'];
        return $msgArr;
    }
    /**
     * Summarize text using Groq's summarization API
     * 
     * Summarizes a given text using Groq's summarization capabilities.
     * 
    */
    public static function summarizePrompt($text): string {
        $client = self::$client;
        $arrMessages = [
            ['role' => 'system', 'content' => 'You summarize the text of the user to a short 2-3 sentence summary. Do not add any other text, just the essence of the text. Stay under 128 characters. Answer in the language of the text.'],
        ];
        $arrMessages[] = ['role' => 'user', 'content' => $text];
        
        $chat = $client->chat()->completions()->create([
            'model' => 'llama-3.3-70b-versatile',
            'messages' => $arrMessages
        ]);
        return $chat['choices'][0]['message']['content'];
    }
}

// Initialize the Groq client
AIGroq::init();