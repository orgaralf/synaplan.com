<?php
/**
 * Test file for whisper.cpp functionality
 * Tests local audio transcription using the whisper.php wrapper
 */

require_once __DIR__ . '/../web/vendor/autoload.php';

use Codewithkyrian\Whisper\Whisper;
use function Codewithkyrian\Whisper\readAudio;
use function Codewithkyrian\Whisper\toTimestamp;

// Test configuration
$modelsDir = __DIR__ . '/../web/inc/whispermodels';
$testAudioFile = __DIR__ . '/test-audio.mp3'; // You'll need to provide a test audio file

echo "<h1>Whisper.cpp Test</h1>\n";
echo "<pre>\n";

// Check if models directory exists
if (!is_dir($modelsDir)) {
    echo "❌ Models directory not found: $modelsDir\n";
    echo "Please ensure the models are downloaded to: $modelsDir\n";
    exit(1);
}

// List available models
echo "📁 Available models in $modelsDir:\n";
$models = glob($modelsDir . '/*.bin');
if (empty($models)) {
    echo "❌ No models found. Please download models first.\n";
    echo "Available models from https://huggingface.co/ggerganov/whisper.cpp:\n";
    echo "- medium.bin (1.5 GiB) - Recommended for good accuracy\n";
    echo "- base.bin (142 MiB) - Faster, smaller\n";
    echo "- small.bin (466 MiB) - Good balance\n";
    exit(1);
}

foreach ($models as $model) {
    $size = filesize($model);
    $sizeMB = round($size / 1024 / 1024, 2);
    echo "  ✅ " . basename($model) . " ($sizeMB MB)\n";
}

// Check if test audio file exists
if (!file_exists($testAudioFile)) {
    echo "\n⚠️  Test audio file not found: $testAudioFile\n";
    echo "Please provide an MP3 file for testing.\n";
    echo "You can create a test file or use an existing audio file.\n";
    
    // Create a simple test with just model loading
    echo "\n🧪 Testing model loading only:\n";
    try {
        $whisper = Whisper::fromPretrained('medium', baseDir: $modelsDir);
        echo "✅ Successfully loaded medium model\n";
        
        // Test with a small audio file if available
        $smallTestFile = __DIR__ . '/../web/up/test.mp3';
        if (file_exists($smallTestFile)) {
            echo "\n🎵 Testing with existing audio file: $smallTestFile\n";
            $audio = readAudio($smallTestFile);
            $segments = $whisper->transcribe($audio, 4);
            
            echo "📝 Transcription result:\n";
            $fullText = "";
            foreach ($segments as $segment) {
                $start = toTimestamp($segment->start);
                $end = toTimestamp($segment->end);
                echo "  [$start - $end]: " . trim($segment->text) . "\n";
                if (strlen($fullText) > 2) $fullText .= " ";
                $fullText .= $segment->text;
            }
            echo "\n📄 Full text: " . trim($fullText) . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error loading model: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n🎵 Test audio file found: $testAudioFile\n";
    
    try {
        // Load the medium model
        echo "🔄 Loading medium model...\n";
        $whisper = Whisper::fromPretrained('medium', baseDir: $modelsDir);
        echo "✅ Model loaded successfully\n";
        
        // Read audio file
        echo "🔄 Reading audio file...\n";
        $audio = readAudio($testAudioFile);
        echo "✅ Audio file read successfully\n";
        
        // Transcribe with 4 threads
        echo "🔄 Transcribing audio (4 threads)...\n";
        $startTime = microtime(true);
        $segments = $whisper->transcribe($audio, 4);
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "✅ Transcription completed in {$duration}s\n\n";
        
        // Display results
        echo "📝 Transcription segments:\n";
        $fullText = "";
        foreach ($segments as $segment) {
            $start = toTimestamp($segment->start);
            $end = toTimestamp($segment->end);
            echo "  [$start - $end]: " . trim($segment->text) . "\n";
            if (strlen($fullText) > 2) $fullText .= " ";
            $fullText .= $segment->text;
        }
        
        echo "\n📄 Full transcription:\n";
        echo trim($fullText) . "\n";
        
        echo "\n📊 Statistics:\n";
        echo "  - Audio duration: " . count($segments) . " segments\n";
        echo "  - Processing time: {$duration}s\n";
        echo "  - Text length: " . strlen(trim($fullText)) . " characters\n";
        
    } catch (Exception $e) {
        echo "❌ Error during transcription: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

// Test different models
echo "\n🧪 Testing different models:\n";
$testModels = ['base', 'medium'];
foreach ($testModels as $modelName) {
    $modelFile = $modelsDir . '/' . $modelName . '.bin';
    if (file_exists($modelFile)) {
        echo "  Testing $modelName model...\n";
        try {
            $whisper = Whisper::fromPretrained($modelName, baseDir: $modelsDir);
            echo "    ✅ $modelName model loaded successfully\n";
        } catch (Exception $e) {
            echo "    ❌ Error loading $modelName model: " . $e->getMessage() . "\n";
        }
    } else {
        echo "    ⚠️  $modelName model not found\n";
    }
}

echo "\n🎯 Test completed!\n";
echo "</pre>\n";

// Add some helpful information
echo "<h2>Usage in your application:</h2>\n";
echo "<pre>\n";
echo "// In your PHP code:\n";
echo "use Codewithkyrian\\Whisper\\Whisper;\n";
echo "use function Codewithkyrian\\Whisper\\readAudio;\n\n";
echo "\$whisper = Whisper::fromPretrained('medium', baseDir: __DIR__.'/inc/whispermodels');\n";
echo "\$audio = readAudio('./path/to/audio.mp3');\n";
echo "\$segments = \$whisper->transcribe(\$audio, 4);\n";
echo "\$fullText = '';\n";
echo "foreach (\$segments as \$segment) {\n";
echo "    if(strlen(\$fullText)>2) \$fullText .= ' ';\n";
echo "    \$fullText .= \$segment->text;\n";
echo "}\n";
echo "echo trim(\$fullText);\n";
echo "</pre>\n";
?> 