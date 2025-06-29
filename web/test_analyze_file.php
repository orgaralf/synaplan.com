<?php
set_time_limit(0);

// Include necessary files
require_once(__DIR__ . '/inc/_confsys.php');
require_once(__DIR__ . '/inc/_confkeys.php');
require_once(__DIR__ . '/inc/_confdb.php');
require_once(__DIR__ . '/inc/_central.php');
require_once(__DIR__ . '/inc/_curler.php');
require_once(__DIR__ . '/inc/_frontend.php');
require_once(__DIR__ . '/inc/_aigoogle.php');

echo "Testing AIGoogle::analyzeFile() method with inline data approach...\n";

// Initialize AIGoogle
if (!AIGoogle::init()) {
    echo "Error: Could not initialize AIGoogle - check API key configuration\n";
    exit(1);
}

// Create a test text file
$testContent = "This is a test document for file analysis.

Key Points:
- This document contains sample text for testing
- It includes multiple paragraphs and formatting
- The purpose is to verify the analyzeFile method works correctly
- It should be processed by Google Gemini API using inline data

Main Topics:
1. File analysis testing
2. Google Gemini integration
3. Document processing capabilities
4. Inline data processing approach

Important Details:
- The file should be read and encoded as base64
- Content should be analyzed and summarized
- Results should be returned in the specified language
- This approach avoids complex file uploads

This concludes the test document content.";

$testFilePath = __DIR__ . '/up/test_document.txt';
file_put_contents($testFilePath, $testContent);

echo "Created test file: $testFilePath\n";

// Prepare test message array
$testMsgArr = [
    'BID' => 'test_' . uniqid(),
    'BUSERID' => 1,
    'BFILEPATH' => 'test_document.txt',
    'BFILETYPE' => 'txt',
    'BLANG' => 'en',
    'BTEXT' => 'Please analyze this test document'
];

echo "Testing file analysis with inline data approach...\n";

try {
    // Call the analyzeFile method
    $result = AIGoogle::analyzeFile($testMsgArr, true);
    
    echo "\n=== ANALYSIS RESULT ===\n";
    echo "Success: " . (isset($result['BFILETEXT']) ? 'Yes' : 'No') . "\n";
    
    if (isset($result['BFILETEXT'])) {
        echo "Analysis Text:\n";
        echo $result['BFILETEXT'] . "\n";
    }
    
    if (isset($result['BTEXT'])) {
        echo "Status Message: " . $result['BTEXT'] . "\n";
    }
    
    if (isset($result['BFILEPATH'])) {
        echo "File Path: " . $result['BFILEPATH'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error during analysis: " . $e->getMessage() . "\n";
}

// Clean up test file
if (file_exists($testFilePath)) {
    unlink($testFilePath);
    echo "Cleaned up test file\n";
}

echo "\nTest completed.\n"; 