<?php

namespace Core\Execution;

use Core\Job\JobResult;

interface RetryPolicy
{
    public function shouldRetry(JobResult $result, int $attempt): bool;
}
