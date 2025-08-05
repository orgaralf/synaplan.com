<?php
/**
 * Test Anthropic Models API
 * 
 * Lists available Anthropic models using the /v1/models endpoint
 * 
 * @package TestAnthropic
 */

// Include necessary files
require_once __DIR__ . '/../web/inc/_confkeys.php';
require_once __DIR__ . '/../web/inc/_tools.php';

// Set debug mode
$GLOBALS["debug"] = true;

/**
 * Test function to list Anthropic models
 */
function testAnthropicModels() {
    // Get API key
    $apiKey = ApiKeys::getAnthropic();
    
    if (!$apiKey) {
        echo "Error: Anthropic API key not found in environment variables\n";
        echo "Please set ANTHROPIC_API_KEY in your environment or .env file\n";
        return false;
    }
    
    echo "Anthropic API Key found: " . substr($apiKey, 0, 10) . "...\n\n";
    
    // API endpoint
    $url = 'https://api.anthropic.com/v1/models';
    
    // Headers
    $headers = [
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
        'content-type: application/json'
    ];
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Synaplan-Anthropic-Test/1.0'
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        echo "cURL Error: " . $error . "\n";
        return false;
    }
    
    // Check HTTP response code
    if ($httpCode !== 200) {
        echo "HTTP Error: " . $httpCode . "\n";
        echo "Response: " . $response . "\n";
        return false;
    }
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Parse Error: " . json_last_error_msg() . "\n";
        echo "Raw Response: " . $response . "\n";
        return false;
    }
    
    // Display results
    echo "=== Anthropic Models List ===\n\n";
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Found " . count($data['data']) . " models:\n\n";
        
        foreach ($data['data'] as $model) {
            echo "Model ID: " . $model['id'] . "\n";
            echo "Display Name: " . $model['display_name'] . "\n";
            echo "Created: " . $model['created_at'] . "\n";
            echo "Type: " . $model['type'] . "\n";
            echo "---\n";
        }
        
        // Display pagination info
        if (isset($data['has_more'])) {
            echo "\nHas more models: " . ($data['has_more'] ? 'Yes' : 'No') . "\n";
        }
        
        if (isset($data['first_id'])) {
            echo "First ID: " . $data['first_id'] . "\n";
        }
        
        if (isset($data['last_id'])) {
            echo "Last ID: " . $data['last_id'] . "\n";
        }
        
    } else {
        echo "No models found in response\n";
        echo "Response structure: " . print_r($data, true) . "\n";
    }
    
    return true;
}

/**
 * Test function to get a specific model by ID
 */
function testGetSpecificModel($modelId) {
    $apiKey = ApiKeys::getAnthropic();
    
    if (!$apiKey) {
        echo "Error: Anthropic API key not found\n";
        return false;
    }
    
    $url = 'https://api.anthropic.com/v1/models/' . urlencode($modelId);
    
    $headers = [
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
        'content-type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "\n=== Specific Model Details ===\n";
        echo "Model ID: " . $data['id'] . "\n";
        echo "Display Name: " . $data['display_name'] . "\n";
        echo "Created: " . $data['created_at'] . "\n";
        echo "Type: " . $data['type'] . "\n";
        return true;
    } else {
        echo "Error getting model $modelId: HTTP $httpCode\n";
        return false;
    }
}

// Main execution
echo "Anthropic Models API Test\n";
echo "========================\n\n";

// Test listing all models
$success = testAnthropicModels();

if ($success) {
    echo "\nTest completed successfully!\n";
    
    // Optionally test a specific model (uncomment to test)
    // testGetSpecificModel('claude-sonnet-4-20250514');
} else {
    echo "\nTest failed!\n";
} 