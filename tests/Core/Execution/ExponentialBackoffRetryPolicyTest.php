<?php

use Core\Execution\ExponentialBackoffRetryPolicy;
use Core\Execution\RetryContext;
use PHPUnit\Framework\TestCase;
use Core\Job\Job;
use Core\Job\JobResult;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\ValueObject\JobId;

// Test Doubles - Steps
final class ExponentialBackoffRetryPolicyTest extends TestCase
{
    public function test_exponential_delay_grows(): void
    {
        $policy = new ExponentialBackoffRetryPolicy(5, 100, 10_000);

        $job = $this->realJob();
        $result = JobResult::failed('Failed Message');
        $startedAt = microtime(true);

        $r1 = $policy->decide(new RetryContext($job, $result, null, 1, $startedAt));
        $r2 = $policy->decide(new RetryContext($job, $result, null, 2, $startedAt));
        $r3 = $policy->decide(new RetryContext($job, $result, null, 3, $startedAt));

        $this->assertSame(100, $r1->delayMs());
        $this->assertSame(200, $r2->delayMs());
        $this->assertSame(400, $r3->delayMs());
    }

    public function test_stops_after_max_attempts(): void
    {
        $policy = new ExponentialBackoffRetryPolicy(3, 100, 1000);

        $job = $this->realJob();
        $result = JobResult::failed('Failed Message');
        $startedAt = microtime(true);

        $r = $policy->decide(new RetryContext($job, $result, null, 4, $startedAt));

        $this->assertFalse($r->shouldRetry());
    }
    
    private function realJob(): Job
    {
        return new Job(
            new JobId('job-x'),
            new class implements Pipeline {
                public function run(PipelineContext $context): PipelineResult
                {
                    return PipelineResult::success();
                }
            }
        );
    }
}
