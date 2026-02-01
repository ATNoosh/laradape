<?php

namespace Core\Pipeline;

final class ObservedPipeline implements Pipeline
{
    public function __construct(
        private Pipeline $inner,
        private PipelineObserver $observer
    ) {}

    public function name(): string
    {
        return 'dummy';
    }

    public function run(PipelineContext $context): PipelineResult
    {
        $start = microtime(true);

        $this->observer->pipelineStarted();
        $result = $this->inner->run($context);
        $this->observer->pipelineFinished(
            $result,
            (microtime(true) - $start) * 1000
        );

        return $result;
    }
}
