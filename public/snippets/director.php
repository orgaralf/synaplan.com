<?php
$contentInc = "unknown";
// Authentication actions are now handled in index.php before HTML output

// ------------------------------------------------------------

$cleanUriArr = explode("?", $_SERVER['REQUEST_URI']);
$urlParts = explode("index.php/", $cleanUriArr[0]);
if(count($urlParts) > 1) {
    $commandParts = explode("/", $urlParts[1]);
} else {
    $commandParts = [];
}

// check if user is logged in
if(!isset($_SESSION['USERPROFILE'])) {
    if(isset($_REQUEST['lid']) AND strlen($_REQUEST['lid']) > 3) {
        if(Frontend::setUserFromTicket()) {
            $contentInc = "chat";
        } else {
            $contentInc = "login";
        }
    } else {
        if(count($commandParts) > 0 AND $commandParts[0] == "register") {
            $contentInc = "register";
        } else {
            $contentInc = "login";
        }
    }
} else {
    if(count($_SESSION['USERPROFILE']) > 0) {
        $contentInc = "chat";
        if(count($commandParts) > 0) {
            if($commandParts[0] == "logout") {
                unset($_SESSION['USERPROFILE']);
                $contentInc = "login";
            } elseif($commandParts[0] == "confirm") {
                $contentInc = "confirm";
            } elseif(strlen($commandParts[0])>2) {
                $contentInc = $commandParts[0];
            }
        }
    } else {
        if(count($commandParts) > 0 AND $commandParts[0] == "register") {
            $contentInc = "register";
        } else {
            $contentInc = "login";
        }
    }
}
// ------------------------------------------------------------
if($contentInc != "login" && $contentInc != "register" && $contentInc != "confirm") {
    include("snippets/c_menu.php");
    include("snippets/c_".$contentInc.".php");
    $serverIp = $_SERVER['SERVER_ADDR'];
    $serverArr = explode(".", $serverIp);
    echo "\n<!-- SERVER: ".$_SERVER['SERVER_NAME']."_".$serverIp." -->\n";
} elseif($contentInc == "register") {
    include("snippets/c_register.php");
} elseif($contentInc == "confirm") {
    include("snippets/c_confirm.php");
} else {
    include("snippets/c_login.php");
}
