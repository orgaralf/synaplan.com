<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

use GeminiAPI\Traits\ArrayTypeValidator;

class CitationMetadata
{
    use ArrayTypeValidator;

    /**
     * @param CitationSource[] $citationSources
     */
    public function __construct(
        public readonly array $citationSources = [],
    ) {
        $this->ensureArrayOfType($citationSources, CitationSource::class);
    }

    /**
     * @param array{
     *  citationSources: array<int, array{startIndex?: int|null, endIndex?: int|null, uri?: string|null, license?: string|null}>,
     * } $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $citationSources = array_map(
            static fn (array $source): CitationSource => CitationSource::fromArray($source),
            $array['citationSources'] ?? [],
        );

        return new self($citationSources);
    }
}
