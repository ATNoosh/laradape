<?php

namespace Core\Execution;

use Core\Job\Job;

final class ObservedExecutor implements Executor
{
    public function __construct(
        private Executor $inner,
        private ExecutorObserver $observer
    ) {}

    public function execute(Job $job): JobReport
    {
        $this->observer->jobStarted($job);

        $report = $this->inner->execute($job);

        $this->observer->jobFinished(
            $job,
            $report,
            1
        );

        return $report;
    }
}
