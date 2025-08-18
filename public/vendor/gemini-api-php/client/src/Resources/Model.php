<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

use JsonSerializable;

class Model implements JsonSerializable
{
    /**
     * @param string $name
     * @param string $version
     * @param string $displayName
     * @param string $description
     * @param int $inputTokenLimit
     * @param int $outputTokenLimit
     * @param string[] $supportedGenerationMethods
     * @param float|null $temperature
     * @param float|null $topP
     * @param int|null $topK
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $displayName,
        public readonly string $description,
        public readonly int $inputTokenLimit,
        public readonly int $outputTokenLimit,
        public readonly array $supportedGenerationMethods,
        public readonly ?float $temperature,
        public readonly ?float $topP,
        public readonly ?int $topK,
    ) {
    }

    /**
     * @param array{
     *   name: string,
     *   version: string,
     *   displayName: string,
     *   description: string,
     *   inputTokenLimit: int,
     *   outputTokenLimit: int,
     *   supportedGenerationMethods: string[],
     *   temperature?: float,
     *   topP?: float,
     *   topK?: int,
     *  } $model
     * @return self
     */
    public static function fromArray(array $model): self
    {
        return new self(
            $model['name'],
            $model['version'],
            $model['displayName'],
            $model['description'],
            $model['inputTokenLimit'],
            $model['outputTokenLimit'],
            $model['supportedGenerationMethods'],
            $model['temperature'] ?? null,
            $model['topP'] ?? null,
            $model['topK'] ?? null,
        );
    }

    /**
     * @return array{
     *    name: string,
     *    version: string,
     *    displayName: string,
     *    description: string,
     *    inputTokenLimit: int,
     *    outputTokenLimit: int,
     *    supportedGenerationMethods: string[],
     *    temperature?: float|null,
     *    topP?: float|null,
     *    topK?: int|null,
     *   }
     */
    public function jsonSerialize(): array
    {
        $arr = [
            'name' => $this->name,
            'version' => $this->version,
            'displayName' => $this->displayName,
            'description' => $this->description,
            'inputTokenLimit' => $this->inputTokenLimit,
            'outputTokenLimit' => $this->outputTokenLimit,
            'supportedGenerationMethods' => $this->supportedGenerationMethods,
        ];

        if ($this->temperature !== null) {
            $arr['temperature'] = $this->temperature;
        }

        if ($this->topP !== null) {
            $arr['topP'] = $this->topP;
        }

        if ($this->topK !== null) {
            $arr['topK'] = $this->topK;
        }

        return $arr;
    }
}
