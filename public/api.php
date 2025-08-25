<?php 
/* 
    API for synaplan.com. Serving as a bridge between the frontend and the backend.
    Design with a bearer token authentication. Bearer token is the session id.
    The bearer Auth token is saved in the database for each user.
*/
# https://github.com/logiscapedev/mcp-sdk-php work with that, when called the MCP way
// Set execution time limit to 6 minutes
set_time_limit(360);
session_start();

// core app files with relative paths
$root = __DIR__ . '/';
require_once($root . '/inc/_coreincludes.php');

// ----------------------------- Bearer API key authentication (override session if provided)
function getAuthHeaderValue(): string {
    $headers = [];
    if (function_exists('getallheaders')) { $headers = getallheaders(); }
    $auth = '';
    if (isset($headers['Authorization'])) { $auth = $headers['Authorization']; }
    elseif (isset($headers['authorization'])) { $auth = $headers['authorization']; }
    elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { $auth = $_SERVER['HTTP_AUTHORIZATION']; }
    return trim($auth);
}

$authHeader = getAuthHeaderValue();
if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
    $apiKey = trim(substr($authHeader, 7));
    if (strlen($apiKey) > 20) {
        $sql = "SELECT BOWNERID, BID, BSTATUS FROM BAPIKEYS WHERE BKEY = '".DB::EscString($apiKey)."' LIMIT 1";
        $res = DB::Query($sql);
        $row = DB::FetchArr($res);
        if ($row && $row['BSTATUS'] === 'active') {
            $userRes = DB::Query("SELECT * FROM BUSER WHERE BID = ".intval($row['BOWNERID'])." LIMIT 1");
            $userArr = DB::FetchArr($userRes);
            if ($userArr) {
                $_SESSION['USERPROFILE'] = $userArr;
                $_SESSION['AUTH_MODE'] = 'api_key';
                // update last used
                DB::Query("UPDATE BAPIKEYS SET BLASTUSED = ".time()." WHERE BID = ".intval($row['BID']));
            }
        } else {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Invalid or inactive API key']);
            exit;
        }
    }
}

// Check if this is a JSON-RPC request
$isJsonRpc = false;
$jsonRpcRequest = null;

// Get the raw POST data
$rawPostData = file_get_contents('php://input');

// Check if the request is JSON-RPC
if (!empty($rawPostData) && Tools::isValidJson($rawPostData)) {
    $jsonRpcRequest = json_decode($rawPostData, true);
    if (isset($jsonRpcRequest['jsonrpc']) && 
        isset($jsonRpcRequest['method']) && 
        isset($jsonRpcRequest['id'])) {
        $isJsonRpc = true;
    }
}
// first, check if the user sent a bearer token
// $bearerToken = $_SERVER['HTTP_AUTHORIZATION'];
// it not, check for session id
// later filled with the right user check for devleopment, we use plain posts

// Handle JSON-RPC request
if ($isJsonRpc) {
    require_once(__DIR__ . '/mcp.php');
    exit;
}

// If not JSON-RPC, continue with REST handling
// what does the user want?
header('Content-Type: application/json; charset=UTF-8');
$apiAction = $_REQUEST['action'];

// ------------------------------------------------------ RATE LIMITING FUNCTION --------------------
/**
 * Check rate limit for API requests
 * 
 * @param string $key Rate limit key (usually user ID or session ID)
 * @param int $window Time window in seconds
 * @param int $maxRequests Maximum requests allowed in the time window
 * @return array Array with 'allowed' boolean and 'retry_after' seconds
 */
function checkRateLimit($key, $window, $maxRequests) {
    $currentTime = time();
    $rateLimitKey = 'rate_limit_' . $key;
    
    // Get current rate limit data from session
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'count' => 0,
            'window_start' => $currentTime
        ];
    }
    
    $rateData = $_SESSION[$rateLimitKey];
    
    // Check if we're in a new time window
    if ($currentTime - $rateData['window_start'] >= $window) {
        // Reset for new window
        $_SESSION[$rateLimitKey] = [
            'count' => 1,
            'window_start' => $currentTime
        ];
        return ['allowed' => true, 'retry_after' => 0];
    }
    
    // Check if we're within limits
    if ($rateData['count'] < $maxRequests) {
        // Increment count
        $_SESSION[$rateLimitKey]['count']++;
        return ['allowed' => true, 'retry_after' => 0];
    }
    
    // Rate limit exceeded
    $retryAfter = $window - ($currentTime - $rateData['window_start']);
    return ['allowed' => false, 'retry_after' => $retryAfter];
}

