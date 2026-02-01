<?php

use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;
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
    public int $started = 0;
    public int $finished = 0;
    public array $events = [];

    public function pipelineStarted(): void
    {
        $this->started++;
        $this->events[] = 'started';
    }

    public function pipelineFinished(PipelineResult $result, float $durationMs): void
    {
        $this->finished++;
        $this->events[] = 'finished';
    }
}
final class SpyPipeline implements Pipeline
{
    
    public function name(): string
    {
        return 'dummy';
    }

    public ?PipelineResult $result = null;

    public function run(PipelineContext $context): PipelineResult
    {
        return $this->result = PipelineResult::success();
    }
}

final class RecordingObserver implements PipelineObserver
{
    public bool $started = false;
    public ?PipelineResult $finishedResult = null;
    public ?float $durationMs = null;

    public function pipelineStarted(): void
    {
        $this->started = true;
    }

    public function pipelineFinished(PipelineResult $result, float $durationMs): void
    {
        $this->finishedResult = $result;
        $this->durationMs = $durationMs;
    }
}

final class TimingPipelineDecoratorTest extends TestCase
{
    public function test_timing_decorator_preserves_pipeline_result(): void
    {
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
    public function test_pipeline_observer_is_called(): void
    {
        $pipeline = new DefaultPipeline([
            new TestFailingStep()
        ]);

        $observer = new TestObserver();

        $decorator = new TimingPipelineDecorator($observer);

        $decorated = $decorator->decorate($pipeline);
        $result = $decorated->run(new PipelineContext());

        $this->assertSame(1, $observer->started);
        $this->assertSame(1, $observer->finished);
        $this->assertSame(['started', 'finished'], $observer->events);
    }
    public function test_decorator_preserves_pipeline_result_instance(): void
    {
        // arrange
        $pipeline = new SpyPipeline();
        $observer = new RecordingObserver();
        $decorator = new TimingPipelineDecorator($observer);

        $decorated = $decorator->decorate($pipeline);

        // act
        $result = $decorated->run(new PipelineContext());

        // assert
        $this->assertSame(
            $pipeline->result,
            $result,
            'Decorator must return the exact PipelineResult instance from inner pipeline'
        );
    }
    public function test_decorator_notifies_observer(): void
    {
        // arrange
        $pipeline = new SpyPipeline();
        $observer = new RecordingObserver();
        $decorator = new TimingPipelineDecorator($observer);

        $decorated = $decorator->decorate($pipeline);

        // act
        $result = $decorated->run(new PipelineContext());

        // assert
        $this->assertTrue($observer->started);
        $this->assertSame($result, $observer->finishedResult);
        $this->assertIsFloat($observer->durationMs);
        $this->assertGreaterThanOrEqual(0.0, $observer->durationMs);
    }
    public function test_decorator_does_not_change_pipeline_outcome(): void
    {
        $pipeline = new SpyPipeline();
        $observer = new RecordingObserver();
        $decorator = new TimingPipelineDecorator($observer);

        $decorated = $decorator->decorate($pipeline);

        $result = $decorated->run(new PipelineContext());

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->error());
    }
}
