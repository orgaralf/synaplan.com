<?php

namespace ArdaGnsrn\Ollama\Responses\Chat;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ChatResponse implements ResponseContract
{
    /**
     * @param string $model
     * @param string $createdAt
     * @param ChatMessageResponse $message
     * @param bool $done
     * @param int|null $totalDuration
     * @param int|null $loadDuration
     * @param int|null $promptEvalCount
     * @param int|null $promptEvalDuration
     * @param int|null $evalCount
     * @param int|null $evalDuration
     */
    private function __construct(
        public readonly string              $model,
        public readonly string              $createdAt,
        public readonly ChatMessageResponse $message,
        public readonly bool                $done,
        public readonly ?int                $totalDuration,
        public readonly ?int                $loadDuration,
        public readonly ?int                $promptEvalCount,
        public readonly ?int                $promptEvalDuration,
        public readonly ?int                $evalCount,
        public readonly ?int                $evalDuration,
    )
    {
    }

    /**
     * @param array $attributes
     * @return ChatResponse
     */
    public static function from(array $attributes): ChatResponse
    {
        return new self(
            model: $attributes['model'],
            createdAt: $attributes['created_at'],
            message: ChatMessageResponse::from($attributes['message']),
            done: $attributes['done'],
            totalDuration: $attributes['total_duration'] ?? null,
            loadDuration: $attributes['load_duration'] ?? null,
            promptEvalCount: $attributes['prompt_eval_count'] ?? null,
            promptEvalDuration: $attributes['prompt_eval_duration'] ?? null,
            evalCount: $attributes['eval_count'] ?? null,
            evalDuration: $attributes['eval_duration'] ?? null,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'created_at' => $this->createdAt,
            'message' => $this->message->toArray(),
            'done' => $this->done,
            'total_duration' => $this->totalDuration,
            'load_duration' => $this->loadDuration,
            'prompt_eval_count' => $this->promptEvalCount,
            'prompt_eval_duration' => $this->promptEvalDuration,
            'eval_count' => $this->evalCount,
            'eval_duration' => $this->evalDuration,
        ];
    }
}
