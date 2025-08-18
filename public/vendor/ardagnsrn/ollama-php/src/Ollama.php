<?php

namespace ArdaGnsrn\Ollama;

final class Ollama
{
    /**
     * @var OllamaClient
     */
    private OllamaClient $ollamaClient;

    /**
     * @param string $host
     * @return Ollama
     */
    public static function client(string $host = 'http://localhost:11434'): Ollama
    {
        return new self($host);
    }

    /**
     * @param string $host
     */
    private function __construct(string $host)
    {
        $this->ollamaClient = new OllamaClient($host);
    }

    /**
     * @return Resources\Completions
     */
    public function completions(): Resources\Completions
    {
        return new Resources\Completions($this->ollamaClient);
    }

    /**
     * @return Resources\Chat
     */
    public function chat(): Resources\Chat
    {
        return new Resources\Chat($this->ollamaClient);
    }

    /**
     * @return Resources\Models
     */
    public function models(): Resources\Models
    {
        return new Resources\Models($this->ollamaClient);
    }

    /**
     * @return Resources\Blobs
     */
    public function blobs(): Resources\Blobs
    {
        return new Resources\Blobs($this->ollamaClient);
    }

    /**
     * @return Resources\Embed
     */
    public function embed(): Resources\Embed
    {
        return new Resources\Embed($this->ollamaClient);
    }

    public function isRunning(): bool
    {
        return $this->ollamaClient->isRunning();
    }
}
