<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use FFI;
use FFI\CData;

class WhisperFullParams
{
    protected SamplingStrategy $strategy;

    protected int $nThreads = 4;

    public int $nMaxTextCtx = 16384;

    public int $offsetMs = 0;

    public int $durationMs = 0;

    public bool $translate = false;

    public bool $noContext = false;

    public bool $noTimestamps = false;

    public bool $singleSegment = false;

    public bool $printSpecial = false;

    public bool $printProgress = false;

    public bool $printRealtime = false;

    public bool $printTimestamps = false;

    public bool $tokenTimestamps = false;

    public float $tholdPt = 0.01;

    public float $tholdPtsum = 0.01;

    public int $maxLen = 0;

    public bool $splitOnWord = false;

    public int $maxTokens = 0;

    public bool $debug = false;

    public int $audioCtx = 0;

    public bool $tdrzEnable = false;

    public bool $detectLanguage = false;

    public bool $suppressBlank = false;

    public bool $suppressNonSpeechTokens = false;

    public float $temperature = 0.0;

    public float $maxInitialTs = 1.0;

    public float $lengthPenalty = -1.0;

    public float $temperatureInc = 0.2;

    public float $entropyThold = 2.4;

    public float $logprobThold = -1.0;

    public float $noSpeechThold = 0.6;

    public float $grammarPenalty = 100.0;

    private mixed $progressCallback = null;

    private mixed $segmentCallback = null;

    /**
     * @var WhisperGrammarElement[]|null
     */
    private ?array $grammar = null;

    private ?string $language = null;

    private ?string $initialPrompt = null;

    private ?array $tokens = null;

