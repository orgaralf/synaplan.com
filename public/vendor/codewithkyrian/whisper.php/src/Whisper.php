<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use Psr\Log\LoggerInterface;

/**
 * A High-Level API for Whisper that simplifies the typical transcription workflow
 * and exposes the main features of Whisper
 */
class Whisper
{
    private static ?LoggerInterface $logger = null;

    private WhisperContext $context;

    private WhisperFullParams $params;

    // Prevent direct instantiation
    private function __construct() {}

    /**
     * Available models that can be downloaded
     */
    public const MODELS = [
        'tiny.en',
        'tiny',
        'base.en',
        'base',
        'small.en',
        'small',
        'medium.en',
        'medium',
        'large-v1',
        'large-v2',
        'large-v3',
        'large',
    ];

    /**
     * Create a Whisper instance from a pretrained model
     *
     * @param  string  $modelName  Name/path of the pretrained model. Can be one of: 'tiny.en', 'tiny', 'base.en', 'base', 'small.en', 'small', 'medium.en', 'medium', 'large-v1', 'large-v2', 'large-v3', 'large'
     * @param  string|null  $baseDir  Base directory to store the model. Defaults to the "$XDG_DATA_HOME/whisper.cpp" directory
     * @param  WhisperFullParams|null  $params  Parameters to use when running the model
     */
    public static function fromPretrained(string $modelName,  ?string $baseDir = null, ?WhisperFullParams $params = null,): self
    {
        if (! in_array($modelName, self::MODELS) && ! is_file($modelName)) {
            throw new \RuntimeException(
                sprintf(
                    "'%s' is not a valid pre-converted model or a file path. Choose one of %s",
                    $modelName,
                    implode(', ', self::MODELS)
                )
            );
        }

        $modelPath = in_array($modelName, self::MODELS)
            ? ModelLoader::loadModel($modelName, $baseDir)
            : $modelName;

        $contextParams = WhisperContextParameters::default();

        $instance = new self;
        $instance->context = new WhisperContext($modelPath, $contextParams);
        $instance->params = $params ?? WhisperFullParams::default();
        $instance->context->resetTimings();

        return $instance;
    }

    /**
     * Transcribe audio data
     *
     * @param  float[]|string  $audio  Audio data as float32 array or path to audio file
     * @param  int  $nThreads  Number of threads to use when processing audio
     * @return SegmentData[] An array of transcribed segment data
     */
    public function transcribe(string|array $audio, int $nThreads = 1): array
    {
        if (is_string($audio)) {
            if (! file_exists($audio)) {
                throw new \RuntimeException("File not found: $audio");
            }

            $audio = readAudio($audio);
        }

        if (! isset($this->context)) {
            throw new \RuntimeException('Context is not initialized. Please call Whisper::fromPretrained() first.');
        }

        $this->params = $this->params->withNThreads($nThreads);

        $state = $this->context->createState();
        $state->full($audio, $this->params);

        $segments = [];

        $numSegments = $state->nSegments();
        for ($i = 0; $i < $numSegments; $i++) {
            $segments[] = new SegmentData($i, $state->getSegmentStartTime($i), $state->getSegmentEndTime($i), $state->getSegmentText($i));
        }

        return $segments;
    }

    /**
     * Get the underlying context
     */
    public function getContext(): WhisperContext
    {
        return $this->context;
    }

    public function getParams(): WhisperFullParams
    {
        return $this->params;
    }

    public function setParams(WhisperFullParams $params): self
    {
        $this->params = $params;

        return $this;
    }

    public static function setLogger(?LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function getLogger(): ?LoggerInterface
    {
        return self::$logger;
    }
}
