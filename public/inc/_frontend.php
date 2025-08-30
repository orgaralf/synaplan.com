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
            if (!$cArr || !is_array($cArr)) {
                continue;
            }
            
            if(isset($cArr['BFILETEXT']) && strlen($cArr['BFILETEXT']) > 64) {
                $cArr['BFILETEXT'] = substr($cArr['BFILETEXT'], 0, 64)."...";
            }
            
            // Get file metadata for this message
            if (isset($cArr['BID'])) {
                $metaSQL = "SELECT * FROM BMESSAGEMETA WHERE BMESSID = ".$cArr['BID']." AND BTOKEN = 'FILECOUNT' ORDER BY BID DESC LIMIT 1";
                $metaRes = DB::Query($metaSQL);
                $metaArr = DB::FetchArr($metaRes);
                if($metaArr && is_array($metaArr)) {
                    $cArr['FILECOUNT'] = intval($metaArr['BVALUE']);
                } else {
                    $cArr['FILECOUNT'] = 0;
                }
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
                if($fileArr && is_array($fileArr) && !empty($fileArr['BFILEPATH']) && !empty($fileArr['BFILETYPE'])) {
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
                $originalName = $_FILES['files']['name'][$i];
                
                if (!is_uploaded_file($tmpName)) {
                    $retArr['error'] .= "Invalid upload: ".$originalName."\n";
                    continue; // skip invalid upload
                }
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
        // SSE hardening
        if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        @ini_set('output_buffering', '0');
        @ini_set('display_errors', '0');        // log errors, don't print
        error_reporting(E_ALL);

        header('Content-Type: text/event-stream; charset=UTF-8');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');
        
        // Register cleanup function for unexpected exits
        register_shutdown_function(function() {
            self::cleanupAgainGlobals();
        });
        
        // Safe flush function
        $flush = function () {
            if (ob_get_level() > 0) { @ob_flush(); }
            @flush();
        };
        
        // Safe SSE send function
        $send = function(array $payload) use ($flush) {
            echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
            $flush();
        };
        
        // Read all needed session values into local variables before streaming
        $isWidget = isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true;
        $widgetOwnerId = isset($_SESSION["widget_owner_id"]) ? $_SESSION["widget_owner_id"] : null;
        $anonymousSessionId = isset($_SESSION["anonymous_session_id"]) ? $_SESSION["anonymous_session_id"] : null;
        $userProfile = isset($_SESSION["USERPROFILE"]) ? $_SESSION["USERPROFILE"] : null;
        
        // Determine user ID from session data
        if ($isWidget) {
            $userId = $widgetOwnerId;
        } else {
            $userId = $userProfile ? $userProfile["BID"] : null;
        }
        
        // Close session immediately to prevent session locks on multiple requests
        session_write_close();
        
        $fileCount = 0;
    
        // Check if this is an "Again" request
        $isAgainRequest = isset($_REQUEST['again']) && $_REQUEST['again'] == '1';
        
        if ($isAgainRequest) {
            try {
                // For Again requests, use explicit in_id or auto-resolve
                $inId = isset($_REQUEST['in_id']) ? intval($_REQUEST['in_id']) : null;
                
                if (!$inId) {
                    $inId = self::getLastInMessageIdForCurrentContextLocal($isWidget, $widgetOwnerId, $anonymousSessionId, $userProfile);
                }
                
                if ($inId <= 0) {
                    $send(['status' => 'error', 'message' => 'No previous IN message found for Again request', 'timestamp' => time()]);
                    exit;
                }
                
                // Validate the IN message
                $msgArr = Central::getMsgById($inId);
                if (!$msgArr || $msgArr['BDIRECT'] !== 'IN') {
                    $send(['status' => 'error', 'message' => 'Invalid IN message ID for Again request', 'timestamp' => time()]);
                    exit;
                }
                
                $lastIds = [$inId];
            } catch (\Throwable $e) {
                error_log("SSE Again request error: " . $e->getMessage());
                $send(['status' => 'error', 'message' => 'Again request failed: ' . $e->getMessage(), 'timestamp' => time()]);
                exit;
            }
        } else {
            // For normal requests, validate lastIds parameter exists
            if (!isset($_REQUEST['lastIds']) || empty($_REQUEST['lastIds'])) {
                $send(['status' => 'error', 'message' => 'Missing lastIds parameter', 'timestamp' => time()]);
                exit;
            }
    
        $lastIds = explode(",", $_REQUEST['lastIds']);
        //error_log("LASTIDS: ". print_r($lastIds, true));

            if (!is_array($lastIds) || empty($lastIds)) {
                $send(['status' => 'error', 'message' => 'Invalid ID list format', 'timestamp' => time()]);
            exit;
        }
        }
        
        // Handle "Again" functionality - model override
        $modelId = isset($_REQUEST['model_id']) ? intval($_REQUEST['model_id']) : null;
        $selectedModel = null;
        
        if ($isAgainRequest) {
            try {
                // Handle "Again" requests with or without specific model_id
                if ($modelId) {
                    // Validate and load the specified model
                    $modelSQL = "SELECT * FROM BMODELS WHERE BID = " . $modelId . " AND BSELECTABLE = 1 LIMIT 1";
                    $modelRes = db::Query($modelSQL);
                    $selectedModel = db::FetchArr($modelRes);
                    
                    if (!$selectedModel) {
                        error_log("SSE Again: Model ID $modelId not found or not selectable");
                    }
                } else {
                    // Auto-select using AgainLogic
                    try {
                        $inId = $lastIds[0];
                        $msgArr = Central::getMsgById($inId);
                        $replayUserId = $msgArr ? $msgArr['BUSERID'] : ($userProfile ? $userProfile["BID"] : 0);
                        
                        $replay = AgainLogic::replay($inId, null, $replayUserId);
                        $selectedModel = $replay['selectedModel'];
                    } catch (\Throwable $e) {
                        error_log("SSE Again auto-select error: " . $e->getMessage());
                        $selectedModel = null;
                    }
                }
                
                if (!$selectedModel) {
                    $send(['status' => 'error', 'message' => 'Invalid model ID or model not selectable', 'meta' => ['isAgain' => true], 'timestamp' => time()]);
                    self::cleanupAgainGlobals();
                    exit;
                }
            } catch (\Throwable $e) {
                error_log("SSE Again model selection error: " . $e->getMessage());
                $send(['status' => 'error', 'message' => 'Model selection failed: ' . $e->getMessage(), 'meta' => ['isAgain' => true], 'timestamp' => time()]);
                self::cleanupAgainGlobals();
                exit;
            }
            
            // Validate BTAG matches the context using AgainLogic
            try {
                $inId = $lastIds[0];
                $msgArr = Central::getMsgById(intval($inId));
                $btagUserId = $msgArr ? $msgArr['BUSERID'] : ($userProfile ? $userProfile["BID"] : 0);
                
                // Get the last OUT message for proper BTAG resolution
                $lastOut = AgainLogic::getLastOutForIn($inId);
                
                // Use AgainLogic to determine the correct BTAG for this context
                $expectedBtag = AgainLogic::resolveTagForReplay($inId, $lastOut);
                
                // Check if selected model BTAG matches expected BTAG
                if ($selectedModel['BTAG'] !== $expectedBtag) {
                    error_log("SSE Again BTAG mismatch: Model BTAG ({$selectedModel['BTAG']}) vs Expected BTAG ($expectedBtag)");
                    $send([
                        'status' => 'error', 
                        'message' => "Model BTAG mismatch: Selected model '{$selectedModel['BNAME']}' has BTAG '{$selectedModel['BTAG']}' but expected BTAG '$expectedBtag' for this context",
                        'meta' => ['btag' => $expectedBtag, 'selectedBtag' => $selectedModel['BTAG'], 'isAgain' => true], 
                        'timestamp' => time()
                    ]);
                    self::cleanupAgainGlobals();
                    exit;
                }
            } catch (\Throwable $e) {
                // If BTAG validation fails, send error instead of allowing any model
                error_log("SSE Again BTAG validation failed: " . $e->getMessage());
                $send([
                    'status' => 'error', 
                    'message' => 'Unable to validate model compatibility for this request context',
                    'timestamp' => time()
                ]);
                self::cleanupAgainGlobals();
                exit;
            }
            
            // Validate service class exists before proceeding
            $forcedServiceClass = "AI" . $selectedModel['BSERVICE'];
            if (!class_exists($forcedServiceClass)) {
                $send(['status' => 'error', 'message' => 'Model service not available: ' . $forcedServiceClass, 'meta' => ['isAgain' => true], 'timestamp' => time()]);
                self::cleanupAgainGlobals();
                exit;
            }
            
            // Set temporary globals for Again bypass (no permanent mutations)
            $GLOBALS["FORCE_AI_MODEL"] = true;
            $GLOBALS["IS_AGAIN"] = true;
            $GLOBALS["FORCED_AI_SERVICE"] = $forcedServiceClass;
            // Use BNAME if BPROVID is empty
            $GLOBALS["FORCED_AI_MODEL"] = !empty($selectedModel['BPROVID']) ? $selectedModel['BPROVID'] : $selectedModel['BNAME'];
            $GLOBALS["FORCED_AI_MODELID"] = $selectedModel['BID'];
            $GLOBALS["FORCED_AI_BTAG"] = $selectedModel['BTAG'];
            
            // For Again requests, ignore promptId to avoid going through sorter
            unset($_REQUEST['promptId']);
            unset($_GET['promptId']);
            unset($_POST['promptId']);
        }
        
        // Send starting heartbeat frame first
        $send([
            'msgId' => "START_".$lastIds[0],
            'status' => 'starting',
            'message' => 'Starting',
            'timestamp' => time()
        ]);
        

        //error_log("START: ". print_r($_REQUEST, true));

        // for each id, get the message
        foreach($lastIds as $msgId) {
            $msgArr = Central::getMsgById(intval($msgId));
            if($msgArr['BFILE'] > 0) {
                $msgArr = Central::parseFile($msgArr, true);
            } else {
                $send([
                    'msgId' => $msgId,
                    'status' => 'pre_processing',
                    'message' => 'Processing message '.$msgId.'. ',
                    'timestamp' => time()
                ]);
            }
        }
        // ------------------------------------------------------------        
        // ------------------------------------------------------------
        $send([
            'msgId' => $msgId,
            'status' => 'pre_processing',
            'message' => 'Finished pre-processing message(s). ',
            'timestamp' => time()
        ]);
        // ------------------------------------------------------------
        // now work on the message itself, sort it and process it
                try {
        $aiLastId = self::createAnswer($msgId);
        } catch (\Throwable $e) {
            error_log("SSE createAnswer failed: " . $e->getMessage());
            $send(['status' => 'error', 'message' => 'Message processing failed: ' . $e->getMessage(), 'timestamp' => time()]);
            self::cleanupAgainGlobals();
            exit;
        }
        
        // Prepare final SSE payload with meta
        $finalPayload = [
            'msgId' => $msgId,
            'status' => 'done',
            'message' => 'That should end the stream. ',
            'timestamp' => time()
        ];
        
        // Add comprehensive meta information with error handling
        try {
            if ($isAgainRequest) {
                $inId = $lastIds[0];
                
                if ($selectedModel) {
                    // Use selected model info
                    $service = $selectedModel['BSERVICE'];
                    $model = $selectedModel['BNAME'];
                    $btag = $selectedModel['BTAG'];
                    $currentModelId = $selectedModel['BID'];
                } else {
                    // Auto-select was already done above, use the selectedModel 
                    if ($selectedModel) {
                        $service = $selectedModel['BSERVICE'];
                        $model = $selectedModel['BNAME'];
                        $btag = $selectedModel['BTAG'];
                        $currentModelId = $selectedModel['BID'];
                    } else {
                        // Fallback to chat if auto-select failed
                        $btag = 'chat';
                        $service = 'Unknown';
                        $model = 'unknown';
                        $currentModelId = 0;
                    }
                }
                
                // Get track ID for debugging (reuse $msgArr if available)
                if (!isset($msgArr)) {
                    $msgArr = Central::getMsgById($inId);
                }
                $trackId = $msgArr ? $msgArr['BTRACKID'] : null;
                
                // Get eligible models and predicted next with error handling
                $eligible = [];
                $predictedNext = null;
                try {
                    $eligible = self::getEligibleModels($btag);
                    
                    // If no eligible models found, try fallback from BCONFIG
                    if (empty($eligible)) {
                        $fallbackModel = self::getFallbackModel($btag);
                        if ($fallbackModel) {
                            $eligible = [$fallbackModel];
                        } else {
                            error_log("No eligible models found for BTAG: $btag (Again request)");
                        }
                    }
                    
                    $predictedNext = self::getPredictedNext($eligible, $currentModelId);
                } catch (\Throwable $e) {
                    error_log("Error getting model lists: " . $e->getMessage());
                }
                
                // Get full OUT message information if available
                $outId = isset($aiLastId) && $aiLastId > 0 ? $aiLastId : null;
                $filePath = '';
                $fileType = '';
                $outText = '';
                
                if ($outId) {
                    $outSQL = "SELECT BTEXT, BFILEPATH, BFILETYPE FROM BMESSAGES WHERE BID = " . intval($outId) . " LIMIT 1";
                    $outRes = db::Query($outSQL);
                    $outRow = db::FetchArr($outRes);
                    if ($outRow) {
                        $outText = $outRow['BTEXT'] ?: '';
                        $filePath = $outRow['BFILEPATH'] ?: '';
                        $fileType = $outRow['BFILETYPE'] ?: '';
                    }
                }
                
                // Normalize service name for logo compatibility
                $normalizedService = $service;
                if (strtolower($service) === 'open') {
                    $normalizedService = 'OpenAI';
                }
                
                $finalPayload['meta'] = [
                    'service' => $normalizedService,
                    'model' => $model,
                    'btag' => $btag,
                    'isAgain' => true,
                    'inId' => $inId,
                    'trackId' => $trackId,
                    'outId' => $outId,
                    'filePath' => $filePath,
                    'fileType' => $fileType,
                    'predictedNext' => $predictedNext,
                    'eligible' => $eligible
                ];
            } else {
            // For normal processing, get actual model info from database
            $inId = $lastIds[0];
            
            // Get track ID for debugging
            $msgArr = Central::getMsgById($inId);
            $trackId = $msgArr ? $msgArr['BTRACKID'] : null;
            
            // Get actual AI service and model from BMESSAGEMETA
            $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($inId) . " AND BTOKEN = 'AISERVICE' ORDER BY BID DESC LIMIT 1";
            $serviceRes = db::Query($serviceSQL);
            $serviceRow = db::FetchArr($serviceRes);
            $currentService = $serviceRow ? str_replace("AI", "", $serviceRow['BVALUE']) : "Unknown";
            
            $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($inId) . " AND BTOKEN = 'AIMODEL' ORDER BY BID DESC LIMIT 1";
            $modelRes = db::Query($modelSQL);
            $modelRow = db::FetchArr($modelRes);
            $currentModel = $modelRow ? $modelRow['BVALUE'] : "unknown";
            
            $modelIdSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($inId) . " AND BTOKEN = 'AIMODELID' ORDER BY BID DESC LIMIT 1";
            $modelIdRes = db::Query($modelIdSQL);
            $modelIdRow = db::FetchArr($modelIdRes);
            $currentModelId = $modelIdRow ? intval($modelIdRow['BVALUE']) : 0;
            
            // Determine BTAG dynamically from the message context
            $currentBtag = "chat"; // Default fallback
            try {
                // Get the last OUT message for proper BTAG resolution
                $lastOut = AgainLogic::getLastOutForIn($inId);
                
                // Resolve BTAG using AgainLogic with proper lastOut context
                $resolvedBtag = AgainLogic::resolveTagForReplay($inId, $lastOut);
                if (!empty($resolvedBtag)) {
                    $currentBtag = $resolvedBtag;
                }
            } catch (\Throwable $e) {
                error_log("Error resolving BTAG for normal request, using default 'chat': " . $e->getMessage());
            }
            
            // Get eligible models and predicted next with error handling
            $eligible = [];
            $predictedNext = null;
            try {
                $eligible = self::getEligibleModels($currentBtag);
                
                // If no eligible models found, try fallback from BCONFIG
                if (empty($eligible)) {
                    $fallbackModel = self::getFallbackModel($currentBtag);
                    if ($fallbackModel) {
                        $eligible = [$fallbackModel];
                    } else {
                        error_log("No eligible models found for BTAG: $currentBtag");
                    }
                }
                
                $predictedNext = $currentModelId > 0 ? self::getPredictedNext($eligible, $currentModelId) : (isset($eligible[0]) ? $eligible[0] : null);
            } catch (\Throwable $e) {
                error_log("Error getting model lists: " . $e->getMessage());
            }
            
            // Get full OUT message information if available
            $outId = isset($aiLastId) && $aiLastId > 0 ? $aiLastId : null;
            $filePath = '';
            $fileType = '';
            $outText = '';
            
            if ($outId) {
                $outSQL = "SELECT BTEXT, BFILEPATH, BFILETYPE FROM BMESSAGES WHERE BID = " . intval($outId) . " LIMIT 1";
                $outRes = db::Query($outSQL);
                $outRow = db::FetchArr($outRes);
                if ($outRow) {
                    $outText = $outRow['BTEXT'] ?: '';
                    $filePath = $outRow['BFILEPATH'] ?: '';
                    $fileType = $outRow['BFILETYPE'] ?: '';
                }
            }
            
            // Normalize service name for logo compatibility
            $normalizedService = $currentService;
            if (strtolower($currentService) === 'open') {
                $normalizedService = 'OpenAI';
            }
            
            $finalPayload['meta'] = [
                'service' => $normalizedService,
                'model' => $currentModel,
                'btag' => $currentBtag,
                'isAgain' => false,
                'inId' => $inId,
                'trackId' => $trackId,
                'outId' => $outId,
                'filePath' => $filePath,
                'fileType' => $fileType,
                'predictedNext' => $predictedNext,
                'eligible' => $eligible
            ];
        }
        } catch (\Throwable $e) {
            error_log("Error creating SSE meta: " . $e->getMessage());
            // Minimal meta on error
            $finalPayload['meta'] = [
                'service' => 'Unknown',
                'model' => 'Unknown',
                'btag' => 'chat',
                'isAgain' => $isAgainRequest,
                'inId' => $lastIds[0] ?? 0,
                'trackId' => null,
                'predictedNext' => null,
                'eligible' => []
            ];
        }
        
        // Add OUT message text if available (overrides default message)
        if (isset($outText) && !empty($outText)) {
            $finalPayload['message'] = $outText;
        }
        
        $send($finalPayload);
        
        // Global cleanup after streaming
        self::cleanupAgainGlobals();
        
        exit;
    }
    
    /**
     * Get the last IN message ID for the current context (user or widget)
     */
    public static function getLastInMessageIdForCurrentContext(): int {
        $isWidget = isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true;

        if ($isWidget) {
            if (!isset($_SESSION["anonymous_session_id"]) || !isset($_SESSION["widget_owner_id"])) {
                return 0;
            }
            $userId = intval($_SESSION["widget_owner_id"]);
            $trackId = crc32($_SESSION["anonymous_session_id"]);
            $sql = "SELECT BID FROM BMESSAGES
                    WHERE BUSERID = {$userId} AND BDIRECT = 'IN' AND BTRACKID = {$trackId}
                    ORDER BY BID DESC LIMIT 1";
        } else {
            if (!isset($_SESSION["USERPROFILE"]["BID"])) {
                return 0;
            }
            $userId = intval($_SESSION["USERPROFILE"]["BID"]);
            $sql = "SELECT BID FROM BMESSAGES
                    WHERE BUSERID = {$userId} AND BDIRECT = 'IN'
                    ORDER BY BID DESC LIMIT 1";
        }
        
        $res = DB::Query($sql);
        $row = DB::FetchArr($res);
        return $row ? intval($row['BID']) : 0;
    }
    
    /**
     * Get the last IN message ID for the current context using local variables (SSE-safe)
     */
    public static function getLastInMessageIdForCurrentContextLocal(bool $isWidget, ?int $widgetOwnerId, ?string $anonymousSessionId, ?array $userProfile): int {
        if ($isWidget) {
            if (!$anonymousSessionId || !$widgetOwnerId) {
                return 0;
            }
            $userId = intval($widgetOwnerId);
            $trackId = crc32($anonymousSessionId);
            $sql = "SELECT BID FROM BMESSAGES
                    WHERE BUSERID = {$userId} AND BDIRECT = 'IN' AND BTRACKID = {$trackId}
                    ORDER BY BID DESC LIMIT 1";
        } else {
            if (!$userProfile || !isset($userProfile["BID"])) {
                return 0;
            }
            $userId = intval($userProfile["BID"]);
            $sql = "SELECT BID FROM BMESSAGES
                    WHERE BUSERID = {$userId} AND BDIRECT = 'IN'
                    ORDER BY BID DESC LIMIT 1";
        }
        
        $res = DB::Query($sql);
        $row = DB::FetchArr($res);
        return $row ? intval($row['BID']) : 0;
    }

    /**
     * Get eligible models for SSE meta (using AgainLogic)
     */
    private static function getEligibleModels(string $btag): array {
        $models = AgainLogic::getEligibleModels($btag);
        
        // Convert to the format expected by SSE meta
        $result = [];
        foreach ($models as $model) {
            $result[] = [
                'model_id' => intval($model['BID']),
                'service' => $model['BSERVICE'],
                // Use BPROVID if available, fallback to BNAME
                'model' => !empty($model['BPROVID']) ? $model['BPROVID'] : $model['BNAME']
            ];
        }
        
        return $result;
    }
    
    /**
     * Get predicted next model using AgainLogic round-robin
     */
    private static function getPredictedNext(array $eligible, int $currentModelId): ?array {
        if (empty($eligible)) {
            return null;
        }
        
        // Convert eligible back to AgainLogic format
        $eligibleForLogic = [];
        foreach ($eligible as $model) {
            $eligibleForLogic[] = [
                'BID' => $model['model_id'],
                'BSERVICE' => $model['service'],
                'BPROVID' => $model['model'],
                'BTAG' => '', // Not needed for pickModel logic
                'BNAME' => $model['model'] // In case needed
            ];
        }
        
        // Use AgainLogic to pick the next model
        try {
            $nextModel = AgainLogic::pickModel($eligibleForLogic, $currentModelId);
            
            // Convert back to SSE meta format
            return [
                'model_id' => intval($nextModel['BID']),
                'service' => $nextModel['BSERVICE'],
                // Use BPROVID if available, fallback to BNAME
                'model' => !empty($nextModel['BPROVID']) ? $nextModel['BPROVID'] : $nextModel['BNAME']
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get fallback model from BCONFIG when no eligible models found
     */
    private static function getFallbackModel(string $btag): ?array {
        try {
            // Map BTAG to BSETTING for BCONFIG lookup
            $btagToSetting = [
                'chat' => 'CHAT',
                'text2pic' => 'TEXT2PIC',
                'text2vid' => 'TEXT2VID',
                'text2audio' => 'TEXT2SOUND',
                'text2sound' => 'TEXT2SOUND',
                'pic2text' => 'PIC2TEXT',
                'sound2text' => 'SOUND2TEXT'
            ];
            
            $bsetting = $btagToSetting[$btag] ?? null;
            if (!$bsetting) {
                error_log("No BCONFIG mapping found for BTAG: $btag");
                return null;
            }
            
            // Get current user ID for scope resolution
            $userId = null;
            if (isset($_SESSION['USERPROFILE']['BID'])) {
                $userId = intval($_SESSION['USERPROFILE']['BID']);
            }
            
            // Try user-specific default first, then global fallback
            $defaultModelId = null;
            
            if ($userId > 0) {
                // Try user-specific scope first
                $userConfigSQL = "SELECT BVALUE FROM BCONFIG 
                                  WHERE BGROUP = 'DEFAULTMODEL' 
                                  AND BSETTING = '" . DB::EscString($bsetting) . "' 
                                  AND BOWNERID = " . $userId . " 
                                  LIMIT 1";
                $userConfigRes = DB::Query($userConfigSQL);
                $userConfigRow = DB::FetchArr($userConfigRes);
                
                if ($userConfigRow && is_array($userConfigRow) && !empty($userConfigRow['BVALUE'])) {
                    $defaultModelId = intval($userConfigRow['BVALUE']);
                }
            }
            
            // Fallback to global default (BOWNERID=0)
            if (!$defaultModelId) {
                $globalConfigSQL = "SELECT BVALUE FROM BCONFIG 
                                   WHERE BGROUP = 'DEFAULTMODEL' 
                                   AND BSETTING = '" . DB::EscString($bsetting) . "' 
                                   AND BOWNERID = 0 
                                   LIMIT 1";
                $globalConfigRes = DB::Query($globalConfigSQL);
                $globalConfigRow = DB::FetchArr($globalConfigRes);
                
                if ($globalConfigRow && is_array($globalConfigRow) && !empty($globalConfigRow['BVALUE'])) {
                    $defaultModelId = intval($globalConfigRow['BVALUE']);
                }
            }
            
            if (!$defaultModelId) {
                error_log("No default model configured for BSETTING: $bsetting (user: $userId, global fallback tried)");
                return null;
            }
            
            // Get the default model from BMODELS
            $modelSQL = "SELECT * FROM BMODELS WHERE BID = " . $defaultModelId . " LIMIT 1";
            $modelRes = DB::Query($modelSQL);
            $modelRow = DB::FetchArr($modelRes);
            
            if (!$modelRow || !is_array($modelRow)) {
                error_log("Default model ID $defaultModelId not found in BMODELS");
                return null;
            }
            
            // Verify BTAG consistency (optional validation)
            if ($modelRow['BTAG'] !== $btag) {
                error_log("Warning: Default model BTAG ({$modelRow['BTAG']}) does not match requested BTAG ($btag), but using anyway");
            }
            
            // Convert to SSE meta format (use BPROVID consistently for model names)
            return [
                'model_id' => intval($modelRow['BID']),
                'service' => $modelRow['BSERVICE'],
                // Use BPROVID if available, fallback to BNAME
                'model' => !empty($modelRow['BPROVID']) ? $modelRow['BPROVID'] : $modelRow['BNAME']
            ];
            
        } catch (\Throwable $e) {
            error_log("Error getting fallback model: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up Again-related global variables
     */
    private static function cleanupAgainGlobals(): void {
        $globalsToClean = [
            'FORCE_AI_MODEL', 'IS_AGAIN', 'FORCED_AI_SERVICE', 
            'FORCED_AI_MODEL', 'FORCED_AI_MODELID', 'FORCED_AI_BTAG'
        ];
        
        foreach ($globalsToClean as $key) {
            if (isset($GLOBALS[$key])) {
                unset($GLOBALS[$key]);
            }
        }
    }
    
    // ********************************************** process the message **********************************************
    /**
     * Process the message
     * 
     */
    public static function createAnswer($msgId) {
        try {
        // Process the message using the provided message ID
            $statusMessage = (isset($GLOBALS["IS_AGAIN"]) && $GLOBALS["IS_AGAIN"] === true) 
                ? 'Direct run (Again)...' 
                : 'Sorting. ';
                
        $update = [
            'msgId' => $msgId,
            'status' => 'pre_processing',
                'message' => $statusMessage
        ];
        self::printToStream($update);
        // error_log("createAnswer: ".print_r(ProcessMethods::$msgArr, true));
        
        ProcessMethods::init($msgId, true);
        } catch (\Throwable $e) {
            error_log("createAnswer init failed: " . $e->getMessage());
            throw $e; // Re-throw to be caught by caller
        }

        // Handle file translation if needed
        // todo: check the config of the user, if he wants it in English as well!
        // ProcessMethods::fileTranslation();
        
        // Retrieve and process message thread
        $timeSeconds = 1200;
        ProcessMethods::$threadArr = Central::getThread(ProcessMethods::$msgArr, $timeSeconds);

        // Again bypass: use BTAG-based dispatch instead of direct chat generation
        if (isset($GLOBALS["IS_AGAIN"]) && $GLOBALS["IS_AGAIN"] === true) {
            try {
                // Get the BTAG from forced globals (set during Again request processing)
                $btag = isset($GLOBALS["FORCED_AI_BTAG"]) ? $GLOBALS["FORCED_AI_BTAG"] : 'chat';
                
                // Use BTAG-based dispatch to call the correct generator function
                ProcessMethods::dispatchByBTag($btag);
                
                self::printToStream([
                    'msgId' => $msgId,
                    'status' => 'pre_processing',
                    'message' => 'Again processing completed.'
                ]);
                
            } catch (\Throwable $e) {
                error_log("Again processing error: " . $e->getMessage());
                self::printToStream([
                    'msgId' => $msgId,
                    'status' => 'error',
                    'message' => 'Error during Again processing: ' . $e->getMessage()
                ]);
                return;
            }
        } else {
            try {
        // Sort and process the message (sort is calling the processor to split tools and topics)
        ProcessMethods::sortMessage();
            } catch (\Throwable $e) {
                error_log("sortMessage error: " . $e->getMessage());
                self::printToStream([
                    'msgId' => $msgId,
                    'status' => 'error',
                    'message' => 'Error during sorting: ' . $e->getMessage()
                ]);
                return 0;
            }
        }

        // Prepare AI answer for database storage
        try {
        $aiLastId = ProcessMethods::saveAnswerToDB();
            
            self::printToStream([
                'msgId' => $msgId,
                'status' => 'pre_processing',
                'message' => 'Answer saved to database.'
            ]);

        return $aiLastId;
        } catch (\Throwable $e) {
            error_log("saveAnswerToDB error: " . $e->getMessage());
            self::printToStream([
                'msgId' => $msgId,
                'status' => 'error',
                'message' => 'Error saving to database: ' . $e->getMessage()
            ]);
            return 0;
        }
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
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) { @ob_flush(); }
        @flush();
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
            if($fileArr && is_array($fileArr) && !empty($fileArr['BFILEPATH']) && !empty($fileArr['BFILETYPE'])) {
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
                // Fetch AI service and model information for AI messages, and SYSTEMTEXT for all messages
                $aiService = '';
                $aiModel = '';
                $systemText = '';
                
                if($chat['BDIRECT'] == 'OUT') {
                    $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AISERVICE' ORDER BY BID DESC LIMIT 1";
                    $serviceRes = DB::Query($serviceSQL);
                    $serviceArr = DB::FetchArr($serviceRes);
                    if($serviceArr && is_array($serviceArr)) {
                        $aiService = $serviceArr['BVALUE'];
                    }
                    
                    $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AIMODEL' ORDER BY BID DESC LIMIT 1";
                    $modelRes = DB::Query($modelSQL);
                    $modelArr = DB::FetchArr($modelRes);
                    if($modelArr && is_array($modelArr)) {
                        $aiModel = $modelArr['BVALUE'];
                    }
                }
                
                // Fetch SYSTEMTEXT for all messages (both IN and OUT)
                $systemSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'SYSTEMTEXT' ORDER BY BID DESC LIMIT 1";
                $systemRes = DB::Query($systemSQL);
                $systemArr = DB::FetchArr($systemRes);
                if($systemArr && is_array($systemArr)) {
                    $systemText = $systemArr['BVALUE'];
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
                    'SYSTEMTEXT' => $systemText
                ];
                
                // Process display text for AI messages
                if($chat['BDIRECT'] == 'OUT') {
                    // Always preserve the original BTEXT as displayText
                    $displayText = $chat['BTEXT'];
                    
                    $hasFile = ($chat['BFILE'] > 0 && !empty($chat['BFILETYPE']) && !empty($chat['BFILEPATH']) && strpos($chat['BFILEPATH'], '/') !== false);
                    
                    // If there's a file and BTEXT is not empty, optionally append file type info
                    if ($hasFile && !empty($displayText)) {
                        $fileTypeLabel = '';
                        if ($chat['BFILETYPE'] == 'mp4' || $chat['BFILETYPE'] == 'webm') {
                            $fileTypeLabel = 'Video';
                        } elseif (in_array($chat['BFILETYPE'], ['png', 'jpg', 'jpeg', 'gif'])) {
                            $fileTypeLabel = 'Image';
                        } else {
                            $fileTypeLabel = 'File';
                        }
                        
                        // Only append file type if BTEXT doesn't already mention it
                        if (!empty($fileTypeLabel) && stripos($displayText, $fileTypeLabel) === false) {
                            $displayText .= " ($fileTypeLabel)";
                        }
                    }
                    // If BTEXT is empty but there's a file, use the file type as fallback
                    elseif ($hasFile && empty($displayText)) {
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
            if (!$row || !is_array($row) || !isset($row['BGROUP']) || !isset($row['BSETTING']) || !isset($row['BVALUE'])) {
                continue;
            }
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
            if (!$row || !is_array($row) || !isset($row['BSETTING']) || !isset($row['BVALUE'])) {
                continue;
            }
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
            if (!$row || !is_array($row) || !isset($row['BVALUE'])) {
                continue;
            }
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
        while($row = DB::FetchArr($res)) { 
            if ($row && is_array($row)) {
                $rows[] = $row; 
            }
        }
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
}	
