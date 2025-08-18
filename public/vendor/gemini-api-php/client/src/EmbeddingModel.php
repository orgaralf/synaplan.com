<?php

declare(strict_types=1);

namespace GeminiAPI;

use GeminiAPI\Enums\ModelName;
use GeminiAPI\Enums\Role;
use GeminiAPI\Enums\TaskType;
use GeminiAPI\Requests\EmbedContentRequest;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\PartInterface;
use GeminiAPI\Responses\EmbedContentResponse;
use Psr\Http\Client\ClientExceptionInterface;

class EmbeddingModel
{
    private ?TaskType $taskType = null;

    public function __construct(
        private readonly Client $client,
        public readonly ModelName|string $modelName,
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function embedContent(PartInterface ...$parts): EmbedContentResponse
    {
        $request = new EmbedContentRequest(
            $this->modelName,
            new Content($parts, Role::User),
            $this->taskType,
        );

        return $this->client->embedContent($request);
    }

    /**
     * embedContentWithTitle will override the task type with TaskType::RETRIEVAL_DOCUMENT.
     * This is not a persistent change.
     *
     * @throws ClientExceptionInterface
     */
    public function embedContentWithTitle(string $title, PartInterface ...$parts): EmbedContentResponse
    {
        $request = new EmbedContentRequest(
            $this->modelName,
            new Content($parts, Role::User),
            TaskType::RETRIEVAL_DOCUMENT,
            $title,
        );

        return $this->client->embedContent($request);
    }

    public function withTaskType(TaskType $taskType): self
    {
        $clone = clone $this;
        $clone->taskType = $taskType;

        return $clone;
    }
}
