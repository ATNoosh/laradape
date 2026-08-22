<?php

namespace Core\Repository;

use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Job\JobStatus;
use Core\ValueObject\JobId;
use RuntimeException;

final class InMemoryJobRepository implements JobRepository
{
    /** @var array<string, Job> */
    private array $jobs = [];

    /** @var array<string, JobStatus> */
    private array $statuses = [];

    public function save(Job $job): void
    {
        $this->jobs[$job->id()->value()] = $job;
        $this->statuses[$job->id()->value()] = JobStatus::Pending;
    }

    public function get(JobId $id): Job
    {
        if (! isset($this->jobs[$id->value()])) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }

        return $this->jobs[$id->value()];
    }

    public function exists(JobId $id): bool
    {
        return isset($this->jobs[$id->value()]);
    }

    public function updateStatus(JobId $id, JobStatus $status): void
    {
        if (! $this->exists($id)) {
            throw new RuntimeException("Job not found: {$id->value()}");
        }

        $this->statuses[$id->value()] = $status;
    }

    public function status(JobId $id): ?JobStatus
    {
        return $this->statuses[$id->value()] ?? null;
    }
}
