<?php

use Codewithkyrian\Whisper\ModelLoader;

use function Codewithkyrian\Whisper\readAudio;

function loadTestAudioPath($name): string
{
    return match ($name) {
        'jfk' => __DIR__.'/../examples/sounds/jfk.wav',
        'george-bush' => __DIR__.'/../examples/sounds/george-bush-columbia.ogg',
        'night-sleep' => __DIR__.'/../examples/sounds/why-you-need-a-good-night-sleep.mp3',
        default => __DIR__.'/../examples/sounds/'.$name.'.wav',
    };
}

function loadTestAudio(string $name): array
{
    $audioPath = loadTestAudioPath($name);

    return readAudio($audioPath);
}

function loadTestModel(string $name): string
{
    return ModelLoader::loadModel($name, __DIR__.'/../examples/models');
}
