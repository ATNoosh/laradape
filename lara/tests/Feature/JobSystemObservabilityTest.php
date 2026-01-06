<?php

namespace Tests\Feature;

use Tests\TestCase;
use Core\Execution\Executor;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobReport;
use Core\Execution\TimingExecutorDecorator;
use Core\Execution\DefaultExecutor;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

final class JobSystemObservabilityTest extends TestCase
{
    public function test_logging_observer_receives_events(): void
    {
        $observer = new class implements ExecutorObserver {
            public array $events = [];

            public function jobStarted(string $jobId): void
            {
                $this->events[] = "start:{$jobId}";
            }

            public function jobFinished(JobReport $report, float $durationMs): void
            {
                $this->events[] = "finish:{$report->jobId()->value()}";
            }

            public function jobFailed(JobReport $report, float $durationMs): void
            {
                $this->events[] = "fail:{$report->jobId()->value()}";
            }
        };

        $executor = new TimingExecutorDecorator(
            new DefaultExecutor(),
            $observer
        );

        $job = new Job(
            new JobId('test-job'),
            new class implements Pipeline {
                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $executor->execute($job);

        $this->assertContains('start:test-job', $observer->events);
        $this->assertContains('finish:test-job', $observer->events);
        $this->assertCount(2, $observer->events);
    }

    public function test_logging_observer_receives_failed_event(): void
    {
        $observer = new class implements ExecutorObserver {
            public array $events = [];
            public ?float $failedDuration = null;

            public function jobStarted(string $jobId): void
            {
                $this->events[] = "start:{$jobId}";
            }

            public function jobFinished(JobReport $report, float $durationMs): void
            {
                $this->events[] = "finish:{$report->jobId()->value()}";
            }

            public function jobFailed(JobReport $report, float $durationMs): void
            {
                $this->events[] = "fail:{$report->jobId()->value()}";
                $this->failedDuration = $durationMs;
            }
        };

        $failingExecutor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                return new JobReport(
                    $job->id(),
                    new JobResult(JobResult::STATE_FAILED, 'Test failure')
                );
            }
        };

        $executor = new TimingExecutorDecorator(
            $failingExecutor,
            $observer
        );

        $job = new Job(
            new JobId('failing-job'),
            new class implements Pipeline {
                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $executor->execute($job);

        $this->assertContains('start:failing-job', $observer->events);
        $this->assertContains('fail:failing-job', $observer->events);
        $this->assertNotContains('finish:failing-job', $observer->events);
        $this->assertNotNull($observer->failedDuration);
        $this->assertGreaterThanOrEqual(0, $observer->failedDuration);
    }

    public function test_observer_receives_duration_in_milliseconds(): void
    {
        $observer = new class implements ExecutorObserver {
            public ?float $duration = null;

            public function jobStarted(string $jobId): void {}

            public function jobFinished(JobReport $report, float $durationMs): void
            {
                $this->duration = $durationMs;
            }

            public function jobFailed(JobReport $report, float $durationMs): void {}
        };

        $slowExecutor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                usleep(10000); // 10ms delay
                return new JobReport(
                    $job->id(),
                    new JobResult(JobResult::STATE_COMPLETED)
                );
            }
        };

        $executor = new TimingExecutorDecorator(
            $slowExecutor,
            $observer
        );

        $job = new Job(
            new JobId('timed-job'),
            new class implements Pipeline {
                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $executor->execute($job);

        $this->assertNotNull($observer->duration);
        $this->assertGreaterThanOrEqual(10, $observer->duration);
    }

    public function test_observer_is_called_via_service_container(): void
    {
        $observer = $this->app->make(ExecutorObserver::class);

        $this->assertInstanceOf(\App\Execution\LoggingExecutorObserver::class, $observer);
    }
}

