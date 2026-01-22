<?php

namespace Core\Execution;

use Core\Job\Job;

final class InMemoryRetryMetricsObserver implements RetryObserver
{
    public int $scheduled = 0;
    public int $stopped = 0;

    public function retryScheduled(Job $job, int $attempt, int $delayMs, string $reason): void
    {
        $this->scheduled++;
    }

    public function retryStopped(Job $job, int $attempt, string $reason): void
    {
        $this->stopped++;
    }
}

