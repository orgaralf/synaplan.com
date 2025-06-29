<?php
// ------------------------------------------------------ base config
require_once(__DIR__ . '/inc/_confdb.php');
require_once(__DIR__ . '/inc/_mail.php');
// -------------------------------------------------- LOCAL SERVICES CHECK
$output = [];
exec('ps afx', $output);
// $output is now an array of lines from "ps afx"

$keyfound = false;
$errors = [];

$keywords = [];
$keywords[] = 'ollama serve';
$keywords[] = 'apache2';
$keywords[] = 'mariadb';
$keywords[] = 'php-fpm';

foreach ($keywords as $keyword) {
    foreach ($output as $line) {
        if (stripos($line, $keyword) !== false) {
            $keyfound = true;
        }
    }
    if (!$keyfound) {
        $errors[] = $keyword.": ERROR - task not running\n";
    } else {
        $errors[] = $keyword.": OK - task running\n";
    }
    $keyfound = false;
}

// -------------------------------------------------- OTHER STUFF
/*
$sites = [];
$sites[] = 'https://www.metadist.de/';
//$sites[] = 'https://www.metadist.com/';
$sites[] = 'https://ralfs.ai/';

$keywords = [];
$keywords[] = 'Digitale Eleganz';
//$keywords[] = 'elegance';
$keywords[] = 'WhatsApp';

$counter = 0;
foreach ($sites as $site) {
    $response = file_get_contents($site);
    if ($response === false) {
        $errors[] = $site.": ERROR - site not reachable\n";
    } else {
        if(substr_count($response, $keywords[$counter]) > 0) {
            $errors[] = $site.": OK - site response contains keyword\n";
        } else {
            $errors[] = $site.": ERROR - site response does not contain keyword\n";
        }
    }
    $counter++;
}
*/
// now count incoming messages to BMESSAGES in database
$sql = "SELECT COUNT(*) ANZ FROM BMESSAGES WHERE BUNIXTIMES > ".(time()-180);
$res = DB::query($sql);
$countArr = DB::FetchArr($res);
$count = $countArr["ANZ"];
if($count > 20) {
    $errors[] = "Incoming messages: ERROR - ".($count)." messages in the last 3 minutes\n";
} else {
    $errors[] = "Incoming messages: OK - ".($count)." messages in the last 3 minutes\n";
}
?>
<html>
    <body>  
        <h1>System Monitor</h1>
        <pre>
            <?php
                print_r($errors);
            ?>
        </pre>
    </body>
</html>