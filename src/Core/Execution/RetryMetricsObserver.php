<?php

namespace Core\Execution;

interface RetryMetricsObserver
{
    public function retryScheduled(RetryContext $context, RetryResult $decision): void;

    public function retryAborted(RetryContext $context, RetryResult $decision): void;

    public function retrySucceeded(RetryContext $context): void;
}
