<?php

namespace App\Execution;

use Core\Execution\RetryContext;
use Core\Execution\RetryMetricsObserver;
use Core\Execution\RetryResult;

final class LaravelRetryMetricsObserver implements RetryMetricsObserver
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

    public function export(): array
    {
        return [
            'retry_scheduled_total' => $this->scheduled,
            'retry_aborted_total'   => $this->aborted,
            'retry_succeeded_total' => $this->succeeded,
        ];
    }
}
