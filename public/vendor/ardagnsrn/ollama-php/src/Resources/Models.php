<?php

namespace ArdaGnsrn\Ollama\Resources;

use ArdaGnsrn\Ollama\Contracts\ModelsContract;
use ArdaGnsrn\Ollama\OllamaClient;
use ArdaGnsrn\Ollama\Responses\Models\CreateModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\ListModelsResponse;
use ArdaGnsrn\Ollama\Responses\Models\ListRunningModelsResponse;
use ArdaGnsrn\Ollama\Responses\Models\PullModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\PushModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\ShowModelResponse;
use ArdaGnsrn\Ollama\Responses\StreamResponse;
use Exception;

final class Models implements ModelsContract
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
     * @return ListModelsResponse
     */
    public function list(): ListModelsResponse
    {
        $response = $this->ollamaClient->get('tags');
        return ListModelsResponse::from($response);
    }

    /**
     * @param string $modelName
     * @param bool $verbose
     * @return ShowModelResponse
     */
    public function show(string $modelName, bool $verbose = false): ShowModelResponse
    {
        $response = $this->ollamaClient->post('show', [
            'name' => $modelName,
            'verbose' => $verbose,
        ]);
        return ShowModelResponse::from($response);
    }

    /**
     * @param array $parameters
     * @return CreateModelResponse
     */
    public function create(array $parameters): CreateModelResponse
    {
        $response = $this->ollamaClient->post('create', $parameters, false);
        return CreateModelResponse::from($response);
    }

    /**
     * @param array $parameters
     * @return StreamResponse
     */
    public function createStreamed(array $parameters): StreamResponse
    {
        $response = $this->ollamaClient->post('create', $parameters, true);
        return new StreamResponse(CreateModelResponse::class, $response);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copy(string $source, string $destination): bool
    {
        try {
            $response = $this->ollamaClient->post('copy', [
                'source' => $source,
                'destination' => $destination,
            ], parseJson: false);

            return in_array($response->getStatusCode(), [200, 201]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $modelName
     * @return bool
     */
    public function delete($modelName): bool
    {
        try {
            $response = $this->ollamaClient->delete("delete", [
                'name' => $modelName,
            ]);
            return in_array($response->getStatusCode(), [200, 204]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return PullModelResponse
     */
    public function pull(string $modelName, bool $insecure = false): PullModelResponse
    {
        $response = $this->ollamaClient->post('pull', [
            'name' => $modelName,
            'insecure' => $insecure,
        ]);
        return PullModelResponse::from($response);
    }

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return StreamResponse
     */
    public function pullStreamed(string $modelName, bool $insecure = false): StreamResponse
    {
        $response = $this->ollamaClient->post('pull', [
            'name' => $modelName,
            'insecure' => $insecure,
        ], true);
        return new StreamResponse(PullModelResponse::class, $response);
    }

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return PushModelResponse
     */
    public function push(string $modelName, bool $insecure = false): PushModelResponse
    {
        $response = $this->ollamaClient->post('push', [
            'name' => $modelName,
            'insecure' => $insecure,
        ]);
        return PushModelResponse::from($response);
    }

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return StreamResponse
     */
    public function pushStreamed(string $modelName, bool $insecure = false): StreamResponse
    {
        $response = $this->ollamaClient->post('push', [
            'name' => $modelName,
            'insecure' => $insecure,
        ], true);
        return new StreamResponse(PushModelResponse::class, $response);
    }

    /**
     * @return ListRunningModelsResponse
     */
    public function runningList(): ListRunningModelsResponse
    {
        $response = $this->ollamaClient->get('ps');
        return ListRunningModelsResponse::from($response);
    }
}
