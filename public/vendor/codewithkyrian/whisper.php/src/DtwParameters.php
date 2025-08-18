<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use InvalidArgumentException;

class DtwParameters
{
    /**
     * @param  int  $nTop  Number of top text layers used from model, should be 0 < n_top <= model n_text_layer
     */
    public function __construct(
        public DtwMode $mode = DtwMode::None,
        public int $nTop = -1,
        public ?array $aheads = null,
        public ?DtwModelPreset $modelPreset = null,
        public int $dtwMemSize = 134217728  // 1024 * 1024 * 128
    ) {}

    public static function default(): self
    {
        return new self;
    }

    public function withTopMost(int $nTop): self
    {
        if ($nTop <= 0) {
            throw new InvalidArgumentException('n_top should be greater than 0');
        }

        $this->mode = DtwMode::TopMost;
        $this->nTop = $nTop;

        return $this;
    }

    public function withCustomAheads(array $aheads): self
    {
        $this->mode = DtwMode::Custom;
        $this->aheads = $aheads;

        return $this;
    }

    public function withModelPreset(DtwModelPreset $preset): self
    {
        $this->mode = DtwMode::ModelPreset;
        $this->modelPreset = $preset;

        return $this;
    }

    public function withMemSize(int $memSize): self
    {
        $this->dtwMemSize = $memSize;

        return $this;
    }
}
