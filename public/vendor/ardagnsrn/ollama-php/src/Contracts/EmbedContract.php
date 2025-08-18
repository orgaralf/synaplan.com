<?php

namespace ArdaGnsrn\Ollama\Contracts;

use ArdaGnsrn\Ollama\Responses\Embed\EmbedResponse;

interface EmbedContract
{
    /**
     * @param array $parameters
     * @return EmbedResponse
     */
    public function create(array $parameters): EmbedResponse;
}
