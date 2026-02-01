<?php

namespace Core\Repository;

use Core\ValueObject\JobId;

interface JobExecutionStore
{
    /**
     * Try to acquire execution lock for a job.
     * Returns false if already locked by another worker.
     */
    public function acquire(JobId $jobId): bool;

    /**
     * Release execution lock.
     */
    public function release(JobId $jobId): void;

    /**
     * Whether this job has already completed successfully.
     */
    public function isCompleted(JobId $jobId): bool;

    /**
     * Mark job as completed (idempotent).
     */
    public function markCompleted(JobId $jobId): void;
}
