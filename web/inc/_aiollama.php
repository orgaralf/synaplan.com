<?php

use ArdaGnsrn\Ollama\Ollama;

class AIOllama {
    private static $host;
    private static $client;

    public static function init(): void {
        $myServer = ApiKeys::getKey("OLLAMA_SERVER");
        self::$host = 'http://'.$myServer;
        self::$client = Ollama::client(self::$host);
    }

    /**
     * Message sorting prompt handler
     * 
     * Analyzes and categorizes incoming messages to determine their intent and
     * appropriate handling method using local Ollama.
     * 
     * @param array $msgArr Current message array
     * @param array $threadArr Conversation thread history
     * @return array|string|bool Sorting result or error message
     */
    public static function sortingPrompt($msgArr, $threadArr): array|string|bool {
        // prompt builder
        file_put_contents('up/ollama_log_'.(date("His")).'.txt', print_r($msgArr, true));
        $systemPrompt = BasicAI::getAprompt('tools:sort');

        $client = self::$client;
        
        // Build the complete prompt with system context and message history
        $fullPrompt = $systemPrompt['BPROMPT']."\n\n";
        
        // Add conversation history
        $fullPrompt .= "Conversation History:\n";
        foreach($threadArr as $msg) {
            if($msg['BDIRECT'] == 'IN') {
                $msg['BTEXT'] = Tools::cleanTextBlock($msg['BTEXT']);
                $msgText = $msg['BTEXT'];
                if(strlen($msg['BFILETEXT']) > 1) {
                    $msgText .= " User provided a file: ".$msg['BFILETYPE'].", saying: '".$msg['BFILETEXT']."'\n\n";
                }
                $fullPrompt .= "User: " . $msgText . "\n";
            } 
            if($msg['BDIRECT'] == 'OUT') {
                if(strlen($msg['BTEXT'])>1000) {
                    // Truncate at word boundary to avoid breaking JSON or quotes
                    $truncatedText = substr($msg['BTEXT'], 0, 1000);
                    // Find the last complete word
                    $lastSpace = strrpos($truncatedText, ' ');
                    if ($lastSpace !== false && $lastSpace > 800) {
                        $truncatedText = substr($truncatedText, 0, $lastSpace);
                    }
                    // Clean up any trailing quotes or incomplete JSON
                    $truncatedText = rtrim($truncatedText, '"\'{}[]');
                    $msg['BTEXT'] = $truncatedText . "...";
                }
                $fullPrompt .= "Assistant: [".$msg['BID']."] ".$msg['BTEXT'] . "\n";
            }
        }

        // Add current message
        $msgText = json_encode($msgArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fullPrompt .= "\nCurrent message to analyze: " . $msgText;

        try {
            $completions = $client->completions()->create([
                'model' => 'deepseek-r1:32b',
                'prompt' => $fullPrompt,
            ]);
            
            $answer = $completions->response;
        } catch (Exception $err) {
            return "*API sorting Error - Ollama error: * " . $err->getMessage();
        }

        // Clean and return response
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
     * Generates responses based on the specific topic of the message using local Ollama.
     * 
     * @param array $msgArr Message array containing topic information
     * @param array $threadArr Thread context for conversation history
     * @return array|string|bool Topic-specific response or error message
     */
    public static function topicPrompt($msgArr, $threadArr, $stream = false): array|string|bool {
        $systemPrompt = BasicAI::getAprompt($msgArr['BTOPIC'], $msgArr['BLANG'], $msgArr, true);

        if(isset($systemPrompt['TOOLS'])) {
            // call tools before the prompt is executed!
        }
        
        $client = self::$client;
        
        // Build the complete prompt with system context and message history
        $fullPrompt = $systemPrompt['BPROMPT'] . "\n\n";
        
        // Add conversation history
        $fullPrompt .= "Conversation History:\n";
        foreach($threadArr as $msg) {
            $fullPrompt .= "[".$msg['BID']."] ".$msg['BTEXT'] . "\n";
        }

        // Add current message
        $msgText = json_encode($msgArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fullPrompt .= "\nCurrent message: " . $msgText;
        
        try {
            $completions = $client->completions()->create([
                'model' => 'deepseek-r1:32b',
                'prompt' => $fullPrompt,
            ]);
            
            $answer = $completions->response;
        } catch (Exception $err) {
            return "*API topic Error - Ollama error: * " . $err->getMessage();
        }

        // Clean response
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
        }
        $answer = trim($answer);

        if(Tools::isValidJson($answer) == false) {
            $arrAnswer = $msgArr;
            $arrAnswer['BTEXT'] = $answer;
            $arrAnswer['BDIRECT'] = 'OUT';
        } else {
            try {
                $arrAnswer = json_decode($answer, true);
            } catch (Exception $err) {
                return "*API topic Error - Ollama error: * " . $err->getMessage();
            }    
        }

        return $arrAnswer;
    }

    /**
     * Welcome message generator
     * 
     * Creates a personalized welcome message for new users using local Ollama.
     * 
     * @param array $msgArr Message array containing user information
     * @return array|string|bool Welcome message or error message
     */
    public static function welcomePrompt($msgArr): array|string|bool {
        $arrPrompt = BasicAI::getAprompt('tools:help');
        $systemPrompt = $arrPrompt['BPROMPT'];
        
        $client = self::$client;

        $fullPrompt = $systemPrompt . "\n\n";
        $msgText = '{"BCOMMAND":"/list","BLANG":"'.$msgArr['BLANG'].'"}';
        $fullPrompt .= "User message: " . $msgText;
        
        try {
            $completions = $client->completions()->create([
                'model' => 'deepseek-r1:32b',
                'prompt' => $fullPrompt,
            ]);
            
            return $completions->response;
        } catch (Exception $err) {
            return "*API welcome Error - Ollama error: * " . $err->getMessage();
        }
    }

    // ****************************************************************
    // local vision AI
    // ****************************************************************
    public static function explainImage($arrMessage): array|string|bool {
        // Handle large images
        if(filesize('./up/'.$arrMessage['BFILEPATH']) > intval(1024*1024*3.5)) {
            $imageFile = Tools::giveSmallImage($arrMessage['BFILEPATH'], false, 1200);
            $savedFile = imagepng($imageFile, "./up/tmp_del_".$arrMessage['BID'].".png");
            chmod("./up/tmp_del_".$arrMessage['BID'].".png", 0755);
            $imagePath = "./up/tmp_del_".$arrMessage['BID'].".png";
        } else {
            $imagePath = './up/'.$arrMessage['BFILEPATH'];
        }

        $myImage = base64_encode(file_get_contents($imagePath));
        $client = self::$client;
        
        // Use the global prompt if available, otherwise use default
        $imgPrompt = isset($GLOBALS["AI_PIC2TEXT"]["PROMPT"]) 
            ? $GLOBALS["AI_PIC2TEXT"]["PROMPT"] 
            : 'Describe this image in detail. Be comprehensive and accurate.';
            
        if($arrMessage['BTEXT'] != '') {
            $imgPrompt .= ' User requested: '.$arrMessage['BTEXT'];
        }

        try {
            $completions = $client->completions()->create([
                'model' => 'llama3.2-vision:11b',
                'prompt' => $imgPrompt,
                'images' => [$myImage],
            ]); 

            $arrRes = $completions->toArray();
            $arrMessage['BFILETEXT'] = $arrRes['response'];
        } catch (Exception $err) {
            $arrMessage['BFILETEXT'] = "*API Image Error - Ollama error: * " . $err->getMessage();
        }
        
        return $arrMessage;
    }

    /**
     * Audio to text converter
     * 
     * Note: Ollama doesn't have built-in audio transcription capabilities like Groq.
     * This method returns an error message indicating the limitation.
     * 
     * @param array $arrMessage Message array containing audio file information
     * @return array|string|bool Error message indicating unsupported feature
     */
    public static function mp3ToText($arrMessage): array|string|bool {
        // Ollama doesn't support audio transcription natively
        // You would need to use a separate service like OpenAI Whisper or similar
        return "Audio transcription is not supported by Ollama. Please use external transcription service.";
    }

    /**
     * Text translator
     * 
     * Translates text content to a specified language using local Ollama.
     * 
     * @param array $msgArr Message array containing text to translate
     * @param string $lang Target language code (optional)
     * @param string $sourceText Field containing text to translate (optional)
     * @return array Translated message array
     */
    public static function translateTo($msgArr, $lang='', $sourceText='BTEXT'): array {    
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
        $fullPrompt = $tPrompt . "\n\nUser message: " . $qTerm;

        try {
            $completions = $client->completions()->create([
                'model' => 'deepseek-r1:32b',
                'prompt' => $fullPrompt,
            ]);
            
            $msgArr[$sourceText] = $completions->response;
        } catch (Exception $err) {
            $msgArr['BTEXT'] = "*API translate Error - Ollama error: * " . $err->getMessage();
        }

        return $msgArr;
    }

    /**
     * Summarize text using local Ollama
     * 
     * Summarizes a given text using Ollama's summarization capabilities.
     * 
     * @param string $text Text to summarize
     * @return string Summarized text
     */
    public static function summarizePrompt($text): string {
        $client = self::$client;
        
        $prompt = 'You summarize the text of the user to a short 2-3 sentence summary. Do not add any other text, just the essence of the text. Stay under 128 characters. Answer in the language of the text.\n\nText to summarize: ' . $text;
        
        try {
            $completions = $client->completions()->create([
                'model' => 'deepseek-r1:32b',
                'prompt' => $prompt,
            ]);
            
            return $completions->response;
        } catch (Exception $err) {
            return "*API summarize Error - Ollama error: * " . $err->getMessage();
        }
    }

    // ****************************************************************************************************** 
    // transfor the message to vector
    // ****************************************************************************************************** 
    public static function embed($text) {
        $vecclient = self::$client;
        $embeds = $vecclient->embed()->create([
            'model' => 'bge-m3',
            'input' => [ $text ]
        ]);
        $arrRes = $embeds->toArray();
        return $arrRes['embeddings'][0];
    }

    // ****************************************************************************************************** 
    // nicefy the text with a LLM locally
    // ****************************************************************************************************** 
    public static function nicefy($text, $lang) {
        $client = self::$client;
        $completions = $client->completions()->create([
            'model' => 'deepseek-r1:32b',
            'prompt' => 'Translate the following text into language "'.$lang.'". Try to improve the text, if you see typos: '.$text
        ]);
        return $completions->response;
    }
}

// ****************************************************************************************************** 
$test = AIOllama::init();
