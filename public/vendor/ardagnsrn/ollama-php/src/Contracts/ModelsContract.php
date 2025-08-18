<?php

namespace ArdaGnsrn\Ollama\Contracts;

use ArdaGnsrn\Ollama\Responses\Models\CreateModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\ListModelsResponse;
use ArdaGnsrn\Ollama\Responses\Models\ListRunningModelsResponse;
use ArdaGnsrn\Ollama\Responses\Models\PullModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\PushModelResponse;
use ArdaGnsrn\Ollama\Responses\Models\ShowModelResponse;
use ArdaGnsrn\Ollama\Responses\StreamResponse;

interface ModelsContract
{
    /**
     * @return ListModelsResponse
     */
    public function list(): ListModelsResponse;

    /**
     * @param string $modelName
     * @param bool $verbose
     * @return ShowModelResponse
     */
    public function show(string $modelName, bool $verbose = false): ShowModelResponse;

    /**
     * @param array $parameters
     * @return CreateModelResponse
     */
    public function create(array $parameters): CreateModelResponse;

    /**
     * @param array $parameters
     * @return StreamResponse
     */
    public function createStreamed(array $parameters): StreamResponse;

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copy(string $source, string $destination): bool;

    /**
     * @param $modelName
     * @return bool
     */
    public function delete($modelName): bool;

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return PullModelResponse
     */
    public function pull(string $modelName, bool $insecure = false): PullModelResponse;

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return StreamResponse
     */
    public function pullStreamed(string $modelName, bool $insecure = false): StreamResponse;

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return PushModelResponse
     */
    public function push(string $modelName, bool $insecure = false): PushModelResponse;

    /**
     * @param string $modelName
     * @param bool $insecure
     * @return StreamResponse
     */
    public function pushStreamed(string $modelName, bool $insecure = false): StreamResponse;

    /**
     * @return ListRunningModelsResponse
     */
    public function runningList(): ListRunningModelsResponse;
}
