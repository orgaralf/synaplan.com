<?php

namespace LucianoTonet\GroqPHP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 * This class is responsible for handling the streaming of HTTP responses.
 * It allows for processing the response in chunks, reading lines from the stream,
 * and retrieving headers from the response.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Stream
{
    private ResponseInterface $response;

    /**
     * Stream constructor.
     * @param ResponseInterface $response The HTTP response to be processed.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = clone $response;
    }

    /**
     * This method returns a generator that yields chunks of data from the response.
     * It processes the response line by line and yields the parsed JSON data.
     * 
     * @return \Generator Yields parsed JSON data from the response.
     * @throws GroqException If an error occurs during processing.
     */
    public function chunks(): \Generator
    {
        if (!$this->response instanceof ResponseInterface) {
            throw new \InvalidArgumentException('Invalid response provided');
        }

        if ($this->response->getStatusCode() >= 400) {
            throw new GroqException('Error response received', $this->response->getStatusCode(), 'ResponseError');
        }

        $body = $this->response->getBody();

        try {
            while (!$body->eof()) {
                $line = $this->readLine($body);

                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, strlen('data:')));

                if ($data === '[DONE]') {
                    break;
                }

                $response = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

                if (isset($response['error'])) {
                    throw new GroqException($response['error'], 0, 'ResponseError');
                }

                yield $response;
            }
        } catch (\Throwable $e) {
            throw new GroqException('Error processing chunks: ' . $e->getMessage(), $e->getCode(), 'ChunksProcessingException');
        } finally {
            $body->close();
        }
    }

    /**
     * Reads a line from the given stream.
     * 
     * @param StreamInterface $stream The stream to read from.
     * @return string The line read from the stream.
     */
    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            $byte = $stream->read(1);

            if ($byte === '') {
                return $buffer;
            }

            $buffer .= $byte;

            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }

    /**
     * Retrieves a specific header from the response.
     * 
     * @param string $name The name of the header to retrieve.
     * @return string The value of the specified header.
     */
    public function getHeader(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * Retrieves all headers from the response.
     * 
     * @return array An associative array of all headers.
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }
}