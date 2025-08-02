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

// Define which endpoints are allowed for anonymous widget users
$anonymousAllowedEndpoints = [
    'messageNew',
    'chatStream',
    'getMessageFiles'
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
    'deleteWidget'
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
    // These endpoints allow anonymous widget sessions
    if ($isAnonymousWidget) {
            // Validate anonymous widget session
    if (!isset($_SESSION["widget_owner_id"]) || !isset($_SESSION["widget_id"]) || !isset($_SESSION["anonymous_session_id"])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid anonymous widget session']);
        exit;
    }
    
    // Check session timeout
    if (!Frontend::validateAnonymousSession()) {
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
        // Regular authenticated user session required
        if (!isset($_SESSION["USERPROFILE"]) || !isset($_SESSION["USERPROFILE"]["BID"])) {
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
    default:
        $resArr = ['error' => 'Invalid action'];
        break;
}

// ------------------------------------------------------ Json output
echo json_encode($resArr);
exit;