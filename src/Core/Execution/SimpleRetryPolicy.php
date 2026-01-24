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

    public function decide(RetryContext $context): RetryResult {
        if ($context->attempt >= $this->maxAttempts) {
            return RetryResult::doNotRetry('Max attempts done!');
        }

        if ($context->result->isFailed()) {
            return RetryResult::retry($this->delayMs);
        }

        return RetryResult::doNotRetry('Simple dont retry');
    }
}

