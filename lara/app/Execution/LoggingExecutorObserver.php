<?php

namespace App\Execution;

use Core\Execution\ExecutorObserver;
use Core\Execution\JobReport;
use Illuminate\Support\Facades\Log;

final class LoggingExecutorObserver implements ExecutorObserver
{
    public function jobStarted(string $jobId): void
    {
        Log::info("Job {$jobId} started.");
    }

    public function jobFinished(JobReport $report, float $durationMs): void
    {
        Log::info("Job {$report->jobId()->value()} finished in {$durationMs}ms with state {$report->result()->state()}.");
    }

    public function jobFailed(JobReport $report, float $durationMs): void
    {
        $message = $report->result()->message() ?? 'Unknown error';
        Log::warning("Job {$report->jobId()->value()} failed in {$durationMs}ms with message: {$message}");
    }
}

