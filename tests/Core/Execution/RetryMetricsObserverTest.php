<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\RetryExecutorDecorator;
use Core\Execution\InMemoryRetryMetricsObserver;
use Core\Execution\ExponentialBackoffRetryPolicy;
use Core\Execution\Executor;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\ValueObject\JobId;
use Core\Pipeline\Pipeline;

final class RetryMetricsObserverTest extends TestCase
{
    public function test_retry_metrics_are_collected(): void
    {
        $attempts = 0;

        $executor = new class($attempts) implements Executor {
            public function __construct(private int &$attempts) {}

            public function execute(Job $job): JobReport
            {
                $this->attempts++;

                if ($this->attempts < 3) {
                    return JobReport::failed($job->id(), 'Fail');
                }

                return JobReport::completed($job->id());
            }
        };

        $policy = new ExponentialBackoffRetryPolicy(
            maxAttempts: 5,
            baseDelayMs: 0,
            maxDelayMs: 0
        );

        $metrics = new InMemoryRetryMetricsObserver();

        $retryExecutor = new RetryExecutorDecorator(
            $executor,
            $policy,
            metrics: $metrics
        );

        $job = new Job(
            new JobId('job-1'),
            $this->createMock(Pipeline::class)
        );

        $report = $retryExecutor->execute($job);

        $this->assertSame(JobResult::STATE_COMPLETED, $report->result()->state());
        $this->assertSame(2, $metrics->scheduled);
        $this->assertSame(1, $metrics->succeeded);
        $this->assertSame(0, $metrics->aborted);
    }
}
