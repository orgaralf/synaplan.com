<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class CreateModelResponse implements ResponseContract
{
    /**
     * @param string $status
     */
    private function __construct(
        public readonly string $status,
    )
    {
    }

    /**
     * @param array $attributes
     * @return CreateModelResponse
     */
    public static function from(array $attributes): CreateModelResponse
    {
        return new self(
            status: $attributes['status'],
        );
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
