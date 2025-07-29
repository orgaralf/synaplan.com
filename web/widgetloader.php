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
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Widget</title>
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