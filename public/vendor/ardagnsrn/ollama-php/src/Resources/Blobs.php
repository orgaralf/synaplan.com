<?php

namespace ArdaGnsrn\Ollama\Resources;

use ArdaGnsrn\Ollama\Contracts\BlobsContract;
use ArdaGnsrn\Ollama\OllamaClient;
use Exception;

final class Blobs implements BlobsContract
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
     * @param string $digest
     * @return bool
     */
    public function exists(string $digest): bool
    {
        try {
            $response = $this->ollamaClient->get("blobs/$digest", parseJson: false);
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $digest
     * @return bool
     */
    public function create(string $digest): bool
    {
        $response = $this->ollamaClient->post("blobs/$digest", parseJson: false);
        return in_array($response->getStatusCode(), [200, 201]);
    }
}
