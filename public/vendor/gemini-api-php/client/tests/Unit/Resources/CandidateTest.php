<?php

declare(strict_types=1);

namespace GeminiAPI\Tests\Unit\Resources;

use GeminiAPI\Enums\FinishReason;
use GeminiAPI\Enums\HarmBlockThreshold;
use GeminiAPI\Enums\HarmCategory;
use GeminiAPI\Enums\HarmProbability;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Candidate;
use GeminiAPI\Resources\CitationMetadata;
use GeminiAPI\Resources\Content;
use GeminiAPI\Resources\SafetyRating;
use GeminiAPI\SafetySetting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CandidateTest extends TestCase
{
    public function testConstructor()
    {
        $candidate = new Candidate(
            new Content([], Role::User),
            FinishReason::OTHER,
            new CitationMetadata(),
            [],
            1,
            1,
        );
        self::assertInstanceOf(Candidate::class, $candidate);
    }

    public function testConstructorWithSafetyRatings()
    {
        $candidate = new Candidate(
            new Content([], Role::User),
            FinishReason::OTHER,
            new CitationMetadata(),
            [
                new SafetyRating(
                    HarmCategory::HARM_CATEGORY_MEDICAL,
                    HarmProbability::HIGH,
                    true,
                ),
                new SafetyRating(
                    HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
                    HarmProbability::LOW,
                    false,
                ),
            ],
            1,
            1,
        );
        self::assertInstanceOf(Candidate::class, $candidate);
    }

    public function testConstructorWithInvalidSafetyRatings()
    {
        $this->expectException(InvalidArgumentException::class);

        new Candidate(
            new Content([], Role::User),
            FinishReason::OTHER,
            new CitationMetadata(),
            [
                new SafetyRating(
                    HarmCategory::HARM_CATEGORY_MEDICAL,
                    HarmProbability::HIGH,
                    false,
                ),
                new SafetySetting(
                    HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
                    HarmBlockThreshold::BLOCK_LOW_AND_ABOVE,
                ),
            ],
            1,
            1,
        );
    }

    public function testFromArray()
    {
        $candidate = Candidate::fromArray([
            'content' => ['parts' => [], 'role' => 'user'],
            'safetyRatings' => [],
            'citationMetadata' => [],
            'index' => 1,
            'tokenCount' => 1,
            'finishReason' => 'OTHER',
        ]);

        self::assertInstanceOf(Candidate::class, $candidate);
    }

    public function testFromArrayWithoutContent()
    {
        $candidate = Candidate::fromArray([
            'safetyRatings' => [],
            'citationMetadata' => [],
            'index' => 1,
            'tokenCount' => 1,
            'finishReason' => 'OTHER',
        ]);

        self::assertInstanceOf(Candidate::class, $candidate);
    }

    public function testFromArrayWithoutFinishReason()
    {
        $candidate = Candidate::fromArray([
            'content' => ['parts' => [], 'role' => 'user'],
            'safetyRatings' => [],
            'citationMetadata' => [],
            'index' => 1,
            'tokenCount' => 1,
        ]);

        self::assertInstanceOf(Candidate::class, $candidate);
        self::assertEquals(FinishReason::OTHER, $candidate->finishReason);
    }
}
