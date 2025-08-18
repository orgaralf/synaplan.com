<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ListModelsModelDetailsResponse implements ResponseContract
{
    /**
     * @param string $format
     * @param string $family
     * @param string $parameterSize
     * @param string $quantizationLevel
     * @param array $families
     * @param string $parentModel
     */
    private function __construct(
        public readonly string $format,
        public readonly string $family,
        public readonly string $parameterSize,
        public readonly string $quantizationLevel,
        public readonly array  $families = [],
        public readonly string $parentModel = '',
    )
    {
    }

    /**
     * @param array $attributes
     * @return ListModelsModelDetailsResponse
     */
    public static function from(array $attributes): ListModelsModelDetailsResponse
    {
        return new self(
            format: $attributes['format'],
            family: $attributes['family'],
            parameterSize: $attributes['parameter_size'],
            quantizationLevel: $attributes['quantization_level'],
            families: $attributes['families'] ?? [],
            parentModel: $attributes['parent_model'],
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'family' => $this->family,
            'parameter_size' => $this->parameterSize,
            'quantization_level' => $this->quantizationLevel,
            'families' => $this->families,
            'parent_model' => $this->parentModel,
        ];
    }
}
