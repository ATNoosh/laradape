<?php

namespace Core\Execution;

use Core\Job\Job;

interface RetryObserver
{
    public function retryScheduled(Job $job, int $attempt, int $delayMs, string $reason): void;
    public function retryStopped(Job $job, int $attempt, string $reason): void;
}
