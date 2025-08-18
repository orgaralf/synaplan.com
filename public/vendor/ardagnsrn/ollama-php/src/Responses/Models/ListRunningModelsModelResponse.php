<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ListRunningModelsModelResponse implements ResponseContract
{
    /**
     * @param string $name
     * @param string $model
     * @param int $size
     * @param string $digest
     * @param ListModelsModelDetailsResponse $details
     * @param string $expiresAt
     * @param int $sizeVram
     */
    private function __construct(
        public readonly string                         $name,
        public readonly string                         $model,
        public readonly int                            $size,
        public readonly string                         $digest,
        public readonly ListModelsModelDetailsResponse $details,
        public readonly string                         $expiresAt,
        public readonly int                            $sizeVram,
    )
    {
    }

    /**
     * @param array $attributes
     * @return ListRunningModelsModelResponse
     */
    public static function from(array $attributes): ListRunningModelsModelResponse
    {
        return new self(
            name: $attributes['name'],
            model: $attributes['model'],
            size: $attributes['size'],
            digest: $attributes['digest'],
            details: ListModelsModelDetailsResponse::from($attributes['details']),
            expiresAt: $attributes['expires_at'],
            sizeVram: $attributes['size_vram'],
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'model' => $this->model,
            'size' => $this->size,
            'digest' => $this->digest,
            'details' => $this->details->toArray(),
            'expires_at' => $this->expiresAt,
            'size_vram' => $this->sizeVram,
        ];
    }
}
