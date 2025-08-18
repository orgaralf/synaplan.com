<?php

declare(strict_types=1);

use Codewithkyrian\Whisper\SegmentData;
use Codewithkyrian\Whisper\TokenData;
use Codewithkyrian\Whisper\WhisperContext;
use Codewithkyrian\Whisper\WhisperFullParams;

beforeEach(function () {
    if (! extension_loaded('ffi')) {
        $this->markTestSkipped('FFI extension is not loaded.');
    }

    $this->modelPath = loadTestModel('tiny.en');
});

it('can transcribe a short audio file with default parameters', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $fullParams = WhisperFullParams::default();

    $pcm = loadTestAudio('jfk');
    $state->full($pcm, $fullParams);

    $numSegments = $state->nSegments();
    expect($numSegments)->toBeGreaterThan(0);

    for ($i = 0; $i < $numSegments; $i++) {
        $segment = $state->getSegmentText($i);
        $startTimestamp = $state->getSegmentStartTime($i);
        $endTimestamp = $state->getSegmentEndTime($i);

        // Verify segment data
        expect($segment)->toBeString()
            ->and($startTimestamp)->toBeInt()
            ->and($endTimestamp)->toBeInt()
            ->and($startTimestamp)->toBeLessThanOrEqual($endTimestamp);
    }
});

it('supports token timestamps', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $fullParams = WhisperFullParams::default()->withTokenTimestamps();

    $pcm = loadTestAudio('jfk');
    $state->full($pcm, $fullParams);

    $numSegments = $state->nSegments();
    expect($numSegments)->toBeGreaterThan(0);

    // Check token-level details
    $numSegments = $state->nSegments();
    for ($segmentIndex = 0; $segmentIndex < $numSegments; $segmentIndex++) {
        $tokenCount = $state->nTokens($segmentIndex);

        for ($tokenIndex = 0; $tokenIndex < $tokenCount; $tokenIndex++) {
            $tokenText = $state->tokenText($segmentIndex, $tokenIndex);
            $tokenData = $state->tokenData($segmentIndex, $tokenIndex);

            expect($tokenText)->toBeString()
                ->and($tokenData)->toBeInstanceOf(TokenData::class)
                ->and($tokenData->startTimestamp)->toBeInt()
                ->and($tokenData->endTimestamp)->toBeInt()
                ->and($tokenData->startTimestamp)->toBeLessThanOrEqual($tokenData->endTimestamp);
        }
    }
});

it('can process across multiple threads', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $fullParams = WhisperFullParams::default()->withNThreads(2);

    $pcm = loadTestAudio('jfk');
    $state->full($pcm, $fullParams);

    $numSegments = $state->nSegments();
    expect($numSegments)->toBeGreaterThan(0);
});

it('can convert raw PCM audio to log mel spectrogram', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $pcm = loadTestAudio('jfk');

    $state->pcmToMel($pcm, 3);

    expect($state->nLen())->toBeGreaterThan(0);
});

it('can get a language id for any language', function () {
    $ctx = new WhisperContext($this->modelPath);

    expect($ctx->langId('en'))->toBe(0)
        ->and($ctx->langId('de'))->toBe(2);
});

it('can get a short string for any language id', function () {
    $ctx = new WhisperContext($this->modelPath);

    expect($ctx->langStr(0))->toBe('en')
        ->and($ctx->langStr(2))->toBe('de');
});

it('can get a full string for any language id', function () {
    $ctx = new WhisperContext($this->modelPath);

    expect($ctx->langStrFull(0))->toBe('english')
        ->and($ctx->langStrFull(2))->toBe('german');
});

it('can auto-detect language', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $pcm = loadTestAudio('jfk');

    $state->pcmToMel($pcm, 3);

    $result = $state->langDetect(5500, 1);

    expect($result['top_lang_id'])->toBeInt();
});

it('can retrieve model vocabulary information', function () {
    $ctx = new WhisperContext($this->modelPath);

    expect($ctx->modelNVocab())->toBeInt();
});

it('handles callbacks for segment processing', function () {
    $ctx = new WhisperContext($this->modelPath);
    $state = $ctx->createState();

    $processedSegments = [];
    $fullParams = WhisperFullParams::default()
        ->withNThreads(1)
        ->withSegmentCallback(function ($segmentData) use (&$processedSegments) {
            $processedSegments[] = $segmentData;
        });

    $pcm = loadTestAudio('jfk');

    $state->full($pcm, $fullParams);

    expect($processedSegments)->not->toBeEmpty()
        ->and($processedSegments[0])->toBeInstanceOf(SegmentData::class);
});
