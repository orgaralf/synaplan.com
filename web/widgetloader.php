<?php
session_start();
// core app files with relative paths
$root = __DIR__.'/';
require_once($root . '/inc/_coreincludes.php');

// Get the prompt configuration from query parameter
if(isset($_REQUEST['widgetid'])) {
    $widgetId = $_REQUEST['widgetid'];
} else {
    $widgetId = '';
}

$includeChat = '';
if(strlen($widgetId) > 1) {
    $includeChat = 'snippets/c_chat.php';
}

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
</head>
<body>
    <?php
    if(strlen($includeChat) > 1) {
        include($includeChat);
    } else {
        echo "<h1>No widget id provided!</h1>";
    }
    ?>
</body>
</html> 