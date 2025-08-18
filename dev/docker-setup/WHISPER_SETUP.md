# Whisper.cpp Setup for Synaplan

This document explains the setup and usage of whisper.cpp for local audio transcription in the Synaplan application.

## Overview

Whisper.cpp is a high-performance inference of OpenAI's Whisper automatic speech recognition (ASR) model. It provides local, offline speech-to-text capabilities without requiring external API calls.

## Installation

The whisper.cpp installation is handled automatically in the Docker container:

1. **Dockerfile Updates**: The Dockerfile includes:
   - Build tools (cmake, pkg-config, build-essential)
   - whisper.cpp compilation from source
   - Model download during build

2. **Models**: The following models are downloaded during container build:
   - `medium.bin` (1.5 GiB) - Recommended for good accuracy
   - `base.bin` (142 MiB) - Faster, smaller model

## Model Information

Based on [Hugging Face whisper.cpp repository](https://huggingface.co/ggerganov/whisper.cpp):

| Model | Size | SHA | Description |
|-------|------|-----|-------------|
| medium | 1.5 GiB | fd9727b6e1217c2f614f9b698455c4ffd82463b4 | Recommended for good accuracy |
| base | 142 MiB | 465707469ff3a37a2b9b8d8f89f2f99de7299dac | Faster, smaller model |
| small | 466 MiB | 55356645c2b361a969dfd0ef2c5a50d530afd8d5 | Good balance of speed/accuracy |

## Usage in PHP

The application uses the `codewithkyrian/whisper.php` package to interface with whisper.cpp:

```php
use Codewithkyrian\Whisper\Whisper;
use function Codewithkyrian\Whisper\readAudio;

// Load the medium model
$whisper = Whisper::fromPretrained('medium', baseDir: __DIR__.'/whispermodels');

// Read audio file
$audio = readAudio('./path/to/audio.mp3');

// Transcribe with 4 threads
$segments = $whisper->transcribe($audio, 4);

// Extract full text
$fullText = "";
foreach ($segments as $segment) {
    if(strlen($fullText)>2) $fullText .= " ";
    $fullText .= $segment->text;
}
echo trim($fullText);
```

## Implementation in Central.php

The whisper.cpp integration is implemented in `web/inc/_central.php`:

1. **MP3 Processing**: Direct transcription of MP3 files
2. **MP4 Processing**: Audio extraction followed by transcription
3. **Fallback**: External service fallback if local whisper fails

### Key Features:
- **Error Handling**: Graceful fallback to external services
- **Threading**: Uses 4 threads for optimal performance
- **Model Selection**: Uses 'medium' model for best accuracy
- **Streaming Support**: Progress updates during processing

## Testing

Use the test file `devtests/testwhisper.php` to verify the setup:

```bash
# Access the test file in your browser
http://localhost/devtests/testwhisper.php
```

The test file will:
- Check model availability
- Test model loading
- Perform transcription on test audio files
- Display performance statistics

## Performance Considerations

1. **Memory Usage**: The medium model requires ~1.5GB RAM
2. **Processing Speed**: Transcription speed depends on audio length and hardware
3. **Threading**: Adjust thread count based on available CPU cores
4. **Model Selection**: Choose model size based on accuracy vs. speed requirements

## Troubleshooting

### Common Issues:

1. **Model Not Found**: Ensure models are downloaded to `/var/www/html/inc/whispermodels/`
2. **Permission Errors**: Check file permissions on models directory
3. **Memory Issues**: Consider using smaller models (base/small) for limited RAM
4. **Audio Format**: Ensure audio files are in supported formats (MP3, WAV)

### Debug Steps:

1. Check model files exist: `ls -la /var/www/html/inc/whispermodels/`
2. Verify whisper binary: `which whisper`
3. Test with simple audio file
4. Check PHP error logs for detailed error messages

## Configuration

### Model Directory
Models are stored in: `/var/www/html/inc/whispermodels/`

### Available Models
- `medium.bin` - Default model for production use
- `base.bin` - Faster alternative for development/testing
- `small.bin` - Good balance (can be added if needed)

### Thread Configuration
Current setting: 4 threads
Adjust in `_central.php` line: `$segments = $whisper->transcribe($audio, 4);`

## Future Enhancements

1. **Model Quantization**: Use quantized models for reduced memory usage
2. **Language Detection**: Automatic language detection and model selection
3. **Batch Processing**: Process multiple audio files efficiently
4. **Caching**: Cache transcription results for repeated audio files 