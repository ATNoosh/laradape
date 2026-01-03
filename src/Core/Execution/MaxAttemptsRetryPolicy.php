<?php

namespace Core\Execution;

use Core\Job\JobResult;
use Core\Job\JobState;

final class MaxAttemptsRetryPolicy implements RetryPolicy
{
    public function __construct(
        private int $maxAttempts
    ) {}

    public function shouldRetry(JobResult $result, int $attempt): bool
    {
        if (! $result->isFailed()) {
            return false;
        }

        return $attempt < $this->maxAttempts;
    }
}
