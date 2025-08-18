<?php

declare(strict_types=1);

namespace GeminiAPI\Enums;

/**
 * @deprecated Use constants from GeminiAPI\Resources\ModelName instead
 */
enum ModelName: string
{
    case Default = 'models/text-bison-001';
    case GeminiPro = 'models/gemini-pro';
    case GeminiPro10 = 'models/gemini-1.0-pro';
    case GeminiPro10Latest = 'models/gemini-1.0-pro-latest';
    case GeminiPro15 = 'models/gemini-1.5-pro';
    case GeminiPro15Flash = 'models/gemini-1.5-flash';
    case GeminiProVision = 'models/gemini-pro-vision';
    case Embedding = 'models/embedding-001';
    case AQA = 'models/aqa';
}
