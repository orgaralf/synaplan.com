<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Requests;

use GeminiAPI\Enums\HarmBlockThreshold;
use GeminiAPI\Enums\HarmCategory;
use GeminiAPI\Enums\HarmProbability;
use GeminiAPI\Enums\Role;
use GeminiAPI\GenerationConfig;
use GeminiAPI\Requests\GenerateContentRequest;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\SafetyRating;
use GeminiAPI\SafetySetting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GenerateContentRequestTest extends TestCase
{
    public function testConstructorWithNoContents(): void
    {
        $request = new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [],
            [],
            null,
        );
        self::assertInstanceOf(GenerateContentRequest::class, $request);
    }

    public function testConstructorWithContents(): void
    {
        $request = new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [
                new Content([], Role::User),
                new Content([], Role::Model),
            ],
            [],
            null,
        );
        self::assertInstanceOf(GenerateContentRequest::class, $request);
    }

    public function testConstructorWithInvalidContents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [
                new Content([], Role::User),
                new TextPart('This is a text'),
            ],
            [],
            null,
        );
    }

    public function testConstructorWithSafetySettings(): void
    {
        $request = new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [],
            [
                new SafetySetting(
                    HarmCategory::HARM_CATEGORY_HATE_SPEECH,
                    HarmBlockThreshold::BLOCK_LOW_AND_ABOVE,
                ),
                new SafetySetting(
                    HarmCategory::HARM_CATEGORY_MEDICAL,
                    HarmBlockThreshold::BLOCK_MEDIUM_AND_ABOVE,
                ),
            ],
            null,
        );
        self::assertInstanceOf(GenerateContentRequest::class, $request);
    }

    public function testConstructorWithInvalidSafetySettings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [],
            [
                new SafetySetting(
                    HarmCategory::HARM_CATEGORY_UNSPECIFIED,
                    HarmBlockThreshold::HARM_BLOCK_THRESHOLD_UNSPECIFIED,
                ),
                new SafetyRating(
                    HarmCategory::HARM_CATEGORY_UNSPECIFIED,
                    HarmProbability::HARM_PROBABILITY_UNSPECIFIED,
                    null,
                )
            ],
            null,
        );
    }

    public function testConstructorWithGenerationConfig(): void
    {
        $request = new GenerateContentRequest(
            ModelName::GEMINI_PRO,
            [],
            [],
            new GenerationConfig(),
        );
        self::assertInstanceOf(GenerateContentRequest::class, $request);
    }

    public function testGetOperation(): void
    {
        $request = new GenerateContentRequest(ModelName::GEMINI_PRO, []);
        self::assertEquals('models/gemini-pro:generateContent', $request->getOperation());
    }

    public function testGetHttpMethod(): void
    {
        $request = new GenerateContentRequest(ModelName::GEMINI_PRO, []);
        self::assertEquals('POST', $request->getHttpMethod());
    }

    public function testGetHttpPayload(): void
    {
        $request = new GenerateContentRequest(
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
        $request = new GenerateContentRequest(
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
        $request = new GenerateContentRequest(
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
