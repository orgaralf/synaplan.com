<p align="center">
    <img src="https://raw.githubusercontent.com/gemini-api-php/client/main/assets/example.png" width="800" alt="Gemini API PHP Client - Example">
</p>
<p align="center">
    <a href="https://packagist.org/packages/gemini-api-php/client"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/gemini-api-php/client"></a>
    <a href="https://packagist.org/packages/gemini-api-php/client"><img alt="Latest Version" src="https://img.shields.io/packagist/v/gemini-api-php/client"></a>
    <a href="https://packagist.org/packages/gemini-api-php/client"><img alt="License" src="https://img.shields.io/github/license/gemini-api-php/client"></a>
</p>

# Gemini API PHP Client

Gemini API PHP Client allows you to use the Google's generative AI models, like Gemini Pro and Gemini Pro Vision.

_This library is not developed or endorsed by Google._

- Erdem Köse - **[github.com/erdemkose](https://github.com/erdemkose)**

## Table of Contents
- [Installation](#installation)
- [How to use](#how-to-use)
  - [Basic text generation](#basic-text-generation)
  - [Text generation with system instruction](#text-generation-with-system-instruction)
  - [Multimodal input](#multimodal-input)
  - [Chat Session (Multi-Turn Conversations)](#chat-session-multi-turn-conversations)
  - [Chat Session with history](#chat-session-with-history)
  - [Streaming responses](#streaming-responses)
  - [Streaming Chat Session](#streaming-chat-session)
  - [Tokens counting](#tokens-counting)
  - [Listing models](#listing-models)
  - [Advanced Usages](#advanced-usages)
    - [Using Beta version](#using-beta-version)
    - [Safety Settings and Generation Configuration](#safety-settings-and-generation-configuration)
    - [Using your own HTTP client](#using-your-own-http-client)
    - [Using your own HTTP client for streaming responses](#using-your-own-http-client-for-streaming-responses)

## Installation

> You need an API key to gain access to Google's Gemini API.
> Visit [Google AI Studio](https://makersuite.google.com/) to get an API key.

First step is to install the Gemini API PHP client with Composer.

```shell
composer require gemini-api-php/client
```

Gemini API PHP client does not come with an HTTP client.
If you are just testing or do not have an HTTP client library in your project,
you need to allow `php-http/discovery` composer plugin or install a PSR-18 compatible client library.

## How to use

### Basic text generation

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$response = $client->generativeModel(ModelName::GEMINI_PRO)->generateContent(
    new TextPart('PHP in less than 100 chars'),
);

print $response->text();
// PHP: A server-side scripting language used to create dynamic web applications.
// Easy to learn, widely used, and open-source.
```

### Text generation with system instruction

> System instruction is currently supported only in beta version

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$response = $client->withV1BetaVersion()
    ->generativeModel(ModelName::GEMINI_1_5_FLASH)
    ->withSystemInstruction('You are a cat. Your name is Neko.')
    ->generateContent(
        new TextPart('PHP in less than 100 chars'),
    );

print $response->text();
// Meow?  <?php echo 'Hello, world!'; ?>  Purrfectly concise, wouldn't you say?  *Stretches luxuriously*
```

### Multimodal input

> Image input modality is only enabled for Gemini Pro Vision model

```php
use GeminiAPI\Client;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$response = $client->generativeModel(ModelName::GEMINI_PRO)->generateContent(
    new TextPart('Explain what is in the image'),
    new ImagePart(
        MimeType::IMAGE_JPEG,
        base64_encode(file_get_contents('elephpant.jpg')),
    ),
);

print $response->text();
// The image shows an elephant standing on the Earth.
// The elephant is made of metal and has a glowing symbol on its forehead.
// The Earth is surrounded by a network of glowing lines.
// The image is set against a starry background.
```

### Chat Session (Multi-Turn Conversations)

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$chat = $client->generativeModel(ModelName::GEMINI_PRO)->startChat();

$response = $chat->sendMessage(new TextPart('Hello World in PHP'));
print $response->text();

$response = $chat->sendMessage(new TextPart('in Go'));
print $response->text();
```

```text
<?php
echo "Hello World!";
?>

This code will print "Hello World!" to the standard output.
```

```text
package main

import "fmt"

func main() {
    fmt.Println("Hello World!")
}

This code will print "Hello World!" to the standard output.
```

### Chat Session with history

```php
use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$history = [
    Content::text('Hello World in PHP', Role::User),
    Content::text(
        <<<TEXT
        <?php
        echo "Hello World!";
        ?>
        
        This code will print "Hello World!" to the standard output.
        TEXT,
        Role::Model,
    ),
];

$client = new Client('GEMINI_API_KEY');
$chat = $client->generativeModel(ModelName::GEMINI_PRO)
    ->startChat()
    ->withHistory($history);

$response = $chat->sendMessage(new TextPart('in Go'));
print $response->text();
```

```text
package main

import "fmt"

func main() {
    fmt.Println("Hello World!")
}

This code will print "Hello World!" to the standard output.
```

### Streaming responses

> Requires `curl` extension to be enabled

In the streaming response, the callback function will be called whenever a response is returned from the server.

Long responses may be broken into separate responses, and you can start receiving responses faster using a content stream.

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Responses\GenerateContentResponse;

$callback = function (GenerateContentResponse $response): void {
    static $count = 0;

    print "\nResponse #{$count}\n";
    print $response->text();
    $count++;
};

$client = new Client('GEMINI_API_KEY');
$client->generativeModel(ModelName::GEMINI_PRO)->generateContentStream(
    $callback,
    [new TextPart('PHP in less than 100 chars')],
);
// Response #0
// PHP: a versatile, general-purpose scripting language for web development, popular for
// Response #1
//  its simple syntax and rich library of functions.
```

### Streaming Chat Session

> Requires `curl` extension to be enabled 

```php
use GeminiAPI\Client;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Responses\GenerateContentResponse;

$history = [
    Content::text('Hello World in PHP', Role::User),
    Content::text(
        <<<TEXT
        <?php
        echo "Hello World!";
        ?>
        
        This code will print "Hello World!" to the standard output.
        TEXT,
        Role::Model,
    ),
];

$callback = function (GenerateContentResponse $response): void {
    static $count = 0;

    print "\nResponse #{$count}\n";
    print $response->text();
    $count++;
};

$client = new Client('GEMINI_API_KEY');
$chat = $client->generativeModel(ModelName::GEMINI_PRO)
    ->startChat()
    ->withHistory($history);

$chat->sendMessageStream($callback, new TextPart('in Go'));
```

```text
Response #0
package main

import "fmt"

func main() {

Response #1
    fmt.Println("Hello World!")
}

This code will print "Hello World!" to the standard output.
```

### Embed Content

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$response = $client->embeddingModel(ModelName::EMBEDDING_001)
    ->embedContent(
        new TextPart('PHP in less than 100 chars'),
    );

print_r($response->embedding->values);
// [
//    [0] => 0.041395925
//    [1] => -0.017692696
//    ...
// ]
```

### Tokens counting

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = new Client('GEMINI_API_KEY');
$response = $client->generativeModel(ModelName::GEMINI_PRO)->countTokens(
    new TextPart('PHP in less than 100 chars'),
);

print $response->totalTokens;
// 10
```

### Listing models

```php
use GeminiAPI\Client;

$client = new Client('GEMINI_API_KEY');
$response = $client->listModels();

print_r($response->models);
//[
//  [0] => GeminiAPI\Resources\Model Object
//    (
//      [name] => models/gemini-pro
//      [displayName] => Gemini Pro
//      [description] => The best model for scaling across a wide range of tasks
//      ...
//    )
//  [1] => GeminiAPI\Resources\Model Object
//    (
//      [name] => models/gemini-pro-vision
//      [displayName] => Gemini Pro Vision
//      [description] => The best image understanding model to handle a broad range of applications
//      ...
//    )
//]
```

### Advanced Usages

#### Using Beta version

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

$client = (new Client('GEMINI_API_KEY'))
    ->withV1BetaVersion();
$response = $client->generativeModel(ModelName::GEMINI_PRO)->countTokens(
    new TextPart('PHP in less than 100 chars'),
);

print $response->totalTokens;
// 10
```

#### Safety Settings and Generation Configuration

```php
use GeminiAPI\Client;
use GeminiAPI\Enums\HarmCategory;
use GeminiAPI\Enums\HarmBlockThreshold;
use GeminiAPI\GenerationConfig;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\SafetySetting;

$safetySetting = new SafetySetting(
    HarmCategory::HARM_CATEGORY_HATE_SPEECH,
    HarmBlockThreshold::BLOCK_LOW_AND_ABOVE,
);
$generationConfig = (new GenerationConfig())
    ->withCandidateCount(1)
    ->withMaxOutputTokens(40)
    ->withTemperature(0.5)
    ->withTopK(40)
    ->withTopP(0.6)
    ->withStopSequences(['STOP']);

$client = new Client('GEMINI_API_KEY');
$response = $client->generativeModel(ModelName::GEMINI_PRO)
    ->withAddedSafetySetting($safetySetting)
    ->withGenerationConfig($generationConfig)
    ->generateContent(
        new TextPart('PHP in less than 100 chars')
    );
```

#### Using your own HTTP client

```php
use GeminiAPI\Client as GeminiClient;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GuzzleHttp\Client as GuzzleClient;

$guzzle = new GuzzleClient([
  'proxy' => 'http://localhost:8125',
]);

$client = new GeminiClient('GEMINI_API_KEY', $guzzle);
$response = $client->generativeModel(ModelName::GEMINI_PRO)->generateContent(
    new TextPart('PHP in less than 100 chars')
);
```

#### Using your own HTTP client for streaming responses

> Requires `curl` extension to be enabled

Since streaming responses are fetched using `curl` extension, they cannot use the custom HTTP client passed to the Gemini Client.
You need to pass a `CurlHandler` if you want to override connection options.

The following curl options will be overwritten by the Gemini Client.

- `CURLOPT_URL`
- `CURLOPT_POST`
- `CURLOPT_POSTFIELDS`
- `CURLOPT_WRITEFUNCTION`

You can also pass the headers you want to be used in the requests.

```php
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Responses\GenerateContentResponse;

$callback = function (GenerateContentResponse $response): void {
    print $response->text();
};

$ch = curl_init();
curl_setopt($ch, \CURLOPT_PROXY, 'http://localhost:8125');

$client = new Client('GEMINI_API_KEY');
$client->withRequestHeaders([
        'User-Agent' => 'My Gemini-backed app'
    ])
    ->generativeModel(ModelName::GEMINI_PRO)
    ->generateContentStream(
        $callback,
        [new TextPart('PHP in less than 100 chars')],
        $ch,
    );
```
