<?php
$widgetId = $_REQUEST['widgetid'];
?>
<html>
    <head>
        <title>Hello World</title>
    </head>
    <body>
        <h1>Hello World</h1>
        <script src="web/widget.php?widgetid=<?php echo $widgetId; ?>"></script>
    </body>
</html>

