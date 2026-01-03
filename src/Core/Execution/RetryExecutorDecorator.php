<?php

namespace Core\Execution;

use Core\Job\Job;

final class RetryExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private RetryPolicy $policy
    ) {}

    public function execute(Job $job): JobReport
    {
        $attempt = 1;

        while (true) {
            $report = $this->inner->execute($job);
            $result = $report->result();

            if (! $this->policy->shouldRetry($result, $attempt)) {
                return $report;
            }

            $attempt++;
        }
    }
}
