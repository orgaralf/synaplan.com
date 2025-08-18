<div>
<?php
require __DIR__ . '/_input.php';
ob_start();

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
            'stream' => true
        ]);

        foreach ($response->chunks() as $chunk) {
            if (isset($chunk['choices'][0]['delta']['role'])) {
                echo "<strong>" . $chunk['choices'][0]['delta']['role'] . ":</strong> ";
            }

            if (isset($chunk['choices'][0]['delta']['content'])) {
                echo $chunk['choices'][0]['delta']['content'];
            }

            // Chame ob_flush() e flush() na ordem correta
            ob_flush(); // Limpa o buffer de saída
            flush(); // Envia os dados para o cliente
        }
    } catch (\LucianoTonet\GroqPHP\GroqException $err) {
        echo "<strong>assistant:</strong><br>Desculpe, ocorreu um erro: " . $err->getMessage() . "<br>";
    }
}
?>
</div>