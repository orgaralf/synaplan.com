<?php

declare(strict_types=1);

namespace GeminiAPI\Requests;

class ListModelsRequest implements RequestInterface
{
    public function getOperation(): string
    {
        return 'models';
    }

    public function getHttpMethod(): string
    {
        return 'GET';
    }

    public function getHttpPayload(): string
    {
        return '';
    }
}
