<?php

namespace ArdaGnsrn\Ollama\Responses\Models;

use ArdaGnsrn\Ollama\Contracts\ResponseContract;

class ListModelsResponse implements ResponseContract
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
     * @return ListModelsResponse
     */
    public static function from(array $attributes): ListModelsResponse
    {
        return new self(
            models: array_map(
                fn(array $model) => ListModelsModelResponse::from($model),
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
                static fn(ListModelsModelResponse $model) => $model->toArray(),
                $this->models,
            ),
        ];
    }
}
