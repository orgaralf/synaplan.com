Ollama PHP Library
============
This is a PHP library for Ollama. Ollama is an open-source project that serves as a powerful and user-friendly platform
for running LLMs on your local
machine. It acts as a bridge between the complexities of LLM technology and the desire for an accessible and
customizable AI experience.
<p align="left">
<a href="https://packagist.org/packages/ardagnsrn/ollama-php"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/ardagnsrn/ollama-php"></a>
<a href="https://packagist.org/packages/ardagnsrn/ollama-php"><img alt="Latest Version" src="https://img.shields.io/packagist/v/ardagnsrn/ollama-php"></a>
<a href="https://packagist.org/packages/ardagnsrn/ollama-php"><img alt="License" src="https://img.shields.io/github/license/ardagnsrn/ollama-php"></a>
</p>

## ☕️ Buy me a coffee

Whether you use this project, have learned something from it, or just like it, please consider supporting it by buying
me a coffee, so I can dedicate more time on open-source projects like this :)

<a href="https://www.buymeacoffee.com/ardagnsrn" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: auto !important;width: auto !important;" ></a>

## Table of Contents

- [Get Started](#get-started)
- [Usage](#usage)
    - [Completions Resource](#completions-resource)
        - [create()](#create)
        - [createStreamed()](#createstreamed)
    - [Chat Resource](#chat-resource)
        - [create()](#create-1)
        - [createStreamed()](#createstreamed-1)
    - [Models Resource](#models-resource)
        - [list()](#list)
        - [show()](#show)
        - [create()](#create-2)
        - [createStreamed()](#createstreamed-2)
        - [copy()](#copy)
        - [delete()](#delete)
        - [pull()](#pull)
        - [pullStreamed()](#pullstreamed)
        - [push()](#push)
        - [pushStreamed()](#pushstreamed)
        - [runningList()](#runninglist)
    - [Blobs Resource](#blobs-resource)
        - [exists()](#exists)
        - [create()](#create-3)
    - [Embed Resource](#embed-resource)
        - [create()](#create-4)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Get Started

> **Requires [PHP 8.1+](https://php.net/releases/)**\
> **Requires [Ollama](https://ollama.com/)**

> You can find Official Ollama documentation [here](https://github.com/ollama/ollama/blob/main/docs/api.md).

First, install Ollama PHP via the [Composer](https://getcomposer.org/) package manager:

```bash
composer require ardagnsrn/ollama-php
```

Then, you can create a new Ollama client instance:

```php
// with default base URL
$client = \ArdaGnsrn\Ollama\Ollama::client();

// or with custom base URL
$client = \ArdaGnsrn\Ollama\Ollama::client('http://localhost:11434');
```

## Usage

### `Completions` Resource

#### `create`

Generate a response for a given prompt with a provided model.

```php
$completions = $client->completions()->create([
    'model' => 'llama3.1',
    'prompt' => 'Once upon a time',
]);

$completions->response; // '...in a land far, far away...'

$response->toArray(); // ['model' => 'llama3.1', 'response' => '...in a land far, far away...', ...]
```

#### `createStreamed`

Generate a response for a given prompt with a provided model and stream the response.

```php
$completions = $client->completions()->createStreamed([
    'model' => 'llama3.1',
    'prompt' => 'Once upon a time',
]);


foreach ($completions as $completion) {
    echo $completion->response;
}
// 1. Iteration: '...in'
// 2. Iteration: ' a'
// 3. Iteration: ' land'
// 4. Iteration: ' far,'
// ...
```

### `Chat` Resource

#### `create`

Generate a response for a given prompt with a provided model.

```php
$response = $client->chat()->create([
    'model' => 'llama3.1',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a llama.'],
        ['role' => 'user', 'content' => 'Hello!'],
        ['role' => 'assistant', 'content' => 'Hi! How can I help you today?'],
        ['role' => 'user', 'content' => 'I need help with my taxes.'],
    ],
]);

$response->message->content; // 'Ah, taxes... *chew chew* Hmm, not really sure how to help with that.'

$response->toArray(); // ['model' => 'llama3.1', 'message' => ['role' => 'assistant', 'content' => 'Ah, taxes...'], ...]
```

Also, you can use the `tools` parameter to provide custom functions to the chat. **`tools` parameter can not be used
with `createStreamed` method.**

```php
$response = $client->chat()->create([
    'model' => 'llama3.1',
    'messages' => [
        ['role' => 'user', 'content' => 'What is the weather today in Paris?'],
    ],
    'tools' => [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_current_weather',
                'description' => 'Get the current weather',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The location to get the weather for, e.g. San Francisco, CA',
                        ],
                        'format' => [
                            'type' => 'string',
                            'description' => 'The location to get the weather for, e.g. San Francisco, CA',
                            'enum' => ['celsius', 'fahrenheit']
                        ],
                    ],
                    'required' => ['location', 'format'],
                ],
            ],
        ]
    ]
]);

$toolCall = $response->message->toolCalls[0];

$toolCall->function->name; // 'get_current_weather'
$toolCall->function->arguments; // ['location' => 'Paris', 'format' => 'celsius']

$response->toArray(); // ['model' => 'llama3.1', 'message' => ['role' => 'assistant', 'toolCalls' => [...]], ...]
```

#### `createStreamed`

Generate a response for a given prompt with a provided model and stream the response.

```php
$responses = $client->chat()->createStreamed([
    'model' => 'llama3.1',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a llama.'],
        ['role' => 'user', 'content' => 'Hello!'],
        ['role' => 'assistant', 'content' => 'Hi! How can I help you today?'],
        ['role' => 'user', 'content' => 'I need help with my taxes.'],
    ],
]);


foreach ($responses as $response) {
    echo $response->message->content;
}
// 1. Iteration: 'Ah,'
// 2. Iteration: ' taxes'
// 3. Iteration: '... '
// 4. Iteration: ' *chew,'
// ...
```

### `Models` Resource

#### `list`

List all available models.

```php
$response = $client->models()->list();

$response->toArray(); // ['models' => [['name' => 'llama3.1', ...], ['name' => 'llama3.1:80b', ...], ...]]
```

### `show`

Show details of a specific model.

```php
$response = $client->models()->show('llama3.1');

$response->toArray(); // ['modelfile' => '...', 'parameters' => '...', 'template' => '...']
```

### `create`

Create a new model.

```php
$response = $client->models()->create([
    'name' => 'mario',
    'modelfile' => "FROM llama3.1\nSYSTEM You are mario from Super Mario Bros."
]);

$response->status; // 'success'
```

### `createStreamed`

Create a new model and stream the response.

```php
$responses = $client->models()->createStreamed([
    'name' => 'mario',
    'modelfile' => "FROM llama3.1\nSYSTEM You are mario from Super Mario Bros."
]);

foreach ($responses as $response) {
    echo $response->status;
}
```

### `copy`

Copy an existing model.

```php
$client->models()->copy('llama3.1', 'llama3.2'); // bool
```

### `delete`

Delete a model.

```php
$client->models()->delete('mario'); // bool
```

### `pull`

Pull a model from the Ollama server.

```php
$response = $client->models()->pull('llama3.1'); 
$response->toArray() // ['status' => 'downloading digestname', 'digest' => 'digestname', 'total' => 2142590208, 'completed' => 241970]
```

### `pullStreamed`

Pull a model from the Ollama server and stream the response.

```php
$responses = $client->models()->pullStreamed('llama3.1'); 

foreach ($responses as $response) {
    echo $response->status; 
}
```

### `push`

Push a model to the Ollama server.

```php
$response = $client->models()->push('llama3.1');
$response->toArray() // ['status' => 'uploading digestname', 'digest' => 'digestname', 'total' => 2142590208]
```

### `pushStreamed`

Push a model to the Ollama server and stream the response.

```php
$responses = $client->models()->pushStreamed('llama3.1');

foreach ($responses as $response) {
    echo $response->status; 
}
```

### `runningList`

List all running models.

```php
$response = $client->models()->runningList();

$response->toArray(); // ['models' => [['name' => 'llama3.1', ...], ['name' => 'llama3.1:80b', ...], ...]]
```

### `Blobs` Resource

#### `exists`

Check if a blob exists.

```php
$client->blobs()->exists('blobname'); // bool
```

#### `create`

Create a new blob.

```php
$client->blobs()->create('blobname'); // bool
```

### `Embed` Resource

#### `create`

Generate an embedding for a given text with a provided model.

```php
$response = $client->embed()->create([
    'model' => 'llama3.1',
    'input' => [
        "Why is the sky blue?",
    ]
]);

$response->toArray(); // ['model' => 'llama3.1', 'embedding' => [0.1, 0.2, ...], ...]
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Credits

- [Arda GUNSUREN](https://github.com/ArdaGnsrn)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
