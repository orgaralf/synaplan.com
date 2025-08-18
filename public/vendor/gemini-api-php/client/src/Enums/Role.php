<?php

declare(strict_types=1);

namespace GeminiAPI\Enums;

enum Role: string
{
    case User = 'user';
    case Model = 'model';
}
