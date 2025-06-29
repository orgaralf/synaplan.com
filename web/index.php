<?php
//==================================================================================
require_once(__DIR__ . '/vendor/autoload.php');
// ------------------------------------------------------ lang config
session_start();
if(!isset($_SESSION["LANG"])) {
    $_SESSION["LANG"] = "en";
}
if(isset($_REQUEST["lang"])) {
  $_SESSION["LANG"] = $_REQUEST["lang"];
}

// ------------------------------------------------------ include files
// core app files with relative paths
$root = __DIR__ . '/';
require_once($root . '/inc/_coreincludes.php');
?>
<!doctype html>
<html lang="en">
  <head>
    <base href="<?php echo $GLOBALS["baseUrl"]; ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ralfs.AI Dashboard">
    <meta name="author" content="Ralf Schwoebel, based on Bootstrap 5">
    <meta name="generator" content="Manually crafted by Ralf">
    <title>synaplan - digital thinking</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/dashboard.css" rel="stylesheet">
    <!-- JQuery we need quickly, sorry SEO -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
  </head>
  <body>    
    <header class="navbar sticky-top flex-md-nowrap p-0 gradient-dots" id="topBar" style="height: 50px;">
      <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/"><img src="img/synaplan_logo_ondark.svg" alt="AI management" width="155"></a>
      <div class="navbar-nav text-white">
        <span class="mx-3" style="font-size: 0.6em;" id="statusBarText">Welcome</span>
      </div>
      <button class="d-md-none collapsed mx-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </header>

    <div class="container-fluid">
      <div class="row">
        <?php include("snippets/director.php"); ?>
      </div>
    </div>
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="node_modules/feather-icons/dist/feather.min.js"></script>
    <script src="js/dashboard.js"></script>
    
    <!-- Generic Modal for various purposes -->
    <div class="modal fade" id="genericModal" tabindex="-1" aria-labelledby="genericModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="genericModalLabel">Modal Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="genericModalBody">
                    <!-- Modal content will be dynamically loaded here -->
                </div>
                <div class="modal-footer" id="genericModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
  </body>
</html>
