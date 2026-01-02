<?php

namespace Core\Pipeline;

interface PipelineObserver
{
    public function pipelineStarted(): void;
    public function pipelineFinished(PipelineResult $result, float $durationMs): void;
}
