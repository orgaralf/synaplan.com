<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

enum WhisperGrammarElementType: int
{
    /**
     * End of rule definition
     */
    case End = 0;

    /**
     * Start of alternate definition for a rule
     */
    case Alternate = 1;

    /**
     * Non-terminal element: reference to another rule
     */
    case RuleReference = 2;

    /**
     * Terminal element: character (code point)
     */
    case Character = 3;

    /**
     * Inverse of a character(s)
     */
    case NotCharacter = 4;

    /**
     * Modifies a preceding Character to be an inclusive range
     */
    case CharacterRangeUpper = 5;

    /**
     * Modifies a preceding Character to add an alternate character to match
     */
    case CharacterAlternate = 6;
}
