<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

use GeminiAPI\Enums\BlockReason;
use GeminiAPI\Traits\ArrayTypeValidator;
use JsonSerializable;

class PromptFeedback implements JsonSerializable
{
    use ArrayTypeValidator;

    /**
     * @param ?BlockReason $blockReason
     * @param SafetyRating[] $safetyRatings
     */
    public function __construct(
        public readonly ?BlockReason $blockReason,
        public readonly array $safetyRatings,
    ) {
        $this->ensureArrayOfType($safetyRatings, SafetyRating::class);
    }

    /**
     * @param array{
     *     blockReason: string|null,
     *     safetyRatings?: array<int, array{category: string, probability: string, blocked?: bool|null}>
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $blockReason = BlockReason::tryFrom($array['blockReason'] ?? '');
        $safetyRatings = array_map(
            static fn (array $rating): SafetyRating => SafetyRating::fromArray($rating),
            $array['safetyRatings'] ?? [],
        );

        return new self($blockReason, $safetyRatings);
    }

    /**
     * @return array<string, string|array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        $arr = [];

        if ($this->blockReason) {
            $arr['blockReason'] = $this->blockReason->value;
        }

        if (!empty($this->safetyRatings)) {
            $arr['safetyRatings'] = $this->safetyRatings;
        }

        return $arr;
    }
}
