<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources;

use GeminiAPI\Enums\MimeType;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\FilePart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Resources\Parts\TextPart;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function testConstructorWithNoContents()
    {
        $content = new Content([], Role::User);
        self::assertInstanceOf(Content::class, $content);
        self::assertEmpty($content->parts);
        self::assertEquals(Role::User, $content->role);
    }

    public function testConstructorWithContents()
    {
        $content = new Content(
            [new TextPart('this is a text')],
            Role::User,
        );
        self::assertInstanceOf(Content::class, $content);
        self::assertEquals([new TextPart('this is a text')], $content->parts);
        self::assertEquals(Role::User, $content->role);
    }

    public function testText()
    {
        $content = Content::text('this is a text', Role::Model);
        self::assertInstanceOf(Content::class, $content);
        self::assertEquals([new TextPart('this is a text')], $content->parts);
        self::assertEquals(Role::Model, $content->role);
    }

    public function testImage()
    {
        $content = Content::image(
            MimeType::IMAGE_JPEG,
            'this is an image',
            Role::Model,
        );
        self::assertInstanceOf(Content::class, $content);
        self::assertEquals([new ImagePart(MimeType::IMAGE_JPEG, 'this is an image')], $content->parts);
        self::assertEquals(Role::Model, $content->role);
    }

    public function testTextAndImage()
    {
        $content = Content::textAndImage(
            'this is a text',
            MimeType::IMAGE_JPEG,
            'this is an image',
            Role::Model,
        );
        $parts = [
            new TextPart('this is a text'),
            new ImagePart(MimeType::IMAGE_JPEG, 'this is an image'),
        ];
        self::assertInstanceOf(Content::class, $content);
        self::assertEquals($parts, $content->parts);
        self::assertEquals(Role::Model, $content->role);
    }

    public function testAddText()
    {
        $content = new Content([], Role::User);
        $content->addText('this is a text');
        self::assertEquals([new TextPart('this is a text')], $content->parts);
    }

    public function testAddImage()
    {
        $content = new Content([], Role::User);
        $content->addImage(MimeType::IMAGE_JPEG, 'this is an image');
        self::assertEquals([new ImagePart(MimeType::IMAGE_JPEG, 'this is an image')], $content->parts);
    }

    public function testFromArrayWithNoParts()
    {
        $content = Content::fromArray([
            'parts' => [],
            'role' => 'user',
        ]);
        self::assertInstanceOf(Content::class, $content);
        self::assertEmpty($content->parts);
        self::assertEquals(Role::User, $content->role);
    }

    public function testFromArrayWithParts()
    {
        $content = Content::fromArray([
            'parts' => [
                ['text' => 'this is a text'],
                ['inlineData' => ['mimeType' => 'image/jpeg', 'data' => 'this is an image']],
            ],
            'role' => 'user',
        ]);
        $parts = [
            new TextPart('this is a text'),
            new FilePart(MimeType::IMAGE_JPEG, 'this is an image'),
        ];
        self::assertInstanceOf(Content::class, $content);
        self::assertEquals($parts, $content->parts);
        self::assertEquals(Role::User, $content->role);
    }
}
