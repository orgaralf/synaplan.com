<?php

declare(strict_types=1);

namespace GeminiAPI;

use CurlHandle;
use GeminiAPI\Enums\ModelName;
use GeminiAPI\Requests\CountTokensRequest;
use GeminiAPI\Requests\EmbedContentRequest;
use GeminiAPI\Requests\GenerateContentRequest;
use GeminiAPI\Requests\GenerateContentStreamRequest;
use GeminiAPI\Responses\CountTokensResponse;
use GeminiAPI\Responses\EmbedContentResponse;
use GeminiAPI\Responses\GenerateContentResponse;
use GeminiAPI\Responses\ListModelsResponse;

/**
 * @since v1.1.0
 */
interface ClientInterface
{
    public const API_KEY_HEADER_NAME = 'x-goog-api-key';
    public const API_VERSION_V1 = 'v1';
    public const API_VERSION_V1_BETA = 'v1beta';

    public function countTokens(CountTokensRequest $request): CountTokensResponse;
    public function generateContent(GenerateContentRequest $request): GenerateContentResponse;
    public function embedContent(EmbedContentRequest $request): EmbedContentResponse;
    public function generativeModel(ModelName|string $modelName): GenerativeModel;
    public function embeddingModel(ModelName|string $modelName): EmbeddingModel;
    public function listModels(): ListModelsResponse;
    public function withBaseUrl(string $baseUrl): self;

    /**
     * @param GenerateContentStreamRequest $request
     * @param callable(GenerateContentResponse): void $callback
     * @param CurlHandle|null $curl
     * @return void
     */
    public function generateContentStream(
        GenerateContentStreamRequest $request,
        callable $callback,
        ?CurlHandle $curl = null,
    ): void;
}
