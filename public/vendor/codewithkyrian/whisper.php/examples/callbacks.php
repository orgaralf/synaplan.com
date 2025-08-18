<?php

declare(strict_types=1);

use Codewithkyrian\Whisper\SegmentData;
use Codewithkyrian\Whisper\WhisperContext;
use Codewithkyrian\Whisper\WhisperContextParameters;
use Codewithkyrian\Whisper\WhisperException;
use Codewithkyrian\Whisper\WhisperFullParams;

use function Codewithkyrian\Whisper\readAudio;
use function Codewithkyrian\Whisper\toTimestamp;

require_once __DIR__.'/../vendor/autoload.php';

$modelPath = __DIR__.'/models/ggml-tiny.en.bin';
$audioPath = __DIR__.'/sounds/jfk.wav';

try {
    $contextParams = WhisperContextParameters::default();
    $ctx = new WhisperContext($modelPath, $contextParams);

    $state = $ctx->createState();
    $fullParams = WhisperFullParams::default()
        ->withSegmentCallback(function (SegmentData $data) {
            $start = toTimestamp($data->startTimestamp);
            $end = toTimestamp($data->endTimestamp);
            printf("[%s - %s]: %s\n", $start, $end, $data->text);
        })
        ->withProgressCallback(function (int $progress) {
            printf("Transcribing: %d%%\n", $progress);
        });

    $audio = readAudio($audioPath);

    $state->full($audio, $fullParams);
} catch (WhisperException $e) {
    fprintf(STDERR, "Whisper error: %s\n", $e->getMessage());
    exit(1);
} catch (Exception $e) {
    fprintf(STDERR, "Error: %s\n", $e->getMessage());
    exit(1);
}
