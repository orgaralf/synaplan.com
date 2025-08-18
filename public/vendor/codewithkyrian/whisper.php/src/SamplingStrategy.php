<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

class SamplingStrategy
{
    const GREEDY = 0;

    const BEAM_SEARCH = 1;

    public int $bestOf;

    public int $beamSize;

    public float $patience;

    public function __construct(public int $type = self::GREEDY) {}

    public static function greedy(int $bestOf = 1): self
    {
        $strategy = new self(self::GREEDY);
        $strategy->bestOf = $bestOf;

        return $strategy;
    }

    public static function beamSearch(int $beamSize = 5, float $patience = -1.0): self
    {
        $strategy = new self(self::BEAM_SEARCH);
        $strategy->beamSize = $beamSize;
        $strategy->patience = $patience;

        return $strategy;
    }
}
