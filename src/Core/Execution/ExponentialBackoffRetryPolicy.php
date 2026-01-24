<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class ExponentialBackoffRetryPolicy implements RetryPolicy
{
    public function __construct(
        private int $maxAttempts = 5,
        private int $baseDelayMs = 100,
        private int $maxDelayMs = 30_000
    ) {}

    public function decide(RetryContext $context): RetryResult {
        if ($context->exception instanceof NonRetryableException) {
            return RetryResult::doNotRetry('Non-retryable exception');
        }

        if ($context->attempt >= $this->maxAttempts) {
            return RetryResult::doNotRetry('Max retry attempts reached');
        }

        if ($context->result->isCompleted()) {
            return RetryResult::doNotRetry('Job completed successfully');
        }

        $delay = $this->baseDelayMs * (2 ** ($context->attempt - 1));
        $delay = min($delay, $this->maxDelayMs);

        return RetryResult::retry($delay, 'Retry attempt {$attempt} with {$delay}ms delay');
    }
}
