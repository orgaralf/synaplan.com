<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

/**
 * Base exception class for all Whisper-related errors
 */
class WhisperException extends \Exception
{
    protected function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function invalidUtf8(int $validUpTo, ?int $errorLen = null): self
    {
        $message = "Invalid UTF-8 detected in a string from Whisper. Index: {$validUpTo}";
        if ($errorLen !== null) {
            $message .= ", Length: {$errorLen}";
        }

        return new self($message);
    }

    public static function invalidLanguage(string $language): self
    {
        return new self("Unsupported or invalid language: {$language}");
    }

    public static function nullByteInString(int $idx): self
    {
        return new self("A null byte was detected in a user-provided string. Index: {$idx}");
    }

    public static function failedToCreateContext(): self
    {
        return new self('Failed to create a new whisper context.');
    }

    public static function failedToCreateState(): self
    {
        return new self('Creating a state pointer failed.');
    }

    public static function invalidMelBands(): self
    {
        return new self('Invalid number of mel bands.');
    }

    public static function invalidThreadCount(): self
    {
        return new self('Invalid thread count.');
    }

    public static function invalidText(): self
    {
        return new self('Whisper failed to convert the provided text into tokens.');
    }

    public static function noSamples(): self
    {
        return new self('Input sample buffer was empty.');
    }

    public static function inputOutputLengthMismatch(int $inputLen, int $outputLen): self
    {
        return new self(
            "Input and output slices were not the same length. Input: {$inputLen}, Output: {$outputLen}"
        );
    }

    public static function halfSampleMissing(int $size): self
    {
        return new self(
            "Input slice was not an even number of samples, got {$size}, expected ".($size + 1)
        );
    }

    public static function failedToCalculateSpectrogram(): self
    {
        return new self('Failed to calculate the spectrogram for some reason.');
    }

    public static function failedToCalculateEvaluation(): self
    {
        return new self('Failed to evaluate model.');
    }

    public static function failedToEncode(): self
    {
        return new self('Failed to run the encoder.');
    }

    public static function failedToDecode(): self
    {
        return new self('Failed to run the decoder.');
    }

    public static function failedToAutoDetectLanguage(): self
    {
        return new self('Failed to auto detect language.');
    }

    public static function audioCtxLongerThanMax(int $audioLen, int $maxLen): self
    {
        return new self(
            "Audio Ctx longer than the maximum allowed length. Audio length: {$audioLen}, Max length: {$maxLen}"
        );
    }

    public static function spectrogramNotInitialized(): self
    {
        return new self("User didn't initialize spectrogram.");
    }

    public static function encodeNotComplete(): self
    {
        return new self('Encode was not called.');
    }

    public static function decodeNotComplete(): self
    {
        return new self('Decode was not called.');
    }

    public static function offsetBeforeAudioStart(int $offset): self
    {
        return new self("Offset ms is before the start of audio.. Offset: {$offset}");
    }

    public static function offsetAfterAudioEnd(int $offset): self
    {
        return new self("Offset ms is after the end of audio. Offset: {$offset}");
    }

    public static function nullPointer(): self
    {
        return new self('Whisper returned a null pointer.');
    }

    public static function genericError(int $code): self
    {
        return new self(
            "Generic whisper error. Error code: {$code}"
        );
    }
}
