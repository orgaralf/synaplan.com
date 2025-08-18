<form method="post" enctype="multipart/form-data" class="mb-10 max-w-xl flex flex-col">
    <label for="audio" class="text-sm">Arquivo de Áudio:</label>
    <input type="file" id="audio" class="border border-black p-2 w-96" name="audio" accept="audio/*" required>
    
    <label for="language" class="text-sm mt-4">Idioma:</label>
    <select id="language" class="border border-transparent p-2 w-96" readonly disabled>
        <option value="en" selected>en - English</option>
    </select>

    <label for="response_format" class="text-sm mt-4">Formato da Resposta:</label>
    <select id="response_format" name="response_format" class="border border-black p-2 w-96" required>
        <option value="json" selected>json</option>
        <option value="verbose_json">verbose_json</option>
        <option value="text">text</option>
    </select>

    <label for="prompt" class="text-sm mt-4">Prompt (opcional):</label>
    <textarea id="prompt" name="prompt" placeholder="Prompt (opcional)" class="border border-black p-2 w-96" rows="4"></textarea>   

    <button type="submit" class="bg-black text-white p-2 mt-4 w-96">Traduzir</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $originalFileName = $_FILES['audio']['name'];
    $tmpFilePath = $_FILES['audio']['tmp_name'];
    $newFilePath = sys_get_temp_dir() . '/' . $originalFileName;

    try {
        move_uploaded_file($tmpFilePath, $newFilePath);

        $translationParams = [
            'file' => $newFilePath,
            'model' => 'whisper-large-v3',
            'response_format' => $_POST['response_format'] ?? 'json',
            'temperature' => $_POST['temperature'] ?? 0.0,
        ];

        if (isset($_POST['prompt'])) {
            $translationParams['prompt'] = $_POST['prompt'];
        }

        $translation = $groq->audio()->translations()->create($translationParams);

        if ($translationParams['response_format'] === 'text') {
            echo $translation; // Retorna a resposta em texto diretamente
        } else {
            echo json_encode($translation, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); // Retorna a resposta em JSON
        }
    } catch (LucianoTonet\GroqPHP\GroqException $e) {
        echo "<strong>Error:</strong> <br><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } finally {
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
        }
    }
}