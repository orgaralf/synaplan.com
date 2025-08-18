<?php

namespace GeminiAPI\Traits;

use GeminiAPI\Enums\ModelName;

trait ModelNameToString
{
    private function modelNameToString(ModelName|string $modelName): string
    {
        return is_string($modelName) ? "models/$modelName" : $modelName->value;
    }
}
