<form method="post" enctype="multipart/form-data" class="mb-10 max-w-xl flex flex-col">
    <label for="audio" class="text-sm">Arquivo de Áudio:</label>
    <input type="file" id="audio" class="border border-black p-2 w-96" name="audio" accept="audio/*" required>
    
    <?php
    $supportedLanguages = [
        'zh' => 'Chinese', 
        'pt' => 'Portuguese', 
        'ta' => 'Tamil', 
        'sw' => 'Swahili', 
        'km' => 'Khmer', 
        'jv' => 'Javanese',
        'nl' => 'Dutch', 
        'vi' => 'Vietnamese', 
        'br' => 'Breton', 
        'sq' => 'Albanian', 
        'si' => 'Sinhala', 
        'my' => 'Burmese',
        'kk' => 'Kazakh', 
        'ka' => 'Georgian', 
        'bo' => 'Tibetan', 
        'tg' => 'Tajik', 
        'yi' => 'Yiddish', 
        'hi' => 'Hindi',
        'th' => 'Thai', 
        'ur' => 'Urdu', 
        'hr' => 'Croatian', 
        'ne' => 'Nepali', 
        'so' => 'Somali', 
        'fo' => 'Faroese',
        'su' => 'Sundanese', 
        'yue' => 'Cantonese', 
        'sr' => 'Serbian', 
        'tl' => 'Tagalog', 
        'he' => 'Hebrew', 
        'mn' => 'Mongolian',
        'oc' => 'Occitan', 
        'tk' => 'Turkmen', 
        'mg' => 'Malagasy', 
        'la' => 'Latin', 
        'sl' => 'Slovenian', 
        'ca' => 'Catalan',
        'id' => 'Indonesian', 
        'uk' => 'Ukrainian', 
        'el' => 'Greek', 
        'da' => 'Danish', 
        'no' => 'Norwegian', 
        'kn' => 'Kannada',
        'et' => 'Estonian', 
        'eu' => 'Basque', 
        'nn' => 'Norwegian Nynorsk', 
        'haw' => 'Hawaiian', 
        'ar' => 'Arabic',
        'lv' => 'Latvian', 
        'bn' => 'Bengali', 
        'sn' => 'Shona', 
        'yo' => 'Yoruba', 
        'am' => 'Amharic', 
        'ms' => 'Malay',
        'bg' => 'Bulgarian', 
        'ml' => 'Malayalam', 
        'cy' => 'Welsh', 
        'ps' => 'Pashto', 
        'sd' => 'Sindhi', 
        'gu' => 'Gujarati',
        'ru' => 'Russian', 
        'ko' => 'Korean', 
        'ro' => 'Romanian', 
        'sk' => 'Slovak', 
        'te' => 'Telugu', 
        'hy' => 'Armenian',
        'lb' => 'Luxembourgish', 
        'as' => 'Assamese', 
        'ln' => 'Lingala', 
        'en' => 'English', 
        'de' => 'German', 
        'sv' => 'Swedish',
        'fi' => 'Finnish', 
        'gl' => 'Galician', 
        'af' => 'Afrikaans', 
        'lt' => 'Lithuanian', 
        'bs' => 'Bosnian', 
        'pl' => 'Polish',
        'fa' => 'Persian', 
        'az' => 'Azerbaijani', 
        'pa' => 'Punjabi', 
        'ha' => 'Hausa', 
        'ja' => 'Japanese', 
        'tr' => 'Turkish',
        'be' => 'Belarusian', 
        'lo' => 'Lao', 
        'ht' => 'Haitian Creole', 
        'ba' => 'Bashkir', 
        'uz' => 'Uzbek', 
        'mt' => 'Maltese',
        'es' => 'Spanish', 
        'it' => 'Italian', 
        'hu' => 'Hungarian', 
        'mk' => 'Macedonian', 
        'is' => 'Icelandic', 
        'mr' => 'Marathi',
        'fr' => 'French', 
        'cs' => 'Czech', 
        'mi' => 'Maori', 
        'sa' => 'Sanskrit', 
        'tt' => 'Tatar'
    ];
    ?>
    <label for="language" class="text-sm mt-4">Idioma:</label>
    <select id="language" name="language" class="border border-black p-2 w-96">
        <option disabled selected>Selecione um idioma</option>
        <?php foreach ($supportedLanguages as $code => $name): ?>
            <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo ucfirst($name); ?></option>
        <?php endforeach; ?>
    </select>
    
    <label for="response_format" class="text-sm mt-4">Formato da Resposta:</label>
    <select id="response_format" name="response_format" class="border border-black p-2 w-96" required>
        <option value="json" selected>json</option>
        <option value="verbose_json">verbose_json</option>
        <option value="text">text</option>
    </select>

    <label for="prompt" class="text-sm mt-4">Prompt (opcional):</label>
    <textarea id="prompt" name="prompt" placeholder="Prompt (opcional)" class="border border-black p-2 w-96" rows="4"></textarea>   
    <button type="submit" class="bg-black text-white p-2 mt-4 w-96">Transcrever</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $originalFileName = $_FILES['audio']['name'];
    $tmpFilePath = $_FILES['audio']['tmp_name'];
    $newFilePath = sys_get_temp_dir() . '/' . $originalFileName;
    
    try {
        move_uploaded_file($tmpFilePath, $newFilePath);

        $transcriptionParams = [
            'file' => $newFilePath,
            'model' => 'whisper-large-v3',
            'response_format' => $_POST['response_format'] ?? 'json',
            'temperature' => $_POST['temperature'] ?? 0.0,
        ];

        if (isset($_POST['language'])) {
            $transcriptionParams['language'] = $_POST['language'] ?? 'en';
        }

        if (isset($_POST['prompt'])) {
            $transcriptionParams['prompt'] = $_POST['prompt'];
        }

        $transcription = $groq->audio()->transcriptions()->create($transcriptionParams);
        
        if ($_POST['response_format'] === 'verbose_json') {
            // Aqui você pode processar a transcrição para incluir timestamps, se necessário
            echo '<pre class="text-xs">';
            echo json_encode($transcription, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo '</pre>';
        } elseif ($_POST['response_format'] === 'text') {
            // Retorna apenas o texto da transcrição
            echo '<p class="text-xs">';
            echo $transcription ?? '';
            echo '</p>';
        } else {
            // Formato padrão é json
            echo '<pre class="text-xs">';
            echo json_encode($transcription, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo '</pre>';
        }
    } catch (LucianoTonet\GroqPHP\GroqException $e) {
        echo "<strong>Error:</strong> <br><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } finally {
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
        }
    }
}