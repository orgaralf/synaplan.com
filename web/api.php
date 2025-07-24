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
        error_log("API changeGroupOfFile called with fileId: $fileId, newGroup: '$newGroup'");
        $resArr = BasicAI::changeGroupOfFile($fileId, $newGroup);
        error_log("API changeGroupOfFile result: " . json_encode($resArr));
        break;
    case 'getProfile':
        $resArr = Frontend::getProfile();
        break;
    case 'loadChatHistory':
        $amount = isset($_REQUEST['amount']) ? intval($_REQUEST['amount']) : 10;
        $resArr = Frontend::loadChatHistory($amount);
        break;
    default:
        $resArr = ['error' => 'Invalid action'];
        break;
}

// ------------------------------------------------------ Json output
echo json_encode($resArr);
exit;