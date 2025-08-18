<div class="max-w-3xl mx-auto w-full p-6">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageUrl = $_POST['image_url'];
    $prompt = $_POST['prompt'];

    echo "<p class='mb-4'><span class='font-semibold'>Prompt:</span> $prompt</p>";

    try {
        $response = $groq->vision()->analyze($imageUrl, $prompt);

        echo "<p class='font-semibold mb-2'>Resposta do Modelo:</p>";
        echo "<p>" . $response['choices'][0]['message']['content'] . "</p>";
    } catch (LucianoTonet\GroqPHP\GroqException $err) {
        echo "<p class='text-red-600'>Erro: " . $err->getMessage() . "</p>";
    }
}
?>

<form method="post" class="mt-6 space-y-4">
    <div>
        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">URL da imagem</label>
        <input type="text" name="image_url" placeholder="URL da imagem" required
            class="w-full border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500">
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