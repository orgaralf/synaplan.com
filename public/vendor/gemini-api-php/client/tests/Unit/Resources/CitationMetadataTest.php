<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources;

use GeminiAPI\Resources\CitationMetadata;
use GeminiAPI\Resources\CitationSource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CitationMetadataTest extends TestCase
{
    public function testConstructorWithNoSources()
    {
        $citationMetadata = new CitationMetadata();
        self::assertInstanceOf(CitationMetadata::class, $citationMetadata);
    }

    public function testConstructorWithSources()
    {
        $citationMetadata = new CitationMetadata(
            [
                new CitationSource(null, null, null, null),
                new CitationSource(null, null, null, null),
            ],
        );
        self::assertInstanceOf(CitationMetadata::class, $citationMetadata);
    }

    public function testConstructorWithInvalidSources()
    {
        $this->expectException(InvalidArgumentException::class);

        new CitationMetadata(
            [
                new CitationSource(null, null, null, null),
                [null, null, null, null],
            ],
        );
    }

    public function testFromArrayWithNoSources()
    {
        $citationMetadata = CitationMetadata::fromArray([
            'citationSources' => [],
        ]);
        self::assertInstanceOf(CitationMetadata::class, $citationMetadata);
    }

    public function testFromArrayWithSources()
    {
        $citationMetadata = CitationMetadata::fromArray([
            'citationSources' => [
                [
                    'startIndex' => 1,
                    'endIndex' => 49,
                ],
                [
                    'startIndex' => 50,
                    'endIndex' => 99,
                ],
            ],
        ]);
        self::assertInstanceOf(CitationMetadata::class, $citationMetadata);
    }
}
