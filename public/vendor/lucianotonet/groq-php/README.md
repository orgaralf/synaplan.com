# Groq PHP

![Groq PHP](https://raw.githubusercontent.com/lucianotonet/groq-php/v0.0.9/art.png)

[![Version](https://img.shields.io/github/v/release/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)
[![Total Downloads](https://img.shields.io/packagist/dt/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)
[![License](https://img.shields.io/packagist/l/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)

**A powerful PHP library for seamless integration with the Groq API. This library simplifies interactions with Groq, allowing developers to effortlessly leverage its advanced language models, audio processing and vision capabilities.**

Using on Laravel? Check this out: [GroqLaravel](https://github.com/lucianotonet/groq-laravel?tab=readme-ov-file#readme)

## Installation

```bash
composer require lucianotonet/groq-php
```

## Configuration

**Obtain your API key from the [Groq Console](https://console.groq.com/keys) and set it as an environment variable:**

```bash
GROQ_API_KEY=your_key_here
```

## Usage

**Initialize the Groq client:**

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq();
```

You can also pass the API key directly to the constructor:

```php
$groq = new Groq('your_key_here');
```

## Configuration

You can set all configuration options via the constructor:

```php
$groq = new Groq([
    'apiKey' => 'your_key_here',
    'temperature' => 0.5,
    // ...
]);
```

Or using the `setConfig` method at any time:

```php
$groq = new Groq();

$groq->setConfig([
    'apiKey' => 'another_key_here',
    'max_tokens' => 1024,
    // ...
]);
```

## Listing Models

**Retrieve a list of available models:**

```php
$models = $groq->models()->list();

foreach ($models['data'] as $model) {
    echo 'Model ID: ' . $model['id'] . PHP_EOL;
    echo 'Developer: ' . $model['owned_by'] . PHP_EOL;
    echo 'Context Window: ' . $model['context_window'] . PHP_EOL;
}
```

## Chat Capabilities

### Basic Chat

**Send a chat completion request:**

```php
$response = $groq->chat()->completions()->create([
    'model' => 'llama3-8b-8192',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Explain the importance of low latency LLMs'
        ]
    ],
]);

echo $response['choices'][0]['message']['content']; // "Low latency LLMs are important because ..."
```

### Streaming Chat

**Stream a chat completion response:**

```php
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'user',
            'content' => $message
        ]
    ],
    'stream' => true
]);

foreach ($response->chunks() as $chunk) {
    if (isset($chunk['choices'][0]['delta']['content'])) {
        echo $chunk['choices'][0]['delta']['content'];
        ob_flush();
        flush();
    }
}
```

### Tool Calling

**Utilize tools in chat completions:**

```php
$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "calendar_tool",
            "description" => "Gets the current time in a specific format.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "format" => [
                        "type" => "string",
                        "description" => "The format of the time to return."
                    ],
                ],
                "required" => ["format"],
                "default" => ["format" => "d-m-Y"]
            ],
        ]
    ],
    [
        "type" => "function",
        "function" => [
            "name" => "weather_tool",
            "description" => "Gets the current weather conditions of a location.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "location" => [
                        "type" => "string",
                        "description" => "Location to get weather information."
                    ],
                ],
                "required" => ["location"],
                "default" => ["location" => "New York"]
            ],
        ]
    ],
    // Other tools...
];

// First inference...
 // Start of Selection
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => $messages,
    "tool_choice" => "auto",
    "tools" => $tools
]);

foreach ($response['choices'][0]['message']['tool_calls'] as $tool_call) {
    $function_args = json_decode($tool_call['function']['arguments'], true);
    
    // Call the tool...
    $function_name = $tool_call['function']['name'];
    if (function_exists($function_name)) {
        $function_response = $function_name($function_args);
    } else {
        $function_response = "Function $function_name not defined.";
    }

    $messages[] = [
        'tool_call_id' => $tool_call['id'],
        'role' => 'tool',
        'name' => $function_name,
        'content' => $function_response,
    ];
}

// Build final response...
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => $messages
]);


echo $response['choices'][0]['message']['content'];
```

### JSON Mode

**Request a JSON object as the response format:**

```php
use LucianoTonet\GroqPHP\GroqException;

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'system',
                'content' => "You are an API and shall respond only with valid JSON.",
            ],
            [
                'role' => 'user',
                'content' => 'Explain the importance of low latency LLMs',
            ],
        ],
        'response_format' => ['type' => 'json_object']
    ]);

    $jsonResponse = json_decode($response['choices'][0]['message']['content'], true);

    // Accessing the JSON response
    print_r($jsonResponse); 

} catch (GroqException $err) {
    echo $err->getCode() . "<br>"; 
    echo $err->getMessage() . "<br>";    
    echo $err->getType() . "<br>";           

    if($err->getFailedGeneration()) {
        print_r($err->getFailedGeneration());
    }
}
```

### Audio Transcription

**Transcribe audio content:**

```php
$transcription = $groq->audio()->transcriptions()->create([
    'file' => '/path/to/audio/file.mp3',
    'model' => 'whisper-large-v3',
    'response_format' => 'json',
    'language' => 'en',
    'prompt' => 'Optional transcription prompt'
]);

echo json_encode($transcription, JSON_PRETTY_PRINT);
```

### Audio Translation

**Translate audio content:**

```php
$translation = $groq->audio()->translations()->create([
    'file' => '/path/to/audio/file.mp3',
    'model' => 'whisper-large-v3',
    'response_format' => 'json',
    'prompt' => 'Optional translation prompt'
]);

echo json_encode($translation, JSON_PRETTY_PRINT);
```

### Vision Capabilities

**Analyze an image with a prompt:**

```php
$analysis = $groq->vision()->analyze('/path/to/your/image.jpg', 'Describe this image');

echo $analysis['choices'][0]['message']['content'];
```

## Error Handling

**Handle potential errors gracefully:**

```php
use LucianoTonet\GroqPHP\GroqException;

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello, world!'
            ]
        ]
    ]);
} catch (GroqException $err) {
    echo "<strong>Error code:</strong> " . $err->getCode() . "<br>"; // e.g., 400
    echo "<strong>Message:</strong> " . $err->getMessage() . "<br>";    // Detailed error description
    echo "<strong>Type:</strong> " . $err->getType() . "<br>";           // e.g., invalid_request_error
    echo "<strong>Headers:</strong><br>"; 
    print_r($err->getHeaders()); // ['server' => 'nginx', ...]
}
```

## Timeouts

### Global Timeout Configuration

**Set a global timeout for all requests (in milliseconds):**

```php
$groq = new Groq([
    'timeout' => 20 * 1000, // 20 seconds
]);
```

### Per-Request Timeout

**Specify a timeout for a specific request (in milliseconds):**

```php
$groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Hello, world!'
        ]
    ],
], ['timeout' => 5 * 1000]); // 5 seconds
```

## Semantic Versioning

This package follows [SemVer](https://semver.org/spec/v2.0.0.html) conventions. However, backward-incompatible changes might be released under minor versions in the following cases:

1. Changes that only affect static types and do not impact runtime behavior.
2. Modifications to internal library components that are technically public but not intended for external use. _(Please submit a GitHub issue if you rely on such internals)_.
3. Changes that are not expected to affect most users in practical scenarios.

## Requirements

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)
