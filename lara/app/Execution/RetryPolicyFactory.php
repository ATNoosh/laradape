<?php

namespace App\Execution;

use Core\Execution\RetryPolicy;
use Core\Execution\SimpleRetryPolicy;
use Illuminate\Contracts\Config\Repository;

final class RetryPolicyFactory
{
    public function __construct(
        private Repository $config
    ) {}

    public function make(): RetryPolicy
    {
        return new SimpleRetryPolicy(
            maxAttempts: (int) $this->config->get('jobs.retry.max_attempts', 3),
            delayMs: (int) $this->config->get('jobs.retry.delay_ms', 0)
        );
    }
}
