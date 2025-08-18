<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources;

use GeminiAPI\Resources\ContentEmbedding;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ContentEmbeddingTest extends TestCase
{
    public function testConstructorWithEmptyArray()
    {
        $embedding = new ContentEmbedding([]);
        self::assertInstanceOf(ContentEmbedding::class, $embedding);
    }

    public function testConstructorWithFloatArray()
    {
        $embedding = new ContentEmbedding([0.0, 1.0]);
        self::assertInstanceOf(ContentEmbedding::class, $embedding);
    }

    public function testConstructorWithInvalidArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected float but found string');

        new ContentEmbedding([0.0, 1.0, '2.0']);
    }

    public function testFromArrayWithEmptyArray()
    {
        $embedding = ContentEmbedding::fromArray([
            'values' => [],
        ]);
        self::assertInstanceOf(ContentEmbedding::class, $embedding);
    }

    public function testFromArrayWithFloatArray()
    {
        $embedding = ContentEmbedding::fromArray([
            'values' => [0.0, 1.0],
        ]);
        self::assertInstanceOf(ContentEmbedding::class, $embedding);
    }

    public function testFromArrayWithMissingKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The required "values" key is missing or is not an array');

        ContentEmbedding::fromArray([
            'foo' => [0.0, 1.0],
        ]);
    }

    public function testFromArrayWithInvalidArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected float but found string');

        ContentEmbedding::fromArray([
            'values' => [0.0, 1.0, '2.0'],
        ]);
    }
}
