<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use FFI;
use FFI\CData;

/**
 * Represents token data from Whisper's transcription
 */
class TokenData
{
    public function __construct(
        public readonly int $id,      // token id
        public readonly int $tid,     // forced timestamp token id
        public readonly float $p,     // probability of the token
        public readonly float $plog,  // log probability of the token
        public readonly float $pt,    // probability of the timestamp token
        public readonly float $ptsum, // sum of probabilities of all timestamp tokens
        public readonly int $startTimestamp,      // start time of the token
        public readonly int $endTimestamp,      // end time of the token
        public readonly int $dtwTimestamp,    // token timing from DTW
        public readonly float $voiceLen,  // voice length of the token
    ) {}

    /**
     * Create a TokenData instance from a CData struct
     */
    public static function fromCStruct(CData $data): self
    {
        return new self(
            id: $data->id,
            tid: $data->tid,
            p: $data->p,
            plog: $data->plog,
            pt: $data->pt,
            ptsum: $data->ptsum,
            startTimestamp: $data->t0,
            endTimestamp: $data->t1,
            dtwTimestamp: $data->t_dtw,
            voiceLen: $data->vlen
        );
    }

    /**
     * Convert this instance to a CData struct
     */
    public function toCStruct(FFI $ffi): CData
    {
        $struct = $ffi->new('struct whisper_token_data');

        $struct->id = $this->id;
        $struct->tid = $this->tid;
        $struct->p = $this->p;
        $struct->plog = $this->plog;
        $struct->pt = $this->pt;
        $struct->ptsum = $this->ptsum;
        $struct->t0 = $this->startTimestamp;
        $struct->t1 = $this->endTimestamp;
        $struct->t_dtw = $this->dtwTimestamp;
        $struct->vlen = $this->voiceLen;

        return $struct;
    }

    /**
     * Get the duration of the token in milliseconds
     */
    public function getDuration(): int
    {
        return $this->endTimestamp - $this->startTimestamp;
    }

    /**
     * Check if this token has valid DTW timing
     */
    public function hasDtwTiming(): bool
    {
        return $this->dtwTimestamp >= 0;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tid' => $this->tid,
            'p' => $this->p,
            'plog' => $this->plog,
            'pt' => $this->pt,
            'ptsum' => $this->ptsum,
            't0' => $this->startTimestamp,
            't1' => $this->endTimestamp,
            't_dtw' => $this->dtwTimestamp,
            'vlen' => $this->voiceLen,
        ];
    }
}
