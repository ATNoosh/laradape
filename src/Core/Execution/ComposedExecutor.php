<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Repository\JobExecutionStore;

final class ComposedExecutor implements Executor
{
    private Executor $executor;

    public function __construct(
        Executor $baseExecutor,
        RetryPolicy $retryPolicy,
        RetryScheduler $scheduler,
        JobExecutionStore $executionStore,
        ?RetryMetricsObserver $metrics = null
    ) {
        $retrying = new RetryExecutorDecorator(
            $baseExecutor,
            $retryPolicy,
            $scheduler,
            observer: $metrics
        );

        $this->executor = new IdempotentExecutorDecorator(
            $retrying,
            $executionStore
        );
    }

    public function execute(Job $job): JobReport
    {
        return $this->executor->execute($job);
    }
}
