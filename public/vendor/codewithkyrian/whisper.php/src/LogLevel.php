<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use Psr\Log\LogLevel as PsrLogLevel;

/**
 * Enum representing GGML log levels, matching the C implementation
 */
enum LogLevel: int
{
    case NONE = 0;
    case DEBUG = 1;
    case INFO = 2;
    case WARN = 3;
    case ERROR = 4;
    case CONT = 5; // continue previous log

    public function toPsrLogLevel(): string
    {
        return match ($this) {
            self::NONE, self::CONT, self::INFO => PsrLogLevel::INFO,
            self::DEBUG => PsrLogLevel::DEBUG,
            self::WARN => PsrLogLevel::WARNING,
            self::ERROR => PsrLogLevel::ERROR,
        };
    }

    public static function fromPsrLogLevel($psrLogLevel): static
    {
        return match ($psrLogLevel) {
            PsrLogLevel::DEBUG => self::DEBUG,
            PsrLogLevel::INFO, PsrLogLevel::NOTICE => self::INFO,
            PsrLogLevel::WARNING => self::WARN,
            PsrLogLevel::ERROR, PsrLogLevel::ALERT, PsrLogLevel::CRITICAL => self::ERROR,
        };
    }
}
