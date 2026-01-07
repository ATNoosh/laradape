<?php

namespace Core\Execution;

use Core\Execution\JobReport;
use Core\Job\Job;
use Throwable;

interface ExecutorObserver
{
    public function jobStarted(Job $job): void;

    public function jobFinished(
        Job $job,
        JobReport $report,
        float $durationMs
    ): void;

    public function jobFailed(
        Job $job,
        Throwable $e,
        float $durationMs
    ): void;
}
