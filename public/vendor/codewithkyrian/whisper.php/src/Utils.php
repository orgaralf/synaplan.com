<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

function readAudio($path, int $chunkSize = 2048): array
{
    $sfInfo = Sndfile::new('SF_INFO');
    $sndFile = Sndfile::open($path, Sndfile::enum('SFM_READ'), \FFI::addr($sfInfo));

    $audioData = '';
    $totalOutputFrames = 0;

    $state = Samplerate::srcNew(Samplerate::enum('SRC_SINC_FASTEST'), $sfInfo->channels);

    $inputSize = $chunkSize * $sfInfo->channels;
    $inputData = Samplerate::new("float[$inputSize]");
    $outputSize = $chunkSize * $sfInfo->channels;
    $outputData = Samplerate::new("float[$outputSize]");

    $srcData = Samplerate::new('SRC_DATA');
    $srcData->data_in = Samplerate::cast('float *', $inputData);
    $srcData->output_frames = $chunkSize / $sfInfo->channels;
    $srcData->data_out = Samplerate::cast('float *', $outputData);
    $srcData->src_ratio = 16000 / $sfInfo->samplerate;

    while (true) {
        /* Read the chunk of data */
        $srcData->input_frames = Sndfile::readFrames($sndFile, $inputData, $chunkSize);

        /* Add to tensor data without resample if the sample rate is the same */
        if ($sfInfo->samplerate === 16000) {
            $strBuffer = \FFI::string($inputData, $srcData->input_frames * $sfInfo->channels * \FFI::sizeof($inputData[0]));
            $audioData .= $strBuffer;
            $totalOutputFrames += $srcData->input_frames;
            if ($srcData->input_frames < $chunkSize) {
                break;
            }

            continue;
        }

        /* The last read will not be a full buffer, so snd_of_input. */
        if ($srcData->input_frames < $chunkSize) {
            $srcData->end_of_input = Sndfile::enum('SF_TRUE');
        }

        /* Process current block. */
        Samplerate::srcProcess($state, \FFI::addr($srcData));

        /* Terminate if done. */
        if ($srcData->end_of_input && $srcData->output_frames_gen === 0) {
            break;
        }

        /* Add the processed data to the tensor data */
        $outputSize = $srcData->output_frames_gen * $sfInfo->channels * \FFI::sizeof($outputData[0]);
        $strBuffer = \FFI::string($outputData, $outputSize);
        $audioData .= $strBuffer;
        $totalOutputFrames += $srcData->output_frames_gen;
    }

    Samplerate::srcDelete($state);

    $array = array_values(unpack('g*', $audioData));
    if ($sfInfo->channels === 2) {
        $newArray = [];
        for ($i = 0; $i < count($array); $i += 2) {
            $newArray[] = ($array[$i] + $array[$i + 1]) / 2;
        }

        return $newArray;
    }

    return $array;
}

/**
 * Converts a timestamp a human-readable string.
 *
 * Inspired by whisper.cpp `to_timestamp`
 *
 * E.g. 0 -> "00:00:00", 376 -> 00:00:03,760, 3536 -> 00:00:35,360
 *
 * @param  int  $t  Input timestamp from Whisper
 * @param  string  $separator  Separator between seconds and milliseconds
 */
function toTimestamp(int $t, string $separator = ','): string
{
    $milliseconds = (int) ($t * 10);
    $seconds = (int) ($milliseconds / 1000);
    $minutes = (int) ($seconds / 60);
    $hours = (int) ($minutes / 60);

    $milliseconds = $milliseconds % 1000;
    $seconds = $seconds % 60;
    $minutes = $minutes % 60;

    return sprintf(
        '%02d:%02d:%02d%s%03d',
        $hours,
        $minutes,
        $seconds,
        $separator,
        $milliseconds
    );
}

/**
 * Creates a raw text file from segments
 *
 * @param  SegmentData[]  $segments  Array of segments
 * @param  string  $outputFilePath  Output file path
 * @return string Absolute path of the created file
 */
function outputTxt(array $segments, string $outputFilePath): string
{
    if (! str_ends_with($outputFilePath, '.txt')) {
        $outputFilePath .= '.txt';
    }

    $absolutePath = realpath(dirname($outputFilePath)).DIRECTORY_SEPARATOR.basename($outputFilePath);

    file_put_contents($absolutePath, implode(PHP_EOL, array_map(fn ($segment) => $segment->text, $segments)));

    return $absolutePath;
}

/**
 * Creates a VTT file from segments
 *
 * @param  SegmentData[]  $segments  Array of segments
 * @param  string  $outputFilePath  Output file path
 * @return string Absolute path of the created file
 */
