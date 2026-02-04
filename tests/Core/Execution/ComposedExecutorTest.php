<?php

use Core\Execution\ComposedExecutor;
use PHPUnit\Framework\TestCase;
use Core\Execution\DefaultExecutor;
use Core\Execution\Executor;
use Core\Execution\SimpleRetryPolicy;
use Core\Repository\InMemoryJobExecutionStore;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\ValueObject\JobId;
use Core\Pipeline\Pipeline;

final class ComposedExecutorTest extends TestCase
{
    public function test_job_is_executed_once_with_retry(): void
    {
        $calls = 0;

        $baseExecutor = new class($calls) implements Executor {
            public function __construct(private int &$calls) {}

            public function execute(Job $job): JobReport
            {
                $this->calls++;

                if ($this->calls < 2) {
                    return JobReport::failed($job->id(), 'fail');
                }

                return JobReport::completed($job->id());
            }
        };

        $scheduler = new InMemoryRetryScheduler();

        $executor = new ComposedExecutor(
            baseExecutor: $baseExecutor,
            retryPolicy: new SimpleRetryPolicy(3),
            scheduler: $scheduler,
            executionStore: new InMemoryJobExecutionStore()
        );

        $job = new Job(
            new JobId('job-1'),
            $this->createMock(Pipeline::class)
        );

        // first attempt
        $report = $executor->execute($job);

        // simulate async retry loop
        $command = null;

        do {
            $report  = $executor->execute($job, $command);
            $command = $scheduler->next();
        } while ($command !== null);

        $this->assertSame(2, $calls);
        $this->assertTrue($report->result()->isCompleted());
    }

    public function test_job_is_retried_and_completed_once(): void
    {
        $attempts = 0;

        $baseExecutor = new class($attempts) implements Executor {
            public function __construct(private int &$attempts) {}

            public function execute(Job $job): JobReport
            {
                $this->attempts++;

                if ($this->attempts < 3) {
                    return JobReport::failed($job->id(), 'fail');
                }

                return JobReport::completed($job->id());
            }
        };

        $scheduler = new InMemoryRetryScheduler();
        $store     = new InMemoryJobExecutionStore();

        $executor = new ComposedExecutor(
            baseExecutor: $baseExecutor,
            retryPolicy: new SimpleRetryPolicy(3),
            scheduler: $scheduler,
            executionStore: $store
        );

        $job = new Job(
            new JobId('job-1'),
            $this->createMock(Pipeline::class)
        );

        // 🔁 simulate worker retry loop
        $report = $executor->execute($job);
        $command = null;

        do {
            $report  = $executor->execute($job, $command);
            $command = $scheduler->next();
        } while ($command !== null);

        $this->assertSame(3, $attempts);

        $this->assertTrue(
            $report->result()->isCompleted()
                || $report->isSkipped()
        );
    }


    public function test_job_is_not_executed_twice(): void
    {
        $calls = 0;

        $baseExecutor = new class($calls) implements Executor {
            public function __construct(private int &$calls) {}

            public function execute(Job $job): JobReport
            {
                $this->calls++;
                return JobReport::completed($job->id());
            }
        };

        $store = new InMemoryJobExecutionStore();

        $executor = new ComposedExecutor(
            $baseExecutor,
            new SimpleRetryPolicy(3),
            new InMemoryRetryScheduler(),
            $store
        );

        $job = new Job(
            new JobId('job-2'),
            $this->createMock(Pipeline::class)
        );

        $executor->execute($job);
        $executor->execute($job);

        $this->assertSame(1, $calls);
    }
}
