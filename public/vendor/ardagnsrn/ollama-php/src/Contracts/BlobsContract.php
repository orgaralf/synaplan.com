<?php

namespace ArdaGnsrn\Ollama\Contracts;

interface BlobsContract
{
    /**
     * @param string $digest
     * @return bool
     */
    public function exists(string $digest): bool;

    /**
     * @param string $digest
     * @return bool
     */
    public function create(string $digest): bool;
}
