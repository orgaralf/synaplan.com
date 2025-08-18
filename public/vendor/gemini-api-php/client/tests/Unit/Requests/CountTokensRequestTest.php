<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Requests;

use GeminiAPI\Enums\Role;
use GeminiAPI\Requests\CountTokensRequest;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CountTokensRequestTest extends TestCase
{
    public function testConstructorWithNoContents(): void
    {
        $request = new CountTokensRequest(ModelName::GEMINI_PRO, []);
        self::assertInstanceOf(CountTokensRequest::class, $request);
    }

    public function testConstructorWithContents(): void
    {
        $request = new CountTokensRequest(
            ModelName::GEMINI_PRO,
            [
                new Content([], Role::User),
                new Content([], Role::Model),
            ],
        );

        self::assertInstanceOf(CountTokensRequest::class, $request);
    }

    public function testConstructorWithInvalidContents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CountTokensRequest(
            ModelName::GEMINI_PRO,
            // @phpstan-ignore-next-line
            [
                new Content([], Role::User),
                new TextPart('This is a text'),
            ],
        );
    }

    public function testGetOperation(): void
    {
        $request = new CountTokensRequest(ModelName::GEMINI_PRO, []);
        self::assertEquals('models/gemini-pro:countTokens', $request->getOperation());
    }

    public function testGetHttpMethod(): void
    {
        $request = new CountTokensRequest(ModelName::GEMINI_PRO, []);
        self::assertEquals('POST', $request->getHttpMethod());
    }

    public function testGetHttpPayload(): void
    {
        $request = new CountTokensRequest(
            ModelName::GEMINI_PRO,
            [
                new Content([new TextPart('This is a text')], Role::User),
            ],
        );

        $expected = '{"model":"models\/gemini-pro","contents":[{"parts":[{"text":"This is a text"}],"role":"user"}]}';
        self::assertEquals($expected, $request->getHttpPayload());
    }

    public function testJsonSerialize(): void
    {
        $request = new CountTokensRequest(
            ModelName::GEMINI_PRO,
            [
                new Content([new TextPart('This is a text')], Role::User),
            ],
        );

        $expected = [
            'model' => 'models/gemini-pro',
            'contents' => [
                new Content([new TextPart('This is a text')], Role::User),
            ],
        ];
        self::assertEquals($expected, $request->jsonSerialize());
    }

    public function test__toString(): void
    {
        $request = new CountTokensRequest(
            ModelName::GEMINI_PRO,
            [
                new Content(
                    [new TextPart('This is a text')],
                    Role::User,
                )
            ],
        );

        $expected = '{"model":"models\/gemini-pro","contents":[{"parts":[{"text":"This is a text"}],"role":"user"}]}';
        self::assertEquals($expected, (string) $request);
    }
}
