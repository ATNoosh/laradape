<?php

namespace Core\Execution;

use Core\Job\Job;
use Throwable;

final class TimingExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private ExecutorObserver $observer
    ) {}

    public function execute(Job $job): JobReport
    {
        $start = microtime(true);

        $this->observer->jobStarted($job);

        try {
            $report = $this->inner->execute($job);

            $durationMs = (microtime(true) - $start) * 1000;

            $this->observer->jobFinished(
                $job,
                $report,
                $durationMs
            );

            return $report;

        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $start) * 1000;

            $this->observer->jobFailed(
                $job,
                $e,
                $durationMs
            );

            throw $e; // ❗ decorator must not swallow exceptions
        }
    }
}
