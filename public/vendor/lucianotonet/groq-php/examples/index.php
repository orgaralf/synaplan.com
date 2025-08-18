<?php
require __DIR__ . '/vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;

 // Start of Selection
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__, '../.env', true);
$dotenv->load();

try {
    $groq = new Groq();
} catch (GroqException $e) {
    echo $e->getMessage();
    die();
}

if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
    require __DIR__ . '/' . $_GET['page'] . '.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <title>Groq PHP Examples</title>
</head>

<body class="w-full p-24 ">

    <div class="container mx-auto max-w-screen-xl gap-12 prose flex">

        <div class="flex flex-col">
            <h3 class="text-md font-bold">Examples:</h3>

            <ul class="list-disc">
                <li><a class="focus:font-bold" href="?page=models">List Models</a></li>
                <li><a class="focus:font-bold" href="?page=chat">Chat</a></li>
                <li><a class="focus:font-bold" href="?page=chat-streaming">Chat stream</a></li>
                <li><a class="focus:font-bold" href="?page=json-mode">JSON mode</a></li>
                <li><a class="focus:font-bold" href="?page=tool-calling">Tool calling</a></li>
                <li><a class="focus:font-bold" href="?page=tool-calling-advanced">Tool calling advanced</a></li>
                <li><a class="focus:font-bold" href="?page=audio-transcriptions">Speech to Text Transcription</a></li>
                <li><a class="focus:font-bold" href="?page=audio-translations">Speech to Text Translation</a></li>
                <li><a class="focus:font-bold" href="?page=vision-simple">Vision Simple</a></li>
                <li><a class="focus:font-bold" href="?page=vision-url">Vision URL</a></li>
                <li><a class="focus:font-bold" href="?page=vision-multiple">Vision Multiple</a></li>
                <li><a class="focus:font-bold" href="?page=vision-feedback">Vision Feedback</a></li>                
            </ul>
        </div>
        
        <div id="content" class="flex flex-1 gap-12">
            <?php
            if (isset($_GET['page'])) {
                require __DIR__ . '/' . $_GET['page'] . '.php';
            } else {
                echo "← Select an example";
            }
            ?>
        </div>

    </div>

</body>

</html>