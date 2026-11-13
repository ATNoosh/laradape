<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;

interface JobRepository
{
    public function get(JobId $id): Job;

    public function save(Job $job): void;

    public function saveIfMissing(Job $job): bool;

    public function exists(JobId $id): bool;

    public function status(JobId $id): ?JobStatus;

    public function updateStatus(JobId $id, JobStatus $status, ?string $error = null): void;

    public function cancelIfPending(JobId $id, ?string $reason = null): bool;

    public function requestCancellation(JobId $id, ?string $reason = null): bool;
}
