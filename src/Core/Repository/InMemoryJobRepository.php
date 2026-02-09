<?php

namespace Core\Repository;

use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\ValueObject\JobId;
use RuntimeException;

final class InMemoryJobRepository implements JobRepository
{
    /** @var array<string, Job> */
    private array $jobs = [];

    public function save(Job $job): void
    {
        $this->jobs[$job->id()->value()] = $job;
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
}
