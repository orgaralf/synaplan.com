<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

class ModelLoader
{
    private const DOWNLOAD_URL = 'https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-%s.bin';

    /**
     * Load a local model or download and cache a model from the official repository
     */
    public static function loadModel(string $modelName, ?string $baseDir = null): string
    {
        $baseDir ??= self::getDefaultModelDir();

        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $modelPath = $baseDir.DIRECTORY_SEPARATOR.'ggml-'.$modelName.'.bin';
        if (file_exists($modelPath)) {
            return $modelPath;
        }

        $url = sprintf(self::DOWNLOAD_URL, $modelName);

        Whisper::getLogger()?->info("Model not found at $modelPath. Downloading from $url...");

        $tempFile = tempnam(sys_get_temp_dir(), 'whisper');

        $ch = curl_init();
        $fp = fopen($tempFile, 'w');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        if (! curl_exec($ch)) {
            fclose($fp);
            unlink($tempFile);
            throw new \RuntimeException(sprintf('Failed to download model from %s: %s', $url, curl_error($ch)));
        }

        curl_close($ch);
        fclose($fp);

        if (! rename($tempFile, $modelPath)) {
            unlink($tempFile);
            throw new \RuntimeException("Failed to move downloaded model to $modelPath");
        }

        Whisper::getLogger()?->info("Model downloaded to $modelPath");

        return $modelPath;
    }

    /**
     * Get the default directory for storing models
     */
    public static function getDefaultModelDir(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return getenv('LOCALAPPDATA').DIRECTORY_SEPARATOR.'whisper.cpp';
        }

        $xdgData = getenv('XDG_DATA_HOME');
        if ($xdgData) {
            return $xdgData.DIRECTORY_SEPARATOR.'whisper.cpp';
        }

        return getenv('HOME').DIRECTORY_SEPARATOR.'.local'.
            DIRECTORY_SEPARATOR.'share'.DIRECTORY_SEPARATOR.'whisper.cpp';
    }
}