// ------------------------------------------------------ API AUTHENTICATION & RATE LIMITING --------------------
// Check if this is an anonymous widget session
$isAnonymousWidget = isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true;

// Debug logging for session state
if ($GLOBALS["debug"]) {
    error_log("API Debug - Session state:");
    error_log("  is_widget: " . (isset($_SESSION["is_widget"]) ? $_SESSION["is_widget"] : "NOT SET"));
    error_log("  USERPROFILE: " . (isset($_SESSION["USERPROFILE"]) ? "SET" : "NOT SET"));
    error_log("  widget_owner_id: " . (isset($_SESSION["widget_owner_id"]) ? $_SESSION["widget_owner_id"] : "NOT SET"));
    error_log("  widget_id: " . (isset($_SESSION["widget_id"]) ? $_SESSION["widget_id"] : "NOT SET"));
    error_log("  anonymous_session_id: " . (isset($_SESSION["anonymous_session_id"]) ? $_SESSION["anonymous_session_id"] : "NOT SET"));
    error_log("  isAnonymousWidget: " . ($isAnonymousWidget ? "TRUE" : "FALSE"));
    error_log("  API Action: " . $apiAction);
}

// Define which endpoints are allowed for anonymous widget users
$anonymousAllowedEndpoints = [
    'messageNew',
    'chatStream',
    'getMessageFiles',
    'userRegister',
    'againMessage',
    'getSelectableModels',
    'getNextModel',
    'getMessageModel',
    'getUserAvatar'
];

// Define which endpoints require authenticated user sessions
$authenticatedOnlyEndpoints = [
    'ragUpload',
    'docSum',
    'promptLoad',
    'promptUpdate',
    'deletePrompt',
    'getPromptDetails',
    'getFileGroups',
    'changeGroupOfFile',
    'getProfile',
    'loadChatHistory',
    'getWidgets',
    'saveWidget',
    'deleteWidget',
    'getApiKeys',
    'createApiKey',
    'setApiKeyStatus',
    'deleteApiKey',
    'getMailhandler',
    'saveMailhandler',
    'mailTestConnection',
    'mailOAuthStart',
    'mailOAuthCallback',
    'mailOAuthStatus',
    'mailOAuthDisconnect'
];

// Check authentication for the requested action
if (in_array($apiAction, $authenticatedOnlyEndpoints)) {
    // These endpoints require authenticated user sessions
    if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required for this endpoint']);
        exit;
    }
} elseif (in_array($apiAction, $anonymousAllowedEndpoints)) {
    // These endpoints allow both anonymous widget sessions and authenticated user sessions
    
    // Check if this is an anonymous widget session
    if ($isAnonymousWidget) {
        if ($GLOBALS["debug"]) {
            error_log("API Debug - Processing as anonymous widget session");
        }
        
        // Validate anonymous widget session
        if (!isset($_SESSION["widget_owner_id"]) || !isset($_SESSION["widget_id"]) || !isset($_SESSION["anonymous_session_id"])) {
            if ($GLOBALS["debug"]) {
                error_log("API Debug - Missing required session variables for anonymous widget");
            }
            http_response_code(401);
            echo json_encode(['error' => 'Invalid anonymous widget session']);
            exit;
        }
        
        // Check session timeout
        if (!Frontend::validateAnonymousSession()) {
            if ($GLOBALS["debug"]) {
                error_log("API Debug - Anonymous session validation failed");
            }
            http_response_code(401);
            echo json_encode(['error' => 'Anonymous session expired. Please refresh the page.']);
            exit;
        }
        
        // Implement rate limiting for anonymous users
        $rateLimitKey = 'anonymous_widget_' . $_SESSION["widget_owner_id"] . '_' . $_SESSION["widget_id"];
        $rateLimitResult = checkRateLimit($rateLimitKey, 60, 30); // 30 requests per minute
        
        if (!$rateLimitResult['allowed']) {
            http_response_code(429);
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'retry_after' => $rateLimitResult['retry_after']
            ]);
            exit;
        }
    } else {
        if ($GLOBALS["debug"]) {
            error_log("API Debug - Processing as authenticated user session");
        }
        
        // Check for regular authenticated user session
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
            if ($GLOBALS["debug"]) {
                error_log("API Debug - Missing USERPROFILE for authenticated user");
            }
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
} else {
    // Unknown endpoint
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    exit;
}

// ------------------------------------------------------ API OPTIONS --------------------
// Take form post of user message and files and save to database
// give back tracking ID of the message

