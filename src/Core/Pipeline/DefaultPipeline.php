<?php

namespace Core\Pipeline;

use Core\Error\PipelineException;

final class DefaultPipeline implements Pipeline
{
    /**
     * @param PipelineStep[] $steps
     */
    public function __construct(
        private array $steps
    ) {
        foreach ($steps as $step) {
            if (!$step instanceof PipelineStep) {
                throw new \InvalidArgumentException('All steps must implement PipelineStep');
            }
        }
    }

    public function name(): string
    {
        return static::class;
    }

    public function run(PipelineContext $context): PipelineResult
    {
        foreach ($this->steps as $step) {
            try {
                $result = $step->execute($context);
                if (! $result->isSuccess()) {
                    return $result;
                }
            } catch (PipelineException $e) {
                // Domain-level exception → bubble up
                throw $e;
            } catch (\Throwable $e) {
                // Unexpected error → wrap as fatal
                throw new \RuntimeException(
                    'Unhandled exception in pipeline step',
                    previous: $e
                );
            }
        }

        return PipelineResult::success();
    }
}
