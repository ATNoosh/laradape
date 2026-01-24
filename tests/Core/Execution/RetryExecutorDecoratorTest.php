<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\RetryExecutorDecorator;
use Core\Execution\SimpleRetryPolicy;
use Core\Execution\Executor;
use Core\Execution\ExponentialBackoffRetryPolicy;
use Core\Execution\InMemoryRetryMetricsObserver;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\Pipeline;
use Core\ValueObject\JobId;

final class RetryExecutorDecoratorTest extends TestCase
{
    private function job(): Job
    {
        return new Job(
            new JobId('job-1'),
            new DefaultPipeline([])
        );
    }

    public function test_retries_on_failure_until_success(): void
    {
        $jobId = new JobId('test-job');
        $executor = new CountingExecutor([
            JobReport::failed($jobId, 'failed 1'),
            JobReport::failed($jobId, 'failed 2'),
            JobReport::completed($jobId),
        ]);

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(3)
        );

        $report = $decorated->execute($this->job());

        $this->assertSame(3, $executor->calls);
        $this->assertTrue($report->result()->isCompleted());
    }

    public function test_stops_after_max_retries(): void
    {
        $jobId = new JobId('test-job');
        $executor = new CountingExecutor([
            JobReport::failed($jobId, 'failed 1'),
            JobReport::failed($jobId, 'failed 2'),
        ]);

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(2)
        );

        $report = $decorated->execute($this->job());

        $this->assertSame(2, $executor->calls);
        $this->assertTrue($report->result()->isFailed());
    }

    public function test_does_not_retry_on_success(): void
    {
        $jobId = new JobId('test-job');
        $executor = new CountingExecutor([
            JobReport::completed($jobId),
        ]);

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(5)
        );

        $decorated->execute($this->job());

        $this->assertSame(1, $executor->calls);
    }

    public function test_retry_with_delay(): void
    {
        $jobId = new JobId('test-job');
        $executor = new CountingExecutor([
            JobReport::failed($jobId, 'failed 1'),
            JobReport::completed($jobId),
        ]);

        $start = microtime(true);
        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(2, 10) // 10ms delay
        );

        $report = $decorated->execute($this->job());
        $duration = (microtime(true) - $start) * 1000;

        $this->assertSame(2, $executor->calls);
        $this->assertTrue($report->result()->isCompleted());
        $this->assertGreaterThan(5, $duration);
    }

    public function test_retry_handles_exceptions(): void
    {
        $failingExecutor = new class implements Executor {
            private int $calls = 0;

            public function execute(Job $job): JobReport
            {
                $this->calls++;
                if ($this->calls < 2) {
                    throw new \RuntimeException('Temporary failure');
                }
                return JobReport::completed($job->id());
            }
        };

        $decorated = new RetryExecutorDecorator(
            $failingExecutor,
            new SimpleRetryPolicy(2)
        );

        $report = $decorated->execute($this->job());

        $this->assertTrue($report->result()->isCompleted());
    }

    public function test_job_is_retried_until_success(): void
    {
        $attempts = 0;

        $failingThenSuccessExecutor = new class($attempts) implements Executor {
            public function __construct(private int &$attempts) {}

            public function execute(Job $job): JobReport
            {
                $this->attempts++;

                if ($this->attempts < 3) {
                    return JobReport::failed(
                        $job->id(),
                        'Failed Message'
                    );
                }

                return JobReport::completed($job->id());
            }
        };

        $policy = new ExponentialBackoffRetryPolicy(5, 0, 0); // بدون delay برای تست

        $executor = new RetryExecutorDecorator(
            $failingThenSuccessExecutor,
            $policy
        );

        $job = new Job(new JobId('retry-job'), $this->createMock(Pipeline::class));

        $report = $executor->execute($job);

        $this->assertSame(JobResult::STATE_COMPLETED, $report->result()->state());
        $this->assertSame(3, $attempts);
    }

    public function test_retry_metrics_are_recorded(): void
    {
        $metrics = new InMemoryRetryMetricsObserver();

        $policy = new ExponentialBackoffRetryPolicy(3, 0, 0);

        $executor = new RetryExecutorDecorator(
            new AlwaysFailExecutor(),
            $policy,
            metrics: $metrics
        );

        $job = new Job(new JobId('job-x'), $this->createMock(Pipeline::class));

        $executor->execute($job);

        $this->assertSame(2, $metrics->scheduled); // two retries
        $this->assertSame(1, $metrics->aborted);   // final stop
    }
}

/**
 * Test Double
 */
final class CountingExecutor implements Executor
{
    public int $calls = 0;

    public function __construct(
        private array $reports
    ) {}

    public function execute(Job $job): JobReport
    {
        $this->calls++;

        return array_shift($this->reports);
    }
}
final class AlwaysFailExecutor implements Executor
{
    public function execute(Job $job): JobReport
    {
        return JobReport::failed(
            $job->id(),
            'Always fails'
        );
    }
}
