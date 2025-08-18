<div class="max-w-3xl mx-auto w-full p-6">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = $_POST['prompt'] ?? '';
    $imagePaths = $_FILES['images']['tmp_name'] ?? [];
    $uploadedImagePaths = [];

    if (empty($prompt)) {
        echo "<p class='text-red-600 font-semibold'>O prompt não pode estar vazio.</p>";
    } else {
        echo "<p class='mb-4'><span class='font-semibold'>Prompt para todas as imagens:</span> " . htmlspecialchars($prompt) . "</p>";

        foreach ($imagePaths as $index => $imagePath) {
            $tempDir = sys_get_temp_dir();
            $tempImagePath = $tempDir . '/' . basename($_FILES['images']['name'][$index]);

            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                echo "<p class='text-red-600'>Erro no upload da imagem " . ($index + 1) . ": " . $_FILES['images']['error'][$index] . "</p>";
                continue;
            }

            if (!move_uploaded_file($imagePath, $tempImagePath)) {
                echo "<p class='text-red-600'>Falha ao mover o arquivo para o diretório temporário da imagem " . ($index + 1) . ".</p>";
                continue;
            }

            $uploadedImagePaths[] = $tempImagePath;

            try {
                $response = $groq->vision()->analyze($tempImagePath, $prompt);
                echo "<p class='mb-2 font-semibold'>Resposta do Modelo para Imagem " . ($index + 1) . ":</p>";
                echo "<p class='mb-4'>" . htmlspecialchars($response['choices'][0]['message']['content']) . "</p>";
            } catch (LucianoTonet\GroqPHP\GroqException $err) {
                echo "<p class='text-red-600'>Erro na análise da imagem " . ($index + 1) . ": " . htmlspecialchars($err->getMessage()) . "</p>";
            } catch (Exception $e) {
                echo "<p class='text-red-600'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    foreach ($uploadedImagePaths as $uploadedImagePath) {
        if (file_exists($uploadedImagePath)) {
            unlink($uploadedImagePath);
        }
    }
}
?>

<form method="post" enctype="multipart/form-data" class="mt-6 space-y-4">
    <div>
        <label for="images" class="block text-sm font-medium text-gray-700 mb-1">Selecione as imagens</label>
        <input type="file" name="images[]" multiple required class="block w-full text-sm text-gray-500 border border-gray-300 rounded p-2
            file:mr-4 file:py-2 file:px-4
            file:rounded-none file:border-0
            file:text-sm file:font-semibold
            file:bg-gray-100 file:text-gray-700
            hover:file:bg-gray-200">
    </div>
    <div>
        <label for="prompt" class="block text-sm font-medium text-gray-700 mb-1">Prompt</label>
        <input type="text" name="prompt" placeholder="Descreva o que você quer saber sobre as imagens" required
            class="w-full border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500">
    </div>
    <button type="submit" class="w-full py-2 px-4 border border-transparent rounded text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Enviar
    </button>
</form>
</div>