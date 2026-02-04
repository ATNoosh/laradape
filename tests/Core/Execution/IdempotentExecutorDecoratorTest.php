<?php

use Core\Execution\Executor;
use Core\Execution\IdempotentExecutorDecorator;
use Core\Execution\JobReport;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Repository\InMemoryJobExecutionStore;
use Core\ValueObject\JobId;
use PHPUnit\Framework\TestCase;

// Test Doubles - Steps
final class IdempotentExecutorDecoratorTest extends TestCase
{
    public function test_job_is_executed_only_once(): void
    {
        $calls = 0;

        $executor = new class($calls) implements Executor {
            public function __construct(private int &$calls) {}

            public function execute(Job $job): JobReport
            {
                $this->calls++;
                return JobReport::completed($job->id());
            }
        };

        $store = new InMemoryJobExecutionStore();

        $decorated = new IdempotentExecutorDecorator($executor, $store);

        $job = $this->job();

        $decorated->execute($job);
        $decorated->execute($job);

        $this->assertSame(1, $calls);
    }
    public function test_concurrent_execution_is_skipped(): void
    {
        $store = new InMemoryJobExecutionStore();
        $job   = $this->job();

        $store->acquire($job->id()); // simulate another worker

        $executor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                throw new \RuntimeException('Should not be executed');
            }
        };

        $decorated = new IdempotentExecutorDecorator($executor, $store);

        $report = $decorated->execute($job);

        $this->assertTrue($report->isSkipped());
    }

    public function test_failed_job_is_not_marked_completed(): void
    {
        $executor = new class implements Executor {
            public function execute(Job $job): JobReport
            {
                return JobReport::failed($job->id(), 'fail');
            }
        };

        $store = new InMemoryJobExecutionStore();
        $job   = $this->job();

        $decorated = new IdempotentExecutorDecorator($executor, $store);

        $decorated->execute($job);

        $this->assertFalse($store->isCompleted($job->id()));
    }

    private function job(): Job
    {
        return new Job(
            new JobId('job-1'),
            $this->createMock(Pipeline::class)
        );
    }
}
