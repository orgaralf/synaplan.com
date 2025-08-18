<div class="max-w-3xl mx-auto w-full p-6">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = $_POST['prompt'];

    echo "<p class='mb-4'><span class='font-semibold'>Prompt:</span> $prompt</p>";

    try {
        $tempDir = sys_get_temp_dir();
        $imagePath = $tempDir . '/' . basename($_FILES['image']['name']);
        
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload: " . $_FILES['image']['error']);
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            throw new Exception("Falha ao mover o arquivo para o diretório temporário.");
        }

        $response = $groq->vision()->analyze($imagePath, $prompt);

        echo "<p class='font-semibold mb-2'>Resposta do Modelo:</p>";
        echo "<p>" . $response['choices'][0]['message']['content'] . "</p>";
    } catch (LucianoTonet\GroqPHP\GroqException $err) {
        echo "<p class='text-red-600'>Erro Groq: " . $err->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p class='text-red-600'>Erro: " . $e->getMessage() . "</p>";
    } finally {
        if (isset($imagePath) && file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}
?>

<form method="post" enctype="multipart/form-data" class="mt-6 space-y-4">
    <div>
        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Selecione a imagem</label>
        <input type="file" name="image" required class="block w-full text-sm text-gray-500 border border-gray-300 rounded p-2
            file:mr-4 file:py-2 file:px-4
            file:rounded-none file:border-0
            file:text-sm file:font-semibold
            file:bg-gray-100 file:text-gray-700
            hover:file:bg-gray-200">
    </div>
    <div>
        <label for="prompt" class="block text-sm font-medium text-gray-700 mb-1">Prompt</label>
        <input type="text" name="prompt" placeholder="Descreva a imagem" required
            class="w-full border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500">
    </div>
    <button type="submit" class="w-full py-2 px-4 border border-transparent rounded text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Enviar
    </button>
</form>
</div>