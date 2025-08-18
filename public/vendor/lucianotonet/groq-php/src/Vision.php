<?php

namespace LucianoTonet\GroqPHP;

class Vision
{
    private Groq $groq;
    private string $defaultModel = 'llava-v1.5-7b-4096-preview';

    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Analyzes an image and returns the model's response.
     * 
     * @param string $imagePathOrUrl Path or URL of the image.
     * @param string $prompt Question or context for the analysis.
     * @param array $options Additional options for the analysis.
     * @return array Model's response.
     */
    public function analyze(string $imagePathOrUrl, string $prompt, array $options = []): array
    {
        $imageContent = $this->getImageContent($imagePathOrUrl);

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $imageContent],
                    ],
                ],
            ],
        ];

        $model = $options['model'] ?? $this->defaultModel;

        $requestOptions = [
            'model' => $model,
            'messages' => $messages,
        ];

        // Adicionar opções adicionais se fornecidas
        if (isset($options['temperature'])) {
            $requestOptions['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $requestOptions['max_tokens'] = $options['max_tokens'];
        }

        return $this->groq->chat()->completions()->create($requestOptions);
    }

    /**
     * Retrieves the image content, either from a local file or a URL.
     * 
     * @param string $imagePathOrUrl Path or URL of the image.
     * @return string Image content in base64 or original URL.
     * @throws GroqException If the image file is not found or exceeds size limits.
     */
    private function getImageContent(string $imagePathOrUrl): string
    {
        if (filter_var($imagePathOrUrl, FILTER_VALIDATE_URL)) {
            // Verificar o tamanho da imagem URL (limite de 20MB)
            $headers = get_headers($imagePathOrUrl, 1);
            $fileSize = isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : 0;
            if ($fileSize > 20 * 1024 * 1024) {
                throw new GroqException(
                    "Image URL exceeds 20MB size limit",
                    400,
                    'ImageSizeLimitExceededException'
                );
            }
            return $imagePathOrUrl;
        }

        if (file_exists($imagePathOrUrl)) {
            // Verificar o tamanho do arquivo local (limite de 4MB para base64)
            $fileSize = filesize($imagePathOrUrl);
            if ($fileSize > 4 * 1024 * 1024) {
                throw new GroqException(
                    "Local image file exceeds 4MB size limit for base64 encoding",
                    400,
                    'ImageSizeLimitExceededException'
                );
            }
            $imageData = base64_encode(file_get_contents($imagePathOrUrl));
            $mimeType = mime_content_type($imagePathOrUrl);
            return "data:$mimeType;base64," . $imageData;
        }

        throw new GroqException(
            "Image file not found: $imagePathOrUrl",
            404,
            'FileNotFoundException'
        );
    }

    /**
     * Sets the default model for vision analysis.
     * 
     * @param string $model The model to use as default.
     */
    public function setDefaultModel(string $model): void
    {
        $this->defaultModel = $model;
    }
}