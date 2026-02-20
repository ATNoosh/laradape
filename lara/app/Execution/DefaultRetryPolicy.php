<?php

namespace App\Execution;

use Core\Execution\RetryContext;
use Core\Execution\RetryPolicy;
use Core\Execution\RetryResult;
use Illuminate\Contracts\Config\Repository;

final class DefaultRetryPolicy implements RetryPolicy
{
    public function __construct(
        private Repository $config
    ) {}

    public function decide(RetryContext $context): RetryResult
    {
        $maxAttempts = (int) $this->config->get('jobs.retry.max_attempts', 3);
        $delayMs = (int) $this->config->get('jobs.retry.delay_ms', 0);

        if ($context->attempt >= $maxAttempts) {
            return RetryResult::doNotRetry('Max retry attempts reached');
        }

        if ($context->isFailure()) {
            return RetryResult::retry($delayMs, 'Retryable failure');
        }

        return RetryResult::doNotRetry('Not retryable');
    }
}
