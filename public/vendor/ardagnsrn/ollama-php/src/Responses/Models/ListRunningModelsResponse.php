<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ListRunningModelsResponse implements ResponseContract
{
    /**
     * @param array $models
     */
    private function __construct(
        public readonly array $models,
    )
    {
    }

    /**
     * @param array $attributes
     * @return ListRunningModelsResponse
     */
    public static function from(array $attributes): ListRunningModelsResponse
    {
        return new self(
            models: array_map(
                fn(array $model) => ListRunningModelsModelResponse::from($model),
                $attributes['models'],
            ),
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'models' => array_map(
                static fn(ListRunningModelsModelResponse $model) => $model->toArray(),
                $this->models,
            ),
        ];
    }
}
