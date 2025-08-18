<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use LucianoTonet\GroqPHP\Stream;

/**
 * Class Transcriptions
 * This class handles audio transcriptions, allowing the conversion of spoken words 
 * in audio or video files into text.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Transcriptions
{
    private Groq $groq;

    /**
     * Transcriptions constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Audio Transcription
     * This method transcribes spoken words in audio or video files.
     *
     * Optional Parameters:
     * - prompt: Provides context or specifies the spelling of unknown words.
     * - response_format: Defines the format of the response. The default is "json".
     *   Use "verbose_json" to receive timestamps for audio segments.
     *   Use "text" to return a plain text response.
     *   vtt and srt formats are not supported.
     * - temperature: Specifies a value between 0 and 1 to control the variability of the transcription.
     * - language: Specifies the language for the transcription (optional; Whisper will automatically detect if not specified).
     *   Use ISO 639-1 language codes (e.g., "en" for English, "fr" for French, etc.).
     *   Specifying a language can improve the accuracy and speed of the transcription.
     * - timestamp_granularities[] is not supported.
     *
     * @param array $params
     * @return array|string|Stream
     */
    public function create(array $params): array|string|Stream
    {
        $this->validateParams($params); // Validate parameters
        $client = new Client();
        $multipart = $this->buildMultipart($params);

        try {
            $response = $client->request('POST', $this->groq->baseUrl() . '/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groq->apiKey()
                ],
                'multipart' => $multipart
            ]);

            return $this->handleResponse($response, $params['response_format'] ?? 'json');
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $responseBody = $response && $response->getBody() ? (string) $response->getBody() : 'No response body available';
            throw new GroqException('Error transcribing audio: ' . $responseBody, $e->getCode(), 'RequestException');
        } catch (GuzzleException $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'GuzzleException');
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'Exception');
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

        if (isset($params['temperature']) && ($params['temperature'] < 0 || $params['temperature'] > 1)) {
            throw new \InvalidArgumentException('The "temperature" parameter must be between 0 and 1.');
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
        ];

        if (isset($params['temperature'])) {
            $multipart[] = [
                'name' => 'temperature',
                'contents' => $params['temperature']
            ];
        }

        if (isset($params['language'])) {
            $multipart[] = [
                'name' => 'language',
                'contents' => $params['language']
            ];
        }

        if (isset($params['prompt'])) {
            $multipart[] = [
                'name' => 'prompt',
                'contents' => $params['prompt']
            ];
        }

        if (isset($params['response_format'])) {
            $multipart[] = [
                'name' => 'response_format',
                'contents' => $params['response_format']
            ];
        }

        return $multipart;
    }

    /**
     * Handles the response from the request.
     *
     * @param ResponseInterface $response
     * @param string $responseFormat
     * @return array|string|Stream
     */
    private function handleResponse(ResponseInterface $response, string $responseFormat): array|string|Stream
    {
        $body = $response->getBody()->getContents();

        if ($responseFormat === 'text') {
            return $body; // Return the body of the response directly
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
            $responseBody = $e->getResponse() ? ($e->getResponse()->getBody() ? (string) $e->getResponse()->getBody() : 'Response body is empty') : 'No response body available';
            throw new GroqException('Failed to stream the response: ' . $responseBody, $e->getCode(), 'RequestException');
        } catch (GuzzleException $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'GuzzleException');
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'Exception');
        }
    }
}