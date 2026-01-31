<?php

namespace App\Execution;

use Core\Execution\RetryScheduler;
use Core\Execution\RetryCommand;
use App\Jobs\RetryJob;

final class LaravelRetryScheduler implements RetryScheduler
{
    public function schedule(RetryCommand $command): void
    {
        RetryJob::dispatch($command)
            ->delay(now()->addMilliseconds($command->delayMs()));
    }
}
