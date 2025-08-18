<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ListModelsModelResponse implements ResponseContract
{
    /**
     * @param string $name
     * @param string $modifiedAt
     * @param int $size
     * @param string $digest
     * @param ListModelsModelDetailsResponse $details
     */
    private function __construct(
        public readonly string                         $name,
        public readonly string                         $modifiedAt,
        public readonly int                            $size,
        public readonly string                         $digest,
        public readonly ListModelsModelDetailsResponse $details,
    )
    {
    }

    /**
     * @param array $attributes
     * @return ListModelsModelResponse
     */
    public static function from(array $attributes): ListModelsModelResponse
    {
        return new self(
            name: $attributes['name'],
            modifiedAt: $attributes['modified_at'],
            size: $attributes['size'],
            digest: $attributes['digest'],
            details: ListModelsModelDetailsResponse::from($attributes['details']),
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'modified_at' => $this->modifiedAt,
            'size' => $this->size,
            'digest' => $this->digest,
            'details' => $this->details->toArray(),
        ];
    }
}
