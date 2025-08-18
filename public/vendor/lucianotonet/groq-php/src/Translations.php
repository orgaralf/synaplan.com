<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use LucianoTonet\GroqPHP\Stream;

/**
 * Class Translations
 * This class handles the translation of spoken words in audio or video files 
 * to the specified language.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Translations
{
    private Groq $groq;

    /**
     * Translations constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Translation Usage
     * This method translates spoken words in audio or video files to the specified language.
     *
     * Optional Parameters:
     * - prompt: Provides context or specifies the spelling of unknown words.
     * - response_format: Defines the format of the response. The default is "json".
     *   Use "verbose_json" to receive timestamps for audio segments.
     *   Use "text" to return a text response.
     *   vtt and srt formats are not supported.
     * - temperature: Specifies a value between 0 and 1 to control the variability of the translation output.
     *
     * @param array $params
     * @return array|string|Stream
     * @throws \InvalidArgumentException
     */
    public function create(array $params): array|string|Stream
    {
        $this->validateParams($params);
        $client = new Client();
        $multipart = $this->buildMultipart($params);

        try {
            $response = $client->request('POST', $this->groq->baseUrl() . '/audio/translations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groq->apiKey()
                ],
                'multipart' => $multipart
            ]);

            return $this->handleResponse($response, $params['response_format'] ?? 'json');
        } catch (GuzzleException $e) {
            if ($e instanceof RequestException && $e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody();
                $responseBodyString = $responseBody ? (string) $responseBody : 'Response body is empty or null';
                if (trim($responseBodyString) === '') {
                    $responseBodyString = 'Response body is empty or null';
                }
                throw GroqException::createFromResponse($e->getResponse());
            } else {
                throw new GroqException('Unexpected error while creating translations: ' . $e->getMessage(), $e->getCode(), 'UnexpectedException', []);
            }
        }
    }

    /**
     * Validates the input parameters.
     *
     * @param array $params
     * @throws \InvalidArgumentException
     */
    private function validateParams(array $params): void
    {
        if (empty($params['file'])) {
            throw new \InvalidArgumentException('The "file" parameter is required.');
        }
        if (!file_exists($params['file'])) {
            throw new \InvalidArgumentException('The specified file does not exist.');
        }
    }

    /**
     * Builds the multipart structure for the request.
     *
     * @param array $params
     * @return array
     */
    private function buildMultipart(array $params): array
    {
        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($params['file'], 'r')
            ],
            [
                'name' => 'model',
                'contents' => $params['model'] ?? 'whisper-large-v3'
            ],
            [
                'name' => 'temperature',
                'contents' => $params['temperature'] ?? 0.0
            ],
        ];

        if (!empty($params['prompt'])) {
            $multipart[] = [
                'name' => 'prompt',
                'contents' => $params['prompt']
            ];
        }

        if (!empty($params['response_format'])) {
            $multipart[] = [
                'name' => 'response_format',
                'contents' => $params['response_format']
            ];
        }

        return $multipart;
    }

    /**
     * Handles the response of the request.
     *
     * @param ResponseInterface $response
     * @param string $responseFormat
     * @return array|string|Stream
     */
    private function handleResponse(ResponseInterface $response, string $responseFormat): array|string|Stream
    {
        $body = $response->getBody()->getContents();

        if ($responseFormat === 'text') {
            return $body; // Returns the body of the response directly
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GroqException('Error decoding the JSON response: ' . json_last_error_msg(), 0, 'JsonDecodeError');
        }

        return $data;
    }

    /**
     * Streams the response from the request.
     *
     * @param Request $request
     * @param array $options
     * @return Stream
     */
    private function streamResponse(Request $request, array $options): Stream
    {
        try {
            $client = new Client();
            $response = $client->send($request, array_merge($options, ['stream' => true]));
            return new Stream($response);
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody() : null;
            $responseBodyString = $responseBody ? (string) $responseBody : 'No response body available';
            throw new GroqException('Failed to stream the response: ' . $responseBodyString, $e->getCode(), 'RequestException', []);
        } catch (GuzzleException $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'UnexpectedException', []);
        }
    }
}
