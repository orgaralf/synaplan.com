<?php

declare(strict_types=1);

namespace GeminiAPI\Requests;

use BadMethodCallException;
use GeminiAPI\Enums\ModelName;
use GeminiAPI\Enums\TaskType;
use GeminiAPI\Resources\Content;
use GeminiAPI\Traits\ModelNameToString;
use JsonSerializable;

use function json_encode;

class EmbedContentRequest implements JsonSerializable, RequestInterface
{
    use ModelNameToString;

    public function __construct(
        public readonly ModelName|string $modelName,
        public readonly Content $content,
        public readonly ?TaskType $taskType = null,
        public readonly ?string $title = null,
    ) {
        if (isset($this->title) && $this->taskType !== TaskType::RETRIEVAL_DOCUMENT) {
            throw new BadMethodCallException('Title is only applicable when TaskType is RETRIEVAL_DOCUMENT');
        }
    }

    public function getOperation(): string
    {
        return "{$this->modelNameToString($this->modelName)}:embedContent";
    }

    public function getHttpMethod(): string
    {
        return 'POST';
    }

    public function getHttpPayload(): string
    {
        return (string) $this;
    }

    /**
     * @return array{
     *     content: Content,
     *     taskType?: TaskType,
     *     title?: string,
     * }
     */
    public function jsonSerialize(): array
    {
        $arr = [
            'content' => $this->content,
        ];

        if (isset($this->taskType)) {
            $arr['taskType'] = $this->taskType;
        }

        if (isset($this->title)) {
            $arr['title'] = $this->title;
        }

        return $arr;
    }

    public function __toString(): string
    {
        return json_encode($this) ?: '';
    }
}
