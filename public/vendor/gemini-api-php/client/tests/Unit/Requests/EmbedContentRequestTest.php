<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Requests;

use BadMethodCallException;
use GeminiAPI\Enums\TaskType;
use GeminiAPI\Requests\EmbedContentRequest;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use PHPUnit\Framework\TestCase;

class EmbedContentRequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
        );
        self::assertInstanceOf(EmbedContentRequest::class, $request);
    }

    public function testConstructorWithTaskType(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
            TaskType::RETRIEVAL_DOCUMENT,
        );
        self::assertInstanceOf(EmbedContentRequest::class, $request);
    }

    public function testConstructorWithTitle(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
            TaskType::RETRIEVAL_DOCUMENT,
            'this is a title',
        );
        self::assertInstanceOf(EmbedContentRequest::class, $request);
    }

    public function testConstructorWithTitleAndWrongTaskType(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Title is only applicable when TaskType is RETRIEVAL_DOCUMENT');

        new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
            TaskType::RETRIEVAL_QUERY,
            'this is a title',
        );
    }

    public function testGetHttpPayload(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
        );
        self::assertEquals('{"content":{"parts":[{"text":"this is a test"}],"role":"user"}}', $request->getHttpPayload());
    }

    public function testGetHttpMethod(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
        );
        self::assertEquals('POST', $request->getHttpMethod());
    }

    public function testGetOperation(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
        );
        self::assertEquals('models/embedding-001:embedContent', $request->getOperation());
    }

    public function testJsonSerialize(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            $content = Content::text('this is a test'),
            TaskType::RETRIEVAL_DOCUMENT,
            'this is a title',
        );
        $expected = [
            'content' => $content,
            'taskType' => TaskType::RETRIEVAL_DOCUMENT,
            'title' => 'this is a title',
        ];
        self::assertEquals($expected, $request->jsonSerialize());
    }

    public function test__toString(): void
    {
        $request = new EmbedContentRequest(
            ModelName::EMBEDDING_001,
            Content::text('this is a test'),
        );
        self::assertEquals('{"content":{"parts":[{"text":"this is a test"}],"role":"user"}}', (string) $request);
    }
}
