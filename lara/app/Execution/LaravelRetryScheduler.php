<?php

namespace App\Execution;

use Core\Execution\RetryScheduler;
use Core\Execution\RetryCommand;
use App\Jobs\RetryJob;
use Illuminate\Contracts\Bus\Dispatcher;

final class LaravelRetryScheduler implements RetryScheduler
{
    public function __construct(
        private Dispatcher $bus
    ) {}

    public function schedule(RetryCommand $command): void
    {
        $retry = new RetryJob($command);

        $delaySeconds = (int) ceil($command->delayMs() / 1000);
        if ($delaySeconds > 0) {
            $retry->delay($delaySeconds);
        }

        $this->bus->dispatch($retry);
    }
}
