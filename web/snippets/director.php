<?php
$contentInc = "unknown";
// check for login command
if(isset($_REQUEST['action']) AND $_REQUEST['action'] == "login") {
    $success = Frontend::setUserFromWebLogin();
}
// ------------------------------------------------------------
// check if user is logged in
if(!isset($_SESSION['USERPROFILE'])) {
    if(isset($_REQUEST['lid']) AND strlen($_REQUEST['lid']) > 3) {
        if(Frontend::setUserFromTicket()) {
            $contentInc = "welcome";
        } else {
            $contentInc = "login";
        }
    } else {
        $contentInc = "login";
    }
} else {
    if(count($_SESSION['USERPROFILE']) > 0) {
        $contentInc = "welcome";
        $cleanUriArr = explode("?", $_SERVER['REQUEST_URI']);
        $urlParts = explode("index.php/", $cleanUriArr[0]);
        if(count($urlParts) > 1) {
            $commandParts = explode("/", $urlParts[1]);
        } else {
            $commandParts = [];
        }
        if(count($commandParts) > 0) {
            if($commandParts[0] == "logout") {
                unset($_SESSION['USERPROFILE']);
                $contentInc = "login";
            } elseif(strlen($commandParts[0])>2) {
                $contentInc = $commandParts[0];
            }
        }
    } else {
        $contentInc = "login";
    }
}
// ------------------------------------------------------------
if($contentInc != "login") {
    include("snippets/c_menu.php");
    include("snippets/c_".$contentInc.".php");
} else {
    include("snippets/c_login.php");
}
