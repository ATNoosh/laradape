<?php

namespace Tests\Feature;

use Tests\TestCase;
use Core\Execution\Executor;
use Core\Execution\ExecutorObserver;
use Core\Execution\JobReport;
use Core\Execution\TimingExecutorDecorator;
use Core\Execution\DefaultExecutor;
use Core\Execution\TimeBox;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use RuntimeException;
use Throwable;

final class JobSystemObservabilityTest extends TestCase
{
    public function test_observer_receives_start_and_finish_events(): void
    {
        $observer = new class implements ExecutorObserver {
            public array $events = [];

            public function jobStarted(Job $job): void
            {
                $this->events[] = "start:{$job->id()->value()}";
            }

            public function jobFinished(Job $job, JobReport $report, float $durationMs): void
            {
                $this->events[] = "finish:{$job->id()->value()}";
            }

            public function jobFailed(Job $job, Throwable $exception, float $durationMs): void
            {
                $this->events[] = "fail:{$job->id()->value()}";
            }
        };

        $executor = new TimingExecutorDecorator(
            new DefaultExecutor(new TimeBox(time(), 60)),
            $observer
        );

        $job = new Job(
            new JobId('test-job'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $executor->execute($job);

        $this->assertSame(
            ['start:test-job', 'finish:test-job'],
            $observer->events
        );
    }

    public function test_observer_receives_failed_event_with_duration(): void
    {
        $observer = new class implements ExecutorObserver {
            public array $events = [];
            public ?float $duration = null;

            public function jobStarted(Job $job): void
            {
                $this->events[] = "start:{$job->id()->value()}";
            }

            public function jobFinished(Job $job, JobReport $report, float $durationMs): void
            {
                $this->events[] = "finish:{$job->id()->value()}";
            }

            public function jobFailed(Job $job, Throwable $exception, float $durationMs): void
            {
                $this->events[] = "fail:{$job->id()->value()}";
                $this->duration = $durationMs;
            }
        };

        $failingExecutor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                throw new RuntimeException('Test failure');
            }
        };

        $executor = new TimingExecutorDecorator(
            $failingExecutor,
            $observer
        );

        $job = new Job(
            new JobId('failing-job'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        try {
            $executor->execute($job);
        } catch (Throwable) {
            // swallow – expected
        }

        $this->assertSame(
            ['start:failing-job', 'fail:failing-job'],
            $observer->events
        );

        $this->assertNotNull($observer->duration);
        $this->assertGreaterThanOrEqual(0, $observer->duration);
    }

    public function test_observer_receives_execution_duration(): void
    {
        $observer = new class implements ExecutorObserver {
            public ?float $duration = null;

            public function jobStarted(Job $job): void {}

            public function jobFinished(Job $job, JobReport $report, float $durationMs): void
            {
                $this->duration = $durationMs;
            }

            public function jobFailed(Job $job, Throwable $exception, float $durationMs): void {}
        };

        $slowExecutor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                usleep(10_000); // 10ms
                return JobReport::completed($job->id());
            }
        };

        $executor = new TimingExecutorDecorator(
            $slowExecutor,
            $observer
        );

        $job = new Job(
            new JobId('timed-job'),
            new class implements Pipeline {

                public function name(): string
                {
                    return 'dummy';
                }

                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );

        $executor->execute($job);

        $this->assertNotNull($observer->duration);
        $this->assertGreaterThan(0, $observer->duration);
    }

    public function test_executor_observer_is_resolvable_from_container(): void
    {
        $observer = $this->app->make(ExecutorObserver::class);

        $this->assertInstanceOf(
            \App\Execution\LoggingExecutorObserver::class,
            $observer
        );
    }
}
