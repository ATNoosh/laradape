<?php

use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use PHPUnit\Framework\TestCase;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;
use Core\Pipeline\PipelineObserver;
use Core\Pipeline\TimingPipelineDecorator;

final class TestSuccessfulStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        return PipelineResult::success();
    }
}

final class TestFailingStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        return PipelineResult::failure(new PipelineError('X', 'failed'));
    }
}

final class TestObserver implements PipelineObserver
{
    public bool $started = false;
    public ?PipelineResult $result = null;
    public float $duration = 0.0;

    public function pipelineStarted(): void
    {
        $this->started = true;
    }

    public function pipelineFinished(PipelineResult $result, float $durationMs): void
    {
        $this->result = $result;
        $this->duration = $durationMs;
    }
}

final class TimingPipelineDecoratorTest extends TestCase
{
    public function test_timing_decorator_preserves_pipeline_result(): void {
            // arrange
            $pipeline = new DefaultPipeline([
                new TestSuccessfulStep()
            ]);

            $observer = new TestObserver();

            $decorator = new TimingPipelineDecorator($observer);

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
    public function test_pipeline_observer_is_called(): void {
        $pipeline = new DefaultPipeline([
            new TestFailingStep()
        ]);

        $observer = new TestObserver();

        $decorator = new TimingPipelineDecorator($observer);

        $decorated = $decorator->decorate($pipeline);
        $result = $decorated->run(new PipelineContext());

        $this->assertTrue($observer->started);
        $this->assertNotNull($observer->result);
        $this->assertSame($result->isSuccess(), $observer->result->isSuccess());
        $this->assertIsFloat($observer->duration);
        $this->assertGreaterThanOrEqual(0.0, $observer->duration);
    }
}
