<?php

declare(strict_types=1);

namespace GeminiAPI;

use GeminiAPI\Traits\ArrayTypeValidator;
use JsonSerializable;
use UnexpectedValueException;

class GenerationConfig implements JsonSerializable
{
    use ArrayTypeValidator;

    /** @var array{
     *     candidateCount?: int,
     *     stopSequences?: string[],
     *     maxOutputTokens?: int,
     *     temperature?: float,
     *     topP?: float,
     *     topK?: int,
     * }
     */
    private array $config;

    public function withCandidateCount(int $candidateCount): self
    {
        if ($candidateCount < 0) {
            throw new UnexpectedValueException('Candidate count is negative');
        }

        $clone = clone $this;
        $clone->config['candidateCount'] = $candidateCount;

        return $clone;
    }

    /**
     * @param string[] $stopSequences
     * @return $this
     */
    public function withStopSequences(array $stopSequences): self
    {
        $this->ensureArrayOfString($stopSequences);

        $clone = clone $this;
        $clone->config['stopSequences'] = $stopSequences;

        return $clone;
    }

    public function withMaxOutputTokens(int $maxOutputTokens): self
    {
        if ($maxOutputTokens < 0) {
            throw new UnexpectedValueException('Max output tokens is negative');
        }

        $clone = clone $this;
        $clone->config['maxOutputTokens'] = $maxOutputTokens;

        return $clone;
    }

    public function withTemperature(float $temperature): self
    {
        if ($temperature < 0.0 || $temperature > 1.0) {
            throw new UnexpectedValueException('Temperature is negative or more than 1');
        }

        $clone = clone $this;
        $clone->config['temperature'] = $temperature;

        return $clone;
    }

    public function withTopP(float $topP): self
    {
        if ($topP < 0.0) {
            throw new UnexpectedValueException('Top-p is negative');
        }

        $clone = clone $this;
        $clone->config['topP'] = $topP;

        return $clone;
    }

    public function withTopK(int $topK): self
    {
        if ($topK < 0) {
            throw new UnexpectedValueException('Top-k is negative');
        }

        $clone = clone $this;
        $clone->config['topK'] = $topK;

        return $clone;
    }

    /**
     * @return array{
     *      candidateCount?: int,
     *      stopSequences?: string[],
     *      maxOutputTokens?: int,
     *      temperature?: float,
     *      topP?: float,
     *      topK?: int,
     *  }
     */
    public function jsonSerialize(): array
    {
        return $this->config;
    }
}
