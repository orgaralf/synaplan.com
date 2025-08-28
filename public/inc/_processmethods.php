<?php
/**
 * Process Methods Class
 * 
 * Contains methods for processing messages step by step. The aiprocessor.php calls these methods
 * with the message array and other parameters to handle message processing.
 * 
 * @package ProcessMethods
 */

class ProcessMethods {
    /** @var array Message array containing all message data */
    public static $msgArr;
    
    /** @var int Message ID */
    public static $msgId;
    
    /** @var array User array containing user data */
    public static $usrArr;
    
    /** @var array Thread array containing conversation thread */
    public static $threadArr;
    
    /** @var string Answer method type */
    public static $answerMethod;
    
    /** @var array Sorting array for message processing */
    public static $sortArr;
    
    /** @var array Tool answer array */
    public static $toolAnswer;

    /** @var bool Stream flag */
    public static $stream;

    /** @var bool Tool processed flag */
    public static $toolProcessed;

    /** @var array AIdetailArr */
    public static $AIdetailArr = [];

    /**
     * Initialize the message processing
     * 
     * Sets up the message array and related data structures for processing
     * 
     * @param int $msgId The ID of the message to process
     * @param bool $stream Stream flag
     * @return void
     */
    public static function init($msgId, $stream = false) {
        self::$msgId = $msgId;
        self::$msgArr = Central::getMsgById($msgId);
        
        //error_log('msgArr: '.json_encode(self::$msgArr));

        self::$usrArr = Central::getUsrById(self::$msgArr['BUSERID']);
        self::$usrArr["DETAILS"] = json_decode(self::$usrArr["BUSERDETAILS"], true);
        
        self::$threadArr = Central::getThread(self::$msgArr, 1200);
        self::$answerMethod = self::$msgArr['BMESSTYPE'];

        self::$stream = $stream;
        self::$toolProcessed = false;
    }

    /**
     * Handle file translation
     * 
     * Translates file content to English if it's not already in English
     * 
     * @return void
     */
    public static function fileTranslation(): void {
        if(self::$msgArr['BFILE']>0 AND strlen(self::$msgArr['BFILETEXT'])>5 AND self::$msgArr['BLANG'] != 'en' 
            AND strlen(self::$msgArr['BFILETYPE'])>2 AND strlen(self::$msgArr['BFILETYPE'])<5) {
                $translatedText = AIGroq::translateTo(self::$msgArr, self::$msgArr['BLANG'], 'BFILETEXT');
                XSControl::storeAIDetails(self::$msgArr, 'AISERVICE', 'AIGroq', self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODEL', 'Translate', self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODELID', '0', self::$stream);

                self::$msgArr['BFILETEXT'] = $translatedText['BFILETEXT']."\n\n";
                self::$msgArr['BFILETEXT'] .= "Translated from this:\n".self::$msgArr['BFILETEXT'];
                $langSQL = "UPDATE BMESSAGES SET BFILETEXT = '".DB::EscString(self::$msgArr['BFILETEXT'])."' WHERE BID = ".self::$msgArr['BID'];
                DB::Query($langSQL);

                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'File description translated. ');
                }
                
