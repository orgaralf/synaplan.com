<?php

declare(strict_types=1);

namespace Codewithkyrian\Whisper;

use FFI;

class WhisperContextParameters
{
    public function __construct(
        protected bool $useGpu = false,
        protected bool $flashAttn = false,
        protected int $gpuDevice = 0,
        protected DtwParameters $dtwParameters = new DtwParameters,
    ) {}

    public function toCStruct(FFI $ffi): mixed
    {
        $params = $ffi->new('struct whisper_context_params');
        $params->use_gpu = $this->useGpu;
        $params->flash_attn = $this->flashAttn;
        $params->gpu_device = $this->gpuDevice;

        // DTW parameters
        $params->dtw_token_timestamps = $this->dtwParameters->mode !== DtwMode::None;
        $params->dtw_mem_size = $this->dtwParameters->dtwMemSize;

        // Set DTW mode-specific parameters
        switch ($this->dtwParameters->mode) {
            case DtwMode::None:
                $params->dtw_aheads_preset = $ffi->WHISPER_AHEADS_NONE;
                $params->dtw_n_top = -1;
                break;
            case DtwMode::TopMost:
                $params->dtw_aheads_preset = $ffi->WHISPER_AHEADS_N_TOP_MOST;
                $params->dtw_n_top = $this->dtwParameters->nTop;
                break;
            case DtwMode::Custom:
                $params->dtw_aheads_preset = $ffi->WHISPER_AHEADS_CUSTOM;
                // Create whisper_aheads structure
                if ($this->dtwParameters->aheads !== null) {
                    $aheads = $ffi->new('struct whisper_aheads');
                    $aheads->n_heads = count($this->dtwParameters->aheads);
                    // Create array of whisper_ahead structures
                    $heads = $ffi->new("struct whisper_ahead[{$aheads->n_heads}]");
                    foreach ($this->dtwParameters->aheads as $i => $ahead) {
                        $heads[$i]->n_text_layer = $ahead['n_text_layer'];
                        $heads[$i]->n_head = $ahead['n_head'];
                    }
                    $aheads->heads = $ffi->cast('struct whisper_ahead*', $heads);
                    $params->dtw_aheads = $aheads;
                }
                break;
            case DtwMode::ModelPreset:
                $params->dtw_aheads_preset = $this->dtwParameters->modelPreset->toCEnum($ffi);
                break;
        }

        return $params;
    }

    public static function default(): self
    {
        return new self;
    }

    public function useGpu(bool $use = true): static
    {
        $this->useGpu = $use;

        return $this;
    }

    public function withFlashAttn(bool $use = true): static
    {
        $this->flashAttn = $use;

        return $this;
    }

    public function withGpuDevice(int $device): static
    {
        $this->gpuDevice = $device;

        return $this;
    }

    public function withDtwParameters(DtwParameters $parameters): static
    {
        $this->dtwParameters = $parameters;

        return $this;
    }
}
