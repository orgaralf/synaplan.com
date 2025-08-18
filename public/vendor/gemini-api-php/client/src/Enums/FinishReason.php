<?php

declare(strict_types=1);

namespace GeminiAPI\Enums;

enum FinishReason: string
{
    case FINISH_REASON_UNSPECIFIED = 'FINISH_REASON_UNSPECIFIED';
    case STOP = 'STOP';
    case MAX_TOKENS = 'MAX_TOKENS';
    case SAFETY = 'SAFETY';
    case RECITATION = 'RECITATION';
    case OTHER = 'OTHER';
}
