<?php

namespace App\Jobs;

use Core\ValueObject\JobId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Core\Execution\RetryCommand;
use Core\Execution\RetryExecutorDecorator;
use Core\Repository\JobRepository;

final class RetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public RetryCommand $command
    ) {}

    public function handle(
        RetryExecutorDecorator $executor,
        JobRepository $jobs
    ): void {
        $job = $jobs->get(new JobId($this->command->jobId()));

        $executor->execute($job, $this->command);
    }
}
