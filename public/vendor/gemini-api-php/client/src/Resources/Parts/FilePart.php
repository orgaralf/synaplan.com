<?php

declare(strict_types=1);

namespace GeminiAPI\Resources\Parts;

use GeminiAPI\Enums\MimeType;
use JsonSerializable;

use function json_encode;

class FilePart implements PartInterface, JsonSerializable
{
    public function __construct(
        public readonly MimeType $mimeType,
        public readonly string $data,
    ) {
    }

    /**
     * @return array{
     *     inlineData: array{
     *         mimeType: string,
     *         data: string,
     *     },
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'inlineData' => [
                'mimeType' => $this->mimeType->value,
                'data' => $this->data,
            ],
        ];
    }

    public function __toString(): string
    {
        return json_encode($this) ?: '';
    }
}
