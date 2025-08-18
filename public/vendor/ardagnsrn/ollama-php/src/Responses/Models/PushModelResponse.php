<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class PushModelResponse implements ResponseContract
{
    /**
     * @param string $status
     * @param string|null $digest
     * @param int|null $total
     */
    private function __construct(
        public readonly string  $status,
        public readonly ?string $digest,
        public readonly ?int    $total,
    )
    {
    }

    /**
     * @param array $attributes
     * @return PushModelResponse
     */
    public static function from(array $attributes): PushModelResponse
    {
        return new self(
            status: $attributes['status'],
            digest: $attributes['digest'] ?? null,
            total: $attributes['total'] ?? null,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'digest' => $this->digest,
            'total' => $this->total,
        ];
    }
}
