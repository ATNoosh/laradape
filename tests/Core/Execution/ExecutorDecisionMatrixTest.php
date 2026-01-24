<?php

use PHPUnit\Framework\TestCase;
use Core\Execution\DefaultExecutor;
use Core\Execution\TimeBox;
use Core\Execution\RetryExecutorDecorator;
use Core\Pipeline\DefaultPipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineStep;
use Core\Pipeline\PipelineResult;
use Core\Job\Job;
use Core\Job\JobState;
use Core\ValueObject\JobId;
use Core\Error\RecoverableException;
use Core\Error\FatalException;
use Core\Execution\RetryContext;
use Core\Execution\RetryPolicy;
use Core\Execution\RetryResult;
use Core\Job\JobResult;

// Test Doubles - Steps
final class DecisionMatrixSuccessfulStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        $context->set('done', true);
        return PipelineResult::success();
    }
}

final class DecisionMatrixFlakyRecoverableStep implements PipelineStep
{
    private int $attempts = 0;

    public function execute(PipelineContext $context): PipelineResult
    {
        $this->attempts++;
        if ($this->attempts < 2) {
            throw new RecoverableException('Temporary failure');
        }
        return PipelineResult::success();
    }
}

final class DecisionMatrixAlwaysFailingRecoverableStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        throw new RecoverableException('Always fails');
    }
}

final class DecisionMatrixFatalStep implements PipelineStep
{
    public function execute(PipelineContext $context): PipelineResult
    {
        throw new FatalException('Fatal error');
    }
}

// Test Doubles - Retry Policies
final class SingleRetryPolicy implements RetryPolicy
{
    public function decide(RetryContext $context): RetryResult {
        if ($context->attempt >= 2) {
            return RetryResult::doNotRetry('Number of attepts exceeds from 2');
        }
        if ($context->result->isFailed()) {
            return RetryResult::retry(0);
        }
        return RetryResult::doNotRetry('Simple Reason');
    }
}

final class NoRetryPolicy implements RetryPolicy
{
    public function decide(RetryContext $context): RetryResult {
        return RetryResult::doNotRetry('Do not retry');
    }
}

final class ExecutorDecisionMatrixTest extends TestCase
{
    public function test_executor_times_out_before_pipeline_runs(): void
    {
        $executor = new DefaultExecutor(
            new TimeBox(time() - 100, 1)
        );

        $job = new Job(
            new JobId('job-timeout-before'),
            new DefaultPipeline([new DecisionMatrixSuccessfulStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::TIMED_OUT,
            $report->result()->state()
        );
    }

    public function test_executor_times_out_after_pipeline_runs(): void
    {
        $startTime = time();
        $executor = new DefaultExecutor(
            new TimeBox($startTime, 1) // expires after 1 second
        );

        $job = new Job(
            new JobId('job-timeout-after'),
            new DefaultPipeline([
                new class implements PipelineStep {
                    public function execute(PipelineContext $context): PipelineResult
                    {
                        sleep(2); // Wait longer than TimeBox allows
                        return PipelineResult::success();
                    }
                }
            ])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::TIMED_OUT,
            $report->result()->state()
        );
    }

    public function test_executor_completes_successfully(): void
    {
        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $job = new Job(
            new JobId('job-success'),
            new DefaultPipeline([new DecisionMatrixSuccessfulStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::COMPLETED,
            $report->result()->state()
        );
    }

    public function test_executor_recovers_from_recoverable_exception(): void
    {
        $baseExecutor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $executor = new RetryExecutorDecorator(
            $baseExecutor,
            new SingleRetryPolicy()
        );

        $job = new Job(
            new JobId('job-retry-ok'),
            new DefaultPipeline([new DecisionMatrixFlakyRecoverableStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::COMPLETED,
            $report->result()->state()
        );
    }

    public function test_executor_fails_after_retry_exhausted(): void
    {
        $baseExecutor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $executor = new RetryExecutorDecorator(
            $baseExecutor,
            new NoRetryPolicy()
        );

        $job = new Job(
            new JobId('job-retry-fail'),
            new DefaultPipeline([new DecisionMatrixAlwaysFailingRecoverableStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::FAILED,
            $report->result()->state()
        );
    }

    public function test_executor_fails_on_fatal_exception(): void
    {
        $executor = new DefaultExecutor(
            new TimeBox(time(), 60)
        );

        $job = new Job(
            new JobId('job-fatal'),
            new DefaultPipeline([new DecisionMatrixFatalStep()])
        );

        $report = $executor->execute($job);

        $this->assertSame(
            JobState::FAILED,
            $report->result()->state()
        );
    }
}
