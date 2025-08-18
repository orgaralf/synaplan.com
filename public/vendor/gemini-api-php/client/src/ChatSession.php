<?php

declare(strict_types=1);

namespace GeminiAPI;

use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\Parts\PartInterface;
use GeminiAPI\Responses\GenerateContentResponse;
use GeminiAPI\Traits\ArrayTypeValidator;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;

class ChatSession
{
    use ArrayTypeValidator;

    /** @var Content[] */
    private array $history;

    public function __construct(
        private readonly GenerativeModel $model,
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function sendMessage(PartInterface ...$parts): GenerateContentResponse
    {
        $this->history[] = new Content($parts, Role::User);

        $config = (new GenerationConfig())
        ->withCandidateCount(1);
        $response = $this->model
            ->withGenerationConfig($config)
            ->generateContentWithContents($this->history);

        if (!empty($response->candidates)) {
            $parts = $response->candidates[0]->content->parts;
            $this->history[] = new Content($parts, Role::Model);
        }

        return $response;
    }

    /**
     * @param callable(GenerateContentResponse): void $callback
     * @param PartInterface ...$parts
     * @return void
     */
    public function sendMessageStream(
        callable $callback,
        PartInterface ...$parts,
    ): void {
        $this->history[] = new Content($parts, Role::User);

        $parts = [];
        $partsCollectorCallback = function (GenerateContentResponse $response) use ($callback, &$parts) {
            if (!empty($response->candidates)) {
                array_push($parts, ...$response->parts());
            }

            $callback($response);
        };

        $config = (new GenerationConfig())
            ->withCandidateCount(1);
        $this->model
            ->withGenerationConfig($config)
            ->generateContentStreamWithContents($partsCollectorCallback, $this->history);

        if (!empty($parts)) {
            $this->history[] = new Content($parts, Role::Model);
        }
    }

    /**
     * @return Content[]
     */
    public function history(): array
    {
        return $this->history;
    }

    /**
     * @param Content[] $history
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withHistory(array $history): self
    {
        $this->ensureArrayOfType($history, Content::class);

        $clone = clone $this;
        $clone->history = $history;

        return $clone;
    }
}
