<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

final class SimpleRetryPolicy implements RetryPolicy
{
    public function __construct(
        private int $maxAttempts = 3,
        private int $delayMs = 0
    ) {}

    public function decide(
        Job $job,
        JobResult $result,
        ?Throwable $exception = null,
        int $attempt = 1
    ): RetryResult {
        if ($attempt >= $this->maxAttempts) {
            return RetryResult::doNotRetry();
        }

        if ($result->isFailed()) {
            return RetryResult::retry($this->delayMs);
        }

        return RetryResult::doNotRetry();
    }
}

