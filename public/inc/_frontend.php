<?php
/**
 * Frontend Class
 * 
 * Handles frontend-related functionality including user authentication,
 * message handling, and chat streaming.
 * 
 * @package Frontend
 */

Class Frontend {
    /** @var array AIdetailArr */
    public static $AIdetailArr = [];

    /**
     * Set user from web login
     * 
     * Authenticates a user based on their web login and sets up their session
     * 
     * @return bool True if authentication successful, false otherwise
     */
    public static function setUserFromWebLogin(): bool {
        $success = false;
        
        // Get email and password from request
        $email = isset($_REQUEST['email']) ? DB::EscString($_REQUEST['email']) : '';
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        
        // Validate input
        if(strlen($email) > 0 && strlen($password) > 0) {
            // MD5 encrypt the password
            $passwordMd5 = md5($password);
            
            // Query the database for matching user
            $uSQL = "SELECT * FROM BUSER WHERE BMAIL = '".$email."' AND BPW = '".$passwordMd5."'";
            $uRes = DB::Query($uSQL);
            $uArr = DB::FetchArr($uRes);
            
            if($uArr) {
                // User found - set session
                $_SESSION["USERPROFILE"] = $uArr;
                $success = true;
                self::$AIdetailArr["GMAIL"] = substr($email, 0, strpos($email, '@'));
            } else {
                // User not found or wrong password - clear session
                unset($_SESSION["USERPROFILE"]);
                $success = false;
            }
        }
        
        return $success;
    }
    /**
     * Set user from ticket
     * 
     * Authenticates a user based on their ticket ID and sets up their session
     * 
     * @return bool True if authentication successful, false otherwise
     */
    public static function setUserFromTicket() {
        //print_r($_REQUEST);
        $userId = intval($_REQUEST['id']);
        $ticketVal = DB::EscString($_REQUEST['lid']);
        if(strlen($ticketVal) > 3) {
            $uSQL = "SELECT * FROM BUSER WHERE BID = ".$userId." AND BUSERDETAILS like '%:\"".$ticketVal."\"%'";
            $uRes = DB::Query($uSQL);
            $uArr = DB::FetchArr($uRes);
            if($uArr) {
                $_SESSION["USERPROFILE"] = $uArr;
                return true;
            } else {
                unset($_SESSION["USERPROFILE"]);
                return false;
            }
        }
        return false;
    }
    // ------------------------------------------------------------
    /**
     * Get latest chats
     * 
     * Retrieves the most recent chat messages for the logged-in user
     * 
     * @return array Array of chat messages
     */
    public static function getLatestChats($myLimit = 10, $myOrder = "DESC") {
        $chatArr = [];
        
        // Handle anonymous widget sessions
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Use widget owner ID for anonymous sessions
            $userId = $_SESSION["widget_owner_id"];
            
            // For anonymous widget sessions, filter by BTRACKID to get only messages from this session
            if (isset($_SESSION["anonymous_session_id"])) {
                $trackingHash = $_SESSION["anonymous_session_id"];
                $numericTrackId = crc32($trackingHash);
                
                $cSQL = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." AND BTRACKID = ".$numericTrackId." ORDER BY BID DESC LIMIT ".($myLimit);
            } else {
                // Fallback to regular query if no session ID
                $cSQL = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." ORDER BY BID DESC LIMIT ".($myLimit);
            }
        } else {
            // Regular authenticated user sessions
            $userId = $_SESSION["USERPROFILE"]["BID"];
            
            // Get messages with a larger limit to account for potential grouping
            $cSQL = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." ORDER BY BID DESC LIMIT ".($myLimit);
        }
        $cRes = DB::Query($cSQL);
        $allMessages = [];
        while($cArr = DB::FetchArr($cRes)) {
            if(strlen($cArr['BFILETEXT']) > 64) {
                $cArr['BFILETEXT'] = substr($cArr['BFILETEXT'], 0, 64)."...";
            }
            
            // Get file metadata for this message
            $metaSQL = "SELECT * FROM BMESSAGEMETA WHERE BMESSID = ".$cArr['BID']." AND BTOKEN = 'FILECOUNT' ORDER BY BID DESC LIMIT 1";
            $metaRes = DB::Query($metaSQL);
            $metaArr = DB::FetchArr($metaRes);
            if($metaArr) {
                $cArr['FILECOUNT'] = intval($metaArr['BVALUE']);
            } else {
                $cArr['FILECOUNT'] = 0;
            }
            
            $allMessages[] = $cArr;
        }
        $allMessages = array_reverse($allMessages);

        // Simple grouping: group messages with same BTRACKID and BDIRECT within 5 seconds
        $groupedMessages = [];
        $processedIds = [];
        
        foreach($allMessages as $message) {
            if(in_array($message['BID'], $processedIds)) {
                continue;
            }
            
            $trackId = $message['BTRACKID'];
            $direction = $message['BDIRECT'];
            $timestamp = $message['BUNIXTIMES'];
            
            // Find related messages
            $relatedMessages = [];
            $relatedIds = [];
            
            foreach($allMessages as $relatedMessage) {
                if($relatedMessage['BTRACKID'] == $trackId && 
                   $relatedMessage['BDIRECT'] == $direction &&
                   abs($relatedMessage['BUNIXTIMES'] - $timestamp) <= 5 &&
                   !in_array($relatedMessage['BID'], $processedIds)) {
                    
                    $relatedMessages[] = $relatedMessage;
                    $relatedIds[] = $relatedMessage['BID'];
                }
            }
            
            // Mark as processed
            $processedIds = array_merge($processedIds, $relatedIds);
            
            // Use the first message as the base
            $groupedMessage = $relatedMessages[0];
            
            // If multiple messages, update file count
            if(count($relatedMessages) > 1) {
                $fileCount = 0;
                foreach($relatedMessages as $relatedMsg) {
                    if($relatedMsg['BFILE'] > 0 && !empty($relatedMsg['BFILEPATH'])) {
                        $fileCount++;
                    }
                }
                $groupedMessage['FILECOUNT'] = $fileCount;
                $groupedMessage['GROUPED_MESSAGE_IDS'] = $relatedIds;
            }
            
            $groupedMessages[] = $groupedMessage;
        }
        
        // Sort and limit
        if($myOrder == "ASC") {
            $groupedMessages = array_reverse($groupedMessages);
        }
        
        $chatArr = $groupedMessages;
        
        return $chatArr;
    }
    // ****************************************************************************************************** 
    // Get file details for a specific message
    // ****************************************************************************************************** 
    public static function getMessageFiles($messageId) {
        $files = [];
        
        // Handle anonymous widget sessions
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Use widget owner ID for anonymous sessions
            $userId = $_SESSION["widget_owner_id"];
        } else {
            // Regular authenticated user sessions
            $userId = $_SESSION["USERPROFILE"]["BID"];
        }
        
        // First get the original message to find its track ID and timestamp
        $msgSQL = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." AND BID = ".intval($messageId);
        $msgRes = DB::Query($msgSQL);
        $msgArr = DB::FetchArr($msgRes);
        
        if($msgArr) {
            // Get all messages with files that have the same track ID and are within a few seconds
            $timeWindow = 10; // seconds
            $sql = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." 
                    AND BTRACKID = ".$msgArr['BTRACKID']." 
                    AND BFILE > 0 
                    AND ABS(BUNIXTIMES - ".$msgArr['BUNIXTIMES'].") <= ".$timeWindow."
                    ORDER BY BID ASC";
            $res = DB::Query($sql);
            while($fileArr = DB::FetchArr($res)) {
                if(!empty($fileArr['BFILEPATH']) && !empty($fileArr['BFILETYPE'])) {
                    $files[] = [
                        'BID' => $fileArr['BID'],
                        'BFILEPATH' => $fileArr['BFILEPATH'],
                        'BFILETYPE' => $fileArr['BFILETYPE'],
                        'BTEXT' => $fileArr['BTEXT'],
                        'BDATETIME' => $fileArr['BDATETIME']
                    ];
                }
            }
        }
        return $files;
    }
    // ********************************************** SAVE WEB MESSAGES **********************************************
    /**
     * Save web messages
     * 
     * Handles saving of web-based messages and file uploads
     * 
     * @return array Array containing status and message information
     */
    public static function saveWebMessages(): array {
        // return the last inserted ids
        $retArr = ["error" => "", "lastIds" => [], "success" => false];
        $lastInsertsId = [];
        $inMessageArr = [];
        
        // Handle anonymous widget sessions
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Use widget owner ID for anonymous sessions
            $userId = $_SESSION["widget_owner_id"];
            
            // Create unique tracking ID for anonymous session
            if (isset($_SESSION["anonymous_session_id"])) {
                $trackingHash = $_SESSION["anonymous_session_id"];
                $numericTrackId = crc32($trackingHash);
                $inMessageArr['BTRACKID'] = $numericTrackId;
            } else {
                $inMessageArr['BTRACKID'] = (int) (microtime(true) * 1000000);
            }
        } else {
            // Regular authenticated user sessions
            $userId = $_SESSION["USERPROFILE"]["BID"];
            $inMessageArr['BTRACKID'] = (int) (microtime(true) * 1000000);
        }
        
        $fileCount = 0;
        // take the files uploaded into a new array
        $filesArr = [];
        
        $inMessageArr['BUNIXTIMES'] = time();
        $inMessageArr['BDATETIME'] = (string) date("YmdHis");

        // Handle file uploads if any
        if(!empty($_FILES['files'])) {
            foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
                if (!is_uploaded_file($tmpName)) {
                    $retArr['error'] .= "Invalid upload: ".$originalName."\n";
                    continue; // skip invalid upload
                }
                
                $originalName = $_FILES['files']['name'][$i];
                $fileSize = $_FILES['files']['size'][$i];

                if($fileSize > 1024*1024*90) {
                    $retArr['error'] .= "File too large: ".$originalName."\n";
                    continue; // skip too large files
                }

                $fileType = mime_content_type($tmpName);
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                // Use appropriate MIME type checking based on session type
                $mimeTypeAllowed = false;
                if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
                    // Anonymous widget users have restricted file types
                    $mimeTypeAllowed = Central::checkMimeTypesForAnonymous($fileExtension, $fileType);
                } else {
                    // Regular authenticated users have full file type access
                    $mimeTypeAllowed = Central::checkMimeTypes($fileExtension, $fileType);
                }

                if($mimeTypeAllowed) {
                    // Zielpfad 
                    $userRelPath = substr($userId, -5, 3) . '/' . substr($userId, -2, 2) . '/' . date("Ym") . '/';
                    $fullUploadDir = __DIR__ . '/../up/' . $userRelPath;
                    if (!is_dir($fullUploadDir)) {
                        mkdir($fullUploadDir, 0755, true);
                    }

                    //$newFileName = 'up-' . date("YmdHis") . '-' . ($fileCount++) . '.' . $fileExtension;
                    $newFileName = Tools::sysStr($originalName);
                    $targetPath = $fullUploadDir . $newFileName;

                    // Speichern
                    move_uploaded_file($tmpName, $targetPath);
                    $filesArr[] = [
                        'BFILEPATH' => $userRelPath.$newFileName,
                        'BFILETYPE' => $fileExtension,
                        'BFILE' => 1
                    ];
                } else {
                    $retArr['error'] .= "Invalid file type: ".$fileExtension."\n";
                    return $retArr;
                }
            }
        }
        // fill for sorting first
        $inMessageArr['BUSERID'] = $userId;
       
        $cleanPost = Tools::turnURLencodedIntoUTF8($_REQUEST['message']);
        $inMessageArr['BTEXT'] = DB::EscString(trim(strip_tags($cleanPost)));
        // ------------------------------------------------
        $convArr = Central::searchConversation($inMessageArr);
        // ------------------------------------------------
        if(is_array($convArr) AND $convArr['BID'] > 0) {
            $inMessageArr['BTRACKID'] = $convArr['BTRACKID'];
            $inMessageArr['BTOPIC'] = $convArr['BTOPIC'];
            $inMessageArr['BLANG'] = $convArr['BLANG'];
        } else {
            $inMessageArr['BLANG'] = Central::getLanguageByBrowser();
            $inMessageArr['BTOPIC'] = ''; 
        }
        // --
        if(strlen($inMessageArr['BLANG']) != 2) {
            $inMessageArr['BLANG'] = Central::getLanguageByBrowser();
        }
        // --
        $inMessageArr['BID'] = 'DEFAULT';
        $inMessageArr['BPROVIDX'] = session_id();
        $inMessageArr['BMESSTYPE'] = 'WEB';
        $inMessageArr['BFILE'] = 0;
        $inMessageArr['BFILEPATH'] = '';
        $inMessageArr['BFILETYPE'] = '';
        $inMessageArr['BDIRECT'] = 'IN';
        $inMessageArr['BSTATUS'] = 'NEW';
        $inMessageArr['BFILETEXT'] = '';

        // save the message to the database
        // Define the model id to save model to the message

        $filesAttached = count($filesArr);
        // error_log("FILES ATTACHED: ".print_r($filesArr, true));
        // NO FILE ATTACHED
        if($filesAttached == 0) {
            $filesArr[] = [
                'BFILEPATH' => "",
                'BFILETYPE' => "",
                'BFILE' => 0
            ];
        }
        // now loop through the files and save the whole message
        foreach($filesArr as $file) {
            $inMessageArr['BFILEPATH'] = $file['BFILEPATH'];
            $inMessageArr['BFILETYPE'] = $file['BFILETYPE'];
            $inMessageArr['BFILE'] = $file['BFILE'];
            $resArr = Central::handleInMessage($inMessageArr);
            // save the last insert ID on the db connection
            $inMessageArr['BID'] = $resArr['lastId'];
            // also add it to an array for loopings
            $lastInsertsId[] = $resArr['lastId'];
            // new inserts for the meta data
            if($filesAttached > 0) {
                $metaSQL = "insert into BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) values (DEFAULT, ".(0 + $resArr['lastId']).", 'FILECOUNT', '".($filesAttached)."');";
                $metaRes = DB::Query($metaSQL);
            }
            // count bytes
            $inMessageArr['BID'] = $resArr['lastId'];
            XSControl::countBytes($inMessageArr, 'FILE', false);
            // set the prompt id
            $metaRes = Central::handlePromptIdForMessage($inMessageArr);
            
            // Process RAG for anonymous widget users with "WIDGET" group key
            if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true && $file['BFILE'] == 1) {
                $ragFilesArr = [
                    [
                        'BID' => $resArr['lastId'],
                        'BFILEPATH' => $file['BFILEPATH'],
                        'BFILETYPE' => $file['BFILETYPE'],
                        'BTEXT' => 'Widget file: ' . basename($file['BFILEPATH'])
                    ]
                ];
                
                // Process with "WIDGET" group key for anonymous widget users
                $ragResult = Central::processRAGFiles($ragFilesArr, $userId, 'WIDGET', false);
                
                if($GLOBALS["debug"]) {
                    error_log("Anonymous widget RAG processing result: " . print_r($ragResult, true));
                }
            }
        }
        // --
        $retArr['message'] = $inMessageArr['BTEXT'];
        if($filesAttached > 0) {
            $retArr['message'] .= "<br>\n<small>(+ ".($filesAttached)." files)</small>";
        }
        $retArr['time'] = date("Y-m-d H:i:s");
        $retArr['lastIds'] = $lastInsertsId;
        $retArr['success'] = true;
        $retArr['fileCount'] = $filesAttached;
        return $retArr;
    }
    // ********************************************** SAVE RAG FILES **********************************************
    /**
     * Save RAG files with custom group key
     * 
     * Handles saving of RAG files with specific group keys for file manager uploads
     * 
     * @return array Array containing status and processing information
     */
    public static function saveRAGFiles(): array {
        $retArr = ["error" => "", "success" => false, "processedFiles" => []];
        
        // Handle anonymous widget sessions
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Use widget owner ID for anonymous sessions
            $userId = $_SESSION["widget_owner_id"];
        } else {
            // Regular authenticated user sessions
            $userId = $_SESSION["USERPROFILE"]["BID"];
        }
        
        // Get the group key from POST data or use default based on session type
        $groupKey = 'DEFAULT';
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Anonymous widget users use "WIDGET" as default group key
            $groupKey = isset($_REQUEST['groupKey']) ? trim(db::EscString($_REQUEST['groupKey'])) : 'WIDGET';
        } else {
            // Regular users can specify their own group key
            $groupKey = isset($_REQUEST['groupKey']) ? trim(db::EscString($_REQUEST['groupKey'])) : 'DEFAULT';
        }
        
        if(empty($groupKey) || $groupKey === '') {
            $retArr['error'] = "Group key is required";
            return $retArr;
        }
        
        // Handle file uploads
        $filesArr = [];
        if(!empty($_FILES['files'])) {
            foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
                if (!is_uploaded_file($tmpName)) {
                    $retArr['error'] .= "Invalid upload: ".$_FILES['files']['name'][$i]."\n";
                    continue;
                }
                
                $originalName = $_FILES['files']['name'][$i];
                $fileSize = $_FILES['files']['size'][$i];

                if($fileSize > 1024*1024*90) {
                    $retArr['error'] .= "File too large: ".$originalName."\n";
                    continue;
                }

                $fileType = mime_content_type($tmpName);
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                // Use appropriate MIME type checking based on session type
                $mimeTypeAllowed = false;
                if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
                    // Anonymous widget users have restricted file types
                    $mimeTypeAllowed = Central::checkMimeTypesForAnonymous($fileExtension, $fileType);
                } else {
                    // Regular authenticated users have full file type access
                    $mimeTypeAllowed = Central::checkMimeTypes($fileExtension, $fileType);
                }

                if($mimeTypeAllowed) {
                    // Create file path
                    $userRelPath = substr($userId, -5, 3) . '/' . substr($userId, -2, 2) . '/' . date("Ym") . '/';
                    $fullUploadDir = __DIR__ . '/../up/' . $userRelPath;
                    if (!is_dir($fullUploadDir)) {
                        mkdir($fullUploadDir, 0755, true);
                    }

                    $newFileName = Tools::sysStr($originalName);
                    $targetPath = $fullUploadDir . $newFileName;

                    // Move uploaded file
                    if(move_uploaded_file($tmpName, $targetPath)) {
                        // Create message entry first
                        $inMessageArr = [];
                        $inMessageArr['BUSERID'] = $userId;
                        $inMessageArr['BTEXT'] = 'RAG file: ' . $originalName;
                        $inMessageArr['BUNIXTIMES'] = time();
                        $inMessageArr['BDATETIME'] = date("YmdHis");
                        $inMessageArr['BTRACKID'] = (int) (microtime(true) * 1000000);
                        $inMessageArr['BLANG'] = 'en';
                        $inMessageArr['BTOPIC'] = 'RAG';
                        $inMessageArr['BID'] = 'DEFAULT';
                        $inMessageArr['BPROVIDX'] = session_id();
                        $inMessageArr['BMESSTYPE'] = 'RAG';
                        $inMessageArr['BFILE'] = 1;
                        $inMessageArr['BFILEPATH'] = $userRelPath . $newFileName;
                        $inMessageArr['BFILETYPE'] = $fileExtension;
                        $inMessageArr['BDIRECT'] = 'IN';
                        $inMessageArr['BSTATUS'] = 'NEW';
                        $inMessageArr['BFILETEXT'] = '';

                        // Save to database
                        $resArr = Central::handleInMessage($inMessageArr);
                        
                        if($resArr['lastId'] > 0) {
                            $filesArr[] = [
                                'BID' => $resArr['lastId'],
                                'BFILEPATH' => $userRelPath . $newFileName,
                                'BFILETYPE' => $fileExtension,
                                'BTEXT' => 'RAG file: ' . $originalName
                            ];
                        }
                    } else {
                        $retArr['error'] .= "Failed to move file: ".$originalName."\n";
                    }
                } else {
                    $retArr['error'] .= "Invalid file type: ".$fileExtension."\n";
                }
            }
        }

        if(count($filesArr) == 0) {
            $retArr['error'] = "No valid files uploaded";
            return $retArr;
        }

        // Process files through RAG system
        if($GLOBALS["debug"]) error_log("* * * * * * ************** _________ PROCESSING RAG FILES: ".print_r($filesArr, true));
        $ragResult = Central::processRAGFiles($filesArr, $userId, $groupKey, false);
        
        $retArr['success'] = $ragResult['success'];
        $retArr['processedFiles'] = $ragResult['results'];
        $retArr['processedCount'] = $ragResult['processedCount'];
        $retArr['totalFiles'] = $ragResult['totalFiles'];
        $retArr['groupKey'] = $ragResult['groupKey'];
        $retArr['message'] = "Successfully processed " . $ragResult['processedCount'] . " out of " . $ragResult['totalFiles'] . " files with group key: " . $groupKey;
        
        return $retArr;
    }
    // ********************************************** CHAT STREAM **********************************************
    /**
     * Stream chat updates
     * 
     * Handles server-sent events for real-time chat updates
     * 
     * @return array Empty array (output is sent directly to client)
     */
    public static function chatStream(): array {
        // Handle anonymous widget sessions
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Use widget owner ID for anonymous sessions
            $userId = $_SESSION["widget_owner_id"];
        } else {
            // Regular authenticated user sessions
            $userId = $_SESSION["USERPROFILE"]["BID"];
        }
        $fileCount = 0;
        // ------------------------------------------------------------
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
    
        $lastIds = explode(",", $_REQUEST['lastIds']);
        //error_log("LASTIDS: ". print_r($lastIds, true));

        if (!is_array($lastIds)) {
            http_response_code(400);
            echo "event: error\ndata: Invalid ID list\n\n";
            exit;
        }
        // START OUTPUT 
        $update = [
            'msgId' => "START_".$lastIds[0],
            'status' => 'starting',
            'message' => 'Starting'
        ];
        self::printToStream($update);
        //error_log("START: ". print_r($_REQUEST, true));

        // for each id, get the message
        foreach($lastIds as $msgId) {
            $msgArr = Central::getMsgById(intval($msgId));
            if($msgArr['BFILE'] > 0) {
                $msgArr = Central::parseFile($msgArr, true);
            } else {
                $update = [
                    'msgId' => $msgId,
                    'status' => 'pre_processing',
                    'message' => 'Processing message '.$msgId.'. '
                ];
                self::printToStream($update);
            }
        }
        // ------------------------------------------------------------        
        // ------------------------------------------------------------
        $update = [
            'msgId' => $msgId,
            'status' => 'pre_processing',
            'message' => 'Finished pre-processing message(s). '
        ];
        self::printToStream($update);
        // ------------------------------------------------------------
        // now work on the message itself, sort it and process it
        $aiResponseId = self::createAnswer($msgId);
        
        // Resolve final AI service/model from persisted AI response message (not globals)
        $finalService = '';
        $finalModelProvider = '';
        if ($aiResponseId) {
            $svcRes = db::Query("SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($aiResponseId)." AND BTOKEN = 'AISERVICE' ORDER BY BID ASC LIMIT 1");
            if ($svcArr = db::FetchArr($svcRes)) {
                $finalService = $svcArr['BVALUE'];
            }
            $mdlRes = db::Query("SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($aiResponseId)." AND BTOKEN = 'AIMODEL' ORDER BY BID ASC LIMIT 1");
            if ($mdlArr = db::FetchArr($mdlRes)) {
                $finalModelProvider = $mdlArr['BVALUE'];
            }
        }
        if ($finalService === '') $finalService = $GLOBALS["AI_CHAT"]["SERVICE"] ?? '';
        if ($finalModelProvider === '') $finalModelProvider = $GLOBALS["AI_CHAT"]["MODEL"] ?? '';

        $update = [
            'msgId' => $msgId,
            'aiResponseId' => $aiResponseId, // The actual AI response ID for Again button
            'status' => 'done',
            'message' => 'That should end the stream. ',
            'aiModel' => $finalModelProvider,
            'aiService' => $finalService
        ];
        self::printToStream($update);

        // ------------------------------------------------------------
        // Finish the stream nicely
        echo "event: done\ndata: Stream ended successfully\n\n";
        ob_flush();
        flush();
        return [];
    }
    // ********************************************** process the message **********************************************
    /**
     * Process the message
     * 
     */
    public static function createAnswer($msgId) {
        // Process the message using the provided message ID
        $update = [
            'msgId' => $msgId,
            'status' => 'pre_processing',
            'message' => 'Sorting. '
        ];
        self::printToStream($update);
        // error_log("createAnswer: ".print_r(ProcessMethods::$msgArr, true));
        
        ProcessMethods::init($msgId, true);

        // Handle file translation if needed
        // todo: check the config of the user, if he wants it in English as well!
        // ProcessMethods::fileTranslation();
        
        // Retrieve and process message thread
        $timeSeconds = 1200;
        ProcessMethods::$threadArr = Central::getThread(ProcessMethods::$msgArr, $timeSeconds);

        // Sort and process the message (sort is calling the processor to split tools and topics)
        ProcessMethods::sortMessage();

        // Prepare AI answer for database storage
        $aiLastId = ProcessMethods::saveAnswerToDB();

        return $aiLastId;
    }

    // ********************************************** PRINT TO STREAM **********************************************
    /**
     * Print data to stream
     * 
     * Formats and sends data to the client via server-sent events
     * 
     * @param array $data Data to send
     * @return void
     */
    public static function printToStream($data) {
        $data['timestamp'] = time();
        echo "data: " . json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        ob_flush();
        flush();
    }
    // ****************************************************************************************************** 
    // print to the stream, either AI or status...
    // ****************************************************************************************************** 
    public static function statusToStream($channelId = 0,$streamId = 'pre', $myText = ''): bool {
        $update = [
            'msgId' => $channelId,
            'status' => "{$streamId}_processing",
            'message' => $myText
        ];
        Frontend::printToStream($update);
        return true;
    }
    // ********************************************** PROFILE MANAGEMENT **********************************************
    
    /**
     * Get user profile data
     * 
     * Retrieves the current user's profile information including BUSERDETAILS JSON
     * 
     * @return array User profile data or error message
     */
    public static function getProfile(): array {
        $retArr = ["error" => "", "success" => false];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        
        // Query user data from database
        $sql = "SELECT BID, BMAIL, BUSERDETAILS FROM BUSER WHERE BID = " . $userId;
        $res = DB::Query($sql);
        $userArr = DB::FetchArr($res);
        
        if ($userArr) {
            $retArr = $userArr;
            $retArr["success"] = true;
            
            // Parse BUSERDETAILS JSON if it exists
            if (!empty($userArr['BUSERDETAILS'])) {
                $retArr['BUSERDETAILS'] = json_decode($userArr['BUSERDETAILS'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $retArr['BUSERDETAILS'] = [];
                }
            } else {
                $retArr['BUSERDETAILS'] = [];
            }
        } else {
            $retArr["error"] = "User not found in database";
        }
        
        return $retArr;
    }

    // ****************************************************************************************************** 
    // Get dashboard statistics for the user
    // ****************************************************************************************************** 
    public static function getDashboardStats(): array {
        $userId = $_SESSION["USERPROFILE"]["BID"];
        $stats = [
            'total_messages' => 0,
            'messages_sent' => 0,
            'messages_received' => 0,
            'total_files' => 0,
            'files_sent' => 0,
            'files_received' => 0
        ];
        
        // Get total message counts
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN BDIRECT = 'IN' THEN 1 ELSE 0 END) as messages_received,
                    SUM(CASE WHEN BDIRECT = 'OUT' THEN 1 ELSE 0 END) as messages_sent,
                    SUM(CASE WHEN BFILE > 0 AND BFILEPATH != '' THEN 1 ELSE 0 END) as total_files,
                    SUM(CASE WHEN BFILE > 0 AND BFILEPATH != '' AND BDIRECT = 'IN' THEN 1 ELSE 0 END) as files_received,
                    SUM(CASE WHEN BFILE > 0 AND BFILEPATH != '' AND BDIRECT = 'OUT' THEN 1 ELSE 0 END) as files_sent
                FROM BMESSAGES 
                WHERE BUSERID = ".$userId;
        
        $res = DB::Query($sql);
        $row = DB::FetchArr($res);
        
        if($row) {
            $stats['total_messages'] = intval($row['total_messages']);
            $stats['messages_sent'] = intval($row['messages_sent']);
            $stats['messages_received'] = intval($row['messages_received']);
            $stats['total_files'] = intval($row['total_files']);
            $stats['files_sent'] = intval($row['files_sent']);
            $stats['files_received'] = intval($row['files_received']);
        }
        
        return $stats;
    }
    
    // ****************************************************************************************************** 
    // Get latest files for the dashboard
    // ****************************************************************************************************** 
    public static function getLatestFiles($limit = 10): array {
        $userId = $_SESSION["USERPROFILE"]["BID"];
        $files = [];
        
        $sql = "SELECT BID, BFILEPATH, BFILETYPE, BTEXT, BDIRECT, BDATETIME, BTOPIC 
                FROM BMESSAGES 
                WHERE BUSERID = ".$userId." 
                AND BFILE > 0 
                AND BFILEPATH != '' 
                ORDER BY BID DESC 
                LIMIT ".intval($limit);
        
        $res = DB::Query($sql);
        while($fileArr = DB::FetchArr($res)) {
            if(!empty($fileArr['BFILEPATH']) && !empty($fileArr['BFILETYPE'])) {
                $files[] = [
                    'BID' => $fileArr['BID'],
                    'BFILEPATH' => $fileArr['BFILEPATH'],
                    'BFILETYPE' => $fileArr['BFILETYPE'],
                    'BTEXT' => $fileArr['BTEXT'],
                    'BDIRECT' => $fileArr['BDIRECT'],
                    'BDATETIME' => $fileArr['BDATETIME'],
                    'BTOPIC' => $fileArr['BTOPIC'],
                    'FILENAME' => basename($fileArr['BFILEPATH'])
                ];
            }
        }
        
        return $files;
    }

    // ****************************************************************************************************** 
    // Load chat history for API calls
    // 
    // This method retrieves chat history with enhanced metadata including AI service and model information.
    // It processes messages to include file attachments, markdown rendering, and proper formatting
    // for both user and AI messages. Used by the API to load chat history dynamically.
    // 
    // @param int $amount Number of messages to load (10, 20, or 30)
    // @return array Array containing processed chat messages with all necessary metadata
    // ****************************************************************************************************** 
    public static function loadChatHistory($amount = 10): array {
        $retArr = ["error" => "", "success" => false, "messages" => []];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        // Validate amount parameter
        $validAmounts = [10, 20, 30];
        if (!in_array($amount, $validAmounts)) {
            $amount = 10; // Default to 10 if invalid
        }
        

        
        $historyChatArr = self::getLatestChats($amount);
        
        if(count($historyChatArr) > 0) {
            foreach($historyChatArr as $chat) {
                // Fetch AI service and model information for AI messages
                $aiService = '';
                $aiModel = '';
                if($chat['BDIRECT'] == 'OUT') {
                    // For AI responses, get the AI information from the previous user message (which has the correct AI info)
                    // Find the previous user message (BDIRECT = 'IN') that triggered this AI response
                    $prevUserSQL = "SELECT BID FROM BMESSAGES WHERE BID < ".intval($chat['BID'])." AND BUSERID = ".intval($chat['BUSERID'])." AND BDIRECT = 'IN' ORDER BY BID DESC LIMIT 1";
                    $prevUserRes = DB::Query($prevUserSQL);
                    $prevUserBID = null;
                    if($prevUserArr = DB::FetchArr($prevUserRes)) {
                        $prevUserBID = $prevUserArr['BID'];
                    }
                    
                    if($prevUserBID) {
                        // Check if the user message is an "Again" message (has AGAIN_STATUS = 'RETRY')
                        $againCheckSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($prevUserBID)." AND BTOKEN = 'AGAIN_STATUS' AND BVALUE = 'RETRY'";
                        $againCheckRes = DB::Query($againCheckSQL);
                        $isAgainMessage = (DB::FetchArr($againCheckRes) !== false);
                        
                        // For both normal and Again messages, use the FIRST (correct) AI information
                        // The Again logic sets the correct AI info first, before any potential overwrites
                        $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($prevUserBID)." AND BTOKEN = 'AISERVICE' ORDER BY BID ASC LIMIT 1";
                        $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($prevUserBID)." AND BTOKEN = 'AIMODEL' ORDER BY BID ASC LIMIT 1";
                        
                        $serviceRes = DB::Query($serviceSQL);
                        if($serviceArr = DB::FetchArr($serviceRes)) {
                            $aiService = $serviceArr['BVALUE'];
                        }
                        
                        $modelRes = DB::Query($modelSQL);
                    } else {
                        // Fallback: use AI response metadata (though this might be wrong)
                        $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AISERVICE' ORDER BY BID ASC LIMIT 1";
                        $serviceRes = DB::Query($serviceSQL);
                        if($serviceArr = DB::FetchArr($serviceRes)) {
                            $aiService = $serviceArr['BVALUE'];
                        }
                        
                        $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AIMODEL' ORDER BY BID ASC LIMIT 1";
                        $modelRes = DB::Query($modelSQL);
                    }
                    if($modelArr = DB::FetchArr($modelRes)) {
                        $modelProvider = $modelArr['BVALUE'];
                        
                        // Get display name from BMODELS
                        $modelDetailsSQL = "SELECT BTAG, BNAME, BPROVID FROM BMODELS WHERE BPROVID = '" . db::EscString($modelProvider) . "' LIMIT 1";
                        $modelDetailsRes = db::Query($modelDetailsSQL);
                        $modelDetails = db::FetchArr($modelDetailsRes);
                        
                        if ($modelDetails) {
                            // Use BNAME if it's meaningful and specific, otherwise use provider
                            if (!empty($modelDetails['BNAME']) && $modelDetails['BNAME'] !== 'chat' && strlen($modelDetails['BNAME']) > 3) {
                                $aiModel = $modelDetails['BNAME'];
                            } else {
                                $aiModel = $modelProvider; // Use actual provider like "o3", "gpt-4.1", "claude-opus-4-20250514"
                            }
                        } else {
                            $aiModel = $modelProvider;
                        }
                    }
                }
                
                // Check if message is "agained"
                $againStatus = null;
                $againMetaSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($chat['BID']) . " AND BTOKEN = 'AGAIN_STATUS'";
                $againMetaRes = db::Query($againMetaSQL);
                $againMetaData = db::FetchArr($againMetaRes);
                if ($againMetaData) {
                    $againStatus = $againMetaData['BVALUE'];
                }
                
                // Process message data
                $messageData = [
                    'BID' => $chat['BID'],
                    'BDIRECT' => $chat['BDIRECT'],
                    'BTEXT' => $chat['BTEXT'],
                    'BDATETIME' => $chat['BDATETIME'],
                    'BTOPIC' => $chat['BTOPIC'],
                    'FILECOUNT' => isset($chat['FILECOUNT']) ? $chat['FILECOUNT'] : 0,
                    'BFILE' => $chat['BFILE'],
                    'BFILEPATH' => $chat['BFILEPATH'],
                    'BFILETYPE' => $chat['BFILETYPE'],
                    'aiService' => $aiService,
                    'aiModel' => $aiModel,
                    // expose raw provider to front-end for provider-specific icon logic (e.g., DeepSeek via Groq)
                    'aiModelProvider' => isset($modelProvider) ? $modelProvider : '',
                    'againStatus' => $againStatus
                ];
                

                
                // Process display text for AI messages
                if($chat['BDIRECT'] == 'OUT') {
                    $displayText = $chat['BTEXT'];
                    if(substr($chat['BTEXT'], 0, 1) == '/') {
                        $displayText = "File generated";
                    }
                    
                    $hasFile = ($chat['BFILE'] > 0 && !empty($chat['BFILETYPE']) && !empty($chat['BFILEPATH']) && strpos($chat['BFILEPATH'], '/') !== false);
                    
                    // If the message starts with a tool command but has a file, show a better message
                    if ($hasFile && substr($chat['BTEXT'], 0, 1) == '/') {
                        if ($chat['BFILETYPE'] == 'mp4' || $chat['BFILETYPE'] == 'webm') {
                            $displayText = "Video";
                        } elseif (in_array($chat['BFILETYPE'], ['png', 'jpg', 'jpeg', 'gif'])) {
                            $displayText = "Image";
                        } else {
                            $displayText = "File";
                        }
                    }
                    
                    $messageData['displayText'] = $displayText;
                    $messageData['hasFile'] = $hasFile;
                }
                
                $retArr['messages'][] = $messageData;
            }
        }
        
        $retArr['success'] = true;
        $retArr['count'] = count($retArr['messages']);
        $retArr['amount'] = $amount;
        $retArr['timestamp'] = time(); // Add timestamp to prevent caching
        
        return $retArr;
    }

    // ****************************************************************************************************** 
    // Widget Management Methods
    // ****************************************************************************************************** 
    
    /**
     * Get all widgets for the current user
     * 
     * @return array Array containing widget configurations
     */
    public static function getWidgets(): array {
        $retArr = ["error" => "", "success" => false, "widgets" => []];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        
        // Get all widget configurations for this user
        $sql = "SELECT BGROUP, BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP LIKE 'widget_%' ORDER BY BGROUP, BSETTING";
        $res = DB::Query($sql);
        
        $widgets = [];
        while($row = DB::FetchArr($res)) {
            $group = $row['BGROUP'];
            $setting = $row['BSETTING'];
            $value = $row['BVALUE'];
            
            // Extract widget ID from group (e.g., "widget_1" -> 1)
            if (preg_match('/^widget_(\d+)$/', $group, $matches)) {
                $widgetId = intval($matches[1]);
                
                if (!isset($widgets[$widgetId])) {
                    $widgets[$widgetId] = [
                        'widgetId' => $widgetId,
                        'userId' => $userId, // Add user ID to widget data
                        'color' => '#007bff',
                        'position' => 'bottom-right',
                        'autoMessage' => '',
                        'prompt' => 'general'
                    ];
                }
                
                // Map settings to widget properties
                switch($setting) {
                    case 'color':
                        $widgets[$widgetId]['color'] = $value;
                        break;
                    case 'position':
                        $widgets[$widgetId]['position'] = $value;
                        break;
                    case 'autoMessage':
                        $widgets[$widgetId]['autoMessage'] = $value;
                        break;
                    case 'prompt':
                        $widgets[$widgetId]['prompt'] = $value;
                        break;
                }
            }
        }
        
        $retArr["success"] = true;
        $retArr["widgets"] = array_values($widgets);
        return $retArr;
    }
    
    /**
     * Save widget configuration
     * 
     * @return array Result of the save operation
     */
    public static function saveWidget(): array {
        $retArr = ["error" => "", "success" => false];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        $widgetId = intval($_REQUEST['widgetId'] ?? 0);
        
        if ($widgetId < 1 || $widgetId > 9) {
            $retArr["error"] = "Invalid widget ID. Must be between 1 and 9.";
            return $retArr;
        }
        
        // Validate and sanitize input
        $color = db::EscString($_REQUEST['widgetColor'] ?? '#007bff');
        $position = db::EscString($_REQUEST['widgetPosition'] ?? 'bottom-right');
        $autoMessage = db::EscString($_REQUEST['autoMessage'] ?? '');
        $prompt = db::EscString($_REQUEST['widgetPrompt'] ?? 'general');
        
        // Validate position
        $validPositions = ['bottom-right', 'bottom-left', 'bottom-center'];
        if (!in_array($position, $validPositions)) {
            $retArr["error"] = "Invalid position value";
            return $retArr;
        }
        
        // Validate color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $retArr["error"] = "Invalid color format";
            return $retArr;
        }
        
        $group = "widget_" . $widgetId;
        
        // Save widget settings
        $settings = [
            'color' => $color,
            'position' => $position,
            'autoMessage' => $autoMessage,
            'prompt' => $prompt
        ];
        
        foreach ($settings as $setting => $value) {
            // Check if setting already exists
            $checkSQL = "SELECT BID FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = '" . db::EscString($group) . "' AND BSETTING = '" . db::EscString($setting) . "'";
            $checkRes = DB::Query($checkSQL);
            
            if (DB::CountRows($checkRes) > 0) {
                // Update existing setting
                $updateSQL = "UPDATE BCONFIG SET BVALUE = '" . $value . "' WHERE BOWNERID = " . $userId . " AND BGROUP = '" . db::EscString($group) . "' AND BSETTING = '" . db::EscString($setting) . "'";
                DB::Query($updateSQL);
            } else {
                // Insert new setting
                $insertSQL = "INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (" . $userId . ", '" . db::EscString($group) . "', '" . db::EscString($setting) . "', '" . $value . "')";
                DB::Query($insertSQL);
            }
        }
        
        $retArr["success"] = true;
        $retArr["message"] = "Widget saved successfully";
        return $retArr;
    }
    
    /**
     * Delete widget configuration
     * 
     * @return array Result of the delete operation
     */
    public static function deleteWidget(): array {
        $retArr = ["error" => "", "success" => false];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        $widgetId = intval($_REQUEST['widgetId'] ?? 0);
        
        if ($widgetId < 1 || $widgetId > 9) {
            $retArr["error"] = "Invalid widget ID. Must be between 1 and 9.";
            return $retArr;
        }
        
        $group = "widget_" . $widgetId;
        
        // Delete all settings for this widget
        $deleteSQL = "DELETE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = '" . db::EscString($group) . "'";
        DB::Query($deleteSQL);
        
        $retArr["success"] = true;
        $retArr["message"] = "Widget deleted successfully";
        return $retArr;
    }
    
    // ****************************************************************************************************** 
    // Mail Handler Configuration (per user)
    // ****************************************************************************************************** 
    
    /**
     * Load mail handler configuration for current user
     */
    public static function getMailhandler(): array {
        $retArr = ["success" => false, "config" => [], "departments" => []];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        
        // Defaults
        $config = [
            'mailServer' => '',
            'mailPort' => '993',
            'mailProtocol' => 'imap',
            'mailSecurity' => 'ssl',
            'mailUsername' => '',
            'mailPassword' => '',
            'mailCheckInterval' => '10',
            'mailDeleteAfter' => '0',
            'authMethod' => 'password'
        ];
        
        // Load saved config
        $cfgSQL = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = 'mailhandler'";
        $cfgRes = DB::Query($cfgSQL);
        while($row = DB::FetchArr($cfgRes)) {
            switch ($row['BSETTING']) {
                case 'server': $config['mailServer'] = $row['BVALUE']; break;
                case 'port': $config['mailPort'] = $row['BVALUE']; break;
                case 'protocol': $config['mailProtocol'] = $row['BVALUE']; break;
                case 'security': $config['mailSecurity'] = $row['BVALUE']; break;
                case 'username': $config['mailUsername'] = $row['BVALUE']; break;
                case 'password': $config['mailPassword'] = $row['BVALUE']; break;
                case 'checkInterval': $config['mailCheckInterval'] = $row['BVALUE']; break;
                case 'deleteAfter': $config['mailDeleteAfter'] = $row['BVALUE']; break;
                case 'authMethod': $config['authMethod'] = $row['BVALUE']; break;
            }
        }

        // Compute server-side redirect URIs for UI display (avoid origin mismatches)
        $config['googleRedirectUri'] = $GLOBALS['baseUrl'] . 'api.php?action=mailOAuthCallback&provider=google';
        $config['microsoftRedirectUri'] = $GLOBALS['baseUrl'] . 'api.php?action=mailOAuthCallback&provider=microsoft';
        
        // Load departments
        $deptSQL = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = 'mailhandler_dept' ORDER BY CAST(BSETTING AS UNSIGNED) ASC";
        $deptRes = DB::Query($deptSQL);
        $departments = [];
        while($row = DB::FetchArr($deptRes)) {
            // stored as email|description|isDefault
            $parts = explode('|', $row['BVALUE']);
            $departments[] = [
                'email' => $parts[0] ?? '',
                'description' => $parts[1] ?? '',
                'isDefault' => ($parts[2] ?? '0') === '1' ? 1 : 0
            ];
        }
        
        // OAuth status
        try {
            $status = mailHandler::oauthStatus($userId);
            $retArr['oauthStatus'] = $status;
        } catch (\Throwable $e) {
            $retArr['oauthStatus'] = ['success' => false];
        }

        $retArr['success'] = true;
        $retArr['config'] = $config;
        $retArr['departments'] = $departments;
        return $retArr;
    }
    
    /**
     * Save mail handler configuration for current user
     */
    public static function saveMailhandler(): array {
        $retArr = ["success" => false];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        
        // Sanitize inputs
        $server = db::EscString($_REQUEST['mailServer'] ?? '');
        $port = intval($_REQUEST['mailPort'] ?? 993);
        $protocol = db::EscString($_REQUEST['mailProtocol'] ?? 'imap');
        $security = db::EscString($_REQUEST['mailSecurity'] ?? 'ssl');
        $username = db::EscString($_REQUEST['mailUsername'] ?? '');
        $password = db::EscString($_REQUEST['mailPassword'] ?? '');
        $checkInterval = intval($_REQUEST['mailCheckInterval'] ?? 10);
        $deleteAfter = isset($_REQUEST['mailDeleteAfter']) && ($_REQUEST['mailDeleteAfter'] === 'on' || $_REQUEST['mailDeleteAfter'] === '1') ? 1 : 0;
        $authMethod = isset($_REQUEST['authMethod']) ? db::EscString($_REQUEST['authMethod']) : 'password';
        if (!in_array($authMethod, ['password','oauth_google','oauth_microsoft'])) { $authMethod = 'password'; }
        
        // Basic validation
        if ($server === '' || $port < 1 || $port > 65535 || $username === '') {
            $retArr['error'] = 'Invalid input values';
            return $retArr;
        }
        
        $group = 'mailhandler';
        $settings = [
            'server' => $server,
            'port' => (string)$port,
            'protocol' => $protocol,
            'security' => $security,
            'username' => $username,
            'password' => $password,
            'checkInterval' => (string)$checkInterval,
            'deleteAfter' => (string)$deleteAfter,
            'authMethod' => $authMethod
        ];
        
        foreach ($settings as $setting => $value) {
            $checkSQL = "SELECT BID FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = '" . db::EscString($group) . "' AND BSETTING = '" . db::EscString($setting) . "'";
            $checkRes = DB::Query($checkSQL);
            if (DB::CountRows($checkRes) > 0) {
                $updateSQL = "UPDATE BCONFIG SET BVALUE = '" . $value . "' WHERE BOWNERID = " . $userId . " AND BGROUP = '" . db::EscString($group) . "' AND BSETTING = '" . db::EscString($setting) . "'";
                DB::Query($updateSQL);
            } else {
                $insertSQL = "INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (" . $userId . ", '" . db::EscString($group) . "', '" . db::EscString($setting) . "', '" . $value . "')";
                DB::Query($insertSQL);
            }
        }

        // No per-user OAuth apps on shared platform
        
        // Departments
        $emails = isset($_REQUEST['departmentEmail']) ? $_REQUEST['departmentEmail'] : [];
        $descs = isset($_REQUEST['departmentDescription']) ? $_REQUEST['departmentDescription'] : [];
        $defaultIdx = isset($_REQUEST['defaultDepartment']) ? intval($_REQUEST['defaultDepartment']) : -1;
        
        // Normalize arrays
        if (!is_array($emails)) $emails = [];
        if (!is_array($descs)) $descs = [];
        
        // Clear previous departments
        DB::Query("DELETE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = 'mailhandler_dept'");
        
        // Insert new departments
        $count = 0;
        for ($i = 0; $i < count($emails); $i++) {
            $email = trim($emails[$i]);
            $desc = isset($descs[$i]) ? trim($descs[$i]) : '';
            if ($email === '') continue;
            $isDefault = ($i === $defaultIdx) ? '1' : '0';
            $val = db::EscString($email . '|' . $desc . '|' . $isDefault);
            $ins = "INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (" . $userId . ", 'mailhandler_dept', '" . $count . "', '" . $val . "')";
            DB::Query($ins);
            $count++;
        }
        
        // Persist authMethod also through mailHandler helper for consistency
        try { mailHandler::setAuthMethodForUser($userId, $authMethod); } catch (\Throwable $e) {}

        $retArr['success'] = true;
        $retArr['message'] = 'Mail handler configuration saved';
        return $retArr;
    }

    // ****************************************************************************************************** 
    // Mail OAuth API delegations
    // ****************************************************************************************************** 

    public static function mailOAuthStart(): array {
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        $provider = isset($_REQUEST['provider']) ? trim(strtolower($_REQUEST['provider'])) : '';
        $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
        if (!in_array($provider, ['google','microsoft'])) { return ['success' => false, 'error' => 'Invalid provider']; }
        $redirectUri = $GLOBALS['baseUrl'] . "api.php?action=mailOAuthCallback&provider=".$provider;
        return mailHandler::oauthStart($provider, $redirectUri, $userId, $email);
    }

    public static function mailOAuthCallback(): array {
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        $provider = isset($_REQUEST['provider']) ? trim(strtolower($_REQUEST['provider'])) : '';
        $code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
        if (!in_array($provider, ['google','microsoft'])) { return ['success' => false, 'error' => 'Invalid provider']; }
        if (strlen($code) < 5) { return ['success' => false, 'error' => 'Missing code']; }
        $redirectUri = $GLOBALS['baseUrl'] . "api.php?action=mailOAuthCallback&provider=".$provider;
        return mailHandler::oauthCallback($provider, $code, $redirectUri, $userId);
    }

    public static function mailOAuthStatus(): array {
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        return mailHandler::oauthStatus($userId);
    }

    public static function mailOAuthDisconnect(): array {
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        return mailHandler::oauthDisconnect($userId);
    }

    /**
     * Test IMAP/POP connection using current form values (does not persist)
     */
    public static function mailTestConnection(): array {
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        $params = [
            'server' => isset($_REQUEST['mailServer']) ? trim($_REQUEST['mailServer']) : '',
            'port' => isset($_REQUEST['mailPort']) ? intval($_REQUEST['mailPort']) : 993,
            'protocol' => isset($_REQUEST['mailProtocol']) ? trim($_REQUEST['mailProtocol']) : 'imap',
            'security' => isset($_REQUEST['mailSecurity']) ? trim($_REQUEST['mailSecurity']) : 'ssl',
            'username' => isset($_REQUEST['mailUsername']) ? trim($_REQUEST['mailUsername']) : '',
            'password' => isset($_REQUEST['mailPassword']) ? (string)$_REQUEST['mailPassword'] : '',
            'authMethod' => isset($_REQUEST['authMethod']) ? trim($_REQUEST['authMethod']) : ''
        ];
        $result = mailHandler::imapTestConnection($userId, $params);
        // Add simple mask for username in echo
        if (isset($result['connection']['username'])) {
            $u = $result['connection']['username'];
            if (strlen($u) > 4) {
                $result['connection']['username_masked'] = substr($u, 0, 2) . '***' . substr($u, -2);
            } else {
                $result['connection']['username_masked'] = '***';
            }
        }
        return $result;
    }
    
    /**
     * Set anonymous widget session
     * 
     * Creates a temporary session for anonymous widget users
     * 
     * @param int $ownerId The widget owner's user ID
     * @param int $widgetId The widget ID
     * @return bool True if session was set successfully, false otherwise
     */
    public static function setAnonymousWidgetSession($ownerId, $widgetId): bool {
        // Validate parameters
        if ($ownerId <= 0 || $widgetId < 1 || $widgetId > 9) {
            return false;
        }
        
        // Generate unique anonymous session ID (shorter MD5 hash for DB storage)
        $anonymousSessionId = md5('anon_' . uniqid() . '_' . time());
        
        // Set widget session variables
        $_SESSION["is_widget"] = true;
        $_SESSION["widget_owner_id"] = intval($ownerId);
        $_SESSION["widget_id"] = intval($widgetId);
        $_SESSION["anonymous_session_id"] = $anonymousSessionId;
        $_SESSION["anonymous_session_created"] = time(); // Add creation timestamp
        
        // Do NOT set $_SESSION["USERPROFILE"] to prevent login access
        // Messages will be saved as the widget owner
        
        return true;
    }
    
    /**
     * Validate anonymous widget session timeout
     * 
     * @return bool True if session is still valid, false if expired
     */
    public static function validateAnonymousSession(): bool {
        if (!isset($_SESSION["is_widget"]) || $_SESSION["is_widget"] !== true) {
            return false;
        }
        
        // Check if session was created more than 24 hours ago (86400 seconds)
        $sessionTimeout = 86400; // 24 hours
        $sessionCreated = $_SESSION["anonymous_session_created"] ?? 0;
        
        if ((time() - $sessionCreated) > $sessionTimeout) {
            // Session expired, clear anonymous session data
            unset($_SESSION["is_widget"]);
            unset($_SESSION["widget_owner_id"]);
            unset($_SESSION["widget_id"]);
            unset($_SESSION["anonymous_session_id"]);
            unset($_SESSION["anonymous_session_created"]);
            return false;
        }
        
        return true;
    }

    // ********************************************** API KEY MANAGEMENT **********************************************
    public static function getApiKeys(): array {
        $ret = ["success"=>false, "keys"=>[]];
        if (!isset($_SESSION["USERPROFILE"]["BID"])) return $ret;
        $uid = intval($_SESSION["USERPROFILE"]["BID"]);
        $sql = "SELECT BID, BOWNERID, BNAME, CONCAT(SUBSTRING(BKEY,1,12),'...',RIGHT(BKEY,4)) AS BMASKEDKEY, BSTATUS, BCREATED, BLASTUSED FROM BAPIKEYS WHERE BOWNERID = ".$uid." ORDER BY BID DESC";
        $res = DB::Query($sql);
        $rows = [];
        while($row = DB::FetchArr($res)) { $rows[] = $row; }
        $ret["success"] = true;
        $ret["keys"] = $rows;
        return $ret;
    }

    public static function createApiKey(): array {
        $ret = ["success"=>false];
        if (!isset($_SESSION["USERPROFILE"]["BID"])) return $ret;
        $uid = intval($_SESSION["USERPROFILE"]["BID"]);
        $name = isset($_REQUEST['name']) ? DB::EscString($_REQUEST['name']) : '';
        $now = time();
        $random = bin2hex(random_bytes(24));
        $key = 'sk_live_' . $random;
        $ins = "INSERT INTO BAPIKEYS (BOWNERID, BNAME, BKEY, BSTATUS, BCREATED, BLASTUSED) VALUES (".$uid.", '".$name."', '".DB::EscString($key)."', 'active', ".$now.", 0)";
        DB::Query($ins);
        $ret["success"] = true;
        $ret["key"] = $key;
        return $ret;
    }

    public static function setApiKeyStatus(): array {
        $ret = ["success"=>false];
        if (!isset($_SESSION["USERPROFILE"]["BID"])) return $ret;
        $uid = intval($_SESSION["USERPROFILE"]["BID"]);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $status = isset($_REQUEST['status']) ? DB::EscString($_REQUEST['status']) : '';
        if($id <= 0 || !in_array($status, ['active','paused'])) return $ret;
        $upd = "UPDATE BAPIKEYS SET BSTATUS='".$status."' WHERE BID = ".$id." AND BOWNERID = ".$uid;
        DB::Query($upd);
        $ret["success"] = DB::AffectedRows() ? true : true; // consider true even if unchanged
        return $ret;
    }

    public static function deleteApiKey(): array {
        $ret = ["success"=>false];
        if (!isset($_SESSION["USERPROFILE"]["BID"])) return $ret;
        $uid = intval($_SESSION["USERPROFILE"]["BID"]);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if($id <= 0) return $ret;
        $del = "DELETE FROM BAPIKEYS WHERE BID = ".$id." AND BOWNERID = ".$uid;
        DB::Query($del);
        $ret["success"] = DB::AffectedRows() ? true : true;
        return $ret;
    }

    // ********************************************** USER REGISTRATION **********************************************
    /**
     * Register a new user
     * 
     * Creates a new user account with email confirmation
     * 
     * @return array Array with success status and error message if applicable
     */
    public static function registerNewUser(): array {
        $retArr = ["success" => false, "error" => ""];
        
        // Get email and password from request
        $email = isset($_REQUEST['email']) ? DB::EscString($_REQUEST['email']) : '';
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        $confirmPassword = isset($_REQUEST['confirmPassword']) ? $_REQUEST['confirmPassword'] : '';
        
        // Validate input
        if(strlen($email) > 0 && strlen($password) > 0 && $password === $confirmPassword && strlen($password) >= 6) {
            // Check if email already exists
            $checkSQL = "SELECT BID FROM BUSER WHERE BMAIL = '".$email."'";
            $checkRes = DB::Query($checkSQL);
            $existingUser = DB::FetchArr($checkRes);
            
            if($existingUser) {
                // Email already exists
                $retArr["error"] = "An account with this email address already exists.";
                return $retArr;
            }
            
            // Generate 6-character alphanumeric PIN
            $pin = self::generatePin();
            
            // MD5 encrypt the password
            $passwordMd5 = md5($password);
            
            // Create user details JSON
            $userDetails = [
                'firstName' => '',
                'lastName' => '',
                'phone' => '',
                'companyName' => '',
                'vatId' => '',
                'street' => '',
                'zipCode' => '',
                'city' => '',
                'country' => '',
                'language' => $_SESSION["LANG"] ?? 'en',
                'timezone' => '',
                'invoiceEmail' => '',
                'emailConfirmed' => false,
                'pin' => $pin
            ];
            
            // Insert new user
            $insertSQL = "INSERT INTO BUSER (BCREATED, BINTYPE, BMAIL, BPW, BPROVIDERID, BUSERLEVEL, BUSERDETAILS) 
                         VALUES ('".date("YmdHis")."', 'MAIL', '".$email."', '".$passwordMd5."', '".DB::EscString($email)."', 'PIN:".$pin."', '".DB::EscString(json_encode($userDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))."')";
            
            DB::Query($insertSQL);
            $newUserId = DB::LastId();
            
            if($newUserId > 0) {
                // Send confirmation email
                $emailSent = self::sendRegistrationConfirmationEmail($email, $pin, $newUserId);
                if($emailSent) {
                    $retArr["success"] = true;
                    $retArr["message"] = "Registration successful! Please check your email for confirmation.";
                } else {
                    // User was created but email failed - still return success but with warning
                    $retArr["success"] = true;
                    $retArr["message"] = "Account created successfully, but confirmation email could not be sent. Please contact support.";
                }
            } else {
                $retArr["error"] = "Failed to create user account. Please try again.";
            }
        } else {
            if(strlen($email) == 0) {
                $retArr["error"] = "Email address is required.";
            } elseif(strlen($password) == 0) {
                $retArr["error"] = "Password is required.";
            } elseif($password !== $confirmPassword) {
                $retArr["error"] = "Passwords do not match.";
            } elseif(strlen($password) < 6) {
                $retArr["error"] = "Password must be at least 6 characters long.";
            } else {
                $retArr["error"] = "Invalid input data.";
            }
        }
        
        return $retArr;
    }
    
    /**
     * Generate a random 6-character alphanumeric PIN
     * 
     * @return string 6-character PIN
     */
    private static function generatePin(): string {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $pin = '';
        for ($i = 0; $i < 6; $i++) {
            $pin .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $pin;
    }
    
    /**
     * Send registration confirmation email
     * 
     * @param string $email User's email address
     * @param string $pin Confirmation PIN
     * @param int $userId User ID
     * @return bool True if email sent successfully
     */
    private static function sendRegistrationConfirmationEmail(string $email, string $pin, int $userId): bool {
        $confirmLink = $GLOBALS["baseUrl"] . "index.php/confirm/?PIN=" . $pin . "&UID=" . $userId;
        
        $htmlText = "
        <h2>Welcome to Synaplan!</h2>
        <p>Thank you for registering with Synaplan. To complete your registration, please click the confirmation link below:</p>
        <p><a href='".$confirmLink."' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Confirm Email Address</a></p>
        <p>Or copy and paste this link into your browser:</p>
        <p>".$confirmLink."</p>
        <p>This link will expire once used. If you did not create this account, please ignore this email.</p>
        <p>Best regards,<br>The Synaplan Team</p>
        ";
        
        $plainText = "
        Welcome to Synaplan!
        
        Thank you for registering with Synaplan. To complete your registration, please visit this link:
        
        ".$confirmLink."
        
        This link will expire once used. If you did not create this account, please ignore this email.
        
        Best regards,
        The Synaplan Team
        ";
        
        try {
            return _mymail("info@metadist.de", $email, "Synaplan - Confirm Your Email Address", $htmlText, $plainText, "noreply@synaplan.com");
        } catch (Exception $e) {
            if($GLOBALS["debug"]) {
                error_log("Failed to send registration email: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Handle "Again" request for AI message retry
     * 
     * Implements Round-Robin model selection per thread:
     * - Global model ranking from BMODELS (BQUALITY DESC, BID ASC)
     * - Round-Robin logic: exclude used models, reset when exhausted
     * - Avoid direct repetition of last used model
     * - Lock-based concurrency control (no rate limiting)
     * - Transactional processing with proper error handling
     * 
     * @return array Response with success/error status and model BIDs
     */
    public static function againMessage(): array {
        $retArr = ["error" => "", "success" => false, "error_code" => ""];
        
        // Get message ID from request
        $messageId = isset($_REQUEST['messageId']) ? intval($_REQUEST['messageId']) : 0;
        
        if (!$messageId) {
            $retArr["error"] = "Message ID required";
            $retArr["error_code"] = "INVALID_REQUEST";
            return $retArr;
        }
        
        // Get optional model override from request
        $overrideModelId = isset($_REQUEST['modelBid']) ? intval($_REQUEST['modelBid']) : null;
        
        // Get user ID (handle both authenticated and anonymous widget sessions)
        $userId = 0;
        if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            // Anonymous widget session
            $userId = $_SESSION["widget_owner_id"];
        } elseif (isset($_SESSION["USERPROFILE"]["BID"])) {
            // Authenticated user session
            $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        } else {
            $retArr["error"] = "Authentication required";
            $retArr["error_code"] = "UNAUTHORIZED";
            return $retArr;
        }
        
        // Check if message can be "agained" (only lock check)
        $canAgain = AgainLogic::canAgainMessage($messageId);
        if (!$canAgain['allowed']) {
            $retArr["error"] = self::getErrorMessage($canAgain['error_code']);
            $retArr["error_code"] = $canAgain['error_code'];
            AgainLogic::storeErrorAnalytics($messageId, $canAgain['error_code']);
            return $retArr;
        }
        
        // Get original message details with fallback
        $originalMessageSQL = "SELECT * FROM BMESSAGES WHERE BID = " . intval($messageId) . " AND BDIRECT = 'OUT'";
        $originalMessageRes = db::Query($originalMessageSQL);
        $originalMessage = db::FetchArr($originalMessageRes);
        
        if (!$originalMessage) {
            // Debug logging
            if($GLOBALS["debug"]) {
                error_log("AgainMessage: Direct message lookup failed for ID " . $messageId);
                
                // Check if message exists at all
                $debugSQL = "SELECT BID, BDIRECT, BUNIXTIMES FROM BMESSAGES WHERE BID = " . intval($messageId);
                $debugRes = db::Query($debugSQL);
                $debugData = db::FetchArr($debugRes);
                if ($debugData) {
                    error_log("AgainMessage: Message exists but BDIRECT = " . $debugData['BDIRECT'] . ", BUNIXTIMES = " . $debugData['BUNIXTIMES']);
                } else {
                    error_log("AgainMessage: Message with ID " . $messageId . " does not exist in database");
                }
            }
            
            $retArr["error"] = "Original message not found (ID: " . $messageId . ")";
            $retArr["error_code"] = "MESSAGE_NOT_FOUND";
            AgainLogic::storeErrorAnalytics($messageId, "MESSAGE_NOT_FOUND");
            return $retArr;
        }
        
        // Get original model details
        $originalModel = AgainLogic::getOriginalModelFromMessage($messageId);
        if (!$originalModel) {
            $retArr["error"] = "Could not determine original model";
            $retArr["error_code"] = "MODEL_NOT_FOUND";
            return $retArr;
        }
        
        // Get next best model using topic-aware Round-Robin logic (with optional override)
        $topic = $originalMessage['BTOPIC'] ?: 'chat';
        $nextModel = AgainLogic::getNextBestModel($originalModel['bid'], $originalMessage['BTRACKID'], $overrideModelId, $topic);
        if (!$nextModel) {
            $retArr["error"] = "No alternative model available";
            $retArr["error_code"] = "NO_ALTERNATIVE_MODEL";
            AgainLogic::storeErrorAnalytics($messageId, "NO_ALTERNATIVE_MODEL");
            return $retArr;
        }
        
        // Lock the message for processing
        if (!AgainLogic::lockMessage($messageId)) {
            $retArr["error"] = "Could not lock message for processing";
            $retArr["error_code"] = "LOCK_FAILED";
            return $retArr;
        }
        
        try {
            // Store original GLOBALS for restoration
            $originalGlobals = [
                'SERVICE' => $GLOBALS["AI_CHAT"]["SERVICE"] ?? null,
                'MODEL' => $GLOBALS["AI_CHAT"]["MODEL"] ?? null,
                'MODELID' => $GLOBALS["AI_CHAT"]["MODELID"] ?? null
            ];
            
            // Find the last user message before the agained AI response (robust filtering)
            $originalUserMessageSQL = "SELECT * FROM BMESSAGES WHERE BTRACKID = " . intval($originalMessage['BTRACKID']) . " AND BDIRECT = 'IN' AND (BUNIXTIMES < " . intval($originalMessage['BUNIXTIMES']) . " OR (BUNIXTIMES = " . intval($originalMessage['BUNIXTIMES']) . " AND BID < " . intval($messageId) . ")) ORDER BY BUNIXTIMES DESC, BID DESC LIMIT 1";
            $originalUserMessageRes = db::Query($originalUserMessageSQL);
            $originalUserMessage = db::FetchArr($originalUserMessageRes);
            
            if (!$originalUserMessage) {
                throw new Exception("Could not find original user message");
            }
            
            // Create retry message array
            $retryMessage = $originalUserMessage;
            $retryMessage['BID'] = 'DEFAULT';
            $retryMessage['BUNIXTIMES'] = time();
            $retryMessage['BDATETIME'] = date("YmdHis");
            $retryMessage['BSTATUS'] = 'AGAIN_RETRY';
            
            // Save retry message to database
            $retryResult = Central::handleInMessage($retryMessage);
            if (!$retryResult || $retryResult['error'] || !$retryResult['lastId']) {
                throw new Exception("Failed to create retry message: " . $retryResult['error']);
            }
            
            $retryMessageId = $retryResult['lastId'];
            
            // Don't process immediately - just prepare for streaming
            // The frontend will trigger a separate SSE stream
            
            // Override the model selection for this specific request
            $GLOBALS["AI_CHAT"]["SERVICE"] = "AI" . $nextModel['BSERVICE'];
            $GLOBALS["AI_CHAT"]["MODEL"] = $nextModel['BPROVID'];
            $GLOBALS["AI_CHAT"]["MODELID"] = $nextModel['BID'];
            
            // Store model selection for the retry message
            XSControl::storeAIDetails(['BID' => $retryMessageId], 'AIMODEL', $nextModel['BPROVID'], false);
            XSControl::storeAIDetails(['BID' => $retryMessageId], 'AISERVICE', 'AI' . $nextModel['BSERVICE'], false);
            
            $aiRetryId = $retryMessageId; // Use the user message ID as placeholder
            
            // Mark original message as "agained" and store metadata with BIDs
            if (!AgainLogic::markMessageAsAgained($messageId, $retryMessageId, $originalModel['bid'], intval($nextModel['BID']), $nextModel['BPROVID'], $nextModel['BSERVICE'])) {
                throw new Exception("Failed to mark message as agained");
            }
            
            // Success response
            $retArr["success"] = true;
            $retArr["original_message_id"] = $messageId;
            $retArr["retry_message_id"] = $aiRetryId;
            $retArr["original_model"] = $originalModel['provider'];
            $retArr["original_model_bid"] = $originalModel['bid'];
            $retArr["retry_model"] = $nextModel['BPROVID'];
            $retArr["retry_model_bid"] = intval($nextModel['BID']);
            $retArr["retry_model_service"] = $nextModel['BSERVICE'];
            $retArr["was_override"] = ($overrideModelId !== null && intval($nextModel['BID']) == $overrideModelId);
            
            // Don't include content - let SSE streaming handle it
            // $retArr["retry_content"] will be empty to trigger streaming
            
        } catch (Exception $e) {
            // Error occurred - clean up and return error
            if($GLOBALS["debug"]) {
                error_log("AgainMessage failed: " . $e->getMessage());
            }
            
            $retArr["error"] = "Retry failed: " . $e->getMessage();
            $retArr["error_code"] = "RETRY_FAILED";
            AgainLogic::storeErrorAnalytics($messageId, "RETRY_FAILED");
            
        } finally {
            // Restore original GLOBALS
            if (isset($originalGlobals)) {
                foreach ($originalGlobals as $key => $value) {
                    if ($value !== null) {
                        $GLOBALS["AI_CHAT"][$key] = $value;
                    } else {
                        unset($GLOBALS["AI_CHAT"][$key]);
                    }
                }
            }
            
            // Always unlock the message
            AgainLogic::unlockMessage($messageId);
        }
        
        return $retArr;
    }
    
    /**
     * Get user-friendly error message for error codes
     * 
     * @param string $errorCode Error code
     * @return string User-friendly error message
     */
    private static function getErrorMessage(string $errorCode): string {
        switch ($errorCode) {
            case 'LOCKED':
                return 'Another retry is already in progress for this message';
            case 'NO_ALTERNATIVE_MODEL':
                return 'No alternative AI model available for retry';
            case 'RETRY_FAILED':
                return 'The retry attempt failed. Please try again later';
            case 'MESSAGE_NOT_FOUND':
                return 'The original message could not be found';
            case 'MODEL_NOT_FOUND':
                return 'Could not determine the original AI model';
            case 'UNAUTHORIZED':
                return 'Authentication required';
            case 'INVALID_REQUEST':
                return 'Invalid request parameters';
            case 'INTERNAL':
                return 'An internal error occurred';
            default:
                return 'An unexpected error occurred';
        }
    }
    
    /**
     * Get selectable models for Again dropdown
     * 
     * @return array Array with models data or error
     */
    public static function getSelectableModels(): array {
        $retArr = ["error" => "", "success" => false];
        
        try {
            // Get topic from request for filtering
            $topic = isset($_REQUEST['topic']) ? db::EscString($_REQUEST['topic']) : 'chat';
            $models = AgainLogic::getSelectableModels($topic);
            $retArr["success"] = true;
            $retArr["models"] = $models;
        } catch (Exception $e) {
            $retArr["error"] = "Failed to load models: " . $e->getMessage();
            $retArr["error_code"] = "INTERNAL";
        }
        
        return $retArr;
    }
    
    /**
     * Get next model prediction for Again button label
     * 
     * @return array Array with next model info or error
     */
    public static function getNextModel(): array {
        $retArr = ["error" => "", "success" => false];
        
        // Get message ID from request
        $messageId = isset($_REQUEST['messageId']) ? intval($_REQUEST['messageId']) : 0;
        
        if (!$messageId) {
            $retArr["error"] = "Message ID required";
            return $retArr;
        }
        
        try {
            // Get original message details
            $originalMessageSQL = "SELECT * FROM BMESSAGES WHERE BID = " . intval($messageId) . " AND BDIRECT = 'OUT'";
            $originalMessageRes = db::Query($originalMessageSQL);
            $originalMessage = db::FetchArr($originalMessageRes);
            
            if (!$originalMessage) {
                $retArr["error"] = "Message not found";
                return $retArr;
            }
            
            // Get original model details
            $originalModel = AgainLogic::getOriginalModelFromMessage($messageId);
            if (!$originalModel) {
                $retArr["error"] = "Could not determine original model";
                return $retArr;
            }
            
            // Get next best model using topic-aware Round-Robin logic
            $topic = 'chat'; // Default for getNextModel API
            if (isset($_REQUEST['topic'])) {
                $topic = db::EscString($_REQUEST['topic']);
            }
            $nextModel = AgainLogic::getNextBestModel($originalModel['bid'], $originalMessage['BTRACKID'], null, $topic);
            
            if ($nextModel) {
                // Get proper display name - use BNAME if meaningful, otherwise provider
                if (!empty($nextModel['BNAME']) && $nextModel['BNAME'] !== 'chat' && strlen($nextModel['BNAME']) > 3) {
                    $displayName = $nextModel['BNAME'];
                } else {
                    $displayName = $nextModel['BPROVID']; // Use provider ID like "o3", "gpt-4.1"
                }
                
                $retArr["success"] = true;
                $retArr["next_model"] = [
                    'bid' => intval($nextModel['BID']),
                    'tag' => $displayName,
                    'provider' => $nextModel['BPROVID'],
                    'service' => $nextModel['BSERVICE']
                ];
            } else {
                $retArr["error"] = "No alternative model available";
            }
            
        } catch (Exception $e) {
            $retArr["error"] = "Failed to get next model: " . $e->getMessage();
        }
        
        return $retArr;
    }
    
    /**
     * Get model information for a specific message
     * 
     * @return array Array with model info or error
     */
    public static function getMessageModel(): array {
        $retArr = ["error" => "", "success" => false];
        
        // Get message ID from request
        $messageId = isset($_REQUEST['messageId']) ? intval($_REQUEST['messageId']) : 0;
        
        if (!$messageId) {
            $retArr["error"] = "Message ID required";
            return $retArr;
        }
        
        try {
            // Get AIMODEL and AISERVICE from BMESSAGEMETA
            $modelSQL = "SELECT BTOKEN, BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($messageId) . " AND BTOKEN IN ('AIMODEL', 'AISERVICE')";
            $modelRes = db::Query($modelSQL);
            
            $modelData = [];
            while ($row = db::FetchArr($modelRes)) {
                $modelData[$row['BTOKEN']] = $row['BVALUE'];
            }
            
            if (isset($modelData['AIMODEL'])) {
                // Get display name from BMODELS
                $modelDetailsSQL = "SELECT BTAG, BNAME, BPROVID FROM BMODELS WHERE BPROVID = '" . db::EscString($modelData['AIMODEL']) . "' LIMIT 1";
                $modelDetailsRes = db::Query($modelDetailsSQL);
                $modelDetails = db::FetchArr($modelDetailsRes);
                
                if ($modelDetails) {
                    // Use BNAME if it's meaningful and specific, otherwise use provider
                    if (!empty($modelDetails['BNAME']) && $modelDetails['BNAME'] !== 'chat' && strlen($modelDetails['BNAME']) > 3) {
                        $displayName = $modelDetails['BNAME'];
                    } else {
                        $displayName = $modelData['AIMODEL']; // Use actual provider
                    }
                } else {
                    $displayName = $modelData['AIMODEL'];
                }
                
                $retArr["success"] = true;
                $retArr["model"] = $displayName;
                $retArr["service"] = $modelData['AISERVICE'] ?? '';
            } else {
                $retArr["error"] = "Model information not found";
            }
            
        } catch (Exception $e) {
            $retArr["error"] = "Failed to get model info: " . $e->getMessage();
        }
        
        return $retArr;
    }
    
    /**
     * Get user avatar (Gravatar)
     */
    public static function getUserAvatar() {
        $email = '';
        
        if (isset($_SESSION["USERPROFILE"]["BMAIL"])) {
            $email = $_SESSION["USERPROFILE"]["BMAIL"];
        } elseif (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
            $email = 'widget@synaplan.com'; // Default for widget users
        }
        
        $defaultPath = __DIR__ . '/../up/avatars/default.png';
        
        if ($email) {
            $avatarPath = Gravatar::getCachedGravatar($email, 64);
            
            // If cached file exists, serve it
            if (strpos($avatarPath, 'up/avatars/') === 0) {
                $fullPath = __DIR__ . '/../' . $avatarPath;
                if (file_exists($fullPath)) {
                    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $contentType = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    header('Content-Type: ' . $contentType);
                    header('Cache-Control: public, max-age=604800');
                    readfile($fullPath);
                    return;
                }
            }
        }
        
        // Always serve default.png
        if (file_exists($defaultPath)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=604800');
            readfile($defaultPath);
        } else {
            // Create default if not exists
            Gravatar::createDefaultAvatar($defaultPath);
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=604800');
            readfile($defaultPath);
        }
    }
}	
