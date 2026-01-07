<?php

use Core\Execution\DefaultExecutor;
use Core\Execution\Executor;
use Core\Execution\ExecutorDecorator;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobReport;
use Core\Execution\TimeBox;
use Core\Execution\TimingExecutorDecorator;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Job\JobState;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineStep;
use Core\ValueObject\JobId;
use PHPUnit\Framework\TestCase;

final class NoOpExecutorDecorator implements ExecutorDecorator
{
    public function decorate(Executor $executor): Executor
    {
        return $executor;
    }
}

final class SpyExecutor implements Executor
{
    public ?JobReport $report = null;

    public function execute(Job $job): JobReport
    {
        return $this->report = JobReport::completed($job->id());
    }
}

final class RecordingExecutorObserver implements ExecutorObserver
{
    public bool $started = false;
    public bool $failed = false;
    public ?JobReport $report = null;
    public ?float $duration = null;

    public function jobStarted(Job $job): void
    {
        $this->started = true;
    }

    public function jobFinished(
        Job $job,
        JobReport $report,
        float $durationMs
    ): void {
        $this->report = $report;
        $this->duration = $durationMs;
    }

    public function jobFailed(Job $job, Throwable $e, float $durationMs): void
    {
        $this->failed = true;
    }
}

final class ExecutorDecoratorContractTest extends TestCase
{
    public function test_executor_decorator_preserves_job_result(): void
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

        $job = new Job(new JobId('job-1'), $pipeline);

        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $decorator = new NoOpExecutorDecorator();

        // act
        $originalReport = $executor->execute($job);
        $decoratedExecutor = $decorator->decorate($executor);
        $decoratedReport = $decoratedExecutor->execute($job);

        // assert
        $this->assertSame(
            $originalReport->result()->state(),
            $decoratedReport->result()->state()
        );

        $this->assertSame(
            $originalReport->result()->message(),
            $decoratedReport->result()->message()
        );
    }
    public function test_executor_decorator_preserves_job_result_instance(): void
    {
        $executor = new SpyExecutor();
        $observer = new RecordingExecutorObserver();

        $decorated = new TimingExecutorDecorator($executor, $observer);

        $job = new Job(new JobId('job-1'), $this->createMock(Pipeline::class));

        $report = $decorated->execute($job);

        $this->assertSame(
            $executor->report->result(),
            $observer->report->result(),
            'Executor decorator must not replace JobResult'
        );
    }
    public function test_executor_decorator_does_not_change_job_state(): void
    {
        $executor = new SpyExecutor();
        $observer = new RecordingExecutorObserver();

        $decorated = new TimingExecutorDecorator($executor, $observer);

        $job = new Job(new JobId('job-1'), $this->createMock(Pipeline::class));

        $result = $decorated->execute($job)->result();

        $this->assertSame(JobState::COMPLETED, $result->state());
    }
}
