<?php
$widgetId = $_REQUEST['widgetid'] ?? 1;
$uid = $_REQUEST['uid'] ?? 1; // Default to user ID 1 if not provided
?>
<html>
    <head>
        <title>Widget Test Page</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 40px;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                text-align: center;
            }
            .info {
                background: #e7f3ff;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                border-left: 4px solid #007bff;
            }
            .code {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                font-family: monospace;
                margin: 10px 0;
                border: 1px solid #dee2e6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Widget Test Page</h1>
            
            <div class="info">
                <strong>Widget Configuration:</strong><br>
                User ID: <?php echo htmlspecialchars($uid); ?><br>
                Widget ID: <?php echo htmlspecialchars($widgetId); ?>
            </div>
            
            <p>This page demonstrates the chat widget integration. The widget should appear on this page.</p>
            
            <div class="code">
                <strong>Integration Code Used:</strong><br>
                &lt;script src="web/widget.php?uid=<?php echo htmlspecialchars($uid); ?>&amp;widgetid=<?php echo htmlspecialchars($widgetId); ?>"&gt;&lt;/script&gt;
            </div>
            
            <p>If you don't see the widget, please check:</p>
            <ul>
                <li>The widget configuration exists for User ID <?php echo htmlspecialchars($uid); ?> and Widget ID <?php echo htmlspecialchars($widgetId); ?></li>
                <li>The widget.php file is accessible</li>
                <li>No JavaScript errors in the browser console</li>
            </ul>
        </div>
        
        <script src="web/widget.php?uid=<?php echo htmlspecialchars($uid); ?>&widgetid=<?php echo htmlspecialchars($widgetId); ?>"></script>
    </body>
</html>

