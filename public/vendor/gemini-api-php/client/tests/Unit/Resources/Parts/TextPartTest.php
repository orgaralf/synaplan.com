<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources\Parts;

use GeminiAPI\Resources\Parts\TextPart;
use PHPUnit\Framework\TestCase;

class TextPartTest extends TestCase
{
    public function testConstructor()
    {
        $part = new TextPart('');
        self::assertInstanceOf(TextPart::class, $part);
    }

    public function testJsonSerialize()
    {
        $part = new TextPart('this is a text');
        self::assertEquals(['text' => 'this is a text'], $part->jsonSerialize());
    }

    public function test__toString()
    {
        $part = new TextPart('this is a text');
        $expected = '{"text":"this is a text"}';
        self::assertEquals($expected, (string) $part);
    }
}
