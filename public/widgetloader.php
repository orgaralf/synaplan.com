<?php
session_start();
// core app files with relative paths
$root = __DIR__.'/';
require_once($root . '/inc/_coreincludes.php');

// Get parameters
$uid = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
$widgetId = isset($_REQUEST['widgetid']) ? intval($_REQUEST['widgetid']) : 1;

// Validate parameters
if ($uid <= 0 || $widgetId < 1) {
    echo "<h1>Invalid widget parameters!</h1>";
    exit;
}

// Set anonymous widget session variables
$_SESSION["is_widget"] = true;
$_SESSION["widget_owner_id"] = $uid;
$_SESSION["widget_id"] = $widgetId;
$_SESSION["anonymous_session_id"] = uniqid('widget_', true); // Generate unique session ID
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
        $_SESSION["anonymous_session_id"] = uniqid('widget_', true); // Generate new unique session ID
        $_SESSION["anonymous_session_created"] = time();
    }
}

// Use Tools::getWidgetConfig for dynamic configuration loading
$config = Tools::getWidgetConfig($widgetId, $uid);

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
    <base href="<?php echo ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/'; ?>">
    <!-- Bootstrap CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Dashboard CSS - includes all chat interface styles -->
    <link href="css/dashboard.css" rel="stylesheet">
    <style>
        /* Widget-specific overrides */
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
            overflow: auto;
        }
        
        /* Widget-specific chat container adjustments */
        .chat-container {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .chatbox {
            height: 100%;
            display: flex;
            flex-direction: column;
            border: none;
            box-shadow: none;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .chat-input-container {
            border-top: 1px solid #e9ecef;
            background: white;
            padding: 15px;
        }
        
        /* Hide file preview by default in widget mode */
        #filesDiv {
            display: none !important;
        }
        
        .file-preview-container {
            display: none !important;
        }
        
        .file-preview-container.active {
            display: block !important;
        }
        
        /* Widget-specific input layout */
        .input-controls-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        
        .action-buttons {
            flex-shrink: 0;
        }
        
        .message-input-wrapper {
            flex: 1;
            min-width: 0;
        }
        
        /* Responsive adjustments for widget */
        @media (max-width: 480px) {
            .chat-input-container {
                padding: 10px;
            }
            
            .input-controls-wrapper {
                gap: 8px;
            }
            
            .message-input {
                min-height: 36px;
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .send-btn, .attach-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
    <!-- jQuery - needed for chat functionality -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
</head>
<body>
    <div class="widget-header">
        Chat Support
    </div>
    <div class="widget-content">
        <?php include('snippets/c_chat.php'); ?>
    </div>
    <!-- Bootstrap JS - needed for dropdowns and other components -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 