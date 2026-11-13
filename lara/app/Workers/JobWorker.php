<?php

namespace App\Workers;

use Core\Execution\Executor;
use Core\ValueObject\JobId;
use Core\Execution\JobReport;
use Core\Execution\JobRepository;
use Core\Job\JobStatus;

final class JobWorker
{
    public function __construct(
        private Executor $executor,
        private JobRepository $jobs
    ) {}

    public function run(JobId $jobId): JobReport
    {
        $job = $this->jobs->get($jobId);
        if ($this->jobs->status($jobId) === JobStatus::Cancelled) {
            return JobReport::skipped($jobId);
        }

        $this->jobs->updateStatus($jobId, JobStatus::Running);

        try {
            $report = $this->executor->execute($job);
        } catch (\Throwable $exception) {
            $this->jobs->updateStatus($jobId, JobStatus::Failed, $exception->getMessage());
            throw $exception;
        }

        if (! $report->isSkipped()) {
            $this->jobs->updateStatus(
                $jobId,
                $report->isSuccessful() ? JobStatus::Completed : JobStatus::Failed,
                $report->result()?->message()
            );
        }

        return $report;
    }
}
