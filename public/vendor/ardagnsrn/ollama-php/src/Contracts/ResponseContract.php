<?php

namespace ArdaGnsrn\Ollama\Contracts;

interface ResponseContract
{
    /**
     * Returns the array representation of the Response.
     *
     * @return array
     */
    public function toArray(): array;
}
