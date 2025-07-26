# Whisper.cpp Testing Guide

This guide explains how to test the whisper.cpp functionality in the Synaplan application.

## Prerequisites

1. **Docker Container**: Ensure the application container is running with whisper.cpp installed
2. **Models**: Verify that whisper models are downloaded to `/var/www/html/inc/whispermodels/`
3. **Test Audio**: Prepare an MP3 file for testing

## Quick Test

### 1. Access the Test Page

Navigate to the test page in your browser:
```
http://localhost/devtests/testwhisper.php
```

### 2. Check Model Availability

The test page will automatically check for available models and display:
- Model names and sizes
- Directory structure
- System compatibility

### 3. Test with Audio File

#### Option A: Use Existing Audio File
If you have an audio file in the uploads directory (`web/up/`), the test will automatically use it.

#### Option B: Create Test Audio File
Create a test audio file named `test-audio.mp3` in the `devtests/` directory:

```bash
# Option 1: Use ffmpeg to create a test audio file
ffmpeg -f lavfi -i "sine=frequency=1000:duration=5" -c:a mp3 devtests/test-audio.mp3

# Option 2: Use sox (if available)
sox -n -r 44100 -c 1 devtests/test-audio.mp3 synth 5 sine 1000

# Option 3: Copy an existing MP3 file
cp /path/to/your/audio.mp3 devtests/test-audio.mp3
```

#### Option C: Use Online Text-to-Speech
You can create a test audio file using online TTS services:
1. Go to https://text-to-speech.online/
2. Enter some test text (e.g., "Hello, this is a test of the whisper transcription system")
3. Download as MP3
4. Save as `devtests/test-audio.mp3`

## Expected Test Results

### Successful Test Output
```
📁 Available models in /var/www/html/inc/whispermodels:
  ✅ medium.bin (1536.00 MB)
  ✅ base.bin (142.00 MB)

🎵 Test audio file found: /var/www/html/devtests/test-audio.mp3

🔄 Loading medium model...
✅ Model loaded successfully
🔄 Reading audio file...
✅ Audio file read successfully
🔄 Transcribing audio (4 threads)...
✅ Transcription completed in 2.34s

📝 Transcription segments:
  [00:00:00.000 - 00:00:05.000]: Hello, this is a test of the whisper transcription system.

📄 Full transcription:
Hello, this is a test of the whisper transcription system.

📊 Statistics:
  - Audio duration: 1 segments
  - Processing time: 2.34s
  - Text length: 67 characters
```

### Common Issues and Solutions

#### 1. Models Not Found
```
❌ No models found. Please download models first.
```
**Solution**: Run the model download script:
```bash
docker exec -it your-container-name /var/www/html/dockerdev/manage-whisper-models.sh download recommended
```

#### 2. Permission Errors
```
❌ Error loading model: Permission denied
```
**Solution**: Check file permissions:
```bash
docker exec -it your-container-name chown -R www-data:www-data /var/www/html/inc/whispermodels
```

#### 3. Memory Issues
```
❌ Error during transcription: Out of memory
```
**Solution**: Use a smaller model or increase container memory:
```bash
# Use base model instead of medium
$whisper = Whisper::fromPretrained('base', baseDir: __DIR__.'/whispermodels');
```

#### 4. Audio Format Issues
```
❌ Error reading audio file: Unsupported format
```
**Solution**: Ensure audio file is in MP3 or WAV format:
```bash
# Convert to MP3 if needed
ffmpeg -i input.wav -c:a mp3 output.mp3
```

## Performance Testing

### Test Different Models
The test script will automatically test different available models:
- **base**: Fastest, good for development
- **medium**: Recommended for production
- **small**: Good balance (if available)

### Performance Benchmarks
Typical performance on different hardware:

| Model | CPU | Memory | Audio Length | Processing Time |
|-------|-----|--------|--------------|-----------------|
| base | 4 cores | 4GB | 1 minute | ~30 seconds |
| medium | 4 cores | 8GB | 1 minute | ~60 seconds |
| base | 8 cores | 8GB | 1 minute | ~20 seconds |
| medium | 8 cores | 16GB | 1 minute | ~40 seconds |

## Integration Testing

### Test in Application Context
1. Upload an MP3 file through the web interface
2. Check if transcription works in the message processing pipeline
3. Verify the transcribed text appears in the database

### Test Error Handling
1. Try uploading an invalid audio file
2. Test with very large audio files
3. Verify fallback to external services works

## Debugging

### Enable Debug Logging
Add debug output to the test script:
```php
// Add this to testwhisper.php for more detailed output
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Container Logs
```bash
# View container logs
docker logs your-container-name

# Check PHP error logs
docker exec -it your-container-name tail -f /var/log/apache2/error.log
```

### Verify Installation
```bash
# Check if whisper binary is available
docker exec -it your-container-name which whisper

# Check model files
docker exec -it your-container-name ls -la /var/www/html/inc/whispermodels/

# Test whisper directly
docker exec -it your-container-name whisper --help
```

## Next Steps

After successful testing:
1. **Production Deployment**: Ensure models are properly downloaded in production
2. **Monitoring**: Set up monitoring for transcription performance
3. **Optimization**: Adjust thread count and model selection based on usage patterns
4. **Backup**: Consider backing up the models directory for faster container rebuilds 