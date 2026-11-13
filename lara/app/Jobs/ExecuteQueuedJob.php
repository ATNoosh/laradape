<?php

namespace App\Jobs;

use App\Workers\JobWorker;
use App\Execution\PupgraderHopScheduler;
use Core\ValueObject\JobId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExecuteQueuedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public function __construct(public string $jobId)
    {
        $this->timeout = (int) config('jobs.timeout_seconds', 600);
    }

    public function handle(JobWorker $worker, PupgraderHopScheduler $scheduler): void
    {
        $report = $worker->run(new JobId($this->jobId));

        if ($report->isSkipped()) {
            return;
        }

        if ($report->isFailed() || $report->isTimedOut()) {
            throw new \RuntimeException($report->result()->message());
        }

        if ($report->isSuccessful()) {
            $scheduler->scheduleNext(new JobId($this->jobId));
        }
    }
}
