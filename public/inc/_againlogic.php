<?php
/**
 * Again Logic Class
 * 
 * Handles the "Again" functionality for AI message retries.
 * Implements global model ranking without user personalization.
 * 
 * @package AgainLogic
 */

class AgainLogic {
    
    /**
     * Get the next best model for retry using topic-aware Round-Robin logic
     * 
     * Enhanced Rules:
     * 1. Pool: Models suitable for the topic (chat, pic2text, text2pic, etc.)
     * 2. UsedSet: Already used retry models (BIDs) in current thread
     * 3. Pick: First candidate not in UsedSet, avoiding direct repetition
     * 4. Reset: When all candidates exhausted, restart cycle but avoid last used
     * 5. Override: If specific model requested, validate and use if available
     * 
     * @param int $originalModelId The original model ID to exclude initially
     * @param int $trackId Thread tracking ID for Round-Robin logic
     * @param int $overrideModelId Optional specific model ID to use instead of Round-Robin
     * @param string $topic Topic for model filtering (default: 'chat')
     * @return array|null Next best model details or null if none available
     */
    public static function getNextBestModel($originalModelId, $trackId = null, $overrideModelId = null, $topic = 'chat') {
        // Determine topic-specific model requirements
        $topicCategories = [
            'chat' => ['chat'],
            'general' => ['chat'], 
            'pic2text' => ['pic2text'],
            'text2pic' => ['text2pic'],
            'text2sound' => ['text2sound'],
            'text2vid' => ['text2vid'],
            'sound2text' => ['sound2text'],
            'analyzefile' => ['chat', 'pic2text'], // Can use both
            'mediamaker' => ['text2pic', 'text2vid', 'text2sound']
        ];
        
        $allowedCategories = $topicCategories[$topic] ?? ['chat']; // Default to chat models
        $categoryFilter = "'" . implode("','", $allowedCategories) . "'";
        
        // Get models suitable for this topic, ordered by global ranking
        $modelsSQL = "SELECT BID, BTAG, BNAME, BPROVID, BSERVICE, BQUALITY FROM BMODELS 
                     WHERE BSELECTABLE = 1 AND BTAG IN ($categoryFilter) 
                     ORDER BY BQUALITY DESC, BID ASC";
        $modelsRes = db::Query($modelsSQL);
        
        $modelPool = [];
        while ($model = db::FetchArr($modelsRes)) {
            $modelPool[] = $model;
        }
        
        if (empty($modelPool)) {
            return null;
        }
        
        // Handle model override - Override has ALWAYS priority if valid
        if ($overrideModelId) {
            foreach ($modelPool as $model) {
                if (intval($model['BID']) == intval($overrideModelId)) {
                    // Override always takes precedence if model is selectable
                    return $model;
                }
            }
            // Override model not found in selectable pool, fall back to Round-Robin
        }
        
        // Edge case: Only one selectable model and it's the original
        if (count($modelPool) == 1 && $modelPool[0]['BID'] == $originalModelId) {
            return null;
        }
        
        if (!$trackId) {
            // No thread context, just return first non-original
            foreach ($modelPool as $model) {
                if ($model['BID'] != $originalModelId) {
                    return $model;
                }
            }
            return null;
        }
        
        // Get already used retry models (BIDs) in this thread
        $usedModelBids = self::getUsedModelBids($trackId);
        
        // Get last used retry model to avoid direct repetition
        $lastUsedSQL = "SELECT CAST(BVALUE AS UNSIGNED) as MODEL_BID FROM BMESSAGEMETA 
                       WHERE BTOKEN = 'AGAIN_RETRY_MODEL' 
                       AND BMESSID IN (
                           SELECT BID FROM BMESSAGES WHERE BTRACKID = " . intval($trackId) . "
                       )
                       ORDER BY BID DESC LIMIT 1";
        $lastUsedRes = db::Query($lastUsedSQL);
        $lastUsedData = db::FetchArr($lastUsedRes);
        $lastUsedBid = $lastUsedData ? intval($lastUsedData['MODEL_BID']) : null;
        
        // Round-Robin Selection Logic
        
        // Phase 1: Try to find unused model (not in UsedSet)
        foreach ($modelPool as $model) {
            $modelBid = intval($model['BID']);
            
            // Skip if already used in this thread
            if (in_array($modelBid, $usedModelBids)) {
                continue;
            }
            
            // For first retry: exclude original model
            if (!empty($usedModelBids) || $modelBid != $originalModelId) {
                return $model;
            }
        }
        
        // Phase 2: All candidates exhausted - Reset cycle
        // Find first model that's not the last used (avoid direct repetition)
        foreach ($modelPool as $model) {
            $modelBid = intval($model['BID']);
            
            // Avoid direct repetition of last used model
            if ($modelBid != $lastUsedBid) {
                return $model;
            }
        }
        
        // Phase 3: Edge case - only one model or all are last used
        // Return first available (even if it's a repetition)
        return reset($modelPool);
    }
    
