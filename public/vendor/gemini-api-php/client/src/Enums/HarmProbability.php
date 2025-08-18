<?php

declare(strict_types=1);

namespace GeminiAPI\Enums;

enum HarmProbability: string
{
    case HARM_PROBABILITY_UNSPECIFIED = 'HARM_PROBABILITY_UNSPECIFIED';
    case NEGLIGIBLE = 'NEGLIGIBLE';
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
}
