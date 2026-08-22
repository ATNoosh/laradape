<?php

namespace Core\Execution;

use Core\Job\Job;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;

interface JobRepository
{
    public function get(JobId $id): Job;

    public function save(Job $job): void;

    public function exists(JobId $id): bool;

    public function status(JobId $id): ?JobStatus;

    public function updateStatus(JobId $id, JobStatus $status): void;
}