    /**
     * Check if a message can be "agained" (lock check with TTL)
     * 
     * @param int $messageId Message ID to check
     * @return array Status with 'allowed' boolean and 'error_code' if not allowed
     */
    public static function canAgainMessage($messageId) {
        // Check if message is already locked for again processing (with TTL 60s)
        $lockSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($messageId) . " AND BTOKEN = 'AGAIN_LOCK'";
        $lockRes = db::Query($lockSQL);
        $lockData = db::FetchArr($lockRes);
        
        if ($lockData) {
            $lockTime = intval($lockData['BVALUE']);
            $currentTime = time();
            
            // Check if lock is still valid (TTL 60 seconds)
            if ($currentTime - $lockTime < 60) {
                return ['allowed' => false, 'error_code' => 'LOCKED'];
            } else {
                // Lock expired, clean it up
                self::unlockMessage($messageId);
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Lock a message for again processing
     * 
     * @param int $messageId Message ID to lock
     * @return bool Success status
     */
    public static function lockMessage($messageId) {
        $lockSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($messageId) . ", 'AGAIN_LOCK', '" . time() . "')";
        return db::Query($lockSQL);
    }
    
    /**
     * Unlock a message (remove lock)
     * 
     * @param int $messageId Message ID to unlock
     * @return bool Success status
     */
    public static function unlockMessage($messageId) {
        $unlockSQL = "DELETE FROM BMESSAGEMETA WHERE BMESSID = " . intval($messageId) . " AND BTOKEN = 'AGAIN_LOCK'";
        return db::Query($unlockSQL);
    }
    
    /**
     * Mark original message as "agained" and store metadata
     * 
     * @param int $originalMessageId Original message ID
     * @param int $newMessageId New retry message ID
     * @param int $originalModelBid Original model BID
     * @param int $retryModelBid Retry model BID
     * @param string $retryModelProvider Retry model provider name (for UI tokens)
     * @param string $retryModelService Retry model service name (for UI tokens)
     * @return bool Success status
     */
    public static function markMessageAsAgained($originalMessageId, $newMessageId, $originalModelBid, $retryModelBid, $retryModelProvider, $retryModelService) {
        $timestamp = time();
        
        // Start transaction
        db::Query("START TRANSACTION");
        
        try {
            // Mark original message as agained
            $againStatusSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($originalMessageId) . ", 'AGAIN_STATUS', 'AGAINED')";
            db::Query($againStatusSQL);
            
            // Mark new message as retry
            $retryStatusSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($newMessageId) . ", 'AGAIN_STATUS', 'RETRY')";
            db::Query($retryStatusSQL);
            
            // Link new message to original
            $parentLinkSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($newMessageId) . ", 'AGAIN_PARENT_ID', '" . intval($originalMessageId) . "')";
            db::Query($parentLinkSQL);
            
            // Store retry model BID (canonical ID for Round-Robin logic)
            $retryModelSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($newMessageId) . ", 'AGAIN_RETRY_MODEL', '" . intval($retryModelBid) . "')";
            db::Query($retryModelSQL);
            
            // Increment again count for root of chain
            $rootMessageId = self::getRootMessageId($originalMessageId);
            $currentCountSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($rootMessageId) . " AND BTOKEN = 'AGAIN_COUNT'";
            $currentCountRes = db::Query($currentCountSQL);
            $currentCountData = db::FetchArr($currentCountRes);
            
            $newCount = $currentCountData ? intval($currentCountData['BVALUE']) + 1 : 1;
            
            if ($currentCountData) {
                $updateCountSQL = "UPDATE BMESSAGEMETA SET BVALUE = '" . $newCount . "' WHERE BMESSID = " . intval($rootMessageId) . " AND BTOKEN = 'AGAIN_COUNT'";
                db::Query($updateCountSQL);
            } else {
                $insertCountSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($rootMessageId) . ", 'AGAIN_COUNT', '" . $newCount . "')";
                db::Query($insertCountSQL);
            }
            
            // Store analytics data with model BIDs
            $analyticsData = json_encode([
                'outcome' => 'SUCCESS',
                'timestamp' => $timestamp,
                'original_model_bid' => $originalModelBid,
                'retry_model_bid' => $retryModelBid
            ]);
            $analyticsSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($originalMessageId) . ", 'AGAIN_ANALYTICS', '" . db::EscString($analyticsData) . "')";
            db::Query($analyticsSQL);
            
            // Commit transaction
            db::Query("COMMIT");
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            db::Query("ROLLBACK");
            error_log("AgainLogic::markMessageAsAgained failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the root message ID of an again chain
     * 
     * @param int $messageId Message ID to trace back
     * @return int Root message ID
     */
    private static function getRootMessageId($messageId) {
        $parentSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($messageId) . " AND BTOKEN = 'AGAIN_PARENT_ID'";
        $parentRes = db::Query($parentSQL);
        $parentData = db::FetchArr($parentRes);
        
        if ($parentData) {
            // Recursively find the root
            return self::getRootMessageId(intval($parentData['BVALUE']));
        }
        
        return $messageId; // This is the root
    }
    
    /**
     * Update cooldown timestamp for a message (No-Op - kept for compatibility)
     * 
     * @param int $messageId Message ID
     * @return bool Always true
     */
    public static function updateCooldown($messageId) {
        // No-Op: Cooldown functionality removed
        return true;
    }
    
    /**
     * Store error analytics for failed again attempts
     * 
     * @param int $messageId Message ID
     * @param string $errorCode Error code
     * @return bool Success status
     */
    public static function storeErrorAnalytics($messageId, $errorCode) {
        $analyticsData = json_encode([
            'outcome' => 'ERROR',
            'timestamp' => time(),
            'error_code' => $errorCode
        ]);
        
        $analyticsSQL = "INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) VALUES (DEFAULT, " . intval($messageId) . ", 'AGAIN_ANALYTICS', '" . db::EscString($analyticsData) . "')";
        return db::Query($analyticsSQL);
    }
    
    /**
     * Get original model details from message metadata
     * 
     * @param int $messageId Message ID
     * @return array|null Array with 'bid', 'provider' and 'service' or null if not found
     */
    public static function getOriginalModelFromMessage($messageId) {
        // Get AIMODEL (provider) and AISERVICE from BMESSAGEMETA
        // Use ORDER BY BID ASC to get the FIRST (correct) entries, not the wrong ones
        $modelSQL = "SELECT BTOKEN, BVALUE FROM BMESSAGEMETA WHERE BMESSID = " . intval($messageId) . " AND BTOKEN IN ('AIMODEL', 'AISERVICE') ORDER BY BID ASC";
        $modelRes = db::Query($modelSQL);
        
        $modelData = [];
        while ($row = db::FetchArr($modelRes)) {
            // Only store the first occurrence of each token (correct one)
            if (!isset($modelData[$row['BTOKEN']])) {
                $modelData[$row['BTOKEN']] = $row['BVALUE'];
            }
        }
        
        if (isset($modelData['AIMODEL']) && isset($modelData['AISERVICE'])) {
            // Find the BID from BMODELS using provider name
            $bidSQL = "SELECT BID FROM BMODELS WHERE BPROVID = '" . db::EscString($modelData['AIMODEL']) . "' LIMIT 1";
            $bidRes = db::Query($bidSQL);
            $bidData = db::FetchArr($bidRes);
            
            if ($bidData) {
                return [
                    'bid' => intval($bidData['BID']),
                    'provider' => $modelData['AIMODEL'],
                    'service' => $modelData['AISERVICE']
                ];
            }
        }
        
        // Fallback: try to get from current globals (less reliable)
        if (isset($GLOBALS["AI_CHAT"]["MODEL"]) && isset($GLOBALS["AI_CHAT"]["SERVICE"]) && isset($GLOBALS["AI_CHAT"]["MODELID"])) {
            return [
                'bid' => intval($GLOBALS["AI_CHAT"]["MODELID"]),
                'provider' => $GLOBALS["AI_CHAT"]["MODEL"],
                'service' => $GLOBALS["AI_CHAT"]["SERVICE"]
            ];
        }
        
        return null;
    }
    
    /**
     * Get used model BIDs for a thread (helper method)
     * 
     * @param int $trackId Thread tracking ID
     * @return array Array of used model BIDs
     */
    private static function getUsedModelBids($trackId) {
        $usedModelsSQL = "SELECT DISTINCT CAST(BVALUE AS UNSIGNED) as MODEL_BID FROM BMESSAGEMETA 
                         WHERE BTOKEN = 'AGAIN_RETRY_MODEL' 
                         AND BMESSID IN (
                             SELECT BID FROM BMESSAGES WHERE BTRACKID = " . intval($trackId) . "
                         )";
        $usedModelsRes = db::Query($usedModelsSQL);
        
        $usedModelBids = [];
        while ($usedModel = db::FetchArr($usedModelsRes)) {
            $usedModelBids[] = intval($usedModel['MODEL_BID']);
        }
        
        return $usedModelBids;
    }
    
    /**
     * Get all selectable models for UI dropdown (topic-aware)
     * 
     * @param string $topic Topic to filter models (default: 'chat')
     * @return array Array of model details for dropdown
     */
    public static function getSelectableModels($topic = 'chat') {
        // Topic-specific model filtering
        $topicCategories = [
            'chat' => ['chat'],
            'general' => ['chat'], 
            'pic2text' => ['pic2text'],
            'text2pic' => ['text2pic'],
            'text2sound' => ['text2sound'],
            'text2vid' => ['text2vid'],
            'sound2text' => ['sound2text'],
            'analyzefile' => ['chat', 'pic2text'],
            'mediamaker' => ['text2pic', 'text2vid', 'text2sound']
        ];
        
        $allowedCategories = $topicCategories[$topic] ?? ['chat'];
        $categoryFilter = "'" . implode("','", $allowedCategories) . "'";
        
        $modelsSQL = "SELECT BID, BTAG, BNAME, BPROVID, BSERVICE, BQUALITY FROM BMODELS 
                     WHERE BSELECTABLE = 1 AND BTAG IN ($categoryFilter) 
                     ORDER BY BQUALITY DESC, BID ASC";
        $modelsRes = db::Query($modelsSQL);
        
        $models = [];
        while ($model = db::FetchArr($modelsRes)) {
            // Create proper display name - use BNAME if meaningful, otherwise BPROVID
            if (!empty($model['BNAME']) && $model['BNAME'] !== 'chat' && strlen($model['BNAME']) > 3) {
                $displayName = $model['BNAME']; // Use full name if meaningful
            } else {
                $displayName = $model['BPROVID']; // Default to provider ID (like "o3", "gpt-4.1")
            }
            
            $models[] = [
                'bid' => intval($model['BID']),
                'tag' => $displayName,
                'provider' => $model['BPROVID'],
                'service' => $model['BSERVICE'],
                'quality' => floatval($model['BQUALITY'])
            ];
        }
        
        return $models;
    }
}
