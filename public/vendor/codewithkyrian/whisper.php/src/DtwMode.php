<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

enum DtwMode
{
    /**
     * DTW token level timestamps disabled
     */
    case None;

    /**
     * Use N Top Most layers from loaded model
     */
    case TopMost;

    /**
     * Use custom aheads
     */
    case Custom;

    /**
     * Use predefined preset for standard models
     */
    case ModelPreset;
}
