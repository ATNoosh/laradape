<?php

namespace App\Workers;

use Core\Execution\Executor;
use Core\ValueObject\JobId;
use Core\Execution\JobReport;
use Core\Execution\JobRepository;

final class JobWorker
{
    public function __construct(
        private Executor $executor,
        private JobRepository $jobs
    ) {}

    public function run(JobId $jobId): JobReport
    {
        $job = $this->jobs->get($jobId);

        return $this->executor->execute($job);
    }
}