                // Update sorting array with file information
                self::$sortArr['BFILE'] = self::$msgArr['BFILE'];
                self::$sortArr['BFILETEXT'] = self::$msgArr['BFILETEXT'];
                self::$sortArr['BFILETYPE'] = self::$msgArr['BFILETYPE'];
                self::$sortArr['BFILEPATH'] = self::$msgArr['BFILEPATH'];
            }
    }

    /**
     * Sort and process the message
     * 
     * Determines the appropriate topic and processes the message accordingly
     * 
     * @return void
     */
    public static function sortMessage(): void {
        // -----------------------------------------------------
        // -----------------------------------------------------
        // Detect tool commands using safer method
        $isToolCommand = isset(self::$msgArr['BTEXT'][0]) && self::$msgArr['BTEXT'][0] === '/';
        
        if(!$isToolCommand) {
            // -----------------------------------------------------
            // it is NOT a tool request
            // -----------------------------------------------------
            // -----------------------------------------------------
            // Check for a set prompt ID, if it is set, use it to override the topic
            // -----------------------------------------------------
            $promptId = 'tools:sort';
            $metaSQL = "select BVALUE from BMESSAGEMETA where BMESSID = ".self::$msgArr['BID']." and BTOKEN = 'PROMPTID'";
            $metaRes = DB::Query($metaSQL);
            $metaArr = DB::FetchArr($metaRes);

            // ----------------------------------------------------- override the topic with the selected prompt
            if($metaArr && is_array($metaArr) && isset($metaArr['BVALUE']) AND strlen($metaArr['BVALUE'])>1 AND $metaArr['BVALUE'] != 'tools:sort') {
                $promptId = $metaArr['BVALUE'];
                // Override the topic with the selected prompt
                self::$msgArr['BTOPIC'] = $promptId;
                // Update the message in database to reflect the prompt-based topic
                $updateSQL = "update BMESSAGES set BTOPIC = '".DB::EscString($promptId)."' where BID = ".intval(self::$msgArr['BID']);
                DB::Query($updateSQL);
                // target prompt set previously
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'Target set: '.self::$msgArr['BTOPIC'].' ');
                }

            }

            // ----------------------------------------------------- Standard sorting
            // -----------------------------------------------------
            if($promptId == 'tools:sort') {
                $AIGENERAL = $GLOBALS["AI_SORT"]["SERVICE"];
                $AIGENERALmodel = $GLOBALS["AI_SORT"]["MODEL"];
                $AIGENERALmodelId = $GLOBALS["AI_SORT"]["MODELID"];

                // -----------------------------------------------------
                /*
                {
                    "BDATETIME": "20250314182858",
                    "BFILEPATH": "123/4321/soundfile.mp3",
                    "BTOPIC": "",
                    "BLANG": "en",
                    "BTEXT": "Please help me to translate this message to Spanish.",
                    "BFILETEXT": "Hello, this text was extracted from the sound file."
                }
                */
                $sortingArr = [];
                $sortingArr["BDATETIME"]=self::$msgArr['BDATETIME'] ?? date("YmdHis");
                $sortingArr["BFILEPATH"]=self::$msgArr['BFILEPATH'];
                $sortingArr["BTOPIC"]=self::$msgArr['BTOPIC'];
                $sortingArr["BLANG"]=self::$msgArr['BLANG'];
                $sortingArr["BTEXT"]=self::$msgArr['BTEXT'];
                $sortingArr["BFILETEXT"]=self::$msgArr['BFILETEXT'];

                try {
                    $answerJson = $AIGENERAL::sortingPrompt($sortingArr, self::$threadArr);
                } catch (Exception $err) {
                    if($GLOBALS["debug"]) error_log($err->getMessage());
                    $answerJson = 'Error: '.$err->getMessage();
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'ai', $answerJson);
                    }
                }


                $answerJsonArr = json_decode($answerJson, true);

                // Unified fallback if sorting failed or returned invalid structure
                $fallbackUsed = false;
                if (!is_array($answerJsonArr)) {
                    $fallbackUsed = true;
                    if($GLOBALS["debug"]) error_log("Sorting failed or returned invalid JSON: ".$answerJson);
                    $answerJsonArr = [];
                }

                // Preserve original BTEXT if not provided by sorter
                if (!isset($answerJsonArr['BTEXT']) || $answerJsonArr['BTEXT'] === null) {
                    $answerJsonArr['BTEXT'] = self::$msgArr['BTEXT'] ?? '';
                }

                // Ensure topic and language defaults
                if (!isset($answerJsonArr['BTOPIC']) || !is_string($answerJsonArr['BTOPIC']) || strlen(trim($answerJsonArr['BTOPIC'])) === 0) {
                    $answerJsonArr['BTOPIC'] = 'general';
                    $fallbackUsed = true;
                }
                if (!isset($answerJsonArr['BLANG']) || !is_string($answerJsonArr['BLANG']) || strlen(trim($answerJsonArr['BLANG'])) === 0) {
                    $answerJsonArr['BLANG'] = 'en';
                    $fallbackUsed = true;
                }

                // Apply resolved values
                self::$msgArr['BTEXT'] = $answerJsonArr['BTEXT'];
                self::$msgArr['BTOPIC'] = $answerJsonArr['BTOPIC'];
                self::$msgArr['BLANG'] = $answerJsonArr['BLANG'];

                // Notify via stream if we had to fallback
                if ($fallbackUsed && self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'Sorting unavailable. Defaulted to general (en). ');
                }

                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'Topic and language determined: '.self::$msgArr['BTOPIC'].' ('.self::$msgArr['BLANG'].'). ');
                }
                // Convert tools:xyz to /xyz format and set processed flag
                // error_log('BTOPIC: '.self::$msgArr['BTOPIC']);
                if(substr(self::$msgArr['BTOPIC'], 0, 6) == 'tools:') {
                    self::$toolProcessed = true;
                    self::$msgArr['BTEXT'] = str_replace('tools:', '/', self::$msgArr['BTOPIC'])." ".self::$msgArr['BTEXT'];
                    
                    // Stream once and return to prevent double processing
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'pre', 'Tool target converted: ' . self::$msgArr['BTOPIC'] . '. ');
                    }
                }
                // count bytes
                XSControl::countBytes(self::$msgArr, 'SORT', self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AISERVICE', $AIGENERAL, self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODEL', $AIGENERALmodel, self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODELID', $AIGENERALmodelId, self::$stream);
            }
        }

        // -----------------------------------------------------
        // ----------------------------------------------------- has it added a tool?
        if(substr(self::$msgArr['BTEXT'], 0, 1) == '/') {
            // -----------------------------------------------------
            // it is a tool request
            // -----------------------------------------------------
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Tool requested. ');
            }       

            // ************************* CALL THE TOOL *************
            self::$toolAnswer = BasicAI::toolPrompt(self::$msgArr, self::$stream);
            
            // Set tool processed flag unconditionally after tool execution
            self::$toolProcessed = true;
            
            // Preserve essential fields from original message when merging tool answer
            /*
            $originalTopic = self::$msgArr['BTOPIC'] ?? 'general';
            $originalId = self::$msgArr['BID'] ?? null;
            $originalUserId = self::$msgArr['BUSERID'] ?? null;
            $originalTrackId = self::$msgArr['BTRACKID'] ?? null;
            $originalLang = self::$msgArr['BLANG'] ?? 'en';
            $originalMethod = self::$msgArr['BMESSTYPE'] ?? '';
            $originalDirect = self::$msgArr['BDIRECT'] ?? 'IN';
            */

            self::$msgArr = self::preserveEssentialFields(self::$toolAnswer);
            
            // Restore essential fields if they're missing in the tool answer            
            // For tool responses, ensure BTOPIC is not a tools: prefixed value to prevent duplication
            if (substr(self::$msgArr['BTOPIC'], 0, 1) == '/') {
                // Mark that a tool was processed
                self::$toolProcessed = true;
                self::$msgArr['BTOPIC'] = substr(self::$msgArr['BTOPIC'], 1);
            }
            
            //error_log('msgArr: '. print_r(self::$msgArr, true));
            $outText = Tools::addMediaToText(self::$msgArr);

            // print to stream
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'ai', $outText);
            }
            self::$AIdetailArr['TARGET'] = substr(self::$msgArr['BTEXT'], 0, strpos(self::$msgArr['BTEXT'], ' '));
            XSControl::storeAIDetails(self::$msgArr, 'AITOOL', self::$AIdetailArr['TARGET'], self::$stream);
        }
        // -----------------------------------------------------
        // ----------------------------------------------------- maybe process it
        if(substr(self::$msgArr['BTEXT'], 0, 1) != '/') {
            self::processMessage();
        }
        return;
    }

    /**
     * Process the message based on topic and user intent
     * 
     * Handles message processing according to the determined topic and user's intent
     * 
     * @return void
     */
    // ****************************************************************************************************** 
    public static function processMessage(): void {
        $answerJsonArr = [];
        $answerSorted = [];
        $ragArr = [];
        
        // Initialize answer array with file information
        if(isset(self::$msgArr['BFILE']) && self::$msgArr['BFILE']>0) {
            if(isset(self::$msgArr['BFILE']) && self::$msgArr['BFILE']==2) {
                $storedInArr = self::$msgArr;
            }
            $answerJsonArr['BFILE'] = 1;
            $answerJsonArr['BFILETEXT'] = self::$msgArr['BFILETEXT'];
            $answerJsonArr['BUNIXTIMES'] = self::$msgArr['BUNIXTIMES'];
        }
        // -----------------------------------------------------
        // Check if this is an Again request with forced model
        if (isset($GLOBALS["IS_AGAIN"]) && $GLOBALS["IS_AGAIN"] === true && isset($GLOBALS["FORCED_AI_SERVICE"])) {
            $AIGENERAL = $GLOBALS["FORCED_AI_SERVICE"];
            $AIGENERALmodel = $GLOBALS["FORCED_AI_MODEL"];
            $AIGENERALmodelId = $GLOBALS["FORCED_AI_MODELID"];
            
            // Set the forced model in the global AI_CHAT settings so AI services use it
            $GLOBALS["AI_CHAT"]["MODEL"] = $AIGENERALmodel;
            $GLOBALS["AI_CHAT"]["MODELID"] = $AIGENERALmodelId;
            $GLOBALS["AI_CHAT"]["SERVICE"] = $AIGENERAL;
        } else {
            $AIGENERAL = $GLOBALS["AI_CHAT"]["SERVICE"];
            $AIGENERALmodel = $GLOBALS["AI_CHAT"]["MODEL"];
            $AIGENERALmodelId = $GLOBALS["AI_CHAT"]["MODELID"];
        }

        // get the tools for the prompt
        // --- check for: aiModel, tool_internet, tool_files, tool_screenshot, tool_transfer
        
        // Ensure BTOPIC is set and valid before calling getPromptDetails
        if (!isset(self::$msgArr['BTOPIC']) || empty(self::$msgArr['BTOPIC'])) {
            self::$msgArr['BTOPIC'] = 'general'; // Default fallback
            //error_log('Warning: BTOPIC was missing or empty, defaulting to "general"');
        }
        
        // For tool-generated responses, skip prompt processing and just return the response
        if(self::$toolProcessed) {
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'ai', Tools::addMediaToText(self::$msgArr['BTEXT']));
            }
            return;
        }
        
        $promptDetails = BasicAI::getPromptDetails(self::$msgArr['BTOPIC']);
        $promptSettings = [];

        // Ensure SETTINGS exists and is an array before iterating
        if (isset($promptDetails['SETTINGS']) && is_array($promptDetails['SETTINGS'])) {
            foreach($promptDetails['SETTINGS'] as $setting) {
                // Skip if setting is not an array
                if (!is_array($setting) || !isset($setting['BTOKEN']) || !isset($setting['BVALUE'])) {
                    continue;
                }
                
                $promptSettings[$setting['BTOKEN']] = $setting['BVALUE'];
                //error_log('setting: '.$setting['BTOKEN'].' = '.$setting['BVALUE']);

                // ------------------------------------------------
                // ------------------------------------------------ tool_internet
                // ------------------------------------------------
                // do internet search before the prompt is executed!
                if($setting['BTOKEN'] == 'tool_internet' AND $setting['BVALUE'] == '1') {
                    $searchArr = self::$msgArr;
                    $searchArr['BTOPIC'] = "tools:search";
                    $answerJsonArr = $AIGENERAL::topicPrompt($searchArr, self::$threadArr);
                    
                    // Check if AI returned an error string instead of array
                    if (is_string($answerJsonArr)) {
                        // Skip internet search on error
                        if(self::$stream) {
                            Frontend::statusToStream(self::$msgId, 'pre', 'Internet search skipped due to error. ');
                        }
                    } else if(isset($answerJsonArr['SEARCH_TERM'])) {
                        $searchArr = Tools::searchWeb($searchArr, $answerJsonArr['SEARCH_TERM']);
                        if(isset($searchArr['BTEXT'])) {
                            self::$msgArr['BTEXT'] .= "\n\n\n---\n\n\n".$searchArr['BTEXT'];
                        }
                        if(self::$stream) {
                            Frontend::statusToStream(self::$msgId, 'pre', 'Web search '.$answerJsonArr['SEARCH_TERM'].". ");
                        }
                    }
                }
                
                // ------------------------------------------------
                // ------------------------------------------------ tool_files
                // ------------------------------------------------
                // do file search before the prompt is executed!
                // the file contents get added to the threadArr
                if($setting['BTOKEN'] == 'tool_files' AND $setting['BVALUE'] == '1') {
                    $searchArr = self::$msgArr;
                    $ragArr = Tools::searchRAG($searchArr);
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'pre', 'RAG Search: '.count($ragArr).' files... ');
                    }
                    self::$threadArr = array_merge(self::$threadArr, $ragArr);
                }

                // ------------------------------------------------
                // ------------------------------------------------ aiModel selection
                // ------------------------------------------------
                if($setting['BTOKEN'] == 'aiModel' AND intval($setting['BVALUE']) > 0) {
                    $modelDetails = BasicAI::getModelDetails(intval($setting['BVALUE']));
                    // Check if model details were found
                    if (!empty($modelDetails) && is_array($modelDetails) && isset($modelDetails['BSERVICE'])) {
                        $AIGENERAL = "AI".$modelDetails['BSERVICE'];
                    }
                }
            }
        }

        // **************************************************************************************************
        // ----------------------------------------------------- now call the topic prompt
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        // error_log('calling '.$AIGENERAL."::topicPrompt");
        // $answerSorted = AIGoogle::topicPrompt(self::$msgArr, self::$threadArr);

        // run exceptions for specific topics
        $defaultPromptArr = ['analyzefile','mediamaker'];
        // check of the the a call was done
        $previousCall = false;
        // ----------------------------------------------------- default or extra?
        if(!in_array(self::$msgArr['BTOPIC'], $defaultPromptArr)) {
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Calling standard '.$AIGENERAL.'. ');
            }
            $answerSorted = $AIGENERAL::topicPrompt(self::$msgArr, self::$threadArr, self::$stream);
            
            // Check if AI returned an error string instead of array
            if (is_string($answerSorted)) {
                // Convert error string to proper array format
                $answerSorted = [
                    'BTEXT' => $answerSorted,
                    'BTOPIC' => self::$msgArr['BTOPIC'],
                    'BLANG' => self::$msgArr['BLANG']
                ];
            }
            
            // Get the actual model used from the AI response
            $usedModel = $GLOBALS["AI_CHAT"]["MODEL"]; // Default fallback
            $usedService = $AIGENERAL; // Default fallback
            
            if (is_array($answerSorted)) {
                if (isset($answerSorted['_USED_MODEL'])) {
                    $usedModel = $answerSorted['_USED_MODEL'];
                    // Remove the internal fields from the response
                    unset($answerSorted['_USED_MODEL']);
                }
                if (isset($answerSorted['_AI_SERVICE'])) {
                    $usedService = $answerSorted['_AI_SERVICE'];
                    // Remove the internal fields from the response
                    unset($answerSorted['_AI_SERVICE']);
                }
            }
            
            XSControl::storeAIDetails(self::$msgArr, 'AISERVICE', $usedService, self::$stream);
            XSControl::storeAIDetails(self::$msgArr, 'AIMODEL', $usedModel, self::$stream);
            $previousCall = true;
        } else {
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Calling extra ' . self::$msgArr['BTOPIC'] . '. ');
            }
        }
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        if (self::$msgArr['BTOPIC'] === 'mediamaker') {
            // Call specific method for media creation tasks
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Modifying prompt with '.$AIGENERAL.'. ');
            }
            $answerSorted = $AIGENERAL::topicPrompt(self::$msgArr, [], false);
            
            // Check if AI returned an error string instead of array for mediamaker
            if (is_string($answerSorted)) {
                // For mediamaker, we need to create a proper response with media info
                // Extract the original BTAG to determine media type
                $originalBtag = isset($GLOBALS["FORCED_AI_BTAG"]) ? $GLOBALS["FORCED_AI_BTAG"] : 'text2pic';
                $mediaType = '';
                switch($originalBtag) {
                    case 'text2pic':
                        $mediaType = 'image';
                        break;
                    case 'text2vid':
                        $mediaType = 'video';
                        break;
                    case 'text2sound':
                        $mediaType = 'audio';
                        break;
                    default:
                        $mediaType = 'image'; // fallback
                }
                
                // Convert error string to proper mediamaker format
                $answerSorted = [
                    'BTEXT' => self::$msgArr['BTEXT'], // Use original prompt text
                    'BTOPIC' => 'mediamaker',
                    'BLANG' => self::$msgArr['BLANG'],
                    'BMEDIA' => $mediaType
                ];
            }
            
            // Get the actual model used from the AI response
            $usedModel = $GLOBALS["AI_CHAT"]["MODEL"]; // Default fallback
            $usedService = $AIGENERAL; // Default fallback
            
            if (is_array($answerSorted)) {
                if (isset($answerSorted['_USED_MODEL'])) {
                    $usedModel = $answerSorted['_USED_MODEL'];
                    // Remove the internal fields from the response
                    unset($answerSorted['_USED_MODEL']);
                }
                if (isset($answerSorted['_AI_SERVICE'])) {
                    $usedService = $answerSorted['_AI_SERVICE'];
                    // Remove the internal fields from the response
                    unset($answerSorted['_AI_SERVICE']);
                }
            }
            
            // Do NOT store AI details here - will be overwritten by actual media generation below
            $previousCall = true;

            // DEBUG: Log the raw response for troubleshooting
            if($GLOBALS["debug"]) error_log('MEDIAMAKER DEBUG - Raw response: ' . json_encode($answerSorted));
            
            // Validate the response structure
            if (!is_array($answerSorted)) {
                if($GLOBALS["debug"]) error_log('MEDIAMAKER ERROR - Response is not an array: ' . gettype($answerSorted));
                
                // Fallback: Try to parse as JSON string if it's a string
                if (is_string($answerSorted)) {
                    if($GLOBALS["debug"]) error_log('MEDIAMAKER DEBUG - Attempting to parse response as JSON string');
                    $parsedResponse = json_decode($answerSorted, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsedResponse)) {
                        $answerSorted = $parsedResponse;
                        if($GLOBALS["debug"]) error_log('MEDIAMAKER DEBUG - Successfully parsed JSON string');
                    } else {
                        if($GLOBALS["debug"]) error_log('MEDIAMAKER ERROR - Failed to parse JSON string: ' . json_last_error_msg());
                        if(self::$stream) {
                            Frontend::statusToStream(self::$msgId, 'ai', 'Error: Invalid response format from media generation service.');
                        }
                        return;
                    }
                } else {
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'ai', 'Error: Invalid response format from media generation service.');
                    }
                    return;
                }
            }
            
            // Check if BMEDIA field exists
            if (!isset($answerSorted['BMEDIA']) || empty($answerSorted['BMEDIA'])) {
                if($GLOBALS["debug"]) error_log('MEDIAMAKER ERROR - BMEDIA field missing or empty: ' . json_encode($answerSorted));
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'ai', 'Error: Could not determine media type (image/video/audio).');
                }
                return;
            }
            
            // Validate BMEDIA value
            $validMediaTypes = ['image', 'video', 'audio'];
            if (!in_array($answerSorted['BMEDIA'], $validMediaTypes)) {
                if($GLOBALS["debug"]) error_log('MEDIAMAKER ERROR - Invalid BMEDIA value: ' . $answerSorted['BMEDIA']);
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'ai', 'Error: Invalid media type specified.');
                }
                return;
            }
            
            // Check if BTEXT field exists
            if (!isset($answerSorted['BTEXT']) || empty($answerSorted['BTEXT'])) {
                if($GLOBALS["debug"]) error_log('MEDIAMAKER ERROR - BTEXT field missing or empty: ' . json_encode($answerSorted));
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'ai', 'Error: No prompt text generated for media creation.');
                }
                return;
            }

            $task = $answerSorted['BMEDIA'];
            $answerText = '';
            if($previousCall) {
                $answerText = $answerSorted['BTEXT']."\n\n";
            }           
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);
            $answerSorted['BTEXT'] = $answerText.$answerSorted['BTEXT'];

            // DEBUG: Log the task and processed text
            if($GLOBALS["debug"]) error_log('MEDIAMAKER DEBUG - Task: ' . $task . ', Text: ' . substr($answerSorted['BTEXT'], 0, 100) . '...');

            // Set BID before calling tool and use correct parameters
            $answerSorted['BID'] = self::$msgId;
            
            if($task == 'image') {
                $answerSorted['BTEXT'] = "/pic ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$stream);
            }
            if($task == 'video') {
                $answerSorted['BTEXT'] = "/vid ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$stream);
            }
            if($task == 'audio') {
                $answerSorted['BTEXT'] = "/audio ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$stream);
            }
            
            // Preserve essential fields and set tool processed flag
            self::$msgArr = self::preserveEssentialFields($answerSorted);
            self::$toolProcessed = true;
            
            // Render and stream the tool result for media generation
            if(self::$stream) {
                $outText = Tools::addMediaToText($answerSorted);
                Frontend::statusToStream(self::$msgId, 'ai', $outText);
                // Mark as already shown to prevent double output
                self::$msgArr['ALREADYSHOWN'] = true;
            }
            
            // Return to prevent double processing - let normal save/stream path handle final message
            return;
            // $answerSorted['BTEXT'] = Tools::addMediaToText($answerSorted);
        }
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        if (self::$msgArr['BTOPIC'] === 'officemaker') {
            // Call specific method for office document creation tasks
            $previousCall = true;
            $task = $answerSorted['BMEDIA'];
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);
            if($task == 'xls' || $task == 'ppt' || $task == 'doc') {
                /*
                $result['success'] = true;
                $result['filePath'] = $filePath;
                $result['fileName'] = $fileName;
                $result['fileType'] = $fileExtension;
                */
                $answerText = '';
                if($previousCall) {
                    $answerText = $answerSorted['BTEXT']."\n\n";
                }                
                $result = AIOpenAI::createOfficeFile($answerSorted, self::$usrArr, self::$stream);
                $answerSorted['BTEXT'] = $answerText.$answerSorted['BTEXT'];
                $feNote = 'No file created';

                if($result['success']) {
                    $answerSorted['BFILE'] = 1;
                    $answerSorted['BFILEPATH'] = $result['filePath'];
                    $answerSorted['BFILETYPE'] = $result['fileType'];
                    if(strlen($result['textContent']) > 0) {
                        $answerSorted['BTEXT'] = Tools::processComplexHtml($result['textContent']);
                    }
                    if(strlen($result['filePath']) > 0) {
                        $feNote = 'File created successfully ';
                    }
                }

                if(self::$stream) {
                    //error_log('result: '.print_r($result, true));
                    Frontend::statusToStream(self::$msgId, 'pre', $feNote);
                }
                XSControl::storeAIDetails(self::$msgArr, 'AISERVICE', 'AIOpenAI', self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODEL', 'CreateOfficeFile', self::$stream);
                XSControl::storeAIDetails(self::$msgArr, 'AIMODELID', '0', self::$stream);
            }
            // $answerSorted['BTEXT'] = Tools::addMediaToText($answerSorted);
        }
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************
        // **************************************************************************************************

        if (isset(self::$msgArr['BTOPIC']) && self::$msgArr['BTOPIC'] === 'analyzefile' && isset(self::$msgArr['BFILE']) && self::$msgArr['BFILE'] == 1 && isset(self::$msgArr['BFILEPATH']) && substr(self::$msgArr['BFILEPATH'], -4) == '.pdf') {
            $previousCall = true;
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);
            $answerText = '';
            if($previousCall) {
                $answerText = $answerSorted['BTEXT']."\n\n";
            }                
            $answerSorted = AIGoogle::analyzeFile($answerSorted, self::$stream);
            $answerSorted['BTEXT'] = $answerText.$answerSorted['BTEXT'];

            if(is_string($answerSorted)) {
                if($GLOBALS["debug"]) error_log($answerSorted);
                $answerSorted['BTEXT'] = $answerSorted;
            }
            $answerSorted['BFILE'] = 0;
            $answerSorted['BFILEPATH'] = '';
            $answerSorted['BFILETYPE'] = '';
            $answerSorted['BTEXT'] = Tools::processComplexHtml($answerSorted['BFILETEXT']);
            $answerSorted['BFILETEXT'] = '';
            XSControl::storeAIDetails(self::$msgArr, 'AISERVICE', 'AIGoogle', self::$stream);
            XSControl::storeAIDetails(self::$msgArr, 'AIMODEL', 'AnalyzeFile', self::$stream);
            XSControl::storeAIDetails(self::$msgArr, 'AIMODELID', '0', self::$stream);
        }

        // **************************************************************************************************
        // **************************************************************************************************
        // Handle web search if needed
        error_log('BFILE: '.print_r($answerSorted, true));

        if (isset($answerSorted['BFILE']) && $answerSorted['BFILE'] == 10 && isset($answerSorted['BFILETEXT']) && strlen($answerSorted['BFILETEXT']) > 0 && strlen($answerSorted['BFILETEXT']) < 64) {
            $answerText = '';
            if($previousCall) {
                $answerText = $answerSorted['BTEXT']."\n\n";
            }    
            $answerSorted = Tools::searchWeb(self::$msgArr, $answerSorted['BFILETEXT']);
            $answerSorted['BTEXT'] = $answerText.$answerSorted['BTEXT'];
            $answerSorted['BFILE'] = 0;
        }

        // **************************************************************************************************
        // **************************************************************************************************        
        // do we have found files for this answer?
        if(count($ragArr) > 0) {
            $previousCall = true;
            foreach($ragArr as $rag) {
                if(strlen(basename($rag['BFILEPATH'])) > 0) {
                    $answerSorted['BTEXT'] .= "\n".'* ['.basename($rag['BFILEPATH']).']('.$GLOBALS["baseUrl"].'up/'.$rag['BFILEPATH'].')';
                }
            }
        }
        // **************************************************************************************************
        // **************************************************************************************************
        // check, if we have called anything
        if(!$previousCall) {
            self::$msgArr['BTOPIC'] = 'general';
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Calling '.$AIGENERAL.'. ');
            }
            $answerSorted = $AIGENERAL::topicPrompt(self::$msgArr, self::$threadArr, self::$stream);
            
            // Check if AI returned an error string instead of array
            if (is_string($answerSorted)) {
                // Convert error string to proper array format
                $answerSorted = [
                    'BTEXT' => $answerSorted,
                    'BTOPIC' => self::$msgArr['BTOPIC'],
                    'BLANG' => self::$msgArr['BLANG']
                ];
            }
        }

        // **************************************************************************************************
        // **************************************************************************************************
        // ----------------------------------------------------- DONE
        // **************************************************************************************************

        self::$msgArr = self::preserveEssentialFields($answerSorted);
        
        $outText = Tools::addMediaToText($answerSorted);
        // print to stream
        if(self::$stream) {
            //error_log('outText: '.$outText);
            if(!isset(self::$msgArr['ALREADYSHOWN'])) {
                Frontend::statusToStream(self::$msgId, 'ai', $outText);
            }
        }

        // **************************************************************************************************
        // **************************************************************************************************        
        // Handle audio file generation for WhatsApp
        if(isset(self::$msgArr['BFILE']) && self::$msgArr['BFILE'] == 1 && isset(self::$answerMethod) && self::$answerMethod == 'WA' && isset(self::$msgArr['BTEXT']) && strlen(self::$msgArr['BTEXT']) > 0 && isset(self::$msgArr['BFILETYPE']) && self::$msgArr['BFILETYPE'] == 'mp3') {
            $soundArr = AIOpenAI::textToSpeech(self::$msgArr, self::$usrArr);
            if(count($soundArr) > 0) {
                self::$msgArr['BFILE'] = 1;
                self::$msgArr['BFILEPATH'] = $soundArr['BFILEPATH'];
                self::$msgArr['BFILETYPE'] = $soundArr['BFILETYPE'];
            }
        }
        
        // **************************************************************************************************
        // **************************************************************************************************        
        // Clean up file information for WhatsApp messages without files
        if(isset(self::$answerMethod) && self::$answerMethod == 'WA' && isset(self::$msgArr['BFILE']) && self::$msgArr['BFILE'] == 0) {
            self::$msgArr['BFILEPATH'] = '';
            self::$msgArr['BFILETYPE'] = '';    
        }

        return;
    }
    
    /**
     * Direct chat generation bypassing sorter (for Again functionality)
     * 
     * @return void
     */
    public static function directChatGeneration(): void {
        // For Again requests, process like a normal message but skip sorter
        // Set topic to general for direct chat
        self::$msgArr['BTOPIC'] = 'general';
        
        if(self::$stream) {
            Frontend::statusToStream(self::$msgId, 'pre', 'Calling standard AI (Again). ');
        }
        
        // Use the same logic as normal processing but skip sorter
        self::processMessage();
        
        return;
    }

    /**
     * BTAG-based dispatch for Again requests
     * Calls the appropriate generator function based on BTAG
     */
    public static function dispatchByBTag(string $btag): void {
        try {
            switch ($btag) {
                case 'text2pic':
                case 'text2vid':
                case 'text2sound':
                    // Use mediamaker flow for media generation - skip sorter for Again requests
                    self::$msgArr['BTOPIC'] = 'mediamaker';
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'pre', "Calling $btag generator (Again). ");
                    }
                    ProcessMethods::processMessage();
                    break;
                
                case 'pic2text':
                case 'sound2text':
                    // Use analyzefile flow for analysis tasks
                    self::$msgArr['BTOPIC'] = 'analyzefile';
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'pre', "Calling $btag analyzer (Again). ");
                    }
                    ProcessMethods::processMessage();
                    break;
                
                case 'chat':
                default:
                    // Use direct chat generation for chat models
                    if(self::$stream) {
                        Frontend::statusToStream(self::$msgId, 'pre', 'Calling chat generator (Again). ');
                    }
                    ProcessMethods::processMessage();
                    break;
            }
        } catch (\Throwable $e) {
            error_log("dispatchByBTag error for BTAG '$btag': " . $e->getMessage());
            throw $e; // Re-throw to be caught by caller
        }
    }
    
    /**
     * Save the answer to the database
     * 
     * @return int The last inserted ID
     */
    public static function saveAnswerToDB() {
        // **************************************************************************************************
        // get the incoming id
        $incomingId = self::$msgArr['BID'];
        // **************************************************************************************************
        // **************************************************************************************************
        $aiAnswer = self::$msgArr;
        $aiAnswer['BUNIXTIMES'] = time();
        $aiAnswer['BDATETIME'] = date("YmdHis");
        $aiAnswer['BID'] = 'DEFAULT';
        $aiAnswer['BLANG'] = self::$msgArr['BLANG'];
        $aiAnswer['BDIRECT'] = 'OUT';
        $aiAnswer['BSTATUS'] = '';

        // Handle anonymous widget messages
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Prepend "WEBWIDGET: " to BTEXT for anonymous widget messages
            if (isset($aiAnswer['BTEXT'])) {
                $aiAnswer['BTEXT'] = "WEBWIDGET: " . $aiAnswer['BTEXT'];
            }
            
            // Use widget owner ID as BUSERID
            $aiAnswer['BUSERID'] = $_SESSION["widget_owner_id"];
            
            // Create unique tracking ID for anonymous session
            if (isset($_SESSION["anonymous_session_id"])) {
                // Convert MD5 hash to numeric value for BTRACKID (bigint)
                $trackingHash = $_SESSION["anonymous_session_id"];
                $numericTrackId = crc32($trackingHash); // Convert to 32-bit integer
                $aiAnswer['BTRACKID'] = $numericTrackId;
            }
        }

        // Process complex HTML in BTEXT before saving
        /*
        if (isset($aiAnswer['BTEXT'])) {
            $aiAnswer['BTEXT'] = $aiAnswer['BTEXT'];
        }
        */

        // Define valid BMESSAGES table columns
        $validColumns = [
            'BID', 'BUSERID', 'BTRACKID', 'BPROVIDX', 'BUNIXTIMES', 
            'BDATETIME', 'BMESSTYPE', 'BFILE', 'BFILEPATH', 'BFILETYPE', 
            'BTOPIC', 'BLANG', 'BTEXT', 'BDIRECT', 'BSTATUS', 'BFILETEXT'
        ];

        // Filter $aiAnswer to only include valid table columns
        $filteredAnswer = [];
        foreach ($validColumns as $column) {
            if (isset($aiAnswer[$column])) {
                $filteredAnswer[$column] = $aiAnswer[$column];
            }
        }

        // Prepare database fields and values
        foreach($filteredAnswer as $field => $val) {
            $fields[] = $field;
            if($field == 'BID') {
                $values[] = 'DEFAULT';
            } else {
                if(is_numeric($val)) {
                    $values[] = $val;
                } else {
                    if(is_string($val)) {
                        $values[] = "'" . DB::EscString($val) . "'";
                    } else {
                        $values[] = 0;
                    }
                }
            }
        }
        
        // Insert the processed message into the database
        $newSQL = "insert into BMESSAGES (" . implode(",", $fields) . ") values (" . implode(",", $values) . ")";
        $newRes = DB::Query($newSQL);
        $aiLastId = DB::LastId();

        // **************************************************************************************************
        // count bytes
        $aiAnswer['BID'] = $aiLastId;
        XSControl::countBytes($aiAnswer, 'ALL', self::$stream);
        // **************************************************************************************************
        // **************************************************************************************************
        XSControl::storeAIDetails($aiAnswer, 'AISYSPROMPT', self::$msgArr['BTOPIC'], self::$stream);

        // For Again requests, use forced AI model information; otherwise use incoming message data
        if (isset($GLOBALS["IS_AGAIN"]) && $GLOBALS["IS_AGAIN"] === true) {
            // Use forced AI model information for Again requests
            if (isset($GLOBALS["FORCED_AI_SERVICE"])) {
                XSControl::storeAIDetails($aiAnswer, 'AISERVICE', $GLOBALS["FORCED_AI_SERVICE"], self::$stream);
            }
            if (isset($GLOBALS["FORCED_AI_MODEL"])) {
                XSControl::storeAIDetails($aiAnswer, 'AIMODEL', $GLOBALS["FORCED_AI_MODEL"], self::$stream);
            }
            if (isset($GLOBALS["FORCED_AI_MODELID"])) {
                $modelId = intval($GLOBALS["FORCED_AI_MODELID"]);
                XSControl::storeAIDetails($aiAnswer, 'AIMODELID', strval($modelId), self::$stream);
                
                if ($modelId > 0) {
                    // Update BPROVIDX with the forced model ID for OUT messages
                    $updateSQL = "UPDATE BMESSAGES SET BPROVIDX = " . $modelId . " WHERE BID = " . $aiLastId;
                    DB::Query($updateSQL);
                    
                    // Store BTAG from final model for the original IN message
                    $modelSQL = "SELECT BTAG FROM BMODELS WHERE BID = " . $modelId . " LIMIT 1";
                    $modelRes = DB::Query($modelSQL);
                    $modelRow = DB::FetchArr($modelRes);
                    if ($modelRow && is_array($modelRow) && !empty($modelRow['BTAG'])) {
                        // Store BTAG for the original IN message (overwrite any existing)
                        $inMessageForBtag = ['BID' => $incomingId];
                        XSControl::storeAIDetails($inMessageForBtag, 'BTAG', $modelRow['BTAG'], self::$stream);
                    }
                } else {
                    // If forced model ID is invalid, leave BPROVIDX empty
                    $updateSQL = "UPDATE BMESSAGES SET BPROVIDX = '' WHERE BID = " . $aiLastId;
                    DB::Query($updateSQL);
                }
            }
            // Store Again flag
            XSControl::storeAIDetails($aiAnswer, 'IS_AGAIN', 'true', self::$stream);
        } else {
            // Fetch AI service and model information from incoming message for regular requests
            $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($incomingId)." AND BTOKEN = 'AISERVICE' ORDER BY BID DESC LIMIT 1";
            $serviceRes = DB::Query($serviceSQL);
            $serviceArr = DB::FetchArr($serviceRes);
            if($serviceArr && is_array($serviceArr)) {
                XSControl::storeAIDetails($aiAnswer, 'AISERVICE', $serviceArr['BVALUE'], self::$stream);
            }
            //
            $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($incomingId)." AND BTOKEN = 'AIMODEL' ORDER BY BID DESC LIMIT 1";
            $modelRes = DB::Query($modelSQL);
            $modelArr = DB::FetchArr($modelRes);
            if($modelArr && is_array($modelArr)) {
                XSControl::storeAIDetails($aiAnswer, 'AIMODEL', $modelArr['BVALUE'], self::$stream);
            }
            
            // Fetch and set AIMODELID to BPROVIDX for OUT messages
            $modelIdSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($incomingId)." AND BTOKEN = 'AIMODELID' ORDER BY BID DESC LIMIT 1";
            $modelIdRes = DB::Query($modelIdSQL);
            $modelIdArr = DB::FetchArr($modelIdRes);
            if($modelIdArr && is_array($modelIdArr)) {
                $modelId = intval($modelIdArr['BVALUE']);
                if ($modelId > 0) {
                    // Update BPROVIDX with the actual model ID for OUT messages
                    $updateSQL = "UPDATE BMESSAGES SET BPROVIDX = " . $modelId . " WHERE BID = " . $aiLastId;
                    DB::Query($updateSQL);
                    
                    XSControl::storeAIDetails($aiAnswer, 'AIMODELID', $modelIdArr['BVALUE'], self::$stream);
                    
                    // Store BTAG from final model for the IN message (overwrite any existing)
                    $modelSQL = "SELECT BTAG FROM BMODELS WHERE BID = " . $modelId . " LIMIT 1";
                    $modelRes = DB::Query($modelSQL);
                    $modelRow = DB::FetchArr($modelRes);
                    if ($modelRow && is_array($modelRow) && !empty($modelRow['BTAG'])) {
                        $inMessageForBtag = ['BID' => $incomingId];
                        XSControl::storeAIDetails($inMessageForBtag, 'BTAG', $modelRow['BTAG'], self::$stream);
                    }
                } else {
                    // If AIMODELID exists but is invalid (0 or negative), leave BPROVIDX empty
                    $updateSQL = "UPDATE BMESSAGES SET BPROVIDX = '' WHERE BID = " . $aiLastId;
                    DB::Query($updateSQL);
                    
                    XSControl::storeAIDetails($aiAnswer, 'AIMODELID', $modelIdArr['BVALUE'], self::$stream);
                }
            } else {
                // If no AIMODELID found, leave BPROVIDX empty for OUT messages
                $updateSQL = "UPDATE BMESSAGES SET BPROVIDX = '' WHERE BID = " . $aiLastId;
                DB::Query($updateSQL);
            }
        }
        // **************************************************************************************************
        // **************************************************************************************************

        return $aiLastId;
    }

    /**
     * Preserve essential fields when replacing message array
     * 
     * Ensures that critical fields like BID are not lost when replacing the message array
     * 
     * @param array $newMsgArr The new message array to use
     * @return array The new message array with essential fields preserved
     */
    private static function preserveEssentialFields($newMsgArr) {
        // Essential fields that should be preserved from the original message
        $essentialFields = ['BID', 'BUSERID', 'BTRACKID', 'BMESSTYPE', 'BDIRECT'];
        
        foreach ($essentialFields as $field) {
            if (isset(self::$msgArr[$field]) && !isset($newMsgArr[$field])) {
                $newMsgArr[$field] = self::$msgArr[$field];
            }
        }
        
        return $newMsgArr;
    }
}