switch($apiAction) {
    case 'messageNew':
        $resArr = Frontend::saveWebMessages();
        break;
    case 'ragUpload':
        $resArr = Frontend::saveRAGFiles();
        break;
    case 'chatStream':
        $resArr = Frontend::chatStream();
        exit;
    case 'docSum':
        $resArr = BasicAI::doDocSum();
        break;
    case 'promptLoad':
        $resArr = BasicAI::getAprompt($_REQUEST['promptKey'], $_REQUEST['lang'], [], false);
        break;
    case 'promptUpdate':
        $resArr = BasicAI::updatePrompt($_REQUEST['promptKey']);
        break;
    case 'deletePrompt':
        $resArr = BasicAI::deletePrompt($_REQUEST['promptKey']);
        break;
    case 'getPromptDetails':
        $resArr = BasicAI::getPromptDetails($_REQUEST['promptKey']);
        break;
    case 'getMessageFiles':
        $messageId = intval($_REQUEST['messageId']);
        $files = Frontend::getMessageFiles($messageId);
        $resArr = ['success' => true, 'files' => $files];
        break;
    case 'getFileGroups':
        $groups = BasicAI::getAllFileGroups();
        $resArr = ['success' => true, 'groups' => $groups];
        break;
    case 'changeGroupOfFile':
        $fileId = intval($_REQUEST['fileId']);
        $newGroup = isset($_REQUEST['newGroup']) ? trim($_REQUEST['newGroup']) : '';
        if($GLOBALS["debug"]) error_log("API changeGroupOfFile called with fileId: $fileId, newGroup: '$newGroup'");
        $resArr = BasicAI::changeGroupOfFile($fileId, $newGroup);
        if($GLOBALS["debug"]) error_log("API changeGroupOfFile result: " . json_encode($resArr));
        break;
    case 'getProfile':
        $resArr = Frontend::getProfile();
        break;
    case 'loadChatHistory':
        $amount = isset($_REQUEST['amount']) ? intval($_REQUEST['amount']) : 10;
        $resArr = Frontend::loadChatHistory($amount);
        break;
    case 'getWidgets':
        $resArr = Frontend::getWidgets();
        break;
    case 'saveWidget':
        $resArr = Frontend::saveWidget();
        break;
    case 'deleteWidget':
        $resArr = Frontend::deleteWidget();
        break;
    case 'getApiKeys':
        $resArr = Frontend::getApiKeys();
        break;
    case 'createApiKey':
        $resArr = Frontend::createApiKey();
        break;
    case 'setApiKeyStatus':
        $resArr = Frontend::setApiKeyStatus();
        break;
    case 'deleteApiKey':
        $resArr = Frontend::deleteApiKey();
        break;
    case 'userRegister':
        $resArr = Frontend::registerNewUser();
        break;
    case 'getMailhandler':
        $resArr = Frontend::getMailhandler();
        break;
    case 'saveMailhandler':
        $resArr = Frontend::saveMailhandler();
        break;
    case 'mailOAuthStart':
        $resArr = Frontend::mailOAuthStart();
        break;
    case 'mailOAuthCallback':
        // Handle OAuth callback and then redirect back to the UI by default
        $result = Frontend::mailOAuthCallback();
        if (!isset($_REQUEST['ui']) || $_REQUEST['ui'] !== 'json') {
            // Override content type and redirect
            $target = $GLOBALS['baseUrl'] . 'index.php/mailhandler';
            if (!empty($result['success'])) { $target .= '?oauth=ok'; }
            else { $target .= '?oauth=error'; }
            header('Content-Type: text/html; charset=UTF-8');
            header('Location: ' . $target);
            echo '<html><head><meta http-equiv="refresh" content="0;url='.$target.'"></head><body>Redirecting...</body></html>';
            exit;
        }
        $resArr = $result;
        break;
    case 'mailOAuthStatus':
        $resArr = Frontend::mailOAuthStatus();
        break;
    case 'mailOAuthDisconnect':
        $resArr = Frontend::mailOAuthDisconnect();
        break;
    case 'mailTestConnection':
        $resArr = Frontend::mailTestConnection();
        break;
    case 'againMessage':
        $resArr = Frontend::againMessage();
        break;
    case 'getSelectableModels':
        $resArr = Frontend::getSelectableModels();
        break;
    case 'getNextModel':
        $resArr = Frontend::getNextModel();
        break;
    case 'getMessageModel':
        $resArr = Frontend::getMessageModel();
        break;
    case 'getUserAvatar':
        Frontend::getUserAvatar();
        exit;
    default:
        $resArr = ['error' => 'Invalid action'];
        break;
}

// ------------------------------------------------------ Json output
echo json_encode($resArr);
exit;