<?php declare(strict_types=1);

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use LucianoTonet\GroqPHP\GroqException;
use Psr\Http\Message\ResponseInterface;
use LucianoTonet\GroqPHP\Vision; // Adicionando a importação da classe Vision

/**
 * Class Groq
 * @package LucianoTonet\GroqPHP
 *
 * The Groq class serves as the main interface for interacting with the Groq API.
 * It manages API key, base URL, and provides methods to create various service instances.
 */
class Groq
{
    private string $apiKey; // API key for authentication
    public string $baseUrl; // Base URL for the API
    public array $options; // Additional options for configuration

    /**
     * Groq constructor.
     * Initializes the Groq instance with an API key and options.
     *
     * @param string|null $apiKey API key for authentication, defaults to environment variable
     * @param array $options Configuration options for the Groq instance
     * @throws GroqException if the API key is not set
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $apiKey = $apiKey ?? $_ENV['GROQ_API_KEY'];

        if (!$apiKey) {
            throw GroqException::apiKeyNotSet(); // Throw exception if API key is not provided
        }

        $this->apiKey = $apiKey; // Set the API key
        $this->options = $options; // Set the options
        $this->baseUrl = $options['baseUrl'] ?? $_ENV['GROQ_API_BASE'] ?? 'https://api.groq.com/openai/v1'; // Set base URL
    }

    /**
     * Sets additional options for the Groq instance.
     *
     * @param array $options Options to be merged with existing options
     *   - apiKey: (string) The API key for authentication
     *   - baseUrl: (string) The base URL for API requests (default: https://api.groq.com/openai/v1)
     *   - timeout: (int) Request timeout in milliseconds
     *   - maxRetries: (int) Maximum number of retry attempts for failed requests
     *   - headers: (array) Additional headers to include in requests
     *   - proxy: (string) Proxy configuration for requests
     *   - verify: (bool) Whether to verify SSL certificates
     *   - debug: (bool) Enable debug mode for detailed logging
     *   - stream: (bool) Enable response streaming
     *   - responseFormat: (string) Preferred response format (json|text)
     */
    public function setOptions(array $options): void
    {
        // Update API key if provided
        if (isset($options['apiKey'])) {
            $this->apiKey = $options['apiKey'];
        }
        // Update base URL if provided
        if (isset($options['baseUrl'])) {
            $this->baseUrl = $options['baseUrl'];
        }
        // Merge new options with existing ones
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Creates a new Chat instance.
     *
     * @return Chat A new instance of the Chat class
     */
    public function chat(): Chat
    {
        return new Chat($this); // Return a new Chat instance
    }

    /**
     * Creates a new Audio instance.
     *
     * @return Audio A new instance of the Audio class
     */
    public function audio(): Audio
    {
        return new Audio($this); // Return a new Audio instance
    }

    /**
     * Creates a new Models instance.
     *
     * @return Models A new instance of the Models class
     */
    public function models(): Models
    {
        return new Models($this); // Return a new Models instance
    }
    
    /**
     * Sends an HTTP request using the Guzzle client and returns the response.
     *
     * @param Request $request The HTTP request to be sent
     * @return ResponseInterface The response from the API
     */
    public function makeRequest(Request $request): ResponseInterface
    {
        $client = new Client(); // Create a new Guzzle client
        return $client->send($request); // Send the request and return the response
    }

    /**
     * Retrieves the base URL for the API.
     *
     * @return string The base URL
     */
    public function baseUrl(): string
    {
        return $this->baseUrl; // Return the base URL
    }

    /**
     * Retrieves the API key used for authentication.
     *
     * @return string The API key
     */
    public function apiKey(): string
    {
        return $this->apiKey; // Return the API key
    }

    /**
     * Creates a new Vision instance.
     *
     * @return Vision A new instance of the Vision class
     */
    public function vision(): Vision
    {
        return new Vision($this); // Return a new Vision instance
    }
}
