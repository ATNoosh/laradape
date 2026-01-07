<?php

namespace App\Execution;

use Core\Execution\ExecutorObserver;
use Core\Execution\JobReport;
use Core\Job\Job;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LoggingExecutorObserver implements ExecutorObserver
{
    public function jobStarted(Job $job): void
    {
        Log::info('job.started', [
            'job_id' => $job->id()->value(),
        ]);
    }

    public function jobFinished(
        Job $job,
        JobReport $report,
        float $durationMs
    ): void {
        Log::info('job.finished', [
            'job_id'      => $job->id()->value(),
            'state'       => $report->result()->state(),
            'duration_ms' => round($durationMs, 2),
        ]);
    }

    public function jobFailed(Job $job, Throwable $e, float $durationMs): void
    {
        Log::error('job.failed', [
            'job_id'    => $job->id()->value(),
            'exception' => $e::class,
            'message'   => $e->getMessage(),
            'duration'  => $durationMs,
            'trace'     => config('app.debug') ? $e->getTraceAsString() : null,
        ]);
    }
}
