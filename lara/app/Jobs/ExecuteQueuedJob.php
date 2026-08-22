<?php

namespace App\Jobs;

use App\Workers\JobWorker;
use Core\ValueObject\JobId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExecuteQueuedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $jobId) {}

    public function handle(JobWorker $worker): void
    {
        $report = $worker->run(new JobId($this->jobId));

        if ($report->isFailed() || $report->isTimedOut()) {
            throw new \RuntimeException($report->result()->message());
        }
    }
}
