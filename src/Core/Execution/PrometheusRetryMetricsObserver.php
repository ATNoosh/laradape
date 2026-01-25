<?php
namespace Core\Execution;

use Core\Execution\RetryContext;
use Core\Execution\RetryMetricsObserver;
use Core\Execution\RetryResult;
use Core\Job\Job;

final class PrometheusRetryMetricsObserver implements RetryMetricsObserver
{
    private int $scheduled = 0;
    private int $stopped = 0;
    private int $succeeded = 0;

    public function retryScheduled(RetryContext $context, RetryResult $decision): void
    {
        $this->scheduled++;
    }

    public function retryAborted(RetryContext $context, RetryResult $decision): void
    {
        $this->stopped++;
    }

    public function retrySucceeded(RetryContext $context): void
    {
        $this->succeeded++;
    }

    public function export(): array
    {
        return [
            'retry_scheduled_total' => $this->scheduled,
            'retry_stopped_total'   => $this->stopped,
            'retry_succeeded_total' => $this->succeeded,
        ];
    }
}
