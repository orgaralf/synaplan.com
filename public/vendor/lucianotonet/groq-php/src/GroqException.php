<?php

namespace LucianoTonet\GroqPHP;

use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Class GroqException
 * This class represents exceptions that occur during interactions with the API.
 * It provides detailed information about the error, including type, headers, and response body.
 */
class GroqException extends \Exception
{
    // Define various error types that may occur
    const ERROR_TYPES = [
        'invalid_request' => 'Invalid Request',
        'api_error' => 'API Error',
        'authentication_error' => 'Authentication Error',
        'rate_limit_error' => 'Rate Limit Error',
        'chat_completion_error' => 'Chat Completion Error',
        'transcription_error' => 'Transcription Error',
        'not_found_error' => 'Not Found Error',
        'unprocessable_entity' => 'Unprocessable Entity',
        'timeout_error' => 'Timeout Error',
        'service_unavailable' => 'Service Unavailable',
        'invalid_request_error' => 'Invalid Request Error',
        'invalid_api_key' => 'Invalid API Key',
        'network_error' => 'Network Error',
        'failed_generation' => 'Failed Generation', // Added new error type
    ];

    // Map error types to HTTP status codes
    const HTTP_STATUS_CODES = [
        self::ERROR_TYPES['invalid_request'] => 400,
        self::ERROR_TYPES['api_error'] => 500,
        self::ERROR_TYPES['authentication_error'] => 401,
        self::ERROR_TYPES['rate_limit_error'] => 429,
        self::ERROR_TYPES['not_found_error'] => 404,
        self::ERROR_TYPES['unprocessable_entity'] => 422,
        self::ERROR_TYPES['timeout_error'] => 504,
        self::ERROR_TYPES['service_unavailable'] => 503,
        self::ERROR_TYPES['invalid_request_error'] => 400,
        self::ERROR_TYPES['invalid_api_key'] => 0, // Mapped to status code 0
        self::ERROR_TYPES['network_error'] => 503,
        self::ERROR_TYPES['failed_generation'] => 400, // Mapped to status code 400
    ];

    protected string $type; // Type of the error
    protected array $headers; // Response headers
    protected ?stdClass $responseBody; // Response body
    protected ?string $failedGeneration; // Field for failed_generation

    /**
     * Constructor for GroqException.
     * @param string $message Error message
     * @param int $code Error code
     * @param string $type Type of the error
     * @param string[] $headers Response headers
     * @param string|null $failedGeneration Field for failed_generation
     * @param stdClass|null $responseBody Response body
     */
    public function __construct(string $message, int $code, string $type, array $headers = [], ?stdClass $responseBody = null, ?string $failedGeneration = null)
    {
        parent::__construct($message, $code);
        $this->type = $type;
        $this->headers = $headers;
        $this->responseBody = $responseBody;
        $this->failedGeneration = $failedGeneration; // Assigning the failed_generation field
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getResponseBody(): ?stdClass
    {
        return $this->responseBody;
    }

    public function getFailedGeneration(): ?string
    {
        return $this->failedGeneration ?? null; // Method to return the failed_generation field
    }

    /**
     * Converts the exception details to JSON format.
     * @return string
     */
    public function toJson(): string
    {
        $errorDetails = [
            'message' => $this->getMessage(),
            'type' => $this->getType(),
            'code' => $this->getCode(),
        ];

        if ($this->getFailedGeneration() !== null) {
            $errorDetails['failed_generation'] = $this->getFailedGeneration(); // Using the new getFailedGeneration method
        }

        return json_encode([
            'error' => $errorDetails,
            'headers' => $this->getHeaders(),
            'responseBody' => $this->responseBody ? json_decode(json_encode($this->responseBody), true) : null,
        ]);
    }

    public function toArray(): array
    {
        return json_decode($this->toJson(), true);
    }

    public function getError(): array
    {
        $errorDetails = [
            'message' => $this->getMessage(),
            'type' => $this->getType(),
            'code' => $this->getCode(),
        ];

        if ($this->getFailedGeneration() !== null) {
            $errorDetails['failed_generation'] = $this->getFailedGeneration(); // Using the new getFailedGeneration method
        }

        return $errorDetails;
    }

    /**
     * Creates a GroqException from a response.
     * @param ResponseInterface $response
     * @return self
     * @throws self
     */
    public static function createFromResponse(ResponseInterface $response): self
    {
        $body = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new self('Invalid JSON response', 500, self::ERROR_TYPES['api_error'], $response->getHeaders());
        }

        $errorMessage = $body['error']['message'] ?? 'Unknown error';
        $errorType = $body['error']['type'] ?? self::ERROR_TYPES['api_error'];
        $errorCode = $response->getStatusCode();
        $failedGeneration = $body['error']['failed_generation'] ?? null; // Captura o campo failed_generation, se disponível

        // Handle specific error types using the factory pattern
        return match ($errorType) {
            'invalid_request_error' => self::invalidRequest($errorMessage),
            'invalid_api_key' => self::authenticationError('Invalid API key provided.'),
            self::ERROR_TYPES['invalid_request'] => self::invalidRequest($errorMessage),
            self::ERROR_TYPES['authentication_error'] => self::authenticationError($errorMessage),
            self::ERROR_TYPES['not_found_error'] => self::notFoundError($errorMessage),
            self::ERROR_TYPES['unprocessable_entity'] => self::unprocessableEntity($errorMessage),
            self::ERROR_TYPES['rate_limit_error'] => self::rateLimitError($errorMessage),
            self::ERROR_TYPES['timeout_error'] => new self($errorMessage, 504, $errorType, $response->getHeaders()),
            self::ERROR_TYPES['service_unavailable'] => new self($errorMessage, 503, $errorType, $response->getHeaders()),
            self::ERROR_TYPES['network_error'] => new self('A network error occurred', 503, self::ERROR_TYPES['network_error'], $response->getHeaders()),
            self::ERROR_TYPES['failed_generation'] => new self($errorMessage, 400, self::ERROR_TYPES['failed_generation'], $response->getHeaders(), $failedGeneration), // Handling for failed_generation
            default => new self($errorMessage, $errorCode, $errorType, $response->getHeaders(), json_decode(json_encode($body))),
        };
    }

    public static function invalidRequest(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['invalid_request'], self::ERROR_TYPES['invalid_request']);
    }

    public static function authenticationError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['authentication_error'], self::ERROR_TYPES['authentication_error']);
    }

    public static function notFoundError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['not_found_error'], self::ERROR_TYPES['not_found_error']);
    }

    public static function unprocessableEntity(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['unprocessable_entity'], self::ERROR_TYPES['unprocessable_entity']);
    }

    public static function rateLimitError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['rate_limit_error'], self::ERROR_TYPES['rate_limit_error']);
    }

    public static function internalServerError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['api_error'], self::ERROR_TYPES['api_error']);
    }

    public static function apiKeyNotSet(): self
    {
        return new self(
            'The API key is not set. Please provide an API key when initializing the Groq client or set the environment variable GROQ_API_KEY.',
            400,
            self::ERROR_TYPES['authentication_error']
        );
    }
}