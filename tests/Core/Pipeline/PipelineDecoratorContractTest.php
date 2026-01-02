<?php

use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineDecorator;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;
use PHPUnit\Framework\TestCase;

final class NoOpPipelineDecorator implements PipelineDecorator
{
    public function decorate(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }
}

final class PipelineDecoratorContractTest extends TestCase
{
    public function test_decorator_does_not_change_pipeline_result(): void
    {
        // arrange
        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        ]);
        $decorator = new NoOpPipelineDecorator();

        // act
        $originalResult  = $pipeline->run(new PipelineContext());
        $decoratedPipeline = $decorator->decorate($pipeline);
        $decoratedResult = $decoratedPipeline->run(new PipelineContext());

        // assert
        $this->assertSame(
            $originalResult->isSuccess(),
            $decoratedResult->isSuccess()
        );

        $this->assertSame(
            $originalResult->error()?->code(),
            $decoratedResult->error()?->code()
        );
    }
}
