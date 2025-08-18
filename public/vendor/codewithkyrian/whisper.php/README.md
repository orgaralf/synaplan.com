# whisper.php

A PHP binding for [whisper.cpp](https://github.com/ggerganov/whisper.cpp/), enabling high-performance automatic speech
recognition and transcription.

## Requirements

- PHP 8.1+
- FFI Extension

## Platform Support

Currently, whisper.php supports the following platforms:

- Linux (x86_64 and arm64)
- macOS (Apple Silicon and Intel)

Note: Windows support is currently in development. Contributions and help are welcome to expand platform compatibility!

## Features

Speech recognition can be complex, but it doesn't have to be. Whisper.php simplifies the process by providing:

- 🚀 High and low-level APIs
- 📁 Model auto-downloading
- 🎧 Support for various audio formats
- 📝 Multiple output format exports
- 🔊 Callback support for streaming and progress tracking

## Installation

Install the library using Composer:

```bash
composer require codewithkyrian/whisper.php
```

Whisper.php requires the FFI extension to be enabled. In your php.ini configuration file, uncomment or add:

```ini
extension = ffi
```

## Quick Start

### Low-Level API

The low-level API provides developers with granular control over the transcription process. It closely mimics the
original C implementation,
allowing for detailed configuration and manual segment processing:

```php
// Initialize context with a model
$contextParams = WhisperContextParameters::default();
$ctx = new WhisperContext("path/to/model.bin", $contextParams);

// Create state and set parameters
$state = $ctx->createState();
$fullParams = WhisperFullParams::default()
    ->withNThreads(4)
    ...
    ->withLanguage('en');

// Transcribe audio
$state->full($pcm, $fullParams);

// Process segments
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
}
```

#### Model Loading

Downloading and managing whisper models can be a complex process. Whisper.php simplifies this with the ModelLoader, a
convenient utility that
streamlines model acquisition and management.

```php
// Automatically download and load a model if it's already downloaded
$modelPath = ModelLoader::loadModel('tiny.en', __DIR__.'/models');
```

The `ModelLoader::loadModel()` method accepts two key parameters:

1. **Model Name**: Specify the model variant you want to use:
    - Supported base models: tiny, tiny.en, base, base.en, small, small.en, medium, medium.en, large, large.en
    - Note: Quantized models (q5, q8, etc.) are not supported by this utility
2. **Model Directory**: Specify the local directory where models should be stored and searched

In the example above, it looks for `ggml-tiny.en.bin` in the `__DIR__./models` directory and if the model isn't found
locally, it automatically downloads it
from the official `whisper.cpp` huggingface repository

#### Libraries Loading

Whisper.php relies on platform-specific shared libraries, which are automatically downloaded the first time you
initialize a model context. While this may cause a slight delay on the initial run, the process is one-time (unless you
update the library via Composer). Once the libraries are cached, subsequent runs will perform as expected.

#### Audio Input

THe Whisper model expects a float array of sampled audio data at 16kHz. While tools like ffmpeg can generate this data,
Whisper.php provides a built-in helper function to simplify the process for you.

```php
// Convenient audio reading function
$pcm = readAudio($audioPath);
```

The `readAudio()`helper function Supports multiple audio formats (MP3, WAV, OGG, M4A), automatically resamples to 16kHz
and does these efficiently using `libsndfile` and `libsamplerate`

The low level approach is ideal for developers who need:

- Exact control over transcription parameters
- Custom segment processing
- Integration with existing complex audio processing pipelines

### High-Level API

For those seeking a more straightforward experience, the high-level API offers a simpler more abstracted workflow:

```php
// Simple transcription
$whisper = Whisper::fromPretrained('tiny.en', baseDir: __DIR__.'/models');
$audio = readAudio(__DIR__.'/sounds/sample.wav');
$segments = $whisper->transcribe($audio, 4);

// Accessing segment data
foreach ($segments as $segment) {
    echo toTimestamp($segment->startTimestamp) . ': ' . $segment->text . "\n";
}
```

The Whisper::fromPretrained() method simplifies the entire setup process with three key parameters:

1. **Model Name**: Specify the whisper model variant (e.g., 'tiny.en', 'base', 'small.en')
2. **Base Directory**: Specify where models should be stored and searched
3. **Transcription Parameters**: Optionally customize transcription behavior

```php
// Advanced usage with custom parameters
$params = WhisperFullParams::default()
    ->withNThreads(4)
    ->withLanguage('en');

$whisper = Whisper::fromPretrained(
    'tiny.en',           // Model name
    baseDir: __DIR__.'/models',  // Model storage directory
    params: $params      // Custom transcription parameters
);
```

The high-level API is perfect for quick prototyping, simple projects, or when you want to minimize boilerplate code
while maintaining the power of the underlying whisper.cpp technology.

## Whisper Full Parameters

The `WhisperFullParams` offers a comprehensive and flexible configuration mechanism for fine-tuning the transcription
process. It's designed with a fluent interface thus enabling method chaining and creating a clean, readable way to
configure transcription parameters.

### Language Detection

While the whisper model is remarkably good at automatic language detection, there are scenarios where manually
specifying the language can improve accuracy:

```php
$fullParams = WhisperFullParams::default()
    ->withLanguage('en');  // Specify two-letter language code eg. 'en' (English), 'de' (German), 'es' (Spanish)
```

### Threading

Computational performance can be fine-tuned by adjusting the number of threads used during transcription:

```php
$fullParams = WhisperFullParams::default()
    ->withNThreads(8);  // Default is 4
```

More threads can speed up transcription on multi-core systems. For very short audio files however, more threads might
introduce overhead. Experiment with thread counts to find the sweet spot for your specific use case and hardware
configuration.

### Segment Callback

In many real-world applications, you'll want to process transcription segments as they're generated, rather than waiting
for the entire transcription to complete.
You can achieve that by providing a callback to the full params object that accepts a `SegmentData` object.

```php
$fullParams = WhisperFullParams::default()
    ->withSegmentCallback(function (SegmentData $data) {
        printf("[%s - %s]: %s\n", 
            toTimestamp($data->startTimestamp), 
            toTimestamp($data->endTimestamp), 
            $data->text
        );
    })
```

### Progress Callback

Provide a callback to the full params to get access to the transcription progress.

```php
$fullParams = $fullParams
        ->withProgressCallback(function (int $progress) {
            printf("Transcribing: %d%%\n", $progress);
        });
```

There are lots of configurations in the `WhisperFullParams`. Modern IDEs with robust PHP intellisense will reveal a
comprehensive list of configuration methods as you type, offering real-time suggestions and documentation for each
parameter. Simply start
typing `withXXX()` after `WhisperFullParams::default()`, and your IDE will guide you through the available configuration
options.

## Exporting Outputs

Once you've generated your transcription segments, you'll often need to export them in various formats for different use
cases. Whisper.php provides convenient helper methods to export transcription segments to the most popular and
widely-used formats.
The exported segments are derived from an array of `SegmentData` objects, each containing precise timestamp and text
information.

```php
outputTxt($segments, 'transcription.txt'); // Ideal for quick reading, documentation, or further text processing
outputVtt($segments, 'subtitles.vtt'); // Primarily used for web-based video subtitles, compatible with HTML5 video players
outputSrt($segments, 'subtitles.srt'); // Widely supported by media players, video editing software, and streaming platforms
outputCsv($segments, 'transcription.csv'); // Perfect for data analysis and spreadsheet applications
```

## Logging

Whisper.php provides flexible logging capabilities, fully compatible with PSR-3 standards, which means seamless
integration with popular logging libraries like Monolog and Laravel's logging system.

By default, logging is disabled, but the library includes a built-in `WhisperLogger` that allows quick and easy logging:

```php
// Log to a file
Whisper::setLogger(new WhisperLogger('whisper.log'));

// Log to standard output
Whisper::setLogger(new WhisperLogger(STDOUT));
```

Just make sure to call this `setLogger` method before initializing your WhisperContext.

For more advanced logging needs, whisper.php integrates perfectly with Monolog, the most popular PHP logging library:

```php
$monologLogger = new Logger('whisper');
$monologLogger->pushHandler(new StreamHandler('whisper.log', Logger::DEBUG));
$monologLogger->pushHandler(new FirePHPHandler());

// Set the Monolog logger
Whisper::setLogger($monologLogger);
```

OR with Laravel Application Logger

```php
// Using Laravel's Log facade
Whisper::setLogger(Log::getLogger());

// Or directly with Laravel's logger
Whisper::setLogger(app('log'));
```

## Contributing

Contributions are welcome! Especially for:

- Windows platform support
- Additional features
- Bug fixes

## License

This project is licensed under the MIT License. See
the [LICENSE](https://github.com/codewithkyrian/whisper.php/blob/main/LICENSE) file for more information.

## Acknowledgements

- [whisper.cpp](https://github.com/ggerganov/whisper.cpp) - The underlying speech recognition technology
