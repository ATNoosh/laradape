<?php

namespace Core\Execution;

use Core\Job\JobResult;

interface ExecutorObserver
{
    public function executionStarted(): void;
    public function executionFinished(JobResult $result): void;
}
