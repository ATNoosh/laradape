<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

interface RetryPolicy
{
    public function decide(RetryContext $context): RetryResult;
}
