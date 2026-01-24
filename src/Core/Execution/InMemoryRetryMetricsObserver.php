<?php

namespace Core\Execution;

use Core\Job\Job;

final class InMemoryRetryMetricsObserver implements RetryMetricsObserver
{
    public int $scheduled = 0;
    public int $aborted   = 0;
    public int $succeeded = 0;

    public function retryScheduled(RetryContext $context, RetryResult $decision): void
    {
        $this->scheduled++;
    }

    public function retryAborted(RetryContext $context, RetryResult $decision): void
    {
        $this->aborted++;
    }

    public function retrySucceeded(RetryContext $context): void
    {
        $this->succeeded++;
    }
}

