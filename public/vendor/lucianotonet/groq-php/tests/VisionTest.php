<?php

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use PHPUnit\Framework\TestCase;

class VisionTest extends TestCase
{
    private Groq $groq;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
        $dotenv->load();
        $this->groq = new Groq($_ENV['GROQ_API_KEY']);
    }

    public function testVisionAnalysisWithLocalImage()
    {
        $imagePath = __DIR__ . '/images/australian_shepherd_puppies.png';
        $prompt = "What do you see in this image?";

        try {
            $response = $this->groq->vision()->analyze($imagePath, $prompt);
        } catch (GroqException $e) {
            $this->fail("Error analyzing local image: " . $e->getMessage() . " - Code: " . $e->getCode() . " - File: " . $e->getFile());
        } catch (ArgumentCountError $e) {
            $this->fail("Argument count error: " . $e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    public function testVisionAnalysisWithUrlImage()
    {
        $imageUrl = "https://raw.githubusercontent.com/groq/groq-api-cookbook/d4f9b68e85989e107e2c50caae9d4ad86a46f375/tutorials/multimodal-image-processing/images/australian_shepherd_puppies.png";
        $prompt = "What do you see in this image?";

        try {
            $response = $this->groq->vision()->analyze($imageUrl, $prompt);
        } catch (GroqException $e) {
            $this->fail("Error analyzing URL image: " . $e->getMessage() . " - Code: " . $e->getCode() . " - File: " . $e->getFile());
        } catch (ArgumentCountError $e) {
            $this->fail("Argument count error: " . $e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }
}