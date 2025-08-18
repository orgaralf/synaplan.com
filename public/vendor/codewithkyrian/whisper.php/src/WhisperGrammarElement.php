<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use FFI;

class WhisperGrammarElement
{
    public function __construct(
        public readonly WhisperGrammarElementType $elementType,
        public readonly int $value
    ) {}

    /**
     * Create a new grammar element
     */
    public static function make(WhisperGrammarElementType $elementType, int $value): self
    {
        return new self($elementType, $value);
    }

    /**
     * Convert to C struct
     */
    public function toCStruct(FFI $ffi): FFI\CData
    {
        $element = $ffi->new('struct whisper_grammar_element');
        $element->type = $this->elementType->value;
        $element->value = $this->value;

        return $element;
    }

    /**
     * Create from C struct
     */
    public static function fromCStruct(FFI\CData $cElement): self
    {
        return new self(
            WhisperGrammarElementType::from($cElement->type),
            $cElement->value
        );
    }
}
