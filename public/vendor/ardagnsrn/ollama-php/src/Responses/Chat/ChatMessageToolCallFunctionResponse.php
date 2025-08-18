<?php

namespace ArdaGnsrn\Ollama\Responses\Chat;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ChatMessageToolCallFunctionResponse implements ResponseContract
{
    /**
     * @param string $name
     * @param array $arguments
     */
    private function __construct(
        public readonly string $name,
        public readonly array  $arguments,
    )
    {
    }

    /**
     * @param array $attributes
     * @return ChatMessageToolCallFunctionResponse
     */
    public static function from(array $attributes): ChatMessageToolCallFunctionResponse
    {
        return new self(
            name: $attributes['name'],
            arguments: $attributes['arguments'],
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}
