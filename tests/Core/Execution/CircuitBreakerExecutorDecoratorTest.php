<?php

use Core\Execution\CircuitBreaker;
use Core\Execution\CircuitBreakerExecutorDecorator;
use Core\Execution\CircuitState;
use Core\Execution\Executor;
use Core\Execution\JobReport;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;
use PHPUnit\Framework\TestCase;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Job\JobState;

final class DummyPipeline implements Pipeline
{
    public function name(): string
    {
        return 'dummy';
    }

    public function run(PipelineContext $context): PipelineResult
    {
        return PipelineResult::success();
    }
}

final class SucceedingExecutor implements Executor
{
    public function execute(Job $job): JobReport
    {
        return JobReport::completed($job->id());
    }
}

final class FailingExecutor implements Executor
{
    public function execute(Job $job): JobReport
    {
        return JobReport::failed($job->id(), 'execution failed');
    }
}
final class FlakyExecutor implements Executor
{
    private int $attempts = 0;

    public function execute(Job $job): JobReport
    {
        $this->attempts++;

        if ($this->attempts < 2) {
            return JobReport::failed($job->id(), 'temporary failure');
        }

        return JobReport::completed($job->id());
    }
}


final class CircuitBreakerExecutorDecoratorTest extends TestCase
{
    public function test_circuit_opens_after_consecutive_failures(): void
    {

        $job = new Job(
            new JobId('dummy-job'),
            new DummyPipeline()
        );

        $executor = new FailingExecutor();
        $breaker = new CircuitBreaker(2, 60);

        $decorated = new CircuitBreakerExecutorDecorator($executor, $breaker);

        $decorated->execute($job);
        $decorated->execute($job);

        $report = $decorated->execute($job);
        $result = $report->result();

        $this->assertSame(JobState::FAILED, $result->state());
        $this->assertSame(CircuitBreakerExecutorDecorator::OPEN_MESSAGE, $result->message());
    }
    public function test_circuit_allows_execution_after_cooldown(): void
    {

        $job = new Job(
            new JobId('dummy-job'),
            new DummyPipeline()
        );

        $executor = new SucceedingExecutor();
        $breaker = new CircuitBreaker(1, 0);

        $decorated = new CircuitBreakerExecutorDecorator($executor, $breaker);

        $decorated->execute($job); // fail → open

        $report = $decorated->execute($job);
        $result = $report->result();

        $this->assertSame(JobState::COMPLETED, $result->state());
    }
    public function test_success_resets_failure_count(): void
    {

        $job = new Job(
            new JobId('dummy-job'),
            new DummyPipeline()
        );

        $executor = new FlakyExecutor(); // fail once, then success
        $breaker = new CircuitBreaker(2, 60);

        $decorated = new CircuitBreakerExecutorDecorator($executor, $breaker);

        $decorated->execute($job); // fail
        $report = $decorated->execute($job); // success
        $result = $report->result();

        $this->assertSame(JobState::COMPLETED, $result->state());
        $this->assertSame(CircuitState::CLOSED, $breaker->state());
    }
}
