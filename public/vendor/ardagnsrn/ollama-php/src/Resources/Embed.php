<?php

namespace ArdaGnsrn\Ollama\Resources;

use ArdaGnsrn\Ollama\Contracts\EmbedContract;
use ArdaGnsrn\Ollama\OllamaClient;
use ArdaGnsrn\Ollama\Responses\Embed\EmbedResponse;

final class Embed implements EmbedContract
{
    /**
     * @var OllamaClient
     */
    private OllamaClient $ollamaClient;

    /**
     * @param OllamaClient $ollamaClient
     */
    public function __construct(OllamaClient $ollamaClient)
    {
        $this->ollamaClient = $ollamaClient;
    }

    /**
     * @param array $parameters
     * @return EmbedResponse
     */
    public function create(array $parameters): EmbedResponse
    {
        $response = $this->ollamaClient->post('embed', $parameters, false);
        return EmbedResponse::from($response);
    }
}
