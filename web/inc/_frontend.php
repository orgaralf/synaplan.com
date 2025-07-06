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
        $userId = $_SESSION["USERPROFILE"]["BID"];
        
        // Get messages with a larger limit to account for potential grouping
        $cSQL = "SELECT * FROM BMESSAGES WHERE BUSERID = ".$userId." ORDER BY BID DESC LIMIT ".($myLimit);
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
        $userId = $_SESSION["USERPROFILE"]["BID"];
        
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
        $userId = $_SESSION["USERPROFILE"]["BID"];
        $fileCount = 0;
        // take the files uploaded into a new array
        $filesArr = [];
        
        $inMessageArr['BUNIXTIMES'] = time();
        $inMessageArr['BDATETIME'] = (string) date("YmdHis");
        $inMessageArr['BTRACKID'] = (int) (microtime(true) * 1000000);

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

                if(Central::checkMimeTypes($fileExtension, $fileType)) {
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
                    /*
                        $inMessageArr['BFILEPATH'] = $fileRelPath;
                        $inMessageArr['BFILETYPE'] = $fileType;
                        $inMessageArr['BFILE'] = 1;
                    */
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
        $inMessageArr['BUSERID'] = $_SESSION["USERPROFILE"]["BID"];
        
        $cleanPost = Tools::turnURLencodedIntoUTF8($_REQUEST['message']);
        //error_log("****************************** CLEANPOST: " . $cleanPost);

        $inMessageArr['BTEXT'] = DB::EscString(trim(strip_tags($cleanPost)));
        // --
        $convArr = Central::searchConversation($inMessageArr);
        
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
        $userId = $_SESSION["USERPROFILE"]["BID"];
        
        // Get the group key from POST data
        $groupKey = isset($_REQUEST['groupKey']) ? trim(db::EscString($_REQUEST['groupKey'])) : 'DEFAULT';
        
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

                if(Central::checkMimeTypes($fileExtension, $fileType)) {
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
        error_log("* * * * * * ************** _________ PROCESSING RAG FILES: ".print_r($filesArr, true));
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
        $userId = $_SESSION["USERPROFILE"]["BID"];
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
        self::createAnswer($msgId);
        
        $update = [
            'msgId' => $msgId,
            'status' => 'done',
            'message' => 'That should end the stream. '
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

    /**
     * Update user profile data
     * 
     * Updates user profile information including personal details, company info, and address
     * All additional data is stored in the BUSERDETAILS JSON column
     * 
     * @return array Success status and message
     */
    public static function updateProfile(): array {
        $retArr = ["error" => "", "success" => false];
        
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            $retArr["error"] = "User not logged in";
            return $retArr;
        }
        
        $userId = intval($_SESSION["USERPROFILE"]["BID"]);
        
        // Get current user details
        $sql = "SELECT BUSERDETAILS, BPW FROM BUSER WHERE BID = " . $userId;
        $res = DB::Query($sql);
        $userArr = DB::FetchArr($res);
        
        if (!$userArr) {
            $retArr["error"] = "User not found";
            return $retArr;
        }
        
        // Parse existing BUSERDETAILS or create empty array
        $userDetails = [];
        if (!empty($userArr['BUSERDETAILS'])) {
            $userDetails = json_decode($userArr['BUSERDETAILS'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $userDetails = [];
            }
        }
        
        // TODO: Implement the actual profile update logic here
        // This is the empty method as requested - you can fill it later
        
        // Example structure for when you implement it:
        /*
        // Update profile fields from POST data
        $userDetails['firstName'] = DB::EscString($_REQUEST['firstName'] ?? '');
        $userDetails['lastName'] = DB::EscString($_REQUEST['lastName'] ?? '');
        $userDetails['phone'] = DB::EscString($_REQUEST['phone'] ?? '');
        $userDetails['companyName'] = DB::EscString($_REQUEST['companyName'] ?? '');
        $userDetails['vatId'] = DB::EscString($_REQUEST['vatId'] ?? '');
        $userDetails['street'] = DB::EscString($_REQUEST['street'] ?? '');
        $userDetails['zipCode'] = DB::EscString($_REQUEST['zipCode'] ?? '');
        $userDetails['city'] = DB::EscString($_REQUEST['city'] ?? '');
        $userDetails['country'] = DB::EscString($_REQUEST['country'] ?? '');
        $userDetails['language'] = DB::EscString($_REQUEST['language'] ?? '');
        $userDetails['timezone'] = DB::EscString($_REQUEST['timezone'] ?? '');
        $userDetails['invoiceEmail'] = DB::EscString($_REQUEST['invoiceEmail'] ?? '');
        
        // Handle password change if provided
        if (!empty($_REQUEST['currentPassword']) && !empty($_REQUEST['newPassword'])) {
            $currentPasswordMd5 = md5($_REQUEST['currentPassword']);
            if ($currentPasswordMd5 === $userArr['BPW']) {
                $newPasswordMd5 = md5($_REQUEST['newPassword']);
                // Update password in separate query
            } else {
                $retArr["error"] = "Current password is incorrect";
                return $retArr;
            }
        }
        
        // Encode updated details as JSON
        $userDetailsJson = json_encode($userDetails, JSON_UNESCAPED_UNICODE);
        
        // Update database
        $updateSql = "UPDATE BUSER SET BUSERDETAILS = '" . DB::EscString($userDetailsJson) . "'";
        if (isset($newPasswordMd5)) {
            $updateSql .= ", BPW = '" . $newPasswordMd5 . "'";
        }
        $updateSql .= " WHERE BID = " . $userId;
        
        $updateRes = DB::Query($updateSql);
        
        if ($updateRes) {
            $retArr["success"] = true;
            $retArr["message"] = "Profile updated successfully";
            
            // Update session data
            $_SESSION["USERPROFILE"]["BUSERDETAILS"] = $userDetailsJson;
        } else {
            $retArr["error"] = "Database update failed";
        }
        */
        
        // For now, just return a placeholder response
        $retArr["error"] = "Profile update method not yet implemented";
        
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
}	
