<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use FFI;

class WhisperState
{
    public function __construct(
        private FFI $ffi,
        private mixed $ctx,
        private mixed $state
    ) {}

    /**
     * Run the entire model: PCM -> log mel spectrogram -> encoder -> decoder -> text
     * Uses the specified decoding strategy to obtain the text.
     *
     *
     * @param  float[]  $pcm  Raw PCM audio data, 32 bit floating point at a sample rate of 16 kHz, 1 channel. @see Utils::readAudio for a helper.
     * @param  WhisperFullParams  $params  The parameters to use.
     */
    public function full(array $pcm, WhisperFullParams $params): void
    {
        if (empty($pcm)) {
            // can randomly trigger segmentation faults if we don't check this
            throw WhisperException::noSamples();
        }

        $pcmSize = count($pcm);
        $samples = $this->ffi->new("float[$pcmSize]");

        foreach ($pcm as $i => $sample) {
            $samples[$i] = $sample;
        }

        $cParams = $params->toCStruct($this->ffi);
        $result = $this->ffi->whisper_full_with_state(
            $this->ctx,
            $this->state,
            $cParams,
            $this->ffi->cast('float *', $samples),
            $pcmSize
        );

        match ($result) {
            -1, -2 => throw WhisperException::failedToCalculateSpectrogram(),
            -3 => throw WhisperException::failedToAutoDetectLanguage(),
            -5 => throw WhisperException::audioCtxLongerThanMax($params->audioCtx, $this->ffi->whisper_model_n_audio_ctx($this->ctx)),
            -6 => WhisperException::failedToEncode(),
            -7, -8 => WhisperException::failedToDecode(),
            0 => null,
            default => throw WhisperException::genericError($result)
        };
    }

    /**
     *  Convert raw PCM audio (floating point 32 bit) to log mel spectrogram.
     *
     * The resulting spectrogram is stored in the context transparently.
     *
     * @param  float[]  $pcm  The raw PCM audio.
     * @param  int  $nThreads  How many threads to use. Defaults to 1. Must be at least 1, returns an error otherwise.
     *
     * @throws WhisperException
     */
    public function pcmToMel(array $pcm, int $nThreads): void
    {
        if ($nThreads < 1) {
            throw WhisperException::invalidThreadCount();
        }

        $pcmSize = count($pcm);
        $samples = $this->ffi->new("float[$pcmSize]");

        foreach ($pcm as $i => $sample) {
            $samples[$i] = $sample;
        }

        $result = $this->ffi->whisper_pcm_to_mel_with_state(
            $this->ctx,
            $this->state,
            $this->ffi->cast('float *', $samples),
            $pcmSize,
            $nThreads
        );

        match ($result) {
            -1 => throw WhisperException::failedToCalculateSpectrogram(),
            0 => null,
            default => throw WhisperException::genericError($result)
        };
    }

    /**
     * Sets a custom log mel spectrogram inside the provided whisper state.
     * Use this instead of `pcmToMel()` if you want to provide your own log mel spectrogram.
     *
     * Note: This is a low-level function.
     * If you're a typical user, you probably don't want to use this function.
     * See instead WhisperState::pcmToMel().
     *
     * @param  float[]  $data  The log mel spectrogram
     *
     * @throws WhisperException
     */
    public function setMel(array $data): void
    {
        $hopSize = 160;
        $nLen = (count($data) / $hopSize) * 2;

        // Create CData array for float data
        $dataSize = count($data);
        $cData = $this->ffi->new("float[$dataSize]");
        foreach ($data as $i => $value) {
            $cData[$i] = $value;
        }

        $result = $this->ffi->whisper_set_mel_with_state(
            $this->ctx,
            $this->state,
            $this->ffi->cast('float *', $cData),
            $nLen,
            80
        );

        match ($result) {
            -1 => throw WhisperException::invalidMelBands(),
            0 => null,
            default => throw WhisperException::genericError($result)
        };
    }

    /**
     * Run the Whisper encoder on the log mel spectrogram stored inside the provided whisper state.
     * Make sure to call WhisperState::pcmToMel() or WhisperState::setMel() first.
     *
     * @param  int  $offset  Can be used to specify the offset of the first frame in the spectrogram. Usually 0.
     * @param  int  $nThreads  How many threads to use. Defaults to 1. Must be at least 1.
     *
     * @throws WhisperException
     */
    public function encode(int $offset, int $nThreads): void
    {
        if ($nThreads < 1) {
            throw WhisperException::invalidThreadCount();
        }

        $result = $this->ffi->whisper_encode_with_state(
            $this->ctx,
            $this->state,
            $offset,
            $nThreads
        );

        match ($result) {
            -1 => throw WhisperException::failedToCalculateEvaluation(),
            0 => null,
            default => throw WhisperException::genericError($result)
        };
    }

    /**
     * Run the Whisper decoder to obtain the logits and probabilities for the next token.
     * Make sure to call WhisperState::encode() first.
     * tokens + n_tokens is the provided context for the decoder.
     *
     * @param  int[]  $tokens  The tokens to decode
     * @param  int  $nPast  The number of past tokens to use for the decoding
     * @param  int  $nThreads  How many threads to use. Must be at least 1.
     *
     * @throws WhisperException
     */
    public function decode(array $tokens, int $nPast, int $nThreads): void
    {
        if ($nThreads < 1) {
            throw WhisperException::invalidThreadCount();
        }

        // Create CData array for tokens
        $tokenSize = count($tokens);
        $cTokens = $this->ffi->new("int[$tokenSize]");
        foreach ($tokens as $i => $token) {
            $cTokens[$i] = $token;
        }

        $ret = $this->ffi->whisper_decode_with_state(
            $this->ctx,
            $this->state,
            $this->ffi->cast('int *', $cTokens),
            count($tokens),
            $nPast,
            $nThreads
        );

        match ($ret) {
            -1 => throw WhisperException::failedToCalculateEvaluation(),
            0 => null,
            default => throw WhisperException::genericError($ret)
        };
    }

