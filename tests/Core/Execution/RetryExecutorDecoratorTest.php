<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\RetryExecutorDecorator;
use Core\Execution\MaxAttemptsRetryPolicy;
use Core\Execution\Executor;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\DefaultPipeline;
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
            new MaxAttemptsRetryPolicy(3)
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
            new MaxAttemptsRetryPolicy(2)
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
            new MaxAttemptsRetryPolicy(5)
        );

        $decorated->execute($this->job());

        $this->assertSame(1, $executor->calls);
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
