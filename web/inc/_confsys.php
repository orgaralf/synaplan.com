<?php
// -----------------------------------------------------
// This is the core and central include file for the synaplan system setup
// -----------------------------------------------------

// It starts with some basic global variables and logic
if(isset($_REQUEST["logout"]) && $_REQUEST["logout"]=="true") {
    unset($_SESSION['user']);
    session_destroy();
    header("Location: index.php");
    exit;
}

// GLOBALS CONFIGS
if(isset($_SERVER["SCRIPT_NAME"])) {
    $scriptname = basename($_SERVER["SCRIPT_NAME"]);
} else {
    $scriptname = "cli";
}
// -----------------------------------------------------
if(isset($_SERVER["SERVER_NAME"])) {
    $server = $_SERVER["SERVER_NAME"];
} else {
    $server = "cli";
}
// -----------------------------------------------------
if(isset($_SERVER["REQUEST_URI"])) {
    $uri = $_SERVER["REQUEST_URI"];
} else {
    $uri = "cli";
}
// -----------------------------------------------------

// ----------------------------------------------------- LIVE
$liveUrl = "https://app.synaplan.com/";

// ----------------------------------------------------- DEV
$devUrl = "http://localhost/synaplan.com/web/";
$baseUrl = "";

// ----------------------------------------------------- set right url
// ----------------------------------------------------- storage path
if (substr_count($server, "localhost") > 0) {
    $GLOBALS["baseUrl"] = $devUrl;
} else {
    $GLOBALS["baseUrl"] = $liveUrl;
}
