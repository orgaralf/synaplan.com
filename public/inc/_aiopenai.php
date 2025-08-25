<?php

/**
 * AIOpenAi Class
 * 
 * Handles interactions with the OpenAI API for various AI processing tasks
 * including text generation, translation, image analysis, and audio processing.
 * 
 * @package AIOpenAi
 */

class AIOpenAI {
    /** @var string OpenAI API key */
    private static $key;
    /** @var OpenAI client instance */
    private static $client;

    /**
     * Initialize the OpenAI client
     * 
     * Loads the API key from the centralized configuration and creates a new OpenAI client instance
     * 
     * @return bool True if initialization is successful
     */
    public static function init() {
        self::$key = ApiKeys::getOpenAI();
        if (!self::$key) {
            if($GLOBALS["debug"]) error_log("OpenAI API key not configured");
            return false;
        }
        self::$client = OpenAI::client(self::$key);
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
                $arrMessages[] = ['role' => 'assistant', 'content' => "[".$msg['BID']."] ".$msg['BTEXT']];
            }
        }

        // Add current message
        $msgText = json_encode($msgArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $arrMessages[] = ['role' => 'user', 'content' => $msgText];
        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];

        try {
            $chat = $client->chat()->create([
                'model' =>  $myModel,
                'messages' => $arrMessages
            ]);
        } catch (Exception $err) {
            print "Error: ".$err->getMessage();
            return "*API sorting Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }

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
     * Supports both streaming and non-streaming modes.
     * 
     * @param array $msgArr Message array containing topic information
     * @param array $threadArr Thread context for conversation history
     * @param bool $stream Whether to use streaming mode
     * @return array|string|bool Topic-specific response or error message
     */
    public static function topicPrompt($msgArr, $threadArr, $stream = false): array|string|bool {
        set_time_limit(3600);
        $systemPrompt = BasicAI::getAprompt($msgArr['BTOPIC'], $msgArr['BLANG'], $msgArr, true);

        $client = self::$client;
        if ($stream) {
            $arrMessages = [
                ['role' => 'system', 'content' => 'You are the Synaplan.com AI assistant. Please answer in the language of the user.'],
            ];
        } else {
            $arrMessages = [
                ['role' => 'system', 'content' => $systemPrompt['BPROMPT']],
            ];
        }
        // Build message history
        foreach($threadArr as $msg) {
            $role = 'user';
            if($msg['BDIRECT'] == 'OUT') {
                $role = 'assistant';
            }   
            $arrMessages[] = ['role' => $role, 'content' => "[".$msg['BDATETIME']."]: ".$msg['BTEXT']];
        }

        // Add current message
        if($stream) {
            $msgText = $msgArr['BTEXT'];
            if(strlen($msgArr['BFILETEXT']) > 1) {
                $msgText .= "\n\n\n---\n\n\nUser provided a file: ".$msgArr['BFILETYPE'].", saying: '".$msgArr['BFILETEXT']."'\n\n";
            }
            $arrMessages[] = ['role' => 'user', 'content' => $msgText];
        } else {
            $msgText = json_encode($msgArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $arrMessages[] = ['role' => 'user', 'content' => $msgText];    
        }

        // different model configged?
        if(isset($systemPrompt['SETTINGS'])) {
            foreach($systemPrompt['SETTINGS'] as $setting) {
                $systemPrompt[$setting['BTOKEN']] = $setting['BVALUE'];
            }
            if(isset($systemPrompt['aiModel']) AND intval($systemPrompt['aiModel']) > 0) {
                $modelArr = BasicAI::getModelDetails(intval($systemPrompt['aiModel']));
                $myModel = $modelArr['BPROVID'];
            } else {
                $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
            }
        } else {
            $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
        }

        //error_log(" *************** OPENAI call - response object:" . date("Y-m-d H:i:s"));        
        // now ask the AI and give the stream out or the result when done!
        try {
            if ($stream) {
                // Use streaming mode - simplified
                $stream = $client->responses()->createStreamed([
                    'model' => $myModel,
                    'tools' => [
                        [
                            "type" => "web_search_preview",
                            "search_context_size" => "low"
                        ]
                    ],
                    'input' => $arrMessages,
                    'tool_choice' => 'auto',
                    'parallel_tool_calls' => true,
                    'store' => true,
                    'metadata' => [
                        'user_id' => $msgArr['BUSERID'],
                        'session_id' => $msgArr['BTRACKID']
                    ]
                ]);

                $answer = '';
                
                foreach ($stream as $response) {
                    // Handle text delta events - stream the difference
                    if ($response->event === 'response.output_text.delta') {
                        // Try to access delta text directly from the response object
                        $textChunk = '';
                        
                        $repArr = $response->toArray();

                        // Try different ways to access delta text
                        if(isset($repArr['data']['delta'])) {
                            $textChunk = $repArr['data']['delta'];
                            // Debug: log the response structure on localhost
                            if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) && 1==2) {
                                if($GLOBALS["debug"]) error_log("DEBUG: Response structure: " . print_r($response->toArray(), true));
                            }
                        }
                        
                        // Only stream non-empty chunks
                        if (!empty($textChunk)) {
                            if($GLOBALS["debug"]) error_log("DEBUG: Streaming chunk: " . $textChunk);
                            $answer .= $textChunk;
                            // Stream the chunk to frontend
                            Frontend::statusToStream($msgArr["BID"], 'ai', $textChunk);
                        }
                    }
                    
                    // Handle text done events - might contain final text
                    if ($response->event === 'response.output_text.done') {
                        // Debug: log the response structure on localhost
                        if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) && 1==2) {
                            if($GLOBALS["debug"]) error_log("DEBUG: Response event: " . $response->event);
                            if($GLOBALS["debug"]) error_log("DEBUG: Response structure: " . print_r($response->toArray(), true));
                        }
                        // Try to access final text
                        if (isset($response->text)) {
                            $finalText = $response->text;
                            // If we don't have accumulated text, use the final text
                            if(empty($answer)) {
                                $answer = $finalText;
                            }
                        }
                    }
                    
                    // Handle errors
                    if ($response->event === 'error') {
                        return "*API topic Error - Streaming failed: " . $response->message;
                    }
                }
                // error_log("***************** DEBUG: Streaming done - answer: ".$answer);
                // **************************************************************************************************                
                // ** return a different way to the rest of the process
                $arrAnswer = $msgArr;
                $arrAnswer['BTEXT'] = $answer;
                $arrAnswer['BDIRECT'] = 'OUT';
                $arrAnswer['BDATETIME'] = date('Y-m-d H:i:s');
                $arrAnswer['BUNIXTIMES'] = time();
                
                // Clear file-related fields since there's no valid JSON
                $arrAnswer['BFILE'] = 0;
                $arrAnswer['BFILEPATH'] = '';
                $arrAnswer['BFILETYPE'] = '';
                $arrAnswer['BFILETEXT'] = '';

                // Add model information to the response
                $arrAnswer['_USED_MODEL'] = $myModel;
                $arrAnswer['_AI_SERVICE'] = 'AIOpenAI';
                
                // avoid double output to the chat window
                $arrAnswer['ALREADYSHOWN'] = true;

                return $arrAnswer;
                
            } else {
                // Use non-streaming mode (existing logic)
                $chat = $client->responses()->create([
                    'model' => $myModel,
                    'tools' => [
                        [
                            "type" => "web_search_preview",
                            "search_context_size" => "low"
                        ]
                    ],
                    'input' => $arrMessages,
                    'tool_choice' => 'auto',
                    'parallel_tool_calls' => true,
                    'store' => true,
                    'metadata' => [
                        'user_id' => $msgArr['BUSERID'],
                        'session_id' => $msgArr['BTRACKID']
                    ]
                ]);
                // JSON response processing
                // Process the response (same logic for both streaming and non-streaming)
                $answer = '';
                foreach ($chat->output as $output) {
                    if ($output->type === 'message'          // nur Messages …
                        && $output->role === 'assistant'     // … vom Assistenten
                        && $output->status === 'completed')  // … die fertig sind
                    {
                        foreach ($output->content as $content) {
                            if ($content->type === 'output_text') {
                                if($stream) {
                                    // Already streamed above, just accumulate for final processing
                                    $answer .= $content->text . PHP_EOL;
                                } else {
                                    $answer .= $content->text . PHP_EOL;   // <- text chunks
                                }
                            }
                        }
                    }
                }
                
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

                //error_log(" __________________________ OPENAI ANSWER: ".$answer);

                if(Tools::isValidJson($answer) == false) {
                    //error_log(" __________________________ OPENAI ANSWER: ".$answer);
                    // Fill $arrAnswer with values from $msgArr when JSON is not valid
                    $arrAnswer = $msgArr;
                    $arrAnswer['BTEXT'] = $answer;
                    $arrAnswer['BDIRECT'] = 'OUT';
                    $arrAnswer['BDATETIME'] = date('Y-m-d H:i:s');
                    $arrAnswer['BUNIXTIMES'] = time();
                    
                    // Clear file-related fields since there's no valid JSON
                    $arrAnswer['BFILE'] = 0;
                    $arrAnswer['BFILEPATH'] = '';
                    $arrAnswer['BFILETYPE'] = '';
                    $arrAnswer['BFILETEXT'] = '';

                } else {
                    try {
                        $arrAnswer = json_decode($answer, true);
                    } catch (Exception $err) {
                        return "*API topic Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
                    }    
                }
                
                // Add model information to the response
                $arrAnswer['_USED_MODEL'] = $myModel;
                $arrAnswer['_AI_SERVICE'] = 'AIOpenAI';
                
                //file_put_contents('up/openai_log_'.(date("His")).'.txt', print_r($chat, true));            
                return $arrAnswer;
            }
            
        } catch (Exception $err) {
            return "*APItopic Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
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
        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
        try {
            $chat = $client->chat()->create([
                'model' => $myModel,
                'messages' => $arrMessages
            ]);
        } catch (Exception $err) {
            return "*APIwelcome Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        return $chat['choices'][0]['message']['content'];
    }    
    /**
     * Text to speech converter
     * 
     * Converts text content to speech using OpenAI's text-to-speech API.
     * Saves the generated audio file and returns the file information.
     * 
     * @param array $msgArr Message array containing text to convert
     * @param array $usrArr User array containing user information
     * @return array|bool Message array with file information or false on error
     */
    public static function textToSpeech($msgArr, $usrArr): array | bool {
        // https://github.com/openai-php/client/issues/30
        $client = OpenAI::client(self::$key);

        $response = $client->audio()->speech([
            'model' => $GLOBALS["AI_TEXT2SOUND"]["MODEL"],
            'input' => $msgArr['BTEXT'],
            'voice' => 'nova',
        ]);
        
        // --- save the output
        if(strlen($response) > 1000) {
            $userPhoneNo = $usrArr['BPROVIDERID'];
            $savePath = substr($userPhoneNo, -5, 3) . '/' . substr($userPhoneNo, -2, 2) . '/' . date("Ym");        
            if(!is_dir('./up/'.$savePath)) {
                mkdir('./up/'.$savePath, 0777, true);
            }
            $saveTo = $savePath . '/' . 'wa_'.(time()).'.mp3';
            file_put_contents('./up/'.$saveTo, $response);
            $msgArr['BFILE']= 1;
            $msgArr['BFILEPATH'] = $saveTo;
            $msgArr['BFILETYPE'] = 'mp3';

            if(file_exists('./up/'.$saveTo)) {
                return $msgArr;
            }
        }
        return false;
    }
    /**
     * Image prompt handler
     * 
     * Generates images based on text prompts using OpenAI's image generation API.
     * Saves the generated image and returns the file information.
     * 
     * @param array $msgArr Message array containing image prompt
     * @param bool $stream Whether to stream the response
     * @return array Message array with image file information
     */
    public static function picPrompt($msgArr, $stream = false): array {
        $usrArr = Central::getUsrById($msgArr['BUSERID']);

        if(substr($msgArr['BTEXT'], 0, 1) == '/') {
            $picPrompt = substr($msgArr['BTEXT'], strpos($msgArr['BTEXT'], " "));
        } else {
            $picPrompt = $msgArr['BTEXT'];
        }

        $picPrompt = trim($picPrompt);

        $client = OpenAI::client(self::$key);

        $myModel = $GLOBALS["AI_TEXT2PIC"]["MODEL"];

        if($stream) { 
            $update = [
                'msgId' => $msgArr["BID"],
                'status' => 'pre_processing',
                'message' => 'Generation started... '
            ];
            Frontend::printToStream($update);
        }

        $response = $client->images()->create([
            'model' => $myModel,
            'prompt' => $picPrompt,
            'n' => 1,
            'size' => '1024x1024'
        ]);

        //error_log(print_r($response->toArray(), true));

        foreach ($response->data as $data) {
            $imgBuffer = $data->b64_json; // 'https://oaidalleapiprodscus.blob.core.windows.net/private/...'
        }
        // decode the base64 image
        // Remove the data URI scheme if present
        if (strpos($imgBuffer, 'data:image') === 0) {
            $imgBuffer = preg_replace('/^data:image\/\w+;base64,/', '', $imgBuffer);
        }

        // Decode base64 to binary
        $imageData = base64_decode($imgBuffer);
        // should be dynamic!
        $fileType = 'png';

        // save file to
        // hive: $fileUrl = $arrRes['output'][0]['url'];
        $fileOutput = substr($usrArr["BPROVIDERID"], -5, 3) . '/' . substr($usrArr["BPROVIDERID"], -2, 2) . '/' . date("Ym");
        $filePath = $fileOutput . '/oai_' . $msgArr['BID'] . '.' . $fileType;
        // Create the public/up directory path (DocumentRoot is public)
        $publicUpDir = __DIR__ . '/../up/' . $fileOutput;
        if (!is_dir($publicUpDir)) {
            mkdir($publicUpDir, 0755, true);
        }
        $msgArr['BFILE'] = 1;
        $msgArr['BFILETEXT'] = 'OK: OpenAI Image'; //json_encode($msgArr['input']);
        $msgArr['BFILEPATH'] = $filePath;
        $msgArr['BFILETYPE'] = $fileType;    

        // Write image file and verify success
        $absoluteFile = __DIR__ . '/../up/' . $filePath;
        $writeOk = @file_put_contents($absoluteFile, $imageData);
        if ($writeOk === false || !file_exists($absoluteFile)) {
            $msgArr['BFILE'] = 0;
            $msgArr['BFILEPATH'] = '';
            $msgArr['BFILETEXT'] = 'Error: could not write image file';
        } else {
            @chmod($absoluteFile, 0644);
        }
        // return the message array
        return $msgArr;
    }

    /**
     * Image content analyzer
     * 
     * Analyzes image content and generates a description using OpenAI's vision API.
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

        $client = self::$client;

        // Use the global prompt if available, otherwise use default
        $imgPrompt = isset($GLOBALS["AI_PIC2TEXT"]["PROMPT"]) 
        ? $GLOBALS["AI_PIC2TEXT"]["PROMPT"] 
        : 'Describe this image in detail. Be comprehensive and accurate.';
    
        try {
            $response = $client->images()->analyze([
                'model' => $GLOBALS["AI_PIC2TEXT"]["MODEL"],
                'image' => fopen($imagePath, 'r'),
                'prompt' => $imgPrompt
            ]);
            $arrMessage['BFILETEXT'] = $response['choices'][0]['message']['content'];
        } catch (Exception $err) {
            $arrMessage['BFILETEXT'] = "*API Image Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        return $arrMessage;
    }
    /**
     * Audio to text converter
     * 
     * Transcribes MP3 audio files to text using OpenAI's Whisper API.
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
                'model' => $GLOBALS["AI_SOUND2TEXT"]["MODEL"],
                'response_format' => 'text'
            ]);
        
            $fullText = $transcription;

        } catch (Exception $e) {
            $fullText = "Error: " . $e->getMessage();
        }
        return $fullText;
    }
    /**
     * Text translator
     * 
     * Translates text content to a specified language using OpenAI's translation capabilities.
     * Supports multiple languages and handles translation errors gracefully.
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
        $arrMessages = [
            ['role' => 'system', 'content' => $tPrompt]
        ];

        $arrMessages[] = ['role' => 'user', 'content' => $qTerm];

        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
        try {
            $chat = $client->chat()->create([
                'model' => $myModel,
                'messages' => $arrMessages
            ]);
        } catch (Exception $err) {
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
        
        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
        try {
            $chat = $client->chat()->create([
                'model' => $myModel,
                'messages' => $arrMessages
            ]);
        } catch (Exception $err) {
            return "*APIwelcome Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
        }
        return $chat['choices'][0]['message']['content'];
    }

    /**
     * Create Office file using OpenAI Responses API
     * 
     * Creates PowerPoint, Word, or Excel files using OpenAI's Responses API with code interpreter.
     * Follows the same logic as the openai-ppt.sh script but implemented in PHP.
     * 
     * @param string $creatorPrompt The prompt describing what file to create
     * @param array $usrArr User array containing user information
     * @param bool $stream Whether to stream progress updates
     * @return array Result array with file information or error message
     */
    public static function createOfficeFile($msgArr, $usrArr, $stream = false): array {
        $creatorPrompt = $msgArr['BTEXT'];
        $result = [
            'success' => false,
            'error' => '',
            'filePath' => '',
            'fileName' => '',
            'fileType' => ''
        ];

        // Check if we're on localhost for debug logging
        $isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
                      (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']));

        if ($isLocalhost) {
            if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Starting with prompt: " . substr($creatorPrompt, 0, 100) . "...");
        }

        // Initialize OpenAI client
        if (!self::init()) {
            $result['error'] = 'Failed to initialize OpenAI client';
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Failed to initialize OpenAI client");
            }
            return $result;
        }

        $apiKey = ApiKeys::getOpenAI();
        if (!$apiKey) {
            $result['error'] = 'OpenAI API key not found';
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: OpenAI API key not found");
            }
            return $result;
        }

        if ($isLocalhost) {
            if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: API key found, length: " . strlen($apiKey));
        }

        if ($stream) {
            $update = [
                'msgId' => $msgArr['BID'],
                'status' => 'pre_processing',
                'message' => 'Starting Office file creation... '
            ];
            Frontend::printToStream($update);
        }

        try {
            // Step 1: Create the response request
            $responseData = [
                'model' => $GLOBALS["AI_CHAT"]["MODEL"],
                'tools' => [
                    [
                        'type' => 'code_interpreter',
                        'container' => [
                            'type' => 'auto'
                        ]
                    ]
                ],
                'input' => [
                    [
                        'role' => 'user',
                        'content' => $creatorPrompt
                    ]
                ],
                'store' => true
            ];

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Request data prepared: " . json_encode($responseData,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ];

            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'Sending request to OpenAI... '
                ];
                Frontend::printToStream($update);
            }

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Making initial request to OpenAI API...");
            }

            // Make the initial request using Curler
            $responseJson = Curler::callJson(
                'https://api.openai.com/v1/responses',
                $headers,
                $responseData
            );

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Initial response received: " . json_encode($responseJson));
            }

            if (!isset($responseJson['id'])) {
                $result['error'] = 'Failed to create response: ' . json_encode($responseJson);
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: No response ID in response: " . json_encode($responseJson));
                }
                return $result;
            }

            $responseId = $responseJson['id'];

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Response ID obtained: " . $responseId);
            }

            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'Response queued: ' . $responseId . ' '
                ];
                Frontend::printToStream($update);
            }

            // Step 2: Poll until the response is completed
            $maxAttempts = 60; // 5 minutes with 5-second intervals
            $attempts = 0;
            
            while ($attempts < $maxAttempts) {
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Polling attempt " . ($attempts + 1) . " for response ID: " . $responseId);
                }

                $statusResponse = Curler::callJson(
                    'https://api.openai.com/v1/responses/' . $responseId,
                    ['Authorization: Bearer ' . $apiKey]
                );

                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Status response: " . json_encode($statusResponse));
                }

                if (!isset($statusResponse['status'])) {
                    $result['error'] = 'Failed to get response status';
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: No status in response: " . json_encode($statusResponse));
                    }
                    return $result;
                }

                $status = $statusResponse['status'];

                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Current status: " . $status);
                }

                if ($stream) {
                    $update = [
                        'msgId' => $msgArr['BID'],
                        'status' => 'pre_processing',
                        'message' => ($attempts+1) .' '
                    ];
                    Frontend::printToStream($update);
                }

                if ($status === 'completed') {
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Response completed successfully");
                    }
                    break;
                }

                if ($status === 'failed' || $status === 'cancelled') {
                    $result['error'] = 'Generation failed with status: ' . $status . ' ';
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Generation failed with status: " . $status);
                    }
                    return $result;
                }

                sleep(5);
                $attempts++;
            }

            if ($attempts >= $maxAttempts) {
                $result['error'] = 'Generation timed out after 5 minutes ';
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Generation timed out after " . $maxAttempts . " attempts");
                }
                return $result;
            }

            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'Generation completed, extracting file... '
                ];
                Frontend::printToStream($update);
            }

            // Step 3: Extract file information from the completed response
            $containerId = null;
            $fileId = null;
            $textContent = null;

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Extracting file IDs from completed response");
            }

            // Search for container_file_citation in the response
            self::extractFileIds($statusResponse, $containerId, $fileId);

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Extracted containerId: " . ($containerId ?? 'null') . ", fileId: " . ($fileId ?? 'null'));
            }

            // If no file found, try to extract text content
            if (!$containerId || !$fileId) {
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: No file found, checking for text content");
                }
                
                $textContent = self::extractTextContent($statusResponse);
                
                if ($textContent) {
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Found text content, length: " . strlen($textContent));
                    }
                    
                    // Return the text content instead of a file
                    $result['success'] = true;
                    $result['textContent'] = $textContent;
                    $result['filePath'] = '';
                    $result['fileName'] = '';
                    $result['fileType'] = 'text';
                    
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Returning text content instead of file");
                    }
                    
                    return $result;
                } else {
                    $result['error'] = 'No file or text content found in the response';
                    if ($isLocalhost) {
                        if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: No file or text content found in response");
                    }
                    return $result;
                }
            }

            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'Found file: container=' . $containerId . ', file=' . $fileId . ' '
                ];
                Frontend::printToStream($update);
            }

            // Step 4: Download the file
            $downloadUrl = 'https://api.openai.com/v1/containers/' . $containerId . '/files/' . $fileId . '/content';
            
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Downloading file from URL: " . $downloadUrl);
            }

            $downloadHeaders = [
                'Authorization: Bearer ' . $apiKey
            ];

            // Use Curler to download the file
            $fileContent = self::downloadFile($downloadUrl, $downloadHeaders);
            
            if ($fileContent === false) {
                $result['error'] = 'Failed to download file ';
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Failed to download file from URL: " . $downloadUrl);
                }
                return $result;
            }

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: File downloaded successfully, size: " . strlen($fileContent) . " bytes");
            }

            // Step 5: Save the file with dynamic path structure
            $fileExtension = self::determineFileExtension($creatorPrompt);
            
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Determined file extension: " . $fileExtension);
            }

            // Create dynamic file path similar to picPrompt method
            $fileOutput = substr($usrArr["BPROVIDERID"], -5, 3) . '/' . substr($usrArr["BPROVIDERID"], -2, 2) . '/' . date("Ym");
            $fileName = 'oai_' . time() . '.' . $fileExtension;
            $filePath = $fileOutput . '/' . $fileName;
            
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: File path: up/" . $filePath);
            }

            // Create the directory if it doesn't exist
            if(!is_dir('up/'.$fileOutput)) {
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Creating directory: up/" . $fileOutput);
                }
                mkdir('up/'.$fileOutput, 0777, true);
            }

            if (file_put_contents('up/'.$filePath, $fileContent) === false) {
                $result['error'] = 'Failed to save file ';
                if ($isLocalhost) {
                    if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Failed to save file to: up/" . $filePath);
                }
                return $result;
            }

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: File saved successfully to: up/" . $filePath);
            }

            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'File saved successfully: ' . $fileName . ' '
                ];
                Frontend::printToStream($update);
            }

            $result['success'] = true;
            $result['filePath'] = $filePath;
            $result['fileName'] = $fileName;
            $result['fileType'] = $fileExtension;

            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Method completed successfully");
            }

            return $result;

        } catch (Exception $e) {
            $result['error'] = 'Exception: ' . $e->getMessage();
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Exception caught: " . $e->getMessage());
                if($GLOBALS["debug"]) error_log("DEBUG createOfficeFile: Exception trace: " . $e->getTraceAsString());
            }
            return $result;
        }
    }

    /**
     * Extract container and file IDs from response
     * 
     * @param array $response The response array
     * @param string &$containerId Reference to store container ID
     * @param string &$fileId Reference to store file ID
     */
    private static function extractFileIds($response, &$containerId, &$fileId) {
        // Recursively search for container_file_citation objects
        self::searchForFileCitation($response, $containerId, $fileId);
    }

    /**
     * Recursively search for file citation in response
     * 
     * @param mixed $data The data to search
     * @param string &$containerId Reference to store container ID
     * @param string &$fileId Reference to store file ID
     */
    private static function searchForFileCitation($data, &$containerId, &$fileId) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'type' && $value === 'container_file_citation') {
                    if (isset($data['container_id'])) {
                        $containerId = $data['container_id'];
                    }
                    if (isset($data['file_id'])) {
                        $fileId = $data['file_id'];
                    }
                    return;
                }
                if (is_array($value) || is_object($value)) {
                    self::searchForFileCitation($value, $containerId, $fileId);
                }
            }
        } elseif (is_object($data)) {
            $dataArray = (array) $data;
            self::searchForFileCitation($dataArray, $containerId, $fileId);
        }
    }

    /**
     * Download file content
     * 
     * @param string $url The download URL
     * @param array $headers The headers to use
     * @return string|false The file content or false on failure
     */
    private static function downloadFile($url, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $content === false) {
            return false;
        }

        return $content;
    }

    /**
     * Determine file extension based on prompt
     * 
     * @param string $prompt The creation prompt
     * @return string The file extension
     */
    private static function determineFileExtension($prompt) {
        $prompt = strtolower($prompt);
        
        if (strpos($prompt, 'powerpoint') !== false || strpos($prompt, 'ppt') !== false) {
            return 'pptx';
        }
        if (strpos($prompt, 'excel') !== false || strpos($prompt, 'spreadsheet') !== false) {
            return 'xlsx';
        }
        if (strpos($prompt, 'word') !== false || strpos($prompt, 'document') !== false) {
            return 'docx';
        }
        
        // Default to pptx for office files
        return 'pptx';
    }

    /**
     * Extract text content from response
     * 
     * @param array $response The response array
     * @return string|false The extracted text content or false on failure
     */
    private static function extractTextContent($response) {
        // Check if we're on localhost for debug logging
        $isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
                      (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']));

        if ($isLocalhost) {
            if($GLOBALS["debug"]) error_log("DEBUG extractTextContent: Starting extraction from response");
        }

        // Try to extract from the output array structure
        if (isset($response['output']) && is_array($response['output'])) {
            foreach ($response['output'] as $output) {
                if (isset($output['type']) && $output['type'] === 'message' && 
                    isset($output['content']) && is_array($output['content'])) {
                    
                    foreach ($output['content'] as $content) {
                        if (isset($content['type']) && $content['type'] === 'output_text' && 
                            isset($content['text'])) {
                            
                            if ($isLocalhost) {
                                if($GLOBALS["debug"]) error_log("DEBUG extractTextContent: Found text content in output_text");
                            }
                            
                            return $content['text'];
                        }
                    }
                }
            }
        }

        // Fallback: try to extract from choices structure (older API format)
        if (isset($response['choices'][0]['message']['content'])) {
            if ($isLocalhost) {
                if($GLOBALS["debug"]) error_log("DEBUG extractTextContent: Found text content in choices structure");
            }
            return $response['choices'][0]['message']['content'];
        }

        if ($isLocalhost) {
            if($GLOBALS["debug"]) error_log("DEBUG extractTextContent: No text content found in response");
        }
        
        return false;
    }

    /*
    / Method(s) to upload a file and analyze it in one fell swoop
    / The following code shall take an uploaded file from the message array
    / and upload it to OpenAI via the API. It takes the uploaded file ID
    / and adds it to the message for OpenAI to analyze.
    /
    */

    /**
     * Analyze uploaded file using OpenAI
     * 
     * Uploads a file to OpenAI and analyzes it using the Responses API.
     * Supports office documents, PDFs, code files, and other document types.
     * 
     * @param array $msgArr Message array containing file information
     * @param bool $stream Whether to stream progress updates
     * @return array|string|bool Analysis result or error message
     */
    public static function analyzeFile($msgArr, $stream = false): array|string|bool {
        // Check if file exists and is actually a file
        $filePath = __DIR__ . '/../up/' . $msgArr['BFILEPATH'];

        $errorStop = '';       
        // Get absolute path to avoid any path issues
        $absolutePath = realpath($filePath);
        if (!$absolutePath) {
            $errorStop .= "*API File Error - Cannot resolve file path: " . $msgArr['BFILEPATH'];
        }

        if ($errorStop != '') {
            if($GLOBALS["debug"]) error_log($errorStop);
            if ($stream) {
                $update = [
                    'msgId' => $msgArr['BID'],
                    'status' => 'pre_processing',
                    'message' => 'Error: ' . $errorStop.' '
                ];
                Frontend::printToStream($errorStop);
            }
            return $errorStop;
        }
        if($GLOBALS["debug"]) error_log($absolutePath);

        $client = self::$client;

    }    

    /**
     * Simple prompt execution
     * 
     * Executes a simple prompt with system and user messages, returning a structured response.
     * This provides a clean interface for basic AI interactions.
     * 
     * @param string $systemPrompt The system prompt/instruction
     * @param string $userPrompt The user's input/prompt
     * @return array Response array with success status and result/error
     */
    public static function simplePrompt($systemPrompt, $userPrompt): array {
        $client = self::$client;
        
        $arrMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // which model on OpenAI?
        $myModel = $GLOBALS["AI_CHAT"]["MODEL"];
        
        try {
            $chat = $client->chat()->create([
                'model' => $myModel,
                'messages' => $arrMessages
            ]);
            
            $result = $chat['choices'][0]['message']['content'];
            
            return [
                'success' => true,
                'summary' => $result
            ];
            
        } catch (Exception $err) {
            return [
                'success' => false,
                'summary' => "*API Simple Prompt Error - OpenAI error: * " . $err->getMessage()
            ];
        }
    }
}

$test = AIOpenAi::init();
