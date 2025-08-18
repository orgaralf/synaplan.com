<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class PullModelResponse implements ResponseContract
{
    /**
     * @param string $status
     * @param string|null $digest
     * @param int|null $total
     * @param int|null $completed
     */
    private function __construct(
        public readonly string  $status,
        public readonly ?string $digest,
        public readonly ?int    $total,
        public readonly ?int    $completed,
    )
    {
    }

    /**
     * @param array $attributes
     * @return PullModelResponse
     */
    public static function from(array $attributes): PullModelResponse
    {
        return new self(
            status: $attributes['status'],
            digest: $attributes['digest'] ?? null,
            total: $attributes['total'] ?? null,
            completed: $attributes['completed'] ?? null,
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
            'completed' => $this->completed,
        ];
    }
}
