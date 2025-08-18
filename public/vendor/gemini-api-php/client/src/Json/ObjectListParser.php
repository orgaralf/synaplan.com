<?php

declare(strict_types=1);

namespace GeminiAPI\Json;

use RuntimeException;

class ObjectListParser
{
    private int $depth = 0;
    private bool $inString = false;
    private bool $inEscape = false;
    private string $json = '';

    /** @var callable(array): void */
    private $callback; // @phpstan-ignore-line

    /**
     * @phpstan-ignore-next-line
     * @param callable(array): void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param string $str
     * @return int
     * @throws RuntimeException
     */
    public function consume(string $str): int
    {
        $offset = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($this->inEscape) {
                $this->inEscape = false;
            } elseif ($this->inString) {
                if ($str[$i] === '\\') {
                    $this->inEscape = true;
                } elseif ($str[$i] === '"') {
                    $this->inString = false;
                }
            } elseif ($str[$i] === '"') {
                $this->inString = true;
            } elseif ($str[$i] === '{') {
                if ($this->depth === 0) {
                    $offset = $i;
                }
                $this->depth++;
            } elseif ($str[$i] === '}') {
                $this->depth--;
                if ($this->depth === 0) {
                    $this->json .= substr($str, $offset, $i - $offset + 1);
                    $arr = json_decode($this->json, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException('ObjectListParser could not decode the given message');
                    }

                    ($this->callback)($arr);
                    $this->json = '';
                    $offset = $i + 1;
                }
            }
        }

        $this->json .= substr($str, $offset) ?: '';

        return strlen($str);
    }
}
