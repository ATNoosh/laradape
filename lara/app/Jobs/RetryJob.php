<?php

namespace App\Jobs;

use Core\Execution\JobRepository;
use Core\Execution\JobDispatcherInterface;
use Core\ValueObject\JobId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Core\Execution\RetryCommand;

final class RetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public RetryCommand $command
    ) {}

    public function handle(
        JobDispatcherInterface $dispatcher,
        JobRepository $jobs
    ): void {
        $job = $jobs->get(new JobId($this->command->jobId()));

        $dispatcher->dispatch($job, $this->command);
    }
}