    public function __construct(SamplingStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public static function default(?SamplingStrategy $strategy = null): static
    {
        $strategy = $strategy ?? SamplingStrategy::greedy();

        return new static($strategy);
    }

    public function withNThreads(int $nThreads): static
    {
        $this->nThreads = $nThreads;

        return $this;
    }

    public function withNMaxTextCtx(int $nMaxTextCtx): static
    {
        $this->nMaxTextCtx = $nMaxTextCtx;

        return $this;
    }

    public function withOffsetMs(int $offsetMs): static
    {
        $this->offsetMs = $offsetMs;

        return $this;
    }

    public function withDurationMs(int $durationMs): static
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function withTranslate(bool $translate = true): static
    {
        $this->translate = $translate;

        return $this;
    }

    public function withNoContext(bool $noContext = true): static
    {
        $this->noContext = $noContext;

        return $this;
    }

    public function withNoTimestamps(bool $noTimestamps = true): static
    {
        $this->noTimestamps = $noTimestamps;

        return $this;
    }

    public function withSingleSegment(bool $singleSegment = true): static
    {
        $this->singleSegment = $singleSegment;

        return $this;
    }

    public function withPrintSpecial(bool $printSpecial = true): static
    {
        $this->printSpecial = $printSpecial;

        return $this;
    }

    public function withPrintProgress(bool $printProgress = true): static
    {
        $this->printProgress = $printProgress;

        return $this;
    }

    public function withPrintRealtime(bool $printRealtime = true): static
    {
        $this->printRealtime = $printRealtime;

        return $this;
    }

    public function withPrintTimestamps(bool $printTimestamps = true): static
    {
        $this->printTimestamps = $printTimestamps;

        return $this;
    }

    public function withTokenTimestamps(bool $tokenTimestamps = true): static
    {
        $this->tokenTimestamps = $tokenTimestamps;

        return $this;
    }

    public function withTholdPt(float $tholdPt): static
    {
        $this->tholdPt = $tholdPt;

        return $this;
    }

    public function withTholdPtsum(float $tholdPtsum): static
    {
        $this->tholdPtsum = $tholdPtsum;

        return $this;
    }

    public function withMaxLen(int $maxLen): static
    {
        $this->maxLen = $maxLen;

        return $this;
    }

    public function withSplitOnWord(bool $splitOnWord): static
    {
        $this->splitOnWord = $splitOnWord;

        return $this;
    }

    public function withMaxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function withTemperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function withLengthPenalty(float $lengthPenalty): static
    {
        $this->lengthPenalty = $lengthPenalty;

        return $this;
    }

    public function withTemperatureInc(float $temperatureInc): static
    {
        $this->temperatureInc = $temperatureInc;

        return $this;
    }

    public function withLogitsInc(float $logitsInc): static
    {
        $this->logitsInc = $logitsInc;

        return $this;
    }

    public function withEntropyThold(float $entropyThold): static
    {
        $this->entropyThold = $entropyThold;

        return $this;
    }

    public function withLogprobThold(float $logprobThold): static
    {
        $this->logprobThold = $logprobThold;

        return $this;
    }

    public function withNoSpeechThold(float $noSpeechThold): static
    {
        $this->noSpeechThold = $noSpeechThold;

        return $this;
    }

    public function toCStruct($ffi): ?CData
    {
        $params = $ffi->new('struct whisper_full_params');

        $params->strategy = $this->strategy->type;

        // Set basic parameters
        $params->n_threads = $this->nThreads;
        $params->n_max_text_ctx = $this->nMaxTextCtx;
        $params->offset_ms = $this->offsetMs;
        $params->duration_ms = $this->durationMs;

        // Set boolean flags
        $params->translate = $this->translate;
        $params->no_context = $this->noContext;
        $params->no_timestamps = $this->noTimestamps;
        $params->single_segment = $this->singleSegment;
        $params->print_special = $this->printSpecial;
        $params->print_progress = $this->printProgress;
        $params->print_realtime = $this->printRealtime;
        $params->print_timestamps = $this->printTimestamps;
        $params->token_timestamps = $this->tokenTimestamps;
        $params->split_on_word = $this->splitOnWord;
        $params->debug_mode = $this->debug;
        $params->tdrz_enable = $this->tdrzEnable;
        $params->detect_language = $this->detectLanguage;
        $params->suppress_blank = $this->suppressBlank;
        $params->suppress_non_speech_tokens = $this->suppressNonSpeechTokens;

        // Set numeric parameters
        $params->thold_pt = $this->tholdPt;
        $params->thold_ptsum = $this->tholdPtsum;
        $params->max_len = $this->maxLen;
        $params->max_tokens = $this->maxTokens;
        $params->audio_ctx = $this->audioCtx;
        $params->temperature = $this->temperature;
        $params->max_initial_ts = $this->maxInitialTs;
        $params->length_penalty = $this->lengthPenalty;
        $params->temperature_inc = $this->temperatureInc;
        $params->entropy_thold = $this->entropyThold;
        $params->logprob_thold = $this->logprobThold;
        $params->no_speech_thold = $this->noSpeechThold;

        // Set language if specified
        if ($this->language !== null) {
            $len = strlen($this->language) + 1;
            $language = $ffi->new("char[$len]");
            FFI::memcpy($language, $this->language, strlen($this->language));
            $params->language = $ffi->cast('char *', $language);
        }

        // Set initial prompt if specified
        if ($this->initialPrompt !== null) {
            $len = strlen($this->initialPrompt) + 1;
            $initialPrompt = $ffi->new("char[$len]");
            FFI::memcpy($initialPrompt, $this->initialPrompt, strlen($this->initialPrompt));
            $params->initial_prompt = $ffi->cast('char *', $initialPrompt);
        }

        // Set tokens if specified
        if ($this->tokens !== null) {
            $tokenCount = count($this->tokens);
            $tokenArray = $ffi->new("whisper_token[$tokenCount]");
            foreach ($this->tokens as $i => $token) {
                $tokenArray[$i] = $token;
            }
            $params->prompt_tokens = $ffi->cast('whisper_token *', $tokenArray);
            $params->prompt_n_tokens = $tokenCount;
        }

        // Set grammar if specified
        if ($this->grammar !== null) {
            $grammarCount = count($this->grammar);
            $grammarArray = $ffi->new("whisper_grammar_element[$grammarCount]");
            foreach ($this->grammar as $i => $element) {
                $grammarArray[$i] = $element->toCStruct($ffi);
            }
            $params->grammar_rules = $ffi->cast('whisper_grammar_element**', $grammarArray);
            $params->n_grammar_rules = $grammarCount;
            $params->grammar_penalty = $this->grammarPenalty;
        }

        if ($this->segmentCallback !== null) {
            $segmentCallback = $this->segmentCallback;
            $params->new_segment_callback = function (CData $ctx, CData $state, int $nnNew, ?CData $user_data) use ($segmentCallback, $ffi) {
                $nSegments = $ffi->whisper_full_n_segments_from_state($state);
                $s0 = $nSegments - $nnNew;

                // Process each new segment
                for ($i = $s0; $i < $nSegments; $i++) {
                    $text = $ffi->whisper_full_get_segment_text_from_state($state, $i);
                    $t0 = $ffi->whisper_full_get_segment_t0_from_state($state, $i);
                    $t1 = $ffi->whisper_full_get_segment_t1_from_state($state, $i);

                    // Create callback data object
                    $callbackData = new SegmentData($i, $t0, $t1, $text);

                    // Call the PHP callback
                    $segmentCallback($callbackData);
                }
            };
        }

        if ($this->progressCallback !== null) {
            $progressCallback = $this->progressCallback;
            $params->progress_callback = function (CData $ctx, CData $state, int $progress, ?CData $user_data) use ($progressCallback) {
                return $progressCallback($progress);
            };
        }

        return $params;
    }

    public function withLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function withInitialPrompt(string $prompt): self
    {
        $this->initialPrompt = $prompt;

        return $this;
    }

    public function withTokens(array $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * Enable an array of grammar elements to be passed to the whisper model.
     *
     * @param  WhisperGrammarElement[]  $grammar
     */
    public function withGrammar(array $grammar): self
    {
        $this->grammar = $grammar;

        return $this;
    }

    public function withGrammarPenalty(float $penalty): self
    {
        $this->grammarPenalty = $penalty;

        return $this;
    }

    /**
     * Sets a callback for progress updates
     */
    public function withProgressCallback(callable $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Sets a callback for segment updates
     */
    public function withSegmentCallback(callable $callback): self
    {
        $this->segmentCallback = $callback;

        return $this;
    }
}
