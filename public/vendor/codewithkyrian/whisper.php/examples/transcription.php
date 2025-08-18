<?php

declare(strict_types=1);

use Codewithkyrian\Whisper\ModelLoader;
use Codewithkyrian\Whisper\SegmentData;
use Codewithkyrian\Whisper\WhisperContext;
use Codewithkyrian\Whisper\WhisperContextParameters;
use Codewithkyrian\Whisper\WhisperException;
use Codewithkyrian\Whisper\WhisperFullParams;

use function Codewithkyrian\Whisper\outputSrt;
use function Codewithkyrian\Whisper\readAudio;
use function Codewithkyrian\Whisper\toTimestamp;

require_once __DIR__.'/../vendor/autoload.php';

$modelPath = ModelLoader::loadModel('tiny.en', __DIR__.'/models');
$audioPath = __DIR__.'/sounds/jfk.wav';

try {
    $contextParams = WhisperContextParameters::default();
    $ctx = new WhisperContext($modelPath, $contextParams);

    $state = $ctx->createState();
    $fullParams = WhisperFullParams::default()
        ->withNThreads(1)
        ->withPrintTimestamps()
        ->withLanguage('en')
        ->withTokenTimestamps();

    $audio = readAudio($audioPath);

    $state->full($audio, $fullParams);

    $segments = [];
    $numSegments = $state->nSegments();
    for ($i = 0; $i < $numSegments; $i++) {
        $segment = $state->getSegmentText($i);
        $startTimestamp = $state->getSegmentStartTime($i);
        $endTimestamp = $state->getSegmentEndTime($i);

        printf(
            "[%s - %s]: %s\n",
            toTimestamp($startTimestamp),
            toTimestamp($endTimestamp),
            $segment
        );

        $segments[] = new SegmentData($i, $startTimestamp, $endTimestamp, $segment);
    }

    // Create output files
    $transcriptionPath = __DIR__.'/outputs/transcription.srt';
    outputSrt($segments, $transcriptionPath);
} catch (WhisperException $e) {
    fprintf(STDERR, "Whisper error: %s\n", $e->getMessage());
    exit(1);
} catch (Exception $e) {
    fprintf(STDERR, "Error: %s\n", $e->getMessage());
    exit(1);
}
