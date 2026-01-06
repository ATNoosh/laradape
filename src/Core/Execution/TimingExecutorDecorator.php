<?php

namespace Core\Execution;

use Core\Job\Job;

final class TimingExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private ExecutorObserver $observer
    ) {}

    public function execute(Job $job): JobReport
    {
        $start = microtime(true);
        $this->observer->jobStarted($job->id()->value());

        $report = $this->inner->execute($job);

        $duration = (microtime(true) - $start) * 1000;

        if ($report->result()->isFailed()) {
            $this->observer->jobFailed($report, $duration);
        } else {
            $this->observer->jobFinished($report, $duration);
        }

        return $report;
    }
}
