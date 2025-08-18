<?php

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use PHPUnit\Framework\TestCase;

class GroqTest extends TestCase
{
    private Groq $groq;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
        $dotenv->load();
        $this->groq = new Groq($_ENV['GROQ_API_KEY']);
    }

    public function testInvalidApiKey()
    {
        $groq = new Groq('invalid_api_key');

        $this->expectException(GroqException::class);
        $this->expectExceptionCode(0); // O código de erro será 0 para chaves de API inválidas
        $this->expectExceptionMessage('Invalid API Key'); // A mensagem de erro será 'Invalid API Key'

        $groq->chat()->completions()->create([
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);
    }

    public function testListModels()
    {
        $models = $this->groq->models()->list();        

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models); // Verifica se a chave 'data' está presente
    }

    public function testChatCompletionWithValidApiKey()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    // public function testAudioTranscription()
    // {
    //     $audioFile = __DIR__ . '/test_audio.mp3';
    //     $response = $this->groq->audio()->transcriptions()->create([
    //         'file' => $audioFile,
    //         'model' => 'whisper-large-v3',
    //         'response_format' => 'json',
    //     ]);

    //     $this->assertArrayHasKey('text', $response);
    //     $this->assertNotEmpty($response['text']);
    // }

    // public function testAudioTranslation()
    // {
    //     $audioFile = __DIR__ . '/test_audio.mp3';
    //     $response = $this->groq->audio()->translations()->create([
    //         'file' => $audioFile,
    //         'model' => 'whisper-large-v3',
    //         'response_format' => 'json',
    //     ]);

    //     $this->assertArrayHasKey('text', $response);
    //     $this->assertNotEmpty($response['text']);
    // }

    public function testSetOptions()
    {
        // Setup
        $initialApiKey = $_ENV['GROQ_API_KEY'];
        $groq = new Groq($initialApiKey);
        
        // Test setting new options
        $newOptions = [
            'apiKey' => 'new_test_key',
            'baseUrl' => 'https://test-api.groq.com/v1',
            'timeout' => 30000,
            'maxRetries' => 3,
            'headers' => ['X-Custom-Header' => 'test'],
            'proxy' => 'http://proxy.test',
            'verify' => false,
            'debug' => true,
            'stream' => true,
            'responseFormat' => 'json'
        ];
        
        $groq->setOptions($newOptions);
        
        // Verify API key was updated
        $this->assertEquals('new_test_key', $groq->apiKey());        
        
        // Get actual options
        $actualOptions = $groq->options;
        
        // Verify all options were set correctly
        $this->assertEquals($newOptions['baseUrl'], $groq->baseUrl);
        $this->assertEquals($newOptions['timeout'], $actualOptions['timeout']);
        $this->assertEquals($newOptions['maxRetries'], $actualOptions['maxRetries']);
        $this->assertEquals($newOptions['headers'], $actualOptions['headers']);
        $this->assertEquals($newOptions['proxy'], $actualOptions['proxy']);
        $this->assertEquals($newOptions['verify'], $actualOptions['verify']);
        $this->assertEquals($newOptions['debug'], $actualOptions['debug']);
        $this->assertEquals($newOptions['stream'], $actualOptions['stream']);
        $this->assertEquals($newOptions['responseFormat'], $actualOptions['responseFormat']);
    }

    public function testSetOptionsPartial()
    {
        // Setup
        $initialApiKey = $_ENV['GROQ_API_KEY'];
        $groq = new Groq($initialApiKey, ['timeout' => 10000]);
        
        // Test setting only some options
        $newOptions = [
            'timeout' => 20000,
            'debug' => true
        ];
        
        $groq->setOptions($newOptions);
        
        // Create a reflection class to access private properties
        $reflection = new \ReflectionClass($groq);
        $optionsProperty = $reflection->getProperty('options');
        $optionsProperty->setAccessible(true);
        
        // Get actual options
        $actualOptions = $optionsProperty->getValue($groq);
        
        // Verify specific options were updated
        $this->assertEquals(20000, $actualOptions['timeout']);
        $this->assertEquals(true, $actualOptions['debug']);
        
        // Verify API key remained unchanged
        $this->assertEquals($initialApiKey, $groq->apiKey());
    }
}
