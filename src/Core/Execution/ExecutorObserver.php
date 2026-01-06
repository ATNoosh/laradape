<?php

namespace Core\Execution;

use Core\Execution\JobReport;

interface ExecutorObserver
{
    public function jobStarted(string $jobId): void;

    public function jobFinished(JobReport $report, float $durationMs): void;

    public function jobFailed(JobReport $report, float $durationMs): void;
}
