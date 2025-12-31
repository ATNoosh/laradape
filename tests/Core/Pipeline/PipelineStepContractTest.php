<?php

use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use PHPUnit\Framework\TestCase;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;

final class SuccessfulStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        return PipelineResult::success();
    }
}

final class FailingStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        return PipelineResult::failure(new PipelineError('FAIL', 'failed'));
    }
}

final class ExplodingStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        throw new RuntimeException('Boom');
    }
}

final class PipelineStepContractTest extends TestCase
{
    public function test_pipeline_stops_on_first_failure(): void
    {
        $pipeline = new DefaultPipeline([
            new SuccessfulStep(),
            new FailingStep(),
            new ExplodingStep(), // نباید اجرا شود
        ]);

        $result = $pipeline->run(new PipelineContext());

        $this->assertFalse($result->isSuccess());
    }
    public function test_step_failure_is_not_exception(): void
    {
        $this->expectNotToPerformAssertions();

        $pipeline = new DefaultPipeline([
            new class implements PipelineStep {
                public function execute(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::failure(
                        new PipelineError('X', 'expected')
                    );
                }
            }
        ]);

        $pipeline->run(new PipelineContext());
    }
}
