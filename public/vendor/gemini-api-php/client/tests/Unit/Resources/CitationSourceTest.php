<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources;

use GeminiAPI\Resources\CitationSource;
use PHPUnit\Framework\TestCase;

class CitationSourceTest extends TestCase
{
    public function testConstructor()
    {
        $citationSource = new CitationSource(
            1,
            49,
            'test-uri',
            'test-license',
        );
        self::assertInstanceOf(CitationSource::class, $citationSource);
        self::assertEquals(1, $citationSource->startIndex);
        self::assertEquals(49, $citationSource->endIndex);
        self::assertEquals('test-uri', $citationSource->uri);
        self::assertEquals('test-license', $citationSource->license);
    }

    public function testFromArray()
    {
        $citationSource = CitationSource::fromArray([
            'startIndex' => 1,
            'endIndex' => 49,
            'uri' => 'test-uri',
            'license' => 'test-license',
        ]);
        self::assertInstanceOf(CitationSource::class, $citationSource);
        self::assertEquals(1, $citationSource->startIndex);
        self::assertEquals(49, $citationSource->endIndex);
        self::assertEquals('test-uri', $citationSource->uri);
        self::assertEquals('test-license', $citationSource->license);
    }

    public function testJsonSerialize()
    {
        $citationSource = new CitationSource(
            1,
            49,
            'test-uri',
            'test-license',
        );
        $expected = [
            'startIndex' => 1,
            'endIndex' => 49,
            'uri' => 'test-uri',
            'license' => 'test-license',
        ];
        self::assertEquals($expected, $citationSource->jsonSerialize());
    }
}