    /**
     * Use mel data at offset_ms to try and auto-detect the spoken language
     * Make sure to call pcmToMel() or setMel() first
     *
     * @param  int  $offsetMs  The offset in milliseconds to use for the language detection
     * @param  int  $nThreads  How many threads to use. Must be at least 1.
     * @return array{top_lang_id: int, lang_probs: float[]} Tuple of [detected language id, array of probabilities]
     *
     * @throws WhisperException
     */
    public function langDetect(int $offsetMs, int $nThreads): array
    {
        if ($nThreads < 1) {
            throw WhisperException::invalidThreadCount();
        }

        // Get max language ID and create probabilities array
        $maxLangId = $this->langMaxId();
        $langProbs = $this->ffi->new('float['.($maxLangId + 1).']');

        $ret = $this->ffi->whisper_lang_auto_detect_with_state(
            $this->ctx,
            $this->state,
            $offsetMs,
            $nThreads,
            $this->ffi->cast('float *', $langProbs)
        );

        if ($ret < 0) {
            match ($ret) {
                -1 => throw WhisperException::offsetBeforeAudioStart($offsetMs),
                -2 => throw WhisperException::offsetAfterAudioEnd($offsetMs),
                -6 => throw WhisperException::failedToEncode(),
                -7 => throw WhisperException::failedToDecode(),
                default => throw WhisperException::genericError($ret)
            };
        }

        // Convert CData array to PHP array
        $probsArray = [];
        for ($i = 0; $i <= $maxLangId; $i++) {
            $probsArray[] = $langProbs[$i];
        }

        return ['top_lang_id' => $ret, 'lang_probs' => $probsArray];
    }

    /**
     * Gets logits obtained from the last call to WhisperState::decode().
     * As of whisper.cpp 1.4.1, only a single row of logits is available,
     * corresponding to the last token in the input.
     *
     * @return float[] Array of logits with length equal to n_vocab
     *
     * @throws WhisperException
     */
    public function getLogits(): array
    {
        $ret = $this->ffi->whisper_get_logits_from_state($this->state);

        if (FFI::isNull($ret)) {
            throw WhisperException::nullPointer();
        }

        $nVocab = $this->nVocab();
        $logits = [];

        // Convert CData array to PHP array
        for ($i = 0; $i < $nVocab; $i++) {
            $logits[] = $ret[$i];
        }

        return $logits;
    }

    /**
     * Get the mel spectrogram length.
     */
    public function nLen(): int
    {
        return $this->ffi->whisper_n_len_from_state($this->state);
    }

    /**
     * Get n_vocab.
     */
    public function nVocab(): int
    {
        return $this->ffi->whisper_n_vocab($this->ctx);
    }

    /**
     * Gets the maximum language ID
     */
    public function langMaxId(): int
    {
        return $this->ffi->whisper_lang_max_id();
    }

    /**
     * Number of generated text segments.
     *
     * A segment can be a few words, a sentence, or even a paragraph.
     */
    public function nSegments(): int
    {
        return $this->ffi->whisper_full_n_segments_from_state($this->state);
    }

    /**
     * Get the text of the segment at the specified index.
     *
     * @param  int  $index  Segment index.
     */
    public function getSegmentText(int $index): string
    {
        return $this->ffi->whisper_full_get_segment_text_from_state($this->state, $index);
    }

    /**
     * Get the start time of the segment at the specified index.
     *
     * @param  int  $index  Segment index.
     */
    public function getSegmentStartTime(int $index): int
    {
        return $this->ffi->whisper_full_get_segment_t0_from_state($this->state, $index);
    }

    /**
     * Get the end time of the segment at the specified index.
     *
     * @param  int  $index  Segment index.
     */
    public function getSegmentEndTime(int $index): int
    {
        return $this->ffi->whisper_full_get_segment_t1_from_state($this->state, $index);
    }

    /**
     * Get number of tokens in the specified segment.
     *
     * @param  int  $index  Segment index.
     */
    public function nTokens(int $index): int
    {
        return $this->ffi->whisper_full_n_tokens_from_state($this->state, $index);
    }

    /**
     * Get the token text of the specified token in the specified segment.
     *
     * @param  int  $index  Segment index.
     * @param  int  $token  Token index.
     */
    public function tokenText(int $index, int $token): string
    {
        return $this->ffi->whisper_full_get_token_text_from_state(
            $this->ctx,
            $this->state,
            $index,
            $token
        );
    }

    public function tokenData(int $index, int $token): ?TokenData
    {
        $data = $this->ffi->whisper_full_get_token_data_from_state($this->state, $index, $token);

        return TokenData::fromCStruct($data);
    }

    /**
     * Get the token ID of the specified token in the specified segment.
     *
     * @param  int  $index  Segment index.
     * @param  int  $token  Token index.
     */
    public function tokenId(int $index, int $token): int
    {
        return $this->ffi->whisper_full_get_token_id_from_state($this->state, $index, $token);
    }

    /**
     * Get the probability of the specified token in the specified segment.
     *
     * @param  int  $index  Segment index.
     * @param  int  $token  Token index.
     */
    public function tokenProb(int $index, int $token): float
    {
        return $this->ffi->whisper_full_get_token_prob_from_state($this->state, $index, $token);
    }

    public function __destruct()
    {
        if (isset($this->state)) {
            $this->ffi->whisper_free_state($this->state);
        }
    }
}
