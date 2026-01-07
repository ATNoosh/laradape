<?php

namespace Core\Execution;

use Core\Job\Job;

interface RetryObserver
{
    public function retryScheduled(
        Job $job,
        int $attempt,
        int $delayMs
    ): void;
}

