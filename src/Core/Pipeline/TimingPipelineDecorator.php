<?php

namespace Core\Pipeline;

final class TimingPipelineDecorator implements PipelineDecorator
{
    public function __construct(
        private PipelineObserver $observer
    ) {}

    public function decorate(Pipeline $pipeline): Pipeline
    {
        return new ObservedPipeline($pipeline, $this->observer);
    }
}
