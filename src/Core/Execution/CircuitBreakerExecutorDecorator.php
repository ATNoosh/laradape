<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobResult;
use Core\Job\JobState;

final class CircuitBreakerExecutorDecorator implements Executor
{
    public const OPEN_MESSAGE = 'Circuit breaker is open.';
    public function __construct(
        private Executor $inner,
        private CircuitBreaker $breaker
    ) {}

    public function execute(Job $job): JobReport
    {
        if (! $this->breaker->canExecute()) {
            return JobReport::failed($job->id(), self::OPEN_MESSAGE);
        }

        $report = $this->inner->execute($job);
        $result = $report->result();

        if ($result->isCompleted()) {
            $this->breaker->reportSuccess();
        } else {
            $this->breaker->reportFailure();
        }

        return $report;
    }
}
