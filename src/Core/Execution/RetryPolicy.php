<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Throwable;

interface RetryPolicy
{
    public function decide(
        Job $job,
        JobResult $result,
        ?Throwable $exception = null,
        int $attempt = 1
    ): RetryResult;
}
