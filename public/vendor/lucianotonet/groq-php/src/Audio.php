<?php

namespace LucianoTonet\GroqPHP;

/**
 * Class Audio
 * This class provides methods to work with audio-related functionalities,
 * including obtaining transcription and translation services.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Audio
{
    private Groq $groq;

    /**
     * Audio constructor.
     * @param Groq $groq An instance of the Groq class used for API interactions.
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Returns a Transcriptions object to work with audio transcriptions.
     *
     * @return Transcriptions An instance of the Transcriptions class.
     */
    public function transcriptions(): Transcriptions
    {
        return new Transcriptions($this->groq);
    }

    /**
     * Returns a Translations object to work with audio translations.
     *
     * @return Translations An instance of the Translations class.
     */
    public function translations(): Translations
    {
        return new Translations($this->groq);
    }
}
