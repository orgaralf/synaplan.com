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
                self::$msgArr['BFILETEXT'] = $translatedText['BFILETEXT']."\n\n";
                self::$msgArr['BFILETEXT'] .= "Translated from this:\n".self::$msgArr['BFILETEXT'];
                $langSQL = "UPDATE BMESSAGES SET BFILETEXT = '".db::EscString(self::$msgArr['BFILETEXT'])."' WHERE BID = ".self::$msgArr['BID'];
                db::Query($langSQL);

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
        if(substr(self::$msgArr['BTEXT'], 0, 1) != '/') {
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
            if(isset($metaArr['BVALUE']) AND $metaArr['BVALUE'] != 'tools:sort') {
                $promptId = $metaArr['BVALUE'];
                // Override the topic with the selected prompt
                self::$msgArr['BTOPIC'] = $promptId;
                // Update the message in database to reflect the prompt-based topic
                $updateSQL = "update BMESSAGES set BTOPIC = '".db::EscString($promptId)."' where BID = ".intval(self::$msgArr['BID']);
                db::Query($updateSQL);
                // target prompt set previously
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'Target set: '.self::$msgArr['BTOPIC'].' ');
                }

            }
            // ----------------------------------------------------- Standard sorting
            // -----------------------------------------------------
            if($promptId == 'tools:sort') {
                $AIGENERAL = $GLOBALS["AI_SORT"]["SERVICE"];

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

                $answerJson = $AIGENERAL::sortingPrompt($sortingArr, self::$threadArr);

                $answerJsonArr = json_decode($answerJson, true);
                self::$msgArr['BTEXT'] = $answerJsonArr['BTEXT'];
                self::$msgArr['BTOPIC'] = $answerJsonArr['BTOPIC'];
                self::$msgArr['BLANG'] = $answerJsonArr['BLANG'];
                if(self::$stream) {
                    Frontend::statusToStream(self::$msgId, 'pre', 'Topic and language determined: '.self::$msgArr['BTOPIC'].' ('.self::$msgArr['BLANG'].').');
                }
                // add the tools: prefix to the text
                // error_log('BTOPIC: '.self::$msgArr['BTOPIC']);
                if(substr(self::$msgArr['BTOPIC'], 0, 6) == 'tools:') {
                    self::$toolProcessed = true;
                    self::$msgArr['BTEXT'] = str_replace('tools:', '/', self::$msgArr['BTOPIC'])." ".self::$msgArr['BTEXT'];
                }
                // count bytes
                XSControl::countBytes(self::$msgArr, 'SORT', self::$stream);
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

            self::$msgArr = self::$toolAnswer;
            
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

        // Handle new user case
        /*
        if(count(ProcessMethods::$threadArr) < 1) {
            $welcomeText = $AISORT::welcomePrompt(['BCOMMAND' => "/list", 'BLANG' => self::$msgArr['BLANG']]);
        }
        */
        
        // Initialize answer array with file information
        if(self::$msgArr['BFILE']>0) {
            if(self::$msgArr['BFILE']==2) {
                $storedInArr = self::$msgArr;
            }
            $answerJsonArr['BFILE'] = 1;
            $answerJsonArr['BFILETEXT'] = self::$msgArr['BFILETEXT'];
            $answerJsonArr['BUNIXTIMES'] = self::$msgArr['BUNIXTIMES'];
        }

        // -----------------------------------------------------
        $AIGENERAL = $GLOBALS["AI_CHAT"]["SERVICE"];

        // get the tools for the prompt
        // --- check for: aiModel, tool_internet, tool_files, tool_screenshot, tool_transfer
        
        // Ensure BTOPIC is set and valid before calling getPromptDetails
        if (!isset(self::$msgArr['BTOPIC']) || empty(self::$msgArr['BTOPIC'])) {
            self::$msgArr['BTOPIC'] = 'general'; // Default fallback
            //error_log('Warning: BTOPIC was missing or empty, defaulting to "chat"');
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

        foreach($promptDetails['SETTINGS'] as $setting) {
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
                if(isset($answerJsonArr['SEARCH_TERM'])) {
                    $searchArr = Tools::searchWeb($searchArr, $answerJsonArr['SEARCH_TERM']);
                    if(isset($searchArr['BTEXT'])) {
                        self::$msgArr['BTEXT'] .= "\n\n".'Add URLs to your answer from todays WEB SEARCH RESULTS, if you find it helpful:'."\n\n".$searchArr['BTEXT'];
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
                $AIGENERAL = "AI".$modelDetails['BSERVICE'];
            }
        }

        // --------------------------------------------------------------------------------
        // ----------------------------------------------------- now call the topic prompt
        // --------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------
        //error_log('calling '.$AIGENERAL."::topicPrompt");
        // -----------------------------------------------------
        // $answerSorted = AIGoogle::topicPrompt(self::$msgArr, self::$threadArr);

        $defaultPromptArr = ['analyzefile'];
        // ----------------------------------------------------- default or extra?
        if(!in_array(self::$msgArr['BTOPIC'], $defaultPromptArr)) {
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Calling '.$AIGENERAL.'. ');
            }
            $answerSorted = $AIGENERAL::topicPrompt(self::$msgArr, self::$threadArr);

        } else {
            if(self::$stream) {
                Frontend::statusToStream(self::$msgId, 'pre', 'Calling ' . self::$msgArr['BTOPIC'] . '. ');
            }
        }

        // --------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------
        // -----------------------------------------------------
        // post processing of the answer
        //error_log('answerSorted 381: '.print_r($answerSorted, true));
        // Handle preconfigured sorted messages
        if (self::$msgArr['BTOPIC'] === 'mediamaker') {
            // Call specific method for media creation tasks
            $task = $answerSorted['BMEDIA'];
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);

            if($task == 'image') {
                $answerSorted['BTEXT'] = "/pic ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$threadArr);
            }
            if($task == 'video') {
                $answerSorted['BTEXT'] = "/vid  ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$threadArr);
            }
            if($task == 'audio') {
                $answerSorted['BTEXT'] = "/audio ".$answerSorted['BTEXT'];
                $answerSorted = BasicAI::toolPrompt($answerSorted, self::$threadArr);
            }
            // $answerSorted['BTEXT'] = Tools::addMediaToText($answerSorted);
        }
        // **************************************************************************************************
        if (self::$msgArr['BTOPIC'] === 'officemaker') {
            // Call specific method for office document creation tasks
            $task = $answerSorted['BMEDIA'];
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);
            if($task == 'xls' || $task == 'ppt' || $task == 'doc') {
                /*
                $result['success'] = true;
                $result['filePath'] = $filePath;
                $result['fileName'] = $fileName;
                $result['fileType'] = $fileExtension;
                */
                $result = AIOpenAI::createOfficeFile($answerSorted, self::$usrArr, self::$stream);
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
            }
            // $answerSorted['BTEXT'] = Tools::addMediaToText($answerSorted);
        }
        // **************************************************************************************************
        
        if (self::$msgArr['BTOPIC'] === 'analyzefile') {
            $answerSorted = Tools::migrateArray(self::$msgArr, $answerSorted);
            $answerSorted = AIGoogle::analyzeFile($answerSorted, self::$stream);
            if(is_string($answerSorted)) {
                error_log($answerSorted);
                $answerSorted['BTEXT'] = $answerSorted;
            }
            $answerSorted['BFILE'] = 0;
            $answerSorted['BFILEPATH'] = '';
            $answerSorted['BFILETYPE'] = '';
            $answerSorted['BTEXT'] = Tools::processComplexHtml($answerSorted['BFILETEXT']);
            $answerSorted['BFILETEXT'] = '';
        }

        // Handle web search if needed
        if($answerSorted['BFILE'] == 10 && strlen($answerSorted['BFILETEXT']) > 0 AND strlen($answerSorted['BFILETEXT']) < 64) {
            $answerSorted = Tools::searchWeb(self::$msgArr, $answerSorted['BFILETEXT']);
            $answerSorted['BFILE'] = 0;
        }
        
        // do we have found files for this answer?
        if(count($ragArr) > 0) {
            $answerSorted['BTEXT'] .= "\n";
            foreach($ragArr as $rag) {
                if(strlen(basename($rag['BFILEPATH'])) > 0) {
                    $answerSorted['BTEXT'] .= "\n".'* ['.basename($rag['BFILEPATH']).']('.$GLOBALS["baseUrl"].'up/'.$rag['BFILEPATH'].')';
                }
            }
        }
        // -----------------------------------------------------
        // ----------------------------------------------------- DONE
        // -----------------------------------------------------
        self::$msgArr = $answerSorted;
        
        $outText = Tools::addMediaToText($answerSorted);
        // print to stream
        if(self::$stream) {
            Frontend::statusToStream(self::$msgId, 'ai', $outText);
        }

        // Handle audio file generation for WhatsApp
        if(self::$msgArr['BFILE'] == 1 && self::$answerMethod == 'WA' && strlen(self::$msgArr['BTEXT']) > 0 && self::$msgArr['BFILETYPE'] == 'mp3') {
            $soundArr = AIOpenAI::textToSpeech(self::$msgArr, self::$usrArr);
            if(count($soundArr) > 0) {
                self::$msgArr['BFILE'] = 1;
                self::$msgArr['BFILEPATH'] = $soundArr['BFILEPATH'];
                self::$msgArr['BFILETYPE'] = $soundArr['BFILETYPE'];
            }
        }
        
        // Clean up file information for WhatsApp messages without files
        if(self::$answerMethod == 'WA' && self::$msgArr['BFILE'] == 0) {
            self::$msgArr['BFILEPATH'] = '';
            self::$msgArr['BFILETYPE'] = '';    
        }
    }
    
    /**
     * Save the answer to the database
     * 
     * @return int The last inserted ID
     */
    public static function saveAnswerToDB(): int {        
        $aiAnswer = self::$msgArr;
        $aiAnswer['BUNIXTIMES'] = time();
        $aiAnswer['BDATETIME'] = date("YmdHis");
        $aiAnswer['BID'] = 'DEFAULT';
        $aiAnswer['BLANG'] = self::$msgArr['BLANG'];
        $aiAnswer['BDIRECT'] = 'OUT';
        $aiAnswer['BSTATUS'] = '';

        // Process complex HTML in BTEXT before saving
        if (isset($aiAnswer['BTEXT'])) {
            $aiAnswer['BTEXT'] = $aiAnswer['BTEXT'];
        }

        // Prepare database fields and values
        foreach($aiAnswer as $field => $val) {
            $fields[] = $field;
            if($field == 'BID') {
                $values[] = 'DEFAULT';
            } else {
                if(is_numeric($val)) {
                    $values[] = $val;
                } else {
                    if(is_string($val)) {
                        $values[] = "'" . db::EscString($val) . "'";
                    } else {
                        $values[] = 0;
                    }
                }
            }
        }

        // Insert the processed message into the database
        $newSQL = "insert into BMESSAGES (" . implode(",", $fields) . ") values (" . implode(",", $values) . ")";
        $newRes = db::Query($newSQL);
        $aiLastId = db::LastId();

        // count bytes
        $aiAnswer['BID'] = $aiLastId;
        XSControl::countBytes($aiAnswer, 'BOTH', self::$stream);

        return $aiLastId;
    }
}

