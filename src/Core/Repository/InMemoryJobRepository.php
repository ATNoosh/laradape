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

    public function saveIfMissing(Job $job): bool
    {
        if ($this->exists($job->id())) {
            return false;
        }

        $this->save($job);

        return true;
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

    public function updateStatus(JobId $id, JobStatus $status, ?string $error = null): void
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

    public function cancelIfPending(JobId $id, ?string $reason = null): bool
    {
        if ($this->status($id) !== JobStatus::Pending) {
            return false;
        }

        $this->statuses[$id->value()] = JobStatus::Cancelled;

        return true;
    }

    public function requestCancellation(JobId $id, ?string $reason = null): bool
    {
        if (! in_array($this->status($id), [JobStatus::Pending, JobStatus::Running], true)) {
            return false;
        }

        $job = $this->get($id);
        $payload = $job->payload();
        $payload['cancellation_requested'] = true;
        $payload['cancellation_reason'] = $reason;
        $this->jobs[$id->value()] = new Job(
            $job->id(),
            $job->pipeline(),
            $job->state(),
            $job->queue(),
            $payload
        );

        return true;
    }
}
