<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Class Completions
 * Handles the creation of completions using the Groq API.
 */
class Completions
{
    private Groq $groq;

    /**
     * Completions constructor.
     * Initializes the Completions instance with a Groq object.
     *
     * @param Groq $groq The Groq instance for API interactions.
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Creates a completion based on the provided parameters.
     *
     * @param array $params Parameters for creating the completion.
     * @return array|Stream The response from the API or a stream.
     * @throws GroqException If an error occurs during the request.
     */
    public function create(array $params = []): array|Stream
    {
        $this->validateParams($params);
        $this->prepareParams($params);

        $request = $this->createRequest($params);

        try {
            return isset($params['stream']) && $params['stream'] === true
                ? $this->streamResponse($request)
                : $this->handleResponse($this->groq->makeRequest($request));
        } catch (RequestException $e) {
            throw $this->createGroqExceptionFromRequestException($e);
        } catch (GuzzleException $e) {
            throw new GroqException('Unexpected error while creating the completion: ' . $e->getMessage(), $e->getCode(), 'api_error');
        } catch (\Exception $e) {
            throw new GroqException('Unexpected error: ' . $e->getMessage(), $e->getCode(), 'unknown_error');
        }
    }

    /**
     * Validates the required parameters for the completion request.
     *
     * @param array $params The parameters to validate.
     * @throws GroqException If required parameters are missing.
     */
    private function validateParams(array $params): void
    {
        if (empty($params['model'])) {
            throw new GroqException('Missing required parameter: model', 400, 'invalid_request');
        }
        if (empty($params['messages'])) {
            throw new GroqException('Missing required parameter: messages', 400, 'invalid_request');
        }
    }

    /**
     * Prepares the parameters for the API request.
     *
     * @param array $params The parameters to prepare.
     */
    private function prepareParams(array &$params): void
    {
        // Remove unnecessary parameters
        if (isset($params['response_format'], $params['tools'])) {
            unset($params['response_format']);
        }
        $this->processImageContent($params['messages']);
    }

    /**
     * Processes image content within the messages.
     *
     * @param array $messages The messages containing image content.
     */
    private function processImageContent(array &$messages): void
    {
        foreach ($messages as &$message) {
            if (isset($message['content']) && is_array($message['content'])) {
                foreach ($message['content'] as &$content) {
                    // Verifica se $content['image_url'] é um array e tem a chave 'url'
                    if (isset($content['type']) && $content['type'] === 'image_url' &&
                        isset($content['image_url']) && is_array($content['image_url']) &&
                        isset($content['image_url']['url'])) {
                        $this->processImageUrl($content['image_url']['url']);
                    }
                }
            }
        }
    }

    /**
     * Processes the image URL and converts it to base64 if necessary.
     *
     * @param string &$url The image URL to process.
     */
    private function processImageUrl(string &$url): void
    {
        if (strpos($url, 'data:image/') === 0) {
            return; // Already in base64 format
        }
        if (file_exists($url)) {
            $imageData = base64_encode(file_get_contents($url));
            $url = 'data:image/jpeg;base64,' . $imageData;
        }
    }

    /**
     * Creates a request object for the API.
     *
     * @param array $params The parameters for the request.
     * @return Request The created request object.
     */
    private function createRequest(array $params): Request
    {
        $body = json_encode(array_filter([
            'model' => $params['model'],
            'messages' => $params['messages'],
            'stream' => $params['stream'] ?? false,
            'response_format' => $params['response_format'] ?? null,
            'tools' => $params['tools'] ?? null,
            'tool_choice' => $params['tool_choice'] ?? null,
            'max_tokens' => $params['max_tokens'] ?? null,
            'temperature' => $params['temperature'] ?? null,
            'top_p' => $params['top_p'] ?? null,
            'stop' => $params['stop'] ?? null,
            'seed' => $params['seed'] ?? null,
            'parallel_tool_calls' => $params['parallel_tool_calls'] ?? null,
            'frequency_penalty' => $params['frequency_penalty'] ?? 0, // Penaliza a repetição de tokens
            'presence_penalty' => $params['presence_penalty'] ?? 0, // Penaliza a repetição de tópicos
            'n' => $params['n'] ?? 1, // Número de escolhas de completions
            'logprobs' => $params['logprobs'] ?? false, // Retorna log probabilities
            'function_call' => $params['function_call'] ?? null, // Controla a chamada de funções
            'logit_bias' => $params['logit_bias'] ?? null, // Modifica a probabilidade de tokens
            'top_logprobs' => $params['top_logprobs'] ?? null, // Retorna os tokens mais prováveis
        ], function ($value) {
            return $value !== null;
        }));

        return new Request(
            'POST',
            $this->groq->baseUrl() . '/chat/completions',
            [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $this->groq->apiKey(),
            ],
            $body
        );
    }

    /**
     * Handles the API response and converts it to an array.
     *
     * @param ResponseInterface $response The response from the API.
     * @return array The decoded response data.
     */
    private function handleResponse(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Creates a GroqException from a RequestException.
     *
     * @param RequestException $e The original request exception.
     * @return GroqException The created GroqException.
     */
    private function createGroqExceptionFromRequestException(RequestException $e): GroqException
    {
        $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'Response body not available';
        $errorData = json_decode($responseBody);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($errorData->error)) {
            return new GroqException(
                $errorData->error->message ?? 'Unknown error',
                (int) ($errorData->error->code ?? 0),
                $errorData->error->type ?? 'api_error'
            );
        }

        return new GroqException('Unknown error', 0, 'unknown_error');
    }

    /**
     * Streams the response from the API.
     *
     * @param Request $request The HTTP request to send.
     * @return Stream The streamed response.
     * @throws GroqException If an error occurs during streaming.
     */
    private function streamResponse(Request $request): Stream
    {
        try {
            $client = new Client();
            $response = $client->send($request, ['stream' => true]);
            return new Stream($response);
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'Response body not available';
            throw new GroqException('Failed to stream the response: ' . $responseBody, $e->getCode(), 'stream_error');
        } catch (GuzzleException $e) {
            throw new GroqException('Unexpected error while trying to stream the response: ' . $e->getMessage(), $e->getCode(), 'api_error');
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'generic_error');
        }
    }
}