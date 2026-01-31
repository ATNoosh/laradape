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

        $scheduler = new InMemoryRetryScheduler();

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(3),
            $scheduler
        );

        $report = $this->runRetryLoop($decorated, $scheduler, $this->job());

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

        $scheduler = new InMemoryRetryScheduler();

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(2),
            $scheduler
        );

        $report = $this->runRetryLoop($decorated, $scheduler, $this->job());

        $this->assertSame(2, $executor->calls);
        $this->assertTrue($report->result()->isFailed());
    }

    public function test_does_not_retry_on_success(): void
    {
        $jobId = new JobId('test-job');

        $executor = new CountingExecutor([
            JobReport::completed($jobId),
        ]);

        $scheduler = new InMemoryRetryScheduler();

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(5),
            $scheduler
        );

        $report = $this->runRetryLoop($decorated, $scheduler, $this->job());

        $this->assertSame(1, $executor->calls);
        $this->assertEmpty($scheduler->scheduled);
        $this->assertTrue($report->result()->isCompleted());
    }

    public function test_retry_with_delay(): void
    {
        $jobId = new JobId('test-job');
    
        $executor = new CountingExecutor([
            JobReport::failed($jobId, 'failed 1'),
            JobReport::completed($jobId),
        ]);
    
        $scheduler = new InMemoryRetryScheduler();
    
        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(2, 10), // 10ms delay
            $scheduler
        );
    
        $decorated->execute($this->job(), null);
    
        $this->assertCount(1, $scheduler->scheduled);
        $this->assertSame(10, $scheduler->scheduled[0]->delayMs());
    }
    
    public function test_retry_handles_exceptions(): void
    {
        $executor = new class implements Executor {
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

        $scheduler = new InMemoryRetryScheduler();

        $decorated = new RetryExecutorDecorator(
            $executor,
            new SimpleRetryPolicy(2),
            $scheduler
        );

        $report = $this->runRetryLoop($decorated, $scheduler, $this->job());

        $this->assertTrue($report->result()->isCompleted());
    }


    public function test_job_is_retried_until_success(): void
    {
        $attempts = 0;

        $executor = new class($attempts) implements Executor {
            public function __construct(private int &$attempts) {}

            public function execute(Job $job): JobReport
            {
                $this->attempts++;

                if ($this->attempts < 3) {
                    return JobReport::failed($job->id(), 'Failed');
                }

                return JobReport::completed($job->id());
            }
        };

        $scheduler = new InMemoryRetryScheduler();

        $decorated = new RetryExecutorDecorator(
            $executor,
            new ExponentialBackoffRetryPolicy(5, 0, 0),
            $scheduler
        );

        $report = $this->runRetryLoop($decorated, $scheduler, $this->job());

        $this->assertSame(3, $attempts);
        $this->assertTrue($report->result()->isCompleted());
    }

    public function test_retry_metrics_are_recorded(): void
    {
        $metrics   = new InMemoryRetryMetricsObserver();
        $scheduler = new InMemoryRetryScheduler();
    
        $executor = new RetryExecutorDecorator(
            new AlwaysFailExecutor(),
            new ExponentialBackoffRetryPolicy(3, 0, 0),
            $scheduler,
            observer: $metrics
        );
    
        $job = $this->job();
    
        $executor->execute($job, null);
    
        while ($command = $scheduler->next()) {
            $executor->execute($job, $command);
        }
    
        $this->assertSame(2, $metrics->scheduled); // retries
        $this->assertSame(1, $metrics->aborted);   // final stop
    }
    

    private function runRetryLoop(
        RetryExecutorDecorator $executor,
        InMemoryRetryScheduler $scheduler,
        Job $job
    ): JobReport {
        $command = null;
        $report  = null;

        do {
            $report  = $executor->execute($job, $command);
            $command = $scheduler->next();
        } while ($command !== null);

        return $report;
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
