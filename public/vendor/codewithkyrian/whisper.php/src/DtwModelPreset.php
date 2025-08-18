<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

enum DtwModelPreset
{
    case TinyEn;
    case Tiny;
    case BaseEn;
    case Base;
    case SmallEn;
    case Small;
    case MediumEn;
    case Medium;
    case LargeV1;
    case LargeV2;
    case LargeV3;

    public function toCEnum($ffi)
    {
        return match ($this) {
            self::TinyEn => $ffi->WHISPER_AHEADS_TINY_EN,
            self::Tiny => $ffi->WHISPER_AHEADS_TINY,
            self::BaseEn => $ffi->WHISPER_AHEADS_BASE_EN,
            self::Base => $ffi->WHISPER_AHEADS_BASE,
            self::SmallEn => $ffi->WHISPER_AHEADS_SMALL_EN,
            self::Small => $ffi->WHISPER_AHEADS_SMALL,
            self::MediumEn => $ffi->WHISPER_AHEADS_MEDIUM_EN,
            self::Medium => $ffi->WHISPER_AHEADS_MEDIUM,
            self::LargeV1 => $ffi->WHISPER_AHEADS_LARGE_V1,
            self::LargeV2 => $ffi->WHISPER_AHEADS_LARGE_V2,
            self::LargeV3 => $ffi->WHISPER_AHEADS_LARGE_V3,
        };
    }
}
