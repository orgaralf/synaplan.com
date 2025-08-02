<?php
session_start();
// core app files with relative paths
$root = __DIR__.'/';
require_once($root . '/inc/_coreincludes.php');

// Get parameters
$uid = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
$widgetId = isset($_REQUEST['widgetid']) ? intval($_REQUEST['widgetid']) : 1;

// Validate parameters
if ($uid <= 0 || $widgetId < 1 || $widgetId > 9) {
    echo "<h1>Invalid widget parameters!</h1>";
    exit;
}

// Set anonymous widget session variables
$_SESSION["is_widget"] = true;
$_SESSION["widget_owner_id"] = $uid;
$_SESSION["widget_id"] = $widgetId;
$_SESSION["anonymous_session_created"] = time(); // Add creation timestamp for timeout validation

// Validate session timeout for existing sessions
if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true) {
    $sessionTimeout = 86400; // 24 hours
    $sessionCreated = $_SESSION["anonymous_session_created"] ?? 0;
    
    if ((time() - $sessionCreated) > $sessionTimeout) {
        // Session expired, clear and recreate
        unset($_SESSION["is_widget"]);
        unset($_SESSION["widget_owner_id"]);
        unset($_SESSION["widget_id"]);
        unset($_SESSION["anonymous_session_id"]);
        unset($_SESSION["anonymous_session_created"]);
        
        // Recreate session
        $_SESSION["is_widget"] = true;
        $_SESSION["widget_owner_id"] = $uid;
        $_SESSION["widget_id"] = $widgetId;
        $_SESSION["anonymous_session_created"] = time();
    }
}

// Get widget configuration from database
$group = "widget_" . $widgetId;
$sql = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $uid . " AND BGROUP = '" . db::EscString($group) . "'";
$res = db::Query($sql);

$config = [
    'color' => '#007bff',
    'position' => 'bottom-right',
    'autoMessage' => '',
    'prompt' => 'general'
];

while ($row = db::FetchArr($res)) {
    $config[$row['BSETTING']] = $row['BVALUE'];
}

// Set the prompt topic for the chat
$_SESSION['WIDGET_PROMPT'] = $config['prompt'];
$_SESSION['WIDGET_AUTO_MESSAGE'] = $config['autoMessage'];

// Set headers to prevent caching and allow iframe embedding
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: ALLOWALL'); // Allow cross-origin iframe embedding
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Widget</title>
    <base href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/'; ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: white;
        }
        .widget-header {
            background: <?php echo $config['color']; ?>;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        .widget-content {
            height: calc(100vh - 60px);
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="widget-header">
        Chat Support
    </div>
    <div class="widget-content">
        <?php include('snippets/c_chat.php'); ?>
    </div>
</body>
</html> 