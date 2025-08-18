<div>
<?php
use LucianoTonet\GroqPHP\GroqException;
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    try {
        $response = $groq->chat()->completions()->create([
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
        ]);

        echo "<strong>assistant: </strong> ";
        echo $response['choices'][0]['message']['content'];
    } catch (GroqException $err) {
        echo "<strong>assistant:</strong><br>".$err->getMessage()."<br>";

        echo "<pre>";
        print_r($err->getError());

        echo "</pre>";
    }
}
?>
</div>