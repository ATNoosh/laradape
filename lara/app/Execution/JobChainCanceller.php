<?php

namespace App\Execution;

use Core\Execution\JobRepository;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;

final class JobChainCanceller
{
    public function __construct(
        private JobRepository $jobs
    ) {}

    public function cancel(JobChainDetails $chain, string $reason = 'Cancelled by request'): int
    {
        $cancelled = 0;
        foreach ($chain->jobs as $job) {
            if ($job->status !== JobStatus::Pending->value) {
                if ($job->status === JobStatus::Running->value) {
                    $this->jobs->requestCancellation(new JobId($job->jobId), $reason);
                }

                continue;
            }

            if ($this->jobs->cancelIfPending(new JobId($job->jobId), $reason)) {
                $cancelled++;
            } else {
                $this->jobs->requestCancellation(new JobId($job->jobId), $reason);
            }
        }

        return $cancelled;
    }
}
