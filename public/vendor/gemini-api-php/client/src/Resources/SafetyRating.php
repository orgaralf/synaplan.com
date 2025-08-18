<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

use GeminiAPI\Enums\HarmCategory;
use GeminiAPI\Enums\HarmProbability;
use JsonSerializable;

class SafetyRating implements JsonSerializable
{
    public function __construct(
        public readonly HarmCategory $category,
        public readonly HarmProbability $probability,
        public readonly ?bool $blocked,
    ) {
    }

    /**
     * @param array{
     *     category: string,
     *     probability: string,
     *     blocked?: bool|null,
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $category = HarmCategory::from($array['category']);
        $probability = HarmProbability::from($array['probability']);
        $blocked = $array['blocked'] ?? null;

        return new self($category, $probability, $blocked);
    }

    /**
     * @return array<string, bool|string>
     */
    public function jsonSerialize(): array
    {
        $arr = [
            'category' => $this->category->value,
            'probability' => $this->probability->value,
        ];

        if ($this->blocked !== null) {
            $arr['blocked'] = $this->blocked;
        }

        return $arr;
    }
}