function outputVtt(array $segments, string $outputFilePath): string
{
    if (! str_ends_with($outputFilePath, '.vtt')) {
        $outputFilePath .= '.vtt';
    }

    $content = "WEBVTT\n\n";
    foreach ($segments as $segment) {
        $content .= sprintf(
            "%s --> %s\n%s\n\n",
            toTimestamp($segment->startTimestamp, '.'),
            toTimestamp($segment->endTimestamp, '.'),
            $segment->text
        );
    }

    if (! file_exists(dirname($outputFilePath))) {
        mkdir(dirname($outputFilePath), 0755, true);
    }

    file_put_contents($outputFilePath, $content);

    return $outputFilePath;
}

/**
 * Creates an SRT file from segments
 *
 * @param  SegmentData[]  $segments  Array of segments
 * @param  string  $outputFilePath  Output file path
 * @return string Absolute path of the created file
 */
function outputSrt(array $segments, string $outputFilePath): string
{
    if (! str_ends_with($outputFilePath, '.srt')) {
        $outputFilePath .= '.srt';
    }

    $content = '';

    foreach ($segments as $index => $segment) {
        $content .= sprintf(
            "%d\n%s --> %s\n%s\n\n",
            $index + 1,
            toTimestamp($segment->startTimestamp, ','),
            toTimestamp($segment->endTimestamp, ','),
            $segment->text
        );
    }

    if (! file_exists(dirname($outputFilePath))) {
        mkdir(dirname($outputFilePath), 0755, true);
    }

    file_put_contents($outputFilePath, $content);

    return $outputFilePath;
}

/**
 * Creates a CSV file from segments
 *
 * @param  SegmentData  $segments  Array of segments
 * @param  string  $outputFilePath  Output file path
 * @return string Absolute path of the created file
 */
function outputCsv(array $segments, string $outputFilePath): string
{
    if (! str_ends_with($outputFilePath, '.csv')) {
        $outputFilePath .= '.csv';
    }

    if (! file_exists(dirname($outputFilePath))) {
        mkdir(dirname($outputFilePath), 0755, true);
    }

    $handle = fopen($outputFilePath, 'w');

    foreach ($segments as $segment) {
        fputcsv($handle, [
            $segment->startTimestamp * 10,
            $segment->endTimestamp * 10,
            $segment->text,
        ]);
    }

    fclose($handle);

    return $outputFilePath;
}

function outputJson(array $segments, string $outputFilePath): string
{
    if (! str_ends_with($outputFilePath, '.json')) {
        $outputFilePath .= '.json';
    }

    if (! file_exists(dirname($outputFilePath))) {
        mkdir(dirname($outputFilePath), 0755, true);
    }

    $json = json_encode(array_map(fn ($segment) => [
        'start' => $segment->startTimestamp,
        'end' => $segment->endTimestamp,
        'text' => $segment->text,
    ], $segments), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    file_put_contents($outputFilePath, $json);

    return $outputFilePath;
}

/**
 * Creates output files in multiple formats at once
 *
 * @param  SegmentData[]  $segments  Array of segments
 * @param  string  $outputFilePath  Base output file path (without extension)
 * @param  array  $formats  Array of formats to output (e.g., ['txt', 'vtt', 'srt', 'csv'])
 * @return array<string, string> Array of format => file path pairs
 */
function outputMultiple(array $segments, string $outputFilePath, array $formats = ['txt', 'vtt', 'srt', 'csv']): array
{
    $results = [];

    $formatMap = [
        'txt' => 'outputTxt',
        'vtt' => 'outputVtt',
        'srt' => 'outputSrt',
        'csv' => 'outputCsv',
    ];

    foreach ($formats as $format) {
        if (isset($formatMap[$format])) {
            $method = $formatMap[$format];
            $results[$format] = $method($segments, $outputFilePath);
        }
    }

    return $results;
}

function timeUsage(bool $milliseconds = false, bool $sinceLastCall = true, bool $returnString = true): string|float
{
    static $lastCallTime = 0;

    $currentTime = microtime(true);

    $timeDiff = $sinceLastCall ? ($lastCallTime !== 0 ? $currentTime - $lastCallTime
        : $currentTime - $_SERVER['REQUEST_TIME_FLOAT'])
        : $currentTime - $_SERVER['REQUEST_TIME_FLOAT'];

    $lastCallTime = $currentTime;

    $timeDiff = $milliseconds ? $timeDiff * 1000 : $timeDiff;

    //    return @round($timeDiff, 4) . ($milliseconds ? ' ms' : ' s');
    return $returnString ? @round($timeDiff, 4).($milliseconds ? ' ms' : ' s') : @round($timeDiff, 4);
}

function memoryUsage(): string
{
    $mem = memory_get_usage(true);
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    return @round($mem / pow(1024, ($i = floor(log($mem, 1024)))), 2).' '.$unit[$i];
}
