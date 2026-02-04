<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Repository\JobExecutionStore;

final class IdempotentExecutorDecorator implements Executor
{
    public function __construct(
        private Executor $inner,
        private JobExecutionStore $store
    ) {}

    public function execute(Job $job): JobReport
    {
        $jobId = $job->id();

        // 1️⃣ already completed → short-circuit
        if ($this->store->isCompleted($jobId)) {
            return JobReport::completed($jobId);
        }

        // 2️⃣ try acquire lock
        if (! $this->store->acquire($jobId)) {
            return JobReport::skipped($jobId);
        }

        try {
            $report = $this->inner->execute($job);

            // 3️⃣ mark completed on success
            if ($report->result()->isCompleted()) {
                $this->store->markCompleted($jobId);
            }

            return $report;
        } finally {
            // 4️⃣ always release lock
            $this->store->release($jobId);
        }
    }
}
