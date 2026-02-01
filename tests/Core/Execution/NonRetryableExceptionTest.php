<?php

use Core\Execution\RetryContext;
use PHPUnit\Framework\TestCase;
use Core\Execution\ExponentialBackoffRetryPolicy;
use Core\Execution\RetryResult;
use Core\Job\JobResult;
use Core\Job\Job;
use Core\Execution\Exceptions\ValidationException;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

final class NonRetryableExceptionTest extends TestCase
{
    public function test_policy_does_not_retry_non_retryable_exception(): void
    {
        $policy = new ExponentialBackoffRetryPolicy(
            maxAttempts: 5,
            baseDelayMs: 100,
            maxDelayMs: 1000
        );

        $job = $this->realJob();
        $result = JobResult::failed('validation error');
        $exception = new ValidationException('Invalid input');
        $startedAt = microtime(true);

        $decision = $policy->decide(new RetryContext($job, $result, $exception, 1, $startedAt));

        $this->assertFalse($decision->shouldRetry());
        $this->assertStringContainsString(
            'Non-retryable',
            $decision->reason()
        );
    }

    private function realJob(): Job
    {
        return new Job(
            new JobId('job-x'),
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
    }
}
