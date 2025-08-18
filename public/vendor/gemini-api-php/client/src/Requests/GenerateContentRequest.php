<?php

declare(strict_types=1);

namespace GeminiAPI\Requests;

use GeminiAPI\Enums\ModelName;
use GeminiAPI\GenerationConfig;
use GeminiAPI\Resources\Content;
use GeminiAPI\SafetySetting;
use GeminiAPI\Traits\ArrayTypeValidator;
use GeminiAPI\Traits\ModelNameToString;
use JsonSerializable;

use function json_encode;

class GenerateContentRequest implements JsonSerializable, RequestInterface
{
    use ArrayTypeValidator;
    use ModelNameToString;

    /**
     * @param ModelName|string $modelName
     * @param Content[] $contents
     * @param SafetySetting[] $safetySettings
     * @param GenerationConfig|null $generationConfig
     * @param ?Content $systemInstruction
     */
    public function __construct(
        public readonly ModelName|string $modelName,
        public readonly array $contents,
        public readonly array $safetySettings = [],
        public readonly ?GenerationConfig $generationConfig = null,
        public readonly ?Content $systemInstruction = null,
    ) {
        $this->ensureArrayOfType($this->contents, Content::class);
        $this->ensureArrayOfType($this->safetySettings, SafetySetting::class);
    }

    public function getOperation(): string
    {
        return "{$this->modelNameToString($this->modelName)}:generateContent";
    }

    public function getHttpMethod(): string
    {
        return 'POST';
    }

    public function getHttpPayload(): string
    {
        return (string) $this;
    }

    /**
     * @return array{
     *     model: string,
     *     contents: Content[],
     *     safetySettings?: SafetySetting[],
     *     generationConfig?: GenerationConfig,
     *     systemInstruction?: Content,
     * }
     */
    public function jsonSerialize(): array
    {
        $arr = [
            'model' => $this->modelNameToString($this->modelName),
            'contents' => $this->contents,
        ];

        if (!empty($this->safetySettings)) {
            $arr['safetySettings'] = $this->safetySettings;
        }

        if ($this->generationConfig) {
            $arr['generationConfig'] = $this->generationConfig;
        }

        if ($this->systemInstruction) {
            $arr['systemInstruction'] = $this->systemInstruction;
        }

        return $arr;
    }

    public function __toString(): string
    {
        return json_encode($this) ?: '';
    }
}
