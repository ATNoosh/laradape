<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class ExponentialBackoffRetryPolicy implements RetryPolicy
{
    public function __construct(
        private int $maxAttempts = 5,
        private int $baseDelayMs = 100
    ) {}

    public function decide(
        Job $job,
        JobResult $result,
        ?Throwable $exception = null,
        int $attempt = 1
    ): RetryResult {
        if ($exception instanceof NonRetryableException) {
            return RetryResult::doNotRetry('Non-retryable exception');
        }

        if ($attempt >= $this->maxAttempts) {
            return RetryResult::doNotRetry('Max retry attempts reached');
        }

        if ($result->isCompleted()) {
            return RetryResult::doNotRetry('Job completed successfully');
        }

        $delay = $this->baseDelayMs * (2 ** ($attempt - 1));

        return RetryResult::retry($delay, 'Exponential backoff retry');
    }
}
