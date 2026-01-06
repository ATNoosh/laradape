<?php

namespace App\Execution;

use Core\Execution\ExecutorObserver;
use Core\Job\JobResult;
use Illuminate\Support\Facades\Log;

final class LaravelExecutorObserver implements ExecutorObserver
{
    public function executionStarted(): void
    {
        // Log execution start if needed
        // For now, this is a no-op implementation
        // Can be extended to log metrics, send events, etc.
    }

    public function executionFinished(JobResult $result): void
    {
        // Log execution finish if needed
        // For now, this is a no-op implementation
        // Can be extended to log metrics, send events, etc.
    }
}

