<?php

namespace Core\Execution;

use Core\Job\Job;

interface RetryObserver
{
    public function retryScheduled(RetryContext $context, RetryResult $decision): void;

    public function retryAborted(RetryContext $context, RetryResult $decision): void;

    public function retrySucceeded(RetryContext $context): void;
}
