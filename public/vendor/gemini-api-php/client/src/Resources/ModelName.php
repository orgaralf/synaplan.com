<?php

declare(strict_types=1);

namespace GeminiAPI\Resources;

class ModelName
{
    // Optimized for: Natural language tasks, multi-turn text and code chat, and code generation
    public const GEMINI_PRO = 'gemini-pro';
    public const GEMINI_1_0_PRO = 'gemini-1.0-pro';
    public const GEMINI_1_0_PRO_001 = 'gemini-1.0-pro-001';
    public const GEMINI_1_0_PRO_LATEST = 'gemini-1.0-pro-latest';

    // Optimized for: Complex reasoning tasks requiring more intelligence
    public const GEMINI_1_5_PRO = 'gemini-1.5-pro';
    public const GEMINI_1_5_PRO_001 = 'gemini-1.5-pro-001';
    public const GEMINI_1_5_PRO_002 = 'gemini-1.5-pro-002';
    public const GEMINI_1_5_PRO_LATEST = 'gemini-1.5-pro-latest';

    // Optimized for: Fast and versatile performance across a diverse variety of tasks
    public const GEMINI_1_5_FLASH = 'gemini-1.5-flash';
    public const GEMINI_1_5_FLASH_001 = 'gemini-1.5-flash-001';
    public const GEMINI_1_5_FLASH_002 = 'gemini-1.5-flash-002';
    public const GEMINI_1_5_FLASH_LATEST = 'gemini-1.5-flash-latest';

    // Optimized for: High volume and lower intelligence tasks
    public const GEMINI_1_5_FLASH_8B = 'gemini-1.5-flash-8b';
    public const GEMINI_1_5_FLASH_8B_001 = 'gemini-1.5-flash-8b-001';
    public const GEMINI_1_5_FLASH_8B_LATEST = 'gemini-1.5-flash-8b-latest';

    // Optimized for: Measuring the relatedness of text strings
    public const TEXT_EMBEDDING_004 = 'text-embedding-004';
    public const EMBEDDING_001 = 'embedding-001';

    // Optimized for: Providing source-grounded answers to questions
    public const AQA = 'aqa';
}
