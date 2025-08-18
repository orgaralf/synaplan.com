<?php

namespace ArdaGnsrn\Ollama\Contracts;

use ArdaGnsrn\Ollama\Responses\Chat\ChatResponse;
use ArdaGnsrn\Ollama\Responses\StreamResponse;

interface ChatContract
{

    /**
     * @param array $parameters
     * @return ChatResponse
     */
    public function create(array $parameters): ChatResponse;

    /**
     * @param array $parameters
     * @return StreamResponse
     */
    public function createStreamed(array $parameters): StreamResponse;
}
