<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

use GeminiAPI\Enums\MimeType;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Parts\FilePart;
use GeminiAPI\Traits\ArrayTypeValidator;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Resources\Parts\PartInterface;
use GeminiAPI\Resources\Parts\TextPart;

class Content
{
    use ArrayTypeValidator;

    /**
     * @param PartInterface[] $parts
     * @param Role $role
     */
    public function __construct(
        public array $parts,
        public readonly Role $role,
    ) {
        $this->ensureArrayOfType($parts, PartInterface::class);
    }

    public function addText(string $text): self
    {
        $this->parts[] = new TextPart($text);

        return $this;
    }

    public function addImage(MimeType $mimeType, string $image): self
    {
        $this->parts[] = new ImagePart($mimeType, $image);

        return $this;
    }

    public function addFile(MimeType $mimeType, string $file): self
    {
        $this->parts[] = new FilePart($mimeType, $file);

        return $this;
    }

    public static function text(
        string $text,
        Role $role = Role::User,
    ): self {
        return new self(
            [
                new TextPart($text),
            ],
            $role,
        );
    }

    public static function image(
        MimeType $mimeType,
        string $image,
        Role $role = Role::User
    ): self {
        return new self(
            [
                new ImagePart($mimeType, $image),
            ],
            $role,
        );
    }

    public static function file(
        MimeType $mimeType,
        string $file,
        Role $role = Role::User
    ): self {
        return new self(
            [
                new FilePart($mimeType, $file),
            ],
            $role,
        );
    }

    public static function textAndImage(
        string $text,
        MimeType $mimeType,
        string $image,
        Role $role = Role::User,
    ): self {
        return new self(
            [
                new TextPart($text),
                new ImagePart($mimeType, $image),
            ],
            $role,
        );
    }

    public static function textAndFile(
        string $text,
        MimeType $mimeType,
        string $file,
        Role $role = Role::User,
    ): self {
        return new self(
            [
                new TextPart($text),
                new FilePart($mimeType, $file),
            ],
            $role,
        );
    }

    /**
     * @param array{
     *     parts: array<int, array{text?: string, inlineData?: array{mimeType: string, data: string}}>,
     *     role: string,
     * } $content
     * @return self
     */
    public static function fromArray(array $content): self
    {
        $parts = [];
        foreach ($content['parts'] as $part) {
            if (!empty($part['text'])) {
                $parts[] = new TextPart($part['text']);
            }

            if (!empty($part['inlineData'])) {
                $mimeType = MimeType::from($part['inlineData']['mimeType']);
                $parts[] = new FilePart($mimeType, $part['inlineData']['data']);
            }
        }

        return new self(
            $parts,
            Role::from($content['role']),
        );
    }
}
