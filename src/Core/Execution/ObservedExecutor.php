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
        $this->observer->executionStarted();

        $report = $this->inner->execute($job);

        $this->observer->executionFinished(
            $report->result()
        );

        return $report; 
    }
}
