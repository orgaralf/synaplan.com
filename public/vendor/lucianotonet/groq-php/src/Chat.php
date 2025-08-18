<?php

namespace LucianoTonet\GroqPHP;

/**
 * Class Chat
 * @package LucianoTonet\GroqPHP
 */
class Chat
{
    private Groq $groq;

    /**
     * Chat constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * @return Completions
     */
    public function completions(): Completions
    {
        return new Completions($this->groq);
    }
}