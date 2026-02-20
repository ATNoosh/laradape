<?php

namespace Core\Execution;

use Core\Job\Job;

interface JobDispatcherInterface
{
    public function dispatch(Job $job, ?RetryCommand $command = null): JobReport;
}
