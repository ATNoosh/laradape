<?php

namespace Core\Execution;

use Core\Job\Job;
use Throwable;

/**
 * ExecutorObserver implementation that translates executor lifecycle events
 * into metrics via a MetricsCollector.
 *
 * This class is completely framework-agnostic.
 */
final class MetricsExecutorObserver implements ExecutorObserver
{
    public function __construct(
        private MetricsCollector $metrics
    ) {}

    public function jobStarted(Job $job): void
    {
        $this->metrics->increment('jobs_started_total');
    }

    public function jobFinished(
        Job $job,
        JobReport $report,
        float $durationMs
    ): void {
        $state = $report->result()->state();

        $this->metrics->increment('jobs_finished_total', [
            'state' => $state,
        ]);

        $this->metrics->observe('job_duration_ms', $durationMs, [
            'state' => $state,
        ]);
    }

    public function jobFailed(
        Job $job,
        Throwable $e,
        float $durationMs
    ): void {
        $this->metrics->increment('jobs_failed_total');

        $this->metrics->observe('job_duration_ms', $durationMs, [
            'state' => 'failed',
        ]);
    }
}


